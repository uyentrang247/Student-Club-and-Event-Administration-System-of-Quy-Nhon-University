<?php
$page_css = "change-password.css";
require 'site.php';
load_top();
load_header();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require('assets/database/connect.php');
$user_id = $_SESSION['user_id'];

$success_message = '';
$error_message = '';
$old_password_error = '';
$new_password_error = '';
$confirm_password_error = '';

// Kiểm tra nếu vừa đổi mật khẩu thành công (từ URL parameter)
if (isset($_GET['success']) && $_GET['success'] === '1') {
    $success_message = "success";
}

// Chỉ xử lý POST nếu không phải redirect success
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_GET['success'])) {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate
    if (empty($old_password)) {
        $old_password_error = "Vui lòng nhập mật khẩu hiện tại!";
    }
    
    if (empty($new_password)) {
        $new_password_error = "Vui lòng nhập mật khẩu mới!";
    } elseif (strlen($new_password) < 8) {
        $new_password_error = "Mật khẩu mới phải có ít nhất 8 ký tự!";
    }
    
    if (empty($confirm_password)) {
        $confirm_password_error = "Vui lòng xác nhận mật khẩu mới!";
    } elseif ($new_password !== $confirm_password) {
        $confirm_password_error = "Mật khẩu xác nhận không khớp!";
    }
    
    // Nếu không có lỗi validation
    if (empty($old_password_error) && empty($new_password_error) && empty($confirm_password_error)) {
        // Kiểm tra mật khẩu cũ
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        // Kiểm tra cả 2 cách: hash và plain text (để tương thích với login)
        $password_match = ($old_password === $user['password']) || password_verify($old_password, $user['password']);
        
        if ($password_match) {
            // Mật khẩu cũ đúng, cập nhật mật khẩu mới
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "success";
            } else {
                $error_message = "Lỗi cập nhật mật khẩu!";
            }
        } else {
            $old_password_error = "Mật khẩu hiện tại không đúng!";
        }
    }
}
?>

<!-- Success Modal -->
<div id="successModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="success-icon">✓</div>
        <h2>Thành công!</h2>
        <p>Mật khẩu của bạn đã được đổi thành công</p>
        <div class="redirect-text">Đang chuyển về trang cài đặt...</div>
    </div>
</div>

<div class="change-password-container">
    <div class="page-header">
        <h1>Đổi mật khẩu</h1>
        <p>Cập nhật mật khẩu để bảo mật tài khoản của bạn</p>
    </div>

    <div class="form-card">

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                ❌ <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="old_password">Mật khẩu hiện tại *</label>
                <div class="password-wrapper">
                    <input type="password" id="old_password" name="old_password" 
                           placeholder="Nhập mật khẩu hiện tại" autocomplete="current-password">
                    <img src="assets/img/eye-off.svg.png" class="eye-icon" id="eyeIcon1" 
                         onclick="togglePassword('old_password', 'eyeIcon1')">
                </div>
                <?php if ($old_password_error): ?>
                    <span class="error-text"><?php echo $old_password_error; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="new_password">Mật khẩu mới *</label>
                <div class="password-wrapper">
                    <input type="password" id="new_password" name="new_password" 
                           placeholder="Nhập mật khẩu mới" autocomplete="new-password">
                    <img src="assets/img/eye-off.svg.png" class="eye-icon" id="eyeIcon2" 
                         onclick="togglePassword('new_password', 'eyeIcon2')">
                </div>
                <div class="input-note">Ít nhất 8 ký tự</div>
                <?php if ($new_password_error): ?>
                    <span class="error-text"><?php echo $new_password_error; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu mới *</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Nhập lại mật khẩu mới" autocomplete="new-password">
                    <img src="assets/img/eye-off.svg.png" class="eye-icon" id="eyeIcon3" 
                         onclick="togglePassword('confirm_password', 'eyeIcon3')">
                </div>
                <?php if ($confirm_password_error): ?>
                    <span class="error-text"><?php echo $confirm_password_error; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="history.back()">Hủy</button>
                <button type="submit" class="btn-submit">Đổi mật khẩu</button>
            </div>
        </form>
    </div>
</div>

<script>
const isSuccess = <?php echo ($success_message === 'success') ? 'true' : 'false'; ?>;

function togglePassword(inputId, eyeIconId) {
    const passwordInput = document.getElementById(inputId);
    const eyeIcon = document.getElementById(eyeIconId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.src = 'assets/img/eye.svg.png';
    } else {
        passwordInput.type = 'password';
        eyeIcon.src = 'assets/img/eye-off.svg.png';
    }
}

// Ẩn icon mắt ban đầu
document.addEventListener('DOMContentLoaded', function() {
    const eyeIcons = document.querySelectorAll('.eye-icon');
    eyeIcons.forEach(icon => icon.style.display = 'none');
    
    // Hiện icon khi có input
    ['old_password', 'new_password', 'confirm_password'].forEach(id => {
        const input = document.getElementById(id);
        const iconId = id === 'old_password' ? 'eyeIcon1' : (id === 'new_password' ? 'eyeIcon2' : 'eyeIcon3');
        
        input.addEventListener('input', function() {
            const icon = document.getElementById(iconId);
            icon.style.display = this.value.length > 0 ? 'block' : 'none';
        });
    });
    
    // Hiển thị modal nếu thành công
    if (isSuccess) {
        const modal = document.getElementById('successModal');
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => {
                window.location.href = 'settings.php';
            }, 2000);
        }
    }
});
</script>

<?php
load_footer();
?>
