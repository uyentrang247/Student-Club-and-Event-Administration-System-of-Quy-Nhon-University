<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/includes/constants.php';
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

// Rate limit
if (!check_rate_limit('cancel_event_' . $_SESSION['user_id'], 5, 60)) {
    echo json_encode(['success' => false, 'message' => 'Bạn thao tác quá nhanh, vui lòng thử lại sau.']);
    exit;
}

// CSRF check for state-changing action
$csrf_token = $_POST[CSRF_TOKEN_NAME] ?? '';
if (!verify_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Phiên không hợp lệ, vui lòng tải lại trang.']);
    exit;
}

require_once 'assets/database/connect.php';

$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$user_id = $_SESSION['user_id'];

if ($event_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID sự kiện không hợp lệ!']);
    exit;
}

// Lấy thông tin sự kiện và CLB
$event_sql = "SELECT e.ten_su_kien, e.club_id FROM events e WHERE e.id = ?";
$stmt_evt = $conn->prepare($event_sql);
$stmt_evt->bind_param("i", $event_id);
$stmt_evt->execute();
$event = $stmt_evt->get_result()->fetch_assoc();
$stmt_evt->close();

if (!$event) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện.']);
    exit;
}

// Kiểm tra có bản ghi đăng ký không và trạng thái thành viên còn hoạt động
$stmt = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?");
$stmt->bind_param("ii", $event_id, $user_id);
$stmt->execute();
$reg = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reg) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng ký sự kiện này.']);
    exit;
}

// Chỉ thành viên đang hoạt động hoặc chủ CLB mới được hủy
$club_id = (int)$event['club_id'];
$member_stmt = $conn->prepare("SELECT 1 FROM club_members WHERE club_id = ? AND user_id = ? AND trang_thai = 'dang_hoat_dong'");
$member_stmt->bind_param("ii", $club_id, $user_id);
$member_stmt->execute();
$is_active_member = $member_stmt->get_result()->num_rows > 0;
$member_stmt->close();

if (!$is_active_member) {
    $owner_stmt = $conn->prepare("SELECT 1 FROM clubs WHERE id = ? AND chu_nhiem_id = ?");
    $owner_stmt->bind_param("ii", $club_id, $user_id);
    $owner_stmt->execute();
    $is_owner = $owner_stmt->get_result()->num_rows > 0;
    $owner_stmt->close();
    if (!$is_owner) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền hủy đăng ký sự kiện này.']);
        exit;
    }
}

// Xóa đăng ký
$del = $conn->prepare("DELETE FROM event_registrations WHERE id = ?");
$del->bind_param("i", $reg['id']);
if ($del->execute()) {
    // Gửi thông báo cho quản lý CLB về việc hủy đăng ký
    // Lấy tên user
    $user_sql = "SELECT ho_ten FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_res = $user_stmt->get_result();
    $user_name = $user_res && $user_res->num_rows > 0 ? $user_res->fetch_assoc()['ho_ten'] : 'Một người dùng';
    $user_stmt->close();

    // Lấy danh sách quản lý CLB
    $club_id = (int)$event['club_id'];
    $managers_sql = "SELECT DISTINCT user_id FROM (
                        SELECT chu_nhiem_id as user_id FROM clubs WHERE id = ?
                        UNION
                        SELECT user_id FROM club_members 
                        WHERE club_id = ? AND trang_thai = 'dang_hoat_dong' 
                        AND vai_tro IN ('doi_truong', 'doi_pho', 'truong_ban')
                        UNION
                        SELECT truong_phong_id as user_id FROM phong_ban 
                        WHERE club_id = ? AND truong_phong_id IS NOT NULL
                    ) AS managers";
    $mgr_stmt = $conn->prepare($managers_sql);
    $mgr_stmt->bind_param("iii", $club_id, $club_id, $club_id);
    $mgr_stmt->execute();
    $mgr_res = $mgr_stmt->get_result();

    $notif_title = "Thành viên hủy đăng ký sự kiện";
    $notif_link = "chi_tiet_su_kien.php?id=" . $event_id;
    $notif_type = "event_invite";
    $notif_message = $user_name . " đã hủy đăng ký sự kiện \"" . htmlspecialchars($event['ten_su_kien']) . "\"";

    while ($mgr = $mgr_res->fetch_assoc()) {
        $mgr_id = $mgr['user_id'];
        if ($mgr_id == $user_id) continue;
        $insert_notif = "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)";
        if ($nstmt = $conn->prepare($insert_notif)) {
            $nstmt->bind_param("issss", $mgr_id, $notif_type, $notif_title, $notif_message, $notif_link);
            $nstmt->execute();
            $nstmt->close();
        }
    }
    $mgr_stmt->close();

    echo json_encode(['success' => true, 'message' => 'Đã hủy đăng ký sự kiện.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể hủy đăng ký, vui lòng thử lại.']);
}
$del->close();

