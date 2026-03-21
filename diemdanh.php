<?
// Tìm kiếm và filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$department_filter = isset($_GET['department']) ? (int)$_GET['department'] : 0;

// Lấy danh sách phòng ban để hiển thị trong dropdown
$departments_sql = "SELECT id, ten_phong_ban FROM phong_ban WHERE club_id = ? ORDER BY created_at ASC, id ASC";
$dept_stmt = $conn->prepare($departments_sql);
$dept_stmt->bind_param("i", $club_id);
$dept_stmt->execute();
$departments_result = $dept_stmt->get_result();
$departments_list = [];
while ($row = $departments_result->fetch_assoc()) {
    $departments_list[] = $row;
}
$dept_stmt->close();

// Xây dựng WHERE clause
$where_conditions = ["cm.club_id = ?", "cm.trang_thai = 'dang_hoat_dong'"];
$params = [$club_id];
$types = 'i';

if (!empty($search)) {
    $where_conditions[] = "(u.ho_ten LIKE ? OR u.email LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($role_filter) && in_array($role_filter, ['doi_truong', 'doi_pho', 'truong_ban', 'thanh_vien', 'chu_nhiem', 'pho_chu_nhiem'])) {
    $where_conditions[] = "cm.vai_tro = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if (!empty($department_filter) && $department_filter > 0) {
    $where_conditions[] = "cm.phong_ban_id = ?";
    $params[] = $department_filter;
    $types .= 'i';
}

$where_clause = implode(' AND ', $where_conditions);
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
            max-width: 1100px;
            margin: 0 auto;
        }

        /* --- HEADER SECTION --- */
        .header {
            background: var(--white);
            padding: 25px 30px;
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            /* Đẩy nội dung ra 2 đầu */
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

        /* Ô chọn ngày thiết kế mới */
        .date-picker-group {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--light-purple);
            padding: 8px 18px;
            border-radius: 10px;
            border: 1px solid #e0def3;
            width: fit-content;
        }

        /* Tạo hàng ngang cho CLB và Ngày */
        .info-row {
            display: flex;
            align-items: center;
            /* Căn giữa theo chiều dọc */
            gap: 30px;
            /* Khoảng cách giữa tên CLB và ô chọn ngày */
            margin-top: 5px;
        }

        .info-row p {
            margin: 0 !important;
            /* Bỏ margin mặc định để nó không bị lệch hàng */
        }

        /* Chỉnh lại ô chọn ngày cho gọn hơn để nằm vừa trên hàng */
        .date-picker-group {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--light-purple);
            padding: 5px 15px;
            /* Giảm padding một chút cho thanh thoát */
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

        /* Nút lịch sử bên phải */
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

        /* --- FILTER BAR --- */
        .filter-bar {
            background: var(--white);
            padding: 15px;
            border-radius: 12px;
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            align-items: center;
        }

        .search-input {
            flex: 1;
            border: 1px solid #ddd;
            padding: 12px 15px;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
        }

        .search-input:focus {
            border-color: var(--primary-purple);
        }

        .dept-select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            background: #fff;
            min-width: 180px;
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

        /* --- TABLE SECTION --- */
        .table-container {
            background: var(--white);
            border-radius: 15px;
            padding: 20px;
            min-height: 300px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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

        /* Custom Radio Buttons */
        input[type="radio"] {
            appearance: none;
            width: 22px;
            height: 22px;
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
            width: 12px;
            height: 12px;
            background: var(--primary-purple);
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

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
    </style>
</head>

<body>
    <div class="container">
        <header class="header">
            <div class="header-left">
                <h2>Điểm danh thành viên</h2>
                <div class="info-row">
                    <p>Câu lạc bộ: <span>Kết nối trẻ</span></p> <!-- Tên CLB lấy từ database dựa vào id nhá -->

                    <div class="date-picker-group">
                        <label for="att-date">Ngày điểm danh:</label>
                        <input type="date" id="att-date" class="date-input" value="2026-03-19">
                    </div>
                </div>
            </div>

            <div class="header-right">
                <button class="btn-history">
                    <span>📅</span> Lịch sử điểm danh
                </button>
            </div>
        </header>

        <div class="filter-bar">
            <input type="text" placeholder="Tìm kiếm theo tên hoặc mã sinh viên..." class="search-input">
            <select class="dept-select">
                <option>Tất cả phòng ban</option>
                <option>Ban Truyền thông</option>
                <option>Ban Kỹ thuật</option>
                <option>Ban Sự kiện</option>
            </select>
            <button class="btn-search">🔍 Tìm kiếm</button>
        </div>

        <div class="table-container">
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
                    <tr> <!-- lấy danh sách từ database -->
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><input type="radio" name="member1" checked></td>
                        <td><input type="radio" name="member1"></td>
                        <td><input type="radio" name="member1"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer-actions">
            <button class="btn-cancel">Hủy bỏ</button>
            <button class="btn-save">
                <span>📥</span> Lưu điểm danh
            </button>
        </div>
    </div>
</body>

</html>