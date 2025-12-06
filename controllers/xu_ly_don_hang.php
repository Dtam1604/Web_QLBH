
<?php
session_start();
require __DIR__ . '/../config/db.php';

// Kiểm tra đăng nhập - bắt buộc phải đăng nhập mới được mua hàng
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['khach_hang_id'])) {
    // Lưu giỏ hàng vào session nếu có
    if (isset($_POST['cart_json'])) {
        $_SESSION['cart'] = json_decode($_POST['cart_json'], true);
    }
    
    // Redirect đến trang đăng nhập với thông báo
    $_SESSION['login_required_message'] = 'Vui lòng đăng nhập để tiếp tục mua hàng.';
    header('Location: ../auth/login_customer.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Lấy thông tin người mua
// Nếu khách hàng đã đăng nhập, ưu tiên lấy từ session
if (isset($_SESSION['customer_id']) && isset($_SESSION['khach_hang_id'])) {
    // Lấy thông tin từ database
    $khachHangRepo = new KhachHangRepository();
    $khachHang = $khachHangRepo->findById($_SESSION['khach_hang_id']);
    
    if ($khachHang) {
        $ho_ten = $_POST['ho_ten'] ?? $khachHang->getHoTen();
        $so_dien_thoai = $_POST['so_dien_thoai'] ?? $khachHang->getSoDienThoai();
        
        // Lấy địa chỉ mặc định
        $diaChiRepo = new DiaChiRepository();
        $diaChiDefault = $diaChiRepo->findDefaultByKhachHangId($khachHang->getId());
        if ($diaChiDefault) {
            $so_nha = trim($_POST['so_nha'] ?? '') ?: $diaChiDefault->getSoNha();
            $ten_duong = trim($_POST['ten_duong'] ?? '') ?: $diaChiDefault->getTenDuong();
            $phuong_xa = trim($_POST['phuong_xa'] ?? '') ?: $diaChiDefault->getPhuongXa();
            $tinh_thanh = trim($_POST['tinh_thanh'] ?? '') ?: $diaChiDefault->getTinhThanh();
        } else {
            $so_nha = trim($_POST['so_nha'] ?? '');
            $ten_duong = trim($_POST['ten_duong'] ?? '');
            $phuong_xa = trim($_POST['phuong_xa'] ?? '');
            $tinh_thanh = trim($_POST['tinh_thanh'] ?? '');
        }
    } else {
        $ho_ten = $_POST['ho_ten'] ?? '';
        $so_dien_thoai = $_POST['so_dien_thoai'] ?? '';
        $so_nha = trim($_POST['so_nha'] ?? '');
        $ten_duong = trim($_POST['ten_duong'] ?? '');
        $phuong_xa = trim($_POST['phuong_xa'] ?? '');
        $tinh_thanh = trim($_POST['tinh_thanh'] ?? '');
    }
} else {
    $ho_ten = $_POST['ho_ten'] ?? '';
    $so_dien_thoai = $_POST['so_dien_thoai'] ?? '';
    $so_nha = trim($_POST['so_nha'] ?? '');
    $ten_duong = trim($_POST['ten_duong'] ?? '');
    $phuong_xa = trim($_POST['phuong_xa'] ?? '');
    $tinh_thanh = trim($_POST['tinh_thanh'] ?? '');
}

// Lấy giỏ hàng từ JSON do form gửi sang
$cart_json = $_POST['cart_json'] ?? '[]';
$cart = json_decode($cart_json, true); 

// Lưu vào session nếu cần dùng lại
$_SESSION['cart'] = $cart;

// Validate dữ liệu đầu vào
if (empty($ho_ten) || empty($so_dien_thoai) || empty($cart)) {
    echo "<h1 class='order-title'>Lỗi: Vui lòng điền đầy đủ thông tin khách hàng!</h1>";
    echo "<a href='../pages/san_pham.php' class='logout-btn'>Quay lại</a>";
    exit;
}

// Validate địa chỉ
if (empty($ten_duong) || empty($phuong_xa) || empty($tinh_thanh)) {
    echo "<h1 class='order-title'>Lỗi: Vui lòng điền đầy đủ thông tin địa chỉ (Tên đường, Phường/Xã, Tỉnh/Thành phố)!</h1>";
    echo "<a href='../pages/san_pham.php' class='logout-btn'>Quay lại</a>";
    exit;
}

try {
    // 1. Tạo hoặc lấy khách hàng
    $khachHangRepo = new KhachHangRepository();
    
    // Nếu đã đăng nhập, sử dụng khách hàng từ session
    if (isset($_SESSION['customer_id']) && isset($_SESSION['khach_hang_id'])) {
        $khachHang = $khachHangRepo->findById($_SESSION['khach_hang_id']);
        
        // Cập nhật thông tin nếu có thay đổi
        if ($khachHang && ($khachHang->getHoTen() !== $ho_ten || $khachHang->getSoDienThoai() !== $so_dien_thoai)) {
            $khachHang->setHoTen($ho_ten);
            $khachHang->setSoDienThoai($so_dien_thoai);
            $khachHangRepo->update($khachHang);
        }
    } else {
        // Tạo mới hoặc tìm khách hàng
        $khachHang = $khachHangRepo->findOrCreate($ho_ten, $so_dien_thoai);
    }
    
    // 2. Tạo địa chỉ
    $diaChiRepo = new DiaChiRepository();
    $diaChi = $diaChiRepo->findOrCreate($khachHang->getId(), null, 
                                        $so_nha ?: null, $ten_duong, 
                                        $phuong_xa, $tinh_thanh);
    
    // 3. Tạo đơn hàng
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $donHang = new DonHang();
    $donHang->setMaDonHang(DonHang::generateMaDonHang());
    $donHang->setKhachHangId($khachHang->getId());
    $donHang->setDiaChiId($diaChi->getId());
    
    // Xử lý trạng thái và thanh toán
    if ($payment_method === 'online') {
        // Nếu thanh toán online, đặt trạng thái là pending và payment_status là pending
        $donHang->setTrangThai('pending');
        $donHang->setPaymentMethod('online');
        $donHang->setPaymentStatus('pending');
    } else {
        // Nếu COD, giữ nguyên logic cũ
        $donHang->setTrangThai('pending');
        $donHang->setPaymentMethod('cod');
        $donHang->setPaymentStatus('unpaid');
    }
    
    // 4. Tạo chi tiết đơn hàng
    $sanPhamRepo = new SanPhamRepository();
    $tong = 0;
    
    foreach ($cart as $item) {
        // Tìm sản phẩm theo tên (từ giỏ hàng)
        $sanPham = $sanPhamRepo->findByName($item['name']);
        
        if (!$sanPham) {
            throw new Exception("Không tìm thấy sản phẩm: " . $item['name']);
        }
        
        $chiTiet = new DonHangChiTiet();
        $chiTiet->setSanPhamId($sanPham->getId());
        $chiTiet->setSoLuong($item['qty']);
        $chiTiet->setGiaBan($item['price']);
        $chiTiet->calculateThanhTien();
        
        $donHang->addChiTiet($chiTiet);
        $tong += $chiTiet->getThanhTien();
    }
    
    $donHang->setTongTien($tong);
    
    // 5. Validate và lưu đơn hàng
    $errors = $donHang->validate();
    if (!empty($errors)) {
        throw new Exception("Lỗi validation: " . implode(", ", $errors));
    }
    
    $donHangRepo = new DonHangRepository();
    $donHangRepo->create($donHang);
    
    // Reload đầy đủ thông tin để sử dụng tiếp
    $donHang = $donHangRepo->findById($donHang->getId());
    
} catch (Exception $e) {
    echo "<h1 class='order-title'>Lỗi: " . htmlspecialchars($e->getMessage()) . "</h1>";
    echo "<a href='../pages/san_pham.php' class='logout-btn'>Quay lại</a>";
    exit;
}

// Lấy lại đơn hàng với đầy đủ thông tin để hiển thị
$khachHang = $donHang->getKhachHang();
$diaChi = $donHang->getDiaChi();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông tin đơn hàng</title>
    <link rel="stylesheet" href="../CSS/styles.css">
</head>
<body class="order-page">

        <div class="container order-container">
        <div class="order-success-box">
            <h1 class="order-success-title">✓ Đặt hàng thành công!</h1>
            <p class="order-success-text">Mã đơn hàng: <strong><?= htmlspecialchars($donHang->getMaDonHang()) ?></strong></p>
            <?php if ($donHang->getPaymentMethod() === 'online' && $donHang->getPaymentStatus() === 'pending'): ?>
            <p class="order-warning-text">
                ⏳ Đơn hàng đang chờ thanh toán. Vui lòng thanh toán để đơn hàng được xử lý.
            </p>
            <?php endif; ?>
        </div>
        
        <div class="info-box">
            <h2 class="info-box-title">
                📋 Thông tin người đặt hàng
            </h2>
            <table class="info-table">
                <tr>
                    <td>Họ tên:</td>
                    <td><?= htmlspecialchars($khachHang->getHoTen()) ?></td>
                </tr>
                <tr>
                    <td>Số điện thoại:</td>
                    <td><?= htmlspecialchars($khachHang->getSoDienThoai()) ?></td>
                </tr>
                <?php if ($khachHang->getEmail()): ?>
                <tr>
                    <td>Email:</td>
                    <td><?= htmlspecialchars($khachHang->getEmail()) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td>Địa chỉ:</td>
                    <td><?= htmlspecialchars($diaChi->getDiaChiDayDu()) ?></td>
                </tr>
            </table>
        </div>

        <div class="order-detail-box">
            <h2 class="info-box-title">
                🛒 Chi tiết đơn hàng
            </h2>
            <table class="order-table" border="1" cellpadding="10">
                <thead>
                    <tr>
                        <th>Tên sản phẩm</th>
                        <th class="text-right">Giá</th>
                        <th class="text-center">Số lượng</th>
                        <th class="text-right">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($donHang->getChiTiet() as $chiTiet): 
                        $sanPham = $chiTiet->getSanPham();
                    ?>
                    <tr>
                        <td class="product-name">
                            <?= htmlspecialchars($sanPham ? $sanPham->getTenSanPham() : 'N/A') ?>
                        </td>
                        <td class="text-right">
                            <?= number_format($chiTiet->getGiaBan(), 0, '.', '.') ?> đ
                        </td>
                        <td class="text-center">
                            <?= $chiTiet->getSoLuong() ?>
                        </td>
                        <td class="text-right price-primary">
                            <?= $chiTiet->getThanhTienFormatted() ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="total-label">
                            Tổng cộng:
                        </td>
                        <td class="total-amount">
                            <?= $donHang->getTongTienFormatted() ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="order-actions-box">
            <?php if ($donHang->getPaymentMethod() === 'online' && $donHang->getPaymentStatus() === 'pending'): ?>
            <a href="../controllers/xu_ly_thanh_toan.php?id=<?= $donHang->getId() ?>" class="order-action-link payment">
                💳 Thanh toán ngay
            </a>
            <?php endif; ?>
            <a href="../pages/san_pham.php" class="order-action-link back">
                ← Quay lại trang chủ
            </a>
        </div>
    </div>
</body>
</html>
