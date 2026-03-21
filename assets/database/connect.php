<?php
/**
 * Database Connection
 * Kết nối database an toàn với error handling
 */

// Prevent multiple includes
if (defined('DB_CONNECTION_LOADED')) {
    return;
}
define('DB_CONNECTION_LOADED', true);

// Load configuration
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

global $conn;

if (!isset($conn)) {
    try {
        // Create connection
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset
        if (!$conn->set_charset(DB_CHARSET)) {
            throw new Exception("Error setting charset: " . $conn->error);
        }
        
        // Set timezone
        $conn->query("SET time_zone = '+07:00'");
        
    } catch (Exception $e) {
        // Log error
        log_error("Database connection error: " . $e->getMessage());
        
        // Show user-friendly error
        if (APP_ENV === 'development') {
            die("Lỗi kết nối database: " . $e->getMessage());
        } else {
            die("Hệ thống đang bảo trì. Vui lòng thử lại sau.");
        }
    }
}

/**
 * Close database connection
 */
if (!function_exists('close_db_connection')) {
    function close_db_connection() {
        global $conn;
        if (isset($conn) && $conn instanceof mysqli) {
            // Kiểm tra connection có còn mở không bằng cách kiểm tra thread_id
            // Nếu thread_id là null thì connection đã bị đóng
            try {
                $thread_id = @$conn->thread_id;
                if ($thread_id !== null && $thread_id !== false) {
                    // Connection vẫn còn mở, đóng nó
                    @$conn->close();
                }
            } catch (Throwable $e) {
                // Connection already closed hoặc có lỗi, ignore
                // Không cần làm gì cả
            }
        }
    }
}

// Register shutdown function to close connection (only once)
static $shutdown_registered = false;
if (!$shutdown_registered) {
    register_shutdown_function('close_db_connection');
    $shutdown_registered = true;
}
?>

