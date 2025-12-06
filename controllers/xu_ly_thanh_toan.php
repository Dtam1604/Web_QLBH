<?php
session_start();
require __DIR__ . '/../config/db.php';

// Kiểm tra đơn hàng ID
$don_hang_id = $_GET['id'] ?? null;
if (!$don_hang_id) {
    header('Location: ../pages/san_pham.php');
    exit;
}

$donHangRepo = new DonHangRepository();
$donHang = $donHangRepo->findById($don_hang_id);

if (!$donHang) {
    echo "<h1>Không tìm thấy đơn hàng!</h1>";
    echo "<a href='../pages/san_pham.php'>Quay lại</a>";
    exit;
}

// Kiểm tra quyền truy cập - chỉ khách hàng sở hữu đơn hàng mới được thanh toán
if (isset($_SESSION['khach_hang_id'])) {
    if ($donHang->getKhachHangId() != $_SESSION['khach_hang_id']) {
        echo "<h1>Bạn không có quyền thanh toán đơn hàng này!</h1>";
        echo "<a href='../pages/don_hang_customer.php'>Quay lại</a>";
        exit;
    }
} else {
    // Nếu chưa đăng nhập, yêu cầu đăng nhập
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ../auth/login_customer.php');
    exit;
}

// Kiểm tra đơn hàng có thể thanh toán không
if ($donHang->getPaymentMethod() !== 'online' || $donHang->getPaymentStatus() !== 'pending') {
    echo "<h1>Đơn hàng này không thể thanh toán online hoặc đã được thanh toán!</h1>";
    echo "<a href='../pages/don_hang_customer.php'>Quay lại</a>";
    exit;
}

// Xử lý thanh toán
$payment_success = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    try {
        // Mô phỏng thanh toán thành công (trong thực tế sẽ gọi API cổng thanh toán)
        // Tạo transaction ID giả
        $transaction_id = 'TXN' . date('YmdHis') . rand(1000, 9999);
        
        // Cập nhật trạng thái thanh toán
        $donHangRepo->updatePaymentStatus(
            $donHang->getId(),
            'paid',
            $transaction_id,
            date('Y-m-d H:i:s'),
            json_encode(['status' => 'success', 'method' => 'simulated'])
        );
        
        // Reload đơn hàng
        $donHang = $donHangRepo->findById($donHang->getId());
        $payment_success = true;
    } catch (Exception $e) {
        $error_message = "Lỗi thanh toán: " . $e->getMessage();
    }
}

$khachHang = $donHang->getKhachHang();
$diaChi = $donHang->getDiaChi();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh toán online</title>
    <link rel="stylesheet" href="../CSS/styles.css">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .payment-header h1 {
            color: #1976d2;
            margin: 0;
        }
        
        .order-info {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .order-info p {
            margin: 8px 0;
        }
        
        .payment-amount {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .payment-amount .label {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .payment-amount .amount {
            font-size: 2.5em;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .payment-form {
            margin-top: 30px;
        }
        
        .payment-method-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }
        
        .payment-method-info p {
            margin: 5px 0;
            color: #1976d2;
        }
        
        .btn-payment {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.2em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
        }
        
        .btn-payment:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.5);
        }
        
        .btn-payment:active {
            transform: translateY(0);
        }
        
        .success-message {
            background: #e8f5e9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #4caf50;
            text-align: center;
        }
        
        .success-message h2 {
            color: #2e7d32;
            margin: 0 0 10px 0;
        }
        
        .error-message {
            background: #ffebee;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #f44336;
            color: #c62828;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #1976d2;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="order-page">
    <div class="payment-container">
        <div class="payment-header">
            <h1>💳 Thanh toán online</h1>
        </div>
        
        <?php if ($payment_success): ?>
            <div class="success-message">
                <h2>✓ Thanh toán thành công!</h2>
                <p>Đơn hàng của bạn đã được thanh toán thành công.</p>
                <p><strong>Mã giao dịch:</strong> <?= htmlspecialchars($donHang->getPaymentTransactionId()) ?></p>
                <p><strong>Thời gian:</strong> <?= date('d/m/Y H:i:s', strtotime($donHang->getPaidAt())) ?></p>
            </div>
            <div style="text-align: center; margin-top: 30px;">
                <a href="../pages/don_hang_customer.php" class="btn-payment" style="text-decoration: none; display: inline-block; padding: 12px 30px;">
                    Xem đơn hàng
                </a>
            </div>
        <?php else: ?>
            <div class="order-info">
                <p><strong>Mã đơn hàng:</strong> <?= htmlspecialchars($donHang->getMaDonHang()) ?></p>
                <p><strong>Người nhận:</strong> <?= htmlspecialchars($khachHang->getHoTen()) ?></p>
                <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($khachHang->getSoDienThoai()) ?></p>
                <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($diaChi->getDiaChiDayDu()) ?></p>
            </div>
            
            <div class="payment-amount">
                <div class="label">Số tiền cần thanh toán</div>
                <div class="amount"><?= $donHang->getTongTienFormatted() ?></div>
            </div>
            
            <div class="payment-method-info">
                <p><strong>Phương thức thanh toán:</strong> Thanh toán online (Mô phỏng)</p>
                <p style="font-size: 0.9em; margin-top: 10px;">
                    ⚠️ Đây là hệ thống mô phỏng thanh toán. Trong môi trường thực tế sẽ được chuyển đến API thanh toán.
                </p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="payment-form">
                <button type="submit" name="confirm_payment" class="btn-payment">
                    ✓ Xác nhận thanh toán
                </button>
            </form>
            
            <a href="../pages/don_hang_customer.php" class="back-link">← Quay lại danh sách đơn hàng</a>
        <?php endif; ?>
    </div>
</body>
</html>

