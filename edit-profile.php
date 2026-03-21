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
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $so_dien_thoai = trim($_POST['so_dien_thoai'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $class = trim($_POST['class'] ?? '');
    $faculty = trim($_POST['faculty'] ?? '');
    $gender = $_POST['gender'] ?? 'khac';
    
    // Validate
    if (empty($ho_ten)) {
        $error_message = "Vui lòng nhập họ tên!";
    } elseif (empty($email)) {
        $error_message = "Vui lòng nhập email!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Email không hợp lệ!";
    } elseif (!empty($so_dien_thoai) && !preg_match('/^0\d{9}$/', $so_dien_thoai)) {
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
            $sql = "UPDATE users SET ho_ten = ?, email = ?, so_dien_thoai = ?, student_id = ?, class = ?, faculty = ?, gender = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssi", $ho_ten, $email, $so_dien_thoai, $student_id, $class, $faculty, $gender, $user_id);
            
            if ($stmt->execute()) {
                // Update session
                $_SESSION['ho_ten'] = $ho_ten;
                $_SESSION['email'] = $email;
                $_SESSION['so_dien_thoai'] = $so_dien_thoai;
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
                $user['ho_ten'] = $ho_ten;
                $user['email'] = $email;
                $user['so_dien_thoai'] = $so_dien_thoai;
                $user['student_id'] = $student_id;
                $user['class'] = $class;
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

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="ho_ten">Họ và tên *</label>
                    <input type="text" id="ho_ten" name="ho_ten" 
                           value="<?php echo htmlspecialchars($user['ho_ten'] ?? ''); ?>" 
                           placeholder="Nhập họ và tên" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                           placeholder="Nhập email" required>
                </div>
            </div>

            <div class="form-row">
            <div class="form-group">
                <label for="so_dien_thoai">Số điện thoại</label>
                <input type="tel" id="so_dien_thoai" name="so_dien_thoai" 
                       value="<?php echo htmlspecialchars($user['so_dien_thoai'] ?? ''); ?>" 
                       placeholder="Nhập số điện thoại"
                       pattern="0\d{9}"
                       inputmode="tel">
                <?php if (!empty($error_phone)): ?>
                    <span class="error-text"><?php echo htmlspecialchars($error_phone); ?></span>
                <?php endif; ?>
                <small id="phone-helper" class="error-text" style="display:none;margin-top:4px;"></small>
            </div>

                <div class="form-group">
                    <label for="student_id">Mã sinh viên</label>
                    <input type="text" id="student_id" name="student_id" 
                       value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>" 
                       placeholder="Nhập mã sinh viên"
                       pattern="\d*"
                       inputmode="numeric">
                <?php if (!empty($error_student_id)): ?>
                    <span class="error-text"><?php echo htmlspecialchars($error_student_id); ?></span>
                <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="class">Lớp</label>
                    <input type="text" id="class" name="class" 
                           value="<?php echo htmlspecialchars($user['class'] ?? ''); ?>" 
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
                        <input type="radio" name="gender" value="nam" 
                               <?php echo ($user['gender'] ?? '') === 'nam' ? 'checked' : ''; ?>>
                        <span>Nam</span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="gender" value="nu" 
                               <?php echo ($user['gender'] ?? '') === 'nu' ? 'checked' : ''; ?>>
                        <span>Nữ</span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="gender" value="khac" 
                               <?php echo ($user['gender'] ?? 'khac') === 'khac' ? 'checked' : ''; ?>>
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

// Client-side validate phone number
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.getElementById('so_dien_thoai');
    const phoneHelper = document.getElementById('phone-helper');
    const studentInput = document.getElementById('student_id');
    const studentHelper = document.createElement('small');
    if (studentInput) {
        studentHelper.className = 'error-text';
        studentHelper.style.display = 'none';
        studentHelper.style.marginTop = '4px';
        studentInput.parentNode.appendChild(studentHelper);
    }
    const form = phoneInput ? phoneInput.closest('form') : null;
    const msgInvalid = 'Số điện thoại phải có 10 số và bắt đầu bằng 0.';
    const regex = /^0\d{9}$/;
    const msgStudentInvalid = 'Mã sinh viên chỉ được chứa số.';

    function validatePhone() {
        if (!phoneInput || !phoneHelper) return true;
        const val = phoneInput.value.trim();
        if (!val) {
            phoneHelper.style.display = 'none';
            return true;
        }
        const ok = regex.test(val);
        phoneHelper.textContent = ok ? '' : msgInvalid;
        phoneHelper.style.display = ok ? 'none' : 'block';
        return ok;
    }

    function validateStudent() {
        if (!studentInput || !studentHelper) return true;
        const val = studentInput.value.trim();
        if (!val) {
            studentHelper.style.display = 'none';
            return true;
        }
        const ok = /^\d+$/.test(val);
        studentHelper.textContent = ok ? '' : msgStudentInvalid;
        studentHelper.style.display = ok ? 'none' : 'block';
        return ok;
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', validatePhone);
        phoneInput.addEventListener('blur', validatePhone);
    }
    if (studentInput) {
        studentInput.addEventListener('input', validateStudent);
        studentInput.addEventListener('blur', validateStudent);
    }
    if (form) {
        form.addEventListener('submit', function(e) {
            const phoneOk = validatePhone();
            const stuOk = validateStudent();
            if (!phoneOk || !stuOk) {
                e.preventDefault();
                if (!phoneOk && phoneInput) phoneInput.focus();
                else if (!stuOk && studentInput) studentInput.focus();
            }
        });
    }
});
</script>

<?php
load_footer();
?>
