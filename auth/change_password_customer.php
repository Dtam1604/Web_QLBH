<?php
/**
 * Trang đổi mật khẩu cho khách hàng
 */
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    header('Location: login_customer.php');
    exit;
}

require '../config/db.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['mat_khau_cu'] ?? '';
    $new_password = $_POST['mat_khau_moi'] ?? '';
    $confirm_password = $_POST['mat_khau_moi_confirm'] ?? '';
    
    // Validation
    if (empty($old_password)) {
        $errors[] = 'Vui lòng nhập mật khẩu cũ.';
    }
    
    if (empty($new_password)) {
        $errors[] = 'Vui lòng nhập mật khẩu mới.';
    } elseif (strlen($new_password) < 6) {
        $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = 'Mật khẩu xác nhận không khớp.';
    }
    
    if ($old_password === $new_password) {
        $errors[] = 'Mật khẩu mới phải khác mật khẩu cũ.';
    }
    
    if (empty($errors)) {
        try {
            // Lấy thông tin user hiện tại
            $stmt = $connection->prepare('SELECT id, mat_khau FROM user WHERE id = :id AND loai_user = "customer"');
            $stmt->execute([':id' => $_SESSION['customer_id']]);
            $user = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$user) {
                $errors[] = 'Không tìm thấy tài khoản.';
            } elseif (!password_verify($old_password, $user->mat_khau)) {
                $errors[] = 'Mật khẩu cũ không đúng.';
            } else {
                // Cập nhật mật khẩu mới
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $connection->prepare('UPDATE user SET mat_khau = :mat_khau WHERE id = :id');
                $updateStmt->execute([
                    ':mat_khau' => $new_hash,
                    ':id' => $_SESSION['customer_id']
                ]);
                
                $success = true;
            }
        } catch (Exception $e) {
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
  <title>Đổi mật khẩu - Khách hàng</title>
  <link rel="stylesheet" href="../CSS/auth.css">
</head>
<body>
  <div class="auth-card" style="max-width: 500px;">
    <h2>🔑 Đổi mật khẩu</h2>
    <p class="lead">Thay đổi mật khẩu tài khoản của bạn</p>

    <?php if ($success): ?>
      <div class="success-note">
        <strong>✓ Thành công!</strong> Mật khẩu đã được thay đổi. 
        <a href="../pages/san_pham.php" style="color: #0b6a3b; text-decoration: underline;">Quay lại trang chủ</a>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="error-list">
        <ul style="margin: 0; padding-left: 20px;">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form id="change-password-form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
      <div class="form-row password-row">
        <label for="mat_khau_cu">Mật khẩu cũ *</label>
        <input type="password" id="mat_khau_cu" name="mat_khau_cu" required autofocus>
        <button type="button" class="toggle-password" data-target="#mat_khau_cu">Hiện</button>
      </div>

      <div class="form-row password-row">
        <label for="mat_khau_moi">Mật khẩu mới *</label>
        <input type="password" id="mat_khau_moi" name="mat_khau_moi" required minlength="6">
        <button type="button" class="toggle-password" data-target="#mat_khau_moi">Hiện</button>
      </div>

      <div class="form-row password-row">
        <label for="mat_khau_moi_confirm">Xác nhận mật khẩu mới *</label>
        <input type="password" id="mat_khau_moi_confirm" name="mat_khau_moi_confirm" required minlength="6">
        <button type="button" class="toggle-password" data-target="#mat_khau_moi_confirm">Hiện</button>
      </div>

      <div class="form-actions">
        <button class="btn-primary" type="submit">Đổi mật khẩu</button>
        <span class="small-note">
          <a class="link-muted" href="../pages/san_pham.php">Quay lại</a>
        </span>
      </div>
    </form>
    <?php endif; ?>
  </div>
  <script src="../JS/auth.js"></script>
</body>
</html>

