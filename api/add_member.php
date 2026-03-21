<?php
// Load dependencies FIRST
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/constants.php';
require_once __DIR__ . '/../includes/functions.php';

// NOW start session
session_start();

require_once __DIR__ . '/../assets/database/connect.php';

header('Content-Type: application/json; charset=utf-8');

// Check login
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Chưa đăng nhập'], HttpStatus::UNAUTHORIZED);
}

// CSRF
$csrf_token = $_POST[CSRF_TOKEN_NAME] ?? '';
if (!verify_csrf_token($csrf_token)) {
    json_response(['success' => false, 'message' => 'Phiên không hợp lệ'], HttpStatus::BAD_REQUEST);
}

// Rate limit
if (!check_rate_limit('add_member_' . $_SESSION['user_id'], 10, 300)) {
    json_response(['success' => false, 'message' => 'Bạn thao tác quá nhanh, vui lòng thử lại sau'], HttpStatus::BAD_REQUEST);
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Phương thức không hợp lệ'], HttpStatus::BAD_REQUEST);
}

$club_id = isset($_POST['club_id']) ? (int)$_POST['club_id'] : 0;
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$phong_ban_id = isset($_POST['phong_ban_id']) && $_POST['phong_ban_id'] !== '' ? (int)$_POST['phong_ban_id'] : null;
$current_user_id = $_SESSION['user_id'];

if (!$club_id || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

// Phòng ban là bắt buộc
if ($phong_ban_id === null || $phong_ban_id <= 0) {
    json_response(['success' => false, 'message' => 'Vui lòng chọn phòng ban'], HttpStatus::BAD_REQUEST);
}

// Kiểm tra user tồn tại
$user_exists_stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$user_exists_stmt->bind_param("i", $user_id);
$user_exists_stmt->execute();
$user_exists = $user_exists_stmt->get_result()->num_rows > 0;
$user_exists_stmt->close();
if (!$user_exists) {
    json_response(['success' => false, 'message' => 'Người dùng không tồn tại'], HttpStatus::NOT_FOUND);
}

// Kiểm tra quyền (phải là đội trưởng hoặc đội phó)
if (!can_manage_club($conn, $current_user_id, $club_id)) {
    json_response(['success' => false, 'message' => 'Bạn không có quyền thêm thành viên'], HttpStatus::FORBIDDEN);
}

// Kiểm tra phòng ban thuộc CLB
$dept_stmt = $conn->prepare("SELECT id FROM phong_ban WHERE id = ? AND club_id = ?");
$dept_stmt->bind_param("ii", $phong_ban_id, $club_id);
$dept_stmt->execute();
$valid_dept = $dept_stmt->get_result()->num_rows > 0;
$dept_stmt->close();
if (!$valid_dept) {
    json_response(['success' => false, 'message' => 'Phòng ban không hợp lệ'], HttpStatus::BAD_REQUEST);
}

// Kiểm tra xem user đã là thành viên chưa
$check_member = "SELECT id FROM club_members WHERE club_id = ? AND user_id = ?";
if ($stmt = $conn->prepare($check_member)) {
    $stmt->bind_param("ii", $club_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Người dùng đã là thành viên']);
        $stmt->close();
        exit;
    }
    $stmt->close();
}

// Thêm thành viên mới (bắt buộc có phòng ban)
$insert_member = "INSERT INTO club_members (club_id, user_id, vai_tro, trang_thai, phong_ban_id) 
                  VALUES (?, ?, ?, ?, ?)";

if ($stmt = $conn->prepare($insert_member)) {
    $vai_tro = UserRole::THANH_VIEN;
    $trang_thai = MemberStatus::DANG_HOAT_DONG;
    $stmt->bind_param("iissi", $club_id, $user_id, $vai_tro, $trang_thai, $phong_ban_id);
    
    if ($stmt->execute()) {
        // Lấy thông tin CLB
        $get_club = "SELECT ten_clb FROM clubs WHERE id = ?";
        $club_name = "CLB";
        
        if ($stmt_club = $conn->prepare($get_club)) {
            $stmt_club->bind_param("i", $club_id);
            $stmt_club->execute();
            $result_club = $stmt_club->get_result();
            if ($row = $result_club->fetch_assoc()) {
                $club_name = $row['ten_clb'];
            }
            $stmt_club->close();
        }
        
        // Tạo thông báo cho người được thêm
        $notification_title = "Bạn đã được thêm vào CLB";
        $notification_message = "Bạn đã được thêm vào CLB " . $club_name . ". Chào mừng bạn đến với CLB!";
        $notification_link = "club-detail.php?id=" . $club_id;
        $notification_type = NotificationType::CLUB_JOIN;
        
        $insert_notification = "INSERT INTO notifications (user_id, type, title, message, link) 
                               VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt_notif = $conn->prepare($insert_notification)) {
            $stmt_notif->bind_param("issss", $user_id, $notification_type, $notification_title, $notification_message, $notification_link);
            $stmt_notif->execute();
            $stmt_notif->close();
        }
        
        // Log activity
        log_error("Member added to club", ['club_id' => $club_id, 'user_id' => $user_id, 'added_by' => $current_user_id]);
        
        // Cập nhật số lượng thành viên
        $update_count = "UPDATE clubs SET so_thanh_vien = (
                            SELECT COUNT(*) FROM club_members 
                            WHERE club_id = ? AND trang_thai = 'dang_hoat_dong'
                        ) WHERE id = ?";
        
        if ($stmt_update = $conn->prepare($update_count)) {
            $stmt_update->bind_param("ii", $club_id, $club_id);
            $stmt_update->execute();
            $stmt_update->close();
        }
        
        json_response(['success' => true, 'message' => 'Thêm thành viên thành công'], HttpStatus::CREATED);
    } else {
        log_error("Error adding member: " . $stmt->error, ['club_id' => $club_id, 'user_id' => $user_id]);
        json_response(['success' => false, 'message' => 'Lỗi khi thêm thành viên'], HttpStatus::INTERNAL_ERROR);
    }
    
    $stmt->close();
} else {
    json_response(['success' => false, 'message' => 'Lỗi database'], HttpStatus::INTERNAL_ERROR);
}
