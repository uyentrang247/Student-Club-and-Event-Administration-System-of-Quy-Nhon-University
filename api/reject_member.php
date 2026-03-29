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

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Phương thức không hợp lệ'], HttpStatus::BAD_REQUEST);
}

$member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
$current_user_id = $_SESSION['user_id'];

if (!$member_id) {
    json_response(['success' => false, 'message' => 'Thiếu thông tin'], HttpStatus::BAD_REQUEST);
}

// Lấy thông tin yêu cầu từ bảng members
$get_request = "SELECT m.club_id, m.user_id, c.name as club_name 
                FROM members m
                JOIN clubs c ON m.club_id = c.id
                WHERE m.id = ? AND m.status = ?";

$stmt = $conn->prepare($get_request);
$status_pending = 'pending';
$stmt->bind_param("is", $member_id, $status_pending);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    json_response(['success' => false, 'message' => 'Yêu cầu không tồn tại'], HttpStatus::NOT_FOUND);
}

$request_data = $result->fetch_assoc();
$club_id = $request_data['club_id'];
$user_id = $request_data['user_id'];
$club_name = $request_data['club_name'];
$stmt->close();

// Lấy tên người từ chối
$get_rejecter_sql = "SELECT full_name FROM users WHERE id = ?";
$stmt_rejecter = $conn->prepare($get_rejecter_sql);
$stmt_rejecter->bind_param("i", $current_user_id);
$stmt_rejecter->execute();
$rejecter_result = $stmt_rejecter->get_result();
$rejecter_name = 'Quản trị viên';
if ($rejecter_result->num_rows > 0) {
    $rejecter_data = $rejecter_result->fetch_assoc();
    $rejecter_name = $rejecter_data['full_name'];
}
$stmt_rejecter->close();

// Kiểm tra quyền (phải là đội trưởng hoặc đội phó)
if (!can_manage_club($conn, $current_user_id, $club_id)) {
    json_response(['success' => false, 'message' => 'Bạn không có quyền từ chối thành viên'], HttpStatus::FORBIDDEN);
}

// Từ chối yêu cầu - xóa khỏi bảng members
$reject_sql = "DELETE FROM members WHERE id = ?";
$stmt_reject = $conn->prepare($reject_sql);
$stmt_reject->bind_param("i", $member_id);

if ($stmt_reject->execute()) {
    // Tạo thông báo chi tiết cho người bị từ chối
    $notification_title = "❌ Yêu cầu tham gia CLB bị từ chối";
    $notification_message = "Rất tiếc, yêu cầu tham gia CLB \"{$club_name}\" của bạn đã bị {$rejecter_name} từ chối. Bạn có thể thử lại sau hoặc tham gia các CLB khác.";
    $notification_link = "DanhsachCLB.php";
    $notification_type = "club_join";
    
    $insert_notification = "INSERT INTO notifications (user_id, type, title, message, link) 
                           VALUES (?, ?, ?, ?, ?)";
    
    $stmt_notif = $conn->prepare($insert_notification);
    $stmt_notif->bind_param("issss", $user_id, $notification_type, $notification_title, $notification_message, $notification_link);
    $stmt_notif->execute();
    $stmt_notif->close();
    
    // Log activity
    log_error("Member request rejected", [
        'club_id' => $club_id,
        'member_id' => $member_id,
        'rejected_by' => $current_user_id
    ]);
    
    json_response(['success' => true, 'message' => 'Đã từ chối yêu cầu'], HttpStatus::OK);
} else {
    log_error("Error rejecting member: " . $stmt_reject->error, ['member_id' => $member_id]);
    json_response(['success' => false, 'message' => 'Lỗi khi từ chối'], HttpStatus::INTERNAL_ERROR);
}

$stmt_reject->close();
?>
