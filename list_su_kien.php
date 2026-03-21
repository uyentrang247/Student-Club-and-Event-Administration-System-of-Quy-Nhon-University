<?php 
session_start();
require 'site.php';
require_once(__DIR__ . "/assets/database/connect.php");

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vui lòng đăng nhập!";
    header("Location: login.php");
    exit;
}

$club_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($club_id <= 0) {
    $_SESSION['error'] = "ID câu lạc bộ không hợp lệ";
    header("Location: myclub.php");
    exit;
}

$page_css = "list_sukien.css";
load_top();
load_header();

global $conn;

// Lấy tên câu lạc bộ và chủ nhiệm để phân quyền
$sql_club = "SELECT ten_clb, chu_nhiem_id FROM clubs WHERE id = ?";
$stmt = $conn->prepare($sql_club);
$stmt->bind_param("i", $club_id);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc();
$ten_clb = $club['ten_clb'] ?? 'Câu lạc bộ';
$is_owner = isset($club['chu_nhiem_id']) && ((int)$club['chu_nhiem_id'] === (int)$_SESSION['user_id']);
$stmt->close();

// Kiểm tra vai trò của người dùng trong CLB (đội phó cũng được quyền chỉnh sửa)
$user_role = 'guest';
$role_sql = "SELECT vai_tro FROM club_members WHERE club_id = ? AND user_id = ? AND trang_thai = 'dang_hoat_dong' LIMIT 1";
$role_stmt = $conn->prepare($role_sql);
if ($role_stmt) {
    $role_stmt->bind_param("ii", $club_id, $_SESSION['user_id']);
    $role_stmt->execute();
    $role_res = $role_stmt->get_result();
    if ($role_res && $role_res->num_rows > 0) {
        $user_role = strtolower($role_res->fetch_assoc()['vai_tro'] ?? 'guest');
    }
    $role_stmt->close();
}

// Chuẩn hóa vai trò để so sánh (xử lý cả tiếng Việt có dấu)
function normalize_role($role) {
    $role = strtolower(trim($role));
    $map = [
        'đội phó' => 'doi_pho',
        'doi pho' => 'doi_pho',
        'đội trưởng' => 'doi_truong',
        'doi truong' => 'doi_truong',
        'trưởng ban' => 'truong_ban',
        'truong ban' => 'truong_ban',
        'phó chủ nhiệm' => 'pho_chu_nhiem',
        'pho chu nhiem' => 'pho_chu_nhiem',
        'chủ nhiệm' => 'chu_nhiem',
        'chu nhiem' => 'chu_nhiem'
    ];
    return $map[$role] ?? $role;
}

$role_key = normalize_role($user_role);
$can_manage = $is_owner || in_array($role_key, ['doi_pho', 'chu_nhiem', 'pho_chu_nhiem', 'truong_ban', 'doi_truong']);

// Tự động cập nhật trạng thái sự kiện dựa trên thời gian hiện tại
// CHỈ tự động cập nhật cho "sap_dien_ra" và "dang_dien_ra"
// KHÔNG động vào "da_ket_thuc" và "da_huy" (để tôn trọng thay đổi thủ công của người dùng)
$now = date('Y-m-d H:i:s');
$update_status_sql = "UPDATE events 
                      SET trang_thai = CASE 
                          -- Sắp diễn ra: chưa bắt đầu
                          WHEN thoi_gian_bat_dau > ? THEN 'sap_dien_ra'
                          -- Đang diễn ra: đã bắt đầu nhưng chưa kết thúc
                          WHEN thoi_gian_bat_dau <= ? AND thoi_gian_ket_thuc >= ? THEN 'dang_dien_ra'
                          -- Đã kết thúc: đã kết thúc (chỉ tự động nếu đang là sap_dien_ra hoặc dang_dien_ra)
                          WHEN thoi_gian_ket_thuc < ? AND trang_thai IN ('sap_dien_ra', 'dang_dien_ra') THEN 'da_ket_thuc'
                          ELSE trang_thai
                      END
                      WHERE club_id = ? AND trang_thai NOT IN ('da_huy', 'da_ket_thuc')";
$update_stmt = $conn->prepare($update_status_sql);
$update_stmt->bind_param("ssssi", $now, $now, $now, $now, $club_id);
$update_stmt->execute();
$update_stmt->close();

// Thống kê (dựa trên trạng thái đã được cập nhật trong database)
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN trang_thai = 'da_ket_thuc' THEN 1 ELSE 0 END) as da_ket_thuc,
                SUM(CASE WHEN trang_thai = 'dang_dien_ra' THEN 1 ELSE 0 END) as dang_dien_ra,
                SUM(CASE WHEN trang_thai = 'sap_dien_ra' THEN 1 ELSE 0 END) as sap_dien_ra
            FROM events 
            WHERE club_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $club_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// Danh sách sự kiện
