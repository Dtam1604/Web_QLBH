<?php
/**
 * Đăng xuất khách hàng
 */
session_start();

// Xóa session khách hàng
unset($_SESSION['customer_id']);
unset($_SESSION['customer_username']);
unset($_SESSION['customer_name']);
unset($_SESSION['khach_hang_id']);

// Xóa cookie nếu có
if (isset($_COOKIE['username'])) {
    setcookie('username', '', time() - 3600, '/');
}

// Redirect về trang chủ
header('Location: ../pages/san_pham.php');
exit;

