<?php
session_start();
require_once('assets/database/connect.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    echo "<script>alert('Vui lòng đăng nhập!'); window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'];
$member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
$club_id = isset($_POST['club_id']) ? (int)$_POST['club_id'] : 0;
$role = isset($_POST['role']) ? trim($_POST['role']) : '';
$department_id = isset($_POST['department_id']) && $_POST['department_id'] !== '' ? (int)$_POST['department_id'] : null;

// Kiểm tra dữ liệu
if ($member_id <= 0 || $club_id <= 0 || $role === '' || $department_id === null || $department_id <= 0) {
    echo "<script>alert('Vui lòng chọn phòng ban!'); window.history.back();</script>";
    exit;
}

// Kiểm tra quyền (chỉ leader mới được sửa)
$check_owner = $conn->prepare("SELECT leader_id FROM clubs WHERE id = ?");
$check_owner->bind_param("i", $club_id);
$check_owner->execute();
$owner = $check_owner->get_result()->fetch_assoc();
$check_owner->close();

if (!$owner || $owner['leader_id'] != $user_id) {
    echo "<script>alert('Bạn không có quyền thực hiện thao tác này!'); window.history.back();</script>";
    exit;
}

// Lấy thông tin user_id từ member_id
$get_user = $conn->prepare("SELECT user_id FROM members WHERE id = ? AND club_id = ?");
$get_user->bind_param("ii", $member_id, $club_id);
$get_user->execute();
$user_result = $get_user->get_result();
if ($user_result->num_rows === 0) {
    $get_user->close();
    echo "<script>alert('Không tìm thấy thành viên!'); window.history.back();</script>";
    exit;
}
$member_user_id = $user_result->fetch_assoc()['user_id'];
$get_user->close();

// Đảm bảo 1 user chỉ có 1 chức vụ trong 1 CLB
// Nếu gán vai trò cao (leader, vice_leader) thì xóa head của phòng ban
if (in_array($role, ['leader', 'vice_leader'])) {
    // Xóa head nếu user đang là head của phòng ban
    $remove_head = $conn->prepare("UPDATE departments SET head_id = NULL WHERE club_id = ? AND head_id = ?");
    $remove_head->bind_param("ii", $club_id, $member_user_id);
    $remove_head->execute();
    $remove_head->close();
}

// Nếu gán vai trò head, đảm bảo không phải là leader hoặc vice_leader
if ($role === 'head') {
    // Lấy vai trò hiện tại
    $get_current_role = $conn->prepare("SELECT role FROM members WHERE id = ? AND club_id = ?");
    $get_current_role->bind_param("ii", $member_id, $club_id);
    $get_current_role->execute();
    $current_role_result = $get_current_role->get_result();
    if ($current_role_result->num_rows > 0) {
        $current_role = $current_role_result->fetch_assoc()['role'];
        // Nếu user đang là leader hoặc vice_leader, không cho phép
        if (in_array($current_role, ['leader', 'vice_leader'])) {
            $get_current_role->close();
            echo "<script>alert('Không thể gán head cho leader/vice_leader. Vui lòng chuyển về member trước.'); window.history.back();</script>";
            exit;
        }
    }
    $get_current_role->close();
}

// Cập nhật chức vụ và phòng ban
$stmt = $conn->prepare("UPDATE members SET role = ?, department_id = ? WHERE id = ? AND club_id = ?");
$stmt->bind_param("siii", $role, $department_id, $member_id, $club_id);
$result = $stmt->execute();
$stmt->close();

if ($result) {
    echo "<script>alert('Cập nhật chức vụ thành công!'); window.location.href='taopb.php?id=$club_id';</script>";
} else {
    echo "<script>alert('Có lỗi xảy ra! Vui lòng thử lại.'); window.history.back();</script>";
}
?>
