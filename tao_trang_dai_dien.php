<?php
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// NOW start session
session_start();

// Kiểm tra đăng nhập
require_login();

require 'site.php';

$user_id = $_SESSION['user_id'];
$club_id = get_club_id();

if ($club_id <= 0) {
    redirect('myclub.php', 'Không tìm thấy câu lạc bộ!', 'error');
}

// Kết nối database để lấy thông tin CLB
require_once 'assets/database/connect.php';
require_once __DIR__ . '/includes/constants.php';

// Kiểm tra quyền quản lý CLB
if (!can_manage_club($conn, $user_id, $club_id)) {
    redirect('myclub.php', 'Bạn không có quyền tạo trang đại diện cho câu lạc bộ này!', 'error');
}

// Lấy thông tin CLB hiện có
$sql = "SELECT * FROM clubs WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $club_id);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc();

// Kiểm tra nếu không tìm thấy club
if (!$club) {
    $_SESSION['error'] = "Không tìm thấy thông tin câu lạc bộ!";
    header("Location: myclub.php");
    exit;
}

// Lấy thông tin trang đại diện nếu đã có
$club_page = null;
$table_check = $conn->query("SHOW TABLES LIKE 'club_pages'");
if ($table_check && $table_check->num_rows > 0) {
    $sql = "SELECT * FROM club_pages WHERE club_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $club_page = $result->fetch_assoc();
    }
}

// Đếm số thành viên
$sql = "SELECT COUNT(*) as total FROM club_members WHERE club_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $club_id);
$stmt->execute();
$member_count = $stmt->get_result()->fetch_assoc()['total'];

$page_css = "tao_trang_dai_dien.css";
load_top();
load_header();
?>

