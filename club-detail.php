<?php
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// NOW start session
session_start();

// Thêm header để tránh cache
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

require 'site.php';

// Kiểm tra đăng nhập TRƯỚC khi load header
require_login();

require_once('assets/database/connect.php');
$user_id = $_SESSION['user_id'];

// Lấy ID CLB từ URL
$club_id = get_club_id();

// Lấy thông tin CLB với cấu trúc database mới
$sql = "SELECT * FROM clubs WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $club_id);
$stmt->execute();
$result = $stmt->get_result();
$club = $result->fetch_assoc();

// Nếu không tìm thấy, redirect về danh sách
if (!$club) {
    header("Location: DanhsachCLB.php");
    exit();
}

// Lấy thông tin trang đại diện từ pages (bao gồm banner/logo từ media)
$club_page = null;
try {
    // Kiểm tra xem bảng pages có tồn tại không
    $table_check = $conn->query("SHOW TABLES LIKE 'pages'");
    if ($table_check && $table_check->num_rows > 0) {
        $sql = "SELECT 
                    p.*,
                    banner.path AS banner_path,
                    logo.path AS logo_path
                FROM pages p
                LEFT JOIN media banner ON p.banner_id = banner.id
                LEFT JOIN media logo ON p.logo_id = logo.id
                WHERE p.club_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $club_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $club_page = $result->fetch_assoc();
            // Merge thông tin từ pages vào $club
            if (!empty($club_page['banner_path'] ?? null)) {
                $club['banner_url'] = $club_page['banner_path'];
            }
            
            if (!empty($club_page['logo_path'] ?? null)) {
                $club['logo'] = $club_page['logo_path'];
                $club['logo_url'] = $club_page['logo_path'];
            }
            if (!empty($club_page['about'] ?? null)) {
                $club['description'] = $club_page['about'];
            }
            if (!empty($club_page['primary_color'] ?? null)) {
                $club['color'] = $club_page['primary_color'];
            }
        }
    }
} catch (Exception $e) {
    error_log("pages table not found: " . $e->getMessage());
}

// Đảm bảo $club_page luôn là mảng để tránh undefined key
$club_page = $club_page ?: [];

// Lấy thông tin liên hệ từ contacts
try {
    $contact_sql = "SELECT email, phone, website, facebook, instagram, twitter FROM contacts WHERE club_id = ?";
    $contact_stmt = $conn->prepare($contact_sql);
    $contact_stmt->bind_param("i", $club_id);
    $contact_stmt->execute();
    $contact_result = $contact_stmt->get_result();
    if ($contact_result->num_rows > 0) {
        $contact_data = $contact_result->fetch_assoc();
        // Merge thông tin liên hệ vào $club
        if (!empty($contact_data['email'])) {
            $club['email'] = $contact_data['email'];
        }
        if (!empty($contact_data['phone'])) {
            $club['phone'] = $contact_data['phone'];
        }
        if (!empty($contact_data['website'])) {
            $club['website'] = $contact_data['website'];
        }
        if (!empty($contact_data['facebook'])) {
            $club['facebook'] = $contact_data['facebook'];
        }
        if (!empty($contact_data['instagram'])) {
            $club['instagram'] = $contact_data['instagram'];
        }
        if (!empty($contact_data['twitter'])) {
            $club['twitter'] = $contact_data['twitter'];
        }
    }
    $contact_stmt->close();
} catch (Exception $e) {
    error_log("contacts table not found: " . $e->getMessage());
}

// Bây giờ mới load header
$page_css = "club-detail.css";
load_top();
load_header();

