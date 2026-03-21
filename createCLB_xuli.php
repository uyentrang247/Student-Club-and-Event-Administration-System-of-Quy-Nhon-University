<?php
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/constants.php';
require_once __DIR__ . '/includes/functions.php';

// NOW start session
session_start();

require_once __DIR__ . '/assets/database/connect.php';

// Kiểm tra user tồn tại
function userExists($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Kiểm tra đăng nhập
require_login();

// Verify CSRF token
if (!isset($_POST[CSRF_TOKEN_NAME]) || !verify_csrf_token($_POST[CSRF_TOKEN_NAME])) {
        redirect("createCLB.php", "Token bảo mật không hợp lệ!", "danger");
}

$chu_nhiem_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $ten_clb = sanitize_input($_POST['ten_clb']);
    $mo_ta = sanitize_input($_POST['mo_ta']);
    $linh_vuc = sanitize_input($_POST['linh_vuc']);
    $so_thanh_vien = isset($_POST['so_thanh_vien']) ? max(0, intval($_POST['so_thanh_vien'])) : 0;
    
    // Validate inputs
    if (empty($ten_clb) || empty($linh_vuc)) {
        redirect("createCLB.php", "Vui lòng điền đầy đủ thông tin bắt buộc!", "warning");
    }
    // Tên CLB không trùng (không phân biệt hoa thường)
    $stmt_check = $conn->prepare("SELECT id FROM clubs WHERE LOWER(ten_clb) = LOWER(?) LIMIT 1");
    $stmt_check->bind_param("s", $ten_clb);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $stmt_check->close();
        redirect("createCLB.php", "Tên câu lạc bộ đã tồn tại, vui lòng chọn tên khác.", "warning");
    }
    $stmt_check->close();
  
    // Upload ảnh với validation đầy đủ
    if (!isset($_FILES['logo_url']) || $_FILES['logo_url']['error'] !== UPLOAD_ERR_OK) {
        redirect("createCLB.php", "Vui lòng chọn logo cho CLB!", "warning");
    }
    
    // Validate file upload
    $upload_result = upload_file($_FILES['logo_url'], CLUB_LOGO_DIR, 'clb_');
    
    if (!$upload_result['success']) {
        redirect("createCLB.php", implode(', ', $upload_result['errors']), "danger");
    }
    
    // Get relative path for database
    $filePath = str_replace(APP_ROOT . '/', '', $upload_result['path']);

    // Xác thực user tồn tại, nếu không thì dừng lại (tránh tạo CLB mất đội trưởng)
    if (!userExists($chu_nhiem_id)) {
        redirect("createCLB.php", "Tài khoản không tồn tại, vui lòng đăng nhập lại.", "danger");
    }

    // Lưu logo vào media_library trước
    $logo_id = null;
    $stmt_media = $conn->prepare("INSERT INTO media_library (file_path, uploader_id) VALUES (?, ?)");
    $stmt_media->bind_param("si", $filePath, $chu_nhiem_id);
    if ($stmt_media->execute()) {
        $logo_id = $conn->insert_id;
    }
    $stmt_media->close();

    // Thêm CLB (bảng clubs không có logo_url/so_thanh_vien)
    $stmt = $conn->prepare("
        INSERT INTO clubs (ten_clb, mo_ta, linh_vuc, chu_nhiem_id)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param("sssi", $ten_clb, $mo_ta, $linh_vuc, $chu_nhiem_id);

    if ($stmt->execute()) {
        // Lấy ID của CLB vừa tạo
        $club_id = $conn->insert_id;

        // Tạo trang đại diện tối thiểu để lưu logo
        if ($logo_id !== null) {
            $stmt_page = $conn->prepare("
                INSERT INTO club_pages (club_id, logo_id, description, is_public)
                VALUES (?, ?, ?, 1)
            ");
            $stmt_page->bind_param("iis", $club_id, $logo_id, $mo_ta);
            $stmt_page->execute();
            $stmt_page->close();
        }
        
        // Tự động thêm đội trưởng vào bảng club_members
        $stmt_member = $conn->prepare("
            INSERT INTO club_members (club_id, user_id, vai_tro, joined_at, trang_thai)
            VALUES (?, ?, ?, NOW(), ?)
        ");
        $vai_tro = UserRole::DOI_TRUONG;
        $trang_thai = MemberStatus::DANG_HOAT_DONG;
        $stmt_member->bind_param("iiss", $club_id, $chu_nhiem_id, $vai_tro, $trang_thai);
        $stmt_member->execute();
        $stmt_member->close();
        
        // Log activity
        log_error("User created club: $ten_clb", ['user_id' => $chu_nhiem_id, 'club_id' => $club_id]);
        
        redirect("myclub.php", "Tạo câu lạc bộ thành công!", "success");
    } else {
        log_error("Error creating club: " . $stmt->error, ['user_id' => $chu_nhiem_id]);
        redirect("createCLB.php", "Lỗi khi tạo câu lạc bộ. Vui lòng thử lại!", "danger");
    }

    $stmt->close();
}
?>