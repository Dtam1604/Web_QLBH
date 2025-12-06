<?php
// edit_user.php - Sửa thông tin tài khoản
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

// Lấy thông tin user cần sửa
$stmt = $connection->prepare('SELECT id, ten_dang_nhap, loai_user, email FROM user WHERE id = :id');
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_OBJ);
if (!$user) {
    header('Location: user_admin.php');
    exit;
}

// Chỉ cho phép sửa admin (không sửa customer)
if ($user->loai_user !== 'admin') {
    header('Location: user_admin.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['ten_dang_nhap'] ?? '');
  $password = $_POST['mat_khau'] ?? '';
  $password2 = $_POST['mat_khau_confirm'] ?? '';

  if ($username === '') {
    $errors[] = 'Tên đăng nhập không được để trống.';
  }
  if ($password !== '' && strlen($password) < 6) {
    $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
  }
  if ($password !== '' && $password !== $password2) {
    $errors[] = 'Mật khẩu xác nhận không khớp.';
  }

  if (empty($errors)) {
    // Kiểm tra trùng tên đăng nhập (trừ chính mình)
    $stmt2 = $connection->prepare('SELECT id FROM user WHERE ten_dang_nhap = :u AND id != :id');
    $stmt2->execute([':u' => $username, ':id' => $id]);
    $existing = $stmt2->fetch(PDO::FETCH_OBJ);
    if ($existing) {
      $errors[] = 'Tên đăng nhập đã tồn tại, vui lòng chọn tên khác.';
    } else {
      // Update
      try {
        if ($password !== '') {
          $hash = password_hash($password, PASSWORD_DEFAULT);
          $upd = $connection->prepare('UPDATE user SET ten_dang_nhap = :u, mat_khau = :h WHERE id = :id');
          $ok = $upd->execute([':u' => $username, ':h' => $hash, ':id' => $id]);
        } else {
          $upd = $connection->prepare('UPDATE user SET ten_dang_nhap = :u WHERE id = :id');
          $ok = $upd->execute([':u' => $username, ':id' => $id]);
        }
        
        if ($ok) {
          header('Location: user_admin.php');
          exit;
        } else {
          $errors[] = 'Không thể cập nhật tài khoản.';
        }
      } catch (Exception $e) {
        $errors[] = 'Lỗi: ' . $e->getMessage();
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
  <title>Sửa tài khoản</title>
  <link rel="stylesheet" href="../CSS/auth.css">
</head>
<body>
  <div class="auth-card">
    <h2>Sửa tài khoản: <?= htmlspecialchars($user->ten_dang_nhap) ?></h2>
    <?php if (!empty($errors)): ?>
      <div class="error-list">
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form method="post">
      <div class="form-row">
        <label for="ten_dang_nhap">Tên đăng nhập</label>
        <input type="text" id="ten_dang_nhap" name="ten_dang_nhap" value="<?= htmlspecialchars($_POST['ten_dang_nhap'] ?? $user->ten_dang_nhap) ?>" required>
      </div>
      <div class="form-row password-row">
        <label for="mat_khau">Mật khẩu mới (nếu đổi)</label>
        <input type="password" id="mat_khau" name="mat_khau">
        <button type="button" class="toggle-password" data-target="#mat_khau">Hiện</button>
      </div>
      <div class="form-row password-row">
        <label for="mat_khau_confirm">Xác nhận mật khẩu mới</label>
        <input type="password" id="mat_khau_confirm" name="mat_khau_confirm">
        <button type="button" class="toggle-password" data-target="#mat_khau_confirm">Hiện</button>
      </div>
      <?php if ($id == $_SESSION['user_id']): ?>
        <div style="color: #ff9800; margin-top: 10px; padding: 10px; background: #fff3cd; border-radius: 4px;">
          ⚠ Bạn đang sửa chính tài khoản của mình
        </div>
      <?php endif; ?>
      <div class="form-actions">
        <button class="btn-primary" type="submit">Lưu thay đổi</button>
        <a class="link-muted" href="user_admin.php">Quay lại</a>
      </div>
    </form>
  </div>
  <script src="../JS/auth.js"></script>
</body>
</html>
