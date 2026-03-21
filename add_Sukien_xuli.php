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
$ten_su_kien       = sanitize_input(trim($_POST['ten_su_kien'] ?? ''));
$mo_ta             = sanitize_input(trim($_POST['mo_ta'] ?? ''));
$noi_dung_chi_tiet = sanitize_input(trim($_POST['noi_dung_chi_tiet'] ?? ''));
$dia_diem          = sanitize_input(trim($_POST['dia_diem'] ?? ''));
$tg_bat_dau        = $_POST['tg_bat_dau'] ?? '';
$tg_ket_thuc       = $_POST['tg_ket_thuc'] ?? '';
$so_luong          = (int)($_POST['so_luong'] ?? 0);
$han_dang_ky       = $_POST['han_dang_ky'] ?? '';

// GÁN TRẠNG THÁI MẶC ĐỊNH SỚM NHẤT (tránh undefined)
$trang_thai = 'dang_dien_ra';

/**
 * Chuẩn hóa datetime-local về định dạng MySQL (Y-m-d H:i:s)
 * Hỗ trợ cả input có dấu "/" hoặc "T"
 */
function format_datetime_local($dt_raw) {
    if (empty($dt_raw)) {
        return null;
    }
    // Thay T bằng space nếu là giá trị từ input datetime-local
    $dt = str_replace('T', ' ', trim($dt_raw));
    // Phải có phần giờ/phút; nếu thiếu thì coi như không hợp lệ (tránh ghi chuỗi như "2025")
    if (strpos($dt, ':') === false) {
        return null;
    }
    // Một số browser/datepicker trả về dd/mm/yyyy => đảo lại nếu bắt gặp "/"
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

$tg_bat_dau  = format_datetime_local($tg_bat_dau);
$tg_ket_thuc = format_datetime_local($tg_ket_thuc);
$han_dang_ky = format_datetime_local($han_dang_ky);

// === 4. Validate dữ liệu ===
$errors = [];

if (empty($ten_su_kien))                     $errors[] = "Tên sự kiện không được để trống.";
if (empty($mo_ta))                           $errors[] = "Mô tả không được để trống.";
if (empty($noi_dung_chi_tiet))               $errors[] = "Nội dung chi tiết không được để trống.";
if (empty($dia_diem))                        $errors[] = "Địa điểm không được để trống.";
if (empty($tg_bat_dau) || empty($tg_ket_thuc)) $errors[] = "Vui lòng chọn đầy đủ thời gian.";
if (empty($han_dang_ky))                      $errors[] = "Vui lòng chọn hạn đăng ký.";
if ($so_luong < 1)                            $errors[] = "Số lượng tối đa phải ≥ 1.";

// Chỉ so sánh khi đã parse được
if ($tg_bat_dau && $tg_ket_thuc) {
    if (strtotime($tg_bat_dau) >= strtotime($tg_ket_thuc)) {
        $errors[] = "Thời gian kết thúc phải sau thời gian bắt đầu.";
    }
}
if ($han_dang_ky && $tg_bat_dau) {
    if (strtotime($han_dang_ky) >= strtotime($tg_bat_dau)) {
        $errors[] = "Hạn đăng ký phải trước ngày diễn ra sự kiện.";
    }
}

if (!empty($errors)) {
    redirect("add_Su_kien.php?id=$club_id", "• " . implode("<br>• ", $errors), 'error');
}

// === 5. Upload ảnh bìa ===
$anh_bia_id = null;
// Báo lỗi rõ nếu ảnh vượt giới hạn PHP
if (isset($_FILES['anhbia']) && ($_FILES['anhbia']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['anhbia']['error'] === UPLOAD_ERR_FORM_SIZE)) {
    $upload_max = ini_get('upload_max_filesize');
    redirect("add_Su_kien.php?id=$club_id", "Ảnh vượt dung lượng cho phép (tối đa $upload_max).", 'error');
}
if (!isset($_FILES['anhbia']) || $_FILES['anhbia']['error'] !== UPLOAD_ERR_OK) {
    redirect("add_Su_kien.php?id=$club_id", 'Vui lòng tải lên ảnh bìa!', 'error');
}

$upload_dir = __DIR__ . "/anh_bia_sk/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$upload_result = upload_file($_FILES['anhbia'], $upload_dir, 'anhbia_');

if (!$upload_result['success']) {
    redirect("add_Su_kien.php?id=$club_id", implode(', ', $upload_result['errors']), 'error');
}

// Lưu vào media_library và lấy ID
$filePathRelative = str_replace(APP_ROOT . '/', '', $upload_result['path']);
$stmtMedia = $conn->prepare("INSERT INTO media_library (file_path, uploader_id) VALUES (?, ?)");
$stmtMedia->bind_param("si", $filePathRelative, $user_id);
if ($stmtMedia->execute()) {
    $anh_bia_id = $conn->insert_id;
} else {
    // Nếu lưu media_library thất bại, xóa file đã upload
    if (isset($upload_result['path']) && file_exists($upload_result['path'])) {
        delete_file($upload_result['path']);
    }
    redirect("add_Su_kien.php?id=$club_id", 'Lỗi lưu ảnh vào hệ thống: ' . $stmtMedia->error, 'error');
}
$stmtMedia->close();

// === 6. INSERT vào DB – ĐÃ SỬA HOÀN HẢO ===
$sql = "INSERT INTO events (
            club_id, ten_su_kien, mo_ta, noi_dung_chi_tiet, anh_bia_id,
            dia_diem, thoi_gian_bat_dau, thoi_gian_ket_thuc,
            so_luong_toi_da, han_dang_ky, trang_thai, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    // Xóa file đã upload và record trong media_library nếu có
    if (isset($upload_result['path']) && file_exists($upload_result['path'])) {
        delete_file($upload_result['path']);
    }
    if ($anh_bia_id !== null) {
        $conn->query("DELETE FROM media_library WHERE id = $anh_bia_id");
    }
    log_error("Error preparing statement: " . $conn->error, ['club_id' => $club_id, 'user_id' => $user_id]);
    redirect("add_Su_kien.php?id=$club_id", 'Lỗi hệ thống: ' . $conn->error, 'error');
}

$stmt->bind_param(
    "isssississii",
    $club_id,           // i - integer (1)
    $ten_su_kien,       // s - string (2)
    $mo_ta,             // s - string (3)
    $noi_dung_chi_tiet, // s - string (4)
    $anh_bia_id,        // i - integer (5)
    $dia_diem,          // s - string (6)
    $tg_bat_dau,        // s - string (7)
    $tg_ket_thuc,       // s - string (8)
    $so_luong,          // i - integer (9)
    $han_dang_ky,       // s - string (10)
    $trang_thai,        // s - string (11)
    $user_id            // i - integer (12)
);


if ($stmt->execute()) {
    redirect("Dashboard.php?id=$club_id", 'Tạo sự kiện thành công!', 'success');
} else {
    // Xóa file đã upload và record trong media_library nếu có
    if (isset($upload_result['path']) && file_exists($upload_result['path'])) {
        delete_file($upload_result['path']);
    }
    if ($anh_bia_id !== null) {
        $conn->query("DELETE FROM media_library WHERE id = $anh_bia_id");
    }
    log_error("Error creating event: " . $stmt->error, ['club_id' => $club_id, 'user_id' => $user_id]);
    redirect("add_Su_kien.php?id=$club_id", 'Lỗi tạo sự kiện: ' . $stmt->error, 'error');
}

$stmt->close();
?>