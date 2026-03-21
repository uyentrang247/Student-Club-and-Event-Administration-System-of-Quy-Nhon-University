<?php
// Load dependencies FIRST
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/constants.php';
require_once __DIR__ . '/../includes/functions.php';

// NOW start session
session_start();

require_once __DIR__ . '/../assets/database/connect.php';

header('Content-Type: application/json; charset=utf-8');

// Check login
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Chưa đăng nhập'], HttpStatus::UNAUTHORIZED);
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Phương thức không hợp lệ'], HttpStatus::BAD_REQUEST);
}

$club_member_id = isset($_POST['club_member_id']) ? (int)$_POST['club_member_id'] : 0;
$current_user_id = $_SESSION['user_id'];

if (!$club_member_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

// Lấy thông tin thành viên cần xóa
$get_member = "SELECT cm.club_id, cm.user_id, cm.vai_tro 
               FROM club_members cm 
               WHERE cm.id = ?";

if ($stmt = $conn->prepare($get_member)) {
    $stmt->bind_param("i", $club_member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thành viên']);
        $stmt->close();
        exit;
    }
    
    $member_data = $result->fetch_assoc();
    $club_id = $member_data['club_id'];
    $member_user_id = $member_data['user_id'];
    $member_role = $member_data['vai_tro'];
    $stmt->close();
}

// Không cho phép xóa đội trưởng
if (in_array($member_role, [UserRole::DOI_TRUONG, UserRole::CHU_NHIEM, 'doi_truong', 'chu_nhiem'])) {
    json_response(['success' => false, 'message' => 'Không thể xóa đội trưởng CLB'], HttpStatus::FORBIDDEN);
}

// Kiểm tra quyền (phải là đội trưởng hoặc đội phó)
if (!can_manage_club($conn, $current_user_id, $club_id)) {
    json_response(['success' => false, 'message' => 'Bạn không có quyền xóa thành viên'], HttpStatus::FORBIDDEN);
}

// Xóa thành viên
$delete_member = "DELETE FROM club_members WHERE id = ?";

if ($stmt = $conn->prepare($delete_member)) {
    $stmt->bind_param("i", $club_member_id);
    
    if ($stmt->execute()) {
        // Cập nhật số lượng thành viên
        $update_count = "UPDATE clubs SET so_thanh_vien = (
                            SELECT COUNT(*) FROM club_members 
                            WHERE club_id = ? AND trang_thai = ?
                        ) WHERE id = ?";
        
        if ($stmt_update = $conn->prepare($update_count)) {
            $status = MemberStatus::DANG_HOAT_DONG;
            $stmt_update->bind_param("isi", $club_id, $status, $club_id);
            $stmt_update->execute();
            $stmt_update->close();
        }
        
        // Log activity
        log_error("Member deleted from club", [
            'club_id' => $club_id, 
            'member_id' => $club_member_id,
            'deleted_by' => $current_user_id
        ]);
        
        json_response(['success' => true, 'message' => 'Đã xóa thành viên thành công'], HttpStatus::OK);
    } else {
        log_error("Error deleting member: " . $stmt->error, ['member_id' => $club_member_id]);
        json_response(['success' => false, 'message' => 'Lỗi khi xóa thành viên'], HttpStatus::INTERNAL_ERROR);
    }
    
    $stmt->close();
} else {
    json_response(['success' => false, 'message' => 'Lỗi database'], HttpStatus::INTERNAL_ERROR);
}
