<?php
session_start();
require_once('assets/database/connect.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    echo "<script>alert('Vui lòng đăng nhập!'); window.location.href='login.php';</script>";
    exit;
}

// Nhận dữ liệu từ form
$club_id = isset($_POST['club_id']) ? (int)$_POST['club_id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Kiểm tra dữ liệu rỗng
if ($club_id <= 0 || $name === '' || $description === '') {
    echo "<script>alert('Vui lòng điền đầy đủ thông tin!'); window.history.back();</script>";
    exit;
}

// Kiểm tra trùng tên phòng ban trong CLB
$check = $conn->prepare("SELECT id FROM departments WHERE club_id = ? AND name = ?");
$check->bind_param("is", $club_id, $name);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    echo "<script>alert('Tên phòng ban này đã tồn tại trong CLB!'); window.history.back();</script>";
    exit;
}
$check->close();

// Thêm phòng ban mới
$stmt = $conn->prepare("INSERT INTO departments (club_id, name, description, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iss", $club_id, $name, $description);
$result = $stmt->execute();
$dept_id = $conn->insert_id;
$stmt->close();

// Thông báo kết quả
if ($result && $dept_id > 0) {
    echo "<script>alert('Tạo phòng ban thành công!'); window.location.href='taopb.php?id=$club_id';</script>";
} else {
    echo "<script>alert('Có lỗi xảy ra! Vui lòng thử lại.'); window.history.back();</script>";
}
?>
