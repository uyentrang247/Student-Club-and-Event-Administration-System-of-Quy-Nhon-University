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
$club_member_id = isset($_POST['club_member_id']) ? (int)$_POST['club_member_id'] : 0;
$vai_tro = isset($_POST['vai_tro']) ? trim($_POST['vai_tro']) : '';
$phong_ban_id = isset($_POST['phong_ban_id']) && $_POST['phong_ban_id'] !== '' ? (int)$_POST['phong_ban_id'] : null;

// Validate input
if ($club_member_id <= 0 || $vai_tro === '') {
    json_response(['success' => false, 'message' => 'Thiếu thông tin bắt buộc'], HttpStatus::BAD_REQUEST);
}

// Validate role
$valid_roles = [UserRole::DOI_TRUONG, UserRole::DOI_PHO, UserRole::TRUONG_BAN, UserRole::THANH_VIEN, UserRole::CHU_NHIEM, UserRole::PHO_CHU_NHIEM];
if (!in_array($vai_tro, $valid_roles)) {
    json_response(['success' => false, 'message' => 'Vai trò không hợp lệ'], HttpStatus::BAD_REQUEST);
}

// Lấy thông tin thành viên và CLB
$get_member = "SELECT cm.club_id, cm.user_id, cm.vai_tro 
               FROM club_members cm 
               WHERE cm.id = ?";

if ($stmt = $conn->prepare($get_member)) {
    $stmt->bind_param("i", $club_member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        json_response(['success' => false, 'message' => 'Không tìm thấy thành viên'], HttpStatus::NOT_FOUND);
    }
    
    $member_data = $result->fetch_assoc();
    $club_id = $member_data['club_id'];
    $member_role = $member_data['vai_tro'];
    $stmt->close();
} else {
    json_response(['success' => false, 'message' => 'Lỗi truy vấn database'], HttpStatus::INTERNAL_ERROR);
}

// Kiểm tra quyền (chỉ đội trưởng mới được sửa)
$check_owner = $conn->prepare("SELECT chu_nhiem_id FROM clubs WHERE id = ?");
$check_owner->bind_param("i", $club_id);
$check_owner->execute();
$owner = $check_owner->get_result()->fetch_assoc();
$check_owner->close();

if (!$owner || $owner['chu_nhiem_id'] != $current_user_id) {
    json_response(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này'], HttpStatus::FORBIDDEN);
}

// Không cho phép thay đổi vai trò của đội trưởng (trừ khi chuyển sang vai trò khác)
if (in_array($member_role, [UserRole::DOI_TRUONG, UserRole::CHU_NHIEM]) && $vai_tro !== UserRole::DOI_TRUONG && $vai_tro !== UserRole::CHU_NHIEM) {
    json_response(['success' => false, 'message' => 'Không thể thay đổi vai trò của đội trưởng CLB'], HttpStatus::FORBIDDEN);
}

// Nếu phòng ban được chọn, kiểm tra phòng ban thuộc CLB
if ($phong_ban_id !== null && $phong_ban_id > 0) {
    $check_pb = $conn->prepare("SELECT id FROM phong_ban WHERE id = ? AND club_id = ?");
    $check_pb->bind_param("ii", $phong_ban_id, $club_id);
    $check_pb->execute();
    $pb_result = $check_pb->get_result();
    $check_pb->close();
    
    if ($pb_result->num_rows === 0) {
        json_response(['success' => false, 'message' => 'Phòng ban không hợp lệ'], HttpStatus::BAD_REQUEST);
    }
}

// Đảm bảo 1 user chỉ có 1 chức vụ trong 1 CLB
// Nếu gán vai trò cao (doi_truong, doi_pho) thì xóa trưởng phòng ban
if (in_array($vai_tro, [UserRole::DOI_TRUONG, UserRole::DOI_PHO, UserRole::CHU_NHIEM, UserRole::PHO_CHU_NHIEM])) {
    // Xóa trưởng phòng ban nếu user đang là trưởng phòng ban
    $remove_truong_ban = $conn->prepare("UPDATE phong_ban SET truong_phong_id = NULL WHERE club_id = ? AND truong_phong_id = ?");
    $remove_truong_ban->bind_param("ii", $club_id, $member_data['user_id']);
    $remove_truong_ban->execute();
    $remove_truong_ban->close();
}

// Nếu gán vai trò trưởng ban, đảm bảo không phải là đội trưởng hoặc đội phó
if ($vai_tro === UserRole::TRUONG_BAN) {
    // Nếu user đang là đội trưởng hoặc đội phó, không cho phép
    if (in_array($member_role, [UserRole::DOI_TRUONG, UserRole::DOI_PHO, UserRole::CHU_NHIEM, UserRole::PHO_CHU_NHIEM])) {
        json_response(['success' => false, 'message' => 'Không thể gán trưởng ban cho đội trưởng/đội phó. Vui lòng chuyển về thành viên trước.'], HttpStatus::BAD_REQUEST);
    }
}

// Cập nhật vai trò và phòng ban
if ($phong_ban_id !== null && $phong_ban_id > 0) {
    $stmt = $conn->prepare("UPDATE club_members SET vai_tro = ?, phong_ban_id = ? WHERE id = ? AND club_id = ?");
    $stmt->bind_param("siii", $vai_tro, $phong_ban_id, $club_member_id, $club_id);
} else {
    // Nếu không chọn phòng ban, chỉ cập nhật vai trò
    $stmt = $conn->prepare("UPDATE club_members SET vai_tro = ? WHERE id = ? AND club_id = ?");
    $stmt->bind_param("sii", $vai_tro, $club_member_id, $club_id);
}

$result = $stmt->execute();
$stmt->close();

if ($result) {
    json_response(['success' => true, 'message' => 'Cập nhật thành công'], HttpStatus::OK);
} else {
    json_response(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật'], HttpStatus::INTERNAL_ERROR);
}
?>

