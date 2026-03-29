<?php
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// NOW start session
session_start();

require_once __DIR__ . '/assets/database/connect.php';

// Kiểm tra đăng nhập
require_login();

// Kiểm tra nếu là AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Nhận dữ liệu từ form
$club_id = isset($_POST['club_id']) ? (int)$_POST['club_id'] : get_club_id();
$user_id = $_SESSION['user_id'];
$phone = isset($_POST['phone']) ? trim(sanitize_input($_POST['phone'])) : '';
$message = isset($_POST['message']) ? trim(sanitize_input($_POST['message'])) : '';
$department_id = isset($_POST['department_id']) && $_POST['department_id'] !== '' ? (int)$_POST['department_id'] : null;

// Kiểm tra dữ liệu bắt buộc
if ($club_id <= 0) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID câu lạc bộ không hợp lệ!']);
        exit;
    }
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php', 'ID câu lạc bộ không hợp lệ!', 'error');
}

// Kiểm tra xem người dùng đã là thành viên hoặc đã gửi yêu cầu chưa
$check = $conn->prepare("SELECT id, status FROM members WHERE club_id = ? AND user_id = ?");
$check->bind_param("ii", $club_id, $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $existing = $result->fetch_assoc();
    if ($existing['status'] == 'active') {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Bạn đã là thành viên của CLB này!']);
            exit;
        }
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php', 'Bạn đã là thành viên của CLB này!', 'info');
    } else if ($existing['status'] == 'pending') {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Bạn đã gửi yêu cầu tham gia CLB này rồi. Vui lòng chờ duyệt!']);
            exit;
        }
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php', 'Bạn đã gửi yêu cầu tham gia CLB này rồi. Vui lòng chờ duyệt!', 'info');
    }
}
$check->close();

try {
    // Lưu vào join_requests nếu cần lưu số điện thoại và lời nhắn
    // Sau đó lưu vào members
    if (!empty($phone) || !empty($message)) {
        // Lấy email từ user
        $user_email_sql = "SELECT email FROM users WHERE id = ?";
        $user_email_stmt = $conn->prepare($user_email_sql);
        $user_email_stmt->bind_param("i", $user_id);
        $user_email_stmt->execute();
        $user_email_result = $user_email_stmt->get_result();
        $user_email = $user_email_result->num_rows > 0 ? $user_email_result->fetch_assoc()['email'] : '';
        $user_email_stmt->close();
        
        // Kiểm tra xem đã có join_request chưa
        $check_join_request_sql = "SELECT id FROM join_requests WHERE club_id = ? AND user_id = ?";
        $check_join_request_stmt = $conn->prepare($check_join_request_sql);
        $check_join_request_stmt->bind_param("ii", $club_id, $user_id);
        $check_join_request_stmt->execute();
        $check_join_request_result = $check_join_request_stmt->get_result();
        $check_join_request_stmt->close();
        
        if ($check_join_request_result->num_rows > 0) {
            // Update nếu đã có
            $update_join_request_sql = "UPDATE join_requests SET email = ?, phone = ?, message = ?, requested_at = CURRENT_TIMESTAMP WHERE club_id = ? AND user_id = ?";
            $update_join_request_stmt = $conn->prepare($update_join_request_sql);
            $update_join_request_stmt->bind_param("sssii", $user_email, $phone, $message, $club_id, $user_id);
            $update_join_request_stmt->execute();
            $update_join_request_stmt->close();
        } else {
            // Insert mới
            $join_request_sql = "INSERT INTO join_requests (club_id, user_id, email, phone, message, status) 
                                VALUES (?, ?, ?, ?, ?, 'pending')";
            $join_request_stmt = $conn->prepare($join_request_sql);
            $join_request_stmt->bind_param("iisss", $club_id, $user_id, $user_email, $phone, $message);
            $join_request_stmt->execute();
            $join_request_stmt->close();
        }
    }
    
    // Lưu vào members
    if ($department_id !== null) {
        $stmt = $conn->prepare("INSERT INTO members (club_id, user_id, role, status, department_id) VALUES (?, ?, 'member', 'pending', ?)");
        $stmt->bind_param("iii", $club_id, $user_id, $department_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO members (club_id, user_id, role, status) VALUES (?, ?, 'member', 'pending')");
        $stmt->bind_param("ii", $club_id, $user_id);
    }
    $result = $stmt->execute();
    $member_id = $conn->insert_id; // Lấy ID của member vừa insert
    $stmt->close();

if ($result) {
    // Lấy thông tin CLB và leader để gửi thông báo
    $club_sql = "SELECT name, leader_id FROM clubs WHERE id = ?";
    $club_stmt = $conn->prepare($club_sql);
    $club_stmt->bind_param("i", $club_id);
    $club_stmt->execute();
    $club_result = $club_stmt->get_result();
    $club_data = $club_result->fetch_assoc();
    $club_stmt->close();
    
    // Lấy thông tin người đăng ký
    $user_sql = "SELECT full_name FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_name = $user_result->num_rows > 0 ? $user_result->fetch_assoc()['full_name'] : 'Một người dùng';
    $user_stmt->close();
    
    // Gửi thông báo cho leader của CLB
    if (!empty($club_data['leader_id']) && $club_data['leader_id'] != $user_id) {
        require_once __DIR__ . '/includes/constants.php';
        
        $leader_id = $club_data['leader_id'];
        $club_name = $club_data['name'] ?? 'CLB';
        
        $notification_title = "Có yêu cầu tham gia CLB mới";
        $notification_message = $user_name . " đã gửi yêu cầu tham gia CLB \"" . htmlspecialchars($club_name) . "\"";
        // Lưu member_id vào link để có thể duyệt/từ chối trực tiếp
        $notification_link = "Dashboard.php?id=" . $club_id . "&member_id=" . $member_id . "#pending-requests";
        $notification_type = 'club_join';
        
        $insert_notification = "INSERT INTO notifications (user_id, type, title, message, link) 
                               VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt_notif = $conn->prepare($insert_notification)) {
            $stmt_notif->bind_param("issss", $leader_id, $notification_type, $notification_title, $notification_message, $notification_link);
            $stmt_notif->execute();
            $stmt_notif->close();
        }
    }
    
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Đã gửi yêu cầu tham gia CLB thành công! Vui lòng chờ duyệt.']);
        exit;
    }
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php', 'Đã gửi yêu cầu tham gia CLB thành công! Vui lòng chờ duyệt.', 'success');
} else {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra! Vui lòng thử lại.']);
        exit;
    }
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php', 'Có lỗi xảy ra! Vui lòng thử lại.', 'error');
}
} catch (Exception $e) {
    log_error("Error joining club: " . $e->getMessage(), ['club_id' => $club_id, 'user_id' => $user_id]);
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        exit;
    }
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php', 'Lỗi hệ thống: ' . $e->getMessage(), 'error');
}
?>