<?php
session_start();
require_once(__DIR__ . "/../assets/database/connect.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Không cần thay đổi vì bảng notifications giữ nguyên cấu trúc
$sql = "SELECT COUNT(*) as count FROM notifications 
        WHERE user_id = ? AND is_read = 0";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode(['count' => (int)$row['count']]);
    $stmt->close();
} else {
    echo json_encode(['count' => 0]);
}

// Don't close connection - it's managed globally
?>