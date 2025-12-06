<?php
/**
 * File test session - để debug
 * Xóa file này sau khi test xong
 */
session_start();

echo "<h1>Test Session</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Test Customer Session</h2>";
if (isset($_SESSION['customer_id'])) {
    echo "<p style='color: green;'>✓ Customer ID: " . $_SESSION['customer_id'] . "</p>";
    echo "<p>Customer Username: " . ($_SESSION['customer_username'] ?? 'N/A') . "</p>";
    echo "<p>Customer Name: " . ($_SESSION['customer_name'] ?? 'N/A') . "</p>";
    echo "<p>Khach Hang ID: " . ($_SESSION['khach_hang_id'] ?? 'N/A') . "</p>";
} else {
    echo "<p style='color: red;'>✗ Chưa có customer session</p>";
}

echo "<h2>Test Admin Session</h2>";
if (isset($_SESSION['user'])) {
    echo "<p style='color: green;'>✓ Admin User: " . $_SESSION['user'] . "</p>";
} else {
    echo "<p style='color: red;'>✗ Chưa có admin session</p>";
}

echo "<hr>";
echo "<a href='../pages/san_pham.php'>Trang sản phẩm</a> | ";
echo "<a href='../auth/login_customer.php'>Đăng nhập</a> | ";
echo "<a href='../auth/register_customer.php'>Đăng ký</a>";
?>

