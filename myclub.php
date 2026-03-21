<?php
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/constants.php';

// NOW start session
session_start();

// Kiểm tra đăng nhập
require_login();

$page_css = "myclub.css";
require 'site.php';
load_top();
load_header();

// Kết nối database
require_once('assets/database/connect.php');

$user_id = $_SESSION['user_id'];

// Lấy danh sách CLB mà user là đội trưởng HOẶC là thành viên (đang hoạt động) với banner/logo từ media_library
// Sử dụng subquery để lấy phòng ban đầu tiên nếu user là trưởng nhiều phòng ban
$sql = "SELECT DISTINCT
            c.id, 
            c.ten_clb, 
            c.linh_vuc, 
            banner.file_path AS banner_path, 
            logo.file_path   AS logo_path,
            CASE 
                WHEN c.chu_nhiem_id = ? THEN 'doi_truong'
                WHEN cm.vai_tro IN ('doi_truong', 'doi_pho') THEN cm.vai_tro
                WHEN pb.truong_phong_id = ? THEN 'truong_ban'
                ELSE COALESCE(cm.vai_tro, 'thanh_vien')
            END AS user_role,
            pb.id AS phong_ban_id,
            pb.ten_phong_ban
        FROM clubs c
        LEFT JOIN club_pages cp ON c.id = cp.club_id
        LEFT JOIN media_library banner ON cp.banner_id = banner.id
        LEFT JOIN media_library logo   ON cp.logo_id   = logo.id
        LEFT JOIN club_members cm ON c.id = cm.club_id AND cm.user_id = ? AND cm.trang_thai = 'dang_hoat_dong'
        LEFT JOIN (
            SELECT pb1.club_id, pb1.id, pb1.ten_phong_ban, pb1.truong_phong_id
            FROM phong_ban pb1
            WHERE pb1.truong_phong_id = ?
            AND pb1.id = (
                SELECT MIN(pb2.id) 
                FROM phong_ban pb2 
                WHERE pb2.club_id = pb1.club_id 
                AND pb2.truong_phong_id = ?
            )
        ) pb ON pb.club_id = c.id
        WHERE (c.chu_nhiem_id = ? OR (cm.user_id = ? AND cm.trang_thai = 'dang_hoat_dong') OR pb.truong_phong_id = ?)
        ORDER BY c.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Lấy vai trò cao nhất để hiển thị trong stats (ưu tiên: doi_truong > doi_pho > truong_ban > thanh_vien)
