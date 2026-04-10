<?php
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// NOW start session
session_start();

require_once __DIR__ . '/assets/database/connect.php';

// Kiểm tra đăng nhập
require_login();

// Kiểm tra method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('myclub.php', 'Phương thức request không hợp lệ!', 'error');
}

$user_id = $_SESSION['user_id'];

// Lấy club_id
$club_id = get_club_id();

if ($club_id <= 0) {
    log_error("ERROR: club_id is invalid", ['POST' => $_POST['club_id'] ?? 'NULL', 'GET' => $_GET['id'] ?? 'NULL']);
    redirect('myclub.php', 'Không tìm thấy câu lạc bộ! Vui lòng chọn câu lạc bộ từ danh sách.', 'error');
}

// Lấy dữ liệu từ form
$slogan = $_POST['slogan'] ?? '';
$about = $_POST['about'] ?? '';
$primary_color = $_POST['primary_color'] ?? '#667eea';
$facebook = $_POST['facebook'] ?? '';
$instagram = $_POST['instagram'] ?? '';
$twitter = $_POST['twitter'] ?? '';
$website = $_POST['website'] ?? '';
// Đọc giá trị is_public từ hidden input
$is_public = isset($_POST['is_public']) ? (int)$_POST['is_public'] : 1;

// Xử lý upload ảnh bìa vào media
$banner_id = null;
$old_banner_id = null;

if (isset($_FILES['banner']) && $_FILES['banner']['error'] == UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $_FILES['banner']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        // Lấy banner_id cũ để xóa sau
        $check_old = $conn->prepare("SELECT banner_id FROM pages WHERE club_id = ?");
        $check_old->bind_param("i", $club_id);
        $check_old->execute();
        $old_result = $check_old->get_result();
        if ($old_result->num_rows > 0) {
            $old_data = $old_result->fetch_assoc();
            $old_banner_id = $old_data['banner_id'] ?? null;
        }
        $check_old->close();
        
        $new_filename = 'banner_' . $club_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $upload_dir = __DIR__ . '/assets/img/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $upload_path = $upload_dir . $new_filename;
        $relative_path = 'assets/img/' . $new_filename;
        
        // Validate file size (max 10MB)
        if ($_FILES['banner']['size'] > 10 * 1024 * 1024) {
            error_log("Banner upload failed: File too large");
            redirect("tao_trang_dai_dien.php?id=$club_id", 'Ảnh bìa quá lớn. Tối đa 10MB.', 'error');
        }
        
        if (move_uploaded_file($_FILES['banner']['tmp_name'], $upload_path)) {
            if (file_exists($upload_path) && filesize($upload_path) > 0) {
                // Lưu vào media
                $stmtMedia = $conn->prepare("INSERT INTO media (path, uploader_id) VALUES (?, ?)");
                if ($stmtMedia) {
                    $stmtMedia->bind_param("si", $relative_path, $user_id);
                    if ($stmtMedia->execute()) {
                        $banner_id = $conn->insert_id;
                        error_log("Banner uploaded to media: ID=$banner_id, Path=$relative_path");
                        
                        // Xóa banner cũ từ media và file system nếu có
                        if ($old_banner_id) {
                            $get_old_banner = $conn->prepare("SELECT path FROM media WHERE id = ?");
                            $get_old_banner->bind_param("i", $old_banner_id);
                            $get_old_banner->execute();
                            $old_banner_result = $get_old_banner->get_result();
                            if ($old_banner_result->num_rows > 0) {
                                $old_banner_data = $old_banner_result->fetch_assoc();
                                $old_banner_path = $old_banner_data['path'] ?? null;
                                if ($old_banner_path) {
                                    $old_banner_full = (strpos($old_banner_path, '/') === 0 || strpos($old_banner_path, 'C:') === 0) 
                                        ? $old_banner_path 
                                        : __DIR__ . '/' . $old_banner_path;
                                    if (file_exists($old_banner_full) && $old_banner_full != $upload_path) {
                                        @unlink($old_banner_full);
                                        error_log("Old banner file deleted: " . $old_banner_full);
                                    }
                                }
                            }
                            $get_old_banner->close();
                            
                            // Xóa record trong media
                            $delete_old = $conn->prepare("DELETE FROM media WHERE id = ?");
                            $delete_old->bind_param("i", $old_banner_id);
                            $delete_old->execute();
                            $delete_old->close();
                            error_log("Old banner deleted from media: ID=$old_banner_id");
                        }
                    } else {
                        error_log("Failed to insert banner into media: " . $stmtMedia->error);
                        @unlink($upload_path);
                    }
                    $stmtMedia->close();
                } else {
                    error_log("Failed to prepare media insert: " . $conn->error);
                    @unlink($upload_path);
                }
            } else {
                error_log("Banner upload failed: File not found or empty after move");
                if (file_exists($upload_path)) {
                    @unlink($upload_path);
                }
            }
        } else {
            error_log("Banner upload failed: move_uploaded_file failed. Error code: " . $_FILES['banner']['error']);
        }
    } else {
        error_log("Banner upload failed: Invalid file extension: " . $ext);
        redirect("tao_trang_dai_dien.php?id=$club_id", 'Định dạng file không hợp lệ. Chỉ chấp nhận: jpg, jpeg, png, gif, webp.', 'error');
    }
} elseif (isset($_FILES['banner']) && $_FILES['banner']['error'] != UPLOAD_ERR_NO_FILE) {
    error_log("Banner upload error: " . $_FILES['banner']['error']);
}

