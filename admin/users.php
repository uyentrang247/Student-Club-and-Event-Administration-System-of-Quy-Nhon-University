<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../assets/database/connect.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = null;
$message_type = 'success';
$edit_user = null;

// Flash message (sau redirect)
if (isset($_SESSION['flash_users'])) {
    $message = $_SESSION['flash_users']['message'] ?? null;
    $message_type = $_SESSION['flash_users']['type'] ?? 'success';
    unset($_SESSION['flash_users']);
}

// Xử lý thêm/sửa/xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role  = $_POST['role'] === 'admin' ? 'admin' : 'member';

        if ($full_name === '' || $username === '' || $password === '') {
            $message = 'Vui lòng nhập họ tên, username và mật khẩu.';
            $message_type = 'error';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Email không hợp lệ.';
            $message_type = 'error';
        } else {
            // Kiểm tra trùng username
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $message = 'Username đã tồn tại.';
                $message_type = 'error';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $full_name, $username, $email, $hashed, $role);
                if ($stmt->execute()) {
                    $_SESSION['flash_users'] = ['message' => 'Thêm người dùng thành công.', 'type' => 'success'];
                    header('Location: users.php');
                    exit;
                } else {
                    $message = 'Lỗi khi thêm người dùng.';
                    $message_type = 'error';
                }
                $stmt->close();
            }
            $check->close();
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role  = $_POST['role'] === 'admin' ? 'admin' : 'member';
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || $full_name === '') {
            $message = 'Thiếu thông tin để cập nhật.';
            $message_type = 'error';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Email không hợp lệ.';
            $message_type = 'error';
        } else {
            if ($password !== '') {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $full_name, $email, $hashed, $role, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, role = ? WHERE id = ?");
                $stmt->bind_param("sssi", $full_name, $email, $role, $id);
            }
            if ($stmt->execute()) {
                $_SESSION['flash_users'] = ['message' => 'Cập nhật người dùng thành công.', 'type' => 'success'];
                // nếu đang chỉnh sửa chính mình, cập nhật session display name
                if ($id === ($_SESSION['admin_id'] ?? 0)) {
                    $_SESSION['admin_name'] = $full_name;
                    $_SESSION['admin_email'] = $email;
                }
                header('Location: users.php');
                exit;
            } else {
                $message = 'Lỗi khi cập nhật người dùng.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $message = 'Thiếu thông tin để xóa.';
            $message_type = 'error';
        } elseif ($id === ($_SESSION['admin_id'] ?? 0)) {
            $message = 'Không thể tự xóa tài khoản của bạn.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['flash_users'] = ['message' => 'Đã xóa người dùng.', 'type' => 'success'];
                header('Location: users.php');
                exit;
            } else {
                $message = 'Lỗi khi xóa người dùng.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// Lấy dữ liệu user cần chỉnh sửa nếu có
if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT id, full_name, username, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Phân trang
$items_per_page = 20;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Tìm kiếm và filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR username LIKE ? OR email LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($role_filter)) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Đếm tổng số users
$count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_users = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $result = $conn->query($count_sql);
    $total_users = $result->fetch_assoc()['total'];
}
$total_pages = ceil($total_users / $items_per_page);

