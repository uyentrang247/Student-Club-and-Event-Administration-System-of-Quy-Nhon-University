<?php
session_start();
require_once __DIR__ . '/../assets/database/connect.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Chưa đăng nhập'], HttpStatus::UNAUTHORIZED);
}

$member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
$club_id = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;

if (!$member_id || !$club_id) {
    json_response(['success' => false, 'message' => 'Thiếu thông tin'], HttpStatus::BAD_REQUEST);
}

// Lấy thông tin chi tiết từ members, users, và join_requests
$sql = "SELECT 
            m.id AS member_id,
            m.user_id,
            m.club_id,
            m.department_id,
            m.status AS member_status,
            m.role,
            u.full_name,
            u.username,
            u.email,
            u.avatar,
            u.phone AS user_phone,
            d.name AS department_name,
            jr.phone AS request_phone,
            jr.message AS request_message,
            jr.requested_at,
            c.name AS club_name
        FROM members m
        INNER JOIN users u ON m.user_id = u.id
        LEFT JOIN departments d ON m.department_id = d.id AND d.club_id = m.club_id
        LEFT JOIN join_requests jr ON jr.club_id = m.club_id AND jr.user_id = m.user_id
        INNER JOIN clubs c ON m.club_id = c.id
        WHERE m.id = ? AND m.club_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $member_id, $club_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Nếu không tìm thấy trong members, có thể đã bị từ chối (xóa)
    // Lấy thông tin từ join_requests dựa vào club_id (lấy request gần nhất)
    $fallback_sql = "SELECT 
                        jr.club_id,
                        jr.user_id,
                        jr.phone AS request_phone,
                        jr.message AS request_message,
                        jr.requested_at,
                        u.full_name,
                        u.username,
                        u.email,
                        u.avatar,
                        u.phone AS user_phone,
                        c.name AS club_name
                    FROM join_requests jr
                    INNER JOIN users u ON jr.user_id = u.id
                    INNER JOIN clubs c ON jr.club_id = c.id
                    WHERE jr.club_id = ?
                    ORDER BY jr.requested_at DESC LIMIT 1";
    
    $fallback_stmt = $conn->prepare($fallback_sql);
    $fallback_stmt->bind_param("i", $club_id);
    $fallback_stmt->execute();
    $fallback_result = $fallback_stmt->get_result();
    
    if ($fallback_result->num_rows > 0) {
        $data = $fallback_result->fetch_assoc();
        $data['member_status'] = 'rejected'; // Đánh dấu là đã từ chối
        $data['department_id'] = null;
        $data['department_name'] = null;
        $data['role'] = null;
        $data['member_id'] = null;
        $fallback_stmt->close();
    } else {
        $fallback_stmt->close();
        json_response(['success' => false, 'message' => 'Không tìm thấy thông tin yêu cầu'], HttpStatus::NOT_FOUND);
    }
} else {
    $data = $result->fetch_assoc();
}
$stmt->close();

// Lấy danh sách phòng ban của CLB từ bảng departments
$dept_sql = "SELECT id, name FROM departments WHERE club_id = ? ORDER BY name ASC";
$dept_stmt = $conn->prepare($dept_sql);
$dept_stmt->bind_param("i", $club_id);
$dept_stmt->execute();
$dept_result = $dept_stmt->get_result();
$departments = $dept_result->fetch_all(MYSQLI_ASSOC);
$dept_stmt->close();

// Chuyển đổi tên trường cho dễ dùng trong frontend
$response_data = [
    'member_id' => $data['member_id'] ?? null,
    'user_id' => $data['user_id'],
    'club_id' => $data['club_id'],
    'member_status' => $data['member_status'],
    'role' => $data['role'] ?? null,
    'full_name' => $data['full_name'],
    'username' => $data['username'],
    'email' => $data['email'],
    'avatar' => $data['avatar'],
    'user_phone' => $data['user_phone'],
    'department_id' => $data['department_id'] ?? null,
    'department_name' => $data['department_name'],
    'request_phone' => $data['request_phone'],
    'message' => $data['request_message'] ?? '',
    'requested_at' => $data['requested_at'],
    'club_name' => $data['club_name']
];

json_response([
    'success' => true,
    'request' => $response_data,
    'departments' => $departments
], HttpStatus::OK);
?>

