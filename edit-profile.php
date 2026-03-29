<?php
$page_css = "edit-profile.css";
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
$error_phone = '';
$error_student_id = '';

// Lấy thông tin user hiện tại
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $class_name = trim($_POST['class_name'] ?? '');
    $faculty = trim($_POST['faculty'] ?? '');
    $gender = $_POST['gender'] ?? 'other';
    
    // Validate
    if (empty($full_name)) {
        $error_message = "Vui lòng nhập họ tên!";
    } elseif (empty($email)) {
        $error_message = "Vui lòng nhập email!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Email không hợp lệ!";
    } elseif (!empty($phone) && !preg_match('/^0\d{9}$/', $phone)) {
        $error_message = "Số điện thoại phải gồm 10 số và bắt đầu bằng 0.";
        $error_phone = $error_message;
    } elseif (!empty($student_id) && !preg_match('/^\d+$/', $student_id)) {
        $error_message = "Mã sinh viên chỉ được chứa số.";
        $error_student_id = $error_message;
    } else {
        // Kiểm tra email trùng (trừ chính user này)
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = "Email này đã được sử dụng!";
        } else {
            // Update thông tin
            $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, student_id = ?, class_name = ?, faculty = ?, gender = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssi", $full_name, $email, $phone, $student_id, $class_name, $faculty, $gender, $user_id);
            
            if ($stmt->execute()) {
                // Update session
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                $_SESSION['phone'] = $phone;
                // Lấy avatar từ database để cập nhật session nếu có
                $avatar_sql = "SELECT avatar FROM users WHERE id = ?";
                $avatar_stmt = $conn->prepare($avatar_sql);
                if ($avatar_stmt) {
                    $avatar_stmt->bind_param("i", $user_id);
                    $avatar_stmt->execute();
                    $avatar_result = $avatar_stmt->get_result();
                    if ($avatar_data = $avatar_result->fetch_assoc()) {
                        $_SESSION['avatar'] = $avatar_data['avatar'] ?? '';
                    }
                    $avatar_stmt->close();
                }
                
                $success_message = "success";
                // Reload user data
                $user['full_name'] = $full_name;
                $user['email'] = $email;
                $user['phone'] = $phone;
                $user['student_id'] = $student_id;
                $user['class_name'] = $class_name;
                $user['faculty'] = $faculty;
                $user['gender'] = $gender;
            } else {
                $error_message = "Lỗi cập nhật thông tin!";
            }
        }
    }
}
?>

<!-- Success Modal -->
<div id="successModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="success-icon">✓</div>
        <h2>Cập nhật thành công!</h2>
        <p>Thông tin của bạn đã được cập nhật</p>
        <div class="redirect-text">Đang chuyển về trang hồ sơ...</div>
    </div>
</div>

<div class="edit-profile-container">
    <div class="page-header">
        <h1>Chỉnh sửa hồ sơ</h1>
        <p>Cập nhật thông tin cá nhân của bạn</p>
    </div>

    <div class="form-card">
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                ❌ <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Họ và tên *</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                           placeholder="Nhập họ và tên">
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                           placeholder="Nhập email">
                </div>
            </div>

            <div class="form-row">
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                       placeholder="Nhập số điện thoại"
                       pattern="0\d{9}"
                       inputmode="tel">

                <small id="phone-helper" class="error-text" style="display:none;margin-top:4px;"></small>
            </div>

                <div class="form-group">
                    <label for="student_id">Mã sinh viên</label>
                    <input type="text" id="student_id" name="student_id" 
                       value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>" 
                       placeholder="Nhập mã sinh viên"
                       pattern="\d*"
                       inputmode="numeric">
                       
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="class_name">Lớp</label>
                    <input type="text" id="class_name" name="class_name" 
                           value="<?php echo htmlspecialchars($user['class_name'] ?? ''); ?>" 
                           placeholder="Nhập lớp">
                </div>

                <div class="form-group">
                    <label for="faculty">Khoa</label>
                    <input type="text" id="faculty" name="faculty" 
                           value="<?php echo htmlspecialchars($user['faculty'] ?? ''); ?>" 
                           placeholder="Nhập khoa">
                </div>
            </div>

            <div class="form-group">
                <label>Giới tính</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="gender" value="male" 
                               <?php echo ($user['gender'] ?? '') === 'male' ? 'checked' : ''; ?>>
                        <span>Nam</span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="gender" value="female" 
                               <?php echo ($user['gender'] ?? '') === 'female' ? 'checked' : ''; ?>>
                        <span>Nữ</span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="gender" value="other" 
                               <?php echo ($user['gender'] ?? 'other') === 'other' ? 'checked' : ''; ?>>
                        <span>Khác</span>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="history.back()">Hủy</button>
                <button type="submit" class="btn-submit">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
const isSuccess = <?php echo ($success_message === 'success') ? 'true' : 'false'; ?>;

if (isSuccess) {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.style.display = 'flex';
        setTimeout(() => {
            window.location.href = 'profile.php';
        }, 2000);
    }
}


</script>

<?php
load_footer();
?>
