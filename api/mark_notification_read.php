<?php
session_start();
require_once(__DIR__ . "/../assets/database/connect.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;

if (!$notification_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

$sql = "UPDATE notifications SET is_read = 1 
        WHERE id = ? AND user_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $notification_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

// Don't close connection - it's managed globally
