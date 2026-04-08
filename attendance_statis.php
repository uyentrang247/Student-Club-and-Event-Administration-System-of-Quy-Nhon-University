<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/assets/database/connect.php';

// Kiểm tra đăng nhập
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$club_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if ($club_id <= 0) {
    die("ID câu lạc bộ không hợp lệ");
}

// Lấy thông tin CLB
$club_sql = "SELECT name FROM clubs WHERE id = ?";
$club_stmt = $conn->prepare($club_sql);
$club_stmt->bind_param("i", $club_id);
$club_stmt->execute();
$club_result = $club_stmt->get_result();
$club = $club_result->fetch_assoc();
$club_stmt->close();

if (!$club) {
    die("Không tìm thấy câu lạc bộ");
}

// Lấy tổng số thành viên đang hoạt động
$total_members_sql = "SELECT COUNT(*) as total FROM members WHERE club_id = ? AND status = 'active'";
$total_stmt = $conn->prepare($total_members_sql);
$total_stmt->bind_param("i", $club_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_members = $total_result->fetch_assoc()['total'] ?? 0;
$total_stmt->close();

// Lấy thống kê điểm danh trong ngày
$stats_sql = "SELECT 
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
                COUNT(CASE WHEN status = 'absent_without_permission' THEN 1 END) as absent_count,
                COUNT(CASE WHEN status = 'absent_with_permission' THEN 1 END) as absent_with_permission_count
              FROM attendance 
              WHERE club_id = ? AND attendance_date = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("is", $club_id, $date);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

$present_count = $stats['present_count'] ?? 0;
$absent_count = $stats['absent_count'] ?? 0;
$absent_with_permission_count = $stats['absent_with_permission_count'] ?? 0;
$total_absent = $absent_count + $absent_with_permission_count;

// Lấy danh sách thành viên vắng mặt (không phép)
$absent_list_sql = "SELECT a.user_id, a.status, a.notes,
                           u.full_name, u.student_id, u.email,
                           d.name as department_name,
                           m.role
                    FROM attendance a
                    JOIN users u ON a.user_id = u.id
                    LEFT JOIN members m ON m.user_id = u.id AND m.club_id = a.club_id
                    LEFT JOIN departments d ON m.department_id = d.id
                    WHERE a.club_id = ? 
                      AND a.attendance_date = ?
                      AND a.status = 'absent_without_permission'
                    ORDER BY u.full_name ASC";
$absent_stmt = $conn->prepare($absent_list_sql);
$absent_stmt->bind_param("is", $club_id, $date);
$absent_stmt->execute();
$absent_list = $absent_stmt->get_result();
$absent_stmt->close();

// Lấy danh sách thành viên vắng có phép
$absent_with_permission_sql = "SELECT a.user_id, a.status, a.notes,
                                      u.full_name, u.student_id, u.email,
                                      d.name as department_name,
                                      m.role
                               FROM attendance a
                               JOIN users u ON a.user_id = u.id
                               LEFT JOIN members m ON m.user_id = u.id AND m.club_id = a.club_id
                               LEFT JOIN departments d ON m.department_id = d.id
                               WHERE a.club_id = ? 
                                 AND a.attendance_date = ?
                                 AND a.status = 'absent_with_permission'
                               ORDER BY u.full_name ASC";
$permission_stmt = $conn->prepare($absent_with_permission_sql);
$permission_stmt->bind_param("is", $club_id, $date);
$permission_stmt->execute();
$absent_with_permission_list = $permission_stmt->get_result();
$permission_stmt->close();

// Lấy danh sách phòng ban để hiển thị
$departments_sql = "SELECT id, name FROM departments WHERE club_id = ? ORDER BY name ASC";
$dept_stmt = $conn->prepare($departments_sql);
$dept_stmt->bind_param("i", $club_id);
$dept_stmt->execute();
$departments = $dept_stmt->get_result();
$dept_stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê điểm danh - UniQCLUB</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            padding: 40px 20px;
            min-height: 100vh;
        }

        .dashboard-container {
            width: 100%;
            max-width: 1000px;
        }

        /* Header Card */
        .header-card {
            background: white;
            border-radius: 20px;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .header-icon {
            background: #f0effb;
            color: #7d70cc;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 24px;
        }

        .header-info h1 {
            font-size: 1.4rem;
            color: #333;
        }

        .header-info p {
            color: #777;
            font-size: 0.9rem;
        }

        .club-name {
            font-weight: bold;
            color: #7d70cc;
        }

        .date-picker {
            margin-left: auto;
        }

        .date-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 8px solid transparent;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .stat-label {
            font-size: 0.75rem;
            color: #888;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        /* Border & Colors */
        .border-purple { border-left-color: #7d70cc; }
        .border-green { border-left-color: #4cd137; }
        .border-red { border-left-color: #ff4757; }
        .border-orange { border-left-color: #ffa502; }
        .purple-bg { background: #f0effb; color: #7d70cc; }
        .green-bg { background: #eafaf1; color: #4cd137; }
        .red-bg { background: #fff0f0; color: #ff4757; }
        .orange-bg { background: #fff4e6; color: #ffa502; }
        .text-green { color: #4cd137; }
        .text-red { color: #ff4757; }
        .text-orange { color: #ffa502; }

        /* List Card */
        .list-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            min-height: 400px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .list-title {
            color: #ff4757;
            margin-bottom: 25px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .list-title.orange { color: #ffa502; }

        .absent-table {
            width: 100%;
            border-collapse: collapse;
        }

        .absent-table th {
            text-align: left;
            font-size: 0.75rem;
            color: #bbb;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .absent-table td {
            padding: 15px 0;
            color: #555;
            font-size: 0.9rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }

        .avatar-text {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.8rem;
            color: white;
            background: #7d70cc;
        }

        /* Badges */
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            display: inline-block;
        }

        .badge-purple {
            background: #f5f3ff;
            color: #a78bfa;
        }

        .badge-green {
            background: #ecfdf5;
            color: #10b981;
        }

        .badge-orange {
            background: #fff4e6;
            color: #ffa502;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }

        .role-leader { background: #ffd700; color: #8b6b00; }
        .role-vice_leader { background: #c5e0ff; color: #0056b3; }
        .role-head { background: #d4f0e6; color: #00694e; }
        .role-member { background: #e9ecef; color: #495057; }

        /* Footer */
        .footer-actions {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        button {
            padding: 12px 25px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid white;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .btn-export {
            background: white;
            color: #7d70cc;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            color: #888;
        }

        .note-text {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <div class="dashboard-container">
        <div class="header-card">
            <div class="header-icon">
                <i class="fa-solid fa-chart-simple"></i>
            </div>
            <div class="header-info">
                <h1>Thống kê điểm danh</h1>
                <p>Câu lạc bộ: <span class="club-name"><?= htmlspecialchars($club['name']) ?></span></p>
            </div>
            <div class="date-picker">
                <input type="date" id="date-picker" class="date-input" value="<?= $date ?>" onchange="location.href='?id=<?= $club_id ?>&date='+this.value">
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card border-purple">
                <div class="stat-content">
                    <p class="stat-label">TỔNG SỐ THÀNH VIÊN</p>
                    <h2 class="stat-value"><?= number_format($total_members) ?></h2>
                </div>
                <div class="stat-icon purple-bg"><i class="fa-solid fa-users"></i></div>
            </div>

            <div class="stat-card border-green">
                <div class="stat-content">
                    <p class="stat-label">SỐ LƯỢNG CÓ MẶT</p>
                    <h2 class="stat-value text-green"><?= number_format($present_count) ?></h2>
                </div>
                <div class="stat-icon green-bg"><i class="fa-regular fa-circle-check"></i></div>
            </div>

            <div class="stat-card border-orange">
                <div class="stat-content">
                    <p class="stat-label">VẮNG CÓ PHÉP</p>
                    <h2 class="stat-value text-orange"><?= number_format($absent_with_permission_count) ?></h2>
                </div>
                <div class="stat-icon orange-bg"><i class="fa-solid fa-pen"></i></div>
            </div>

            <div class="stat-card border-red">
                <div class="stat-content">
                    <p class="stat-label">VẮNG KHÔNG PHÉP</p>
                    <h2 class="stat-value text-red"><?= number_format($absent_count) ?></h2>
                </div>
                <div class="stat-icon red-bg"><i class="fa-solid fa-bullseye"></i></div>
            </div>
        </div>

        <!-- Danh sách vắng không phép -->
        <div class="list-card">
            <h3 class="list-title"><i class="fa-solid fa-ban"></i> Danh sách vắng mặt không phép</h3>
            <?php if ($absent_list->num_rows > 0): ?>
                <table class="absent-table">
                    <thead>
                        <tr>
                            <th>THÀNH VIÊN</th>
                            <th>MÃ SINH VIÊN</th>
                            <th>PHÒNG BAN</th>
                            <th>VAI TRÒ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($member = $absent_list->fetch_assoc()): 
                            $full_name = htmlspecialchars($member['full_name'] ?? 'Chưa cập nhật');
                            $first_char = mb_substr($full_name, 0, 1, 'UTF-8');
                            $role_class = 'role-' . ($member['role'] ?? 'member');
                            $role_labels = [
                                'leader' => 'Đội trưởng',
                                'vice_leader' => 'Đội phó',
                                'head' => 'Trưởng ban',
                                'member' => 'Thành viên'
                            ];
                        ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="avatar-text"><?= $first_char ?></div>
                                    <div>
                                        <div><?= $full_name ?></div>
                                        <div class="note-text"><?= htmlspecialchars($member['email'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($member['student_id'] ?? '---') ?></td>
                            <td><?= htmlspecialchars($member['department_name'] ?? '---') ?></td>
                            <td><span class="role-badge <?= $role_class ?>"><?= $role_labels[$member['role'] ?? 'member'] ?? 'Thành viên' ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-check-circle" style="font-size: 48px; color: #4cd137; margin-bottom: 15px;"></i>
                    <p>Không có thành viên vắng mặt không phép trong ngày này</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Danh sách vắng có phép -->
        <div class="list-card">
            <h3 class="list-title orange"><i class="fa-solid fa-pen"></i> Danh sách vắng có phép</h3>
            <?php if ($absent_with_permission_list->num_rows > 0): ?>
                <table class="absent-table">
                    <thead>
                        <tr>
                            <th>THÀNH VIÊN</th>
                            <th>MÃ SINH VIÊN</th>
                            <th>PHÒNG BAN</th>
                            <th>VAI TRÒ</th>
                            <th>GHI CHÚ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($member = $absent_with_permission_list->fetch_assoc()): 
                            $full_name = htmlspecialchars($member['full_name'] ?? 'Chưa cập nhật');
                            $first_char = mb_substr($full_name, 0, 1, 'UTF-8');
                            $role_class = 'role-' . ($member['role'] ?? 'member');
                            $role_labels = [
                                'leader' => 'Đội trưởng',
                                'vice_leader' => 'Đội phó',
                                'head' => 'Trưởng ban',
                                'member' => 'Thành viên'
                            ];
                        ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="avatar-text"><?= $first_char ?></div>
                                    <div>
                                        <div><?= $full_name ?></div>
                                        <div class="note-text"><?= htmlspecialchars($member['email'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($member['student_id'] ?? '---') ?></td>
                            <td><?= htmlspecialchars($member['department_name'] ?? '---') ?></td>
                            <td><span class="role-badge <?= $role_class ?>"><?= $role_labels[$member['role'] ?? 'member'] ?? 'Thành viên' ?></span></td>
                            <td><?= htmlspecialchars($member['notes'] ?? '---') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-check-circle" style="font-size: 48px; color: #4cd137; margin-bottom: 15px;"></i>
                    <p>Không có thành viên vắng có phép trong ngày này</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer-actions">
            <button class="btn-back" onclick="history.back()"><i class="fa-solid fa-arrow-left"></i> Quay lại</button>
            <button class="btn-export" onclick="exportReport()"><i class="fa-solid fa-file-export"></i> Xuất báo cáo</button>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const now = new Date();
            const dateString = "Ngày: " + now.getDate().toString().padStart(2, '0') +
                "/" + (now.getMonth() + 1).toString().padStart(2, '0') +
                "/" + now.getFullYear();
            document.getElementById("current-date").innerText = dateString;
        });

        function exportReport() {
            // Tạo URL để export
            const url = new URL(window.location.href);
            url.searchParams.set('export', '1');
            window.location.href = url.toString();
        }
    </script>
</body>

</html>