<?php
// Khởi động session trước khi gọi hàm logout
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dùng chung hàm logout ở xulylogin để xóa session + cookie remember
require_once __DIR__ . '/../xulylogin.php';

logout(); // Hàm đã hủy session, clear cookie và redirect về login.php
?>

