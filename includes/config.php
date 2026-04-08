<?php
/**
 * Configuration File
 * Chứa tất cả cấu hình hệ thống
 */

// Prevent multiple includes
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
// Database đang dùng: leaderclub (phù hợp môi trường hiện tại)
define('DB_NAME', 'uniqclub');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'uniQClub');
define('APP_URL', 'http://localhost/Student-Club-and-Event-Administration-System-of-Quy-Nhon-University');
define('APP_ENV', 'development'); // development, production

// Security Configuration
define('SESSION_LIFETIME', 7200); // 2 hours
define('CSRF_TOKEN_NAME', '_csrf_token');
define('PASSWORD_MIN_LENGTH', 8);

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 20 * 1024 * 1024); // 20MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Paths
define('UPLOAD_DIR', APP_ROOT . '/uploads/');
define('CLUB_LOGO_DIR', UPLOAD_DIR . 'clubs/');
define('EVENT_IMAGE_DIR', APP_ROOT . '/anh_bia_sk/');
define('AVATAR_DIR', APP_ROOT . '/assets/img/avatars/');

// Error Reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', APP_ROOT . '/logs/error.log');
}

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Session Configuration (must be set BEFORE session_start())
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Lax');
}
?>
