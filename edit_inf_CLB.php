<?php
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// NOW start session
session_start();

// Kiểm tra đăng nhập
require_login();

$page_css = "edit_inf_CLB.css";
require 'site.php';
load_top();
load_header();

require_once(__DIR__ . "/assets/database/connect.php");

$user_id = $_SESSION['user_id'];
$club_id = get_club_id();

if ($club_id <= 0) {
    redirect('myclub.php', 'ID CLB không hợp lệ!', 'error');
}

// Kiểm tra quyền quản lý CLB
require_once __DIR__ . '/includes/constants.php';
if (!can_manage_club($conn, $user_id, $club_id)) {
    redirect('myclub.php', 'Bạn không có quyền chỉnh sửa thông tin câu lạc bộ này!', 'error');
}

// Lấy thông tin CLB (logo lấy từ pages + media + contact từ contacts)
$sql = "SELECT 
            c.id, c.name, c.description, c.category, c.total_members, c.founded_date,
            logo.path AS logo_path,
            cc.email AS contact_email,
            cc.phone AS contact_phone,
            cc.website AS contact_website
        FROM clubs c
        LEFT JOIN pages p ON p.club_id = c.id
        LEFT JOIN media logo ON p.logo_id = logo.id
        LEFT JOIN contacts cc ON cc.club_id = c.id
        WHERE c.id=?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $club_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect('myclub.php', 'Không tìm thấy CLB!', 'error');
}

$club = $result->fetch_assoc();

