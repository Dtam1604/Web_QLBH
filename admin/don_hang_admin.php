<?php
require '../config/db.php';
require_once '../utils/pagination.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10; // Số đơn hàng mỗi trang

$donHangRepo = new DonHangRepository();
$totalItems = $donHangRepo->count();
$totalPages = ceil($totalItems / $perPage);
$list_don_hang = $donHangRepo->findAllPaginated($page, $perPage);

// Đếm số đơn hàng chưa được chuẩn hóa (từ tất cả đơn hàng)
$allDonHang = $donHangRepo->findAll();
$unmigratedCount = 0;
foreach ($allDonHang as $dh) {
    if ($dh->getKhachHangId() === null) {
        $unmigratedCount++;
    }
}
 ?>
<!doctype html>
<html lang="en">
<head>
  <title>Danh mục sản phẩm</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="../CSS/styles_admin.css">  
</head>
<body>
  <!-- Thanh menu -->
  <div class="navbar">
    <div class="menu">
      <a href="san_pham_admin.php">Danh mục sản phẩm</a>
      <a href="don_hang_admin.php" class="active">Danh mục đơn hàng</a>
      <a href="user_admin.php">Quản lý tài khoản</a>
      <a href="thong_ke_doanh_thu.php">Thống kê</a>
    </div>
	<a href="../auth/logout.php" class="logout-btn">Logout</a>
  </div>
  <div class="container">
    <h2>Danh mục đơn hàng</h2>
    <?php if ($unmigratedCount > 0): ?>
    <div class="warning-box">
      <strong>⚠ Lưu ý:</strong> Có <strong><?= $unmigratedCount ?></strong> đơn hàng chưa được chuẩn hóa. 
      <a href="../config/migrate_to_normalized.php">
        Chạy migration ngay →
      </a>
    </div>
    <?php endif; ?>
  <table id ="list_tables">
    <tr>
      <th>Mã Đơn hàng</th>
      <th>Thông tin đơn hàng</th>
      <th>Thông tin người mua</th>
      <th>Tổng tiền</th>
      <th>Thanh toán</th>
      <th>Trạng thái</th>
      <th>Ngày tạo</th>
      <th>Action</th>
    </tr>
    <?php foreach($list_don_hang as $don_hang): 
        $khachHang = $don_hang->getKhachHang();
        $diaChi = $don_hang->getDiaChi();
        $chiTiet = $don_hang->getChiTiet();
        
        // Kiểm tra nếu đơn hàng chưa được chuẩn hóa (dữ liệu cũ)
        $isOldFormat = empty($chiTiet) && $don_hang->getKhachHangId() === null;
    ?>
    <tr>
      <td><?= htmlspecialchars($don_hang->getMaDonHang()); ?></td>
      <td>
        <?php if ($isOldFormat): ?>
          <!-- Fallback cho dữ liệu cũ -->
          <div class="old-order-warning">
            <em>⚠ Đơn hàng cũ - Cần migrate</em><br>
            <?php 
            // Lấy dữ liệu từ database trực tiếp
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT thong_tin_don_hang FROM don_hang WHERE id = ?");
            $stmt->execute([$don_hang->getId()]);
            $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
            echo $oldData['thong_tin_don_hang'] ?? 'N/A';
            ?>
          </div>
        <?php else: ?>
          <!-- Dữ liệu chuẩn hóa -->
          <ul class="order-detail-list">
            <?php foreach($chiTiet as $ct): 
                $sanPham = $ct->getSanPham();
            ?>
            <li class="order-detail-item">
        <strong><?= htmlspecialchars($sanPham ? $sanPham->getTenSanPham() : 'N/A') ?></strong>
    
        <span class="order-price">
        <span class="order-qty">x <?= $ct->getSoLuong() ?></span>
        = <?= $ct->getThanhTienFormatted() ?>
          </span>
          </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($isOldFormat): ?>
          <!-- Fallback cho dữ liệu cũ -->
          <div class="old-order-warning">
            <em>⚠ Cần migrate</em><br>
            <?php 
            $stmt = $db->prepare("SELECT thong_tin_nguoi_mua FROM don_hang WHERE id = ?");
            $stmt->execute([$don_hang->getId()]);
            $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
            echo $oldData['thong_tin_nguoi_mua'] ?? 'N/A';
            ?>
          </div>
        <?php else: ?>
          <!-- Dữ liệu chuẩn hóa -->
          <ul class="order-detail-list">
            <li class="order-detail-item">
              <strong>Họ tên:</strong> <?= htmlspecialchars($khachHang ? $khachHang->getHoTen() : 'N/A') ?>
            </li>
            <li class="order-detail-item">
              <strong>SĐT:</strong> <?= htmlspecialchars($khachHang ? $khachHang->getSoDienThoai() : 'N/A') ?>
            </li>
            <?php if ($khachHang && $khachHang->getEmail()): ?>
            <li class="order-detail-item">
              <strong>Email:</strong> <?= htmlspecialchars($khachHang->getEmail()) ?>
            </li>
            <?php endif; ?>
            <?php if ($diaChi): ?>
            <li class="order-detail-item order-address-separator">
              <strong class="order-address-title">📍 Địa chỉ:</strong>
            </li>
            <?php if ($diaChi->getSoNha()): ?>
            <li class="order-address-item">
              <strong>Số nhà:</strong> <?= htmlspecialchars($diaChi->getSoNha()) ?>
            </li>
            <?php endif; ?>
            <?php if ($diaChi->getTenDuong()): ?>
            <li class="order-address-item">
              <strong>Tên đường:</strong> <?= htmlspecialchars($diaChi->getTenDuong()) ?>
            </li>
            <?php endif; ?>
            <?php if ($diaChi->getPhuongXa()): ?>
            <li class="order-address-item">
              <strong>Phường/Xã:</strong> <?= htmlspecialchars($diaChi->getPhuongXa()) ?>
            </li>
            <?php endif; ?>
            <?php if ($diaChi->getTinhThanh()): ?>
            <li class="order-address-item">
              <strong>Tỉnh/Thành phố:</strong> <?= htmlspecialchars($diaChi->getTinhThanh()) ?>
            </li>
            <?php endif; ?>
            <?php if ($diaChi->getDiaChiChiTiet() && empty($diaChi->getSoNha()) && empty($diaChi->getTenDuong())): ?>
            <li class="order-address-item">
              <strong>Địa chỉ chi tiết:</strong> <?= htmlspecialchars($diaChi->getDiaChiChiTiet()) ?>
            </li>
            <?php endif; ?>
            <?php else: ?>
            <li class="order-detail-item">
              <strong>Địa chỉ:</strong> <span class="order-na">N/A</span>
            </li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>
      </td>
      <td>
        <strong class="order-total-amount">
          <?= $don_hang->getTongTienFormatted(); ?>
        </strong>
      </td>
      <td>
        <div class="payment-pill">
          <?= $don_hang->getPaymentMethod() === 'online' ? 'Thanh toán online' : 'COD' ?>
        </div>
        <div class="payment-status <?= $don_hang->isPaid() ? 'paid' : 'unpaid' ?>">
          <?= $don_hang->isPaid() ? 'Đã thanh toán' : 'Chưa thanh toán' ?>
        </div>
        <?php if ($don_hang->getPaymentTransactionId()): ?>
          <small>Mã GD: <?= htmlspecialchars($don_hang->getPaymentTransactionId()) ?></small><br>
        <?php endif; ?>
        <?php if ($don_hang->getPaidAt()): ?>
          <small>Ngày thanh toán: <?= date('d/m/Y H:i', strtotime($don_hang->getPaidAt())) ?></small>
        <?php endif; ?>
      </td>
      <td>
        <span class="order-status-badge">
          <?= htmlspecialchars($don_hang->getTrangThai() ?: 'pending'); ?>
        </span>
      </td>
      <td>
        <?php if ($don_hang->getCreatedAt()): ?>
          <?= date('d/m/Y', strtotime($don_hang->getCreatedAt())) ?><br>
          <small class="order-time"><?= date('H:i', strtotime($don_hang->getCreatedAt())) ?></small>
        <?php else: ?>
          <span class="order-na">N/A</span>
        <?php endif; ?>
      </td>
     <td>
      <a href="edit_don_hang.php?id=<?= $don_hang->getId() ?>" class="btn btn-info">Edit</a>
      <a href="../controllers/export_invoice.php?id=<?= $don_hang->getId() ?>" class="btn btn-info" style="background:#1976d2;border-color:#1565c0;">Hóa đơn</a>
      <a onclick="return confirm('Bạn có thực sự muốn xóa đơn hàng này?')" 
         href="../controllers/delete_don_hang.php?id=<?= $don_hang->getId() ?>" 
         class='btn btn-danger'>Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  
  <!-- Pagination -->
  <?php 
  if ($totalPages > 1) {
      echo renderPagination($page, $totalPages, 'don_hang_admin.php');
  }
  ?>
  
  <!-- Hiển thị thông tin phân trang -->
  <div class="pagination-info">
    Hiển thị <?= count($list_don_hang) ?> / <?= $totalItems ?> đơn hàng
    <?php if ($totalPages > 1): ?>
      (Trang <?= $page ?> / <?= $totalPages ?>)
    <?php endif; ?>
  </div>
</div>

