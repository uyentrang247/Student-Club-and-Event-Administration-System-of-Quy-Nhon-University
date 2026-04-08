<?php
// Dependencies
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Session
session_start();

require_once __DIR__ . '/assets/database/connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Chưa đăng nhập'], HttpStatus::UNAUTHORIZED);
}

$csrf_token = $_POST[CSRF_TOKEN_NAME] ?? '';
if (!verify_csrf_token($csrf_token)) {
    json_response(['success' => false, 'message' => 'Phiên không hợp lệ'], HttpStatus::BAD_REQUEST);
}

$photo_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$club_id  = isset($_POST['club_id']) ? (int)$_POST['club_id'] : 0;
$user_id  = $_SESSION['user_id'];

if ($photo_id <= 0 || $club_id <= 0) {
    json_response(['success' => false, 'message' => 'Thiếu thông tin ảnh hoặc CLB'], HttpStatus::BAD_REQUEST);
}

// Check permission
if (!can_manage_club($conn, $user_id, $club_id)) {
    json_response(['success' => false, 'message' => 'Bạn không có quyền xóa ảnh này'], HttpStatus::FORBIDDEN);
}

// Fetch photo + media info
$sql = "SELECT g.media_id, m.path 
        FROM gallery g 
        LEFT JOIN media m ON g.media_id = m.id
        WHERE g.id = ? AND g.club_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $photo_id, $club_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    json_response(['success' => false, 'message' => 'Ảnh không tồn tại'], HttpStatus::NOT_FOUND);
}

$media_id = (int)$res['media_id'];
$file_path = $res['path'] ?? '';

$conn->begin_transaction();

// Delete gallery entry
$del_gallery = $conn->prepare("DELETE FROM gallery WHERE id = ? AND club_id = ?");
$del_gallery->bind_param("ii", $photo_id, $club_id);
if (!$del_gallery->execute()) {
    $conn->rollback();
    json_response(['success' => false, 'message' => 'Không thể xóa ảnh (gallery)'], HttpStatus::INTERNAL_ERROR);
}
$del_gallery->close();

// Delete media row
if ($media_id > 0) {
    $del_media = $conn->prepare("DELETE FROM media WHERE id = ?");
    $del_media->bind_param("i", $media_id);
    if (!$del_media->execute()) {
        $conn->rollback();
        json_response(['success' => false, 'message' => 'Không thể xóa media'], HttpStatus::INTERNAL_ERROR);
    }
    $del_media->close();
}

$conn->commit();

// Remove file from disk if exists
if (!empty($file_path)) {
    $abs_path = APP_ROOT . '/' . ltrim($file_path, '/');
    if (file_exists($abs_path) && is_file($abs_path)) {
        @unlink($abs_path);
    }
}

json_response(['success' => true, 'message' => 'Đã xóa ảnh thành công'], HttpStatus::OK);
?>