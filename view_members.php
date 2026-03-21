<?php
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// NOW start session
session_start();

// Kiểm tra đăng nhập
require_login();

require 'site.php'; 
load_top();
load_header();
require_once(__DIR__ . "/assets/database/connect.php");

// Lấy ID từ GET hoặc SESSION
$club_id = get_club_id();

if ($club_id <= 0) {
    redirect('myclub.php', 'ID câu lạc bộ không hợp lệ', 'error');
}

// Phân trang
$items_per_page = 12; // Số thành viên mỗi trang
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Tìm kiếm và filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$department_filter = isset($_GET['department']) ? (int)$_GET['department'] : 0;

// Lấy danh sách phòng ban để hiển thị trong dropdown
$departments_sql = "SELECT id, ten_phong_ban FROM phong_ban WHERE club_id = ? ORDER BY created_at ASC, id ASC";
$dept_stmt = $conn->prepare($departments_sql);
$dept_stmt->bind_param("i", $club_id);
$dept_stmt->execute();
$departments_result = $dept_stmt->get_result();
$departments_list = [];
while ($row = $departments_result->fetch_assoc()) {
    $departments_list[] = $row;
}
$dept_stmt->close();

// Xây dựng WHERE clause
$where_conditions = ["cm.club_id = ?", "cm.trang_thai = 'dang_hoat_dong'"];
$params = [$club_id];
$types = 'i';

if (!empty($search)) {
    $where_conditions[] = "(u.ho_ten LIKE ? OR u.email LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($role_filter) && in_array($role_filter, ['doi_truong', 'doi_pho', 'truong_ban', 'thanh_vien', 'chu_nhiem', 'pho_chu_nhiem'])) {
    $where_conditions[] = "cm.vai_tro = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if (!empty($department_filter) && $department_filter > 0) {
    $where_conditions[] = "cm.phong_ban_id = ?";
    $params[] = $department_filter;
    $types .= 'i';
}

$where_clause = implode(' AND ', $where_conditions);

// Đếm tổng số thành viên (cho phân trang)
$count_sql = "
    SELECT COUNT(*) as total
    FROM club_members cm
    INNER JOIN users u ON cm.user_id = u.id
    WHERE $where_clause
";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_members = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_members / $items_per_page);
$count_stmt->close();

// Lấy danh sách thành viên với phân trang
$sql = "
    SELECT 
        cm.id AS club_member_id,
        u.id AS user_id,
        u.ho_ten,
        u.avatar,
        u.email,
        cm.vai_tro AS vai_tro_clb,
        cm.trang_thai,
        COALESCE(pb.ten_phong_ban, '') AS ten_phong_ban,
        cm.phong_ban_id
    FROM club_members cm
    INNER JOIN users u ON cm.user_id = u.id
    LEFT JOIN phong_ban pb ON cm.phong_ban_id = pb.id AND pb.club_id = cm.club_id
    WHERE $where_clause
    ORDER BY 
        CASE cm.vai_tro
            WHEN 'doi_truong' THEN 1
            WHEN 'chu_nhiem' THEN 1
            WHEN 'doi_pho' THEN 2
            WHEN 'pho_chu_nhiem' THEN 2
            WHEN 'truong_ban' THEN 3
            ELSE 4
        END,
        u.ho_ten
    LIMIT ? OFFSET ?
";

$params[] = $items_per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<link rel="stylesheet" href="assets/css/view_member.css">

