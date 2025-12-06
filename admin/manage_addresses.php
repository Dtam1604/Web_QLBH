<?php
// manage_addresses.php - Quản lý địa chỉ khách hàng
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

$khach_hang_id = isset($_GET['khach_hang_id']) ? intval($_GET['khach_hang_id']) : 0;
if ($khach_hang_id <= 0) {
    header('Location: user_admin.php');
    exit;
}

// Lấy thông tin khách hàng
$khachHangRepo = new KhachHangRepository();
$khachHang = $khachHangRepo->findById($khach_hang_id);

if (!$khachHang) {
    header('Location: user_admin.php');
    exit;
}

$diaChiRepo = new DiaChiRepository();

// Xử lý các action
$errors = [];
$success = false;

// Xóa địa chỉ
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $dia_chi_id = intval($_GET['delete']);
    try {
        $diaChiRepo->delete($dia_chi_id);
        $_SESSION['success'] = 'Đã xóa địa chỉ thành công!';
        header('Location: manage_addresses.php?khach_hang_id=' . $khach_hang_id);
        exit;
    } catch (Exception $e) {
        $errors[] = 'Lỗi khi xóa địa chỉ: ' . $e->getMessage();
    }
}

// Đặt địa chỉ mặc định
if (isset($_GET['set_default']) && is_numeric($_GET['set_default'])) {
    $dia_chi_id = intval($_GET['set_default']);
    try {
        $diaChiRepo->setDefault($khach_hang_id, $dia_chi_id);
        $_SESSION['success'] = 'Đã đặt địa chỉ mặc định thành công!';
        header('Location: manage_addresses.php?khach_hang_id=' . $khach_hang_id);
        exit;
    } catch (Exception $e) {
        $errors[] = 'Lỗi khi đặt địa chỉ mặc định: ' . $e->getMessage();
    }
}

