<?php
/**
 * Trang đăng ký cho khách hàng
 */
session_start();

// Nếu đã đăng nhập, redirect
if (isset($_SESSION['customer_id'])) {
    header('Location: ../pages/san_pham.php');
    exit;
}

require '../config/db.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['ten_dang_nhap'] ?? '');
    $password = $_POST['mat_khau'] ?? '';
    $password2 = $_POST['mat_khau_confirm'] ?? '';
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $so_dien_thoai = trim($_POST['so_dien_thoai'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $so_nha = trim($_POST['so_nha'] ?? '');
    $ten_duong = trim($_POST['ten_duong'] ?? '');
    $phuong_xa = trim($_POST['phuong_xa'] ?? '');
    $tinh_thanh = trim($_POST['tinh_thanh'] ?? '');

    // Validation
    if (empty($username)) {
        $errors[] = 'Tên đăng nhập không được để trống.';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Tên đăng nhập phải có ít nhất 3 ký tự.';
    }
    
    if (empty($password)) {
        $errors[] = 'Mật khẩu không được để trống.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }
    
    if ($password !== $password2) {
        $errors[] = 'Mật khẩu xác nhận không khớp.';
    }
    
    if (empty($ho_ten)) {
        $errors[] = 'Họ tên không được để trống.';
    }
    
    if (empty($so_dien_thoai)) {
        $errors[] = 'Số điện thoại không được để trống.';
    } elseif (!preg_match('/^[0-9]{10,11}$/', $so_dien_thoai)) {
        $errors[] = 'Số điện thoại không hợp lệ.';
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if (empty($errors)) {
        try {
            $connection->beginTransaction();
            
            // Kiểm tra username đã tồn tại chưa
            $stmt = $connection->prepare('SELECT id FROM user WHERE ten_dang_nhap = :u');
            $stmt->execute([':u' => $username]);
            if ($stmt->fetch()) {
                $errors[] = 'Tên đăng nhập đã tồn tại, vui lòng chọn tên khác.';
            } else {
                // Tạo user
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insUser = $connection->prepare('INSERT INTO user (ten_dang_nhap, mat_khau, loai_user, email) 
                                                VALUES (:u, :h, "customer", :e)');
                $insUser->execute([':u' => $username, ':h' => $hash, ':e' => $email ?: null]);
                $user_id = $connection->lastInsertId();
                
                // Tạo khách hàng và liên kết với user
                $khachHangRepo = new KhachHangRepository();
                $khachHang = new KhachHang(null, $ho_ten, $so_dien_thoai, $email);
                $khachHang = $khachHangRepo->create($khachHang);
                
                // Cập nhật user_id vào khach_hang
                $updateKh = $connection->prepare('UPDATE khach_hang SET user_id = :user_id WHERE id = :id');
                $updateKh->execute([':user_id' => $user_id, ':id' => $khachHang->getId()]);
                
                // Tạo địa chỉ nếu có thông tin
                if (!empty($ten_duong) || !empty($phuong_xa) || !empty($tinh_thanh)) {
                    $diaChiRepo = new DiaChiRepository();
                    $diaChi = new DiaChi(null, $khachHang->getId(), null, 
                                        $so_nha ?: null, $ten_duong ?: null, 
                                        $phuong_xa ?: null, 
                                        $tinh_thanh ?: null, 1); // mac_dinh = 1 cho địa chỉ đầu tiên
                    $diaChiRepo->create($diaChi);
                }
                
                $connection->commit();
                
                // Tự động đăng nhập
                $_SESSION['customer_id'] = $user_id;
                $_SESSION['customer_username'] = $username;
                $_SESSION['customer_name'] = $ho_ten;
                $_SESSION['khach_hang_id'] = $khachHang->getId();
                
                header('Location: ../pages/san_pham.php');
                exit;
            }
        } catch (Exception $e) {
            $connection->rollBack();
            $errors[] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng ký - Khách hàng</title>
  <link rel="stylesheet" href="../CSS/auth.css">
</head>
<body>
  <div class="auth-card" style="max-width: 500px;">
    <h2>📝 Đăng ký tài khoản</h2>
    <p class="lead">Tạo tài khoản để mua sắm nhanh chóng và tiện lợi</p>

    <?php if (!empty($errors)): ?>
      <div class="error-list">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form id="register-form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
      <div class="form-row">
        <label for="ten_dang_nhap">Tên đăng nhập *</label>
        <input type="text" id="ten_dang_nhap" name="ten_dang_nhap" 
               value="<?= htmlspecialchars($_POST['ten_dang_nhap'] ?? '') ?>" 
               required minlength="3">
      </div>

      <div class="form-row">
        <label for="ho_ten">Họ tên *</label>
        <input type="text" id="ho_ten" name="ho_ten" 
               value="<?= htmlspecialchars($_POST['ho_ten'] ?? '') ?>" 
               required>
      </div>

      <div class="form-row">
        <label for="so_dien_thoai">Số điện thoại *</label>
        <input type="tel" id="so_dien_thoai" name="so_dien_thoai" 
               value="<?= htmlspecialchars($_POST['so_dien_thoai'] ?? '') ?>" 
               required pattern="[0-9]{10,11}" 
               placeholder="10-11 chữ số">
      </div>

      <div class="form-row">
        <label for="email">Email (tùy chọn)</label>
        <input type="email" id="email" name="email" 
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <h4 style="margin-top: 20px; margin-bottom: 10px; color: #333; font-size: 1.1em;">Địa chỉ (tùy chọn)</h4>
      
      <div class="form-row">
        <label for="so_nha">Số nhà</label>
        <input type="text" id="so_nha" name="so_nha" 
               value="<?= htmlspecialchars($_POST['so_nha'] ?? '') ?>" 
               placeholder="Ví dụ: 123">
      </div>
      
      <div class="form-row">
        <label for="ten_duong">Tên đường/Phố</label>
        <input type="text" id="ten_duong" name="ten_duong" 
               value="<?= htmlspecialchars($_POST['ten_duong'] ?? '') ?>" 
               placeholder="Ví dụ: Nguyễn Văn Linh">
      </div>
      
      <div class="form-row">
        <label for="phuong_xa">Phường/Xã</label>
        <input type="text" id="phuong_xa" name="phuong_xa" 
               value="<?= htmlspecialchars($_POST['phuong_xa'] ?? '') ?>" 
               placeholder="Ví dụ: Phường 1">
      </div>
      
      <div class="form-row">
        <label for="tinh_thanh">Tỉnh/Thành phố</label>
        <input type="text" id="tinh_thanh" name="tinh_thanh" 
               value="<?= htmlspecialchars($_POST['tinh_thanh'] ?? '') ?>" 
               placeholder="Ví dụ: TP. Hồ Chí Minh">
      </div>

      <div class="form-row password-row">
        <label for="mat_khau">Mật khẩu *</label>
        <input type="password" id="mat_khau" name="mat_khau" required minlength="6">
        <button type="button" class="toggle-password" data-target="#mat_khau">Hiện</button>
      </div>

      <div class="form-row password-row">
        <label for="mat_khau_confirm">Xác nhận mật khẩu *</label>
        <input type="password" id="mat_khau_confirm" name="mat_khau_confirm" required minlength="6">
        <button type="button" class="toggle-password" data-target="#mat_khau_confirm">Hiện</button>
      </div>

      <div class="form-actions">
        <button class="btn-primary" type="submit">Đăng ký</button>
        <span class="small-note">
          Đã có tài khoản? <a class="link-muted" href="login_customer.php">Đăng nhập</a>
        </span>
      </div>
    </form>
  </div>
  <script src="../JS/auth.js"></script>
</body>
</html>

