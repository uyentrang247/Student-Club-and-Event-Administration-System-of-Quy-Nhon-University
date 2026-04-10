<?php
session_start();
require 'site.php';
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require('assets/database/connect.php');
$user_id = $_SESSION['user_id'];
$club_id = $_GET['id'] ?? 0;

// Lấy thông tin CLB
$sql = "SELECT * FROM clubs WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $club_id);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc();

if (!$club) {
    header("Location: DanhsachCLB.php");
    exit();
}

// Lấy tất cả sự kiện của CLB
$sql = "SELECT e.*, cover.path AS cover_path
        FROM events e
        LEFT JOIN media cover ON e.cover_id = cover.id
        WHERE e.club_id = ?
        ORDER BY e.start_time DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $club_id);
$stmt->execute();
$events = $stmt->get_result();

// Đếm số người đăng ký cho mỗi event
$event_participants = [];
if ($events && $events->num_rows > 0) {
    $events->data_seek(0);
    while ($event = $events->fetch_assoc()) {
        $sql = "SELECT COUNT(*) as total FROM event_registrations WHERE event_id = ?";
        $count_stmt = $conn->prepare($sql);
        $count_stmt->bind_param("i", $event['id']);
        $count_stmt->execute();
        $result = $count_stmt->get_result()->fetch_assoc();
        $event_participants[$event['id']] = (int)($result['total'] ?? 0);
        $count_stmt->close();
    }
    $events->data_seek(0);
}

$page_css = "club-events.css";
load_top();
load_header();
?>

<div class="events-container">
    <!-- Thông báo -->
    <?php if (isset($_SESSION['flash_message'])): 
        $flash_type = $_SESSION['flash_type'] ?? 'info';
        $class = $flash_type === 'success' ? 'alert-success' : ($flash_type === 'error' || $flash_type === 'danger' ? 'alert-error' : 'alert-info');
    ?>
        <div class="alert <?= $class ?>">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="events-header">
        <div class="header-left">
            <a href="club-detail.php?id=<?= $club_id ?>" class="back-btn">← Quay lại</a>
            <h1>📅 Tất cả sự kiện</h1>
            <p class="club-name"><?= htmlspecialchars($club['name']) ?></p>
        </div>
        <?php if (can_manage_club($conn, $user_id, $club_id)): ?>
        <a href="add_Su_kien.php?id=<?= $club_id ?>" class="btn-add">
            <span>+</span> Thêm sự kiện
        </a>
        <?php endif; ?>
    </div>

    <?php if ($events->num_rows > 0): ?>
    <div class="events-grid">
        <?php while ($event = $events->fetch_assoc()): 
            $event_date = new DateTime($event['start_time']);
            $end_date = new DateTime($event['end_time']);
            
            // Badge trạng thái
            $status_badge = '';
            $status_class = '';
            switch($event['status']) {
                case 'upcoming':
                    $status_badge = '<span class="status-badge status-upcoming">🟢 Sắp diễn ra</span>';
                    $status_class = 'upcoming';
                    break;
                case 'ongoing':
                    $status_badge = '<span class="status-badge status-ongoing">🔵 Đang diễn ra</span>';
                    $status_class = 'ongoing';
                    break;
                case 'completed':
                    $status_badge = '<span class="status-badge status-completed">⚫ Đã kết thúc</span>';
                    $status_class = 'completed';
                    break;
                case 'cancelled':
                    $status_badge = '<span class="status-badge status-cancelled">🔴 Đã hủy</span>';
                    $status_class = 'cancelled';
                    break;
                default:
                    $status_badge = '<span class="status-badge status-upcoming">🟢 Sắp diễn ra</span>';
                    $status_class = 'upcoming';
            }
            
            // Ảnh bìa
            $event_image = 'https://via.placeholder.com/400x250?text=Event+Image';
            if (!empty($event['cover_path']) && file_exists($event['cover_path'])) {
                $event_image = htmlspecialchars($event['cover_path']);
            }
            
            $participants = $event_participants[$event['id']] ?? 0;
        ?>
        <div class="event-card <?= $status_class ?>" onclick="window.location.href='chi_tiet_su_kien.php?id=<?= $event['id'] ?>'">
            <div class="event-image">
                <img src="<?= $event_image ?>" alt="<?= htmlspecialchars($event['name']) ?>" 
                     onerror="this.src='https://via.placeholder.com/400x250?text=Event+Image'">
                <div class="event-date-badge">
                    <div class="date-day"><?= $event_date->format('d') ?></div>
                    <div class="date-month">Th<?= $event_date->format('m') ?></div>
                </div>
                <?= $status_badge ?>
            </div>
            <div class="event-info">
                <h3><?= htmlspecialchars($event['name']) ?></h3>
                <div class="event-meta">
                    <span class="meta-time">🕐 <?= $event_date->format('H:i') ?> - <?= $end_date->format('H:i') ?></span>
                    <span class="meta-location">📍 <?= htmlspecialchars($event['location'] ?? 'Chưa xác định') ?></span>
                </div>
                <p class="event-desc"><?= htmlspecialchars(mb_substr($event['short_desc'] ?? $event['full_desc'] ?? '', 0, 100)) ?>...</p>
                <div class="event-footer">
                    <div class="event-participants">
                        <span>👥 <?= $participants ?> / <?= $event['max_participants'] ?? '∞' ?> người</span>
                    </div>
                    <span class="view-detail">Xem chi tiết →</span>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="empty-events">
        <div class="empty-icon">📭</div>
        <h2>Chưa có sự kiện nào</h2>
        <p>CLB chưa tổ chức sự kiện nào</p>
        <?php if (can_manage_club($conn, $user_id, $club_id)): ?>
        <a href="add_Su_kien.php?id=<?= $club_id ?>" class="btn-add-primary">
            + Thêm sự kiện đầu tiên
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php
load_footer();
?>