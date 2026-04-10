<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
require_once('assets/database/connect.php');

$achievement_id = $_GET['id'] ?? 0;
$club_id = $_GET['club_id'] ?? 0;

if (!$achievement_id || !$club_id) {
    $_SESSION['error'] = "Không tìm thấy thành tựu";
    header("Location: Dashboard.php?id=" . $club_id);
    exit();
}

// Kiểm tra quyền
if (!can_manage_club($conn, $_SESSION['user_id'], $club_id)) {
    $_SESSION['error'] = "Bạn không có quyền xóa thành tựu";
    header("Location: myclub.php");
    exit();
}

// Xóa thành tựu
$sql = "DELETE FROM activities WHERE id = ? AND type = 'achievement'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $achievement_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Đã xóa thành tựu!";
} else {
    $_SESSION['error'] = "Có lỗi xảy ra";
}
$stmt->close();

header("Location: Dashboard.php?id=" . $club_id);
exit();
?>