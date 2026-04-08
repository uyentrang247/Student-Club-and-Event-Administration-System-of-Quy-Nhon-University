<?php
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// NOW start session
session_start();

require_once __DIR__ . "/assets/database/connect.php";

$conn->set_charset("utf8mb4");

// DEBUG: Kiểm tra dữ liệu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('myclub.php', 'Phương thức request không hợp lệ!', 'error');
}

// Nếu POST rỗng và không có file => thường do ảnh vượt quá post_max_size/upload_max_filesize
if (empty($_POST) && empty($_FILES)) {
    $post_max = ini_get('post_max_size');
    $upload_max = ini_get('upload_max_filesize');
    redirect('add_Su_kien.php', "Dữ liệu gửi lên bị trống. Có thể file ảnh vượt giới hạn (post_max_size=$post_max, upload_max_filesize=$upload_max). Vui lòng chọn ảnh nhẹ hơn.", 'error');
}

// === 1. Kiểm tra đăng nhập ===
require_login();

$user_id = $_SESSION['user_id'];

// === 2. Kiểm tra club_id ===
$club_id = get_club_id();
if ($club_id <= 0) {
    redirect('myclub.php', 'Câu lạc bộ không hợp lệ. Vui lòng thử lại.', 'error');
}

// === 3. Nhận dữ liệu với sanitization ===
$name           = sanitize_input(trim($_POST['name'] ?? ''));
$short_desc     = sanitize_input(trim($_POST['short_desc'] ?? ''));
$full_desc      = sanitize_input(trim($_POST['full_desc'] ?? ''));
$location       = sanitize_input(trim($_POST['location'] ?? ''));
$start_time     = $_POST['start_time'] ?? '';
$end_time       = $_POST['end_time'] ?? '';
$max_participants = (int)($_POST['max_participants'] ?? 0);
$reg_deadline   = $_POST['reg_deadline'] ?? '';

// GÁN TRẠNG THÁI MẶC ĐỊNH
$status = 'upcoming';

/**
 * Chuẩn hóa datetime-local về định dạng MySQL (Y-m-d H:i:s)
 */
function format_datetime_local($dt_raw) {
    if (empty($dt_raw)) {
        return null;
    }
    $dt = str_replace('T', ' ', trim($dt_raw));
    if (strpos($dt, ':') === false) {
        return null;
    }
    if (strpos($dt, '/') !== false) {
        $ts = DateTime::createFromFormat('d/m/Y h:i A', $dt) ?: DateTime::createFromFormat('d/m/Y H:i', $dt);
        if ($ts instanceof DateTime) {
            return $ts->format('Y-m-d H:i:s');
        }
    }
    $timestamp = strtotime($dt);
    if ($timestamp === false) {
        return null;
    }
    return date('Y-m-d H:i:s', $timestamp);
}

$start_time   = format_datetime_local($start_time);
$end_time     = format_datetime_local($end_time);
$reg_deadline = format_datetime_local($reg_deadline);

// === 4. Validate dữ liệu ===
$errors = [];

if (empty($name))                     $errors[] = "Tên sự kiện không được để trống.";
if (empty($short_desc))               $errors[] = "Mô tả không được để trống.";
if (empty($full_desc))                $errors[] = "Nội dung chi tiết không được để trống.";
if (empty($location))                 $errors[] = "Địa điểm không được để trống.";
if (empty($start_time) || empty($end_time)) $errors[] = "Vui lòng chọn đầy đủ thời gian.";
if (empty($reg_deadline))             $errors[] = "Vui lòng chọn hạn đăng ký.";
if ($max_participants < 1)            $errors[] = "Số lượng tối đa phải ≥ 1.";

