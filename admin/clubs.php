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
if (isset($_SESSION['flash_clubs'])) {
    $message = $_SESSION['flash_clubs']['message'] ?? null;
    $message_type = $_SESSION['flash_clubs']['type'] ?? 'success';
    unset($_SESSION['flash_clubs']);
}

// Xử lý thêm/sửa/xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#667eea');
        $founded_date = !empty($_POST['founded_date']) ? $_POST['founded_date'] : null;
        $leader_id = !empty($_POST['leader_id']) ? (int)$_POST['leader_id'] : null;
        $total_members = isset($_POST['total_members']) ? max(0, (int)$_POST['total_members']) : 0;

        if ($name === '' || $category === '') {
            $message = 'Vui lòng nhập tên CLB và lĩnh vực.';
            $message_type = 'error';
        } else {
            if ($action === 'create') {
                $stmt = $conn->prepare("INSERT INTO clubs (name, description, category, total_members, color, founded_date, leader_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssissi", $name, $description, $category, $total_members, $color, $founded_date, $leader_id);
                if ($stmt->execute()) {
                    $_SESSION['flash_clubs'] = ['message' => 'Thêm CLB thành công.', 'type' => 'success'];
                    header('Location: clubs.php');
                    exit;
                } else {
                    $message = 'Lỗi khi thêm CLB.';
                    $message_type = 'error';
                }
                $stmt->close();
            } elseif ($action === 'update') {
                if ($id <= 0) {
                    $message = 'Thiếu thông tin CLB.';
                    $message_type = 'error';
                } else {
                    $stmt = $conn->prepare("UPDATE clubs SET name=?, description=?, category=?, total_members=?, color=?, founded_date=?, leader_id=? WHERE id=?");
                    $stmt->bind_param("sssissii", $name, $description, $category, $total_members, $color, $founded_date, $leader_id, $id);
                    if ($stmt->execute()) {
                        $_SESSION['flash_clubs'] = ['message' => 'Cập nhật CLB thành công.', 'type' => 'success'];
                        header('Location: clubs.php');
                        exit;
                    } else {
                        $message = 'Lỗi khi cập nhật CLB.';
                        $message_type = 'error';
                    }
                    $stmt->close();
                }
            }
        }
    } elseif ($action === 'delete') {
        if ($id <= 0) {
            $message = 'Thiếu thông tin CLB.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("DELETE FROM clubs WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['flash_clubs'] = ['message' => 'Đã xóa CLB.', 'type' => 'success'];
                header('Location: clubs.php');
                exit;
            } else {
                $message = 'Lỗi khi xóa CLB.';
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
    $where_clause = "WHERE c.name LIKE ? OR c.category LIKE ?";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types = 'ss';
}

$count_sql = "SELECT COUNT(*) as total FROM clubs c $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_clubs = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $result = $conn->query($count_sql);
    $total_clubs = $result->fetch_assoc()['total'];
}
$total_pages = ceil($total_clubs / $items_per_page);

