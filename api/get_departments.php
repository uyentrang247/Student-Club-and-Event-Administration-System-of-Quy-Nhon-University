<?php
session_start();
require_once __DIR__ . '/../assets/database/connect.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

// Lấy club_id từ GET
$club_id = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;

// Nếu không có club_id, lấy từ user hiện tại (giả sử user chỉ quản lý 1 CLB)
if (!$club_id) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM clubs WHERE leader_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $club_id = $row['id'];
    }
    $stmt->close();
}

if (!$club_id) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy CLB']);
    exit;
}

// Lấy danh sách phòng ban từ bảng departments
$stmt = $conn->prepare("SELECT id, name FROM departments WHERE club_id = ? ORDER BY created_at ASC, id ASC");
$stmt->bind_param("i", $club_id);
$stmt->execute();
$result = $stmt->get_result();
$departments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'success' => true,
    'departments' => $departments
]);
?>