// Xử lý upload logo/avatar vào media
$logo_id = null;
$old_logo_id = null;

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['avatar']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        // Lấy logo_id cũ để xóa sau
        $check_old = $conn->prepare("SELECT logo_id FROM pages WHERE club_id = ?");
        $check_old->bind_param("i", $club_id);
        $check_old->execute();
        $old_result = $check_old->get_result();
        if ($old_result->num_rows > 0) {
            $old_data = $old_result->fetch_assoc();
            $old_logo_id = $old_data['logo_id'] ?? null;
        }
        $check_old->close();
        
        $new_filename = 'logo_' . $club_id . '_' . time() . '.' . $ext;
        $upload_dir = __DIR__ . '/assets/img/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $upload_path = $upload_dir . $new_filename;
        $relative_path = 'assets/img/' . $new_filename;
        
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
            if (file_exists($upload_path) && filesize($upload_path) > 0) {
                // Lưu vào media
                $stmtMedia = $conn->prepare("INSERT INTO media (path, uploader_id) VALUES (?, ?)");
                if ($stmtMedia) {
                    $stmtMedia->bind_param("si", $relative_path, $user_id);
                    if ($stmtMedia->execute()) {
                        $logo_id = $conn->insert_id;
                        error_log("Logo uploaded to media: ID=$logo_id, Path=$relative_path");
                        
                        // Xóa logo cũ từ media và file system nếu có
                        if ($old_logo_id) {
                            $get_old_logo = $conn->prepare("SELECT path FROM media WHERE id = ?");
                            $get_old_logo->bind_param("i", $old_logo_id);
                            $get_old_logo->execute();
                            $old_logo_result = $get_old_logo->get_result();
                            if ($old_logo_result->num_rows > 0) {
                                $old_logo_data = $old_logo_result->fetch_assoc();
                                $old_logo_path = $old_logo_data['path'] ?? null;
                                if ($old_logo_path) {
                                    $old_logo_full = (strpos($old_logo_path, '/') === 0 || strpos($old_logo_path, 'C:') === 0) 
                                        ? $old_logo_path 
                                        : __DIR__ . '/' . $old_logo_path;
                                    if (file_exists($old_logo_full) && $old_logo_full != $upload_path) {
                                        @unlink($old_logo_full);
                                        error_log("Old logo file deleted: " . $old_logo_full);
                                    }
                                }
                            }
                            $get_old_logo->close();
                            
                            // Xóa record trong media
                            $delete_old = $conn->prepare("DELETE FROM media WHERE id = ?");
                            $delete_old->bind_param("i", $old_logo_id);
                            $delete_old->execute();
                            $delete_old->close();
                            error_log("Old logo deleted from media: ID=$old_logo_id");
                        }
                    } else {
                        error_log("Failed to insert logo into media: " . $stmtMedia->error);
                        @unlink($upload_path);
                    }
                    $stmtMedia->close();
                } else {
                    error_log("Failed to prepare media insert: " . $conn->error);
                    @unlink($upload_path);
                }
            } else {
                error_log("Logo upload failed: File not found or empty after move");
                if (file_exists($upload_path)) {
                    @unlink($upload_path);
                }
            }
        } else {
            error_log("Avatar upload failed: Error code " . $_FILES['avatar']['error']);
        }
    }
}

// Cập nhật thông tin vào bảng clubs
$update_fields = [];
$params = [];
$types = '';

if ($about) {
    $update_fields[] = "description = ?";
    $params[] = $about;
    $types .= 's';
}

if ($primary_color) {
    $update_fields[] = "color = ?";
    $params[] = $primary_color;
    $types .= 's';
}

// Cập nhật bảng clubs
if (!empty($update_fields)) {
    $sql = "UPDATE clubs SET " . implode(", ", $update_fields) . " WHERE id = ?";
    $params[] = $club_id;
    $types .= 'i';
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
}

// Insert hoặc Update thông tin trang đại diện vào pages
$check_sql = "SELECT * FROM pages WHERE club_id = ?";
$check_stmt = $conn->prepare($check_sql);
if (!$check_stmt) {
    error_log("Prepare check failed: " . $conn->error);
    redirect("tao_trang_dai_dien.php?id=$club_id", 'Lỗi kiểm tra dữ liệu: ' . $conn->error, 'error');
}
$check_stmt->bind_param("i", $club_id);
$check_stmt->execute();
$existing = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

error_log("Processing pages - Club ID: $club_id, Existing: " . ($existing ? 'YES' : 'NO') . ", Banner ID: " . ($banner_id ?? 'NULL') . ", Logo ID: " . ($logo_id ?? 'NULL'));

$stmt = null;
$sql = '';

