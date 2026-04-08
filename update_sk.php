<?php
session_start();
require_once __DIR__ . "/assets/database/connect.php";

$conn->set_charset("utf8mb4");

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để chỉnh sửa sự kiện";
    header("Location: login.php");
    exit;
}

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Phương thức request không hợp lệ!";
    header("Location: myclub.php");
    exit;
}

// Nhận dữ liệu
$event_id = (int)$_POST['event_id'];
$name = trim($_POST['name'] ?? '');
$short_desc = trim($_POST['short_desc'] ?? '');
$full_desc = trim($_POST['full_desc'] ?? '');
$location = trim($_POST['location'] ?? '');
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$max_participants = (int)($_POST['max_participants'] ?? 0);
$reg_deadline = $_POST['reg_deadline'] ?? '';
$status = $_POST['status'] ?? 'upcoming';

// Validate dữ liệu bắt buộc
$errors = [];
if (empty($name)) $errors[] = "Tên sự kiện không được để trống";
if (empty($short_desc)) $errors[] = "Mô tả không được để trống";
if (empty($full_desc)) $errors[] = "Nội dung chi tiết không được để trống";
if (empty($location)) $errors[] = "Địa điểm không được để trống";
if (empty($start_time) || empty($end_time)) $errors[] = "Vui lòng chọn đầy đủ thời gian";
if ($max_participants < 1) $errors[] = "Số lượng tối đa phải ≥ 1";

if (!empty($errors)) {
    $_SESSION['error'] = "• " . implode("<br>• ", $errors);
    header("Location: edit_sk.php?id=$event_id");
    exit;
}

// Format datetime locally
function format_datetime_local($dt) {
    return str_replace("T", " ", $dt) . ":00";
}

$start_time = format_datetime_local($start_time);
$end_time = format_datetime_local($end_time);
$reg_deadline = $reg_deadline ? format_datetime_local($reg_deadline) : null;

// Kiểm tra logic thời gian
if (strtotime($end_time) <= strtotime($start_time)) {
    $_SESSION['error'] = "Thời gian kết thúc phải sau thời gian bắt đầu";
    header("Location: edit_sk.php?id=$event_id");
    exit;
}

if ($reg_deadline && strtotime($reg_deadline) >= strtotime($start_time)) {
    $_SESSION['error'] = "Hạn đăng ký phải trước thời gian bắt đầu sự kiện";
    header("Location: edit_sk.php?id=$event_id");
    exit;
}

// Kiểm tra quyền chỉnh sửa và lấy club_id
$check_sql = "SELECT created_by, club_id FROM events WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $event_id);
$check_stmt->execute();
$event_info = $check_stmt->get_result()->fetch_assoc();

if (!$event_info) {
    $_SESSION['error'] = "Sự kiện không tồn tại";
    header("Location: list_su_kien.php");
    exit;
}

$club_id = $event_info['club_id'];
$user_id = $_SESSION['user_id'];

// Kiểm tra quyền chỉnh sửa (người tạo hoặc quản lý CLB)
$check_role_sql = "SELECT role FROM members WHERE club_id = ? AND user_id = ? AND status = 'active'";
$role_stmt = $conn->prepare($check_role_sql);
$role_stmt->bind_param("ii", $club_id, $user_id);
$role_stmt->execute();
$role_result = $role_stmt->get_result();
$user_role = $role_result->num_rows > 0 ? $role_result->fetch_assoc()['role'] : '';
$role_stmt->close();

$can_edit = ($event_info['created_by'] == $user_id) || in_array($user_role, ['leader', 'vice_leader']);
if (!$can_edit) {
    $_SESSION['error'] = "Bạn không có quyền chỉnh sửa sự kiện này";
    header("Location: list_su_kien.php?id=" . $club_id);
    exit;
}

