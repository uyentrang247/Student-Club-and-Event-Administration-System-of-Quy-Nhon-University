<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_css = "my-clubs.css";
require 'site.php';
load_top();
load_header();

// Kết nối database
require_once('assets/database/connect.php');

$user_id = $_SESSION['user_id'];

// Lấy CLB mà user đã tạo (là leader)
$stmt_created = $conn->prepare("SELECT id, name, category, description FROM clubs WHERE leader_id = ?");
$stmt_created->bind_param("i", $user_id);
$stmt_created->execute();
$created_clubs = $stmt_created->get_result();
$created_count = $created_clubs->num_rows;

// Lấy tất cả CLB mà user tham gia (bao gồm cả CLB đã tạo)
// Logic: User tạo CLB = tự động là thành viên
$stmt_joined = $conn->prepare("SELECT id, name, category, description, leader_id FROM clubs WHERE leader_id = ?");
$stmt_joined->bind_param("i", $user_id);
$stmt_joined->execute();
$joined_clubs = $stmt_joined->get_result();
$joined_count = $joined_clubs->num_rows;

// CLB đang chờ duyệt (tạm thời = 0, có thể mở rộng sau)
$pending_count = 0;

$active_tab = $_GET['tab'] ?? 'joined'; // Mặc định hiển thị tab "Đã tham gia"
?>