// Xử lý form thêm/sửa địa chỉ
$editing_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$editing_diaChi = null;
if ($editing_id > 0) {
    $editing_diaChi = $diaChiRepo->findById($editing_id);
    if (!$editing_diaChi || $editing_diaChi->getKhachHangId() != $khach_hang_id) {
        $editing_diaChi = null;
        $editing_id = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $so_nha = trim($_POST['so_nha'] ?? '');
    $ten_duong = trim($_POST['ten_duong'] ?? '');
    $phuong_xa = trim($_POST['phuong_xa'] ?? '');
    $tinh_thanh = trim($_POST['tinh_thanh'] ?? '');
    $dia_chi_chi_tiet = trim($_POST['dia_chi_chi_tiet'] ?? '');
    $mac_dinh = isset($_POST['mac_dinh']) ? 1 : 0;
    
    // Validate
    if (empty($ten_duong) && empty($dia_chi_chi_tiet)) {
        $errors[] = 'Vui lòng nhập tên đường hoặc địa chỉ chi tiết.';
    }
    if (empty($phuong_xa)) {
        $errors[] = 'Phường/Xã không được để trống.';
    }
    if (empty($tinh_thanh)) {
        $errors[] = 'Tỉnh/Thành phố không được để trống.';
    }
    
    if (empty($errors)) {
        try {
            if ($editing_id > 0) {
                // Sửa địa chỉ
                $editing_diaChi->setSoNha($so_nha ?: null);
                $editing_diaChi->setTenDuong($ten_duong);
                $editing_diaChi->setPhuongXa($phuong_xa);
                $editing_diaChi->setTinhThanh($tinh_thanh);
                $editing_diaChi->setDiaChiChiTiet($dia_chi_chi_tiet ?: null);
                $editing_diaChi->setMacDinh($mac_dinh);
                
                if ($mac_dinh) {
                    $diaChiRepo->setDefault($khach_hang_id, $editing_id);
                } else {
                    $diaChiRepo->update($editing_diaChi);
                }
                
                $_SESSION['success'] = 'Đã cập nhật địa chỉ thành công!';
            } else {
                // Thêm địa chỉ mới
                $diaChi = new DiaChi(null, $khach_hang_id, $dia_chi_chi_tiet ?: null, 
                                    $so_nha ?: null, $ten_duong, $phuong_xa, $tinh_thanh, $mac_dinh);
                
                $errors = $diaChi->validate();
                if (empty($errors)) {
                    $diaChi = $diaChiRepo->create($diaChi);
                    
                    if ($mac_dinh) {
                        $diaChiRepo->setDefault($khach_hang_id, $diaChi->getId());
                    }
                    
                    $_SESSION['success'] = 'Đã thêm địa chỉ thành công!';
                }
            }
            
            if (empty($errors)) {
                header('Location: manage_addresses.php?khach_hang_id=' . $khach_hang_id);
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách địa chỉ
$diaChiList = $diaChiRepo->findAllByKhachHangId($khach_hang_id);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Quản lý địa chỉ - <?= htmlspecialchars($khachHang->getHoTen()) ?></title>
  <link rel="stylesheet" href="../CSS/styles_admin.css">
</head>
<body>
  <div class="navbar">
    <div class="menu">
      <a href="san_pham_admin.php">Danh mục sản phẩm</a>
      <a href="don_hang_admin.php">Danh mục đơn hàng</a>
      <a href="user_admin.php" class="active">Quản lý tài khoản</a>
      <a href="thong_ke_doanh_thu.php">Thống kê</a>
    </div>
    <a href="../auth/logout.php" class="logout-btn">Logout</a>
  </div>
  
  <div class="container">
    <h2>Quản lý địa chỉ - <?= htmlspecialchars($khachHang->getHoTen()) ?></h2>
    
    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['success']) ?>
      </div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    
    <!-- Form thêm/sửa địa chỉ -->
    <div class="form-container" style="margin-bottom: 20px;">
      <h3><?= $editing_id > 0 ? 'Sửa địa chỉ' : 'Thêm địa chỉ mới' ?></h3>
      <form method="post">
        <div class="address-form-grid">
          <div class="form-group">
            <label class="form-label">Số nhà</label>
            <input type="text" name="so_nha" value="<?= htmlspecialchars($_POST['so_nha'] ?? $editing_diaChi?->getSoNha() ?? '') ?>" 
                   class="form-input">
          </div>
          <div class="form-group">
            <label class="form-label">Tên đường/Phố *</label>
            <input type="text" name="ten_duong" value="<?= htmlspecialchars($_POST['ten_duong'] ?? $editing_diaChi?->getTenDuong() ?? '') ?>" 
                   required class="form-input">
          </div>
          <div class="form-group">
            <label class="form-label">Phường/Xã *</label>
            <input type="text" name="phuong_xa" value="<?= htmlspecialchars($_POST['phuong_xa'] ?? $editing_diaChi?->getPhuongXa() ?? '') ?>" 
                   required class="form-input">
          </div>
          <div class="form-group">
            <label class="form-label">Tỉnh/Thành phố *</label>
            <input type="text" name="tinh_thanh" value="<?= htmlspecialchars($_POST['tinh_thanh'] ?? $editing_diaChi?->getTinhThanh() ?? '') ?>" 
                   required class="form-input">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Địa chỉ chi tiết (nếu không điền các trường trên)</label>
          <textarea name="dia_chi_chi_tiet" rows="2" class="form-textarea"><?= htmlspecialchars($_POST['dia_chi_chi_tiet'] ?? $editing_diaChi?->getDiaChiChiTiet() ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label class="checkbox-label">
            <input type="checkbox" name="mac_dinh" value="1" <?= ($_POST['mac_dinh'] ?? $editing_diaChi?->getMacDinh() ?? 0) ? 'checked' : '' ?>>
            <span>Đặt làm địa chỉ mặc định</span>
          </label>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn-add-new"><?= $editing_id > 0 ? 'Cập nhật' : 'Thêm địa chỉ' ?></button>
          <?php if ($editing_id > 0): ?>
            <a href="manage_addresses.php?khach_hang_id=<?= $khach_hang_id ?>" class="btn btn-secondary">Hủy</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
    
    <!-- Danh sách địa chỉ -->
    <div class="address-list-container">
      <h3>Danh sách địa chỉ (<?= count($diaChiList) ?>)</h3>
      <?php if (empty($diaChiList)): ?>
        <p class="empty-message">Chưa có địa chỉ nào</p>
      <?php else: ?>
        <table class="address-table">
          <thead>
            <tr>
              <th>Địa chỉ</th>
              <th class="center" style="width: 120px;">Mặc định</th>
              <th class="center" style="width: 200px;">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($diaChiList as $dc): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($dc->getDiaChiDayDu()) ?></strong>
                <?php if ($dc->getMacDinh()): ?>
                  <span class="address-default-badge">Mặc định</span>
                <?php endif; ?>
              </td>
              <td class="center">
                <?php if ($dc->getMacDinh()): ?>
                  <span style="color: #4caf50;">✓</span>
                <?php else: ?>
                  <a href="manage_addresses.php?khach_hang_id=<?= $khach_hang_id ?>&set_default=<?= $dc->getId() ?>" 
                     onclick="return confirm('Đặt địa chỉ này làm mặc định?')"
                     class="address-link">Đặt mặc định</a>
                <?php endif; ?>
              </td>
              <td class="center">
                <a href="manage_addresses.php?khach_hang_id=<?= $khach_hang_id ?>&edit=<?= $dc->getId() ?>" 
                   class="btn btn-info">Sửa</a>
                <a href="manage_addresses.php?khach_hang_id=<?= $khach_hang_id ?>&delete=<?= $dc->getId() ?>" 
                   class="btn btn-danger" 
                   onclick="return confirm('Bạn có chắc chắn muốn xóa địa chỉ này?')">Xóa</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
    
    <div style="margin-top: 20px;">
      <a href="edit_customer.php?id=<?= $khach_hang_id ?>" class="btn btn-secondary">
        ← Quay lại thông tin khách hàng
      </a>
    </div>
  </div>
</body>
</html>

