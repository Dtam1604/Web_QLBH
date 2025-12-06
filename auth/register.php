<?php
// register.php - simple user registration
// Requires: db.php providing $connection (PDO)

session_start();
require '../config/db.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['ten_dang_nhap'] ?? '');
    $password = $_POST['mat_khau'] ?? '';
    $password2 = $_POST['mat_khau_confirm'] ?? '';

    // Basic validation
    if ($username === '') {
        $errors[] = 'Tên đăng nhập không được để trống.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }
    if ($password !== $password2) {
        $errors[] = 'Mật khẩu xác nhận không khớp.';
    }

    if (empty($errors)) {
        // Check if username exists
        $stmt = $connection->prepare('SELECT id FROM user WHERE ten_dang_nhap = :u');
        $stmt->execute([':u' => $username]);
        $existing = $stmt->fetch(PDO::FETCH_OBJ);
        if ($existing) {
            $errors[] = 'Tên đăng nhập đã tồn tại, vui lòng chọn tên khác.';
        } else {
            // Hash the password and insert (mặc định là admin)
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $connection->prepare('INSERT INTO user (ten_dang_nhap, mat_khau, loai_user) VALUES (:u, :h, "admin")');
            $ok = $ins->execute([':u' => $username, ':h' => $hash]);
            if ($ok) {
                $success = true;
                // Optionally redirect to login page
                header('Location: login.php');
                exit;
            } else {
                $errors[] = 'Không thể tạo tài khoản, vui lòng thử lại sau.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng ký</title>
  <link rel="stylesheet" href="../CSS/auth.css">
</head>
<body>
  <div class="auth-card">
    <h2>📝 Đăng ký Admin</h2>
    <p class="lead">Tạo tài khoản quản trị viên mới</p>

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
        <label for="ten_dang_nhap">Tên đăng nhập</label>
        <input type="text" id="ten_dang_nhap" name="ten_dang_nhap" value="<?= isset($username) ? htmlspecialchars($username) : '' ?>" required>
      </div>

      <div class="form-row password-row">
        <label for="mat_khau">Mật khẩu</label>
        <input type="password" id="mat_khau" name="mat_khau" required>
        <button type="button" class="toggle-password" data-target="#mat_khau">Hiện</button>
      </div>

      <div class="form-row password-row">
        <label for="mat_khau_confirm">Xác nhận mật khẩu</label>
        <input type="password" id="mat_khau_confirm" name="mat_khau_confirm" required>
        <button type="button" class="toggle-password" data-target="#mat_khau_confirm">Hiện</button>
      </div>

      <div class="form-actions">
        <button class="btn-primary" type="submit">Đăng ký</button>
        <span class="small-note">Đã có tài khoản? <a class="link-muted" href="login.php">Đăng nhập admin</a></span>
      </div>
    </form>
    
    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
      <p style="text-align: center; color: #666; font-size: 0.9em;">
        Bạn là khách hàng? 
        <a href="register_customer.php" style="color: #1976d2;">Đăng ký khách hàng</a>
      </p>
    </div>
  </div>
  <script src="../JS/auth.js"></script>
</body>
</html>
