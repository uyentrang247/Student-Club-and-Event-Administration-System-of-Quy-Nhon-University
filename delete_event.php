<?php
session_start();
header('Content-Type: application/json');

require_once 'assets/database/connect.php';
require_once 'includes/functions.php';

$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
if ($event_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID sự kiện không hợp lệ.']);
    exit;
}

// Lấy thông tin sự kiện và chủ nhiệm CLB
$sql = "SELECT e.id, e.club_id, c.chu_nhiem_id 
        FROM events e 
        LEFT JOIN clubs c ON e.club_id = c.id 
        WHERE e.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$res = $stmt->get_result();
$event = $res->fetch_assoc();
$stmt->close();

if (!$event) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện.']);
    exit;
}

// Kiểm tra quyền: admin hệ thống hoặc chủ nhiệm CLB
$user_id = $_SESSION['user_id'] ?? 0;
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_owner = ($event['chu_nhiem_id'] ?? 0) == $user_id;

if (!$is_admin && !$is_owner) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa sự kiện này.']);
    exit;
}

// Xóa đăng ký trước
$del_reg = $conn->prepare("DELETE FROM event_registrations WHERE event_id = ?");
$del_reg->bind_param("i", $event_id);
$del_reg->execute();
$del_reg->close();

// Xóa sự kiện
$del_evt = $conn->prepare("DELETE FROM events WHERE id = ?");
$del_evt->bind_param("i", $event_id);
if ($del_evt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Đã xóa sự kiện thành công.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể xóa sự kiện.']);
}
$del_evt->close();

$conn->close();