<div class="view-members-container">
    <div class="page-header">
        <div class="header-content">
            <div class="header-icon">👥</div>
            <div>
                <h1>Danh sách thành viên</h1>
                <p>Tổng cộng: <strong><?= $total_members ?></strong> thành viên đang hoạt động</p>
            </div>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="search-filter-bar">
        <form method="GET" action="view_members.php" class="search-form">
            <input type="hidden" name="id" value="<?= $club_id ?>">
            <div class="search-box">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="text" name="search" placeholder="Tìm kiếm theo tên hoặc email..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="role" class="role-filter">
                <option value="">Tất cả vai trò</option>
                <option value="doi_truong" <?= in_array($role_filter, ['doi_truong', 'chu_nhiem']) ? 'selected' : '' ?>>Đội trưởng</option>
                <option value="doi_pho" <?= in_array($role_filter, ['doi_pho', 'pho_chu_nhiem']) ? 'selected' : '' ?>>Đội phó</option>
                <option value="truong_ban" <?= $role_filter === 'truong_ban' ? 'selected' : '' ?>>Trưởng ban</option>
                <option value="thanh_vien" <?= $role_filter === 'thanh_vien' ? 'selected' : '' ?>>Thành viên</option>
            </select>
            <select name="department" class="department-filter">
                <option value="">Tất cả phòng ban</option>
                <?php foreach ($departments_list as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= $department_filter == $dept['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['ten_phong_ban']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                Tìm kiếm
            </button>
            <?php if (!empty($search) || !empty($role_filter) || !empty($department_filter)): ?>
            <a href="view_members.php?id=<?= $club_id ?>" class="btn-clear">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                Xóa bộ lọc
            </a>
            <?php endif; ?>
        </form>
    </div>

<div class="members-table-container">
    <table class="members-table">
        <thead>
            <tr>
                <th>Thành viên</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>Phòng ban</th>
                <th>Trạng thái</th>
                <th style="text-align: center;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($member = $result->fetch_assoc()): 
                    $club_member_id = $member['club_member_id'];
                    $ho_ten         = htmlspecialchars($member['ho_ten']);
                    $avatar         = $member['avatar']; 
                    $email          = htmlspecialchars($member['email'] ?? 'Chưa cung cấp');

                    // Vai trò
                    $vai_tro = isset($member['vai_tro_clb']) ? $member['vai_tro_clb'] : '';
                    $vai_tro_hien_thi = match($vai_tro) {
                        'doi_truong', 'chu_nhiem'     => 'Đội trưởng',
                        'doi_pho', 'pho_chu_nhiem'    => 'Đội phó',
                        'truong_ban'                   => 'Trưởng ban',
                        default                        => 'Thành viên'
                    };
                    
                    // Class cho badge vai trò
                    $role_class = match($vai_tro) {
                        'doi_truong', 'chu_nhiem'     => 'role-owner',
                        'doi_pho', 'pho_chu_nhiem'    => 'role-vice',
                        'truong_ban'                  => 'role-leader',
                        default                        => 'role-member'
                    };

                    // Xử lý avatar
                    if (!empty($avatar) && file_exists($avatar)) {
                        $anh_avatar = htmlspecialchars($avatar) . '?v=' . filemtime($avatar);
                    } else {
                        $anh_avatar = "assets/img/avatars/user.svg";
                    }
                    
                    // Phòng ban - kiểm tra kỹ dữ liệu
                    $phong_ban = 'Chưa phân công';
                    $phong_ban_id = isset($member['phong_ban_id']) ? (int)$member['phong_ban_id'] : 0;
                    if (isset($member['ten_phong_ban']) && !empty($member['ten_phong_ban']) && trim($member['ten_phong_ban']) !== '') {
                        $phong_ban = htmlspecialchars(trim($member['ten_phong_ban']));
                    }
                ?>
                <tr>
                    <td>
                        <div class="member-cell">
                            <img src="<?= $anh_avatar ?>" alt="<?= $ho_ten ?>" class="member-avatar-small">
                            <span class="member-name"><?= $ho_ten ?></span>
                        </div>
                    </td>
                    <td><?= $email ?></td>
                    <td>
                        <span class="role-badge-table <?= $role_class ?>">
                            <?= $vai_tro_hien_thi ?>
                        </span>
                    </td>
                    <td>
                        <span class="phong-ban-text"><?= $phong_ban ?></span>
                    </td>
                    <td>
                        <span class="status approved">Đang hoạt động</span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon btn-edit" onclick="openEditModal(<?= $club_member_id ?>, '<?= htmlspecialchars($ho_ten, ENT_QUOTES) ?>', '<?= $vai_tro ?>', <?= $phong_ban_id ?>, <?= $club_id ?>)" title="Chỉnh sửa">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="btn-icon btn-delete" onclick="deleteMember(<?= $club_member_id ?>)" title="Xóa thành viên">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state-table">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <h3>Chưa có thành viên nào</h3>
                            <p>Chưa có thành viên nào trong câu lạc bộ này</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($current_page > 1): ?>
        <a href="?id=<?= $club_id ?>&page=<?= $current_page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($role_filter) ? '&role=' . urlencode($role_filter) : '' ?><?= !empty($department_filter) ? '&department=' . $department_filter : '' ?>" class="page-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            Trước
        </a>
        <?php endif; ?>

        <div class="page-numbers">
            <?php
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            if ($start_page > 1): ?>
                <a href="?id=<?= $club_id ?>&page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($role_filter) ? '&role=' . urlencode($role_filter) : '' ?><?= !empty($department_filter) ? '&department=' . $department_filter : '' ?>" class="page-num">1</a>
                <?php if ($start_page > 2): ?>
                    <span class="page-dots">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a href="?id=<?= $club_id ?>&page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($role_filter) ? '&role=' . urlencode($role_filter) : '' ?><?= !empty($department_filter) ? '&department=' . $department_filter : '' ?>" 
                   class="page-num <?= $i === $current_page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span class="page-dots">...</span>
                <?php endif; ?>
                <a href="?id=<?= $club_id ?>&page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($role_filter) ? '&role=' . urlencode($role_filter) : '' ?><?= !empty($department_filter) ? '&department=' . $department_filter : '' ?>" class="page-num"><?= $total_pages ?></a>
            <?php endif; ?>
        </div>

        <?php if ($current_page < $total_pages): ?>
        <a href="?id=<?= $club_id ?>&page=<?= $current_page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($role_filter) ? '&role=' . urlencode($role_filter) : '' ?><?= !empty($department_filter) ? '&department=' . $department_filter : '' ?>" class="page-btn">
            Sau
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="pagination-info">
        Hiển thị <?= $result->num_rows > 0 ? ($offset + 1) : 0 ?> - <?= min($offset + $items_per_page, $total_members) ?> trong tổng số <?= $total_members ?> thành viên
    </div>
</div>

<?php $stmt->close(); ?>


<!-- Edit Member Modal -->
<div id="editMemberModal" class="edit-modal-overlay" style="display: none;">
    <div class="edit-modal-backdrop" onclick="closeEditModal()"></div>
    <div class="edit-modal-content">
        <div class="edit-modal-header">
            <h3>Chỉnh sửa thành viên</h3>
            <button class="edit-modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form id="editMemberForm">
            <input type="hidden" id="edit_club_member_id" name="club_member_id">
            <input type="hidden" id="edit_club_id" name="club_id">
            
            <div class="edit-form-group">
                <label>Thành viên</label>
                <div class="edit-member-display" id="edit_member_name"></div>
            </div>

            <div class="edit-form-group">
                <label for="edit_phong_ban_id">Phòng ban</label>
                <select id="edit_phong_ban_id" name="phong_ban_id">
                    <option value="">-- Chọn phòng ban --</option>
                </select>
            </div>

            <div class="edit-form-group">
                <label for="edit_vai_tro">Vai trò <span class="required">*</span></label>
                <select id="edit_vai_tro" name="vai_tro" required>
                    <option value="thanh_vien">Thành viên</option>
                    <option value="truong_ban">Trưởng ban</option>
                    <option value="doi_pho">Đội phó</option>
                    <option value="doi_truong">Đội trưởng</option>
                </select>
            </div>

            <div class="edit-modal-footer">
                <button type="button" class="edit-btn-cancel" onclick="closeEditModal()">Hủy</button>
                <button type="submit" class="edit-btn-submit">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<script>
function deleteMember(club_member_id) {
    if (!confirm('Bạn có chắc chắn muốn xóa thành viên này?')) return;

    fetch('api/delete_member.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'club_member_id=' + club_member_id
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) location.reload();
    })
    .catch(err => {
        console.error(err);
        alert('Lỗi kết nối server!');
    });
}