<div class="appearance-container">
    <div class="page-header">
        <div class="header-content">
            <h1>🎨 Tùy chỉnh giao diện trang CLB</h1>
            <p>Chỉnh sửa giao diện trang chi tiết câu lạc bộ của bạn</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            ✓ <?= $_SESSION['success'] ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            ✗ <?= $_SESSION['error'] ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form action="tao_trang_dai_dien_xuli.php?id=<?= $club_id ?>" method="POST" enctype="multipart/form-data" class="appearance-form">
        <input type="hidden" name="club_id" id="club_id_input" value="<?= htmlspecialchars($club_id) ?>">

        <!-- Thông tin cơ bản -->
        <div class="form-section">
            <div class="section-header">
                <h2>📝 Thông tin cơ bản</h2>
                <p>Thông tin hiển thị trên trang chi tiết CLB</p>
            </div>
            
            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="slogan">Slogan câu lạc bộ</label>
                    <input type="text" id="slogan" name="slogan" 
                           value="<?= htmlspecialchars($club_page['slogan'] ?? '') ?>"
                           placeholder="VD: Nơi đam mê công nghệ được thắp sáng">
                    <small>Câu khẩu hiệu ngắn gọn, ấn tượng của CLB</small>
                </div>

                <div class="form-group full-width">
                    <label for="description">Mô tả chi tiết</label>
                    <textarea id="description" name="description" rows="5" 
                              placeholder="Giới thiệu chi tiết về câu lạc bộ..."><?= htmlspecialchars($club_page['description'] ?? $club['mo_ta'] ?? '') ?></textarea>
                    <small>Mô tả đầy đủ về CLB, hoạt động và mục tiêu</small>
                </div>
            </div>
        </div>

        <!-- Hình ảnh -->
        <div class="form-section">
            <div class="section-header">
                <h2>🖼️ Hình ảnh</h2>
                <p>Ảnh bìa và logo hiển thị trên trang CLB</p>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="banner">Ảnh bìa</label>
                    <div class="image-upload-area">
                        <input type="file" id="banner" name="banner" accept="image/*" onchange="previewImage(this, 'banner-preview')">
                        <div class="upload-placeholder" id="banner-preview">
                            <?php if (!empty($club_page['banner_url'])): ?>
                                <img src="<?= htmlspecialchars($club_page['banner_url']) ?>" alt="Banner">
                            <?php else: ?>
                                <span class="upload-icon">📷</span>
                                <span>Chọn ảnh bìa</span>
                                <small>Kích thước đề xuất: 1920x600px</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="avatar">Logo/Avatar CLB</label>
                    <div class="image-upload-area">
                        <input type="file" id="avatar" name="avatar" accept="image/*" onchange="previewImage(this, 'avatar-preview')">
                        <div class="upload-placeholder avatar-placeholder" id="avatar-preview">
                            <?php if (!empty($club_page['logo_url']) || !empty($club['logo'])): ?>
                                <img src="<?= htmlspecialchars($club_page['logo_url'] ?? $club['logo']) ?>" alt="Logo">
                            <?php else: ?>
                                <span class="upload-icon">🎨</span>
                                <span>Chọn logo</span>
                                <small>Kích thước đề xuất: 500x500px</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Màu sắc -->
        <div class="form-section">
            <div class="section-header">
                <h2>🎨 Màu sắc chủ đạo</h2>
                <p>Chọn màu sắc đại diện cho CLB</p>
            </div>
            
            <div class="color-picker-group">
                <div class="form-group">
                    <label for="primary_color">Màu chính</label>
                    <div class="color-input-wrapper">
                        <input type="color" id="primary_color" name="primary_color" 
                               value="<?= htmlspecialchars($club_page['primary_color'] ?? $club['color'] ?? '#667eea') ?>">
                        <input type="text" class="color-text" 
                               value="<?= htmlspecialchars($club_page['primary_color'] ?? $club['color'] ?? '#667eea') ?>" 
                               readonly>
                    </div>
                </div>

                <div class="color-presets">
                    <label>Màu gợi ý:</label>
                    <div class="preset-colors">
                        <button type="button" class="color-preset" style="background: #667eea" onclick="setColor('#667eea')"></button>
                        <button type="button" class="color-preset" style="background: #f093fb" onclick="setColor('#f093fb')"></button>
                        <button type="button" class="color-preset" style="background: #4facfe" onclick="setColor('#4facfe')"></button>
                        <button type="button" class="color-preset" style="background: #43e97b" onclick="setColor('#43e97b')"></button>
                        <button type="button" class="color-preset" style="background: #fa709a" onclick="setColor('#fa709a')"></button>
                        <button type="button" class="color-preset" style="background: #feca57" onclick="setColor('#feca57')"></button>
                        <button type="button" class="color-preset" style="background: #ff6b6b" onclick="setColor('#ff6b6b')"></button>
                        <button type="button" class="color-preset" style="background: #5f27cd" onclick="setColor('#5f27cd')"></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liên kết mạng xã hội -->
        <div class="form-section">
            <div class="section-header">
                <h2>🔗 Mạng xã hội</h2>
                <p>Liên kết đến các trang mạng xã hội của CLB</p>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="facebook">
                        <span class="social-icon">📘</span> Facebook
                    </label>
                    <input type="url" id="facebook" name="facebook" 
                           value="<?= htmlspecialchars($club_page['facebook'] ?? '') ?>"
                           placeholder="https://facebook.com/yourclub">
                </div>

                <div class="form-group">
                    <label for="instagram">
                        <span class="social-icon">📷</span> Instagram
                    </label>
                    <input type="url" id="instagram" name="instagram" 
                           value="<?= htmlspecialchars($club_page['instagram'] ?? '') ?>"
                           placeholder="https://instagram.com/yourclub">
                </div>

                <div class="form-group">
                    <label for="twitter">
                        <span class="social-icon">🐦</span> Twitter
                    </label>
                    <input type="url" id="twitter" name="twitter" 
                           value="<?= htmlspecialchars($club_page['twitter'] ?? '') ?>"
                           placeholder="https://twitter.com/yourclub">
                </div>

                <div class="form-group">
                    <label for="website">
                        <span class="social-icon">🌐</span> Website
                    </label>
                    <input type="url" id="website" name="website" 
                           value="<?= htmlspecialchars($club_page['website'] ?? $club['website'] ?? '') ?>"
                           placeholder="https://yourclub.com">
                </div>
            </div>
        </div>

        <!-- Cài đặt hiển thị -->
        <div class="form-section">
            <div class="section-header">
                <h2>⚙️ Cài đặt hiển thị</h2>
                <p>Tùy chọn hiển thị trang CLB</p>
            </div>
            
            <div class="form-group">
                <!-- Hidden input để đảm bảo giá trị luôn được gửi -->
                <input type="hidden" name="is_public" id="is_public_hidden" value="<?= ($club_page['is_public'] ?? 1) ? '1' : '0' ?>">
                <label class="checkbox-label">
                    <input type="checkbox" id="is_public_checkbox" 
                           <?= ($club_page['is_public'] ?? 1) ? 'checked' : '' ?>
                           onchange="document.getElementById('is_public_hidden').value = this.checked ? '1' : '0'">
                    <span>Công khai trang CLB</span>
                </label>
                <small>Cho phép mọi người xem trang chi tiết CLB</small>
            </div>
        </div>

        <!-- Nút hành động -->
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="location.href='Dashboard.php?id=<?= $club_id ?>'">
                ← Quay lại
            </button>
            <button type="button" class="btn-preview" onclick="window.open('club-detail.php?id=<?= $club_id ?>', '_blank')">
                👁️ Xem trước
            </button>
            <button type="submit" class="btn-primary">
                💾 Lưu thay đổi
            </button>
        </div>
    </form>