if ($start_time && $end_time) {
    if (strtotime($start_time) >= strtotime($end_time)) {
        $errors[] = "Thời gian kết thúc phải sau thời gian bắt đầu.";
    }
}
if ($reg_deadline && $start_time) {
    if (strtotime($reg_deadline) >= strtotime($start_time)) {
        $errors[] = "Hạn đăng ký phải trước ngày diễn ra sự kiện.";
    }
}

if (!empty($errors)) {
    redirect("add_Su_kien.php?id=$club_id", "• " . implode("<br>• ", $errors), 'error');
}

// === 5. Upload ảnh bìa ===
$cover_id = null;
if (isset($_FILES['cover']) && ($_FILES['cover']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['cover']['error'] === UPLOAD_ERR_FORM_SIZE)) {
    $upload_max = ini_get('upload_max_filesize');
    redirect("add_Su_kien.php?id=$club_id", "Ảnh vượt dung lượng cho phép (tối đa $upload_max).", 'error');
}
if (!isset($_FILES['cover']) || $_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
    redirect("add_Su_kien.php?id=$club_id", 'Vui lòng tải lên ảnh bìa!', 'error');
}

$upload_dir = __DIR__ . "/anh_bia_sk/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$upload_result = upload_file($_FILES['cover'], $upload_dir, 'cover_');

if (!$upload_result['success']) {
    redirect("add_Su_kien.php?id=$club_id", implode(', ', $upload_result['errors']), 'error');
}

// Lưu vào media và lấy ID
$filePathRelative = str_replace(APP_ROOT . '/', '', $upload_result['path']);
$stmtMedia = $conn->prepare("INSERT INTO media (path, uploader_id) VALUES (?, ?)");
$stmtMedia->bind_param("si", $filePathRelative, $user_id);
if ($stmtMedia->execute()) {
    $cover_id = $conn->insert_id;
} else {
    // Nếu lưu media thất bại, xóa file đã upload
    if (isset($upload_result['path']) && file_exists($upload_result['path'])) {
        delete_file($upload_result['path']);
    }
    redirect("add_Su_kien.php?id=$club_id", 'Lỗi lưu ảnh vào hệ thống: ' . $stmtMedia->error, 'error');
}
$stmtMedia->close();

// === 6. INSERT vào DB với cấu trúc mới ===
$sql = "INSERT INTO events (
            club_id, name, short_desc, full_desc, cover_id,
            location, start_time, end_time,
            max_participants, reg_deadline, status, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    // Xóa file đã upload và record trong media nếu có
    if (isset($upload_result['path']) && file_exists($upload_result['path'])) {
        delete_file($upload_result['path']);
    }
    if ($cover_id !== null) {
        $conn->query("DELETE FROM media WHERE id = $cover_id");
    }
    log_error("Error preparing statement: " . $conn->error, ['club_id' => $club_id, 'user_id' => $user_id]);
    redirect("add_Su_kien.php?id=$club_id", 'Lỗi hệ thống: ' . $conn->error, 'error');
}

$stmt->bind_param(
    "issssssssisi",
    $club_id,           // i
    $name,              // s
    $short_desc,        // s
    $full_desc,         // s
    $cover_id,          // i
    $location,          // s
    $start_time,        // s
    $end_time,          // s
    $max_participants,  // i
    $reg_deadline,      // s
    $status,            // s
    $user_id            // i
);


if ($stmt->execute()) {
    redirect("Dashboard.php?id=$club_id", 'Tạo sự kiện thành công!', 'success');
} else {
    // Xóa file đã upload và record trong media nếu có
    if (isset($upload_result['path']) && file_exists($upload_result['path'])) {
        delete_file($upload_result['path']);
    }
    if ($cover_id !== null) {
        $conn->query("DELETE FROM media WHERE id = $cover_id");
    }
    log_error("Error creating event: " . $stmt->error, ['club_id' => $club_id, 'user_id' => $user_id]);
    redirect("add_Su_kien.php?id=$club_id", 'Lỗi tạo sự kiện: ' . $stmt->error, 'error');
}

$stmt->close();
?>