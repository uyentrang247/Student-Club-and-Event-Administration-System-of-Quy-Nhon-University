<?php
session_start();
require_once('assets/database/connect.php');

$user_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
$club_id = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;

// Lấy thông tin thành viên
$stmt = $conn->prepare("
    SELECT cm.id, cm.vai_tro, cm.phong_ban_id, u.ho_ten, u.username, pb.ten_phong_ban
    FROM club_members cm
    JOIN users u ON cm.user_id = u.id
    LEFT JOIN phong_ban pb ON cm.phong_ban_id = pb.id
    WHERE cm.user_id = ? AND cm.club_id = ?
");
$stmt->bind_param("ii", $user_id, $club_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Lấy danh sách phòng ban
$stmt_pb = $conn->prepare("SELECT id, ten_phong_ban FROM phong_ban WHERE club_id = ? ORDER BY ten_phong_ban ASC");
$stmt_pb->bind_param("i", $club_id);
$stmt_pb->execute();
$phong_bans = $stmt_pb->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_pb->close();

if (!$member) {
    echo "<script>alert('Không tìm thấy thành viên!'); window.close();</script>";
    exit;
}
?>

<div id="editRoleModal" class="edit-modal-overlay" style="display:flex;">
    <div class="edit-modal-backdrop" onclick="closeRoleModal()"></div>
    <div class="edit-modal-content">
        <div class="edit-modal-header">
            <h3>Chỉnh sửa chức vụ</h3>
            <button class="edit-modal-close" type="button" aria-label="Đóng" onclick="closeRoleModal()">&times;</button>
        </div>

        <form id="editRoleForm" action="process_edit_role.php" method="POST">
            <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
            <input type="hidden" name="club_id" value="<?= $club_id ?>">

            <div class="edit-form-group">
                <label>Thành viên</label>
                <div class="edit-member-display">
                    <strong><?= htmlspecialchars($member['ho_ten']) ?></strong>
                    <span class="text-muted">(<?= htmlspecialchars($member['username']) ?>)</span>
                </div>
            </div>

            <div class="edit-form-group">
                <label for="phong_ban_id">Phòng ban <span class="required">*</span></label>
                <select id="phong_ban_id" name="phong_ban_id" required>
                    <option value="">-- Chọn phòng ban --</option>
                    <?php foreach ($phong_bans as $pb): ?>
                        <option value="<?= $pb['id'] ?>" <?= $member['phong_ban_id'] == $pb['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pb['ten_phong_ban']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="edit-form-group">
                <label for="vai_tro">Chức vụ <span class="required">*</span></label>
                <select id="vai_tro" name="vai_tro" required>
                    <option value="thanh_vien" <?= in_array($member['vai_tro'], ['thanh_vien']) ? 'selected' : '' ?>>Thành viên</option>
                    <option value="truong_ban" <?= in_array($member['vai_tro'], ['truong_ban']) ? 'selected' : '' ?>>Trưởng ban</option>
                    <option value="doi_pho" <?= in_array($member['vai_tro'], ['doi_pho', 'pho_chu_nhiem']) ? 'selected' : '' ?>>Đội phó</option>
                    <option value="doi_truong" <?= in_array($member['vai_tro'], ['doi_truong', 'chu_nhiem']) ? 'selected' : '' ?>>Đội trưởng</option>
                </select>
            </div>

            <div class="edit-modal-footer">
                <button type="button" class="edit-btn-cancel" onclick="closeRoleModal()">Hủy</button>
                <button type="submit" class="edit-btn-submit">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<!-- Tái sử dụng giao diện modal của màn hình view_member -->
<link rel="stylesheet" href="assets/css/view_member.css">