if ($existing) {
    // UPDATE
    $update_parts = [];
    $update_params = [];
    $update_types = '';
    
    $update_parts[] = "slogan = ?";
    $update_params[] = $slogan;
    $update_types .= 's';
    
    $update_parts[] = "about = ?";
    $update_params[] = $about;
    $update_types .= 's';
    
    // Update banner_id nếu có banner mới upload
    if ($banner_id !== null) {
        $update_parts[] = "banner_id = ?";
        $update_params[] = $banner_id;
        $update_types .= 'i';
        error_log("Adding banner_id to UPDATE: $banner_id");
    }
    
    // Update logo_id nếu có logo mới upload
    if ($logo_id !== null) {
        $update_parts[] = "logo_id = ?";
        $update_params[] = $logo_id;
        $update_types .= 'i';
        error_log("Adding logo_id to UPDATE: $logo_id");
    }
    
    $update_parts[] = "primary_color = ?";
    $update_params[] = $primary_color;
    $update_types .= 's';
    
    $update_parts[] = "is_public = ?";
    $update_params[] = $is_public;
    $update_types .= 'i';
    
    $update_params[] = $club_id;
    $update_types .= 'i';
    
    $sql = "UPDATE pages SET " . implode(", ", $update_parts) . " WHERE club_id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare UPDATE failed: " . $conn->error);
        redirect("tao_trang_dai_dien.php?id=$club_id", 'Lỗi chuẩn bị câu lệnh SQL UPDATE: ' . $conn->error, 'error');
    }
    
    if (!$stmt->bind_param($update_types, ...$update_params)) {
        error_log("Bind param failed: " . $stmt->error);
        $stmt->close();
        redirect("tao_trang_dai_dien.php?id=$club_id", 'Lỗi bind tham số: ' . $stmt->error, 'error');
    }
} else {
    // INSERT mới
    $sql = "INSERT INTO pages (club_id, slogan, about, banner_id, logo_id, primary_color, is_public)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare INSERT failed: " . $conn->error);
        redirect("tao_trang_dai_dien.php?id=$club_id", 'Lỗi chuẩn bị câu lệnh SQL INSERT: ' . $conn->error, 'error');
    }
    
    if (!$stmt->bind_param("issiisi", 
        $club_id, 
        $slogan, 
        $about, 
        $banner_id, 
        $logo_id, 
        $primary_color, 
        $is_public
    )) {
        error_log("Bind param failed: " . $stmt->error);
        $stmt->close();
        redirect("tao_trang_dai_dien.php?id=$club_id", 'Lỗi bind tham số: ' . $stmt->error, 'error');
    }
}

// Thực thi câu lệnh pages
if (!$stmt) {
    error_log("ERROR: Statement is null!");
    redirect("tao_trang_dai_dien.php?id=$club_id", 'Lỗi: Không thể tạo câu lệnh SQL!', 'error');
}

$execute_result = $stmt->execute();

if ($execute_result) {
    $stmt->close();
    
    // Lưu social links và website vào contacts
    $check_contact = $conn->prepare("SELECT id FROM contacts WHERE club_id = ?");
    $check_contact->bind_param("i", $club_id);
    $check_contact->execute();
    $contact_exists = $check_contact->get_result()->num_rows > 0;
    $check_contact->close();
    
    if ($contact_exists) {
        // UPDATE contacts
        $update_contact = $conn->prepare("UPDATE contacts SET facebook = ?, instagram = ?, twitter = ?, website = ? WHERE club_id = ?");
        $update_contact->bind_param("ssssi", $facebook, $instagram, $twitter, $website, $club_id);
        $update_contact->execute();
        $update_contact->close();
    } else {
        // INSERT contacts
        $insert_contact = $conn->prepare("INSERT INTO contacts (club_id, facebook, instagram, twitter, website) VALUES (?, ?, ?, ?, ?)");
        $insert_contact->bind_param("issss", $club_id, $facebook, $instagram, $twitter, $website);
        $insert_contact->execute();
        $insert_contact->close();
    }
    
    // Verify database update
    $verify_sql = "SELECT banner_id, logo_id, is_public FROM pages WHERE club_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("i", $club_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    if ($verify_row = $verify_result->fetch_assoc()) {
        error_log("Saved - banner_id: " . ($verify_row['banner_id'] ?? 'NULL') . ", logo_id: " . ($verify_row['logo_id'] ?? 'NULL') . ", is_public: " . ($verify_row['is_public'] ?? 'NULL'));
    }
    $verify_stmt->close();
    
    // Thêm timestamp để force reload và tránh cache
    redirect("tao_trang_dai_dien.php?id=$club_id&updated=" . time(), 'Đã lưu trang đại diện thành công!', 'success');
} else {
    error_log("Database execute failed: " . $stmt->error . " (Code: " . $stmt->errno . ")");
    $stmt->close();
    redirect("tao_trang_dai_dien.php?id=$club_id", 'Có lỗi xảy ra khi lưu dữ liệu: ' . $stmt->error, 'error');
}
// Don't close connection - it's managed globally
?>