// Xử lý upload ảnh mới
$cover = null;
if (isset($_FILES['cover_new']) && $_FILES['cover_new']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['cover_new']['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        $_SESSION['error'] = "Chỉ chấp nhận file: jpg, jpeg, png, gif, webp";
        header("Location: edit_sk.php?id=$event_id");
        exit;
    }
    
    if ($_FILES['cover_new']['size'] > 5 * 1024 * 1024) {
        $_SESSION['error'] = "Ảnh bìa không được quá 5MB!";
        header("Location: edit_sk.php?id=$event_id");
        exit;
    }
    
    $upload_dir = __DIR__ . "/anh_bia_sk/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    
    $file_name = "cover_" . time() . "_" . rand(1000, 9999) . "." . $ext;
    $target_path = "anh_bia_sk/" . $file_name;
    $full_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['cover_new']['tmp_name'], $full_path)) {
        $cover = $target_path;
        
        // Lấy ảnh cũ và xóa
        $old_cover_sql = "SELECT cover_id FROM events WHERE id = ?";
        $old_cover_stmt = $conn->prepare($old_cover_sql);
        $old_cover_stmt->bind_param("i", $event_id);
        $old_cover_stmt->execute();
        $old_cover_result = $old_cover_stmt->get_result();
        if ($old_cover_result->num_rows > 0) {
            $old_cover_id = $old_cover_result->fetch_assoc()['cover_id'];
            if ($old_cover_id) {
                // Lấy path từ media để xóa file
                $get_path_sql = "SELECT path FROM media WHERE id = ?";
                $get_path_stmt = $conn->prepare($get_path_sql);
                $get_path_stmt->bind_param("i", $old_cover_id);
                $get_path_stmt->execute();
                $path_result = $get_path_stmt->get_result();
                if ($path_result->num_rows > 0) {
                    $old_path = $path_result->fetch_assoc()['path'];
                    if ($old_path && file_exists(__DIR__ . '/' . $old_path)) {
                        @unlink(__DIR__ . '/' . $old_path);
                    }
                }
                $get_path_stmt->close();
                // Xóa record trong media
                $delete_media = $conn->prepare("DELETE FROM media WHERE id = ?");
                $delete_media->bind_param("i", $old_cover_id);
                $delete_media->execute();
                $delete_media->close();
            }
        }
        $old_cover_stmt->close();
        
        // Lưu ảnh mới vào media
        $relative_path = $target_path;
        $stmt_media = $conn->prepare("INSERT INTO media (path, uploader_id) VALUES (?, ?)");
        $stmt_media->bind_param("si", $relative_path, $user_id);
        if ($stmt_media->execute()) {
            $cover_id = $conn->insert_id;
            $stmt_media->close();
            
            // Cập nhật cover_id vào events
            $update_cover_sql = "UPDATE events SET cover_id = ? WHERE id = ?";
            $update_cover_stmt = $conn->prepare($update_cover_sql);
            $update_cover_stmt->bind_param("ii", $cover_id, $event_id);
            $update_cover_stmt->execute();
            $update_cover_stmt->close();
        } else {
            $stmt_media->close();
            @unlink($full_path);
        }
    } else {
        $_SESSION['error'] = "Lỗi upload ảnh!";
        header("Location: edit_sk.php?id=$event_id");
        exit;
    }
}

// Cập nhật database
$sql = "UPDATE events SET 
            name = ?, 
            short_desc = ?, 
            full_desc = ?, 
            location = ?,
            start_time = ?, 
            end_time = ?, 
            max_participants = ?,
            reg_deadline = ?, 
            status = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssissi", 
    $name, 
    $short_desc, 
    $full_desc, 
    $location,
    $start_time, 
    $end_time, 
    $max_participants, 
    $reg_deadline, 
    $status, 
    $event_id
);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    $_SESSION['success'] = "✅ Đã chỉnh sửa sự kiện thành công!";
} else {
    $_SESSION['error'] = "❌ Lỗi cập nhật: " . ($conn->error ?? 'Không xác định');
}

$conn->close();

// QUAY VỀ LIST_SU_KIEN.PHP VỚI CLUB_ID
header("Location: list_su_kien.php?id=" . $club_id);
exit;
?>