$sql = "SELECT 
            e.id, e.ten_su_kien, e.mo_ta, e.dia_diem,
            e.thoi_gian_bat_dau, e.thoi_gian_ket_thuc,
            e.so_luong_toi_da, e.han_dang_ky, e.trang_thai,
            media_library.file_path AS anh_bia_path
        FROM events e
        LEFT JOIN media_library ON e.anh_bia_id = media_library.id
        WHERE e.club_id = ? 
        ORDER BY e.thoi_gian_bat_dau DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $club_id);
$stmt->execute();
$result = $stmt->get_result();

// Đếm số lượng đăng ký cho mỗi sự kiện
$event_registrations = [];
if ($result && $result->num_rows > 0) {
    $result->data_seek(0);
    while ($event = $result->fetch_assoc()) {
        $count_sql = "SELECT COUNT(*) as total FROM event_registrations WHERE event_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $event['id']);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $registered_count = 0;
        if ($count_result->num_rows > 0) {
            $registered_count = (int)$count_result->fetch_assoc()['total'];
        }
        $count_stmt->close();
        $event_registrations[$event['id']] = $registered_count;
    }
    $result->data_seek(0); // Reset pointer
}
?>

<div class="list-sk-container">
    <!-- Header -->
    <div class="page-header">
        <div class="header-left">
            <button class="btn-back" onclick="window.location.href='Dashboard.php?id=<?= $club_id ?>'">
                ← Quay lại
            </button>
            <div class="header-title">
                <h1>Danh sách sự kiện</h1>
                <p class="club-name"><?= htmlspecialchars($ten_clb) ?></p>
            </div>
        </div>
        <?php if ($can_manage): ?>
            <a href="add_Su_kien.php?id=<?= $club_id ?>" class="btn-add">
                <span class="icon">+</span> Tạo sự kiện mới
            </a>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label">Tổng sự kiện</div>
            </div>
        </div>
        <div class="stat-card upcoming">
            <div class="stat-icon">🕒</div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['sap_dien_ra'] ?></div>
                <div class="stat-label">Sắp diễn ra</div>
            </div>
        </div>
        <div class="stat-card ongoing">
            <div class="stat-icon">🎯</div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['dang_dien_ra'] ?></div>
                <div class="stat-label">Đang diễn ra</div>
            </div>
        </div>
        <div class="stat-card ended">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-number"><?= $stats['da_ket_thuc'] ?></div>
                <div class="stat-label">Đã kết thúc</div>
            </div>
        </div>
    </div>

    <!-- Events Grid -->
    <?php if ($result->num_rows > 0): ?>
        <div class="events-grid">
            <?php while ($event = $result->fetch_assoc()): ?>
                <div class="event-card" data-event-id="<?= $event['id'] ?>">
                    <div class="event-image">
                        <?php if (!empty($event['anh_bia_path'])): ?>
                            <img src="<?= htmlspecialchars($event['anh_bia_path']) ?>" 
                                 alt="<?= htmlspecialchars($event['ten_su_kien']) ?>">
                        <?php else: ?>
                            <div class="placeholder-img">
                                <span>📅</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php
                        // Ưu tiên trạng thái trong database (tôn trọng thay đổi thủ công của người dùng)
                        // Chỉ tự động tính toán nếu trạng thái là "sap_dien_ra" hoặc "dang_dien_ra"
                        $now = time();
                        $start_time = strtotime($event['thoi_gian_bat_dau']);
                        $end_time = strtotime($event['thoi_gian_ket_thuc']);
                        
                        // Nếu trạng thái đã được đặt thủ công là "da_huy" hoặc "da_ket_thuc", giữ nguyên
                        if ($event['trang_thai'] === 'da_huy' || $event['trang_thai'] === 'da_ket_thuc') {
                            $actual_status = $event['trang_thai'];
                        } elseif ($start_time > $now) {
                            // Sắp diễn ra: chưa bắt đầu
                            $actual_status = 'sap_dien_ra';
                        } elseif ($start_time <= $now && $end_time >= $now) {
                            // Đang diễn ra: đã bắt đầu nhưng chưa kết thúc
                            $actual_status = 'dang_dien_ra';
                        } elseif ($end_time < $now) {
                            // Đã kết thúc: đã kết thúc (chỉ tự động nếu đang là sap_dien_ra hoặc dang_dien_ra)
                            $actual_status = 'da_ket_thuc';
                        } else {
                            // Fallback: dùng trạng thái trong database
                            $actual_status = $event['trang_thai'];
                        }
                        
                        $status_map = [
                            'sap_dien_ra' => ['text' => 'Sắp diễn ra', 'class' => 'upcoming'],
                            'dang_dien_ra' => ['text' => 'Đang diễn ra', 'class' => 'ongoing'],
                            'da_ket_thuc' => ['text' => 'Đã kết thúc', 'class' => 'ended'],
                            'da_huy' => ['text' => 'Đã hủy', 'class' => 'cancelled']
                        ];
                        $status = $status_map[$actual_status] ?? ['text' => 'Không xác định', 'class' => ''];
                        ?>
                        <span class="status-badge <?= $status['class'] ?>">
                            <?= $status['text'] ?>
                        </span>
                        
                        <?php if ($can_manage): ?>
                            <!-- Quick Actions Menu -->
                            <div class="quick-actions">
                                <button class="btn-menu" onclick="toggleMenu(<?= $event['id'] ?>)">⋮</button>
                                <div class="actions-menu" id="menu-<?= $event['id'] ?>">
                                    <button onclick="quickEditStatus(<?= $event['id'] ?>, '<?= $actual_status ?>')">
                                        🔄 Đổi trạng thái
                                    </button>
                                    <a href="edit_sk.php?id=<?= $event['id'] ?>">
                                        ✏️ Chỉnh sửa đầy đủ
                                    </a>
                                    <button onclick="deleteEvent(<?= $event['id'] ?>)" class="danger">
                                        🗑️ Xóa sự kiện
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="event-content">
                        <h3 class="event-title"><?= htmlspecialchars($event['ten_su_kien']) ?></h3>
                        
                        <div class="event-details">
                            <div class="detail-item">
                                <span class="icon">📅</span>
                                <span><?= date('d/m/Y H:i', strtotime($event['thoi_gian_bat_dau'])) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="icon">📍</span>
                                <span><?= htmlspecialchars($event['dia_diem'] ?: 'Chưa cập nhật') ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="icon">👥</span>
                                <span>Đã đăng ký: <strong><?= $event_registrations[$event['id']] ?? 0 ?></strong> / <?= $event['so_luong_toi_da'] ? $event['so_luong_toi_da'] . ' người' : 'Không giới hạn' ?></span>
                            </div>
                        </div>

                        <?php if ($event['mo_ta']): ?>
                            <p class="event-desc">
                                <?= mb_substr(htmlspecialchars($event['mo_ta']), 0, 100) ?>...
                            </p>
                        <?php endif; ?>

                        <div class="event-actions">
                            <a href="chi_tiet_su_kien.php?id=<?= $event['id'] ?>" class="btn-view">
                                Xem chi tiết
                            </a>
                            <?php if ($can_manage): ?>
                                <a href="edit_sk.php?id=<?= $event['id'] ?>" class="btn-edit">
                                    Chỉnh sửa
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📅</div>
            <h3>Chưa có sự kiện nào</h3>
            <p>Hãy tạo sự kiện đầu tiên cho câu lạc bộ của bạn!</p>
            <a href="add_Su_kien.php?id=<?= $club_id ?>" class="btn-add-large">
                + Tạo sự kiện đầu tiên
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Quick Edit Status Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>🔄 Thay đổi trạng thái sự kiện</h3>
            <button class="btn-close" onclick="closeStatusModal()">×</button>
        </div>
        <form id="statusForm" onsubmit="updateStatus(event)">
            <input type="hidden" id="edit_event_id" name="event_id">
            <?php echo csrf_token_input(); ?>
            
            <div class="form-group">
                <label>Trạng thái mới:</label>
                <select id="new_status" name="trang_thai" required>
                    <option value="sap_dien_ra">🕒 Sắp diễn ra</option>
                    <option value="dang_dien_ra">🎯 Đang diễn ra</option>
                    <option value="da_ket_thuc">✅ Đã kết thúc</option>
                    <option value="da_huy">❌ Đã hủy</option>
                </select>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeStatusModal()">Hủy</button>
                <button type="submit" class="btn-submit" id="btnUpdateStatus">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle menu
