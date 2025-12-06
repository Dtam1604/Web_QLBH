<?php
/**
 * Trang đăng nhập cho khách hàng
 */
session_start();

// Nếu đã đăng nhập, redirect
if (isset($_SESSION['customer_id'])) {
    header('Location: ../pages/san_pham.php');
    exit;
}

$error = '';

// Hiển thị thông báo nếu có
if (isset($_SESSION['login_required_message'])) {
    $error = $_SESSION['login_required_message'];
    unset($_SESSION['login_required_message']);
}

if (isset($_POST['submit'])) {
    require '../config/db.php';
    
    $tai_khoan = trim($_POST['tai_khoan'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($tai_khoan) || empty($password)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } else {
        $sql = 'SELECT u.*, k.id as khach_hang_id, k.ho_ten, k.so_dien_thoai 
                FROM user u 
                LEFT JOIN khach_hang k ON k.user_id = u.id 
                WHERE u.ten_dang_nhap = :ten_dang_nhap AND u.loai_user = "customer"';
        $statement = $connection->prepare($sql);
        $statement->execute([':ten_dang_nhap' => $tai_khoan]);
        $user = $statement->fetch(PDO::FETCH_OBJ);

        if ($user && password_verify($password, $user->mat_khau)) {
            // Lưu thông tin vào session
            $_SESSION['customer_id'] = $user->id;
            $_SESSION['customer_username'] = $user->ten_dang_nhap;
            $_SESSION['customer_name'] = $user->ho_ten ?? $user->ten_dang_nhap;
            $_SESSION['khach_hang_id'] = $user->khach_hang_id ?? null;
            
            // Redirect về trang được yêu cầu hoặc trang sản phẩm
            $redirect = $_GET['redirect'] ?? '../pages/san_pham.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Khách hàng</title>
    <link rel="stylesheet" href="../CSS/auth.css">
</head>
<body>
    <div class="auth-card">
        <h2>🔐 Đăng nhập</h2>
        <p class="lead">Đăng nhập để mua sắm nhanh chóng</p>
        
        <?php if ($error): ?>
            <div class="error-list"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form id="login-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <div class="form-row">
                <label for="tai_khoan">Tên đăng nhập</label>
                <input type="text" id="tai_khoan" name="tai_khoan" 
                       value="<?= htmlspecialchars($_POST['tai_khoan'] ?? '') ?>" 
                       required autofocus>
            </div>

            <div class="form-row password-row">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required>
                <button type="button" class="toggle-password" data-target="#password">Hiện</button>
            </div>

            <div class="form-actions">
                <button class="btn-primary" type="submit" name="submit">Đăng nhập</button>
                <span class="small-note">
                    Chưa có tài khoản? <a class="link-muted" href="register_customer.php">Đăng ký ngay</a>
                </span>
            </div>
        </form>
        
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <p style="text-align: center; color: #666; font-size: 0.9em;">
                Bạn là quản trị viên? 
                <a href="login.php" class="link-muted" style="color: var(--primary);">Đăng nhập admin</a>
            </p>
        </div>
    </div>

    <script src="../JS/auth.js"></script>
</body>
</html>

