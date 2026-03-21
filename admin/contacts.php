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
// Flash từ redirect
if (isset($_SESSION['flash_contacts'])) {
    $message = $_SESSION['flash_contacts']['message'] ?? null;
    $message_type = $_SESSION['flash_contacts']['type'] ?? 'success';
    unset($_SESSION['flash_contacts']);
}

// Xử lý hành động
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        $message = 'Thiếu thông tin liên hệ.';
        $message_type = 'error';
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM lienhe WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['flash_contacts'] = ['message' => 'Đã xóa liên hệ.', 'type' => 'success'];
            header('Location: contacts.php');
            exit;
        } else {
            $message = 'Lỗi khi xóa liên hệ.';
            $message_type = 'error';
        }
        $stmt->close();
    } elseif ($action === 'status') {
        $new_status = $_POST['status'] ?? '';
        if (!in_array($new_status, ['new', 'read', 'replied'], true)) {
            $message = 'Trạng thái không hợp lệ.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE lienhe SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $id);
            if ($stmt->execute()) {
                $label = $new_status === 'read' ? 'đã đọc' : ($new_status === 'replied' ? 'đã trả lời' : 'mới');
                $_SESSION['flash_contacts'] = ['message' => "Đã cập nhật trạng thái ($label).", 'type' => 'success'];
                header('Location: contacts.php');
                exit;
            } else {
                $message = 'Lỗi khi cập nhật trạng thái.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

$items_per_page = 20;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where_clause = '';
if (!empty($status_filter)) {
    $where_clause = "WHERE status = '" . $conn->real_escape_string($status_filter) . "'";
}

$count_sql = "SELECT COUNT(*) as total FROM lienhe $where_clause";
$result = $conn->query($count_sql);
$total_contacts = $result->fetch_assoc()['total'];
$total_pages = ceil($total_contacts / $items_per_page);

$sql = "SELECT * FROM lienhe $where_clause ORDER BY created_at DESC LIMIT $items_per_page OFFSET $offset";
$contacts = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Liên hệ - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin/admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-main">
        <?php include 'includes/header.php'; ?>
        
        <div class="admin-content">
            <div class="page-header">
                <div>
                    <h1>Quản lý Liên hệ</h1>
                    <p>Tổng cộng: <strong><?= number_format($total_contacts) ?></strong> liên hệ</p>
                    <?php if ($message): ?>
                        <div class="alert <?= $message_type === 'success' ? 'alert-success' : 'alert-danger' ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="filter-bar">
                <form method="GET" class="filter-form">
                    <select name="status" class="filter-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="new" <?= $status_filter === 'new' ? 'selected' : '' ?>>Mới</option>
                        <option value="read" <?= $status_filter === 'read' ? 'selected' : '' ?>>Đã đọc</option>
                        <option value="replied" <?= $status_filter === 'replied' ? 'selected' : '' ?>>Đã trả lời</option>
                    </select>
                    <button type="submit" class="btn-primary">Lọc</button>
                    <?php if (!empty($status_filter)): ?>
                    <a href="contacts.php" class="btn-secondary">Xóa bộ lọc</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Chủ đề</th>
                            <th>Trạng thái</th>
                            <th>Ngày gửi</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($contacts)): ?>
                            <?php foreach ($contacts as $contact): ?>
                            <tr>
                                <td><?= $contact['id'] ?></td>
                                <td><?= htmlspecialchars($contact['name']) ?></td>
                                <td><?= htmlspecialchars($contact['email']) ?></td>
                                <td><?= htmlspecialchars($contact['subject']) ?></td>
                                <td>
                                    <span class="badge <?= $contact['status'] === 'new' ? 'badge-warning' : ($contact['status'] === 'replied' ? 'badge-success' : 'badge-info') ?>">
                                        <?= $contact['status'] === 'new' ? 'Mới' : ($contact['status'] === 'replied' ? 'Đã trả lời' : 'Đã đọc') ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($contact['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon btn-view" type="button"
                                            data-open-modal
                                            data-id="<?= $contact['id'] ?>"
                                            data-name="<?= htmlspecialchars($contact['name'], ENT_QUOTES) ?>"
                                            data-email="<?= htmlspecialchars($contact['email'], ENT_QUOTES) ?>"
                                            data-subject="<?= htmlspecialchars($contact['subject'], ENT_QUOTES) ?>"
                                            data-message="<?= htmlspecialchars($contact['message'] ?? '', ENT_QUOTES) ?>"
                                            data-status="<?= $contact['status'] ?>"
                                            title="Xem chi tiết"
                                        >
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Xóa liên hệ này?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $contact['id'] ?>">
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

            <!-- Modal xem/trả lời liên hệ -->
            <div class="modal" id="contactModal">
                <div class="modal-dialog">
                    <div class="modal-header">
                        <h3>Chi tiết liên hệ</h3>
                        <button type="button" class="modal-close" id="btnCloseContact">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="contact-detail">
                            <div class="detail-row"><strong>Họ tên:</strong> <span id="cName"></span></div>
                            <div class="detail-row"><strong>Email:</strong> <span id="cEmail"></span></div>
                            <div class="detail-row"><strong>Chủ đề:</strong> <span id="cSubject"></span></div>
                            <div class="detail-row"><strong>Nội dung:</strong>
                                <div class="detail-message" id="cMessage"></div>
                            </div>
                        </div>
                        <div class="form-actions" style="justify-content:flex-start; gap:10px;">
                            <form method="POST" id="formMarkRead" style="display:none">
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="id" value="">
                                <input type="hidden" name="status" value="read">
                                <button type="submit" class="btn-pill">Đánh dấu đã đọc</button>
                            </form>
                            <form method="POST" id="formMarkReplied" style="display:none">
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="id" value="">
                                <input type="hidden" name="status" value="replied">
                                <button type="submit" class="btn-pill success">Đánh dấu đã trả lời</button>
                            </form>
                            <a href="#" id="mailtoBtn" class="btn-pill" target="_blank">Gửi email</a>
                            <button type="button" class="btn-secondary" id="btnCloseContact2">Đóng</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                <a href="?page=<?= $current_page - 1 ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>" class="page-btn">Trước</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                <a href="?page=<?= $i ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>" 
                   class="page-num <?= $i === $current_page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?= $current_page + 1 ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>" class="page-btn">Sau</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../assets/js/admin.js"></script>
    <script>
    (function() {
        const modal = document.getElementById('contactModal');
        const btnClose = document.getElementById('btnCloseContact');
        const btnClose2 = document.getElementById('btnCloseContact2');
        const cName = document.getElementById('cName');
        const cEmail = document.getElementById('cEmail');
        const cSubject = document.getElementById('cSubject');
        const cMessage = document.getElementById('cMessage');
        const formRead = document.getElementById('formMarkRead');
        const formReplied = document.getElementById('formMarkReplied');
        const mailtoBtn = document.getElementById('mailtoBtn');

        const openModal = (data) => {
            if (!modal) return;
            modal.classList.add('open');
            cName.textContent = data.name || '';
            cEmail.textContent = data.email || '';
            cSubject.textContent = data.subject || '';
            cMessage.textContent = data.message || '';

            // Điền ID vào form
            formRead.querySelector('input[name="id"]').value = data.id;
            formReplied.querySelector('input[name="id"]').value = data.id;

            // Hiển thị/ẩn nút theo trạng thái
            formRead.style.display = data.status === 'read' || data.status === 'replied' ? 'none' : 'inline-block';
            formReplied.style.display = data.status === 'replied' ? 'none' : 'inline-block';

            // Mailto
            const subject = encodeURIComponent('[Trả lời liên hệ] ' + (data.subject || ''));
            const body = encodeURIComponent('\n\n---\nNội dung liên hệ:\n' + (data.message || ''));
            mailtoBtn.href = 'mailto:' + (data.email || '') + '?subject=' + subject + '&body=' + body;
        };

        const closeModal = () => modal && modal.classList.remove('open');

        document.querySelectorAll('[data-open-modal]').forEach(btn => {
            btn.addEventListener('click', () => {
                openModal({
                    id: btn.dataset.id,
                    name: btn.dataset.name,
                    email: btn.dataset.email,
                    subject: btn.dataset.subject,
                    message: btn.dataset.message,
                    status: btn.dataset.status
                });
            });
        });

        [btnClose, btnClose2].forEach(b => b && b.addEventListener('click', closeModal));
        if (modal) {
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
        }
    })();
    </script>
</body>
</html>