$sql = "SELECT c.id, c.name, c.category, c.total_members, c.color, c.created_at, u.full_name as leader_name
        FROM clubs c
        LEFT JOIN users u ON c.leader_id = u.id
        $where_clause
        ORDER BY c.created_at DESC
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
$clubs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Danh sách leader
$leaders = $conn->query("SELECT id, full_name, username FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

// CLB đang chỉnh sửa
$edit_club = null;
if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM clubs WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_club = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Câu lạc bộ - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin/admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-main">
        <?php include 'includes/header.php'; ?>
        
        <div class="admin-content">
            <div class="page-header">
                <div>
                    <h1>Quản lý Câu lạc bộ</h1>
                    <p>Tổng cộng: <strong><?= number_format($total_clubs) ?></strong> câu lạc bộ</p>
                    <?php if ($message): ?>
                        <div class="alert <?= $message_type === 'success' ? 'alert-success' : 'alert-danger' ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <button type="button" class="btn-primary" id="btnOpenCreateClub">Thêm mới</button>
                </div>
            </div>

            <!-- Modal thêm CLB -->
            <div class="modal" id="createClubModal">
                <div class="modal-dialog">
                    <div class="modal-header">
                        <h3>Thêm Câu lạc bộ</h3>
                        <button type="button" class="modal-close" id="btnCloseCreateClub">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" class="form-grid">
                            <input type="hidden" name="action" value="create">
                            <div class="form-group">
                                <label>Tên CLB</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-group">
                                <label>Lĩnh vực</label>
                                <input type="text" name="category" required>
                            </div>
                            <div class="form-group">
                                <label>Số thành viên</label>
                                <input type="number" name="total_members" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>Ngày thành lập</label>
                                <input type="date" name="founded_date">
                            </div>
                            <div class="form-group">
                                <label>Đội trưởng</label>
                                <select name="leader_id">
                                    <option value="">-- Chọn --</option>
                                    <?php foreach ($leaders as $leader): ?>
                                        <option value="<?= $leader['id'] ?>"><?= htmlspecialchars($leader['full_name'] ?: $leader['username']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Màu hiển thị</label>
                                <input type="color" name="color" value="#667eea">
                            </div>
                            <div class="form-group" style="grid-column:1/-1;">
                                <label>Mô tả</label>
                                <textarea name="description" rows="3" placeholder="Giới thiệu ngắn về CLB"></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Lưu</button>
                                <button type="button" class="btn-secondary" id="btnCancelCreateClub">Hủy</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal chỉnh sửa CLB -->
            <?php if ($edit_club): ?>
            <div class="modal open" id="editClubModal">
                <div class="modal-dialog">
                    <div class="modal-header">
                        <h3>Chỉnh sửa: <?= htmlspecialchars($edit_club['name']) ?></h3>
                        <button type="button" class="modal-close" id="btnCloseEditClub">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" class="form-grid">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $edit_club['id'] ?>">
                            <div class="form-group">
                                <label>Tên CLB</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($edit_club['name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Lĩnh vực</label>
                                <input type="text" name="category" value="<?= htmlspecialchars($edit_club['category']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Số thành viên</label>
                                <input type="number" name="total_members" min="0" value="<?= (int)$edit_club['total_members'] ?>">
                            </div>
                            <div class="form-group">
                                <label>Ngày thành lập</label>
                                <input type="date" name="founded_date" value="<?= $edit_club['founded_date'] ?? '' ?>">
                            </div>
                            <div class="form-group">
                                <label>Đội trưởng</label>
                                <select name="leader_id">
                                    <option value="">-- Chọn --</option>
                                    <?php foreach ($leaders as $leader): ?>
                                        <option value="<?= $leader['id'] ?>" <?= ($edit_club['leader_id'] ?? null) == $leader['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($leader['full_name'] ?: $leader['username']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Màu hiển thị</label>
                                <input type="color" name="color" value="<?= htmlspecialchars($edit_club['color'] ?? '#667eea') ?>">
                            </div>
                            <div class="form-group" style="grid-column:1/-1;">
                                <label>Mô tả</label>
                                <textarea name="description" rows="3" placeholder="Giới thiệu ngắn về CLB"><?= htmlspecialchars($edit_club['description'] ?? '') ?></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Lưu thay đổi</button>
                                <a href="clubs.php" class="btn-secondary">Hủy</a>
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
                        <input type="text" name="search" placeholder="Tìm kiếm theo tên hoặc lĩnh vực..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button type="submit" class="btn-primary">Tìm kiếm</button>
                    <?php if (!empty($search)): ?>
                    <a href="clubs.php" class="btn-secondary">Xóa bộ lọc</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên CLB</th>
                            <th>Lĩnh vực</th>
                            <th>Đội trưởng</th>
                            <th>Số thành viên</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($clubs)): ?>
                            <?php foreach ($clubs as $club): ?>
                            <tr>
                                <td><?= $club['id'] ?></td>
                                <td>
                                    <div class="club-name-cell">
                                        <span class="color-dot" style="background: <?= htmlspecialchars($club['color'] ?? '#667eea') ?>"></span>
                                        <span><?= htmlspecialchars($club['name']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($club['category']) ?></td>
                                <td><?= htmlspecialchars($club['leader_name'] ?? 'Chưa có') ?></td>
                                <td><?= number_format($club['total_members']) ?></td>
                                <td><?= date('d/m/Y', strtotime($club['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="../club-detail.php?id=<?= $club['id'] ?>" class="btn-icon btn-view" title="Xem">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </a>
                                        <a href="clubs.php?edit_id=<?= $club['id'] ?>" class="btn-icon btn-edit" title="Chỉnh sửa">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </a>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa câu lạc bộ này?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $club['id'] ?>">
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
        const createModal = document.getElementById('createClubModal');
        const btnOpenCreate = document.getElementById('btnOpenCreateClub');
        const btnCloseCreate = document.getElementById('btnCloseCreateClub');
        const btnCancelCreate = document.getElementById('btnCancelCreateClub');
        const editModal = document.getElementById('editClubModal');
        const btnCloseEdit = document.getElementById('btnCloseEditClub');
        
        const closeModal = (m) => m && m.classList.remove('open');
        const openModal = (m) => m && m.classList.add('open');

        if (btnOpenCreate && createModal) btnOpenCreate.addEventListener('click', () => openModal(createModal));
        [btnCloseCreate, btnCancelCreate].forEach(b => b && b.addEventListener('click', () => closeModal(createModal)));
        if (createModal) createModal.addEventListener('click', (e) => { if (e.target === createModal) closeModal(createModal); });

        if (btnCloseEdit && editModal) btnCloseEdit.addEventListener('click', () => closeModal(editModal));
        if (editModal) {
            editModal.addEventListener('click', (e) => { if (e.target === editModal) closeModal(editModal); });
            // mở sẵn khi có edit_id
            openModal(editModal);
        }
    })();
    </script>
</body>
</html>

