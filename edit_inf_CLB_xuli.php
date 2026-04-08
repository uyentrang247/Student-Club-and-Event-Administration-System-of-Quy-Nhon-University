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

// Lấy dữ liệu form
$name           = sanitize_input(trim($_POST['name'] ?? ''));
$category       = trim($_POST['category'] ?? '');
$description    = sanitize_input(trim($_POST['description'] ?? ''));
$founded_date   = sanitize_input(trim($_POST['founded_date'] ?? ''));
$contact_email  = sanitize_input(trim($_POST['contact_email'] ?? ''));
$contact_phone  = sanitize_input(trim($_POST['contact_phone'] ?? ''));
$contact_website = sanitize_input(trim($_POST['contact_website'] ?? ''));

// Tự động đếm số lượng thành viên thực tế
$count_members_sql = "SELECT COUNT(*) as total FROM members WHERE club_id = ? AND status = 'active'";
$count_stmt = $conn->prepare($count_members_sql);
$count_stmt->bind_param("i", $club_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_members = 0;
if ($count_result->num_rows > 0) {
    $count_data = $count_result->fetch_assoc();
    $total_members = (int)$count_data['total'];
}
$count_stmt->close();


if (empty($category)) {
    redirect("edit_inf_CLB.php?id=$club_id", 'Vui lòng chọn lĩnh vực hoạt động!', 'error');
}

// Kiểm tra quyền
$sqlCheck = $conn->prepare("
    SELECT c.id, c.leader_id, logo.path AS logo_path, logo.id AS logo_id,
           banner.path AS banner_path, banner.id AS banner_id
    FROM clubs c
    LEFT JOIN pages p ON p.club_id = c.id
    LEFT JOIN media logo ON p.logo_id = logo.id
    LEFT JOIN media banner ON p.banner_id = banner.id
    WHERE c.id=? AND c.leader_id=?
");
$sqlCheck->bind_param("ii", $club_id, $user_id);
$sqlCheck->execute();
$resultCheck = $sqlCheck->get_result();

if ($resultCheck->num_rows == 0) {
    redirect('myclub.php', 'Bạn không có quyền chỉnh sửa CLB này!', 'error');
}

$oldData = $resultCheck->fetch_assoc();
$oldLogoId   = $oldData['logo_id'] ?? null;
$oldBannerId = $oldData['banner_id'] ?? null;
$oldLogoPath = $oldData['logo_path'] ?? '';
$oldBannerPath = $oldData['banner_path'] ?? '';

// Upload logo mới
$newLogoId = $oldLogoId;
if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $upload_result = upload_file($_FILES['logo'], CLUB_LOGO_DIR, 'clb_');
    if ($upload_result['success']) {
       
    $filePathRelative = str_replace(APP_ROOT . '/', '', $upload_result['path']);
        $stmtMedia = $conn->prepare("INSERT INTO media (path, uploader_id) VALUES (?, ?)");
        $stmtMedia->bind_param("si", $filePathRelative, $user_id);
        if ($stmtMedia->execute()) {
            $newLogoId = $conn->insert_id;
            // Xóa file logo cũ
            if (!empty($oldLogoPath) && file_exists($oldLogoPath)) {
                delete_file($oldLogoPath);
            }
        }
        $stmtMedia->close();
    }
}

// Upload banner mới
$newBannerId = $oldBannerId;
if (!empty($_FILES['banner']['name']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
    $upload_result = upload_file($_FILES['banner'], CLUB_BANNER_DIR, 'clb_banner_');
    if ($upload_result['success']) {
        $filePathRelative = str_replace(APP_ROOT . '/', '', $upload_result['path']);
        $stmtMedia = $conn->prepare("INSERT INTO media (path, uploader_id) VALUES (?, ?)");
        $stmtMedia->bind_param("si", $filePathRelative, $user_id);
        if ($stmtMedia->execute()) {
            $newBannerId = $conn->insert_id;
            // Xóa file banner cũ
            if (!empty($oldBannerPath) && file_exists($oldBannerPath)) {
                delete_file($oldBannerPath);
            }
        }
        $stmtMedia->close();
    }
}

// Cập nhật clubs
$founded_date_sql = !empty($founded_date) ? $founded_date : null;
$stmt = $conn->prepare("UPDATE clubs SET name=?, description=?, category=?, total_members=?, founded_date=? WHERE id=? AND leader_id=?");
$stmt->bind_param("sssissi", $name, $description, $category, $total_members, $founded_date_sql, $club_id, $user_id);
$ok_club = $stmt->execute();


$stmt->close();

// Cập nhật hoặc insert vào bảng pages
$checkPage = $conn->prepare("SELECT id FROM pages WHERE club_id = ?");
$checkPage->bind_param("i", $club_id);
$checkPage->execute();
$pageExists = $checkPage->get_result()->num_rows > 0;
$checkPage->close();

if ($pageExists) {
    // Update existing
    $updateFields = [];
    $params = [];
    $types = '';
    
    if ($newLogoId !== null) {
        $updateFields[] = "logo_id = ?";
        $params[] = $newLogoId;
        $types .= 'i';
    }
    if ($newBannerId !== null) {
        $updateFields[] = "banner_id = ?";
        $params[] = $newBannerId;
        $types .= 'i';
    }
    
    if (!empty($updateFields)) {
        $params[] = $club_id;
        $types .= 'i';
        $sqlPage = "UPDATE pages SET " . implode(", ", $updateFields) . " WHERE club_id = ?";
        $stmtPage = $conn->prepare($sqlPage);
        $stmtPage->bind_param($types, ...$params);
        $stmtPage->execute();
        $stmtPage->close();
    }
} else {
    // Insert new
    $stmtPage = $conn->prepare("INSERT INTO pages (club_id, logo_id, banner_id, is_public) VALUES (?, ?, ?, 1)");
    $stmtPage->bind_param("iii", $club_id, $newLogoId, $newBannerId);
    $stmtPage->execute();
    $stmtPage->close();
}

// Cập nhật contacts
$checkContact = $conn->prepare("SELECT id FROM contacts WHERE club_id = ?");
$checkContact->bind_param("i", $club_id);
$checkContact->execute();
$contactExists = $checkContact->get_result()->num_rows > 0;
$checkContact->close();

if ($contactExists) {
    $stmtContact = $conn->prepare("UPDATE contacts SET email=?, phone=?, website=? WHERE club_id=?");
    $stmtContact->bind_param("sssi", $contact_email, $contact_phone, $contact_website, $club_id);
    $stmtContact->execute();
    $stmtContact->close();
} else {
    $stmtContact = $conn->prepare("INSERT INTO contacts (club_id, email, phone, website) VALUES (?, ?, ?, ?)");
    $stmtContact->bind_param("isss", $club_id, $contact_email, $contact_phone, $contact_website);
    $stmtContact->execute();
    $stmtContact->close();
}

if ($ok_club) {
    redirect("edit_inf_CLB.php?id=$club_id", 'Lưu thông tin thành công!', 'success');
} else {
    
redirect("edit_inf_CLB.php?id=$club_id", 'Lỗi cập nhật', 'error');
}
?>