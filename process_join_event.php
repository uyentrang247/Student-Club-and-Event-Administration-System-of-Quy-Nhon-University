<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . "/assets/database/connect.php";
require_once __DIR__ . "/includes/constants.php";
require_once __DIR__ . "/includes/functions.php";

// CSRF protection for state-changing action
$csrf_token = $_POST[CSRF_TOKEN_NAME] ?? '';
if (!verify_csrf_token($csrf_token)) {
    echo json_encode([
        'success' => false,
        'message' => 'Phiên không hợp lệ, vui lòng tải lại trang.'
    ]);
    exit;
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để tham gia sự kiện!'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

// Rate limit to avoid spam
if (!check_rate_limit('join_event_' . $user_id, 5, 60)) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn thao tác quá nhanh, vui lòng thử lại sau.'
    ]);
    exit;
}

if ($event_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID sự kiện không hợp lệ!'
    ]);
    exit;
}

// Kiểm tra sự kiện có tồn tại không
$sql_check = "SELECT * FROM events WHERE id = ? FOR UPDATE";
$conn->begin_transaction();
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Sự kiện không tồn tại!'
    ]);
    exit;
}

// Kiểm tra trạng thái sự kiện
if (in_array($event['status'], ['completed', 'cancelled'], true)) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Sự kiện không còn mở để đăng ký!'
    ]);
    exit;
}

// Kiểm tra hạn đăng ký
if (!empty($event['reg_deadline']) && strtotime($event['reg_deadline']) < time()) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Đã hết hạn đăng ký sự kiện!'
    ]);
    exit;
}

// Kiểm tra đã đăng ký chưa
$sql_check_registered = "SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql_check_registered);
$stmt->bind_param("ii", $event_id, $user_id);
$stmt->execute();
$already_registered = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($already_registered) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Bạn đã đăng ký sự kiện này rồi!'
    ]);
    exit;
}

// Kiểm tra user có phải là thành viên của CLB không
$club_id = $event['club_id'];
$check_member_sql = "SELECT id FROM members WHERE club_id = ? AND user_id = ? AND status = 'active'";
$stmt = $conn->prepare($check_member_sql);
$stmt->bind_param("ii", $club_id, $user_id);
$stmt->execute();
$member_result = $stmt->get_result();
$is_member = $member_result->num_rows > 0;
$stmt->close();

if (!$is_member) {
    // Kiểm tra xem user có phải là leader không
    $check_owner_sql = "SELECT id FROM clubs WHERE id = ? AND leader_id = ?";
    $stmt = $conn->prepare($check_owner_sql);
    $stmt->bind_param("ii", $club_id, $user_id);
    $stmt->execute();
    $owner_result = $stmt->get_result();
    $is_owner = $owner_result->num_rows > 0;
    $stmt->close();
    
    if (!$is_owner) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Chỉ thành viên của CLB mới được đăng ký tham gia sự kiện này!'
        ]);
        exit;
    }
}

// Kiểm tra số lượng tối đa
$sql_count = "SELECT COUNT(*) as total FROM event_registrations WHERE event_id = ?";
$stmt = $conn->prepare($sql_count);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$count = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$max_slots = (int)($event['max_participants'] ?? 0);
if ($max_slots > 0 && $count >= $max_slots) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Sự kiện đã đủ số lượng người tham gia!'
    ]);
    exit;
}

// Đăng ký tham gia (status mặc định là 'approved' - tự động duyệt)
$sql_insert = "INSERT INTO event_registrations (event_id, user_id, status) VALUES (?, ?, 'approved')";
$stmt = $conn->prepare($sql_insert);
$stmt->bind_param("ii", $event_id, $user_id);

if ($stmt->execute()) {
    $conn->commit();
    // Lấy thông tin user đăng ký và sự kiện để tạo thông báo
    $user_sql = "SELECT full_name FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_name = $user_result->num_rows > 0 ? $user_result->fetch_assoc()['full_name'] : 'Một người dùng';
    $user_stmt->close();
    
    // Đếm số lượng đã đăng ký
    $count_registered_sql = "SELECT COUNT(*) as total FROM event_registrations WHERE event_id = ?";
    $count_stmt = $conn->prepare($count_registered_sql);
    $count_stmt->bind_param("i", $event_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_registered = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
    
    $club_id = $event['club_id'];
    
    // Lấy danh sách tất cả quản lý CLB (leader, vice_leader, head)
    $managers_sql = "SELECT DISTINCT user_id FROM (
                        SELECT leader_id as user_id FROM clubs WHERE id = ?
                        UNION
                        SELECT user_id FROM members 
                        WHERE club_id = ? AND status = 'active' 
                        AND role IN ('leader', 'vice_leader', 'head')
                        UNION
                        SELECT head_id as user_id FROM departments 
                        WHERE club_id = ? AND head_id IS NOT NULL
                    ) AS managers";
    $managers_stmt = $conn->prepare($managers_sql);
    $managers_stmt->bind_param("iii", $club_id, $club_id, $club_id);
    $managers_stmt->execute();
    $managers_result = $managers_stmt->get_result();
    
    // Gửi thông báo cho tất cả quản lý (trừ người đăng ký)
    while ($manager = $managers_result->fetch_assoc()) {
        $manager_id = $manager['user_id'];
        if ($manager_id != $user_id) {
            $notification_title = "Có người đăng ký tham gia sự kiện";
            $notification_message = $user_name . " đã đăng ký tham gia sự kiện \"" . htmlspecialchars($event['name']) . "\" (Đã có " . $total_registered . " người đăng ký)";
            $notification_link = "chi_tiet_su_kien.php?id=" . $event_id;
            $notification_type = 'event';
            
            $insert_notification = "INSERT INTO notifications (user_id, type, title, message, link) 
                                   VALUES (?, ?, ?, ?, ?)";
            
            if ($stmt_notif = $conn->prepare($insert_notification)) {
                $stmt_notif->bind_param("issss", $manager_id, $notification_type, $notification_title, $notification_message, $notification_link);
                $stmt_notif->execute();
                $stmt_notif->close();
            }
        }
    }
    $managers_stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đăng ký tham gia sự kiện thành công!'
    ]);
} else {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
