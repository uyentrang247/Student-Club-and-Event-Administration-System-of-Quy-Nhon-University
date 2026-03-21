<?php
session_start();
require_once __DIR__ . '/../assets/database/connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;

if (!$member_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

// Lấy thông tin member bao gồm phong_ban_id
$sql = "SELECT id, club_id, user_id, phong_ban_id, trang_thai 
        FROM club_members 
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thành viên']);
    $stmt->close();
    exit;
}

$member = $result->fetch_assoc();
$stmt->close();

echo json_encode([
    'success' => true,
    'member' => $member
]);
?>