function openEditModal(club_member_id, member_name, current_role, current_phong_ban_id, club_id) {
    // Set form values
    document.getElementById('edit_club_member_id').value = club_member_id;
    document.getElementById('edit_club_id').value = club_id;
    document.getElementById('edit_member_name').textContent = member_name;
    document.getElementById('edit_vai_tro').value = current_role;
    
    // Load departments
    fetch(`api/get_departments.php?club_id=${club_id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.departments) {
                const select = document.getElementById('edit_phong_ban_id');
                select.innerHTML = '<option value="">-- Chọn phòng ban --</option>';
                data.departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.id;
                    option.textContent = dept.ten_phong_ban;
                    if (current_phong_ban_id && dept.id == current_phong_ban_id) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            }
        })
        .catch(err => {
            console.error('Error loading departments:', err);
        });
    
    // Show modal
    document.getElementById('editMemberModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editMemberModal').style.display = 'none';
}

// Handle form submit
document.getElementById('editMemberForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        club_member_id: formData.get('club_member_id'),
        club_id: formData.get('club_id'),
        vai_tro: formData.get('vai_tro'),
        phong_ban_id: formData.get('phong_ban_id') || ''
    };
    
    // Disable submit button
    const submitBtn = this.querySelector('.edit-btn-submit');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Đang cập nhật...';
    
    fetch('api/update_member.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data).toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Cập nhật';
        }
    })
    .catch(err => {
        console.error(err);
        alert('Lỗi kết nối server!');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Cập nhật';
    });
});
</script>