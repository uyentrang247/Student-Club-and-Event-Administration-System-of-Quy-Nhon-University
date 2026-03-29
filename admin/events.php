<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../assets/database/connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$message = null;
$message_type = 'success';
if (isset($_SESSION['flash_events'])) {
    $message = $_SESSION['flash_events']['message'] ?? null;
    $message_type = $_SESSION['flash_events']['type'] ?? 'success';
    unset($_SESSION['flash_events']);
}

// Xử lý thêm/sửa/xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Bảo vệ CSRF cho toàn bộ thao tác quản trị sự kiện
    $csrf_token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $message = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        $message_type = 'error';
    }

    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($message_type !== 'error' && ($action === 'create' || $action === 'update')) {
        $name = trim($_POST['name'] ?? '');
        $club_id = !empty($_POST['club_id']) ? (int)$_POST['club_id'] : 0;
        $location = trim($_POST['location'] ?? '');
        $start_time = $_POST['start_time'] ?? null;
        $end_time = $_POST['end_time'] ?? null;
        $max_participants = isset($_POST['max_participants']) ? max(0, (int)$_POST['max_participants']) : null;
        $reg_deadline = $_POST['reg_deadline'] ?? null;
        $status = $_POST['status'] ?? 'upcoming';
        $short_desc = trim($_POST['short_desc'] ?? '');

        $valid_status = ['upcoming','ongoing','completed','cancelled'];
        if ($name === '' || $club_id <= 0) {
            $message = 'Vui lòng nhập tên sự kiện và chọn Câu lạc bộ.';
            $message_type = 'error';
        } elseif (!in_array($status, $valid_status, true)) {
            $message = 'Trạng thái không hợp lệ.';
            $message_type = 'error';
        } elseif (!empty($start_time) && !empty($end_time) && strtotime($start_time) >= strtotime($end_time)) {
            $message = 'Thời gian bắt đầu phải trước thời gian kết thúc.';
            $message_type = 'error';
        } elseif (!empty($reg_deadline) && !empty($start_time) && strtotime($reg_deadline) > strtotime($start_time)) {
            $message = 'Hạn đăng ký phải trước thời gian bắt đầu sự kiện.';
            $message_type = 'error';
        } else {
            if ($action === 'create') {
                $stmt = $conn->prepare("INSERT INTO events (club_id, name, short_desc, location, start_time, end_time, max_participants, reg_deadline, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $created_by = $_SESSION['admin_id'] ?? 0;
                $stmt->bind_param("issssssssi", $club_id, $name, $short_desc, $location, $start_time, $end_time, $max_participants, $reg_deadline, $status, $created_by);
                if ($stmt->execute()) {
                    $_SESSION['flash_events'] = ['message' => 'Thêm sự kiện thành công.', 'type' => 'success'];
                    header('Location: events.php');
                    exit;
                } else {
                    $message = 'Lỗi khi thêm sự kiện.';
                    $message_type = 'error';
                }
                $stmt->close();
            } elseif ($action === 'update') {
                if ($id <= 0) {
                    $message = 'Thiếu thông tin sự kiện.';
                    $message_type = 'error';
                } else {
                    $stmt = $conn->prepare("UPDATE events SET club_id=?, name=?, short_desc=?, location=?, start_time=?, end_time=?, max_participants=?, reg_deadline=?, status=? WHERE id=?");
                    $stmt->bind_param("issssssssi", $club_id, $name, $short_desc, $location, $start_time, $end_time, $max_participants, $reg_deadline, $status, $id);
                    if ($stmt->execute()) {
                        $_SESSION['flash_events'] = ['message' => 'Cập nhật sự kiện thành công.', 'type' => 'success'];
                        header('Location: events.php');
                        exit;
                    } else {
                        $message = 'Lỗi khi cập nhật sự kiện.';
                        $message_type = 'error';
                    }
                    $stmt->close();
                }
            }
        }
    } elseif ($action === 'delete') {
        if ($id <= 0) {
            $message = 'Thiếu thông tin sự kiện.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['flash_events'] = ['message' => 'Đã xóa sự kiện.', 'type' => 'success'];
                header('Location: events.php');
                exit;
            } else {
                $message = 'Lỗi khi xóa sự kiện.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

$items_per_page = 20;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where_clause = "WHERE e.name LIKE ? OR c.name LIKE ?";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types = 'ss';
}

$count_sql = "SELECT COUNT(*) as total FROM events e LEFT JOIN clubs c ON e.club_id = c.id $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_events = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $result = $conn->query($count_sql);
    $total_events = $result->fetch_assoc()['total'];
}
$total_pages = ceil($total_events / $items_per_page);

$sql = "SELECT e.id, e.name, c.name as club_name, e.start_time, e.end_time, e.status, e.created_at, e.location
        FROM events e
        LEFT JOIN clubs c ON e.club_id = c.id
        $where_clause
        ORDER BY e.created_at DESC
        LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $items_per_page, $offset);
}
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Danh sách CLB
$clubs = $conn->query("SELECT id, name FROM clubs ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// Tự động cập nhật trạng thái dựa trên thời gian
$now = new DateTime();
foreach ($events as &$evt) {
    if (empty($evt['start_time']) && empty($evt['end_time'])) {
        continue;
    }
    $current_status = $evt['status'];
    $target_status = $current_status;

    try {
        $start = !empty($evt['start_time']) ? new DateTime($evt['start_time']) : null;
        $end   = !empty($evt['end_time']) ? new DateTime($evt['end_time']) : null;

        // Ưu tiên nếu sự kiện đã hủy thì giữ nguyên
        if ($current_status === 'cancelled') {
            $target_status = 'cancelled';
        } elseif ($end && $now >= $end) {
            $target_status = 'completed';
        } elseif ($start && $now >= $start && (!$end || $now < $end)) {
            $target_status = 'ongoing';
        } elseif ($start && $now < $start) {
            $target_status = 'upcoming';
        }

        // Đồng bộ DB để tránh trạng thái hiển thị lệch
        if ($target_status !== $current_status) {
            $evt['status'] = $target_status;
            $update_stmt = $conn->prepare("UPDATE events SET status = ? WHERE id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("si", $target_status, $evt['id']);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
    } catch (Exception $e) {
        // Nếu parse thời gian lỗi, bỏ qua cập nhật
        continue;
    }
}
unset($evt);

// Sự kiện đang chỉnh sửa
$edit_event = null;
if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_event = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sự kiện - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin/admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-main">
        <?php include 'includes/header.php'; ?>
        
        <div class="admin-content">
            <div class="page-header">
                <div>
                    <h1>Quản lý Sự kiện</h1>
                    <p>Tổng cộng: <strong><?= number_format($total_events) ?></strong> sự kiện</p>
                    <?php if ($message): ?>
                        <div class="alert <?= $message_type === 'success' ? 'alert-success' : 'alert-danger' ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <button type="button" class="btn-primary" id="btnOpenCreateEvent">Thêm mới</button>
                </div>
            </div>

            <!-- Modal thêm sự kiện -->
            <div class="modal" id="createEventModal">
                <div class="modal-dialog">
                    <div class="modal-header">
                        <h3>Thêm sự kiện</h3>
                        <button type="button" class="modal-close" id="btnCloseCreateEvent">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" class="form-grid">
                            <?= csrf_token_input(); ?>
                            <input type="hidden" name="action" value="create">
                            <div class="form-group">
                                <label>Tên sự kiện</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-group">
                                <label>Câu lạc bộ</label>
                                <select name="club_id" required>
                                    <option value="">-- Chọn CLB --</option>
                                    <?php foreach ($clubs as $club): ?>
                                        <option value="<?= $club['id'] ?>"><?= htmlspecialchars($club['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Địa điểm</label>
                                <input type="text" name="location" placeholder="Hội trường, phòng, link...">
                            </div>
                            <div class="form-group">
                                <label>Thời gian bắt đầu</label>
                                <input type="datetime-local" name="start_time">
                            </div>
                            <div class="form-group">
                                <label>Thời gian kết thúc</label>
                                <input type="datetime-local" name="end_time">
                            </div>
                            <div class="form-group">
                                <label>Hạn đăng ký</label>
                                <input type="datetime-local" name="reg_deadline">
                            </div>
                            <div class="form-group">
                                <label>Số lượng tối đa</label>
                                <input type="number" name="max_participants" min="0" value="">
                            </div>
                            <div class="form-group">
                                <label>Trạng thái</label>
                                <select name="status">
                                    <option value="upcoming">Sắp diễn ra</option>
                                    <option value="ongoing">Đang diễn ra</option>
                                    <option value="completed">Đã kết thúc</option>
                                    <option value="cancelled">Đã hủy</option>
                                </select>
                            </div>
                            <div class="form-group" style="grid-column:1/-1;">
                                <label>Mô tả</label>
                                <textarea name="short_desc" rows="3" placeholder="Giới thiệu ngắn về sự kiện"></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Lưu</button>
                                <button type="button" class="btn-secondary" id="btnCancelCreateEvent">Hủy</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal chỉnh sửa -->
            <?php if ($edit_event): ?>
            <div class="modal open" id="editEventModal">
                <div class="modal-dialog">
                    <div class="modal-header">
                        <h3>Chỉnh sửa: <?= htmlspecialchars($edit_event['name']) ?></h3>
                        <button type="button" class="modal-close" id="btnCloseEditEvent">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" class="form-grid">
                            <?= csrf_token_input(); ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $edit_event['id'] ?>">
                            <div class="form-group">
                                <label>Tên sự kiện</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($edit_event['name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Câu lạc bộ</label>
                                <select name="club_id" required>
                                    <option value="">-- Chọn CLB --</option>
                                    <?php foreach ($clubs as $club): ?>
                                        <option value="<?= $club['id'] ?>" <?= $edit_event['club_id'] == $club['id'] ? 'selected' : '' ?>><?= htmlspecialchars($club['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Địa điểm</label>
                                <input type="text" name="location" value="<?= htmlspecialchars($edit_event['location'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Thời gian bắt đầu</label>
                                <input type="datetime-local" name="start_time" value="<?= !empty($edit_event['start_time']) ? date('Y-m-d\TH:i', strtotime($edit_event['start_time'])) : '' ?>">
                            </div>
                            <div class="form-group">
                                <label>Thời gian kết thúc</label>
                                <input type="datetime-local" name="end_time" value="<?= !empty($edit_event['end_time']) ? date('Y-m-d\TH:i', strtotime($edit_event['end_time'])) : '' ?>">
                            </div>
                            <div class="form-group">
                                <label>Hạn đăng ký</label>
                                <input type="datetime-local" name="reg_deadline" value="<?= !empty($edit_event['reg_deadline']) ? date('Y-m-d\TH:i', strtotime($edit_event['reg_deadline'])) : '' ?>">
                            </div>
                            <div class="form-group">
                                <label>Số lượng tối đa</label>
                                <input type="number" name="max_participants" min="0" value="<?= htmlspecialchars($edit_event['max_participants'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Trạng thái</label>
                                <select name="status">
                                    <option value="upcoming" <?= $edit_event['status'] === 'upcoming' ? 'selected' : '' ?>>Sắp diễn ra</option>
                                    <option value="ongoing" <?= $edit_event['status'] === 'ongoing' ? 'selected' : '' ?>>Đang diễn ra</option>
                                    <option value="completed" <?= $edit_event['status'] === 'completed' ? 'selected' : '' ?>>Đã kết thúc</option>
                                    <option value="cancelled" <?= $edit_event['status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                </select>
                            </div>
                            <div class="form-group" style="grid-column:1/-1;">
                                <label>Mô tả</label>
                                <textarea name="short_desc" rows="3"><?= htmlspecialchars($edit_event['short_desc'] ?? '') ?></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Lưu thay đổi</button>
                                <a href="events.php" class="btn-secondary">Hủy</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="filter-bar">
                <form method="GET" class="filter-form">
                    <div class="search-box">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" name="search" placeholder="Tìm kiếm theo tên sự kiện hoặc CLB..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button type="submit" class="btn-primary">Tìm kiếm</button>
                    <?php if (!empty($search)): ?>
                    <a href="events.php" class="btn-secondary">Xóa bộ lọc</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên sự kiện</th>
                            <th>Câu lạc bộ</th>
                            <th>Thời gian</th>
                            <th>Trạng thái</th>
                            <th>Địa điểm</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($events)): ?>
                            <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?= $event['id'] ?></td>
                                <td><?= htmlspecialchars($event['name']) ?></td>
                                <td><?= htmlspecialchars($event['club_name'] ?? 'N/A') ?></td>
                                <td><?= !empty($event['start_time']) ? date('d/m/Y H:i', strtotime($event['start_time'])) : 'Chưa có' ?></td>
                                <td>
                                    <?php
                                        $status = $event['status'];
                                        $badgeClass = 'badge-info';
                                        if ($status === 'completed') $badgeClass = 'badge-success';
                                        elseif ($status === 'cancelled') $badgeClass = 'badge-danger';
                                        elseif ($status === 'ongoing') $badgeClass = 'badge-primary';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= $status === 'upcoming' ? 'Sắp diễn ra' : ($status === 'ongoing' ? 'Đang diễn ra' : ($status === 'completed' ? 'Đã kết thúc' : 'Đã hủy')) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($event['location'] ?? '') ?></td>
                                <td><?= date('d/m/Y', strtotime($event['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="../chi_tiet_su_kien.php?id=<?= $event['id'] ?>" class="btn-icon btn-view" title="Xem">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </a>
                                        <a href="events.php?edit_id=<?= $event['id'] ?>" class="btn-icon btn-edit" title="Chỉnh sửa">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </a>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sự kiện này?');">
                                            <?= csrf_token_input(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                            <button class="btn-icon btn-delete" type="submit" title="Xóa">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">Không có dữ liệu</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                <a href="?page=<?= $current_page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="page-btn">Trước</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                   class="page-num <?= $i === $current_page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?= $current_page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="page-btn">Sau</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../assets/js/admin.js"></script>
    <script>
    (function() {
        const createModal = document.getElementById('createEventModal');
        const editModal = document.getElementById('editEventModal');
        const btnOpen = document.getElementById('btnOpenCreateEvent');
        const btnClose = document.getElementById('btnCloseCreateEvent');
        const btnCancel = document.getElementById('btnCancelCreateEvent');
        const btnCloseEdit = document.getElementById('btnCloseEditEvent');

        const closeModal = (m) => m && m.classList.remove('open');
        const openModal = (m) => m && m.classList.add('open');

        if (btnOpen && createModal) btnOpen.addEventListener('click', () => openModal(createModal));
        [btnClose, btnCancel].forEach(b => b && b.addEventListener('click', () => closeModal(createModal)));
        if (createModal) createModal.addEventListener('click', (e) => { if (e.target === createModal) closeModal(createModal); });

        if (btnCloseEdit && editModal) btnCloseEdit.addEventListener('click', () => closeModal(editModal));
        if (editModal) {
            editModal.addEventListener('click', (e) => { if (e.target === editModal) closeModal(editModal); });
            openModal(editModal); // mở sẵn khi có edit_id
        }
    })();
    </script>
</body>
</html>