// Tự động đếm số lượng thành viên thực tế từ members
$count_members_sql = "SELECT COUNT(*) as total FROM members WHERE club_id = ? AND status = 'active'";
$count_stmt = $conn->prepare($count_members_sql);
$count_stmt->bind_param("i", $club_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$actual_member_count = 0;
if ($count_result->num_rows > 0) {
    $count_data = $count_result->fetch_assoc();
    $actual_member_count = (int)$count_data['total'];
}
$count_stmt->close();

// Cập nhật số lượng thành viên trong database nếu khác với thực tế
if ($actual_member_count != ($club['total_members'] ?? 0)) {
    $update_count_sql = "UPDATE clubs SET total_members = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_count_sql);
    $update_stmt->bind_param("ii", $actual_member_count, $club_id);
    $update_stmt->execute();
    $update_stmt->close();
    $club['total_members'] = $actual_member_count;
}

// Set default values
$club['contact_email'] = $club['contact_email'] ?? '';
$club['contact_phone'] = $club['contact_phone'] ?? '';
$club['contact_website'] = $club['contact_website'] ?? '';
$club['total_members'] = $club['total_members'] ?? 0;
$club['founded_date'] = $club['founded_date'] ?? '';
$logo_display = $club['logo_path'] ?? '';
?>

<div class="edit-club-wrapper">
    <div class="edit-club-container">
        <!-- Header -->
        <div class="form-header">
            <div class="header-icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
            </div>
            <h2>Hoàn thiện thông tin câu lạc bộ</h2>
            <p class="form-subtitle">Cập nhật thông tin chi tiết cho câu lạc bộ của bạn</p>
        </div>

        <?php
        // Hiển thị flash message nếu có
        $flash = get_flash_message();
        if ($flash):
            $message = $flash['message'];
            $type = $flash['type'];
            $bg_color = $type === 'success' ? '#10b981' : ($type === 'error' ? '#ef4444' : '#3b82f6');
        ?>
            <div class="flash-message" id="flashMessage" style="background: <?= $bg_color ?>; color: white; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <?php if ($type === 'success'): ?>
                        <polyline points="20 6 9 17 4 12"></polyline>
                    <?php else: ?>
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    <?php endif; ?>
                </svg>
                <span style="flex: 1; font-weight: 500;"><?= htmlspecialchars($message) ?></span>
                <button onclick="document.getElementById('flashMessage').remove()" style="background: none; border: none; color: white; cursor: pointer; padding: 4px; opacity: 0.8; hover:opacity: 1;">&times;</button>
            </div>
            <script>
                // Tự động ẩn sau 5 giây
                setTimeout(() => {
                    const msg = document.getElementById('flashMessage');
                    if (msg) {
                        msg.style.transition = 'opacity 0.3s';
                        msg.style.opacity = '0';
                        setTimeout(() => msg.remove(), 300);
                    }
                }, 5000);
            </script>
        <?php endif; ?>

        <form action="edit_inf_CLB_xuli.php" method="POST" enctype="multipart/form-data" class="edit-form">
            <input type="hidden" name="club_id" value="<?= $club['id'] ?>">

            <!-- Logo Section -->
            <div class="logo-section">
                <div class="current-logo">
                    <img src="<?= htmlspecialchars($logo_display ?: 'assets/img/default-club.png') ?>" alt="Logo" id="logoPreview" onerror="this.src='assets/img/default-club.png'">
                </div>
                <label class="upload-btn" for="logoUpload">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <span>Thay đổi logo</span>
                    <input type="file" id="logoUpload" name="logo" accept="image/*" style="display:none">
                </label>
            </div>

            <!-- Basic Info -->
            <div class="form-section">
                <h3 class="section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    Thông tin cơ bản
                </h3>

                <div class="form-group">
                    <label>Tên câu lạc bộ</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($club['name']) ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Lĩnh vực hoạt động</label>
                        <select name="category" required>
                            <option value="">Chọn lĩnh vực</option>
                            <option value="Academic" <?= ($club['category']=="Academic") ? "selected":""; ?>>📚 Học thuật</option>
                            <option value="Sport" <?= ($club['category']=="Sport") ? "selected":""; ?>>⚽ Thể thao</option>
                            <option value="Art" <?= ($club['category']=="Art") ? "selected":""; ?>>🎨 Nghệ thuật</option>
                            <option value="Volunteer" <?= ($club['category']=="Volunteer") ? "selected":""; ?>>❤️ Tình nguyện</option>
                            <option value="Skill" <?= ($club['category']=="Skill") ? "selected":""; ?>>💡 Kỹ năng</option>
                            <option value="Language" <?= ($club['category']=="Language") ? "selected":""; ?>>🌐 Ngôn ngữ</option>
                            <option value="Other" <?= ($club['category']=="Other") ? "selected":""; ?>>🔖 Khác</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Số lượng thành viên <span style="color: #94a3b8; font-weight: normal; font-size: 13px;">(Tự động cập nhật)</span></label>
                        <input type="number" name="total_members" value="<?= $club['total_members'] ?>" min="0" readonly style="background-color: #f1f5f9; cursor: not-allowed;">
                        <small style="color: #64748b; font-size: 12px; display: block; margin-top: 4px;">Số lượng thành viên được tự động tính từ danh sách thành viên đang hoạt động</small>
                    </div>
                </div>

                <div class="form-group">
                    <label>Ngày thành lập</label>
                    <input type="date" name="founded_date" value="<?= htmlspecialchars($club['founded_date'] ?? '') ?>">
                </div>
            </div>

            <!-- Description -->
            <div class="form-section">
                <h3 class="section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    Giới thiệu câu lạc bộ
                </h3>

                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" rows="5" placeholder="Giới thiệu về mục đích, hoạt động của câu lạc bộ..."><?= htmlspecialchars($club['description'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="form-section">
                <h3 class="section-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    Thông tin liên hệ
                </h3>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email liên hệ</label>
                        <input type="email" name="contact_email" value="<?= htmlspecialchars($club['contact_email'] ?? '') ?>" placeholder="club@example.com">
                    </div>

                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="tel" name="contact_phone" value="<?= htmlspecialchars($club['contact_phone'] ?? '') ?>" placeholder="0123456789">
                    </div>
                </div>

                <div class="form-group">
                    <label>Website <span style="color: #94a3b8; font-weight: normal; font-size: 13px;">(Tùy chọn)</span></label>
                    <input type="url" name="contact_website" value="<?= htmlspecialchars($club['contact_website'] ?? '') ?>" placeholder="https://example.com">
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Lưu thay đổi
                </button>
                <a href="Dashboard.php?id=<?= $club_id ?>" class="btn-cancel">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Quay lại
                </a>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/image-preview.js"></script>
<script>
// Preview logo khi chọn file
initImagePreview('logoUpload', 'logoPreview');
</script>

<?php
load_footer();
?>