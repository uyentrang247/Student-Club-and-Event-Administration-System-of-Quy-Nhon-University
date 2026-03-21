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
$so_dien_thoai = isset($_POST['so_dien_thoai']) ? trim(sanitize_input($_POST['so_dien_thoai'])) : '';
$loi_nhan = isset($_POST['loi_nhan']) ? trim(sanitize_input($_POST['loi_nhan'])) : '';
$phong_ban_id = isset($_POST['phong_ban_id']) && $_POST['phong_ban_id'] !== '' ? (int)$_POST['phong_ban_id'] : null;

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
$check = $conn->prepare("SELECT id, trang_thai FROM club_members WHERE club_id = ? AND user_id = ?");
$check->bind_param("ii", $club_id, $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $existing = $result->fetch_assoc();
    if ($existing['trang_thai'] == 'dang_hoat_dong') {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Bạn đã là thành viên của CLB này!']);
            exit;
        }
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php', 'Bạn đã là thành viên của CLB này!', 'info');
    } else if ($existing['trang_thai'] == 'cho_duyet') {
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
    // Sau đó lưu vào club_members
    if (!empty($so_dien_thoai) || !empty($loi_nhan)) {
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
            $update_join_request_sql = "UPDATE join_requests SET email = ?, so_dien_thoai = ?, loi_nhan = ?, requested_at = CURRENT_TIMESTAMP WHERE club_id = ? AND user_id = ?";
            $update_join_request_stmt = $conn->prepare($update_join_request_sql);
            $update_join_request_stmt->bind_param("sssii", $user_email, $so_dien_thoai, $loi_nhan, $club_id, $user_id);
            $update_join_request_stmt->execute();
            $update_join_request_stmt->close();
        } else {
            // Insert mới
            $join_request_sql = "INSERT INTO join_requests (club_id, user_id, email, so_dien_thoai, loi_nhan, trang_thai) 
                                VALUES (?, ?, ?, ?, ?, 'cho_duyet')";
            $join_request_stmt = $conn->prepare($join_request_sql);
            $join_request_stmt->bind_param("iisss", $club_id, $user_id, $user_email, $so_dien_thoai, $loi_nhan);
            $join_request_stmt->execute();
            $join_request_stmt->close();
        }
    }
    
    // Lưu vào club_members (không có so_dien_thoai và loi_nhan)
    if ($phong_ban_id !== null) {
        $stmt = $conn->prepare("INSERT INTO club_members (club_id, user_id, vai_tro, trang_thai, phong_ban_id) VALUES (?, ?, 'thanh_vien', 'cho_duyet', ?)");
        $stmt->bind_param("iii", $club_id, $user_id, $phong_ban_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO club_members (club_id, user_id, vai_tro, trang_thai) VALUES (?, ?, 'thanh_vien', 'cho_duyet')");
        $stmt->bind_param("ii", $club_id, $user_id);
    }
    $result = $stmt->execute();
    $member_id = $conn->insert_id; // Lấy ID của member vừa insert
    $stmt->close();

if ($result) {
    // Lấy thông tin CLB và đội trưởng để gửi thông báo
    $club_sql = "SELECT ten_clb, chu_nhiem_id FROM clubs WHERE id = ?";
    $club_stmt = $conn->prepare($club_sql);
    $club_stmt->bind_param("i", $club_id);
    $club_stmt->execute();
    $club_result = $club_stmt->get_result();
    $club_data = $club_result->fetch_assoc();
    $club_stmt->close();
    
    // Lấy thông tin người đăng ký
    $user_sql = "SELECT ho_ten FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_name = $user_result->num_rows > 0 ? $user_result->fetch_assoc()['ho_ten'] : 'Một người dùng';
    $user_stmt->close();
    
    // Gửi thông báo cho đội trưởng CLB
    if (!empty($club_data['chu_nhiem_id']) && $club_data['chu_nhiem_id'] != $user_id) {
        require_once __DIR__ . '/includes/constants.php';
        
        $chu_nhiem_id = $club_data['chu_nhiem_id'];
        $club_name = $club_data['ten_clb'] ?? 'CLB';
        
        $notification_title = "Có yêu cầu tham gia CLB mới";
        $notification_message = $user_name . " đã gửi yêu cầu tham gia CLB \"" . htmlspecialchars($club_name) . "\"";
        // Lưu member_id vào link để có thể duyệt/từ chối trực tiếp
        $notification_link = "Dashboard.php?id=" . $club_id . "&member_id=" . $member_id . "#pending-requests";
        $notification_type = NotificationType::CLUB_JOIN;
        
        $insert_notification = "INSERT INTO notifications (user_id, type, title, message, link) 
                               VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt_notif = $conn->prepare($insert_notification)) {
            $stmt_notif->bind_param("issss", $chu_nhiem_id, $notification_type, $notification_title, $notification_message, $notification_link);
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