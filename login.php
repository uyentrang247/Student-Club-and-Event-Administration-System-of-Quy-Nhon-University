<?php 
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// NOW start session
session_start();

require_once __DIR__ . '/assets/database/connect.php';
require_once __DIR__ . '/xulylogin.php';

$page_css = "login.css";

// Kiểm tra tự động đăng nhập từ cookie
if (!isset($_SESSION['logged_in']) && autoLoginFromCookie()) {
    // Kiểm tra nếu là admin thì chuyển đến admin dashboard
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: trangchu.php");
    }
    exit();
}

// Nếu đã đăng nhập, chuyển đến trang tương ứng
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Kiểm tra nếu là admin thì chuyển đến admin dashboard
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: trangchu.php");
    }
    exit();
}

$page_type = 'login';
require('site.php'); 
load_top();

$username_error = "";
$password_error = "";
// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    // Rate limit để tránh brute force
    if (!check_rate_limit('login_attempt', 5, 300)) {
        $username_error = "Quá nhiều lần thử. Vui lòng thử lại sau vài phút.";
    } else {
    $username = sanitize_input(trim($_POST['username']));
    $password = $_POST['password'];
    
    if (empty($username)) {
        $username_error = "Tên đăng nhập không được bỏ trống!";
    }
    if (empty($password)) {
        $password_error = "Mật khẩu không được bỏ trống!";
    }

    if (empty($username_error) && empty($password_error)) {
        $remember = isset($_POST['remember']);
        $login_result = loginUser($username, $password, $remember);
        
        if ($login_result === true) {
            // Đăng nhập user thành công
            // Kiểm tra nếu là admin thì chuyển đến admin dashboard
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: trangchu.php");
            }
            exit();
        } else {
            // Sai tài khoản hoặc mật khẩu
            if (!usernameExists($username)) {
                $username_error = "Tài khoản không tồn tại. Vui lòng đăng ký!";
            } else {
                $password_error = "Tên đăng nhập hoặc mật khẩu không đúng!";
            }
        }
    }
}
}
?>
    <div class="container">
        <button class="back-btn" onclick="window.location.href = 'trangchu.php'">← Quay lại</button>
        
        <div class="login-box">
            <h1>LeaderClub</h1>
            <p class="subtitle">Đăng nhập vào tài khoản của bạn</p>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form class="login-form" method="POST" action="">
                <div class="input-group">
                    <label>Tên đăng nhập</label>
                    <input type="text" name="username" placeholder="Nhập tên đăng nhập" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">

                    <?php if (!empty($username_error)): ?>
                        <p class="input-error"><?php echo $username_error; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="input-group">
                    <label>Mật khẩu</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" autocomplete="current-password">
                        <img src="assets/img/eye-off.svg.png" class="eye-icon" id="eyeIcon" onclick="togglePassword()" alt="Hiển thị mật khẩu">
                    </div>
                    <?php if (!empty($password_error)): ?>
                        <p class="input-error"><?php echo $password_error; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="options">
                    <label class="remember">
                        <input type="checkbox" name="remember"> Ghi nhớ tôi
                    </label>
                    <a href="forgot-password.php" class="forgot-link">Quên mật khẩu?</a>
                </div>
                
                <button type="submit" name="login" class="login-btn">Đăng nhập</button>
                
                <div class="register">
                    Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function goBack() {
            window.history.back();
        }

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
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
            const eyeIcon = document.getElementById('eyeIcon');
            const passwordInput = document.getElementById('password');
            
            eyeIcon.style.display = 'none';
            
            passwordInput.addEventListener('input', function() {
                if (passwordInput.value.length > 0) {
                    eyeIcon.style.display = 'block';
                } else {
                    eyeIcon.style.display = 'none';
                }
            });
        });
    
    </script>
</body>
</html>