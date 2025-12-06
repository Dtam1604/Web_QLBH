<?php
session_start();
if(isset($_POST['submit'])) {
    require '../config/db.php';
    $tai_khoan = $_POST['tai_khoan'];
    $password = $_POST['password'];
    $sql = 'SELECT * FROM user WHERE ten_dang_nhap=:ten_dang_nhap AND (loai_user = "admin" OR loai_user IS NULL)';
    $statement = $connection->prepare($sql);
    $statement->execute([':ten_dang_nhap' => $tai_khoan ]);
    $user = $statement->fetch(PDO::FETCH_OBJ);

    // Use password_verify to compare submitted password with hashed password in DB
    if ($user != null && password_verify($password, $user->mat_khau)) {
        $lifetime = 24*60*60;
        // store minimal info in session only; do not store plaintext password in cookies
    $_SESSION['user'] = $user->ten_dang_nhap;
    $_SESSION['user_id'] = $user->id ?? null;
    $_SESSION['is_admin'] = $user->is_admin ?? 0;
        // Optionally set a username cookie (not password) for convenience
        setcookie('username', $tai_khoan, time() + $lifetime, "/");
        header('Location: ../admin/san_pham_admin.php');
        exit;
    } else {
        echo '<p style = "color:red";> Tên đăng nhập hoặc mật khẩu không đúng, mời đăng nhập lại! </p>';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Đăng nhập</title>
        <link rel="stylesheet" href="../CSS/auth.css">
</head>
<body>
    <div class="auth-card">
        <h2>🔐 Đăng nhập Admin</h2>
        <p class="lead">Nhập tài khoản để quản lý hệ thống</p>
        <?php if(isset($_POST['submit']) && isset($user) && $user == null): ?>
            <div class="error-list">Tên đăng nhập hoặc mật khẩu không đúng, mời đăng nhập lại!</div>
        <?php endif; ?>

        <form id="login-form" action="<?php echo  htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <div class="form-row">
                <label for="tai_khoan">Tài khoản</label>
                <input type="text" id="tai_khoan" name="tai_khoan" required>
            </div>

            <div class="form-row password-row">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required>
                <button type="button" class="toggle-password" data-target="#password">Hiện</button>
            </div>

            <div class="form-actions">
                <button class="btn-primary" type="submit" name="submit">Đăng nhập</button>
              <!-- <span class="small-note">Chưa có tài khoản? <a class="link-muted" href="register.php">Đăng ký admin</a></span> -->
            </div>
        </form>
        
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <p style="text-align: center; color: #666; font-size: 0.9em;">
                Bạn là khách hàng? 
                <a href="login_customer.php" style="color: #1976d2;">Đăng nhập khách hàng</a>
            </p>
        </div>
    </div>

    <script src="../JS/auth.js"></script>
</body>
</html>