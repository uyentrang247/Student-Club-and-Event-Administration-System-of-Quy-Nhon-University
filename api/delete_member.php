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

$member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
$current_user_id = $_SESSION['user_id'];

if (!$member_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

// Lấy thông tin thành viên cần xóa từ bảng members
$get_member = "SELECT m.club_id, m.user_id, m.role 
               FROM members m 
               WHERE m.id = ?";

if ($stmt = $conn->prepare($get_member)) {
    $stmt->bind_param("i", $member_id);
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
    $member_role = $member_data['role'];
    $stmt->close();
}

// Không cho phép xóa leader (đội trưởng)
if (in_array($member_role, ['leader', 'chu_nhiem'])) {
    json_response(['success' => false, 'message' => 'Không thể xóa leader của CLB'], HttpStatus::FORBIDDEN);
}

// Kiểm tra quyền (phải là leader hoặc vice_leader)
if (!can_manage_club($conn, $current_user_id, $club_id)) {
    json_response(['success' => false, 'message' => 'Bạn không có quyền xóa thành viên'], HttpStatus::FORBIDDEN);
}

// Không cho phép xóa chính mình
if ($member_user_id == $current_user_id) {
    json_response(['success' => false, 'message' => 'Bạn không thể tự xóa chính mình khỏi CLB'], HttpStatus::FORBIDDEN);
}

// Xóa thành viên
$delete_member = "DELETE FROM members WHERE id = ?";

if ($stmt = $conn->prepare($delete_member)) {
    $stmt->bind_param("i", $member_id);
    
    if ($stmt->execute()) {
        // Cập nhật số lượng thành viên
        $update_count = "UPDATE clubs SET total_members = (
                            SELECT COUNT(*) FROM members 
                            WHERE club_id = ? AND status = ?
                        ) WHERE id = ?";
        
        if ($stmt_update = $conn->prepare($update_count)) {
            $status = 'active';
            $stmt_update->bind_param("isi", $club_id, $status, $club_id);
            $stmt_update->execute();
            $stmt_update->close();
        }
        
        // Lấy tên CLB để thông báo
        $get_club = "SELECT name FROM clubs WHERE id = ?";
        $club_name = "CLB";
        if ($stmt_club = $conn->prepare($get_club)) {
            $stmt_club->bind_param("i", $club_id);
            $stmt_club->execute();
            $club_result = $stmt_club->get_result();
            if ($row = $club_result->fetch_assoc()) {
                $club_name = $row['name'];
            }
            $stmt_club->close();
        }
        
        // Lấy tên người xóa
        $get_deleter = "SELECT full_name FROM users WHERE id = ?";
        $deleter_name = "Quản trị viên";
        if ($stmt_deleter = $conn->prepare($get_deleter)) {
            $stmt_deleter->bind_param("i", $current_user_id);
            $stmt_deleter->execute();
            $deleter_result = $stmt_deleter->get_result();
            if ($row = $deleter_result->fetch_assoc()) {
                $deleter_name = $row['full_name'];
            }
            $stmt_deleter->close();
        }
        
        // Tạo thông báo cho người bị xóa
        $notification_title = "🚫 Bạn đã bị xóa khỏi CLB";
        $notification_message = "Bạn đã bị {$deleter_name} xóa khỏi CLB \"{$club_name}\".";
        $notification_link = "DanhsachCLB.php";
        $notification_type = "system";
        
        $insert_notification = "INSERT INTO notifications (user_id, type, title, message, link) 
                               VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt_notif = $conn->prepare($insert_notification)) {
            $stmt_notif->bind_param("issss", $member_user_id, $notification_type, $notification_title, $notification_message, $notification_link);
            $stmt_notif->execute();
            $stmt_notif->close();
        }
        
        // Log activity
        log_error("Member deleted from club", [
            'club_id' => $club_id, 
            'member_id' => $member_id,
            'deleted_by' => $current_user_id
        ]);
        
        json_response(['success' => true, 'message' => 'Đã xóa thành viên thành công'], HttpStatus::OK);
    } else {
        log_error("Error deleting member: " . $stmt->error, ['member_id' => $member_id]);
        json_response(['success' => false, 'message' => 'Lỗi khi xóa thành viên'], HttpStatus::INTERNAL_ERROR);
    }
    
    $stmt->close();
} else {
    json_response(['success' => false, 'message' => 'Lỗi database'], HttpStatus::INTERNAL_ERROR);
}
?>