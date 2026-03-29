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

// Lấy danh sách CLB mà user là leader HOẶC là thành viên (đang hoạt động) với banner/logo từ media
$sql = "SELECT DISTINCT
            c.id, 
            c.name, 
            c.category, 
            banner.path AS banner_path, 
            logo.path   AS logo_path,
            CASE 
                WHEN c.leader_id = ? THEN 'leader'
                WHEN m.role IN ('leader', 'vice_leader') THEN m.role
                WHEN d.head_id = ? THEN 'head'
                ELSE COALESCE(m.role, 'member')
            END AS user_role,
            d.id AS department_id,
            d.name AS department_name
        FROM clubs c
        LEFT JOIN pages p ON c.id = p.club_id
        LEFT JOIN media banner ON p.banner_id = banner.id
        LEFT JOIN media logo   ON p.logo_id   = logo.id
        LEFT JOIN members m ON c.id = m.club_id AND m.user_id = ? AND m.status = 'active'
        LEFT JOIN (
            SELECT d1.club_id, d1.id, d1.name, d1.head_id
            FROM departments d1
            WHERE d1.head_id = ?
            AND d1.id = (
                SELECT MIN(d2.id) 
                FROM departments d2 
                WHERE d2.club_id = d1.club_id 
                AND d2.head_id = ?
            )
        ) d ON d.club_id = c.id
        WHERE (c.leader_id = ? OR (m.user_id = ? AND m.status = 'active') OR d.head_id = ?)
        ORDER BY c.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Lấy vai trò cao nhất để hiển thị trong stats
$primary_role = 'member';
$primary_role_label = 'Thành viên';
$role_priority = ['leader' => 4, 'vice_leader' => 3, 'head' => 2, 'member' => 1];
if ($result && $result->num_rows > 0) {
    $result->data_seek(0);
    while($row = $result->fetch_assoc()) {
        $current_role = $row['user_role'] ?? 'member';
        // Nếu là head, lấy tên phòng ban
        if ($current_role === 'head' && !empty($row['department_name'])) {
            $current_role_label = 'Trưởng ban ' . $row['department_name'];
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
                        // Xác định logo và banner
                        $logo = $row['logo_path'] ?? 'assets/img/default-club.png';
                        $banner = $row['banner_path'] ?? '';
                        $has_banner = !empty($banner) && (file_exists($banner) || file_exists(__DIR__ . '/' . $banner));
                        
                        // Lấy lĩnh vực trực tiếp từ database
                        $category = trim((string)($row['category'] ?? ''));
                        if (empty($category) || $category === '0') {
                            $category = 'Chưa phân loại';
                        }
                        
                        // Kiểm tra quyền quản lý CLB
                        $user_role = $row['user_role'] ?? 'member';
                        $can_manage = UserRole::isAdmin($user_role);
                        
                        // Nếu là head, kiểm tra thêm
                        if (!$can_manage && !empty($row['department_id'])) {
                            $can_manage = true; // Head cũng có quyền quản lý
                        }
                        
                        // Lấy label vai trò (bao gồm tên phòng ban nếu là head)
                        $role_label = UserRole::getLabel($user_role);
                        if ($user_role === 'head' && !empty($row['department_name'])) {
                            $role_label = 'Trưởng ban ' . $row['department_name'];
                        }
                    ?>
                        <div class="club-card">
                            <div class="club-card-header" <?= $has_banner ? 'style="background-image: url(\'' . htmlspecialchars($banner) . '\'); background-size: cover; background-position: center;"' : '' ?>>
                                <img src="<?= htmlspecialchars($logo) ?>" 
                                     alt="<?= htmlspecialchars($row['name']) ?>" 
                                     class="club-avatar"
                                     onerror="this.src='assets/img/default-club.png'">
                                <span class="club-badge"><?= htmlspecialchars($category) ?></span>
                            </div>
                            <div class="club-card-body">
                                <h3 class="club-title"><?= htmlspecialchars($row['name']) ?></h3>
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