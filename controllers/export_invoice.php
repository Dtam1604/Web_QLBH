<?php
session_start();
require __DIR__ . '/../config/db.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
    die('Thiếu ID đơn hàng.');
}

$donHangRepo = new DonHangRepository();
$donHang = $donHangRepo->findById($orderId);

if (!$donHang) {
    die('Không tìm thấy đơn hàng.');
}

// Chỉ cho phép khách sở hữu hoặc admin (đã đăng nhập) tải hóa đơn
$isOwner = isset($_SESSION['khach_hang_id']) && $donHang->getKhachHangId() === $_SESSION['khach_hang_id'];
$isAdmin = isset($_SESSION['user_id']);
if (!$isOwner && !$isAdmin) {
    die('Bạn không có quyền xem hóa đơn này.');
}

$invoiceHtml = InvoiceGenerator::renderHtml($donHang);
$download = isset($_GET['download']);

if ($download) {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename=hoa-don-' . $donHang->getMaDonHang() . '.html');
    echo $invoiceHtml;
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn <?= htmlspecialchars($donHang->getMaDonHang()) ?></title>
</head>
<body>
    <?= $invoiceHtml ?>
    <div style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #1976d2; color: #fff; border: none; border-radius: 8px; cursor: pointer;">
            In / Lưu PDF
        </button>
        <a href="?id=<?= $donHang->getId() ?>&download=1" style="margin-left: 12px;">Tải xuống HTML</a>
    </div>
</body>
</html>

