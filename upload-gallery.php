<?php
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// NOW start session
session_start();

require_once __DIR__ . '/assets/database/connect.php';

// Kiểm tra đăng nhập
require_login();

$user_id = $_SESSION['user_id'];
$club_id = 0;
if (isset($_POST['club_id']) && is_numeric($_POST['club_id'])) {
    $club_id = (int)$_POST['club_id'];
} else {
    $club_id = get_club_id();
}

if ($club_id <= 0) {
    redirect('myclub.php', 'Không tìm thấy câu lạc bộ!', 'error');
}

// Kiểm tra quyền quản lý CLB (dùng chuẩn vai trò)
if (!can_manage_club($conn, $user_id, $club_id)) {
    redirect("club-detail.php?id=$club_id", 'Chỉ ban quản lý CLB mới có quyền upload ảnh!', 'error');
}

// Kiểm tra bảng gallery và media đã tồn tại
$check_gallery = $conn->query("SHOW TABLES LIKE 'gallery'");
$check_media   = $conn->query("SHOW TABLES LIKE 'media'");
if (!$check_gallery || $check_gallery->num_rows == 0 || !$check_media || $check_media->num_rows == 0) {
    redirect("club-gallery.php?id=$club_id&mode=manage", 'Thiếu bảng gallery hoặc media, vui lòng kiểm tra database.', 'error');
}

$title = sanitize_input($_POST['title'] ?? '');
$description = sanitize_input($_POST['description'] ?? '');

// Kiểm tra dung lượng POST vượt quá giới hạn php.ini
function ini_size_to_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $num = (int)$val;
    switch($last) {
        case 'g': $num *= 1024;
        case 'm': $num *= 1024;
        case 'k': $num *= 1024;
    }
    return $num;
}
$post_max   = ini_size_to_bytes(ini_get('post_max_size'));
$upload_max = ini_size_to_bytes(ini_get('upload_max_filesize'));
$content_len = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;

error_log("[upload-gallery] post_max_size=" . ini_get('post_max_size') . " (" . $post_max . " bytes); upload_max_filesize=" . ini_get('upload_max_filesize') . " (" . $upload_max . " bytes); CONTENT_LENGTH=" . $content_len);

if ($post_max > 0 && $content_len > $post_max) {
    redirect("club-gallery.php?id=$club_id&mode=manage", 'Dung lượng gửi lên vượt quá giới hạn post_max_size. Hãy chọn ảnh nhỏ hơn hoặc tăng post_max_size.', 'error');
}
$uploaded_count = 0;
$errors = [];


if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
    redirect("club-gallery.php?id=$club_id&mode=manage", 'Không có file nào được chọn!', 'error');
}

// Xử lý upload nhiều ảnh
if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $total_files = count($_FILES['images']['name']);
    
    
    $max_per_file = $upload_max > 0 ? $upload_max : (20 * 1024 * 1024);
    
    
    $upload_dir = __DIR__ . '/assets/img/gallery/';
    $relative_dir = 'assets/img/gallery/';
    
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    for ($i = 0; $i < $total_files; $i++) {
        if ($_FILES['images']['error'][$i] == UPLOAD_ERR_OK) {
            $filename = $_FILES['images']['name'][$i];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                
            if ($_FILES['images']['size'][$i] > $max_per_file) {
                    $errors[] = "File quá lớn: " . $filename . " (tối đa " . round($max_per_file / (1024*1024), 1) . "MB)";
                    continue;
                }
                
                
                $new_filename = 'gallery_' . $club_id . '_' . time() . '_' . $i . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $full_path = $upload_dir . $new_filename;
                $relative_path = $relative_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $full_path)) {
                    
                if (file_exists($full_path) && filesize($full_path) > 0) {
                        // Lưu file vào media và gallery
                        $stmtMedia = $conn->prepare("INSERT INTO media (path, uploader_id) VALUES (?, ?)");
                        if ($stmtMedia) {
                            $stmtMedia->bind_param("si", $relative_path, $user_id);
                            if ($stmtMedia->execute()) {
                                $media_id = $conn->insert_id;
                                $stmtMedia->close();

                                $sql = "INSERT INTO gallery (club_id, media_id, title, description, uploaded_by) 
                                        VALUES (?, ?, ?, ?, ?)";
                                $stmt = $conn->prepare($sql);
                                if ($stmt) {
                                    $stmt->bind_param("iissi", $club_id, $media_id, $title, $description, $user_id);
                                    
                                    if ($stmt->execute()) {
                                        $uploaded_count++;
                                        error_log("Gallery image uploaded successfully: " . $relative_path);
                                    } else {
                                        $errors[] = "Lỗi database: " . $stmt->error;
                                       
                                        @unlink($full_path);
                                    }
                                    $stmt->close();
                                } else {
                                    $errors[] = "Lỗi prepare SQL (gallery): " . $conn->error;
                                    @unlink($full_path);
                                }
                            } else {
                                $errors[] = "Lỗi insert media: " . $stmtMedia->error;
                                $stmtMedia->close();
                                @unlink($full_path);
                            }
                        } else {
                            $errors[] = "Lỗi prepare SQL (media): " . $conn->error;
                            @unlink($full_path);
                        }
                    } else {
                        $errors[] = "File không tồn tại hoặc rỗng sau khi upload: " . $filename;
                        if (file_exists($full_path)) {
                            @unlink($full_path);
                        }
                    }
                } else {
                    $errors[] = "Không thể di chuyển file: " . $filename . " (Error: " . $_FILES['images']['error'][$i] . ")";
                    error_log("Upload failed for file: " . $filename . ", Error code: " . $_FILES['images']['error'][$i]);
                }
            } else {
                $errors[] = "File không hợp lệ: " . $filename . " (chỉ chấp nhận JPG, PNG, GIF, WebP)";
            }
        } elseif ($_FILES['images']['error'][$i] != UPLOAD_ERR_NO_FILE) {
            $errors[] = "Lỗi upload file " . ($i+1) . ": " . $_FILES['images']['error'][$i];
            error_log("Upload error for file " . ($i+1) . ": " . $_FILES['images']['error'][$i]);
        }
    }
}
if ($uploaded_count > 0) {
    $msg = "Đã tải lên $uploaded_count ảnh thành công!";
    if (!empty($errors)) {
        $msg .= " (Có " . count($errors) . " lỗi: " . implode(", ", array_slice($errors, 0, 3)) . ")";
    }
    redirect("club-gallery.php?id=$club_id&mode=manage", $msg, 'success');
} else {
    if (!empty($errors)) {
        redirect("club-gallery.php?id=$club_id&mode=manage", "Không thể tải ảnh lên: " . implode(", ", array_slice($errors, 0, 5)), 'error');
    } else {
        redirect("club-gallery.php?id=$club_id&mode=manage", 'Không thể tải ảnh lên. Vui lòng thử lại!', 'error');
    }
}
?>