</div>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function setColor(color) {
    document.getElementById('primary_color').value = color;
    document.querySelector('.color-text').value = color;
}

// Sync color picker with text input
document.getElementById('primary_color').addEventListener('input', function() {
    document.querySelector('.color-text').value = this.value;
});

// Debug: Log form data before submit
document.querySelector('.appearance-form').addEventListener('submit', function(e) {
    const formData = new FormData(this);
    const clubIdInput = document.getElementById('club_id_input');
    
    // Đảm bảo club_id luôn có giá trị
    if (clubIdInput && (!clubIdInput.value || clubIdInput.value === '0')) {
        const urlParams = new URLSearchParams(window.location.search);
        const clubIdFromUrl = urlParams.get('id');
        if (clubIdFromUrl) {
            clubIdInput.value = clubIdFromUrl;
            console.log('Fixed club_id from URL:', clubIdFromUrl);
        }
    }
    
    console.log('=== Form Submit Debug ===');
    console.log('Club ID:', formData.get('club_id'));
    console.log('Club ID from input:', clubIdInput?.value);
    console.log('Slogan:', formData.get('slogan'));
    console.log('Description:', formData.get('description'));
    console.log('Primary Color:', formData.get('primary_color'));
    console.log('is_public:', formData.get('is_public'));
    console.log('Facebook:', formData.get('facebook'));
    console.log('Instagram:', formData.get('instagram'));
    console.log('Twitter:', formData.get('twitter'));
    console.log('Website:', formData.get('website'));
    console.log('Banner file:', formData.get('banner')?.name || 'No file');
    console.log('Avatar file:', formData.get('avatar')?.name || 'No file');
    
    // Đảm bảo is_public luôn có giá trị
    const isPublicCheckbox = document.getElementById('is_public_checkbox');
    const isPublicHidden = document.getElementById('is_public_hidden');
    if (isPublicCheckbox && isPublicHidden) {
        isPublicHidden.value = isPublicCheckbox.checked ? '1' : '0';
        console.log('is_public final value:', isPublicHidden.value);
    }
    
    // Kiểm tra nếu club_id vẫn là 0 thì ngăn submit
    if (!formData.get('club_id') || formData.get('club_id') === '0') {
        console.error('ERROR: club_id is missing or 0!');
        alert('Lỗi: Không tìm thấy ID câu lạc bộ. Vui lòng thử lại!');
        e.preventDefault();
        return false;
    }
});
</script>

<?php
load_footer();
?>
