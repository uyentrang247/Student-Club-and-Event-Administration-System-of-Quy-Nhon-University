<?php
session_start();
require_once('assets/database/connect.php');

$user_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
$club_id = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;

// Lấy thông tin thành viên
$stmt = $conn->prepare("
    SELECT m.id, m.role, m.department_id, u.full_name, u.username, d.name AS department_name
    FROM members m
    JOIN users u ON m.user_id = u.id
    LEFT JOIN departments d ON m.department_id = d.id
    WHERE m.user_id = ? AND m.club_id = ?
");
$stmt->bind_param("ii", $user_id, $club_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Lấy danh sách phòng ban
$stmt_dept = $conn->prepare("SELECT id, name FROM departments WHERE club_id = ? ORDER BY name ASC");
$stmt_dept->bind_param("i", $club_id);
$stmt_dept->execute();
$departments = $stmt_dept->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_dept->close();

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
                    <strong><?= htmlspecialchars($member['full_name']) ?></strong>
                    <span class="text-muted">(<?= htmlspecialchars($member['username']) ?>)</span>
                </div>
            </div>

            <div class="edit-form-group">
                <label for="department_id">Phòng ban <span class="required">*</span></label>
                <select id="department_id" name="department_id" required>
                    <option value="">-- Chọn phòng ban --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= $member['department_id'] == $dept['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="edit-form-group">
                <label for="role">Chức vụ <span class="required">*</span></label>
                <select id="role" name="role" required>
                    <option value="member" <?= in_array($member['role'], ['member']) ? 'selected' : '' ?>>Thành viên</option>
                    <option value="head" <?= in_array($member['role'], ['head']) ? 'selected' : '' ?>>Trưởng ban</option>
                    <option value="vice_leader" <?= in_array($member['role'], ['vice_leader']) ? 'selected' : '' ?>>Đội phó</option>
                    <option value="leader" <?= in_array($member['role'], ['leader']) ? 'selected' : '' ?>>Đội trưởng</option>
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
