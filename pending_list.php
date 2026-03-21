<?php
session_start();
require_once('assets/database/connect.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    echo '<div class="empty-state"><p>Vui lòng đăng nhập</p></div>';
    exit;
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'];

// Lấy club_id từ session hoặc GET
$club_id = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;

if (!$club_id && isset($_SESSION['club_id'])) {
    $club_id = (int)$_SESSION['club_id'];
}

// Nếu không có club_id, lấy từ user
if (!$club_id) {
    $stmt = $conn->prepare("SELECT id FROM clubs WHERE chu_nhiem_id = ? ORDER BY id ASC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $club_id = $row['id'];
    }
    $stmt->close();
}

if (!$club_id) {
    echo '<div class="empty-state">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <h3>Chưa có CLB</h3>
        <p>Bạn chưa có CLB để quản lý</p>
    </div>';
    exit;
}

// Lấy danh sách thành viên chờ duyệt
$stmt = $conn->prepare("
    SELECT cm.id, cm.user_id, cm.phong_ban_id,
           u.ho_ten, u.username, u.email, u.so_dien_thoai,
           pb.ten_phong_ban,
           jr.loi_nhan
    FROM club_members cm
    JOIN users u ON cm.user_id = u.id
    LEFT JOIN phong_ban pb ON cm.phong_ban_id = pb.id
    LEFT JOIN join_requests jr ON jr.club_id = cm.club_id AND jr.user_id = cm.user_id AND jr.trang_thai = 'cho_duyet'
    WHERE cm.club_id = ? AND cm.trang_thai = 'cho_duyet'
    ORDER BY cm.joined_at DESC
");
$stmt->bind_param("i", $club_id);
$stmt->execute();
$result = $stmt->get_result();
$pending_members = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($pending_members)) {
    echo '<div class="empty-state">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        <h3>Không có yêu cầu nào</h3>
        <p>Hiện tại không có thành viên nào đang chờ duyệt</p>
    </div>';
    exit;
}

// Hiển thị danh sách
foreach ($pending_members as $member):
    $initial = strtoupper(mb_substr($member['ho_ten'], 0, 1));
    $memberName = htmlspecialchars($member['ho_ten']);
    $memberId = $member['id'];
?>
<div id="pending-<?= $memberId ?>" class="pending-request-card">
    <div class="pending-item">
        <div class="pending-avatar"><?= $initial ?></div>
        <div class="pending-info">
            <h4><?= $memberName ?></h4>
            <p><?= htmlspecialchars($member['email']) ?></p>
            <?php if ($member['so_dien_thoai']): ?>
                <p style="font-size: 12px; color: #9CA3AF; margin-top: 4px;">
                    📞 <?= htmlspecialchars($member['so_dien_thoai']) ?>
                </p>
            <?php endif; ?>
            <?php if ($member['loi_nhan']): ?>
                <p style="font-size: 12px; color: #6B7280; margin-top: 4px; font-style: italic;">
                    "<?= htmlspecialchars($member['loi_nhan']) ?>"
                </p>
            <?php endif; ?>
        </div>
        <div class="pending-actions">
            <button class="btn-approve-new" onclick="approveRequest(<?= $memberId ?>, '<?= addslashes($memberName) ?>')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Đồng ý</span>
            </button>
            <button class="btn-reject-new" onclick="rejectRequest(<?= $memberId ?>, '<?= addslashes($memberName) ?>')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                <span>Từ chối</span>
            </button>
        </div>
    </div>
</div>
<?php endforeach; ?>

<style>
.pending-request-card {
    margin-bottom: 12px;
    transition: all 0.3s ease;
}

.pending-request-card.removing {
    opacity: 0;
    transform: translateX(-20px);
}

.pending-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.btn-approve-new,
.btn-reject-new {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    font-family: inherit;
}

.btn-approve-new {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn-approve-new:hover:not(:disabled) {
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    transform: translateY(-1px);
}

.btn-reject-new {
    background: #F3F4F6;
    color: #DC2626;
}

.btn-reject-new:hover:not(:disabled) {
    background: #FEE2E2;
    transform: translateY(-1px);
}

.btn-approve-new:disabled,
.btn-reject-new:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

