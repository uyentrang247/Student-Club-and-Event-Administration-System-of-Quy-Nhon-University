<?php 
session_start();
require 'site.php';
require_once(__DIR__ . "/assets/database/connect.php");

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vui lòng đăng nhập!";
    header("Location: login.php");
    exit;
}

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($event_id <= 0) {
    $_SESSION['error'] = "ID sự kiện không hợp lệ";
    header("Location: myclub.php");
    exit;
}

$page_css = "add_sk.css";

global $conn;

// Lấy thông tin sự kiện (bao gồm ảnh bìa từ media)
$sql = "SELECT e.*, c.name as club_name, c.leader_id, 
               m.path AS cover_path
        FROM events e 
        JOIN clubs c ON e.club_id = c.id 
        LEFT JOIN media m ON e.cover_id = m.id
        WHERE e.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    $_SESSION['error'] = "Sự kiện không tồn tại";
    header("Location: myclub.php");
    exit;
}

// Kiểm tra quyền chỉnh sửa (trước khi render bất kỳ HTML nào)
$user_id = $_SESSION['user_id'];
$check_role_sql = "SELECT role FROM members WHERE club_id = ? AND user_id = ? AND status = 'active'";
$check_stmt = $conn->prepare($check_role_sql);
$check_stmt->bind_param("ii", $event['club_id'], $user_id);
$check_stmt->execute();
$role_result = $check_stmt->get_result();
$user_role = $role_result->num_rows > 0 ? $role_result->fetch_assoc()['role'] : '';
$check_stmt->close();

// Chuẩn hóa vai trò để so sánh (bao quát nhiều trường hợp)
function normalize_role_edit($role) {
    if (empty($role)) return '';
    $role = trim($role);
    $role_lower = mb_strtolower($role, 'UTF-8');
    
    // Kiểm tra các pattern phổ biến (có dấu và không dấu)
    if (stripos($role_lower, 'vice_leader') !== false || stripos($role_lower, 'doi pho') !== false || 
        stripos($role_lower, 'doi_pho') !== false) {
        return 'vice_leader';
    }
    if (stripos($role_lower, 'leader') !== false || stripos($role_lower, 'doi truong') !== false || 
        stripos($role_lower, 'doi_truong') !== false) {
        return 'leader';
    }
    if (stripos($role_lower, 'head') !== false || stripos($role_lower, 'truong ban') !== false || 
        stripos($role_lower, 'truong_ban') !== false) {
        return 'head';
    }
    if (stripos($role_lower, 'member') !== false || stripos($role_lower, 'thanh vien') !== false || 
        stripos($role_lower, 'thanh_vien') !== false) {
        return 'member';
    }
    
    // Nếu là giá trị enum/constant
    $role_upper = strtoupper($role);
    if (in_array($role_upper, ['VICE_LEADER', 'LEADER', 'HEAD', 'MEMBER'])) {
        return strtolower($role_upper);
    }
    
    // Fallback: lowercase trực tiếp
    return strtolower($role);
}

$role_key = normalize_role_edit($user_role);
$is_owner = isset($event['leader_id']) && ((int)$event['leader_id'] === (int)$user_id);
// Chỉ cho phép Leader / Vice Leader / Head
$can_edit = $is_owner || in_array($role_key, ['leader', 'vice_leader', 'head']);
if (!$can_edit) {
    $_SESSION['error'] = "Bạn không có quyền chỉnh sửa sự kiện này";
    header("Location: list_su_kien.php?id=" . $event['club_id']);
    exit;
}

// Sau khi qua kiểm tra quyền, mới render giao diện
load_top();
load_header();

// Format datetime cho input
function format_for_input($datetime) {
    return date('Y-m-d\TH:i', strtotime($datetime));
}
?>

