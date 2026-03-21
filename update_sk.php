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
$ten_su_kien = trim($_POST['ten_su_kien'] ?? '');
$mo_ta = trim($_POST['mo_ta'] ?? '');
$noi_dung_chi_tiet = trim($_POST['noi_dung_chi_tiet'] ?? '');
$dia_diem = trim($_POST['dia_diem'] ?? '');
$tg_bat_dau = $_POST['tg_bat_dau'] ?? '';
$tg_ket_thuc = $_POST['tg_ket_thuc'] ?? '';
$so_luong = (int)($_POST['so_luong'] ?? 0);
$han_dang_ky = $_POST['han_dang_ky'] ?? '';
$trang_thai = $_POST['trang_thai'] ?? 'sap_dien_ra';

// Validate dữ liệu bắt buộc
$errors = [];
if (empty($ten_su_kien)) $errors[] = "Tên sự kiện không được để trống";
if (empty($mo_ta)) $errors[] = "Mô tả không được để trống";
if (empty($noi_dung_chi_tiet)) $errors[] = "Nội dung chi tiết không được để trống";
if (empty($dia_diem)) $errors[] = "Địa điểm không được để trống";
if (empty($tg_bat_dau) || empty($tg_ket_thuc)) $errors[] = "Vui lòng chọn đầy đủ thời gian";
if ($so_luong < 1) $errors[] = "Số lượng tối đa phải ≥ 1";

if (!empty($errors)) {
    $_SESSION['error'] = "• " . implode("<br>• ", $errors);
    header("Location: edit_sk.php?id=$event_id");
    exit;
}

// Format datetime locally to avoid clashing with shared helpers
function format_datetime_local($dt) {
    return str_replace("T", " ", $dt) . ":00";
}

$tg_bat_dau = format_datetime_local($tg_bat_dau);
$tg_ket_thuc = format_datetime_local($tg_ket_thuc);
$han_dang_ky = $han_dang_ky ? format_datetime_local($han_dang_ky) : null;

// Kiểm tra logic thời gian
if (strtotime($tg_ket_thuc) <= strtotime($tg_bat_dau)) {
    $_SESSION['error'] = "Thời gian kết thúc phải sau thời gian bắt đầu";
    header("Location: edit_sk.php?id=$event_id");
    exit;
}

if ($han_dang_ky && strtotime($han_dang_ky) >= strtotime($tg_bat_dau)) {
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
// Kiểm tra quyền chỉnh sửa (người tạo hoặc quản lý CLB)
$user_id = $_SESSION['user_id'];
$check_role_sql = "SELECT vai_tro FROM club_members WHERE club_id = ? AND user_id = ?";
$role_stmt = $conn->prepare($check_role_sql);
$role_stmt->bind_param("ii", $club_id, $user_id);
$role_stmt->execute();
$role_result = $role_stmt->get_result();
$user_role = $role_result->num_rows > 0 ? $role_result->fetch_assoc()['vai_tro'] : '';
$role_stmt->close();

$can_edit = ($event_info['created_by'] == $user_id) || in_array($user_role, ['Đội trưởng', 'Đội phó']);
if (!$can_edit) {
    $_SESSION['error'] = "Bạn không có quyền chỉnh sửa sự kiện này";
    header("Location: list_su_kien.php?id=" . $club_id);
    exit;
}

// Xử lý upload ảnh mới
$anh_bia = null;
if (isset($_FILES['anh_bia_moi']) && $_FILES['anh_bia_moi']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['anh_bia_moi']['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        $_SESSION['error'] = "Chỉ chấp nhận file: jpg, jpeg, png, gif, webp";
        header("Location: edit_sk.php?id=$event_id");
        exit;
    }
    
    if ($_FILES['anh_bia_moi']['size'] > 5 * 1024 * 1024) {
        $_SESSION['error'] = "Ảnh bìa không được quá 5MB!";
        header("Location: edit_sk.php?id=$event_id");
        exit;
    }
    
    $upload_dir = __DIR__ . "/anh_bia_sk/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    
    $file_name = "anhbia_" . time() . "_" . rand(1000, 9999) . "." . $ext;
    $target_path = "anh_bia_sk/" . $file_name;
    $full_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['anh_bia_moi']['tmp_name'], $full_path)) {
        $anh_bia = $target_path;
        
        // Xóa ảnh cũ nếu có
        $old_image_sql = "SELECT anh_bia FROM events WHERE id = ?";
        $old_image_stmt = $conn->prepare($old_image_sql);
        $old_image_stmt->bind_param("i", $event_id);
        $old_image_stmt->execute();
        $old_image_result = $old_image_stmt->get_result();
        if ($old_image_result->num_rows > 0) {
            $old_image = $old_image_result->fetch_assoc()['anh_bia'];
            if ($old_image && file_exists(__DIR__ . '/' . $old_image)) {
                @unlink(__DIR__ . '/' . $old_image);
            }
        }
        $old_image_stmt->close();
    } else {
        $_SESSION['error'] = "Lỗi upload ảnh!";
        header("Location: edit_sk.php?id=$event_id");
        exit;
    }
}

// Cập nhật database
$success = false;
if ($anh_bia) {
    // Cập nhật cả ảnh mới
    $sql = "UPDATE events SET 
                ten_su_kien = ?, mo_ta = ?, noi_dung_chi_tiet = ?, dia_diem = ?,
                thoi_gian_bat_dau = ?, thoi_gian_ket_thuc = ?, so_luong_toi_da = ?,
                han_dang_ky = ?, trang_thai = ?, anh_bia = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssisssi", 
        $ten_su_kien, $mo_ta, $noi_dung_chi_tiet, $dia_diem,
        $tg_bat_dau, $tg_ket_thuc, $so_luong, $han_dang_ky, 
        $trang_thai, $anh_bia, $event_id
    );
    $success = $stmt->execute();
    $stmt->close();
} else {
    // Không cập nhật ảnh
    $sql = "UPDATE events SET 
                ten_su_kien = ?, mo_ta = ?, noi_dung_chi_tiet = ?, dia_diem = ?,
                thoi_gian_bat_dau = ?, thoi_gian_ket_thuc = ?, so_luong_toi_da = ?,
                han_dang_ky = ?, trang_thai = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssissi", 
        $ten_su_kien, $mo_ta, $noi_dung_chi_tiet, $dia_diem,
        $tg_bat_dau, $tg_ket_thuc, $so_luong, $han_dang_ky, 
        $trang_thai, $event_id
    );
    $success = $stmt->execute();
    $stmt->close();
}

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