<?php
/**
 * Common Functions
 * Các hàm tiện ích dùng chung trong toàn hệ thống
 */

// Prevent multiple includes
if (defined('FUNCTIONS_LOADED')) {
    return;
}
define('FUNCTIONS_LOADED', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/constants.php';

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Vietnam format)
 */
function validate_phone($phone) {
    return preg_match('/^(0|\+84)[0-9]{9}$/', $phone);
}

/**
 * Generate CSRF Token
 */
function generate_csrf_token() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF Token HTML Input
 */
function csrf_token_input() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
}

/**
 * Redirect with message
 */
function redirect($url, $message = null, $type = 'info') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

/**
 * Get flash message
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Try auto login from remember cookie (used before redirect)
 */
function try_auto_login_from_cookie() {
    if (is_logged_in()) {
        return true;
    }
    // Tải hàm autoLoginFromCookie nếu chưa có
    if (!function_exists('autoLoginFromCookie')) {
        $path = dirname(__DIR__) . '/xulylogin.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
    if (function_exists('autoLoginFromCookie')) {
        return autoLoginFromCookie();
    }
    return false;
}

/**
 * Require login
 */
function require_login() {
    if (is_logged_in()) return;
    if (try_auto_login_from_cookie()) return;
    redirect('login.php', 'Vui lòng đăng nhập để tiếp tục', 'warning');
}

/**
 * Check if user is admin
 */
function is_admin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require admin
 */
function require_admin() {
    if (is_admin()) return;
    // Thử auto login nếu có cookie
    if (!is_logged_in() && try_auto_login_from_cookie() && is_admin()) return;
    redirect('login.php', 'Vui lòng đăng nhập với quyền admin', 'warning');
}

/**
 * Get club ID from request
 */
function get_club_id() {
    if (isset($_GET['id']) && is_numeric($_GET['id']) && (int)$_GET['id'] > 0) {
        $club_id = (int)$_GET['id'];
        $_SESSION['club_id'] = $club_id;
        return $club_id;
    } elseif (isset($_SESSION['club_id']) && is_numeric($_SESSION['club_id']) && (int)$_SESSION['club_id'] > 0) {
        return (int)$_SESSION['club_id'];
    }
    return 0;
}

/**
 * Validate file upload
 */
function validate_file_upload($file, $allowed_types = ALLOWED_IMAGE_TYPES, $max_size = UPLOAD_MAX_SIZE) {
    $errors = [];
    
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Lỗi upload file';
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $errors[] = 'File quá lớn. Tối đa ' . ($max_size / 1024 / 1024) . 'MB';
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        $errors[] = 'Loại file không được phép';
    }
    
    // Check extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXTENSIONS)) {
        $errors[] = 'Phần mở rộng file không hợp lệ';
    }
    
    return $errors;
}

/**
 * Upload file safely
 */
function upload_file($file, $destination_dir, $prefix = '') {
    $errors = validate_file_upload($file);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Create directory if not exists
    if (!is_dir($destination_dir)) {
        mkdir($destination_dir, 0755, true);
    }
    
    // Generate unique filename
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $prefix . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $filepath = $destination_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'path' => $filepath, 'filename' => $filename];
    }
    
    return ['success' => false, 'errors' => ['Không thể lưu file']];
}

/**
 * Delete file safely
 */
function delete_file($filepath) {
    if (file_exists($filepath) && is_file($filepath)) {
        return @unlink($filepath);
    }
    return false;
}

/**
 * Format datetime for display
 */
if (!function_exists('format_datetime')) {
    function format_datetime($datetime, $format = 'd/m/Y H:i') {
        if (empty($datetime)) return '';
        return date($format, strtotime($datetime));
    }
}

/**
 * Format datetime for input
 */
if (!function_exists('format_datetime_input')) {
    function format_datetime_input($datetime) {
        if (empty($datetime)) return '';
        return date('Y-m-d\TH:i', strtotime($datetime));
    }
}

