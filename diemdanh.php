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

// Lấy ngày điểm danh từ GET hoặc mặc định là hôm nay
$attendance_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Xử lý lưu điểm danh
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $attendance_date = $_POST['attendance_date'];
    
    // Xóa dữ liệu điểm danh cũ trong ngày (nếu có)
    $delete_sql = "DELETE FROM attendance WHERE club_id = ? AND attendance_date = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("is", $club_id, $attendance_date);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // Lưu điểm danh mới
    $status_map = [
        'present' => 'present',
        'absent_with_permission' => 'absent_with_permission',
        'absent_without_permission' => 'absent_without_permission'
    ];
    
    $insert_sql = "INSERT INTO attendance (club_id, user_id, attendance_date, status) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    
    foreach ($_POST['status'] as $user_id => $status) {
        $status_value = $status_map[$status] ?? 'absent_without_permission';
        $insert_stmt->bind_param("iiss", $club_id, $user_id, $attendance_date, $status_value);
        $insert_stmt->execute();
    }
    $insert_stmt->close();
    
    $success_message = "Đã lưu điểm danh thành công!";
}

// Tìm kiếm và filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$department_filter = isset($_GET['department']) ? (int)$_GET['department'] : 0;

// Lấy danh sách phòng ban để hiển thị trong dropdown
$departments_sql = "SELECT id, name FROM departments WHERE club_id = ? ORDER BY created_at ASC, id ASC";
$dept_stmt = $conn->prepare($departments_sql);
$dept_stmt->bind_param("i", $club_id);
$dept_stmt->execute();
$departments_result = $dept_stmt->get_result();
$departments_list = [];
while ($row = $departments_result->fetch_assoc()) {
    $departments_list[] = $row;
}
$dept_stmt->close();

// Lấy điểm danh hiện tại (nếu có)
$attendance_data = [];
$att_sql = "SELECT user_id, status FROM attendance WHERE club_id = ? AND attendance_date = ?";
$att_stmt = $conn->prepare($att_sql);
$att_stmt->bind_param("is", $club_id, $attendance_date);
$att_stmt->execute();
$att_result = $att_stmt->get_result();
while ($row = $att_result->fetch_assoc()) {
    $attendance_data[$row['user_id']] = $row['status'];
}
$att_stmt->close();

// Xây dựng WHERE clause
$where_conditions = ["m.club_id = ?", "m.status = 'active'"];
$params = [$club_id];
$types = 'i';

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.student_id LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($role_filter) && in_array($role_filter, ['leader', 'vice_leader', 'head', 'member'])) {
    $where_conditions[] = "m.role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if (!empty($department_filter) && $department_filter > 0) {
    $where_conditions[] = "m.department_id = ?";
    $params[] = $department_filter;
    $types .= 'i';
}

$where_clause = implode(' AND ', $where_conditions);

// Lấy danh sách thành viên
$members_sql = "SELECT m.id, m.user_id, m.role, m.department_id,
                       u.full_name, u.student_id, u.email,
                       d.name as department_name
                FROM members m
                JOIN users u ON m.user_id = u.id
                LEFT JOIN departments d ON m.department_id = d.id
                WHERE $where_clause
                ORDER BY 
                    CASE m.role
                        WHEN 'leader' THEN 1
                        WHEN 'vice_leader' THEN 2
                        WHEN 'head' THEN 3
                        ELSE 4
                    END,
                    u.full_name ASC";

$members_stmt = $conn->prepare($members_sql);
if (!empty($params)) {
    $members_stmt->bind_param($types, ...$params);
} else {
    $members_stmt->bind_param("i", $club_id);
}
$members_stmt->execute();
$members_result = $members_stmt->get_result();
$members = [];
while ($row = $members_result->fetch_assoc()) {
    $members[] = $row;
}
$members_stmt->close();

// Vai trò hiển thị
$role_labels = [
    'leader' => 'Đội trưởng',
    'vice_leader' => 'Đội phó',
    'head' => 'Trưởng ban',
    'member' => 'Thành viên'
];

