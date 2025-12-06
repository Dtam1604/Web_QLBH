<?php
// edit_don_hang.php - Sửa đơn hàng
session_start();
require '../config/db.php';

// Chỉ cho phép admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Kiểm tra user hiện tại có phải là admin không
$currentUserStmt = $connection->prepare('SELECT id, loai_user FROM user WHERE id = :id');
$currentUserStmt->execute([':id' => $_SESSION['user_id']]);
$currentUser = $currentUserStmt->fetch(PDO::FETCH_OBJ);

if (!$currentUser || $currentUser->loai_user !== 'admin') {
    die('Bạn không có quyền truy cập chức năng này!');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: don_hang_admin.php');
    exit;
}

// Lấy thông tin đơn hàng
$donHangRepo = new DonHangRepository();
$donHang = $donHangRepo->findById($id);

if (!$donHang) {
    header('Location: don_hang_admin.php');
    exit;
}

$khachHang = $donHang->getKhachHang();
$diaChi = $donHang->getDiaChi();
$chiTiet = $donHang->getChiTiet();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trang_thai = trim($_POST['trang_thai'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? $donHang->getPaymentMethod());
    $payment_status = trim($_POST['payment_status'] ?? $donHang->getPaymentStatus());
    $payment_transaction_id = trim($_POST['payment_transaction_id'] ?? '');
    $paid_at_input = trim($_POST['paid_at'] ?? '');
    $paidAt = null;
    
    // Thông tin khách hàng (có thể sửa)
    $so_dien_thoai_moi = trim($_POST['so_dien_thoai'] ?? '');
    $so_nha = trim($_POST['so_nha'] ?? '');
    $ten_duong = trim($_POST['ten_duong'] ?? '');
    $phuong_xa = trim($_POST['phuong_xa'] ?? '');
    $tinh_thanh = trim($_POST['tinh_thanh'] ?? '');
    $dia_chi_chi_tiet = trim($_POST['dia_chi_chi_tiet'] ?? '');
    
    $allowed_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
    if (!in_array($trang_thai, $allowed_statuses)) {
        $errors[] = 'Trạng thái không hợp lệ.';
    }

    $allowed_payment_methods = ['cod', 'online'];
    if (!in_array($payment_method, $allowed_payment_methods, true)) {
        $errors[] = 'Phương thức thanh toán không hợp lệ.';
    }

    $allowed_payment_statuses = ['unpaid', 'pending', 'paid', 'failed'];
    if (!in_array($payment_status, $allowed_payment_statuses, true)) {
        $errors[] = 'Trạng thái thanh toán không hợp lệ.';
    }

    if ($paid_at_input !== '') {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $paid_at_input);
        if ($dt) {
            $paidAt = $dt->format('Y-m-d H:i:s');
        } else {
            $errors[] = 'Ngày thanh toán không hợp lệ.';
        }
    }

    if ($payment_status === 'paid' && $paidAt === null) {
        $paidAt = date('Y-m-d H:i:s');
    }
    
    // Validate địa chỉ nếu có thay đổi
    if ($so_nha !== '' || $ten_duong !== '' || $phuong_xa !== '' || $tinh_thanh !== '') {
        if (empty($ten_duong) && empty($dia_chi_chi_tiet)) {
            $errors[] = 'Vui lòng nhập tên đường hoặc địa chỉ chi tiết.';
        }
        if (empty($phuong_xa)) {
            $errors[] = 'Phường/Xã không được để trống.';
        }
        if (empty($tinh_thanh)) {
            $errors[] = 'Tỉnh/Thành phố không được để trống.';
        }
    }
    
    if (empty($errors)) {
        try {
            $donHang->setTrangThai($trang_thai);
            $donHangRepo->updateTrangThai($id, $trang_thai);
            $donHangRepo->updatePaymentInfo(
                $id,
                $payment_method,
                $payment_status,
                $payment_transaction_id !== '' ? $payment_transaction_id : null,
                $paidAt
            );
            
            // Cập nhật số điện thoại nếu có thay đổi
            if ($so_dien_thoai_moi !== '' && $so_dien_thoai_moi !== ($khachHang ? $khachHang->getSoDienThoai() : '')) {
                $donHangRepo->updateSoDienThoai($id, $so_dien_thoai_moi);
            }
            
            // Cập nhật địa chỉ nếu có thay đổi
            if ($ten_duong !== '' || $phuong_xa !== '' || $tinh_thanh !== '') {
                $donHangRepo->updateDiaChiGiaoHang($id, $so_nha, $ten_duong, $phuong_xa, $tinh_thanh, $dia_chi_chi_tiet);
            }
            
            $success = true;
            // Reload để hiển thị thông tin mới
            $donHang = $donHangRepo->findById($id);
            $khachHang = $donHang->getKhachHang();
            $diaChi = $donHang->getDiaChi();
        } catch (Exception $e) {
            $errors[] = 'Lỗi khi cập nhật: ' . $e->getMessage();
        }
    } else {
        $donHang->setPaymentMethod($payment_method);
        $donHang->setPaymentStatus($payment_status);
        $donHang->setPaymentTransactionId($payment_transaction_id !== '' ? $payment_transaction_id : null);
        $donHang->setPaidAt($paidAt);
    }
}

