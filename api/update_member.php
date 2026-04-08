<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../assets/database/connect.php';

header('Content-Type: application/json; charset=utf-8');

// Check login
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Chưa đăng nhập'], HttpStatus::UNAUTHORIZED);
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Phương thức không hợp lệ'], HttpStatus::BAD_REQUEST);
}

$current_user_id = $_SESSION['user_id'];
$member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
$role = isset($_POST['role']) ? trim($_POST['role']) : '';
$department_id = isset($_POST['department_id']) && $_POST['department_id'] !== '' ? (int)$_POST['department_id'] : null;

// Validate input
if ($member_id <= 0 || $role === '') {
    json_response(['success' => false, 'message' => 'Thiếu thông tin bắt buộc'], HttpStatus::BAD_REQUEST);
}

// Validate role (các giá trị role mới)
$valid_roles = ['leader', 'vice_leader', 'head', 'member'];
if (!in_array($role, $valid_roles)) {
    json_response(['success' => false, 'message' => 'Vai trò không hợp lệ'], HttpStatus::BAD_REQUEST);
}

// Lấy thông tin thành viên và CLB từ bảng members
$get_member = "SELECT m.club_id, m.user_id, m.role 
               FROM members m 
               WHERE m.id = ?";

if ($stmt = $conn->prepare($get_member)) {
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        json_response(['success' => false, 'message' => 'Không tìm thấy thành viên'], HttpStatus::NOT_FOUND);
    }
    
    $member_data = $result->fetch_assoc();
    $club_id = $member_data['club_id'];
    $current_role = $member_data['role'];
    $stmt->close();
} else {
    json_response(['success' => false, 'message' => 'Lỗi truy vấn database'], HttpStatus::INTERNAL_ERROR);
}

// Kiểm tra quyền (chỉ leader mới được sửa)
$check_owner = $conn->prepare("SELECT leader_id FROM clubs WHERE id = ?");
$check_owner->bind_param("i", $club_id);
$check_owner->execute();
$owner = $check_owner->get_result()->fetch_assoc();
$check_owner->close();

if (!$owner || $owner['leader_id'] != $current_user_id) {
    json_response(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này'], HttpStatus::FORBIDDEN);
}

// Không cho phép thay đổi vai trò của leader (trừ khi chuyển sang vai trò khác)
if ($current_role === 'leader' && $role !== 'leader') {
    json_response(['success' => false, 'message' => 'Không thể thay đổi vai trò của leader CLB'], HttpStatus::FORBIDDEN);
}

// Nếu phòng ban được chọn, kiểm tra phòng ban thuộc CLB từ bảng departments
if ($department_id !== null && $department_id > 0) {
    $check_dept = $conn->prepare("SELECT id FROM departments WHERE id = ? AND club_id = ?");
    $check_dept->bind_param("ii", $department_id, $club_id);
    $check_dept->execute();
    $dept_result = $check_dept->get_result();
    $check_dept->close();
    
    if ($dept_result->num_rows === 0) {
        json_response(['success' => false, 'message' => 'Phòng ban không hợp lệ'], HttpStatus::BAD_REQUEST);
    }
}

// Đảm bảo 1 user chỉ có 1 chức vụ trong 1 CLB
// Nếu gán vai trò cao (leader, vice_leader) thì xóa head của phòng ban
if (in_array($role, ['leader', 'vice_leader'])) {
    // Xóa head nếu user đang là head của phòng ban
    $remove_head = $conn->prepare("UPDATE departments SET head_id = NULL WHERE club_id = ? AND head_id = ?");
    $remove_head->bind_param("ii", $club_id, $member_data['user_id']);
    $remove_head->execute();
    $remove_head->close();
}

// Nếu gán vai trò head, đảm bảo không phải là leader hoặc vice_leader
if ($role === 'head') {
    // Nếu user đang là leader hoặc vice_leader, không cho phép
    if (in_array($current_role, ['leader', 'vice_leader'])) {
        json_response(['success' => false, 'message' => 'Không thể gán head cho leader/vice_leader. Vui lòng chuyển về member trước.'], HttpStatus::BAD_REQUEST);
    }
}

// Cập nhật vai trò và phòng ban trong bảng members
if ($department_id !== null && $department_id > 0) {
    $stmt = $conn->prepare("UPDATE members SET role = ?, department_id = ? WHERE id = ? AND club_id = ?");
    $stmt->bind_param("siii", $role, $department_id, $member_id, $club_id);
} else {
    // Nếu không chọn phòng ban, chỉ cập nhật vai trò
    $stmt = $conn->prepare("UPDATE members SET role = ? WHERE id = ? AND club_id = ?");
    $stmt->bind_param("sii", $role, $member_id, $club_id);
}

$result = $stmt->execute();
$stmt->close();

if ($result) {
    // Nếu cập nhật thành leader, cập nhật leader_id trong clubs
    if ($role === 'leader') {
        $update_club = $conn->prepare("UPDATE clubs SET leader_id = ? WHERE id = ?");
        $update_club->bind_param("ii", $member_data['user_id'], $club_id);
        $update_club->execute();
        $update_club->close();
    }
    
    json_response(['success' => true, 'message' => 'Cập nhật thành công'], HttpStatus::OK);
} else {
    json_response(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật'], HttpStatus::INTERNAL_ERROR);
}
?>