// Đếm số thành viên (chỉ đếm thành viên đang hoạt động)
require_once __DIR__ . '/includes/constants.php';
$sql = "SELECT COUNT(*) as total FROM members WHERE club_id = ? AND status = ?";
$stmt = $conn->prepare($sql);
$status_active = MemberStatus::ACTIVE;
$stmt->bind_param("is", $club_id, $status_active);
$stmt->execute();
$member_count = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// THỐNG KÊ SỰ KIỆN - TÍNH TRỰC TIẾP TỪ BẢNG EVENTS
$event_count_sql = "SELECT COUNT(*) as total FROM events WHERE club_id = ?";
$event_stmt = $conn->prepare($event_count_sql);
$event_stmt->bind_param("i", $club_id);
$event_stmt->execute();
$event_count_result = $event_stmt->get_result();
$total_events = 0;
if ($event_count_result && $event_count_result->num_rows > 0) {
    $total_events = (int)$event_count_result->fetch_assoc()['total'];
}
$event_stmt->close();

// Gán vào stats (giữ cấu trúc cũ để code không bị lỗi)
$stats = [
    'total_events' => $total_events,
    'rating' => 0.0
];


// Kiểm tra user có phải leader không
$is_owner = ($club['leader_id'] == $user_id);

// Kiểm tra user đã tham gia chưa (chỉ tính thành viên đang hoạt động)
$sql = "SELECT * FROM members WHERE club_id = ? AND user_id = ? AND status = ?";
$stmt = $conn->prepare($sql);
$status_active = MemberStatus::ACTIVE;
$stmt->bind_param("iis", $club_id, $user_id, $status_active);
$stmt->execute();
$is_member = $stmt->get_result()->num_rows > 0;
$stmt->close();

// Lấy danh sách thành viên (top 12)
$members_result = [];
try {
    $sql = "SELECT u.id, u.full_name, u.avatar, m.role 
            FROM members m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.club_id = ? AND m.status = ?
            LIMIT 12";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $club_id, $status_active);
    $stmt->execute();
    $members = $stmt->get_result();
} catch (Exception $e) {
    $members = null;
}

// ===== SỬA: Lấy 3 sự kiện gần nhất (bao gồm tất cả status) =====
$events = [];
try {
    $sql = "SELECT * FROM events 
            WHERE club_id = ? 
            ORDER BY 
                CASE 
                    WHEN status = 'ongoing' THEN 1
                    WHEN status = 'upcoming' THEN 2
                    WHEN status = 'completed' THEN 3
                    ELSE 4
                END,
                start_time DESC
            LIMIT 3";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $events = $stmt->get_result();
} catch (Exception $e) {
    $events = null;
}

// Đếm số người đăng ký cho mỗi event
$event_participants = [];
if ($events && $events->num_rows > 0) {
    $events->data_seek(0);
    while ($event = $events->fetch_assoc()) {
        $sql = "SELECT COUNT(*) as total FROM event_registrations WHERE event_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event['id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $event_participants[$event['id']] = (int)($result['total'] ?? 0);
    }
    $events->data_seek(0);
}

// Lấy gallery (4 ảnh gần nhất)
$gallery = [];
try {
    $sql = "SELECT g.*, m.path AS image_url 
            FROM gallery g
            LEFT JOIN media m ON g.media_id = m.id
            WHERE g.club_id = ?
            ORDER BY g.uploaded_at DESC
            LIMIT 4";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $gallery = $stmt->get_result();
} catch (Exception $e) {
    $gallery = null;
}

// Lấy hoạt động từ bảng activities
$activities = [];
try {
    // Kiểm tra bảng activities có tồn tại không
    $table_check = $conn->query("SHOW TABLES LIKE 'activities'");
    if ($table_check && $table_check->num_rows > 0) {
        // Lấy từ bảng activities
        $sql = "SELECT type, description, created_at 
                FROM activities 
                WHERE club_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $club_id);
        $stmt->execute();
        $activities = $stmt->get_result();
    } else {
        // Fallback: lấy từ events nếu chưa có bảng activities
        $sql = "SELECT 
                    'event' as type,
                    CONCAT('📅 Tạo sự kiện: ', name) as description,
                    created_at
                FROM events 
                WHERE club_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $club_id);
        $stmt->execute();
        $activities = $stmt->get_result();
    }
} catch (Exception $e) {
    $activities = null;
}
?>

