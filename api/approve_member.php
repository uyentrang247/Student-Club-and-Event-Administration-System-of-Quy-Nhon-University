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
if (!check_rate_limit('approve_member_' . $_SESSION['user_id'], 10, 300)) {
    json_response(['success' => false, 'message' => 'Bạn thao tác quá nhanh, vui lòng thử lại sau'], HttpStatus::BAD_REQUEST);
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Phương thức không hợp lệ'], HttpStatus::BAD_REQUEST);
}

$member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
$department_id = isset($_POST['department_id']) && $_POST['department_id'] !== '' ? (int)$_POST['department_id'] : null;
$current_user_id = $_SESSION['user_id'];

if (!$member_id) {
    json_response(['success' => false, 'message' => 'Thiếu thông tin'], HttpStatus::BAD_REQUEST);
}

// Phòng ban là bắt buộc
if ($department_id === null || $department_id <= 0) {
    json_response(['success' => false, 'message' => 'Vui lòng chọn phòng ban'], HttpStatus::BAD_REQUEST);
}

// Lấy thông tin yêu cầu từ bảng members
// Database mới: bảng members, cột status, department_id
$get_request = "SELECT m.club_id, m.user_id, c.name as club_name 
                FROM members m
                JOIN clubs c ON m.club_id = c.id
                WHERE m.id = ? AND m.status = ?";

$stmt = $conn->prepare($get_request);
$status_pending = 'pending';  // Thay vì MemberStatus::CHO_DUYET
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

// Kiểm tra quyền (phải là đội trưởng hoặc đội phó)
if (!can_manage_club($conn, $current_user_id, $club_id)) {
    json_response(['success' => false, 'message' => 'Bạn không có quyền duyệt thành viên'], HttpStatus::FORBIDDEN);
}

// Kiểm tra phòng ban có thuộc CLB này không
$check_dept = $conn->prepare("SELECT id FROM departments WHERE id = ? AND club_id = ?");
$check_dept->bind_param("ii", $department_id, $club_id);
$check_dept->execute();
$dept_result = $check_dept->get_result();
$check_dept->close();

if ($dept_result->num_rows === 0) {
    json_response(['success' => false, 'message' => 'Phòng ban không hợp lệ'], HttpStatus::BAD_REQUEST);
}

// Lấy tên phòng ban được phân công
$get_dept_sql = "SELECT name FROM departments WHERE id = ? AND club_id = ?";
$stmt_dept = $conn->prepare($get_dept_sql);
$stmt_dept->bind_param("ii", $department_id, $club_id);
$stmt_dept->execute();
$dept_result_name = $stmt_dept->get_result();
$dept_name = '';
if ($dept_result_name->num_rows > 0) {
    $dept_data = $dept_result_name->fetch_assoc();
    $dept_name = $dept_data['name'];
}
$stmt_dept->close();

// Lấy tên người duyệt
$get_approver_sql = "SELECT full_name FROM users WHERE id = ?";
$stmt_approver = $conn->prepare($get_approver_sql);
$stmt_approver->bind_param("i", $current_user_id);
$stmt_approver->execute();
$approver_result = $stmt_approver->get_result();
$approver_name = 'Quản trị viên';
if ($approver_result->num_rows > 0) {
    $approver_data = $approver_result->fetch_assoc();
    $approver_name = $approver_data['full_name'];
}
$stmt_approver->close();

// Duyệt yêu cầu - chuyển trạng thái thành "active" và gán phòng ban
$approve_sql = "UPDATE members SET status = ?, department_id = ? WHERE id = ?";
$stmt_approve = $conn->prepare($approve_sql);
$status_active = 'active';  // Thay vì MemberStatus::DANG_HOAT_DONG
$stmt_approve->bind_param("sii", $status_active, $department_id, $member_id);

if ($stmt_approve->execute()) {
    // Cập nhật số lượng thành viên
    $update_count = "UPDATE clubs SET total_members = (
                        SELECT COUNT(*) FROM members 
                        WHERE club_id = ? AND status = ?
                    ) WHERE id = ?";
    
    $stmt_update = $conn->prepare($update_count);
    $stmt_update->bind_param("isi", $club_id, $status_active, $club_id);
    $stmt_update->execute();
    $stmt_update->close();
    
    // Tạo thông báo chi tiết cho người được duyệt
    $notification_title = "✅ Yêu cầu tham gia CLB được chấp nhận";
    
    // Tạo message chi tiết
    $notification_message = "Chúc mừng! Yêu cầu tham gia CLB \"{$club_name}\" của bạn đã được {$approver_name} chấp nhận.";
    if ($dept_name) {
        $notification_message .= " Bạn đã được phân công vào phòng ban: {$dept_name}.";
    }
    $notification_message .= " Chào mừng bạn đến với CLB!";
    
    $notification_link = "club-detail.php?id={$club_id}";
    $notification_type = 'club_join';  // Thay vì NotificationType::CLUB_JOIN
    
    $insert_notification = "INSERT INTO notifications (user_id, type, title, message, link) 
                           VALUES (?, ?, ?, ?, ?)";
    
    $stmt_notif = $conn->prepare($insert_notification);
    $stmt_notif->bind_param("issss", $user_id, $notification_type, $notification_title, $notification_message, $notification_link);
    $stmt_notif->execute();
    $stmt_notif->close();
    
    // Log activity
    log_error("Member request approved", [
        'club_id' => $club_id,
        'member_id' => $member_id,
        'approved_by' => $current_user_id
    ]);
    
    json_response(['success' => true, 'message' => 'Đã duyệt thành công'], HttpStatus::OK);
} else {
    log_error("Error approving member: " . $stmt_approve->error, ['member_id' => $member_id]);
    json_response(['success' => false, 'message' => 'Lỗi khi duyệt'], HttpStatus::INTERNAL_ERROR);
}

$stmt_approve->close();
?>