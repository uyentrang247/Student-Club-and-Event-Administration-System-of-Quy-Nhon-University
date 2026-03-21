<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../assets/database/connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = null;
$message_type = 'success';
if (isset($_SESSION['flash_notif'])) {
    $message = $_SESSION['flash_notif']['message'] ?? null;
    $message_type = $_SESSION['flash_notif']['type'] ?? 'success';
    unset($_SESSION['flash_notif']);
}

// Xử lý thêm/sửa/xóa/đổi trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($action === 'create' || $action === 'update') {
        $user_id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $type = trim($_POST['type'] ?? 'system');
        $title = trim($_POST['title'] ?? '');
        $message_body = trim($_POST['message'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $is_read = isset($_POST['is_read']) ? 1 : 0;

        if ($user_id <= 0 || $title === '') {
            $message = 'Vui lòng chọn người nhận và nhập tiêu đề.';
            $message_type = 'error';
        } else {
            if ($action === 'create') {
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link, is_read) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssi", $user_id, $type, $title, $message_body, $link, $is_read);
                if ($stmt->execute()) {
                    $_SESSION['flash_notif'] = ['message' => 'Thêm thông báo thành công.', 'type' => 'success'];
                    header('Location: notifications.php');
                    exit;
                } else {
                    $message = 'Lỗi khi thêm thông báo.';
                    $message_type = 'error';
                }
                $stmt->close();
            } elseif ($action === 'update') {
                if ($id <= 0) {
                    $message = 'Thiếu thông tin thông báo.';
                    $message_type = 'error';
                } else {
                    $stmt = $conn->prepare("UPDATE notifications SET user_id=?, type=?, title=?, message=?, link=?, is_read=? WHERE id=?");
                    $stmt->bind_param("issssii", $user_id, $type, $title, $message_body, $link, $is_read, $id);
                    if ($stmt->execute()) {
                        $_SESSION['flash_notif'] = ['message' => 'Cập nhật thông báo thành công.', 'type' => 'success'];
                        header('Location: notifications.php');
                        exit;
                    } else {
                        $message = 'Lỗi khi cập nhật thông báo.';
                        $message_type = 'error';
                    }
                    $stmt->close();
                }
            }
        }
    } elseif ($action === 'delete') {
        if ($id <= 0) {
            $message = 'Thiếu thông tin thông báo.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['flash_notif'] = ['message' => 'Đã xóa thông báo.', 'type' => 'success'];
                header('Location: notifications.php');
                exit;
            } else {
                $message = 'Lỗi khi xóa thông báo.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    } elseif ($action === 'toggle') {
        if ($id <= 0) {
            $message = 'Thiếu thông tin thông báo.';
            $message_type = 'error';
        } else {
            $set_read = isset($_POST['is_read']) ? (int)$_POST['is_read'] : 0;
            $stmt = $conn->prepare("UPDATE notifications SET is_read = ? WHERE id = ?");
            $stmt->bind_param("ii", $set_read, $id);
            if ($stmt->execute()) {
                $_SESSION['flash_notif'] = ['message' => ($set_read ? 'Đã đánh dấu đọc.' : 'Đã đánh dấu chưa đọc.'), 'type' => 'success'];
                header('Location: notifications.php');
                exit;
            } else {
                $message = 'Lỗi khi cập nhật trạng thái.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

$items_per_page = 50;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$read_filter = isset($_GET['read']) ? $_GET['read'] : '';

$where_conditions = [];
if (!empty($type_filter)) {
    $where_conditions[] = "type = '" . $conn->real_escape_string($type_filter) . "'";
}
if ($read_filter !== '') {
    $where_conditions[] = "is_read = " . (int)$read_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$count_sql = "SELECT COUNT(*) as total FROM notifications $where_clause";
$result = $conn->query($count_sql);
$total_notifications = $result->fetch_assoc()['total'];
$total_pages = ceil($total_notifications / $items_per_page);

$sql = "SELECT n.*, u.ho_ten, u.username 
        FROM notifications n
        LEFT JOIN users u ON n.user_id = u.id
        $where_clause
        ORDER BY n.created_at DESC
        LIMIT $items_per_page OFFSET $offset";
$notifications = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Danh sách user cho select
$users = $conn->query("SELECT id, ho_ten, username FROM users ORDER BY ho_ten ASC")->fetch_all(MYSQLI_ASSOC);

// Thông báo đang chỉnh sửa
$edit_notif = null;
if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_notif = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Thông báo - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin/admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-main">
        <?php include 'includes/header.php'; ?>
        
        <div class="admin-content">
            <div class="page-header">
                <div>
                    <h1>Quản lý Thông báo</h1>
                    <p>Tổng cộng: <strong><?= number_format($total_notifications) ?></strong> thông báo</p>
                    <?php if ($message): ?>
                        <div class="alert <?= $message_type === 'success' ? 'alert-success' : 'alert-danger' ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <button type="button" class="btn-primary" id="btnOpenCreateNotif">Thêm mới</button>
                </div>
            </div>
            
            <div class="filter-bar">
                <form method="GET" class="filter-form">
                    <select name="type" class="filter-select">
                        <option value="">Tất cả loại</option>
                        <option value="club_join" <?= $type_filter === 'club_join' ? 'selected' : '' ?>>Tham gia CLB</option>
                        <option value="event_invite" <?= $type_filter === 'event_invite' ? 'selected' : '' ?>>Mời sự kiện</option>
                        <option value="system" <?= $type_filter === 'system' ? 'selected' : '' ?>>Hệ thống</option>
                    </select>
                    
                    <select name="read" class="filter-select">
                        <option value="">Tất cả</option>
                        <option value="0" <?= $read_filter === '0' ? 'selected' : '' ?>>Chưa đọc</option>
                        <option value="1" <?= $read_filter === '1' ? 'selected' : '' ?>>Đã đọc</option>
                    </select>
                    
                    <button type="submit" class="btn-primary">Lọc</button>
                    <?php if (!empty($type_filter) || $read_filter !== ''): ?>
                    <a href="notifications.php" class="btn-secondary">Xóa bộ lọc</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Người nhận</th>
                            <th>Loại</th>
                            <th>Tiêu đề</th>
                            <th>Nội dung</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($notifications)): ?>
                            <?php foreach ($notifications as $notif): ?>
                            <tr>
                                <td><?= $notif['id'] ?></td>
                                <td><?= htmlspecialchars($notif['ho_ten'] ?? $notif['username'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge badge-info"><?= htmlspecialchars($notif['type']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($notif['title']) ?></td>
                                <td><?= htmlspecialchars(mb_substr($notif['message'] ?? '', 0, 80)) ?><?= mb_strlen($notif['message'] ?? '') > 80 ? '...' : '' ?></td>
                                <td>
                                    <span class="badge <?= $notif['is_read'] ? 'badge-success' : 'badge-warning' ?>">
                                        <?= $notif['is_read'] ? 'Đã đọc' : 'Chưa đọc' ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a class="btn-icon btn-edit" href="notifications.php?edit_id=<?= $notif['id'] ?>" title="Chỉnh sửa">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </a>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Xóa thông báo này?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $notif['id'] ?>">
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
                <a href="?page=<?= $current_page - 1 ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?><?= $read_filter !== '' ? '&read=' . $read_filter : '' ?>" class="page-btn">Trước</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                <a href="?page=<?= $i ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?><?= $read_filter !== '' ? '&read=' . $read_filter : '' ?>" 
                   class="page-num <?= $i === $current_page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?= $current_page + 1 ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?><?= $read_filter !== '' ? '&read=' . $read_filter : '' ?>" class="page-btn">Sau</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Modal thêm thông báo -->
            <div class="modal" id="createNotifModal">
                <div class="modal-dialog">
                    <div class="modal-header">
                        <h3>Thêm thông báo</h3>
                        <button type="button" class="modal-close" id="btnCloseCreateNotif">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" class="form-grid">
                            <input type="hidden" name="action" value="create">
                            <div class="form-group">
                                <label>Người nhận</label>
                                <select name="user_id" required>
                                    <option value="">-- Chọn người dùng --</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['ho_ten'] ?: $u['username']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Loại</label>
                                <select name="type">
                                    <option value="system">Hệ thống</option>
                                    <option value="club_join">Tham gia CLB</option>
                                    <option value="event_invite">Mời sự kiện</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tiêu đề</label>
                                <input type="text" name="title" required>
                            </div>
                            <div class="form-group">
                                <label>Link (tùy chọn)</label>
                                <input type="text" name="link" placeholder="https://...">
                            </div>
                            <div class="form-group" style="grid-column:1/-1;">
                                <label>Nội dung</label>
                                <textarea name="message" rows="3" placeholder="Nội dung thông báo"></textarea>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_read" value="1"> Đánh dấu đã đọc
                                </label>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Lưu</button>
                                <button type="button" class="btn-secondary" id="btnCancelCreateNotif">Hủy</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal chỉnh sửa -->
            <?php if ($edit_notif): ?>
            <div class="modal open" id="editNotifModal">
                <div class="modal-dialog">
                    <div class="modal-header">
                        <h3>Chỉnh sửa: <?= htmlspecialchars($edit_notif['title']) ?></h3>
                        <button type="button" class="modal-close" id="btnCloseEditNotif">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" class="form-grid">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $edit_notif['id'] ?>">
                            <div class="form-group">
                                <label>Người nhận</label>
                                <select name="user_id" required>
                                    <option value="">-- Chọn người dùng --</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?= $u['id'] ?>" <?= $edit_notif['user_id'] == $u['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($u['ho_ten'] ?: $u['username']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Loại</label>
                                <select name="type">
                                    <option value="system" <?= $edit_notif['type'] === 'system' ? 'selected' : '' ?>>Hệ thống</option>
                                    <option value="club_join" <?= $edit_notif['type'] === 'club_join' ? 'selected' : '' ?>>Tham gia CLB</option>
                                    <option value="event_invite" <?= $edit_notif['type'] === 'event_invite' ? 'selected' : '' ?>>Mời sự kiện</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tiêu đề</label>
                                <input type="text" name="title" value="<?= htmlspecialchars($edit_notif['title']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Link (tùy chọn)</label>
                                <input type="text" name="link" value="<?= htmlspecialchars($edit_notif['link'] ?? '') ?>">
                            </div>
                            <div class="form-group" style="grid-column:1/-1;">
                                <label>Nội dung</label>
                                <textarea name="message" rows="3"><?= htmlspecialchars($edit_notif['message'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_read" value="1" <?= $edit_notif['is_read'] ? 'checked' : '' ?>> Đánh dấu đã đọc
                                </label>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Lưu thay đổi</button>
                                <a href="notifications.php" class="btn-secondary">Hủy</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../assets/js/admin.js"></script>
    <script>
    (function() {
        const createModal = document.getElementById('createNotifModal');
        const editModal = document.getElementById('editNotifModal');
        const btnOpen = document.getElementById('btnOpenCreateNotif');
        const btnClose = document.getElementById('btnCloseCreateNotif');
        const btnCancel = document.getElementById('btnCancelCreateNotif');
        const btnCloseEdit = document.getElementById('btnCloseEditNotif');

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

