<?php
$page_css = "profile.css";
require 'site.php';
load_top();
load_header();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require('assets/database/connect.php');
$user_id = $_SESSION['user_id'];

$success_message = '';
$error_message = '';

// Lấy thông tin user TRƯỚC khi xử lý upload để có thể xóa avatar cũ
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Xử lý upload avatar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    
    // Kiểm tra lỗi upload
    if ($file['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $file['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Kiểm tra định dạng file
        if (in_array($filetype, $allowed)) {
            // Kiểm tra kích thước (max 5MB)
            if ($file['size'] <= 5000000) {
                // Tạo tên file unique
                $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $filetype;
                $upload_path = 'assets/img/avatars/' . $new_filename;
                
                // Tạo thư mục nếu chưa có
                if (!file_exists('assets/img/avatars')) {
                    mkdir('assets/img/avatars', 0777, true);
                }
                
                // Upload file
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Xóa avatar cũ nếu có
                    if (!empty($user['avatar']) && file_exists($user['avatar'])) {
                        unlink($user['avatar']);
                    }
                    
                    // Cập nhật database
                    $sql = "UPDATE users SET avatar = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $upload_path, $user_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Cập nhật avatar thành công!";
                        $_SESSION['avatar'] = $upload_path;
                        // Cập nhật lại thông tin user để hiển thị avatar mới
                        $user['avatar'] = $upload_path;
                    } else {
                        $error_message = "Lỗi cập nhật database!";
                    }
                    $stmt->close();
                } else {
                    $error_message = "Lỗi upload file!";
                }
            } else {
                $error_message = "File quá lớn! Tối đa 5MB.";
            }
        } else {
            $error_message = "Chỉ chấp nhận file JPG, JPEG, PNG, GIF!";
        }
    } else {
        $error_message = "Lỗi upload file!";
    }
}
?>

<div class="profile-container">
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <div class="profile-header">
        <?php
            $full_name_safe = $user['full_name'] ?? $user['ho_ten'] ?? '';
            $username_safe = $user['username'] ?? '';
            $avatar_path   = $user['avatar'] ?? '';
            $avatar_letter = strtoupper(substr($full_name_safe ?: 'U', 0, 1));
        ?>
        <div class="avatar-upload-wrapper">
            <?php if (!empty($avatar_path) && file_exists($avatar_path)): ?>
                <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Avatar" class="profile-avatar-large">
            <?php else: ?>
                <div class="profile-avatar-large">
                    <?php echo $avatar_letter; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="avatarForm">
                <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display: none;">
                <button type="button" class="btn-change-avatar" onclick="document.getElementById('avatarInput').click()">
                    📷 Đổi ảnh
                </button>
            </form>
        </div>
        
        <h1><?php echo htmlspecialchars($full_name_safe ?: 'Chưa cập nhật'); ?></h1>
        <p class="username">@<?php echo htmlspecialchars($username_safe ?: ''); ?></p>
    </div>

    <div class="profile-content">
        <div class="info-card">
            <h2>Thông tin cá nhân</h2>
            <div class="info-grid">
                <div class="info-item">
                    <?php $email_safe = $user['email'] ?? 'Chưa cập nhật'; ?>
                    <span class="info-label">📧 Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($email_safe); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">📱 Số điện thoại</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['phone'] ?? $user['so_dien_thoai'] ?? 'Chưa cập nhật'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">🎓 Mã sinh viên</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['student_id'] ?? 'Chưa cập nhật'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">🏫 Lớp</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['class_name'] ?? $user['class'] ?? 'Chưa cập nhật'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">📚 Khoa</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['faculty'] ?? 'Chưa cập nhật'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">👤 Giới tính</span>
                    <span class="info-value">
                        <?php 
                            $gender = $user['gender'] ?? 'other';
                            if ($gender === 'male') echo 'Nam';
                            elseif ($gender === 'female') echo 'Nữ';
                            else echo 'Khác';
                        ?>
                    </span>
                </div>
            </div>
            <button class="btn-edit" onclick="location.href='edit-profile.php'">Chỉnh sửa hồ sơ</button>
        </div>
    </div>
</div>

<script>
document.getElementById('avatarInput').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        // Preview ảnh trước khi upload
        const reader = new FileReader();
        reader.onload = function(e) {
            const avatarElement = document.querySelector('.profile-avatar-large');
            if (avatarElement.tagName === 'IMG') {
                avatarElement.src = e.target.result;
            } else {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'profile-avatar-large';
                avatarElement.parentNode.replaceChild(img, avatarElement);
            }
        }
        reader.readAsDataURL(this.files[0]);
        
        // Auto submit form
        document.getElementById('avatarForm').submit();
    }
});
</script>

<?php
load_footer();
?>
