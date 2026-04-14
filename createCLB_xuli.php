<?php
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/constants.php';
require_once __DIR__ . '/includes/functions.php';

// NOW start session
session_start();

require_once __DIR__ . '/assets/database/connect.php';

// Hàm tạo slug từ chuỗi
function createSlug($string) {
    if (empty($string)) {
        return 'club';
    }
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug);
    $slug = trim($slug, '-');
    if (empty($slug)) {
        $slug = 'club';
    }
    return $slug;
}

// Hàm kiểm tra slug duy nhất
function getUniqueSlug($conn, $slug, $excludeId = 0) {
    $originalSlug = $slug;
    $counter = 1;
    
    $stmt = $conn->prepare("SELECT id FROM clubs WHERE slug = ? AND id != ?");
    $stmt->bind_param("si", $slug, $excludeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($result->num_rows > 0) {
        $slug = $originalSlug . '-' . $counter;
        $stmt->bind_param("si", $slug, $excludeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $counter++;
    }
    
    $stmt->close();
    return $slug;
}

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

$leader_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $category = sanitize_input($_POST['category']);
    $total_members = isset($_POST['total_members']) ? max(0, intval($_POST['total_members'])) : 0;
    
    // Validate inputs
    if (empty($name) || empty($category)) {
        redirect("createCLB.php", "Vui lòng điền đầy đủ thông tin bắt buộc!", "warning");
    }
    
    // Tên CLB không trùng (không phân biệt hoa thường)
    $stmt_check = $conn->prepare("SELECT id FROM clubs WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $stmt_check->bind_param("s", $name);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $stmt_check->close();
        redirect("createCLB.php", "Tên câu lạc bộ đã tồn tại, vui lòng chọn tên khác.", "warning");
    }
    $stmt_check->close();
    
    // ========== TẠO SLUG TỪ TÊN CLB (ĐẶT TRONG POST) ==========
    $slug = createSlug($name);
    $slug = getUniqueSlug($conn, $slug);
    // ========================================================
  
    // Upload ảnh với validation đầy đủ
    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        redirect("createCLB.php", "Vui lòng chọn logo cho CLB!", "warning");
    }
    
    // Validate file upload
    $upload_result = upload_file($_FILES['logo'], CLUB_LOGO_DIR, 'clb_');
    
    if (!$upload_result['success']) {
        redirect("createCLB.php", implode(', ', $upload_result['errors']), "danger");
    }
    
    // Get relative path for database
    $filePath = str_replace(APP_ROOT . '/', '', $upload_result['path']);

    // Xác thực user tồn tại, nếu không thì dừng lại (tránh tạo CLB mất đội trưởng)
    if (!userExists($leader_id)) {
        redirect("createCLB.php", "Tài khoản không tồn tại, vui lòng đăng nhập lại.", "danger");
    }

    // Lưu logo vào media trước
    $logo_id = null;
    $stmt_media = $conn->prepare("INSERT INTO media (path, uploader_id) VALUES (?, ?)");
    $stmt_media->bind_param("si", $filePath, $leader_id);
    if ($stmt_media->execute()) {
        $logo_id = $conn->insert_id;
    }
    $stmt_media->close();

    // ========== THÊM CLB - ĐÃ BAO GỒM SLUG ==========
    $stmt = $conn->prepare("
        INSERT INTO clubs (name, slug, description, category, leader_id, total_members)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("ssssii", $name, $slug, $description, $category, $leader_id, $total_members);
    // =================================================

    if ($stmt->execute()) {
        // Lấy ID của CLB vừa tạo
        $club_id = $conn->insert_id;

        // Tạo trang đại diện tối thiểu để lưu logo
        if ($logo_id !== null) {
            $stmt_page = $conn->prepare("
                INSERT INTO pages (club_id, logo_id, about, is_public)
                VALUES (?, ?, ?, 1)
            ");
            $stmt_page->bind_param("iis", $club_id, $logo_id, $description);
            $stmt_page->execute();
            $stmt_page->close();
        }
        
        // Tự động thêm đội trưởng vào bảng members
        $stmt_member = $conn->prepare("
            INSERT INTO members (club_id, user_id, role, joined_at, status)
            VALUES (?, ?, ?, NOW(), ?)
        ");
        $role = UserRole::LEADER;
        $status = MemberStatus::ACTIVE;
        $stmt_member->bind_param("iiss", $club_id, $leader_id, $role, $status);
        $stmt_member->execute();
        $stmt_member->close();
        
        // Log activity
        log_error("User created club: $name", ['user_id' => $leader_id, 'club_id' => $club_id]);
        
        redirect("myclub.php", "Tạo câu lạc bộ thành công!", "success");
    } else {
        log_error("Error creating club: " . $stmt->error, ['user_id' => $leader_id]);
        redirect("createCLB.php", "Lỗi khi tạo câu lạc bộ. Vui lòng thử lại!", "danger");
    }

    $stmt->close();
}
?>