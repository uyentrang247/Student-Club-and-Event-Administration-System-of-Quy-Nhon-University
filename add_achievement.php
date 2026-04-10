<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
require_once('assets/database/connect.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: myclub.php");
    exit();
}

$club_id = $_POST['club_id'] ?? 0;
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$achievement_date = $_POST['achievement_date'] ?? date('Y-m-d');

if (!$club_id || empty($title)) {
    $_SESSION['error'] = "Vui lòng nhập tiêu đề thành tựu";
    header("Location: Dashboard.php?id=" . $club_id);
    exit();
}

// Kiểm tra quyền
if (!can_manage_club($conn, $_SESSION['user_id'], $club_id)) {
    $_SESSION['error'] = "Bạn không có quyền thêm thành tựu";
    header("Location: myclub.php");
    exit();
}

// Tạo mô tả đầy đủ
$full_desc = '🏆 ' . $title;
if (!empty($description)) {
    $full_desc .= ': ' . $description;
}

// Thêm vào database
$sql = "INSERT INTO activities (club_id, type, description, created_at) VALUES (?, 'achievement', ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $club_id, $full_desc, $achievement_date);

if ($stmt->execute()) {
    $_SESSION['success'] = "Đã thêm thành tựu thành công!";
} else {
    $_SESSION['error'] = "Có lỗi xảy ra: " . $conn->error;
}
$stmt->close();

header("Location: Dashboard.php?id=" . $club_id);
exit();
?>