<?php
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// NOW start session
session_start();

// Kiểm tra đăng nhập
require_login();

$page_css = "createCLB.css";
require 'site.php';
load_top();
load_header();

// Display flash messages
$flash = get_flash_message();
?>

<div class="create-club-wrapper">
    <div class="create-club-container">
        <!-- Header -->
        <div class="form-header">
            <div class="header-icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
            </div>
            <h2>Tạo câu lạc bộ mới</h2>
            <p class="form-subtitle">Điền thông tin để tạo câu lạc bộ của bạn</p>
        </div>

<?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo $flash['message']; ?>
        </div>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
        </div>
<?php endif; ?>
<?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
        </div>
<?php endif; ?>

        <form method="POST" action="createCLB_xuli.php" enctype="multipart/form-data" onsubmit="return validateForm()" class="create-form">
            <?php echo csrf_token_input(); ?>
            
            <!-- Logo Preview -->
            <div class="logo-preview-section">
                <div class="logo-preview" id="logoPreview">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <p>Logo câu lạc bộ</p>
                </div>
                <label class="upload-btn" for="fileUpload">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <span id="uploadText">Chọn ảnh logo</span>
                    <input type="file" id="fileUpload" name="logo" accept="image/*" required style="display: none;">
                </label>
            </div>

            <!-- Form Fields -->
            <div class="form-group">
                <label>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                    Tên câu lạc bộ
                </label>
                <input type="text" name="name" placeholder="VD: Câu lạc bộ Lập trình" maxlength="150" required>
            </div>

            <div class="form-group">
                <label>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    Mô tả
                </label>
                <textarea name="description" rows="4" placeholder="Giới thiệu về mục đích, hoạt động của câu lạc bộ..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                            <line x1="7" y1="7" x2="7.01" y2="7"></line>
                        </svg>
                        Lĩnh vực hoạt động
                    </label>
                    <select name="category" required>
                        <option value="">Chọn lĩnh vực</option>
                        <option value="Học thuật">📚 Học thuật</option>
                        <option value="Thể thao">⚽ Thể thao</option>
                        <option value="Nghệ thuật">🎨 Nghệ thuật</option>
                        <option value="Tình nguyện">❤️ Tình nguyện</option>
                        <option value="Kỹ năng">💡 Kỹ năng</option>
                        <option value="Khởi nghiệp">🚀 Khởi nghiệp</option>
                        <option value="Khác">🔖 Khác</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        Số thành viên dự kiến
                    </label>
                    <input type="number" name="total_members" min="1" placeholder="VD: 50">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Tạo câu lạc bộ
                </button>
                <a href="myclub.php" class="btn-cancel">
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
document.getElementById('fileUpload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('logoPreview');
    const uploadText = document.getElementById('uploadText');
    
    if (file) {
        // Hiển thị tên file
        uploadText.textContent = file.name;
        
        // Preview ảnh
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Logo preview" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">`;
        };
        reader.readAsDataURL(file);
    } else {
        uploadText.textContent = 'Chọn ảnh logo';
        preview.innerHTML = `
            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
            <p>Logo câu lạc bộ</p>
        `;
    }
});
</script>

<script>
// Validation form
function validateForm() {
    const fileInput = document.getElementById('fileUpload');
    
    if (fileInput.files.length === 0) {
        alert('Vui lòng chọn logo cho câu lạc bộ!');
        return false;
    }
    
    const file = fileInput.files[0];
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!allowedTypes.includes(file.type)) {
        alert('Chỉ chấp nhận file ảnh (JPEG, PNG, GIF, WebP).');
        return false;
    }
    
    if (file.size > maxSize) {
        alert('Kích thước file không được vượt quá 5MB.');
        return false;
    }
    
    return true;
}
</script>

<?php
load_footer();
?>