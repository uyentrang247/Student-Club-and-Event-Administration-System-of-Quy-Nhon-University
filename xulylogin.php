<?php
// xulylogin.php - Authentication Functions
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/assets/database/connect.php';

// ================== ĐĂNG NHẬP ==================
function loginUser($username, $password, $remember = false) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, full_name, username, email, password, role, phone, avatar FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Kiểm tra mật khẩu
        $password_match = false;
        $is_hashed = substr($user['password'], 0, 4) === '$2y$';
        
        if ($is_hashed) {
            $password_match = password_verify($password, $user['password']);
        } elseif ($password === $user['password']) {
            $password_match = true;
            // Nâng cấp sang bcrypt
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
            $_SESSION['full_name'] = $user['full_name'] ?? '';
            $_SESSION['email']     = $user['email'] ?? '';
            $_SESSION['role']      = $user['role'] ?? 'member';
            $_SESSION['phone']     = $user['phone'] ?? '';
            $_SESSION['avatar']    = $user['avatar'] ?? '';
            $_SESSION['last_activity'] = time();
            
            // Nếu là admin, set thêm session admin
            if ($user['role'] === 'admin') {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_name'] = $user['full_name'] ?? $user['username'];
                $_SESSION['admin_email'] = $user['email'] ?? '';
                $_SESSION['admin_avatar'] = $user['avatar'] ?? '';
            }
            
            // Xử lý "Ghi nhớ tôi"
            if ($remember) {
                $token = generate_random_string(64);
                $expiry = time() + (30 * 24 * 60 * 60);
                
                $stmt = $conn->prepare("UPDATE users SET remember_token = ?, remember_expiry = ? WHERE id = ?");
                $expiry_date = date('Y-m-d H:i:s', $expiry);
                $stmt->bind_param("ssi", $token, $expiry_date, $user['id']);
                $stmt->execute();
                $stmt->close();
                
            
                setcookie('remember_token', $token, $expiry, "/", "", false, true);
                setcookie('remember_user', $user['id'], $expiry, "/", "", false, true);
            }
            
           
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
        
        $stmt = $conn->prepare("SELECT id, full_name, username, email, role, phone, avatar, remember_expiry FROM users WHERE id = ? AND remember_token = ?");
        $stmt->bind_param("is", $user_id, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (strtotime($user['remember_expiry']) > time()) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'] ?? '';
                $_SESSION['email']     = $user['email'] ?? '';
                $_SESSION['role']      = $user['role'] ?? 'member';
                $_SESSION['phone']     = $user['phone'] ?? '';
                $_SESSION['avatar']    = $user['avatar'] ?? '';
                $_SESSION['last_activity'] = time();
                
                if ($user['role'] === 'admin') {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_name'] = $user['full_name'] ?? $user['username'];
                    $_SESSION['admin_email'] = $user['email'] ?? '';
                    $_SESSION['admin_avatar'] = $user['avatar'] ?? '';
                }
                
                return true;
            
                } else {
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
    
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'member';
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $role);
    
    if ($stmt->execute()) {
        log_error("User registered: " . $username, ['ip' => $_SERVER['REMOTE_ADDR']]);
        return true;
    } else {
        log_error("Registration error: " . $conn->error, ['username' => $username]);
        return "Lỗi đăng ký: " . $conn->error;
    }
}

// ================== HOÀN THIỆN HỒ SƠ ==================
function completeUserProfile($username, $full_name, $email, $phone = '', $student_id = '', $class_name = '', $faculty = '', $gender = 'other') {
    global $conn;
    
    // Kiểm tra email trùng
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND username != ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return "Email này đã được sử dụng!";
    }
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, student_id = ?, class_name = ?, faculty = ?, gender = ? WHERE username = ?");
    $stmt->bind_param("ssssssss", $full_name, $email, $phone, $student_id, $class_name, $faculty, $gender, $username);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return "Lỗi cập nhật hồ sơ!";
    }
}

// ================== LOGOUT ==================
function logout() {
    global $conn;
    
    
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("UPDATE users SET remember_token = NULL, remember_expiry = NULL WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        
        log_error("User logout: " . $_SESSION['username'], ['ip' => $_SERVER['REMOTE_ADDR']]);
    }
    
    
    clearRememberCookie();
    
    
    session_unset();
    session_destroy();

    
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