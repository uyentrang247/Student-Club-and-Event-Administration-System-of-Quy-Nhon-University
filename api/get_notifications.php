<?php
session_start();
require_once(__DIR__ . "/../assets/database/connect.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';

$sql = "SELECT id, type, title, message, link, is_read, created_at 
        FROM notifications 
        WHERE user_id = ?";

if ($unread_only) {
    $sql .= " AND is_read = 0";
}

$sql .= " ORDER BY created_at DESC LIMIT ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        // Parse member_id và club_id từ link nếu có
        $row['member_id'] = null;
        $row['club_id'] = null;
        $row['request_status'] = null; // 'pending', 'approved', 'rejected'
        
        if ($row['type'] === 'club_join' && !empty($row['link'])) {
            // Parse member_id từ link
            if (preg_match('/member_id=(\d+)/', $row['link'], $matches)) {
                $row['member_id'] = (int)$matches[1];
            }
            // Parse club_id từ link
            if (preg_match('/club_id=(\d+)/', $row['link'], $matches)) {
                $row['club_id'] = (int)$matches[1];
            } else {
                // Nếu không có club_id trong link, lấy từ club_members
                if ($row['member_id']) {
                    $get_club_sql = "SELECT club_id FROM club_members WHERE id = ? LIMIT 1";
                    if ($stmt_club = $conn->prepare($get_club_sql)) {
                        $stmt_club->bind_param("i", $row['member_id']);
                        $stmt_club->execute();
                        $club_result = $stmt_club->get_result();
                        if ($club_result->num_rows > 0) {
                            $club_data = $club_result->fetch_assoc();
                            $row['club_id'] = (int)$club_data['club_id'];
                        }
                        $stmt_club->close();
                    }
                }
            }
            
            // Kiểm tra trạng thái yêu cầu nếu có member_id
            if ($row['member_id']) {
                $check_status_sql = "SELECT trang_thai FROM club_members WHERE id = ?";
                if ($stmt_status = $conn->prepare($check_status_sql)) {
                    $stmt_status->bind_param("i", $row['member_id']);
                    $stmt_status->execute();
                    $status_result = $stmt_status->get_result();
                    
                    if ($status_result->num_rows > 0) {
                        $status_data = $status_result->fetch_assoc();
                        if ($status_data['trang_thai'] === 'dang_hoat_dong') {
                            $row['request_status'] = 'approved';
                        } elseif ($status_data['trang_thai'] === 'cho_duyet') {
                            $row['request_status'] = 'pending';
                        } else {
                            $row['request_status'] = 'rejected';
                        }
                    } else {
                        // Member không tồn tại = đã bị từ chối (xóa)
                        $row['request_status'] = 'rejected';
                    }
                    $stmt_status->close();
                }
            }
        }
        $notifications[] = $row;
    }
    
    echo json_encode(['success' => true, 'notifications' => $notifications]);
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

// Don't close connection - it's managed globally