<div class="club-detail-container">
    <!-- Cover Image -->
    <div class="club-cover">
        <?php 
        $banner_display = null;
        
        if (!empty($club_page['banner_path'] ?? null)) {
            $banner_path = trim($club_page['banner_path']);
            if (file_exists($banner_path) || file_exists(__DIR__ . '/' . $banner_path)) {
                $banner_display = $banner_path;
            }
        }
        
        if (!$banner_display && !empty($club['banner_url'] ?? null)) {
            $banner_path = trim($club['banner_url']);
            if (file_exists($banner_path) || file_exists(__DIR__ . '/' . $banner_path)) {
                $banner_display = $banner_path;
            }
        }
        
        if ($banner_display): 
            $banner_url = htmlspecialchars($banner_display);
            $banner_url .= '?v=' . time();
        ?>
            <img src="<?php echo $banner_url; ?>" 
                 alt="Cover" onerror="this.style.display='none'; console.error('Banner not found: <?php echo $banner_url; ?>');">
        <?php else: ?>
            <?php if (isset($_GET['debug'])): ?>
                <div style="padding: 20px; background: #f0f0f0;">
                    <p>Club Page Banner: <?php echo htmlspecialchars($club_page['banner_path'] ?? 'NULL'); ?></p>
                    <p>Club Banner: <?php echo htmlspecialchars($club['banner_url'] ?? 'NULL'); ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <div class="cover-overlay"></div>
    </div>

    <!-- Club Header -->
    <div class="club-header">
        <div class="club-header-content">
            <?php
            $logo_display = null;
            if (!empty($club_page['logo_path'] ?? null) && file_exists($club_page['logo_path'])) {
                $logo_display = $club_page['logo_path'];
            } elseif (!empty($club['logo'] ?? null) && file_exists($club['logo'])) {
                $logo_display = $club['logo'];
            } elseif (!empty($club['logo_url'] ?? null) && file_exists($club['logo_url'])) {
                $logo_display = $club['logo_url'];
            }

            $category_label = trim((string)($club['category'] ?? ''));
            if ($category_label === '' || $category_label === '0') {
                $category_label = 'Volunteer';
            }
            ?>
            <div class="club-badge" style="<?php echo !empty($logo_display) ? 'background: white; padding: 8px;' : 'background: ' . htmlspecialchars($club['color'] ?? '#667eea') . ';'; ?>">
                <?php if (!empty($logo_display)): 
                    $logo_url = htmlspecialchars($logo_display);
                    $logo_url .= '?v=' . time();
                ?>
                    <img src="<?php echo $logo_url; ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;" onerror="this.style.display='none'; this.parentElement.innerHTML='<?php echo htmlspecialchars(strtoupper(substr($club['name'], 0, 3))); ?>';">
                <?php else: ?>
                    <?php echo htmlspecialchars(strtoupper(substr($club['name'], 0, 3))); ?>
                <?php endif; ?>
            </div>
            <div class="club-info">
                <div class="club-category"><?php echo htmlspecialchars($category_label); ?></div>
                <h1><?php echo htmlspecialchars($club['name']); ?></h1>
                <?php if (!empty($club_page['slogan'] ?? null)): ?>
                    <p class="club-slogan" style="font-style: italic; color: #667eea; margin: 8px 0;">
                        "<?php echo htmlspecialchars($club_page['slogan']); ?>"
                    </p>
                <?php endif; ?>
                <div class="club-stats">
                    <span>👥 <?php echo $member_count; ?> thành viên</span>
                    <span>📅 Thành lập <?php echo date('Y', strtotime($club['founded_date'] ?? 'now')); ?></span>
                    <span>📅 <?php echo $total_events; ?> sự kiện</span>
                </div>
            </div>
            <div class="club-actions">
                <?php if ($is_owner): ?>
                    <a href="Dashboard.php?id=<?php echo $club_id; ?>" class="btn-manage">
                        <span>⚙️</span> Quản lý CLB
                    </a>
                <?php elseif ($is_member): ?>
                    <button class="btn-joined" disabled>
                        <span>✓</span> Đã tham gia
                    </button>
                <?php else: ?>
                    <button class="btn-join" onclick="joinClub(<?php echo $club_id; ?>)">
                        <span>+</span> Tham gia
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="club-content">
        <div class="content-main">
            <!-- About Section -->
            <div class="section-card">
                <h2>📖 Giới thiệu</h2>
                <p class="club-description">
                    <?php echo nl2br(htmlspecialchars($club['description'] ?? 'Chưa có mô tả')); ?>
                </p>
            </div>

            <!-- Activities Section -->
            <div class="section-card">
                <h2>🎯 Hoạt động chính</h2>
                <div class="activities-grid">
                    <div class="activity-item">
                        <div class="activity-icon">📚</div>
                        <h3>Học tập</h3>
                        <p>Tổ chức các buổi workshop, seminar</p>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">🎉</div>
                        <h3>Sự kiện</h3>
                        <p>Tham gia và tổ chức các sự kiện</p>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">🤝</div>
                        <h3>Giao lưu</h3>
                        <p>Kết nối và chia sẻ kinh nghiệm</p>
                    </div>
                </div>
            </div>

            <!-- ===== ĐÃ SỬA: Tất cả sự kiện (hiển thị 3 cái gần nhất) ===== -->
            <div class="section-card">
                <div class="section-header">
                    <h2>📅 Tất cả sự kiện</h2>
                    <a href="club-events.php?id=<?php echo $club_id; ?>" class="view-all">Xem tất cả →</a>
                </div>
                <div class="events-list">
                    <?php if ($events && $events->num_rows > 0): ?>
                        <?php while ($event = $events->fetch_assoc()): 
                            $event_date = new DateTime($event['start_time']);
                            $start_time = $event_date->format('H:i');
                            $end_date = new DateTime($event['end_time']);
                            $end_time = $end_date->format('H:i');
                            $participants = $event_participants[$event['id']] ?? 0;
                            
                            // Badge trạng thái
                            $status_badge = '';
                            $btn_text = 'Xem chi tiết';
                            $btn_class = 'btn-event-view';
                            
                            switch($event['status']) {
                                case 'upcoming':
                                    $status_badge = '<span class="status-badge status-upcoming">🟢 Sắp diễn ra</span>';
                                    $btn_text = 'Tham gia';
                                    $btn_class = 'btn-event-join';
                                    break;
                                case 'ongoing':
                                    $status_badge = '<span class="status-badge status-ongoing">🔵 Đang diễn ra</span>';
                                    break;
                                case 'completed':
                                    $status_badge = '<span class="status-badge status-completed">⚫ Đã kết thúc</span>';
                                    break;
                                case 'cancelled':
                                    $status_badge = '<span class="status-badge status-cancelled">🔴 Đã hủy</span>';
                                    break;
                                default:
                                    $status_badge = '<span class="status-badge status-upcoming">🟢 Sắp diễn ra</span>';
                                    $btn_text = 'Tham gia';
                                    $btn_class = 'btn-event-join';
                            }
                        ?>
                        <div class="event-card">
                            <div class="event-date">
                                <div class="date-day"><?php echo $event_date->format('d'); ?></div>
                                <div class="date-month">Th<?php echo $event_date->format('m'); ?></div>
                            </div>
                            <div class="event-info">
                                <h4>
                                    <?php echo htmlspecialchars($event['name']); ?>
                                    <?php echo $status_badge; ?>
                                </h4>
                                <p>🕐 <?php echo $start_time; ?> - <?php echo $end_time; ?> | 📍 <?php echo htmlspecialchars($event['location'] ?? 'Chưa xác định'); ?></p>
                                <div class="event-participants">
                                    <span>👥 <?php echo $participants; ?> / <?php echo $event['max_participants'] ?? '∞'; ?> người</span>
                                </div>
                            </div>
                            <a class="<?php echo $btn_class; ?>" href="chi_tiet_su_kien.php?id=<?php echo $event['id']; ?>">
                                <?php echo $btn_text; ?> →
                            </a>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #718096; padding: 40px;">CLB chưa có sự kiện nào</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gallery -->
            <div class="section-card">
                <div class="section-header">
                    <h2>📸 Thư viện ảnh</h2>
                    <a href="club-gallery.php?id=<?= $club_id ?>&mode=view" class="view-all">Xem tất cả →</a>
                </div>
                <div class="gallery-grid">
                    <?php 
                    $gradients = [
                        'linear-gradient(135deg, #667eea, #764ba2)',
                        'linear-gradient(135deg, #f093fb, #f5576c)',
                        'linear-gradient(135deg, #4facfe, #00f2fe)',
                        'linear-gradient(135deg, #43e97b, #38f9d7)'
                    ];
                    $index = 0;
                    if ($gallery && $gallery->num_rows > 0): 
                        while ($photo = $gallery->fetch_assoc()): 
                    ?>
                        <a href="club-gallery.php?id=<?= $club_id ?>&mode=view" class="gallery-item" style="background: <?php echo $gradients[$index % 4]; ?>; <?php if (!empty($photo['image_url'])): ?>background-image: url('<?php echo htmlspecialchars($photo['image_url']); ?>'); background-size: cover; background-position: center;<?php endif; ?>">
                            <div class="gallery-overlay">
                                <span><?php echo htmlspecialchars($photo['title'] ?? 'Ảnh CLB'); ?></span>
                            </div>
                        </a>
                    <?php 
                        $index++;
                        endwhile; 
                    else: 
                        for ($i = 0; $i < 4; $i++):
                    ?>
                        <a href="club-gallery.php?id=<?= $club_id ?>&mode=view" class="gallery-item" style="background: <?php echo $gradients[$i]; ?>;">
                            <div class="gallery-overlay">
                                <span>Chưa có ảnh</span>
                            </div>
                        </a>
                    <?php 
                        endfor;
                    endif; 
                    ?>
                </div>
            </div>

        </div>

        <!-- Sidebar -->
        <div class="content-sidebar">

            <!-- Contact Card -->
            <div class="sidebar-card">
                <h3>📞 Liên hệ</h3>
                <div class="contact-info">
                    <?php if (!empty($club['email'])): ?>
                        <div class="contact-item">
                            <span class="icon">📧</span>
                            <a href="mailto:<?php echo htmlspecialchars($club['email']); ?>">
                                <?php echo htmlspecialchars($club['email']); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="contact-item">
                            <span class="icon">📧</span>
                            <a href="mailto:club@example.com">club@example.com</a>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($club['phone'])): ?>
                        <div class="contact-item">
                            <span class="icon">📱</span>
                            <a href="tel:<?php echo htmlspecialchars($club['phone']); ?>">
                                <?php echo htmlspecialchars($club['phone']); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="contact-item">
                            <span class="icon">📱</span>
                            <a href="tel:0123456789">0123 456 789</a>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($club['website'])): ?>
                        <div class="contact-item">
                            <span class="icon">🌐</span>
                            <a href="<?php echo htmlspecialchars($club['website']); ?>" target="_blank">
                                Website
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="contact-item">
                            <span class="icon">🌐</span>
                            <a href="#" target="_blank">Website</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Info Card -->
            <div class="sidebar-card">
                <h3>ℹ️ Thông tin</h3>
                <div class="info-list">
                    <div class="info-item">
                        <span class="label">Lĩnh vực:</span>
                        <span class="value"><?php 
                            $category_display = trim((string)($club['category'] ?? ''));
                            if ($category_display === '' || $category_display === '0') {
                                $category_display = 'Volunteer';
                            }
                            echo htmlspecialchars($category_display);
                        ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Thành lập:</span>
                        <span class="value"><?php echo date('d/m/Y', strtotime($club['founded_date'] ?? 'now')); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">Trạng thái:</span>
                        <span class="value status-active">Đang hoạt động</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Sự kiện:</span>
                        <span class="value"><?php echo $total_events; ?></span>
                    </div>
                </div>
            </div>

            <!-- Social Links -->
            <div class="sidebar-card">
                <h3>🔗 Mạng xã hội</h3>
                <div class="social-links">
                    <?php 
                    $facebook = $club['facebook'] ?? null;
                    $instagram = $club['instagram'] ?? null;
                    $twitter = $club['twitter'] ?? null;
                    ?>
                    <?php if (!empty($facebook)): ?>
                        <a href="<?php echo htmlspecialchars($facebook); ?>" target="_blank" class="social-btn facebook">
                            <span>📘</span>
                            <span>Facebook</span>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($instagram)): ?>
                        <a href="<?php echo htmlspecialchars($instagram); ?>" target="_blank" class="social-btn instagram">
                            <span>📷</span>
                            <span>Instagram</span>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($twitter)): ?>
                        <a href="<?php echo htmlspecialchars($twitter); ?>" target="_blank" class="social-btn youtube">
                            <span>🐦</span>
                            <span>Twitter</span>
                        </a>
                    <?php endif; ?>
                    <?php if (empty($facebook) && empty($instagram) && empty($twitter)): ?>
                        <p style="text-align: center; color: #718096; padding: 20px;">Chưa có liên kết mạng xã hội</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Activity Timeline -->
            <div class="sidebar-card">
                <h3>⏰ Hoạt động gần đây</h3>
                <div class="timeline">
                    <?php if ($activities && $activities->num_rows > 0): ?>
                        <?php while ($activity = $activities->fetch_assoc()): 
                            $time_field = $activity['created_at'] ?? null;
                            if (!empty($time_field) && $time_field != '0000-00-00 00:00:00') {
                                $activity_date = new DateTime($time_field);
                                $now = new DateTime();
                                $diff = $now->diff($activity_date);
                                
                                if ($diff->d == 0 && $diff->h == 0) {
                                    $time_ago = $diff->i . ' phút trước';
                                } elseif ($diff->d == 0) {
                                    $time_ago = $diff->h . ' giờ trước';
                                } elseif ($diff->d == 1) {
                                    $time_ago = '1 ngày trước';
                                } else {
                                    $time_ago = $diff->d . ' ngày trước';
                                }
                            } else {
                                $time_ago = 'Vừa xong';
                            }
                            
                            $icon = '📌';
                            switch($activity['type']) {
                                case 'event':
                                case 'event_created':
                                    $icon = '📅';
                                    break;
                                case 'new_member':
                                case 'member_joined':
                                    $icon = '👤';
                                    break;
                                case 'achievement':
                                    $icon = '🏆';
                                    break;
                            }
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <p><?php echo $icon; ?> <strong><?php echo htmlspecialchars($activity['description']); ?></strong></p>
                                <span class="time-ago"><?php echo $time_ago; ?></span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #718096; padding: 20px;">Chưa có hoạt động nào</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Container for Join Club -->