<div class="my-clubs-container">
    <div class="page-header">
        <h1>CLB của tôi</h1>
        <p>Quản lý các Câu Lạc Bộ bạn đã tham gia</p>
    </div>

    <div class="clubs-tabs">
        <button class="tab-btn <?= $active_tab === 'joined' ? 'active' : '' ?>" onclick="location.href='?tab=joined'">
            Đã tham gia (<?= $joined_count ?>)
        </button>
        <button class="tab-btn <?= $active_tab === 'pending' ? 'active' : '' ?>" onclick="location.href='?tab=pending'">
            Đang chờ duyệt (<?= $pending_count ?>)
        </button>
        <button class="tab-btn <?= $active_tab === 'created' ? 'active' : '' ?>" onclick="location.href='?tab=created'">
            Đã tạo (<?= $created_count ?>)
        </button>
    </div>

    <?php if ($active_tab === 'joined'): ?>
        <?php if ($joined_count > 0): ?>
            <div class="clubs-grid">
                <?php 
                $joined_clubs->data_seek(0); // Reset pointer
                while($club = $joined_clubs->fetch_assoc()): 
                    $is_owner = ($club['leader_id'] == $user_id);
                ?>
                    <div class="club-card">
                        <div class="club-card-header">
                            <?php
                            // Lấy logo từ pages và media
                            $logo_sql = "SELECT m.path FROM pages p LEFT JOIN media m ON p.logo_id = m.id WHERE p.club_id = ?";
                            $logo_stmt = $conn->prepare($logo_sql);
                            $logo_stmt->bind_param("i", $club['id']);
                            $logo_stmt->execute();
                            $logo_result = $logo_stmt->get_result();
                            $logo_url = '';
                            if ($logo_result->num_rows > 0) {
                                $logo_data = $logo_result->fetch_assoc();
                                $logo_url = $logo_data['path'] ?? '';
                            }
                            $logo_stmt->close();
                            
                            if (empty($logo_url)) {
                                $logo_url = 'assets/img/default-club.png';
                            }
                            ?>
                            <img src="<?= htmlspecialchars($logo_url) ?>" 
                                 alt="<?= htmlspecialchars($club['name']) ?>" 
                                 class="club-logo"
                                 onerror="this.src='assets/img/default-club.png'">
                            <span class="club-badge"><?= htmlspecialchars($club['category']) ?></span>
                            <?php if ($is_owner): ?>
                                <span class="owner-badge">👑 Đội trưởng</span>
                            <?php endif; ?>
                        </div>
                        <div class="club-card-body">
                            <h3 class="club-name"><?= htmlspecialchars($club['name']) ?></h3>
                            <p class="club-desc"><?= htmlspecialchars(substr($club['description'] ?? 'Chưa có mô tả', 0, 80)) ?><?= strlen($club['description'] ?? '') > 80 ? '...' : '' ?></p>
                            <div class="club-actions">
                                <a href="club-detail.php?id=<?= $club['id'] ?>" class="btn-view">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    Xem chi tiết
                                </a>
                                <?php if ($is_owner): ?>
                                    <a href="Dashboard.php?id=<?= $club['id'] ?>" class="btn-manage">
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
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🏆</div>
                <h2>Chưa tham gia CLB nào</h2>
                <p>Hãy khám phá và tham gia các Câu Lạc Bộ phù hợp với bạn!</p>
                <button class="btn-explore" onclick="location.href='DanhsachCLB.php'">
                    Khám phá CLB
                </button>
            </div>
        <?php endif; ?>
    
    <?php elseif ($active_tab === 'created'): ?>
        <?php if ($created_count > 0): ?>
            <div class="clubs-grid">
                <?php 
                $created_clubs->data_seek(0); // Reset pointer
                while($club = $created_clubs->fetch_assoc()): 
                    // Lấy logo từ pages và media
                    $logo_sql = "SELECT m.path FROM pages p LEFT JOIN media m ON p.logo_id = m.id WHERE p.club_id = ?";
                    $logo_stmt = $conn->prepare($logo_sql);
                    $logo_stmt->bind_param("i", $club['id']);
                    $logo_stmt->execute();
                    $logo_result = $logo_stmt->get_result();
                    $logo_url = '';
                    if ($logo_result->num_rows > 0) {
                        $logo_data = $logo_result->fetch_assoc();
                        $logo_url = $logo_data['path'] ?? '';
                    }
                    $logo_stmt->close();
                    
                    if (empty($logo_url)) {
                        $logo_url = 'assets/img/default-club.png';
                    }
                ?>
                    <div class="club-card">
                        <div class="club-card-header">
                            <img src="<?= htmlspecialchars($logo_url) ?>" 
                                 alt="<?= htmlspecialchars($club['name']) ?>" 
                                 class="club-logo"
                                 onerror="this.src='assets/img/default-club.png'">
                            <span class="club-badge"><?= htmlspecialchars($club['category']) ?></span>
                        </div>
                        <div class="club-card-body">
                            <h3 class="club-name"><?= htmlspecialchars($club['name']) ?></h3>
                            <p class="club-desc"><?= htmlspecialchars(substr($club['description'] ?? 'Chưa có mô tả', 0, 80)) ?><?= strlen($club['description'] ?? '') > 80 ? '...' : '' ?></p>
                            <div class="club-actions">
                                <a href="club-detail.php?id=<?= $club['id'] ?>" class="btn-view">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    Xem trang
                                </a>
                                <a href="Dashboard.php?id=<?= $club['id'] ?>" class="btn-manage">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="7" height="7"></rect>
                                        <rect x="14" y="3" width="7" height="7"></rect>
                                        <rect x="14" y="14" width="7" height="7"></rect>
                                        <rect x="3" y="14" width="7" height="7"></rect>
                                    </svg>
                                    Quản lý
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🏆</div>
                <h2>Bạn chưa tạo CLB nào</h2>
                <p>Hãy tạo câu lạc bộ đầu tiên của bạn!</p>
                <button class="btn-explore" onclick="location.href='createCLB.php'">
                    Tạo CLB mới
                </button>
            </div>
        <?php endif; ?>
    
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">⏳</div>
            <h2>Không có CLB đang chờ duyệt</h2>
            <p>Các yêu cầu tham gia CLB của bạn sẽ hiển thị ở đây</p>
        </div>
    <?php endif; ?>
</div>

<?php
load_footer();
?>