// Trạng thái điểm danh
$status_options = [
    'present' => 'Có mặt',
    'absent_with_permission' => 'Vắng có phép',
    'absent_without_permission' => 'Vắng không phép'
];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Điểm danh thành viên - LeaderClub</title>
    <style>
        :root {
            --primary-purple: #6b5bb3;
            --bg-purple: #7469b6;
            --light-purple: #f0eeff;
            --text-gray: #666;
            --white: #ffffff;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-purple);
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* --- HEADER SECTION --- */
        .header {
            background: var(--white);
            padding: 25px 30px;
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .header-left h2 {
            margin: 0 0 8px 0;
            font-size: 24px;
            color: #2d2d2d;
        }

        .header-left p {
            margin: 0 0 15px 0;
            font-size: 15px;
            color: var(--text-gray);
        }

        .header-left p span {
            color: var(--primary-purple);
            font-weight: bold;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-top: 5px;
        }

        .info-row p {
            margin: 0 !important;
        }

        .date-picker-group {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--light-purple);
            padding: 5px 15px;
            border-radius: 8px;
            border: 1px solid #e0def3;
        }

        .date-picker-group label {
            font-size: 14px;
            font-weight: 600;
            color: #555;
        }

        .date-input {
            border: none;
            background: transparent;
            color: var(--primary-purple);
            font-weight: bold;
            font-size: 15px;
            outline: none;
            cursor: pointer;
            font-family: inherit;
        }

        .btn-history {
            background: var(--white);
            color: var(--primary-purple);
            border: 2px solid var(--primary-purple);
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-history:hover {
            background: var(--primary-purple);
            color: var(--white);
            box-shadow: 0 4px 12px rgba(107, 91, 179, 0.3);
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        /* --- FILTER BAR --- */
        .filter-bar {
            background: var(--white);
            padding: 15px;
            border-radius: 12px;
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            border: 1px solid #ddd;
            padding: 12px 15px;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
            min-width: 200px;
        }

        .search-input:focus {
            border-color: var(--primary-purple);
        }

        .dept-select, .role-select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            background: #fff;
            min-width: 150px;
        }

        .btn-search {
            background: var(--primary-purple);
            color: var(--white);
            border: none;
            padding: 0 25px;
            border-radius: 8px;
            height: 42px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-reset {
            background: #f0f0f0;
            color: #666;
            border: none;
            padding: 0 20px;
            border-radius: 8px;
            height: 42px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        /* --- TABLE SECTION --- */
        .table-container {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
            min-height: 300px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: center;
            font-size: 12px;
            color: #888;
            padding: 15px 10px;
            border-bottom: 2px solid #f4f4f4;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        th:first-child {
            text-align: left;
        }

        td {
            padding: 18px 10px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }

        td:first-child {
            text-align: left;
            font-weight: 500;
        }

        .member-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .member-avatar {
            width: 36px;
            height: 36px;
            background: var(--light-purple);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--primary-purple);
        }

        .member-name {
            font-weight: 600;
        }

        .member-email {
            font-size: 12px;
            color: #888;
        }

        /* Custom Radio Buttons */
        .radio-group {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .radio-option {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            font-size: 13px;
        }

        input[type="radio"] {
            appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid #ddd;
            border-radius: 50%;
            outline: none;
            cursor: pointer;
            position: relative;
            transition: 0.2s;
        }

        input[type="radio"]:checked {
            border-color: var(--primary-purple);
        }

        input[type="radio"]:checked::before {
            content: "";
            width: 10px;
            height: 10px;
            background: var(--primary-purple);
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .role-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .role-leader { background: #ffd700; color: #8b6b00; }
        .role-vice_leader { background: #c5e0ff; color: #0056b3; }
        .role-head { background: #d4f0e6; color: #00694e; }
        .role-member { background: #e9ecef; color: #495057; }

        /* --- FOOTER ACTIONS --- */
        .footer-actions {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            padding-bottom: 20px;
        }

        .btn-cancel {
            background: transparent;
            border: 1px solid var(--white);
            color: var(--white);
            padding: 12px 35px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            text-decoration: none;
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-save {
            background: var(--white);
            color: var(--primary-purple);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            color: #888;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="alert-success">
                ✓ <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <header class="header">
            <div class="header-left">
                <h2>Điểm danh thành viên</h2>
                <div class="info-row">
                    <p>Câu lạc bộ: <span><?= htmlspecialchars($club['name']) ?></span></p>

                    <div class="date-picker-group">
                        <label for="att-date">Ngày điểm danh:</label>
                        <input type="date" id="att-date" class="date-input" value="<?= $attendance_date ?>" onchange="location.href='?id=<?= $club_id ?>&date='+this.value">
                    </div>
                </div>
            </div>

            <div class="header-right">
                <button class="btn-history" onclick="location.href='attendance_history.php?id=<?= $club_id ?>'">
                    <span>📅</span> Lịch sử điểm danh
                </button>
            </div>
        </header>

        <div class="filter-bar">
            <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; width: 100%;">
                <input type="hidden" name="id" value="<?= $club_id ?>">
                <input type="hidden" name="date" value="<?= $attendance_date ?>">
                <input type="text" name="search" placeholder="Tìm kiếm theo tên, email hoặc mã sinh viên..." class="search-input" value="<?= htmlspecialchars($search) ?>">
                <select name="role" class="role-select">
                    <option value="">Tất cả vai trò</option>
                    <option value="leader" <?= $role_filter === 'leader' ? 'selected' : '' ?>>Đội trưởng</option>
                    <option value="vice_leader" <?= $role_filter === 'vice_leader' ? 'selected' : '' ?>>Đội phó</option>
                    <option value="head" <?= $role_filter === 'head' ? 'selected' : '' ?>>Trưởng ban</option>
                    <option value="member" <?= $role_filter === 'member' ? 'selected' : '' ?>>Thành viên</option>
                </select>
                <select name="department" class="dept-select">
                    <option value="">Tất cả phòng ban</option>
                    <?php foreach ($departments_list as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= $department_filter == $dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-search">🔍 Tìm kiếm</button>
                <?php if (!empty($search) || !empty($role_filter) || !empty($department_filter)): ?>
                    <a href="attendance.php?id=<?= $club_id ?>&date=<?= $attendance_date ?>" class="btn-reset">✖ Xóa bộ lọc</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <form method="POST">
                <input type="hidden" name="save_attendance" value="1">
                <input type="hidden" name="attendance_date" value="<?= $attendance_date ?>">
                
                <?php if (empty($members)): ?>
                    <div class="empty-state">
                        <p>Không có thành viên nào trong CLB</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 25%;">Thành viên</th>
                                <th>Mã sinh viên</th>
                                <th>Vai trò</th>
                                <th>Phòng ban</th>
                                <th>Có mặt</th>
                                <th>Vắng có phép</th>
                                <th>Vắng không phép</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): 
                                $current_status = $attendance_data[$member['user_id']] ?? 'present';
                                $full_name = htmlspecialchars($member['full_name'] ?? 'Chưa cập nhật');
                                $first_char = mb_substr($full_name, 0, 1, 'UTF-8');
                            ?>
                            <tr>
                                <td>
                                    <div class="member-info">
                                        <div class="member-avatar"><?= htmlspecialchars($first_char) ?></div>
                                        <div>
                                            <div class="member-name"><?= $full_name ?></div>
                                            <div class="member-email"><?= htmlspecialchars($member['email'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($member['student_id'] ?? '---') ?></td>
                                <td>
                                    <span class="role-badge role-<?= $member['role'] ?>">
                                        <?= $role_labels[$member['role']] ?? 'Thành viên' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($member['department_name'] ?? '---') ?></td>
                                <td>
                                    <div class="radio-group">
                                        <label class="radio-option">
                                            <input type="radio" name="status[<?= $member['user_id'] ?>]" value="present" <?= $current_status === 'present' ? 'checked' : '' ?>>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="radio-group">
                                        <label class="radio-option">
                                            <input type="radio" name="status[<?= $member['user_id'] ?>]" value="absent_with_permission" <?= $current_status === 'absent_with_permission' ? 'checked' : '' ?>>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="radio-group">
                                        <label class="radio-option">
                                            <input type="radio" name="status[<?= $member['user_id'] ?>]" value="absent_without_permission" <?= $current_status === 'absent_without_permission' ? 'checked' : '' ?>>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                <div class="footer-actions">
                    <a href="club-detail.php?id=<?= $club_id ?>" class="btn-cancel">Hủy bỏ</a>
                    <button type="submit" class="btn-save">
                        <span>📥</span> Lưu điểm danh
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>