<div id="modalContainer"></div>

<!-- Load Join Request JS -->
<script src="assets/js/join_request.js"></script>
<script src="assets/js/popup_join_validation.js"></script>


<script>
function joinClub(clubId) {
    
if (typeof openJoinModal === 'function') {
        openJoinModal(clubId);
    } else {
       
    alert('Đang tải form đăng ký...');
        window.location.href = 'popup_join.php?club_id=' + clubId;
    }
}

function joinEvent(eventId) {
    
if (typeof openEventModal === 'function') {
        openEventModal(eventId);
    } else {
        
    fetch('popup_joinevent.php?event_id=' + eventId)
            .then(response => response.text())
            .then(html => {
                
            let container = document.getElementById('eventModalContainer');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'eventModalContainer';
                    document.body.appendChild(container);
                }
                container.innerHTML = html;
                const modal = document.getElementById('joinEventModal');
                if (modal) {
                    modal.style.display = 'flex';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi mở form đăng ký sự kiện');
            });
    }
}


document.addEventListener('DOMContentLoaded', function() {
    
if (!document.getElementById('modalContainer')) {
        const container = document.createElement('div');
        container.id = 'modalContainer';
        document.body.appendChild(container);
    }
    
    
    if (!document.getElementById('eventModalContainer')) {
        const container = document.createElement('div');
        container.id = 'eventModalContainer';
        document.body.appendChild(container);
    }
});
</script>

<?php
load_footer();
?>

