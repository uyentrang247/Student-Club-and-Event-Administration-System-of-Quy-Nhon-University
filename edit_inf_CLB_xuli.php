<?php
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// NOW start session
session_start();

require_once(__DIR__ . "/assets/database/connect.php");

// Kiểm tra đăng nhập
require_login();

$user_id = $_SESSION['user_id'];
$club_id = get_club_id();

if ($club_id <= 0) {
    redirect('myclub.php', 'Dữ liệu không hợp lệ!', 'error');
}

// Lấy dữ liệu form với sanitization
$ten_clb         = sanitize_input(trim($_POST['ten_clb'] ?? ''));
$linh_vuc         = trim($_POST['linh_vuc'] ?? ''); // Không dùng sanitize_input để tránh encode HTML entities
$mo_ta            = sanitize_input(trim($_POST['mo_ta'] ?? ''));
$ngay_thanh_lap   = sanitize_input(trim($_POST['ngay_thanh_lap'] ?? ''));
$contact_email    = sanitize_input(trim($_POST['contact_email'] ?? ''));
$contact_phone    = sanitize_input(trim($_POST['contact_phone'] ?? ''));
$contact_website  = sanitize_input(trim($_POST['contact_website'] ?? ''));

// Tự động đếm số lượng thành viên thực tế từ club_members (không dùng giá trị từ form)
$count_members_sql = "SELECT COUNT(*) as total FROM club_members WHERE club_id = ? AND trang_thai = 'dang_hoat_dong'";
$count_stmt = $conn->prepare($count_members_sql);
$count_stmt->bind_param("i", $club_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$so_tv = 0;
if ($count_result->num_rows > 0) {
    $count_data = $count_result->fetch_assoc();
    $so_tv = (int)$count_data['total'];
}
$count_stmt->close();

// Validate linh_vuc - đảm bảo có giá trị hợp lệ
if (empty($linh_vuc)) {
    redirect("edit_inf_CLB.php?id=$club_id", 'Vui lòng chọn lĩnh vực hoạt động!', 'error');
}

// Check quyền + lấy thông tin logo hiện tại
$sqlCheck = $conn->prepare("
    SELECT c.id, c.chu_nhiem_id, logo.file_path AS logo_path, logo.id AS logo_id
    FROM clubs c
    LEFT JOIN club_pages cp ON cp.club_id = c.id
    LEFT JOIN media_library logo ON cp.logo_id = logo.id
    WHERE c.id=? AND c.chu_nhiem_id=?
");
$sqlCheck->bind_param("ii", $club_id, $user_id);
$sqlCheck->execute();
$resultCheck = $sqlCheck->get_result();

if ($resultCheck->num_rows == 0) {
    redirect('myclub.php', 'Bạn không có quyền chỉnh sửa CLB này!', 'error');
}

$oldData = $resultCheck->fetch_assoc();
$oldLogoPath = $oldData['logo_path'] ?? '';
$oldLogoId   = $oldData['logo_id'] ?? null;

// Upload logo mới (lưu media_library + cập nhật club_pages.logo_id)
$newLogoId = $oldLogoId;
if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $upload_result = upload_file($_FILES['logo'], CLUB_LOGO_DIR, 'clb_');
    if ($upload_result['success']) {
        // Lưu vào media_library
        $filePathRelative = str_replace(APP_ROOT . '/', '', $upload_result['path']);
        $stmtMedia = $conn->prepare("INSERT INTO media_library (file_path, uploader_id) VALUES (?, ?)");
        $stmtMedia->bind_param("si", $filePathRelative, $user_id);
        if ($stmtMedia->execute()) {
            $newLogoId = $conn->insert_id;
            // Xóa file logo cũ nếu có (file_path)
            if (!empty($oldLogoPath) && file_exists($oldLogoPath)) {
                delete_file($oldLogoPath);
            }
        }
        $stmtMedia->close();
    }
}

// Cập nhật clubs (core info) - bao gồm ngày thành lập
$ngay_thanh_lap_sql = !empty($ngay_thanh_lap) ? $ngay_thanh_lap : null;
$stmt = $conn->prepare("UPDATE clubs SET ten_clb=?, mo_ta=?, linh_vuc=?, so_thanh_vien=?, ngay_thanh_lap=? WHERE id=? AND chu_nhiem_id=?");
// 7 tham số: ten_clb(s), mo_ta(s), linh_vuc(s), so_thanh_vien(i), ngay_thanh_lap(s), id(i), chu_nhiem_id(i)
$stmt->bind_param("sssissi", $ten_clb, $mo_ta, $linh_vuc, $so_tv, $ngay_thanh_lap_sql, $club_id, $user_id);
$ok_club = $stmt->execute();

// Kiểm tra lỗi nếu có
if (!$ok_club) {
    log_error("Error updating club: " . $stmt->error, [
        'club_id' => $club_id, 
        'user_id' => $user_id,
        'linh_vuc' => $linh_vuc,
        'error' => $stmt->error
    ]);
    $stmt->close();
    redirect("edit_inf_CLB.php?id=$club_id", 'Lỗi cập nhật: ' . $stmt->error, 'error');
}
$stmt->close();

// Cập nhật/insert club_pages với logo_id mới (nếu có)
if ($newLogoId !== null) {
    // Kiểm tra đã có club_pages chưa
    $checkPage = $conn->prepare("SELECT id FROM club_pages WHERE club_id = ?");
    $checkPage->bind_param("i", $club_id);
    $checkPage->execute();
    $pageResult = $checkPage->get_result();
    if ($pageResult->num_rows > 0) {
        $stmtPage = $conn->prepare("UPDATE club_pages SET logo_id=? WHERE club_id=?");
        $stmtPage->bind_param("ii", $newLogoId, $club_id);
    } else {
        $stmtPage = $conn->prepare("INSERT INTO club_pages (club_id, logo_id, is_public) VALUES (?, ?, 1)");
        $stmtPage->bind_param("ii", $club_id, $newLogoId);
    }
    $stmtPage->execute();
    $stmtPage->close();
    $checkPage->close();
}

// Upsert contact vào club_contacts
$stmtContact = $conn->prepare("SELECT id FROM club_contacts WHERE club_id = ?");
$stmtContact->bind_param("i", $club_id);
$stmtContact->execute();
$contactResult = $stmtContact->get_result();
$stmtContact->close();

if ($contactResult->num_rows > 0) {
    $stmtContactUpdate = $conn->prepare("UPDATE club_contacts SET email=?, phone=?, website=? WHERE club_id=?");
    $stmtContactUpdate->bind_param("sssi", $contact_email, $contact_phone, $contact_website, $club_id);
    $stmtContactUpdate->execute();
    $stmtContactUpdate->close();
} else {
    $stmtContactInsert = $conn->prepare("INSERT INTO club_contacts (club_id, email, phone, website) VALUES (?, ?, ?, ?)");
    $stmtContactInsert->bind_param("isss", $club_id, $contact_email, $contact_phone, $contact_website);
    $stmtContactInsert->execute();
    $stmtContactInsert->close();
}

if ($ok_club) {
    redirect("edit_inf_CLB.php?id=$club_id", 'Lưu thông tin thành công!', 'success');
} else {
    log_error("Error updating club", ['club_id' => $club_id, 'user_id' => $user_id]);
    redirect("edit_inf_CLB.php?id=$club_id", 'Lỗi cập nhật', 'error');
}
?>