$paidAtValue = $donHang->getPaidAt() ? date('Y-m-d\TH:i', strtotime($donHang->getPaidAt())) : '';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sửa đơn hàng</title>
  <link rel="stylesheet" href="../CSS/styles_admin.css">
</head>
<body>
  <!-- Thanh menu -->
  <div class="navbar">
    <div class="menu">
      <a href="san_pham_admin.php">Danh mục sản phẩm</a>
      <a href="don_hang_admin.php">Danh mục đơn hàng</a>
      <a href="user_admin.php">Quản lý tài khoản</a>
      <a href="thong_ke_doanh_thu.php">Thống kê</a>
    </div>
    <a href="../auth/logout.php" class="logout-btn">Logout</a>
  </div>

  <div class="container">
    <h2>Sửa đơn hàng: <?= htmlspecialchars($donHang->getMaDonHang()) ?></h2>
    
    <?php if ($success): ?>
      <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin-bottom: 20px; color: #155724;">
        <strong>✓ Thành công!</strong> Đơn hàng đã được cập nhật.
        <a href="don_hang_admin.php" style="color: #155724; text-decoration: underline; margin-left: 10px;">← Quay lại danh sách</a>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
      <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 4px; margin-bottom: 20px; color: #721c24;">
        <ul style="margin: 0; padding-left: 20px;">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
      <h3 style="color: #1976d2; margin-top: 0; border-bottom: 2px solid #1976d2; padding-bottom: 10px;">
        📋 Thông tin đơn hàng
      </h3>
      
      <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
          <td style="padding: 8px 0; width: 150px; font-weight: bold; color: #555;">Mã đơn hàng:</td>
          <td style="padding: 8px 0;"><?= htmlspecialchars($donHang->getMaDonHang()) ?></td>
        </tr>
        <tr>
          <td style="padding: 8px 0; font-weight: bold; color: #555;">Ngày tạo:</td>
          <td style="padding: 8px 0;">
            <?php if ($donHang->getCreatedAt()): ?>
              <?= date('d/m/Y H:i', strtotime($donHang->getCreatedAt())) ?>
            <?php else: ?>
              <span style="color: #999;">N/A</span>
            <?php endif; ?>
          </td>
        </tr>
      </table>
    </div>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $id); ?>">
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
      <h3 style="color: #1976d2; margin-top: 0; border-bottom: 2px solid #1976d2; padding-bottom: 10px;">
        👤 Thông tin khách hàng
      </h3>
      
      <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 12px; border-radius: 4px; margin-bottom: 15px; color: #856404;">
        <strong>⚠ Lưu ý:</strong> Họ tên không thể thay đổi để đảm bảo tính toàn vẹn dữ liệu. Chỉ có thể sửa số điện thoại và địa chỉ giao hàng.
      </div>
      
      <table style="width: 100%; border-collapse: collapse;">
        <tr>
          <td style="padding: 8px 0; width: 150px; font-weight: bold; color: #555;">Họ tên:</td>
          <td style="padding: 8px 0;">
            <?= htmlspecialchars($khachHang ? $khachHang->getHoTen() : 'N/A') ?>
            <span style="color: #999; font-size: 0.9em; margin-left: 10px;">(Không thể thay đổi)</span>
          </td>
        </tr>
        <tr>
          <td style="padding: 8px 0; font-weight: bold; color: #555;">Số điện thoại:</td>
          <td style="padding: 8px 0;">
            <input type="text" name="so_dien_thoai" 
                   value="<?= htmlspecialchars($khachHang ? $khachHang->getSoDienThoai() : '') ?>" 
                   style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; width: 250px;">
          </td>
        </tr>
        <?php if ($khachHang && $khachHang->getEmail()): ?>
        <tr>
          <td style="padding: 8px 0; font-weight: bold; color: #555;">Email:</td>
          <td style="padding: 8px 0;"><?= htmlspecialchars($khachHang->getEmail()) ?></td>
        </tr>
        <?php endif; ?>
      </table>
      
      <h4 style="color: #1976d2; margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid #e0e0e0; padding-bottom: 5px;">
        📍 Địa chỉ giao hàng
      </h4>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
        <div>
          <label style="display: block; font-weight: bold; color: #555; margin-bottom: 5px; font-size: 0.9em;">Số nhà:</label>
          <input type="text" name="so_nha" 
                 value="<?= htmlspecialchars($diaChi ? ($diaChi->getSoNha() ?? '') : '') ?>" 
                 style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">
        </div>
        <div>
          <label style="display: block; font-weight: bold; color: #555; margin-bottom: 5px; font-size: 0.9em;">Tên đường/Phố *:</label>
          <input type="text" name="ten_duong" 
                 value="<?= htmlspecialchars($diaChi ? ($diaChi->getTenDuong() ?? '') : '') ?>" 
                 style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">
        </div>
        <div>
          <label style="display: block; font-weight: bold; color: #555; margin-bottom: 5px; font-size: 0.9em;">Phường/Xã *:</label>
          <input type="text" name="phuong_xa" 
                 value="<?= htmlspecialchars($diaChi ? ($diaChi->getPhuongXa() ?? '') : '') ?>" 
                 style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">
        </div>
        <div>
          <label style="display: block; font-weight: bold; color: #555; margin-bottom: 5px; font-size: 0.9em;">Tỉnh/Thành phố *:</label>
          <input type="text" name="tinh_thanh" 
                 value="<?= htmlspecialchars($diaChi ? ($diaChi->getTinhThanh() ?? '') : '') ?>" 
                 style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">
        </div>
      </div>
      <div>
        <label style="display: block; font-weight: bold; color: #555; margin-bottom: 5px; font-size: 0.9em;">Địa chỉ chi tiết (nếu không điền các trường trên):</label>
        <textarea name="dia_chi_chi_tiet" rows="2" 
                  style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; width: 100%;"><?= htmlspecialchars($diaChi ? ($diaChi->getDiaChiChiTiet() ?? '') : '') ?></textarea>
      </div>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
      <h3 style="color: #1976d2; margin-top: 0; border-bottom: 2px solid #1976d2; padding-bottom: 10px;">
        🛒 Chi tiết đơn hàng
      </h3>
      
      <table style="width: 100%; border-collapse: collapse; margin-top: 15px;" border="1">
        <thead>
          <tr style="background: #f5f5f5;">
            <th style="text-align: left; padding: 12px; border: 1px solid #ddd;">Tên sản phẩm</th>
            <th style="text-align: right; padding: 12px; border: 1px solid #ddd;">Giá</th>
            <th style="text-align: center; padding: 12px; border: 1px solid #ddd;">Số lượng</th>
            <th style="text-align: right; padding: 12px; border: 1px solid #ddd;">Thành tiền</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($chiTiet as $ct): 
              $sanPham = $ct->getSanPham();
          ?>
          <tr>
            <td style="padding: 12px; border: 1px solid #ddd;">
              <strong><?= htmlspecialchars($sanPham ? $sanPham->getTenSanPham() : 'N/A') ?></strong>
            </td>
            <td style="padding: 12px; border: 1px solid #ddd; text-align: right;">
              <?= number_format($ct->getGiaBan(), 0, '.', '.') ?>₫
            </td>
            <td style="padding: 12px; border: 1px solid #ddd; text-align: center;">
              <?= $ct->getSoLuong() ?>
            </td>
            <td style="padding: 12px; border: 1px solid #ddd; text-align: right; font-weight: bold; color: #1976d2;">
              <?= $ct->getThanhTienFormatted() ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background: #e3f2fd; font-weight: bold;">
            <td colspan="3" style="padding: 15px; border: 1px solid #ddd; text-align: right; font-size: 1.1em;">
              Tổng cộng:
            </td>
            <td style="padding: 15px; border: 1px solid #ddd; text-align: right; font-size: 1.2em; color: #d32f2f;">
              <?= $donHang->getTongTienFormatted() ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
      <h3 style="color: #1976d2; margin-top: 0; border-bottom: 2px solid #1976d2; padding-bottom: 10px;">
        💳 Thông tin thanh toán
      </h3>
      <table style="width: 100%; border-collapse: collapse;">
        <tr>
          <td style="padding: 8px 0; width: 180px; font-weight: bold; color: #555;">Phương thức:</td>
          <td style="padding: 8px 0;">
            <?= $donHang->getPaymentMethod() === 'online' ? 'Thanh toán online' : 'COD' ?>
          </td>
        </tr>
        <tr>
          <td style="padding: 8px 0; font-weight: bold; color: #555;">Trạng thái:</td>
          <td style="padding: 8px 0;">
            <?= htmlspecialchars(ucfirst($donHang->getPaymentStatus() ?? 'unpaid')) ?>
          </td>
        </tr>
        <tr>
          <td style="padding: 8px 0; font-weight: bold; color: #555;">Mã giao dịch:</td>
          <td style="padding: 8px 0;">
            <?= htmlspecialchars($donHang->getPaymentTransactionId() ?? 'N/A') ?>
          </td>
        </tr>
        <tr>
          <td style="padding: 8px 0; font-weight: bold; color: #555;">Ngày thanh toán:</td>
          <td style="padding: 8px 0;">
            <?= $donHang->getPaidAt() ? date('d/m/Y H:i', strtotime($donHang->getPaidAt())) : 'Chưa thanh toán' ?>
          </td>
        </tr>
      </table>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
      <h3 style="color: #1976d2; margin-top: 0; border-bottom: 2px solid #1976d2; padding-bottom: 10px;">
        ✏️ Cập nhật trạng thái và thanh toán
      </h3>
      
        <div style="margin-bottom: 20px;">
          <label for="trang_thai" style="display: block; font-weight: bold; margin-bottom: 8px; color: #555;">
            Trạng thái đơn hàng:
          </label>
          <select name="trang_thai" id="trang_thai" style="padding: 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 1rem; min-width: 200px;">
            <option value="pending" <?= $donHang->getTrangThai() === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
            <option value="processing" <?= $donHang->getTrangThai() === 'processing' ? 'selected' : '' ?>>Đang xử lý</option>
            <option value="shipped" <?= $donHang->getTrangThai() === 'shipped' ? 'selected' : '' ?>>Đã giao hàng</option>
            <option value="completed" <?= $donHang->getTrangThai() === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
            <option value="cancelled" <?= $donHang->getTrangThai() === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
          </select>
        </div>

        <div style="margin-bottom: 18px;">
          <label for="payment_method" style="display:block;font-weight:bold;margin-bottom:8px;color:#555;">
            Phương thức thanh toán:
          </label>
          <select name="payment_method" id="payment_method" style="padding:10px;border-radius:4px;border:1px solid #ddd;font-size:1rem;min-width:200px;">
            <option value="cod" <?= $donHang->getPaymentMethod() === 'cod' ? 'selected' : '' ?>>COD</option>
            <option value="online" <?= $donHang->getPaymentMethod() === 'online' ? 'selected' : '' ?>>Thanh toán online</option>
          </select>
        </div>

        <div style="margin-bottom: 18px;">
          <label for="payment_status" style="display:block;font-weight:bold;margin-bottom:8px;color:#555;">
            Trạng thái thanh toán:
          </label>
          <select name="payment_status" id="payment_status" style="padding:10px;border-radius:4px;border:1px solid #ddd;font-size:1rem;min-width:200px;">
            <option value="unpaid" <?= $donHang->getPaymentStatus() === 'unpaid' ? 'selected' : '' ?>>Chưa thanh toán</option>
            <option value="pending" <?= $donHang->getPaymentStatus() === 'pending' ? 'selected' : '' ?>>Đang chờ cổng thanh toán</option>
            <option value="paid" <?= $donHang->getPaymentStatus() === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
            <option value="failed" <?= $donHang->getPaymentStatus() === 'failed' ? 'selected' : '' ?>>Thanh toán thất bại</option>
          </select>
        </div>

        <div style="margin-bottom: 18px;">
          <label for="payment_transaction_id" style="display:block;font-weight:bold;margin-bottom:8px;color:#555;">
            Mã giao dịch (nếu có):
          </label>
          <input type="text" id="payment_transaction_id" name="payment_transaction_id"
                 value="<?= htmlspecialchars($donHang->getPaymentTransactionId() ?? '') ?>"
                 style="padding:10px;border-radius:4px;border:1px solid #ddd;width:100%;max-width:400px;">
        </div>

        <div style="margin-bottom: 18px;">
          <label for="paid_at" style="display:block;font-weight:bold;margin-bottom:8px;color:#555;">
            Ngày thanh toán:
          </label>
          <input type="datetime-local" id="paid_at" name="paid_at"
                 value="<?= htmlspecialchars($paidAtValue) ?>"
                 style="padding:10px;border-radius:4px;border:1px solid #ddd;width:100%;max-width:280px;">
          <p style="color:#777;font-size:0.9em;margin-top:6px;">
            Để trống để hệ thống tự ghi nhận thời điểm hiện tại khi đánh dấu “Đã thanh toán”.
          </p>
        </div>
        
        <div style="display: flex; gap: 10px;">
          <button type="submit" class="btn btn-info" style="padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem;">
            Cập nhật
          </button>
          <a href="don_hang_admin.php" class="btn" style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">
            Hủy
          </a>
        </div>
    </div>
    </form>
  </div>
</body>
</html>

