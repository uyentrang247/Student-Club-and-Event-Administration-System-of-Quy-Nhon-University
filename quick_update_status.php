<?php
session_start();
require_once __DIR__ . "/assets/database/connect.php";
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/functions.php";

header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập']);
    exit;
}

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức request không hợp lệ']);
    exit;
}

// Kiểm tra CSRF token
$csrf_token = $_POST[CSRF_TOKEN_NAME] ?? '';
if (!verify_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Phiên không hợp lệ. Vui lòng thử lại.']);
    exit;
}

// Nhận dữ liệu
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

// Validate
if ($event_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID sự kiện không hợp lệ']);
    exit;
}

$allowed_statuses = ['upcoming', 'ongoing', 'completed', 'cancelled'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
    exit;
}

// Kiểm tra sự kiện tồn tại và lấy thông tin CLB
$check_sql = "SELECT e.club_id, e.created_by, c.leader_id 
              FROM events e 
              LEFT JOIN clubs c ON e.club_id = c.id 
              WHERE e.id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $event_id);
$check_stmt->execute();
$event_info = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

if (!$event_info) {
    echo json_encode(['success' => false, 'message' => 'Sự kiện không tồn tại']);
    exit;
}

$club_id = $event_info['club_id'];
$user_id = $_SESSION['user_id'];
$is_owner = isset($event_info['leader_id']) && ((int)$event_info['leader_id'] === (int)$user_id);
$is_creator = isset($event_info['created_by']) && ((int)$event_info['created_by'] === (int)$user_id);

// Kiểm tra vai trò của người dùng trong CLB
$user_role = 'guest';
$role_sql = "SELECT role FROM members WHERE club_id = ? AND user_id = ? AND status = 'active' LIMIT 1";
$role_stmt = $conn->prepare($role_sql);
if ($role_stmt) {
    $role_stmt->bind_param("ii", $club_id, $user_id);
    $role_stmt->execute();
    $role_res = $role_stmt->get_result();
    if ($role_res && $role_res->num_rows > 0) {
        $user_role = strtolower($role_res->fetch_assoc()['role'] ?? 'guest');
    }
    $role_stmt->close();
}

// Chuẩn hóa vai trò để so sánh
function normalize_role_quick($role) {
    $role = strtolower(trim($role));
    $map = [
        'vice_leader' => 'vice_leader',
        'leader' => 'leader',
        'head' => 'head',
        'member' => 'member'
    ];
    return $map[$role] ?? $role;
}

$role_key = normalize_role_quick($user_role);
$can_manage = $is_owner || $is_creator || in_array($role_key, ['leader', 'vice_leader', 'head']);

if (!$can_manage) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thay đổi trạng thái sự kiện này']);
    exit;
}

// Cập nhật trạng thái
$update_sql = "UPDATE events SET status = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("si", $status, $event_id);

if ($update_stmt->execute()) {
    $update_stmt->close();
    echo json_encode(['success' => true, 'message' => 'Đã cập nhật trạng thái thành công']);
} else {
    $update_stmt->close();
    echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật: ' . $conn->error]);
}

$conn->close();
?>