/**
 * Get user role in club (Database mới: bảng members)
 */
function get_user_role_in_club($conn, $user_id, $club_id) {
    $stmt = $conn->prepare("SELECT role FROM members WHERE user_id = ? AND club_id = ? AND status = ?");
    $status = MemberStatus::ACTIVE;
    $stmt->bind_param("iis", $user_id, $club_id, $status);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['role'];
    }
    
    return null;
}

/**
 * Get user role label in club (including department name for head)
 */
function get_user_role_label_in_club($conn, $user_id, $club_id) {
    // Kiểm tra xem user có phải là leader của CLB không
    $stmt = $conn->prepare("SELECT leader_id FROM clubs WHERE id = ?");
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $club = $result->fetch_assoc();
        if ($club['leader_id'] == $user_id) {
            $stmt->close();
            return 'Đội trưởng';
        }
    }
    $stmt->close();
    
    // Kiểm tra vai trò trong bảng members
    $role = get_user_role_in_club($conn, $user_id, $club_id);
    
    if ($role === 'leader') {
        return 'Đội trưởng';
    } elseif ($role === 'vice_leader') {
        return 'Đội phó';
    } elseif ($role === 'head') {
        // Kiểm tra xem user có phải là head của phòng ban không
        $dept_stmt = $conn->prepare("SELECT name FROM departments WHERE club_id = ? AND head_id = ?");
        $dept_stmt->bind_param("ii", $club_id, $user_id);
        $dept_stmt->execute();
        $dept_result = $dept_stmt->get_result();
        if ($dept_result->num_rows > 0) {
            $dept_data = $dept_result->fetch_assoc();
            $dept_stmt->close();
            return 'Trưởng ban ' . $dept_data['name'];
        }
        $dept_stmt->close();
        return 'Trưởng ban';
    }
    
    return UserRole::getLabel($role ?? 'member');
}

/**
 * Check if user can manage club
 */
function can_manage_club($conn, $user_id, $club_id) {
    // Kiểm tra xem user có phải là leader trong bảng clubs không
    $stmt = $conn->prepare("SELECT leader_id FROM clubs WHERE id = ?");
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $club = $result->fetch_assoc();
        if ($club['leader_id'] == $user_id) {
            $stmt->close();
            return true;
        }
    }
    $stmt->close();
    
    // Kiểm tra vai trò trong bảng members
    $role = get_user_role_in_club($conn, $user_id, $club_id);
    if (UserRole::isAdmin($role)) {
        return true;
    }
    
    // Kiểm tra xem user có phải là head của phòng ban không
    $dept_stmt = $conn->prepare("SELECT id FROM departments WHERE club_id = ? AND head_id = ?");
    $dept_stmt->bind_param("ii", $club_id, $user_id);
    $dept_stmt->execute();
    $dept_result = $dept_stmt->get_result();
    $is_head = $dept_result->num_rows > 0;
    $dept_stmt->close();
    
    return $is_head;
}

/**
 * Log error to file
 */
function log_error($message, $context = []) {
    $log_dir = APP_ROOT . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/error_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? json_encode($context) : '';
    $log_message = "[$timestamp] $message $context_str\n";
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * Send JSON response
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Generate random string
 */
function generate_random_string($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Check session timeout
 */
function check_session_timeout() {
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > SESSION_LIFETIME) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Simple session-based rate limiter.
 * Returns true if under limit, false if blocked.
 */
function check_rate_limit($key, $limit = 5, $window_seconds = 300) {
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    $now = time();
    $bucket = $_SESSION['rate_limit'][$key] ?? ['count' => 0, 'start' => $now];

    // Reset window
    if ($now - $bucket['start'] >= $window_seconds) {
        $bucket = ['count' => 0, 'start' => $now];
    }

    $bucket['count']++;
    $_SESSION['rate_limit'][$key] = $bucket;

    return $bucket['count'] <= $limit;
}
?>
