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
if (!check_rate_limit('delete_account_' . $_SESSION['user_id'], 3, 3600)) {
    json_response(['success' => false, 'message' => 'Bạn đã thử quá nhiều lần. Vui lòng thử lại sau 1 giờ.'], HttpStatus::BAD_REQUEST);
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Phương thức không hợp lệ'], HttpStatus::BAD_REQUEST);
}

$user_id = $_SESSION['user_id'];
$confirm_text = trim($_POST['confirm_text'] ?? '');

// Xác nhận phải nhập đúng "XÓA TÀI KHOẢN"
if ($confirm_text !== 'XÓA TÀI KHOẢN') {
    json_response(['success' => false, 'message' => 'Vui lòng nhập chính xác "XÓA TÀI KHOẢN" để xác nhận'], HttpStatus::BAD_REQUEST);
}

// Kiểm tra xem user có đang là leader của CLB nào không (cột leader_id)
$check_owner_sql = "SELECT COUNT(*) as count FROM clubs WHERE leader_id = ?";
$check_stmt = $conn->prepare($check_owner_sql);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$owner_result = $check_stmt->get_result();
$owner_count = $owner_result->fetch_assoc()['count'] ?? 0;
$check_stmt->close();

if ($owner_count > 0) {
    json_response([
        'success' => false, 
        'message' => 'Bạn đang là leader của ' . $owner_count . ' CLB. Vui lòng chuyển quyền leader hoặc xóa CLB trước khi xóa tài khoản.'
    ], HttpStatus::BAD_REQUEST);
}

// Bắt đầu transaction
$conn->begin_transaction();

try {
    // Lấy thông tin user để xóa avatar
    $user_sql = "SELECT avatar FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    $avatar_path = $user_data['avatar'] ?? '';
    $user_stmt->close();
    
    // Xóa avatar file nếu có
    if (!empty($avatar_path) && file_exists($avatar_path)) {
        @unlink($avatar_path);
    }
    
    // Xóa tất cả dữ liệu liên quan (cascade delete sẽ tự động xử lý)
    // Các bảng có foreign key với ON DELETE CASCADE sẽ tự động xóa:
    // - members (thay vì club_members)
    // - event_registrations
    // - notifications
    // - join_requests
    // - reviews
    // - media (uploader_id - SET NULL)
    // - gallery (uploaded_by - SET NULL)
    
    // Xóa user (cascade sẽ tự động xóa các bảng liên quan)
    $delete_sql = "DELETE FROM users WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $user_id);
    
    if (!$delete_stmt->execute()) {
        throw new Exception("Lỗi khi xóa tài khoản: " . $delete_stmt->error);
    }
    
    $delete_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Xóa session
    session_destroy();
    
    // Log activity
    log_error("Account deleted", ['user_id' => $user_id]);
    
    json_response([
        'success' => true, 
        'message' => 'Tài khoản đã được xóa thành công'
    ], HttpStatus::OK);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    log_error("Error deleting account: " . $e->getMessage(), ['user_id' => $user_id]);
    
    json_response([
        'success' => false, 
        'message' => 'Lỗi khi xóa tài khoản: ' . $e->getMessage()
    ], HttpStatus::INTERNAL_ERROR);
}
?>