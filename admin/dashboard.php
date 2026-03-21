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

// Lấy thống kê
$stats = [];

// Tổng số users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['users'] = $result->fetch_assoc()['total'];

// Tổng số clubs
$result = $conn->query("SELECT COUNT(*) as total FROM clubs");
$stats['clubs'] = $result->fetch_assoc()['total'];

// Tổng số events
$result = $conn->query("SELECT COUNT(*) as total FROM events");
$stats['events'] = $result->fetch_assoc()['total'];

// Tổng số members
$result = $conn->query("SELECT COUNT(*) as total FROM club_members WHERE trang_thai = 'dang_hoat_dong'");
$stats['members'] = $result->fetch_assoc()['total'];

// Tổng số notifications chưa đọc
$result = $conn->query("SELECT COUNT(*) as total FROM notifications WHERE is_read = 0");
$stats['unread_notifications'] = $result->fetch_assoc()['total'];

// Tổng số liên hệ mới
$result = $conn->query("SELECT COUNT(*) as total FROM lienhe WHERE status = 'new'");
$stats['new_contacts'] = $result->fetch_assoc()['total'];

// Users mới nhất
$recent_users = $conn->query("SELECT id, ho_ten, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Clubs mới nhất
$recent_clubs = $conn->query("SELECT c.id, c.ten_clb, c.linh_vuc, u.ho_ten as doi_truong, c.created_at 
                               FROM clubs c 
                               LEFT JOIN users u ON c.chu_nhiem_id = u.id 
                               ORDER BY c.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Events sắp diễn ra
$upcoming_events = $conn->query("SELECT e.id, e.ten_su_kien, c.ten_clb, e.thoi_gian_bat_dau 
                                 FROM events e 
                                 JOIN clubs c ON e.club_id = c.id 
                                 WHERE e.trang_thai = 'sap_dien_ra' 
                                 ORDER BY e.thoi_gian_bat_dau ASC 
                                 LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin/admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-main">
        <?php include 'includes/header.php'; ?>
        
        <div class="admin-content">
            <div class="page-header">
                <h1>Dashboard</h1>
                <p>Chào mừng trở lại, <?= htmlspecialchars($_SESSION['admin_name']) ?>!</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($stats['users']) ?></h3>
                        <p>Người dùng</p>
                    </div>
                    <a href="users.php" class="stat-link">Xem tất cả →</a>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($stats['clubs']) ?></h3>
                        <p>Câu lạc bộ</p>
                    </div>
                    <a href="clubs.php" class="stat-link">Xem tất cả →</a>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($stats['events']) ?></h3>
                        <p>Sự kiện</p>
                    </div>
                    <a href="events.php" class="stat-link">Xem tất cả →</a>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($stats['members']) ?></h3>
                        <p>Thành viên</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($stats['unread_notifications']) ?></h3>
                        <p>Thông báo chưa đọc</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <h3><?= number_format($stats['new_contacts']) ?></h3>
                        <p>Liên hệ mới</p>
                    </div>
                    <a href="contacts.php" class="stat-link">Xem tất cả →</a>
                </div>
            </div>
            
            <!-- Recent Data -->
            <div class="recent-grid">
                <div class="recent-card">
                    <div class="card-header">
                        <h3>Người dùng mới nhất</h3>
                        <a href="users.php">Xem tất cả</a>
                    </div>
                    <div class="card-content">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tên</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Ngày tạo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['ho_ten'] ?? 'Chưa cập nhật') ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email'] ?? 'Chưa có') ?></td>
                                    <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="recent-card">
                    <div class="card-header">
                        <h3>Câu lạc bộ mới nhất</h3>
                        <a href="clubs.php">Xem tất cả</a>
                    </div>
                    <div class="card-content">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tên CLB</th>
                                    <th>Lĩnh vực</th>
                                    <th>Đội trưởng</th>
                                    <th>Ngày tạo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_clubs as $club): ?>
                                <tr>
                                    <td><?= htmlspecialchars($club['ten_clb']) ?></td>
                                    <td><?= htmlspecialchars($club['linh_vuc']) ?></td>
                                    <td><?= htmlspecialchars($club['doi_truong'] ?? 'Chưa có') ?></td>
                                    <td><?= date('d/m/Y', strtotime($club['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="recent-card">
                    <div class="card-header">
                        <h3>Sự kiện sắp diễn ra</h3>
                        <a href="events.php">Xem tất cả</a>
                    </div>
                    <div class="card-content">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tên sự kiện</th>
                                    <th>CLB</th>
                                    <th>Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_events as $event): ?>
                                <tr>
                                    <td><?= htmlspecialchars($event['ten_su_kien']) ?></td>
                                    <td><?= htmlspecialchars($event['ten_clb']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($event['thoi_gian_bat_dau'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>