$primary_role = 'thanh_vien';
$primary_role_label = 'Thành viên';
$role_priority = ['doi_truong' => 4, 'doi_pho' => 3, 'truong_ban' => 2, 'thanh_vien' => 1];
if ($result && $result->num_rows > 0) {
    $result->data_seek(0);
    while($row = $result->fetch_assoc()) {
        $current_role = $row['user_role'] ?? 'thanh_vien';
        // Nếu là trưởng phòng ban, lấy tên phòng ban
        if ($current_role === 'truong_ban' && !empty($row['ten_phong_ban'])) {
            $current_role_label = 'Trưởng ban ' . $row['ten_phong_ban'];
        } else {
            $current_role_label = UserRole::getLabel($current_role);
        }
        
        if (isset($role_priority[$current_role]) && $role_priority[$current_role] > $role_priority[$primary_role]) {
            $primary_role = $current_role;
            $primary_role_label = $current_role_label;
        }
    }
    $result->data_seek(0); // Reset lại để dùng cho phần hiển thị
}
?>
<div class="myclub-wrapper">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <div class="hero-icon">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <h1 class="hero-title">Câu lạc bộ của tôi</h1>
            <p class="hero-subtitle">Quản lý và phát triển các câu lạc bộ của bạn</p>
        </div>
    </div>

    <div class="container-myclub">
        <?php if ($result && $result->num_rows > 0): ?>
            
            <!-- Stats Overview -->
            <div class="stats-section">
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-info">
                        <h3><?= $result->num_rows ?></h3>
                        <p>Câu lạc bộ</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?= htmlspecialchars($primary_role_label) ?></h3>
                        <p>Vai trò của bạn</p>
                    </div>
                </div>
                <div class="stat-card stat-card-action">
                    <a href="createCLB.php" class="stat-create-btn">
                        <span class="plus-icon">+</span>
                        <span>Tạo CLB mới</span>
                    </a>
                </div>
            </div>

            <!-- Club Grid -->
            <div class="clubs-section">
                <h2 class="section-title">
                    <span class="title-icon">📚</span>
                    Danh sách câu lạc bộ
                </h2>
                <div class="club-grid">
                    <?php 
                    $result->data_seek(0); // Reset pointer
                    while($row = $result->fetch_assoc()): 
                        // Xác định logo và banner (ưu tiên từ media_library)
                        $logo = $row['logo_path'] ?? 'assets/img/default-club.png';
                        $banner = $row['banner_path'] ?? '';
                        $has_banner = !empty($banner) && (file_exists($banner) || file_exists(__DIR__ . '/' . $banner));
                        
                        // Lấy lĩnh vực trực tiếp từ database
                        $linh_vuc = trim((string)($row['linh_vuc'] ?? ''));
                        // Nếu rỗng hoặc "0" thì hiển thị "Chưa phân loại"
                        if (empty($linh_vuc) || $linh_vuc === '0') {
                            $linh_vuc = 'Chưa phân loại';
                        }
                        
                        // Kiểm tra quyền quản lý CLB
                        $user_role = $row['user_role'] ?? 'thanh_vien';
                        $can_manage = UserRole::isAdmin($user_role);
                        
                        // Nếu là trưởng phòng ban, kiểm tra thêm
                        if (!$can_manage && !empty($row['phong_ban_id'])) {
                            $can_manage = true; // Trưởng phòng ban cũng có quyền quản lý
                        }
                        
                        // Lấy label vai trò (bao gồm tên phòng ban nếu là trưởng ban)
                        $role_label = UserRole::getLabel($user_role);
                        if ($user_role === 'truong_ban' && !empty($row['ten_phong_ban'])) {
                            $role_label = 'Trưởng ban ' . $row['ten_phong_ban'];
                        }
                    ?>
                        <div class="club-card">
                            <div class="club-card-header" <?= $has_banner ? 'style="background-image: url(\'' . htmlspecialchars($banner) . '\'); background-size: cover; background-position: center;"' : '' ?>>
                                <img src="<?= htmlspecialchars($logo) ?>" 
                                     alt="<?= htmlspecialchars($row['ten_clb']) ?>" 
                                     class="club-avatar"
                                     onerror="this.src='assets/img/default-club.png'">
                                <span class="club-badge"><?= htmlspecialchars($linh_vuc) ?></span>
                            </div>
                            <div class="club-card-body">
                                <h3 class="club-title"><?= htmlspecialchars($row['ten_clb']) ?></h3>
                                <p class="club-role" style="color: #6366f1; font-size: 0.9em; margin: 0.5em 0;">
                                    <strong>Vai trò:</strong> <?= htmlspecialchars($role_label) ?>
                                </p>
                                <div class="club-actions">
                                    <a href="club-detail.php?id=<?= $row['id'] ?>" class="btn-primary">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        Xem chi tiết
                                    </a>
                                    <?php if ($can_manage): ?>
                                    <a href="Dashboard.php?id=<?= $row['id'] ?>" class="btn-secondary">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="7" height="7"></rect>
                                            <rect x="14" y="3" width="7" height="7"></rect>
                                            <rect x="14" y="14" width="7" height="7"></rect>
                                            <rect x="3" y="14" width="7" height="7"></rect>
                                        </svg>
                                        Quản lý
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

        <?php else: ?>

            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-illustration">
                    <svg width="200" height="200" viewBox="0 0 200 200" fill="none">
                        <circle cx="100" cy="100" r="80" fill="#f0f4ff" opacity="0.5"/>
                        <circle cx="100" cy="100" r="60" fill="#e0e7ff" opacity="0.5"/>
                        <path d="M100 60 L100 100 L130 100" stroke="#6366f1" stroke-width="4" stroke-linecap="round"/>
                        <circle cx="100" cy="100" r="8" fill="#6366f1"/>
                    </svg>
                </div>
                <h2 class="empty-title">Chưa có câu lạc bộ nào</h2>
                <p class="empty-description">
                    Bắt đầu hành trình của bạn bằng cách tạo câu lạc bộ đầu tiên<br>
                    hoặc tham gia vào một cộng đồng sẵn có
                </p>
                <div class="empty-actions">
                    <a href="createCLB.php" class="btn-create-primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Tạo câu lạc bộ mới
                    </a>
                    <a href="DanhsachCLB.php" class="btn-explore">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        Khám phá CLB
                    </a>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>


<?php
load_footer();
?>