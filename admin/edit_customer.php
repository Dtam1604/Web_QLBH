<?php
// edit_customer.php - Sửa thông tin khách hàng
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
    header('Location: user_admin.php');
    exit;
}

// Lấy thông tin khách hàng
$khachHangRepo = new KhachHangRepository();
$khachHang = $khachHangRepo->findById($id);

if (!$khachHang) {
    header('Location: user_admin.php');
    exit;
}

// Lấy thông tin user liên kết
$userStmt = $connection->prepare('SELECT id, ten_dang_nhap, email FROM user WHERE id = (SELECT user_id FROM khach_hang WHERE id = :id)');
$userStmt->execute([':id' => $id]);
$user = $userStmt->fetch(PDO::FETCH_OBJ);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $so_dien_thoai = trim($_POST['so_dien_thoai'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $user_email = trim($_POST['user_email'] ?? '');

    // Validate
    if (empty($ho_ten)) {
        $errors[] = 'Họ tên không được để trống.';
    }
    if (empty($so_dien_thoai)) {
        $errors[] = 'Số điện thoại không được để trống.';
    }
    
    // Kiểm tra số điện thoại trùng (trừ chính mình)
    $checkPhone = $connection->prepare('SELECT id FROM khach_hang WHERE so_dien_thoai = :phone AND id != :id');
    $checkPhone->execute([':phone' => $so_dien_thoai, ':id' => $id]);
    if ($checkPhone->fetch()) {
        $errors[] = 'Số điện thoại đã được sử dụng bởi khách hàng khác.';
    }

    if (empty($errors)) {
        try {
            $connection->beginTransaction();
            
            // Cập nhật thông tin khách hàng
            $khachHang->setHoTen($ho_ten);
            $khachHang->setSoDienThoai($so_dien_thoai);
            $khachHang->setEmail($email ?: null);
            $khachHangRepo->update($khachHang);
            
            // Cập nhật email trong user nếu có
            if ($user && $user_email) {
                $updateUser = $connection->prepare('UPDATE user SET email = :email WHERE id = :id');
                $updateUser->execute([':email' => $user_email, ':id' => $user->id]);
            }
            
            $connection->commit();
            $_SESSION['success'] = 'Đã cập nhật thông tin khách hàng thành công!';
            header('Location: user_admin.php');
            exit;
        } catch (Exception $e) {
            $connection->rollBack();
            $errors[] = 'Lỗi: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sửa thông tin khách hàng</title>
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
    <h2>Sửa thông tin khách hàng</h2>
    
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    
    <div class="form-container">
      <form method="post">
        <div class="form-group">
          <label class="form-label">Họ tên *</label>
          <input type="text" name="ho_ten" value="<?= htmlspecialchars($_POST['ho_ten'] ?? $khachHang->getHoTen()) ?>" 
                 required class="form-input">
        </div>
        
        <div class="form-group">
          <label class="form-label">Số điện thoại *</label>
          <input type="text" name="so_dien_thoai" value="<?= htmlspecialchars($_POST['so_dien_thoai'] ?? $khachHang->getSoDienThoai()) ?>" 
                 required class="form-input">
        </div>
        
        <div class="form-group">
          <label class="form-label">Email khách hàng</label>
          <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $khachHang->getEmail() ?? '') ?>" 
                 class="form-input">
        </div>
        
        <?php if ($user): ?>
        <div class="form-group">
          <label class="form-label">Tên đăng nhập (User)</label>
          <input type="text" value="<?= htmlspecialchars($user->ten_dang_nhap) ?>" 
                 disabled class="form-input">
          <small class="form-text">Tên đăng nhập không thể thay đổi</small>
        </div>
        
        <div class="form-group">
          <label class="form-label">Email tài khoản (User)</label>
          <input type="email" name="user_email" value="<?= htmlspecialchars($_POST['user_email'] ?? $user->email ?? '') ?>" 
                 class="form-input">
        </div>
        <?php endif; ?>
        
        <div class="form-actions">
          <button type="submit" class="btn-add-new">Lưu thay đổi</button>
          <a href="user_admin.php" class="btn btn-secondary">Quay lại</a>
        </div>
      </form>
    </div>
    
    <div style="margin-top: 20px;">
      <a href="manage_addresses.php?khach_hang_id=<?= $id ?>" class="btn-add-new" style="display: inline-block;">
        📍 Quản lý địa chỉ
      </a>
    </div>
  </div>
</body>
</html>

