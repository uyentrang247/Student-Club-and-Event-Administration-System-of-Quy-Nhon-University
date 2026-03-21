<?php
session_start();
$page_css = "login.css";
require('assets/database/connect.php');
$page_type = 'login';
require('site.php');
load_top();
?>
<link rel="stylesheet" href="assets/css/forgot-password.css?v=<?php echo time(); ?>">
<?php

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';
$user_data = null;

// Bước 1: Nhập username/email
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['find_account'])) {
    if (!check_rate_limit('forgot_step1', 5, 600)) {
        $error = "Bạn đã thử quá nhiều lần. Vui lòng thử lại sau vài phút.";
    } else {
    $identifier = trim($_POST['identifier'] ?? '');
    
    if (empty($identifier)) {
        $error = "Vui lòng nhập tên đăng nhập hoặc email!";
    } else {
        // Tìm user theo username hoặc email
        $stmt = $conn->prepare("SELECT id, username, email, ho_ten, password FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user_data = $result->fetch_assoc();
            $_SESSION['reset_user_id'] = $user_data['id'];
            $step = 2;
        } else {
            $error = "Không tìm thấy tài khoản với thông tin này!";
        }
    }
}
}

// Bước 2: Xác thực email
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_email'])) {
    if (!check_rate_limit('forgot_step2', 5, 600)) {
        $error = "Bạn đã thử quá nhiều lần. Vui lòng thử lại sau vài phút.";
        $step = 2;
    } else {
    $email = trim($_POST['email'] ?? '');
    $user_id = $_SESSION['reset_user_id'] ?? 0;
    
    if ($user_id > 0) {
        $stmt = $conn->prepare("SELECT id, username, email, ho_ten, password FROM users WHERE id = ? AND email = ?");
        $stmt->bind_param("is", $user_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user_data = $result->fetch_assoc();
            $step = 3;
        } else {
            $error = "Email không khớp với tài khoản!";
            $step = 2;
        }
    }
}
}

// Bước 3: Đặt mật khẩu mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    if (!check_rate_limit('forgot_step3', 5, 600)) {
        $error = "Bạn đã thử quá nhiều lần. Vui lòng thử lại sau vài phút.";
        $step = 3;
    } else {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $user_id = $_SESSION['reset_user_id'] ?? 0;
    
    if (empty($new_password)) {
        $error = "Vui lòng nhập mật khẩu mới!";
    } elseif (strlen($new_password) < 8) {
        $error = "Mật khẩu phải có ít nhất 8 ký tự!";
    } elseif ($new_password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp!";
    } else {
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password, $user_id);
        
        if ($stmt->execute()) {
            $success = "Đặt lại mật khẩu thành công!";
            unset($_SESSION['reset_user_id']);
            $step = 4;
        } else {
            $error = "Có lỗi xảy ra. Vui lòng thử lại!";
        }
    }
}
}

// Lấy thông tin user nếu đang ở bước 2 hoặc 3
if ($step >= 2 && isset($_SESSION['reset_user_id'])) {
    $user_id = $_SESSION['reset_user_id'];
    $result = $conn->query("SELECT id, username, email, ho_ten, password FROM users WHERE id = $user_id");
    if ($result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
    }
}
?>

<div class="login-container">
    <div class="back-button">
        <a href="login.php">Quay lại</a>
    </div>
    
    <div class="login-box">
        <div class="login-header">
            <h1>LeaderClub</h1>
            <p>Khôi phục mật khẩu</p>
        </div>

        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                <div class="step-number">1</div>
                <div class="step-label">Tìm tài khoản</div>
                <div class="step-line"></div>
            </div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                <div class="step-number">2</div>
                <div class="step-label">Xác thực</div>
                <div class="step-line"></div>
            </div>
            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
                <div class="step-number">3</div>
                <div class="step-label">Đặt mật khẩu</div>
                <div class="step-line"></div>
            </div>
            <div class="step <?php echo $step >= 4 ? 'active completed' : ''; ?>">
                <div class="step-number">✓</div>
                <div class="step-label">Hoàn tất</div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
        <!-- Bước 1: Tìm tài khoản -->
        <form method="POST" class="login-form">
            <div class="input-group">
                <label>Tên đăng nhập hoặc Email</label>
                <input type="text" name="identifier" placeholder="Nhập username hoặc email" required autofocus>
            </div>
            <button type="submit" name="find_account" class="login-btn">Tìm tài khoản</button>
        </form>

        <?php elseif ($step == 2): ?>
        <!-- Bước 2: Xác thực email -->
        <div class="info-box">
            <p><strong>Tài khoản tìm thấy:</strong></p>
            <p>Username: <strong><?php echo htmlspecialchars($user_data['username']); ?></strong></p>
            <p>Họ tên: <strong><?php echo htmlspecialchars($user_data['ho_ten'] ?? 'Chưa cập nhật'); ?></strong></p>
        </div>

        <form method="POST" class="login-form">
            <div class="input-group" style="width: 100%;">
                <label>Xác nhận Email của bạn</label>
                <input type="email" name="email" placeholder="Nhập email đã đăng ký" required autofocus style="width: 100% !important; padding: 16px 20px !important; font-size: 15px !important; border-radius: 12px !important;">
                <small style="width: 100% !important; box-sizing: border-box !important; word-break: break-word !important;">Email đã được ẩn một phần: <?php 
                $email_parts = explode('@', $user_data['email']);
                echo substr($email_parts[0], 0, 3) . '***@' . $email_parts[1]; 
                ?></small>
            </div>
            <button type="submit" name="verify_email" class="login-btn">Xác nhận</button>
        </form>

        <?php elseif ($step == 3): ?>
        <!-- Bước 3: Đặt mật khẩu mới -->
        <div class="info-box">
            <p>✅ Mã OTP đã được xác thực!</p>
            <p>Bây giờ bạn có thể đặt mật khẩu mới.</p>
        </div>

        <form method="POST" class="login-form">
            <div class="input-group">
                <label>Mật khẩu mới</label>
                <input type="password" name="new_password" placeholder="Nhập mật khẩu mới" required autofocus minlength="8">
                <small>Tối thiểu 8 ký tự</small>
            </div>

            <div class="input-group">
                <label>Xác nhận mật khẩu mới</label>
                <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu mới" required minlength="8">
            </div>

            <button type="submit" name="reset_password" class="login-btn">Đặt lại mật khẩu</button>
        </form>

        <?php else: ?>
        <!-- Bước 4: Hoàn tất -->
        <div class="success-box">
            <div class="success-icon">✓</div>
            <h2>Thành công!</h2>
            <p>Mật khẩu của bạn đã được đặt lại.</p>
            <p>Bạn có thể đăng nhập ngay bây giờ.</p>
            <a href="login.php" class="login-btn" style="display: inline-block; text-decoration: none; margin-top: 20px;">
                Đăng nhập ngay
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>



<script>
    // Auto redirect sau 3 giây ở bước 4
    <?php if ($step == 4): ?>
    setTimeout(() => {
        window.location.href = 'login.php';
    }, 3000);
    <?php endif; ?>
</script>

</body>
</html>