function toggleMenu(eventId) {
    const menu = document.getElementById('menu-' + eventId);
    const allMenus = document.querySelectorAll('.actions-menu');
    
    allMenus.forEach(m => {
        if (m !== menu) m.classList.remove('show');
    });
    
    menu.classList.toggle('show');
}

// Close menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.quick-actions')) {
        document.querySelectorAll('.actions-menu').forEach(m => m.classList.remove('show'));
    }
});

// Quick edit status
function quickEditStatus(eventId, currentStatus) {
    document.getElementById('edit_event_id').value = eventId;
    document.getElementById('new_status').value = currentStatus;
    document.getElementById('statusModal').style.display = 'flex';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

// Update status
function updateStatus(e) {
    e.preventDefault();
    
    const btnSubmit = document.getElementById('btnUpdateStatus');
    const originalText = btnSubmit.innerHTML;
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-small"></span> Đang cập nhật...';
    
    const formData = new FormData(e.target);
    
    fetch('quick_update_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Kiểm tra Content-Type trước khi parse JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                throw new Error('Server trả về HTML thay vì JSON. Có thể do lỗi server.');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('✅ Đã cập nhật trạng thái thành công!');
            location.reload();
        } else {
            alert('❌ Lỗi: ' + (data.message || 'Không thể cập nhật trạng thái'));
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Lỗi: ' + (error.message || 'Không thể kết nối đến server'));
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalText;
    });
}

// Delete event
function deleteEvent(eventId) {
    if (!confirm('⚠️ Bạn có chắc chắn muốn xóa sự kiện này?\nHành động này không thể hoàn tác!')) {
        return;
    }
    
    fetch('delete_event.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'event_id=' + eventId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Đã xóa sự kiện thành công!');
            location.reload();
        } else {
            alert('❌ Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Lỗi kết nối: ' + error);
    });
}
</script>

<?php 
$stmt->close();
$conn->close();
load_footer();
?>
