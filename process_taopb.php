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
$ten_pb = isset($_POST['ten_phong_ban']) ? trim($_POST['ten_phong_ban']) : '';
$chuc_nang = isset($_POST['chuc_nang_nhiem_vu']) ? trim($_POST['chuc_nang_nhiem_vu']) : '';

// Kiểm tra dữ liệu rỗng
if ($club_id <= 0 || $ten_pb === '' || $chuc_nang === '') {
    echo "<script>alert('Vui lòng điền đầy đủ thông tin!'); window.history.back();</script>";
    exit;
}

// Kiểm tra trùng tên phòng ban trong CLB
$check = $conn->prepare("SELECT id FROM phong_ban WHERE club_id = ? AND ten_phong_ban = ?");
$check->bind_param("is", $club_id, $ten_pb);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    echo "<script>alert('Tên phòng ban này đã tồn tại trong CLB!'); window.history.back();</script>";
    exit;
}
$check->close();

// Thêm phòng ban mới
$stmt = $conn->prepare("INSERT INTO phong_ban (club_id, ten_phong_ban, chuc_nang_nhiem_vu, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iss", $club_id, $ten_pb, $chuc_nang);
$result = $stmt->execute();
$pb_id = $conn->insert_id;
$stmt->close();

// Thông báo kết quả
if ($result && $pb_id > 0) {
    echo "<script>alert('Tạo phòng ban thành công!'); window.location.href='taopb.php?id=$club_id';</script>";
} else {
    echo "<script>alert('Có lỗi xảy ra! Vui lòng thử lại.'); window.history.back();</script>";
}
?>
