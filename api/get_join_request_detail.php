<?php
session_start();
require_once __DIR__ . '/../assets/database/connect.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Chưa đăng nhập'], HttpStatus::UNAUTHORIZED);
}

$member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
$club_id = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;

if (!$member_id || !$club_id) {
    json_response(['success' => false, 'message' => 'Thiếu thông tin'], HttpStatus::BAD_REQUEST);
}

// Lấy thông tin chi tiết từ club_members, users, và join_requests
$sql = "SELECT 
            cm.id AS club_member_id,
            cm.user_id,
            cm.club_id,
            cm.phong_ban_id,
            cm.trang_thai AS member_status,
            cm.vai_tro,
            u.ho_ten,
            u.username,
            u.email,
            u.avatar,
            u.so_dien_thoai AS user_phone,
            pb.ten_phong_ban,
            jr.so_dien_thoai AS request_phone,
            jr.loi_nhan,
            jr.requested_at,
            c.ten_clb
        FROM club_members cm
        INNER JOIN users u ON cm.user_id = u.id
        LEFT JOIN phong_ban pb ON cm.phong_ban_id = pb.id AND pb.club_id = cm.club_id
        LEFT JOIN join_requests jr ON jr.club_id = cm.club_id AND jr.user_id = cm.user_id
        INNER JOIN clubs c ON cm.club_id = c.id
        WHERE cm.id = ? AND cm.club_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $member_id, $club_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Nếu không tìm thấy trong club_members, có thể đã bị từ chối (xóa)
    // Lấy thông tin từ join_requests dựa vào club_id (lấy request gần nhất)
    $fallback_sql = "SELECT 
                        jr.club_id,
                        jr.user_id,
                        jr.so_dien_thoai AS request_phone,
                        jr.loi_nhan,
                        jr.requested_at,
                        u.ho_ten,
                        u.username,
                        u.email,
                        u.avatar,
                        u.so_dien_thoai AS user_phone,
                        c.ten_clb
                    FROM join_requests jr
                    INNER JOIN users u ON jr.user_id = u.id
                    INNER JOIN clubs c ON jr.club_id = c.id
                    WHERE jr.club_id = ?
                    ORDER BY jr.requested_at DESC LIMIT 1";
    
    $fallback_stmt = $conn->prepare($fallback_sql);
    $fallback_stmt->bind_param("i", $club_id);
    $fallback_stmt->execute();
    $fallback_result = $fallback_stmt->get_result();
    
    if ($fallback_result->num_rows > 0) {
        $data = $fallback_result->fetch_assoc();
        $data['member_status'] = 'rejected'; // Đánh dấu là đã từ chối
        $data['phong_ban_id'] = null;
        $data['ten_phong_ban'] = null;
        $data['vai_tro'] = null;
        $fallback_stmt->close();
    } else {
        $fallback_stmt->close();
        json_response(['success' => false, 'message' => 'Không tìm thấy thông tin yêu cầu'], HttpStatus::NOT_FOUND);
    }
} else {
    $data = $result->fetch_assoc();
}
$stmt->close();

// Lấy danh sách phòng ban của CLB
$dept_sql = "SELECT id, ten_phong_ban FROM phong_ban WHERE club_id = ? ORDER BY ten_phong_ban ASC";
$dept_stmt = $conn->prepare($dept_sql);
$dept_stmt->bind_param("i", $club_id);
$dept_stmt->execute();
$dept_result = $dept_stmt->get_result();
$departments = $dept_result->fetch_all(MYSQLI_ASSOC);
$dept_stmt->close();

json_response([
    'success' => true,
    'request' => $data,
    'departments' => $departments
], HttpStatus::OK);
?>

