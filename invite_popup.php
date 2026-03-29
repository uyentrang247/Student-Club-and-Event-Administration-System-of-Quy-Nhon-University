<?php
session_start();
require_once('assets/database/connect.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    exit('Vui lòng đăng nhập');
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'];

// Lấy club_id từ session
$club_id = $_SESSION['club_id'] ?? null;

if (!$club_id) {
    // Nếu không có trong session, lấy CLB đầu tiên của user
    $stmt = $conn->prepare("SELECT id FROM clubs WHERE leader_id = ? ORDER BY id ASC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $club = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($club) {
        $club_id = $club['id'];
    } else {
        exit('Không tìm thấy CLB');
    }
}

// Lấy tên CLB
$club_name = '';
if ($club_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM clubs WHERE id = ?");
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $club_name = $row['name'];
    }
    $stmt->close();
}
?>

<link rel="stylesheet" href="assets/css/invite_popup.css">

<div id="invitePopup" class="popup-overlay" role="dialog" aria-labelledby="inviteTitle" aria-modal="true">
    <div class="modal-backdrop" onclick="document.getElementById('invitePopup').classList.remove('show')"></div>
    <div class="popup-box">
        <div class="modal-header">
            <div class="modal-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            <h2 id="inviteTitle">Mời tham gia CLB</h2>
            <p class="modal-subtitle">Tìm kiếm và mời người dùng tham gia <strong><?= htmlspecialchars($club_name) ?></strong></p>
            <button class="close-btn" id="closeInvitePopup" aria-label="Đóng">&times;</button>
        </div>

        <div class="modal-body">
            <div class="search-box">
                <div class="search-input-wrapper">
                    <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" 
                           id="searchUser" 
                           placeholder="Tìm kiếm theo tên hoặc email..."
                           autocomplete="off"
                           aria-label="Tìm kiếm người dùng"
                           aria-describedby="searchResult">
                </div>

                <div id="searchResult" class="search-result" role="listbox"></div>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label for="invite_department_id">
                    Phòng ban <span style="color: #999; font-weight: normal;">(Tùy chọn)</span>
                </label>
                <select id="invite_department_id" name="department_id" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px;">
                    <option value="">-- Chưa chọn phòng ban --</option>
                    <?php
                    // Lấy danh sách phòng ban của CLB từ bảng departments
                    $stmt_pb = $conn->prepare("SELECT id, name FROM departments WHERE club_id = ? ORDER BY name ASC");
                    $stmt_pb->bind_param("i", $club_id);
                    $stmt_pb->execute();
                    $departments = $stmt_pb->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt_pb->close();
                    
                    foreach ($departments as $dept):
                    ?>
                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <span style="display: block; margin-top: 6px; font-size: 13px; color: #666;">Chọn phòng ban cho thành viên được mời</span>
            </div>

            <div class="info-text">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <span>Nhập ít nhất 2 ký tự để tìm kiếm</span>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn-cancel" id="inviteCancelBtn">
                <span>Hủy</span>
            </button>
            <button class="btn-confirm" id="inviteSendBtn" disabled>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
                <span>Gửi lời mời</span>
            </button>
        </div>
    </div>
</div>