<div class="contain">
    <div class="contain-add-sk">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <h1>✏️ Chỉnh sửa sự kiện</h1>
        <p style="text-align: center; color: #718096; margin: -30px 0 30px 0; font-size: 16px;">
            <?= htmlspecialchars($event['club_name']) ?>
        </p>

        <form action="update_sk.php" method="POST" enctype="multipart/form-data" class="form-sk">
            <input type="hidden" name="event_id" value="<?= $event_id ?>">
            
            <!-- Ảnh bìa -->
            <div class="section-title">📸 Ảnh bìa sự kiện</div>
            
            <div class="current-image">
                <?php if (!empty($event['cover_path'])): ?>
                    <img src="<?= htmlspecialchars($event['cover_path']) ?>" alt="Ảnh bìa hiện tại" id="preview-image" class="anh_bia">
                    <p class="image-note">Ảnh bìa hiện tại</p>
                <?php else: ?>
                    <div class="no-image">Chưa có ảnh bìa</div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="cover_new">Thay đổi ảnh bìa (tùy chọn)</label>
                <input type="file" id="cover_new" name="cover_new" accept="image/*" onchange="previewNewImage(this)" style="padding: 10px;">
                <small style="color: #718096; font-size: 13px;">Chấp nhận: JPG, PNG, GIF, WEBP. Tối đa 5MB</small>
            </div>

            <!-- Thông tin cơ bản -->
            <div class="section-title">📝 Thông tin cơ bản</div>
            
            <div class="form-group">
                <label for="name" class="name-event">Tên sự kiện <span style="color: #e53e3e;">*</span></label>
                <input type="text" id="name" name="name" 
                       value="<?= htmlspecialchars($event['name']) ?>" required>
            </div>

            <div class="form-group">
                <label for="short_desc">Mô tả ngắn <span style="color: #e53e3e;">*</span></label>
                <textarea id="short_desc" name="short_desc" rows="3" required><?= htmlspecialchars($event['short_desc']) ?></textarea>
                <small style="color: #718096; font-size: 13px;">Mô tả ngắn gọn về sự kiện (hiển thị trong danh sách)</small>
            </div>

            <div class="form-group">
                <label for="full_desc">Nội dung chi tiết <span style="color: #e53e3e;">*</span></label>
                <textarea id="full_desc" name="full_desc" rows="6" required><?= htmlspecialchars($event['full_desc']) ?></textarea>
                <small style="color: #718096; font-size: 13px;">Mô tả chi tiết về sự kiện, chương trình, lịch trình...</small>
            </div>

            <!-- Thời gian & Địa điểm -->
            <div class="section-title">📅 Thời gian & Địa điểm</div>
            
            <div class="two-col">
                <div class="form-group">
                    <label for="start_time">Thời gian bắt đầu <span style="color: #e53e3e;">*</span></label>
                    <input type="datetime-local" id="start_time" name="start_time" 
                           value="<?= format_for_input($event['start_time']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="end_time">Thời gian kết thúc <span style="color: #e53e3e;">*</span></label>
                    <input type="datetime-local" id="end_time" name="end_time" 
                           value="<?= format_for_input($event['end_time']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="location">Địa điểm <span style="color: #e53e3e;">*</span></label>
                <input type="text" id="location" name="location" 
                       value="<?= htmlspecialchars($event['location']) ?>" 
                       placeholder="VD: Hội trường A, Tòa nhà B..." required>
            </div>

            <!-- Đăng ký & Trạng thái -->
            <div class="section-title">👥 Đăng ký & Trạng thái</div>
            
            <div class="two-col">
                <div class="form-group">
                    <label for="max_participants">Số lượng tối đa <span style="color: #e53e3e;">*</span></label>
                    <input type="number" id="max_participants" name="max_participants" min="1" 
                           value="<?= $event['max_participants'] ?>" required>
                    <small style="color: #718096; font-size: 13px;">Số người tối đa có thể tham gia</small>
                </div>

                <div class="form-group">
                    <label for="reg_deadline">Hạn đăng ký</label>
                    <input type="datetime-local" id="reg_deadline" name="reg_deadline" 
                           value="<?= $event['reg_deadline'] ? format_for_input($event['reg_deadline']) : '' ?>">
                    <small style="color: #718096; font-size: 13px;">Thời hạn cuối để đăng ký tham gia</small>
                </div>
            </div>

            <div class="form-group">
                <label for="status">Trạng thái sự kiện <span style="color: #e53e3e;">*</span></label>
                <select id="status" name="status" required style="padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 15px; background: #f7fafc; width: 100%;">
                    <option value="upcoming" <?= $event['status'] == 'upcoming' ? 'selected' : '' ?>>Sắp diễn ra</option>
                    <option value="ongoing" <?= $event['status'] == 'ongoing' ? 'selected' : '' ?>>Đang diễn ra</option>
                    <option value="completed" <?= $event['status'] == 'completed' ? 'selected' : '' ?>>Đã kết thúc</option>
                    <option value="cancelled" <?= $event['status'] == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="button-group">
                <button type="button" class="btn-back" onclick="window.location.href='list_su_kien.php?id=<?= $event['club_id'] ?>'">
                    ← Quay lại
                </button>
                <button type="submit" class="btn-submit">
                    💾 Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function previewNewImage(input) {
    if (input.files && input.files[0]) {
        // Kiểm tra kích thước file (tối đa 5MB)
        if (input.files[0].size > 5 * 1024 * 1024) {
            alert('⚠️ Kích thước ảnh không được vượt quá 5MB!');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('preview-image');
            const currentImage = document.querySelector('.current-image');
            
            if (preview) {
                // Đã có ảnh, chỉ cập nhật src
                preview.src = e.target.result;
                const note = preview.nextElementSibling;
                if (note && note.classList.contains('image-note')) {
                    note.textContent = 'Ảnh mới (chưa lưu)';
                }
            } else {
                // Chưa có ảnh, tạo mới img element
                currentImage.innerHTML = `
                    <img src="${e.target.result}" alt="Ảnh mới" id="preview-image" class="anh_bia">
                    <p class="image-note">Ảnh mới (chưa lưu)</p>
                `;
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Validate form trước khi submit
document.querySelector('.form-sk').addEventListener('submit', function(e) {
    const startTime = new Date(document.getElementById('start_time').value);
    const endTime = new Date(document.getElementById('end_time').value);
    const regDeadline = document.getElementById('reg_deadline').value;
    
    if (endTime <= startTime) {
        e.preventDefault();
        alert('⚠️ Thời gian kết thúc phải sau thời gian bắt đầu!');
        return false;
    }
    
    if (regDeadline) {
        const deadline = new Date(regDeadline);
        if (deadline >= startTime) {
            e.preventDefault();
            alert('⚠️ Hạn đăng ký phải trước thời gian bắt đầu sự kiện!');
            return false;
        }
    }
    
    return true;
});
</script>

<?php 
$conn->close();
load_footer();
?>
