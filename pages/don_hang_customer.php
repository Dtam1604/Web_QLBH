<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['khach_hang_id'])) {
    header('Location: ../auth/login_customer.php');
    exit;
}

require '../config/db.php';

$khach_hang_id = $_SESSION['khach_hang_id'];
$donHangRepo = new DonHangRepository();
$list_don_hang = $donHangRepo->findByKhachHangId($khach_hang_id);
?>
<?php require '../views/header_guest.php'; ?>

<!-- Banner ngang -->
<div class="banner">
  <div class="logo">Toét Store</div>
  <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <span style="color: white;">Xin chào, <?= htmlspecialchars($_SESSION['customer_name'] ?? $_SESSION['customer_username']) ?>!</span>
    <a href="san_pham.php" class="login-btn">🏠 Trang chủ</a>
    <a href="../auth/change_password_customer.php" class="login-btn">🔐 Đổi mật khẩu</a>
    <a href="../auth/logout_customer.php" class="login-btn">🚪 Đăng xuất</a>
  </div>
</div>

<!-- Nội dung chính -->
<div class="orders-container">
    <div class="orders-header">
        <h1>📦 Đơn hàng của tôi</h1>
        <p>Xem lại tất cả đơn hàng bạn đã đặt</p>
    </div>
    
    <?php if (empty($list_don_hang)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <p>Bạn chưa có đơn hàng nào.</p>
            <a href="san_pham.php" class="btn-primary">
                🛒 Mua sắm ngay →
            </a>
        </div>
    <?php else: ?>
        <?php foreach($list_don_hang as $don_hang): 
            $khachHang = $don_hang->getKhachHang();
            $diaChi = $don_hang->getDiaChi();
            $chiTiet = $don_hang->getChiTiet();
            
            // Màu sắc và icon theo trạng thái
            $statusConfig = [
                'pending' => ['color' => '#ff9800', 'label' => '⏳ Chờ xử lý', 'bg' => 'linear-gradient(135deg, #ff9800 0%, #f57c00 100%)'],
                'processing' => ['color' => '#2196f3', 'label' => '⚙️ Đang xử lý', 'bg' => 'linear-gradient(135deg, #2196f3 0%, #1976d2 100%)'],
                'confirmed' => ['color' => '#2196f3', 'label' => '✅ Đã xác nhận', 'bg' => 'linear-gradient(135deg, #2196f3 0%, #1976d2 100%)'],
                'shipping' => ['color' => '#9c27b0', 'label' => '🚚 Đang giao hàng', 'bg' => 'linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%)'],
                'shipped' => ['color' => '#9c27b0', 'label' => '🚚 Đang giao hàng', 'bg' => 'linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%)'],
                'completed' => ['color' => '#4caf50', 'label' => '✓ Hoàn thành', 'bg' => 'linear-gradient(135deg, #4caf50 0%, #388e3c 100%)'],
                'cancelled' => ['color' => '#f44336', 'label' => '❌ Đã hủy', 'bg' => 'linear-gradient(135deg, #f44336 0%, #d32f2f 100%)']
            ];
            $trang_thai = $don_hang->getTrangThai() ?? 'pending';
            $status = $statusConfig[$trang_thai] ?? $statusConfig['pending'];
        ?>
        <div class="order-card">
            <!-- Header đơn hàng -->
            <div class="order-header">
                <div class="order-header-left">
                    <h3>
                        Mã đơn hàng: <strong><?= htmlspecialchars($don_hang->getMaDonHang()) ?></strong>
                    </h3>
                    <p>
                        📅 Ngày đặt: <?= date('d/m/Y H:i', strtotime($don_hang->getCreatedAt())) ?>
                    </p>
                    <p>
                        💳 Thanh toán: <strong><?= $don_hang->getPaymentMethod() === 'online' ? 'Thanh toán online' : 'COD' ?></strong>
                        • 
                        <?php if ($don_hang->isPaid()): ?>
                            <span style="color: #4caf50; font-weight: bold;">✓ Đã thanh toán</span>
                        <?php elseif ($don_hang->getPaymentMethod() === 'online' && $don_hang->getPaymentStatus() === 'pending'): ?>
                            <span style="color: #f57c00; font-weight: bold;">⏳ Đang chờ thanh toán</span>
                        <?php else: ?>
                            <span style="color: #f44336;">Chưa thanh toán</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="order-header-right">
                    <span class="status-badge" style="background: <?= $status['bg'] ?>; color: white;">
                        <?= htmlspecialchars($status['label']) ?>
                    </span>
                    <div class="order-total">
                        <?= $don_hang->getTongTienFormatted() ?>
                    </div>
                </div>
            </div>
            
            <!-- Body đơn hàng -->
            <div class="order-body">
                <!-- Thông tin giao hàng -->
                <?php if ($khachHang && $diaChi): ?>
                <div class="shipping-info">
                    <h4>📋 Thông tin giao hàng</h4>
                    <p>
                        <strong>👤 Người nhận:</strong> <?= htmlspecialchars($khachHang->getHoTen()) ?><br>
                        <strong>📞 SĐT:</strong> <?= htmlspecialchars($khachHang->getSoDienThoai()) ?><br>
                        <strong>📍 Địa chỉ:</strong> <?= htmlspecialchars($diaChi->getDiaChiDayDu()) ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <!-- Chi tiết sản phẩm -->
                <div class="products-section">
                    <h4>🛒 Sản phẩm đã đặt</h4>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Số lượng</th>
                                <th>Giá</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($chiTiet as $ct): 
                                $sanPham = $ct->getSanPham();
                            ?>
                            <tr>
                                <td>
                                    <span class="product-name"><?= htmlspecialchars($sanPham ? $sanPham->getTenSanPham() : 'N/A') ?></span>
                                </td>
                                <td>
                                    <strong><?= $ct->getSoLuong() ?></strong>
                                </td>
                                <td>
                                    <span class="product-price"><?= number_format($ct->getGiaBan(), 0, '.', '.') ?> đ</span>
                                </td>
                                <td>
                                    <span class="product-total"><?= $ct->getThanhTienFormatted() ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right;">
                                    <strong>Tổng cộng:</strong>
                                </td>
                                <td style="color: #764ba2; font-size: 1.2em;">
                                    <?= $don_hang->getTongTienFormatted() ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="order-actions">
                    <?php if ($don_hang->getPaymentMethod() === 'online' && $don_hang->getPaymentStatus() === 'pending'): ?>
                    <a class="order-action-btn payment-btn" href="../controllers/xu_ly_thanh_toan.php?id=<?= $don_hang->getId() ?>">
                        💳 Thanh toán online
                    </a>
                    <?php endif; ?>
                    <a class="order-action-btn" href="../controllers/export_invoice.php?id=<?= $don_hang->getId() ?>" target="_blank">
                        🧾 Xem hóa đơn
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <div style="text-align: center; margin-top: 40px;">
        <a href="san_pham.php" class="btn-back">
            ← Quay lại trang chủ
        </a>
    </div>
</div>

<?php require_once '../views/footer.php'; ?>
</body>
</html>