// Lấy danh sách users
$sql = "SELECT id, full_name, username, email, role, created_at, avatar FROM users $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
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
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Người dùng - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin/admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-main">
        <?php include 'includes/header.php'; ?>
        
        <div class="admin-content">
            <div class="page-header">
                <div>
                    <h1>Quản lý Người dùng</h1>
                    <p>Tổng cộng: <strong><?= number_format($total_users) ?></strong> người dùng</p>
                    <?php if ($message): ?>
                        <div class="alert <?= $message_type === 'success' ? 'alert-success' : 'alert-danger' ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <button type="button" class="btn-primary" id="btnOpenCreate">Thêm mới</button>
                </div>
            </div>

            <!-- Modal thêm mới -->
            <div class="modal" id="createModal">
                <div class="modal-dialog">
                    <div class="modal-header">
                        <h3>Thêm người dùng</h3>
                        <button type="button" class="modal-close" id="btnCloseCreate">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" class="form-grid">
                            <input type="hidden" name="action" value="create">
                            <div class="form-group">
                                <label>Họ tên</label>
                                <input type="text" name="full_name" required>
                            </div>
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" placeholder="Tuỳ chọn">
                            </div>
                            <div class="form-group">
                                <label>Mật khẩu</label>
                                <input type="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <label>Vai trò</label>
                                <select name="role">
                                    <option value="member">Thành viên</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Lưu</button>
                                <button type="button" class="btn-secondary" id="btnCancelCreate">Hủy</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal chỉnh sửa (khi có edit_id) -->
            <?php if ($edit_user): ?>
            <div class="modal open" id="editModal">
                <div class="modal-dialog">
                    <div class="modal-header">
                        <h3>Chỉnh sửa: <?= htmlspecialchars($edit_user['username']) ?></h3>
                        <button type="button" class="modal-close" id="btnCloseEdit">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" class="form-grid">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $edit_user['id'] ?>">
                            <div class="form-group">
                                <label>Họ tên</label>
                                <input type="text" name="full_name" value="<?= htmlspecialchars($edit_user['full_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" value="<?= htmlspecialchars($edit_user['username']) ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Mật khẩu mới (để trống nếu không đổi)</label>
                                <input type="password" name="password" placeholder="••••••">
                            </div>
                            <div class="form-group">
                                <label>Vai trò</label>
                                <select name="role">
                                    <option value="member" <?= $edit_user['role'] === 'member' ? 'selected' : '' ?>>Thành viên</option>
                                    <option value="admin" <?= $edit_user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Lưu thay đổi</button>
                                <a href="users.php" class="btn-secondary">Hủy</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Search and Filter -->
            <div class="filter-bar">
                <form method="GET" class="filter-form">
                    <div class="search-box">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" name="search" placeholder="Tìm kiếm theo tên, username hoặc email..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <select name="role" class="filter-select">
                        <option value="">Tất cả vai trò</option>
                        <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="member" <?= $role_filter === 'member' ? 'selected' : '' ?>>Thành viên</option>
                    </select>
                    
                    <button type="submit" class="btn-primary">Tìm kiếm</button>
                    
                    <?php if (!empty($search) || !empty($role_filter)): ?>
                    <a href="users.php" class="btn-secondary">Xóa bộ lọc</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Users Table -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Người dùng</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Vai trò</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <div class="user-cell">
                                        <img src="<?= !empty($user['avatar']) ? '../' . htmlspecialchars($user['avatar']) : '../assets/img/avatars/user.svg' ?>" 
                                             alt="<?= htmlspecialchars($user['full_name'] ?? 'User') ?>" 
                                             class="user-avatar-small">
                                        <span><?= htmlspecialchars($user['full_name'] ?? 'Chưa cập nhật') ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email'] ?? 'Chưa có') ?></td>
                                <td>
                                    <span class="badge <?= $user['role'] === 'admin' ? 'badge-danger' : 'badge-primary' ?>">
                                        <?= $user['role'] === 'admin' ? 'Admin' : 'Thành viên' ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a class="btn-icon btn-edit" href="users.php?edit_id=<?= $user['id'] ?>" title="Chỉnh sửa">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </a>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
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
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                <a href="?page=<?= $current_page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($role_filter) ? '&role=' . urlencode($role_filter) : '' ?>" class="page-btn">Trước</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($role_filter) ? '&role=' . urlencode($role_filter) : '' ?>" 
                   class="page-num <?= $i === $current_page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?= $current_page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($role_filter) ? '&role=' . urlencode($role_filter) : '' ?>" class="page-btn">Sau</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../assets/js/admin.js"></script>
    <script>
    (function() {
        const modal = document.getElementById('createModal');
        const editModal = document.getElementById('editModal');
        const btnOpen = document.getElementById('btnOpenCreate');
        const btnClose = document.getElementById('btnCloseCreate');
        const btnCancel = document.getElementById('btnCancelCreate');
        const btnCloseEdit = document.getElementById('btnCloseEdit');

        const closeModal = () => modal && modal.classList.remove('open');
        const openModal = () => modal && modal.classList.add('open');
        const closeEdit = () => editModal && editModal.classList.remove('open');

        if (btnOpen && modal) btnOpen.addEventListener('click', openModal);
        if (btnClose && modal) btnClose.addEventListener('click', closeModal);
        if (btnCancel && modal) btnCancel.addEventListener('click', closeModal);
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        }

        if (btnCloseEdit && editModal) {
            btnCloseEdit.addEventListener('click', closeEdit);
            editModal.addEventListener('click', (e) => {
                if (e.target === editModal) closeEdit();
            });
        }
    })();
    </script>
</body>
</html>

