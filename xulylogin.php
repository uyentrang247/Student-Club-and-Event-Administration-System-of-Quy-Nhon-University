<?php
// xulylogin.php - Authentication Functions
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/assets/database/connect.php';

// ================== ĐĂNG NHẬP ==================
function loginUser($username, $password, $remember = false) {
    global $conn;
    
    // Admin và user thường dùng chung form đăng nhập ở login.php
    // Hệ thống sẽ tự động redirect admin đến admin/dashboard.php sau khi đăng nhập
    
    $stmt = $conn->prepare("SELECT id, ho_ten, username, email, password, vai_tro, so_dien_thoai, avatar FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Kiểm tra mật khẩu: ưu tiên hash, tự động chuyển plain-text sang hash để vá lỗ hổng lưu mật khẩu trần
        $password_match = false;
        $is_hashed = substr($user['password'], 0, 4) === '$2y$';
        
        if ($is_hashed) {
            $password_match = password_verify($password, $user['password']);
        } elseif ($password === $user['password']) {
            $password_match = true;
            // Nâng cấp sang bcrypt ngay sau lần đăng nhập thành công
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $rehash_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $rehash_stmt->bind_param("si", $new_hash, $user['id']);
            $rehash_stmt->execute();
            $rehash_stmt->close();
        }
        
        if ($password_match) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['ho_ten']    = $user['ho_ten'] ?? '';
            $_SESSION['email']     = $user['email'] ?? '';
            $_SESSION['vai_tro']   = $user['vai_tro'] ?? UserRole::THANH_VIEN;
            $_SESSION['so_dien_thoai'] = $user['so_dien_thoai'] ?? '';
            $_SESSION['avatar']    = $user['avatar'] ?? '';
            $_SESSION['last_activity'] = time();
            
            // Nếu là admin, set thêm session admin
            if ($user['vai_tro'] === 'ADMIN') {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_name'] = $user['ho_ten'] ?? $user['username'];
                $_SESSION['admin_email'] = $user['email'] ?? '';
                $_SESSION['admin_avatar'] = $user['avatar'] ?? '';
            }
            
            // Xử lý "Ghi nhớ tôi"
            if ($remember) {
                $token = generate_random_string(64);
                $expiry = time() + (30 * 24 * 60 * 60); // 30 ngày
                
                // Lưu token vào database
                $stmt = $conn->prepare("UPDATE users SET remember_token = ?, remember_token_expiry = ? WHERE id = ?");
                $expiry_date = date('Y-m-d H:i:s', $expiry);
                $stmt->bind_param("ssi", $token, $expiry_date, $user['id']);
                $stmt->execute();
                $stmt->close();
                
                // Lưu token vào cookie
                setcookie('remember_token', $token, $expiry, "/", "", false, true);
                setcookie('remember_user', $user['id'], $expiry, "/", "", false, true);
            }
            
            // Log activity
            log_error("User login successful: " . $username, ['ip' => $_SERVER['REMOTE_ADDR']]);
            
            return true;
        }
    }
    return false;
}

// ================== TỰ ĐỘNG ĐĂNG NHẬP TỪ COOKIE ==================
function autoLoginFromCookie() {
    global $conn;
    
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
        $token = $_COOKIE['remember_token'];
        $user_id = $_COOKIE['remember_user'];
        
        $stmt = $conn->prepare("SELECT id, ho_ten, username, email, vai_tro, so_dien_thoai, avatar, remember_token_expiry FROM users WHERE id = ? AND remember_token = ?");
        $stmt->bind_param("is", $user_id, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Kiểm tra token còn hạn không
            if (strtotime($user['remember_token_expiry']) > time()) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['ho_ten']    = $user['ho_ten'] ?? '';
                $_SESSION['email']     = $user['email'] ?? '';
                $_SESSION['vai_tro']   = $user['vai_tro'] ?? UserRole::THANH_VIEN;
                $_SESSION['so_dien_thoai'] = $user['so_dien_thoai'] ?? '';
                $_SESSION['avatar']    = $user['avatar'] ?? '';
                $_SESSION['last_activity'] = time();
                
                // Nếu là admin, set thêm session admin
                if ($user['vai_tro'] === 'ADMIN') {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_name'] = $user['ho_ten'] ?? $user['username'];
                    $_SESSION['admin_email'] = $user['email'] ?? '';
                    $_SESSION['admin_avatar'] = $user['avatar'] ?? '';
                }
                
                return true;
            } else {
                // Token hết hạn, xóa cookie
                clearRememberCookie();
            }
        }
    }
    return false;
}

// ================== XÓA COOKIE GHI NHỚ ==================
function clearRememberCookie() {
    setcookie('remember_token', '', time() - 3600, "/");
    setcookie('remember_user', '', time() - 3600, "/");
}

// ================== KIỂM TRA USERNAME TỒN TẠI ==================
function usernameExists($username) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// ================== ĐĂNG KÝ USER ==================
function registerUser($username, $password) {
    global $conn;
    
    if (usernameExists($username)) {
        return "Tên đăng nhập đã tồn tại!";
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $vai_tro = UserRole::THANH_VIEN;
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, vai_tro) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $vai_tro);
    
    if ($stmt->execute()) {
        log_error("User registered: " . $username, ['ip' => $_SERVER['REMOTE_ADDR']]);
        return true;
    } else {
        log_error("Registration error: " . $conn->error, ['username' => $username]);
        return "Lỗi đăng ký: " . $conn->error;
    }
}

// ================== HOÀN THIỆN HỒ SƠ ==================
function completeUserProfile($username, $ho_ten, $email, $so_dien_thoai = '', $student_id = '', $class = '', $faculty = '', $gender = 'khac') {
    global $conn;
    
    // Kiểm tra email trùng (trừ chính người dùng)
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND username != ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return "Email này đã được sử dụng!";
    }
    
    $stmt = $conn->prepare("UPDATE users SET ho_ten = ?, email = ?, so_dien_thoai = ?, student_id = ?, class = ?, faculty = ?, gender = ? WHERE username = ?");
    $stmt->bind_param("ssssssss", $ho_ten, $email, $so_dien_thoai, $student_id, $class, $faculty, $gender, $username);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return "Lỗi cập nhật hồ sơ!";
    }
}

// ================== LOGOUT ==================
function logout() {
    global $conn;
    
    // Xóa remember token trong database nếu có
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("UPDATE users SET remember_token = NULL, remember_token_expiry = NULL WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        
        log_error("User logout: " . $_SESSION['username'], ['ip' => $_SERVER['REMOTE_ADDR']]);
    }
    
    // Xóa cookie
    clearRememberCookie();
    
    // Destroy session
    session_unset();
    session_destroy();

    // Redirect về trang đăng nhập với đường dẫn tuyệt đối, chuẩn hóa slash để tránh 403
    $appRoot = str_replace('\\', '/', APP_ROOT);
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $projectPath = ltrim(str_replace($docRoot, '', $appRoot), '/');
    $projectPath = $projectPath ? '/' . $projectPath : '';
    $loginUrl = $projectPath . '/login.php';
    header("Location: $loginUrl");
    exit();
}

function getUserInfo($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows === 1 ? $result->fetch_assoc() : null;
}
?>