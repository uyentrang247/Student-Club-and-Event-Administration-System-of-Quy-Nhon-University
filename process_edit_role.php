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
$vai_tro = isset($_POST['vai_tro']) ? trim($_POST['vai_tro']) : '';
$phong_ban_id = isset($_POST['phong_ban_id']) && $_POST['phong_ban_id'] !== '' ? (int)$_POST['phong_ban_id'] : null;

// Kiểm tra dữ liệu
if ($member_id <= 0 || $club_id <= 0 || $vai_tro === '' || $phong_ban_id === null || $phong_ban_id <= 0) {
    echo "<script>alert('Vui lòng chọn phòng ban!'); window.history.back();</script>";
    exit;
}

// Kiểm tra quyền (chỉ đội trưởng mới được sửa)
$check_owner = $conn->prepare("SELECT chu_nhiem_id FROM clubs WHERE id = ?");
$check_owner->bind_param("i", $club_id);
$check_owner->execute();
$owner = $check_owner->get_result()->fetch_assoc();
$check_owner->close();

if (!$owner || $owner['chu_nhiem_id'] != $user_id) {
    echo "<script>alert('Bạn không có quyền thực hiện thao tác này!'); window.history.back();</script>";
    exit;
}

// Lấy thông tin user_id từ member_id
$get_user = $conn->prepare("SELECT user_id FROM club_members WHERE id = ? AND club_id = ?");
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
// Nếu gán vai trò cao (doi_truong, doi_pho) thì xóa trưởng phòng ban
if (in_array($vai_tro, ['doi_truong', 'doi_pho', 'chu_nhiem', 'pho_chu_nhiem'])) {
    // Xóa trưởng phòng ban nếu user đang là trưởng phòng ban
    $remove_truong_ban = $conn->prepare("UPDATE phong_ban SET truong_phong_id = NULL WHERE club_id = ? AND truong_phong_id = ?");
    $remove_truong_ban->bind_param("ii", $club_id, $member_user_id);
    $remove_truong_ban->execute();
    $remove_truong_ban->close();
}

// Nếu gán vai trò trưởng ban, đảm bảo không phải là đội trưởng hoặc đội phó
if ($vai_tro === 'truong_ban') {
    // Lấy vai trò hiện tại
    $get_current_role = $conn->prepare("SELECT vai_tro FROM club_members WHERE id = ? AND club_id = ?");
    $get_current_role->bind_param("ii", $member_id, $club_id);
    $get_current_role->execute();
    $current_role_result = $get_current_role->get_result();
    if ($current_role_result->num_rows > 0) {
        $current_role = $current_role_result->fetch_assoc()['vai_tro'];
        // Nếu user đang là đội trưởng hoặc đội phó, không cho phép
        if (in_array($current_role, ['doi_truong', 'doi_pho', 'chu_nhiem', 'pho_chu_nhiem'])) {
            $get_current_role->close();
            echo "<script>alert('Không thể gán trưởng ban cho đội trưởng/đội phó. Vui lòng chuyển về thành viên trước.'); window.history.back();</script>";
            exit;
        }
    }
    $get_current_role->close();
}

// Cập nhật chức vụ và phòng ban (phòng ban là bắt buộc)
$stmt = $conn->prepare("UPDATE club_members SET vai_tro = ?, phong_ban_id = ? WHERE id = ? AND club_id = ?");
$stmt->bind_param("siii", $vai_tro, $phong_ban_id, $member_id, $club_id);
$result = $stmt->execute();
$stmt->close();

if ($result) {
    echo "<script>alert('Cập nhật chức vụ thành công!'); window.location.href='taopb.php?id=$club_id';</script>";
} else {
    echo "<script>alert('Có lỗi xảy ra! Vui lòng thử lại.'); window.history.back();</script>";
}
?>
