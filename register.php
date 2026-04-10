<?php 
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// NOW start session
session_start();

require_once __DIR__ . '/assets/database/connect.php';
require_once __DIR__ . '/xulylogin.php';

$page_css = "register.css";
$page_type = 'login';
require('site.php'); 
load_top();

// Chỉ xóa session tạm khi người dùng vào lại trang đăng ký mà không qua form POST
if (isset($_SESSION['temp_username']) && basename($_SERVER['PHP_SELF']) == 'register.php' && empty($_POST)) {
    unset($_SESSION['temp_username']);
    unset($_SESSION['temp_password']);
    unset($_SESSION['registration_time']);
}

$success_message = '';
$error_message = '';

// Xử lý đăng ký
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Rate limit đăng ký để tránh spam
    if (!check_rate_limit('register_attempt', 5, 600)) {
        $error_message = 'Bạn đã thử quá nhiều lần. Vui lòng thử lại sau vài phút.';
    } else {
    $username = sanitize_input(trim($_POST['username']));
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Validate
    $usernameRegex = '/^[a-zA-Z0-9]+$/';
    if (!preg_match($usernameRegex, $username)) {
        $error_message = 'Tên đăng nhập chỉ được dùng chữ và số!';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error_message = 'Mật khẩu phải có ít nhất ' . PASSWORD_MIN_LENGTH . ' ký tự!';
    } elseif ($password !== $confirmPassword) {
        $error_message = 'Mật khẩu nhập lại không khớp!';
    } else {
        // Đăng ký user
        $result = registerUser($username, $password);

        if ($result === true) {
            $_SESSION['temp_username'] = $username;
            $_SESSION['temp_password'] = $password;
            $_SESSION['registration_time'] = time();
            
            // HIỆN THÔNG BÁO + TỰ ĐỘNG CHUYỂN SAU 2 GIÂY
            $success_message = 'Đăng ký thành công! Đang chuyển đến hoàn thiện hồ sơ...';
        } else {
            $error_message = $result;
        }
    }
}
}
?>

<div class="container">
    <button class="back-btn" onclick="window.location.href = 'trangchu.php'">← Quay lại</button>
        
    <div class="register-box">
        <h1>Tạo tài khoản LeaderClub</h1>
            
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message">
                <strong><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></strong>
                <div style="margin-top:15px; font-size:14px; color:#155724;">
                    Đang chuyển tự động trong <span id="countdown">2</span> giây...
                </div>
            </div>

            <!-- TỰ ĐỘNG CHUYỂN SAU 2 GIÂY -->
            <script>
                let seconds = 2;
                const countdown = document.getElementById('countdown');
                const timer = setInterval(() => {
                    seconds--;
                    countdown.textContent = seconds;
                    if (seconds <= 0) {
                        clearInterval(timer);
                        window.location.href = "complete_profile.php";
                    }
                }, 1000);
            </script>
        <?php endif; ?>

        <?php if (!$success_message): ?>
            <form class="register-form" method="POST" action="" id="registerForm" novalidate>
                <div id="clientError" class="error-message" style="display:none"></div>
                <div class="input-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           pattern="[A-Za-z0-9]+"
                           minlength="4"
                           maxlength="30"
                           inputmode="latin"
                           autocomplete="username"
                           title="4-30 ký tự, chỉ dùng chữ và số"
                           required>
                    <div class="input-note">Chỉ dùng chữ và số (4-30 ký tự)</div>
                </div>
                
                <div class="input-group">
                    <label for="password">Mật khẩu</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" autocomplete="new-password"
                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" maxlength="64" required>
                        <img src="assets/img/eye-off.svg.png" class="eye-icon" id="eyeIcon1" onclick="togglePassword('password', 'eyeIcon1')">
                    </div>
                    <div class="input-note">Ít nhất 8 ký tự</div>
                </div>
                
                <div class="input-group">
                    <label for="confirmPassword">Nhập lại mật khẩu</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Nhập lại mật khẩu" autocomplete="new-password"
                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" maxlength="64" required>
                        <img src="assets/img/eye-off.svg.png" class="eye-icon" id="eyeIcon2" onclick="togglePassword('confirmPassword', 'eyeIcon2')">
                    </div>
                    <div id="confirmError" class="error-message" style="display:none"></div>
                </div>
                
                <div class="divider"></div>
                
                <button type="submit" class="submit-btn">Tạo tài khoản</button>
                
                <div class="login-link">
                    Bạn đã có tài khoản? <a href="login.php">Đăng nhập</a>
                </div>
            </form>
        <?php endif;  ?>
    </div>
</div>

<script>
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
    
    // Hàm kiểm tra và hiện/ẩn icon mắt
    function toggleEyeIconVisibility(inputId, eyeIconId) {
        const passwordInput = document.getElementById(inputId);
        const eyeIcon = document.getElementById(eyeIconId);
        
        if (passwordInput.value.length > 0) {
            eyeIcon.style.display = 'block';
        } else {
            eyeIcon.style.display = 'none';
        }
    }
        
    document.addEventListener('DOMContentLoaded', function() {
        // Ẩn tất cả icon mắt ban đầu
        const eyeIcons = document.querySelectorAll('.eye-icon');
        eyeIcons.forEach(icon => {
            icon.style.display = 'none';
        });
        
        // Thêm event listener cho các ô password
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                toggleEyeIconVisibility('password', 'eyeIcon1');
            });
        }
        
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                toggleEyeIconVisibility('confirmPassword', 'eyeIcon2');
            });
        }

        // Client-side form validation
        const form = document.getElementById('registerForm');
        const clientError = document.getElementById('clientError');
        const confirmError = document.getElementById('confirmError');
        if (form) {
            form.addEventListener('submit', function(e) {
                clientError.style.display = 'none';
                clientError.textContent = '';
                if (confirmError) {
                    confirmError.style.display = 'none';
                    confirmError.textContent = '';
                }

                const username = (document.getElementById('username')?.value || '').trim();
                const pw = passwordInput?.value || '';
                const cpw = confirmPasswordInput?.value || '';
                const minLen = parseInt(<?php echo PASSWORD_MIN_LENGTH; ?>, 10) || 8;
                const usernameRegex = /^[A-Za-z0-9]+$/;

                if (!username) {
                    e.preventDefault();
                    clientError.textContent = 'Vui lòng nhập tên đăng nhập.';
                } else if (!pw) {
                    e.preventDefault();
                    clientError.textContent = 'Vui lòng nhập mật khẩu.';
                } else if (!cpw) {
                    e.preventDefault();
                    clientError.textContent = 'Vui lòng nhập lại mật khẩu.';
                } else if (!usernameRegex.test(username) || username.length < 4 || username.length > 30) {
                    e.preventDefault();
                    clientError.textContent = 'Tên đăng nhập phải 4-30 ký tự, chỉ gồm chữ và số.';
                } else if (pw.length < minLen) {
                    e.preventDefault();
                    clientError.textContent = `Mật khẩu phải có ít nhất ${minLen} ký tự.`;
                } else if (pw !== cpw) {
                    e.preventDefault();
                    clientError.textContent = 'Mật khẩu nhập lại không khớp.';
                    if (confirmError) {
                        confirmError.textContent = 'Mật khẩu nhập lại không khớp.';
                        confirmError.style.display = 'block';
                    }
                }

                if (clientError.textContent) {
                    clientError.style.display = 'block';
                    clientError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        }
    });
</script>

</body>
</html>