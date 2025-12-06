<?php
// user_admin.php - Quản lý tài khoản người dùng
session_start();
require '../config/db.php';

// Chỉ cho phép admin (loai_user='admin') quản lý tài khoản
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

$currentUserId = $_SESSION['user_id'];

// Xử lý xóa tài khoản
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    
    // Không cho phép xóa chính mình
    if ($delId == $currentUserId) {
        $_SESSION['error'] = 'Bạn không thể xóa chính tài khoản của mình!';
        header('Location: user_admin.php');
        exit;
    }
    
    // Lấy thông tin user cần xóa
    $userToDelete = $connection->prepare('SELECT id, loai_user FROM user WHERE id = :id');
    $userToDelete->execute([':id' => $delId]);
    $userInfo = $userToDelete->fetch(PDO::FETCH_OBJ);
    
    if (!$userInfo) {
        $_SESSION['error'] = 'Không tìm thấy tài khoản cần xóa!';
        header('Location: user_admin.php');
        exit;
    }
    
    // Kiểm tra nếu đang xóa admin, đảm bảo còn ít nhất 1 admin khác
    if ($userInfo->loai_user === 'admin') {
        $adminCount = $connection->prepare('SELECT COUNT(*) as count FROM user WHERE loai_user = "admin"');
        $adminCount->execute();
        $count = $adminCount->fetch(PDO::FETCH_OBJ);
        
        if ($count->count <= 1) {
            $_SESSION['error'] = 'Không thể xóa admin cuối cùng!';
            header('Location: user_admin.php');
            exit;
        }
    }
    
    try {
        $connection->beginTransaction();
        
        // Nếu là customer, xóa dữ liệu liên quan
        if ($userInfo->loai_user === 'customer') {
            // Lấy khach_hang_id
            $khStmt = $connection->prepare('SELECT id FROM khach_hang WHERE user_id = :user_id');
            $khStmt->execute([':user_id' => $delId]);
            $khachHang = $khStmt->fetch(PDO::FETCH_OBJ);
            
            if ($khachHang) {
                // Xóa địa chỉ (CASCADE sẽ tự xóa)
                $delDiaChi = $connection->prepare('DELETE FROM dia_chi WHERE khach_hang_id = :kh_id');
                $delDiaChi->execute([':kh_id' => $khachHang->id]);
                
                // Xóa khách hàng
                $delKh = $connection->prepare('DELETE FROM khach_hang WHERE id = :id');
                $delKh->execute([':id' => $khachHang->id]);
            }
        }
        
        // Xóa user
        $del = $connection->prepare('DELETE FROM user WHERE id = :id');
        $del->execute([':id' => $delId]);
        
        $connection->commit();
        $_SESSION['success'] = 'Đã xóa tài khoản thành công!';
    } catch (Exception $e) {
        $connection->rollBack();
        $_SESSION['error'] = 'Lỗi khi xóa tài khoản: ' . $e->getMessage();
    }
    
    header('Location: user_admin.php');
    exit;
}

// Lấy danh sách tài khoản (cả admin và customer)
$stmt = $connection->prepare('SELECT u.id, u.ten_dang_nhap, u.loai_user, u.email, u.created_at,
                                     k.ho_ten, k.so_dien_thoai
                              FROM user u 
                              LEFT JOIN khach_hang k ON k.user_id = u.id 
                              ORDER BY u.id DESC');
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_OBJ);

// Phân loại users
$adminUsers = [];
$customerUsers = [];
foreach ($users as $u) {
    if ($u->loai_user === 'customer') {
        $customerUsers[] = $u;
    } else {
        $adminUsers[] = $u;
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Quản lý tài khoản</title>
  <link rel="stylesheet" href="../CSS/styles_admin.css">
</head>
<body>
  <!-- Thanh menu -->
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
    <h2>Quản lý tài khoản</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-error">
        <?= htmlspecialchars($_SESSION['error']) ?>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['success']) ?>
      </div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <!-- Tab navigation -->
    <div class="tab-container">
      <button onclick="showTab('admin')" id="tab-admin" class="tab-btn active">
        👤 Quản trị viên (<?= count($adminUsers) ?>)
      </button>
      <button onclick="showTab('customer')" id="tab-customer" class="tab-btn">
        🛒 Khách hàng (<?= count($customerUsers) ?>)
      </button>
    </div>

    <!-- Admin Users Tab -->
    <div id="tab-content-admin" class="tab-content">
      <h3>Danh sách quản trị viên</h3>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Tên đăng nhập</th>
            <!-- <th>Email</th> -->
            <th>Quyền</th>
            <th>Ngày tạo</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($adminUsers as $u): ?>
          <tr>
            <td><?= $u->id ?></td>
            <td>
              <?= htmlspecialchars($u->ten_dang_nhap) ?>
              <?php if ($u->id == $currentUserId): ?>
                <span class="badge-current">(Bạn)</span>
              <?php endif; ?>
            </td>
            <!-- <td><?= htmlspecialchars($u->email ?: '-') ?></td> -->
            <td>
              <span class="badge badge-primary">
                Admin
              </span>
            </td>
            <td><?= $u->created_at ? date('d/m/Y', strtotime($u->created_at)) : '-' ?></td>
            <td>
              <a href="edit_user.php?id=<?= $u->id ?>" class="btn btn-info">Sửa</a>
              <?php if ($u->id != $currentUserId): ?>
                <a href="user_admin.php?delete=<?= $u->id ?>" class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản <?= htmlspecialchars($u->ten_dang_nhap) ?>?')">Xóa</a>
              <?php else: ?>
                <span class="badge-text">Không thể xóa</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <a href="../auth/register.php" class="btn-add-new">+ Thêm admin mới</a>
    </div>

    <!-- Customer Users Tab -->
    <div id="tab-content-customer" class="tab-content hidden">
      <h3>Danh sách khách hàng</h3>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Tên đăng nhập</th>
            <th>Họ tên</th>
            <th>Số điện thoại</th>
            <th>Email</th>
            <th>Ngày tạo</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($customerUsers as $u): ?>
          <tr>
            <td><?= $u->id ?></td>
            <td><?= htmlspecialchars($u->ten_dang_nhap) ?></td>
            <td><?= htmlspecialchars($u->ho_ten ?: '-') ?></td>
            <td><?= htmlspecialchars($u->so_dien_thoai ?: '-') ?></td>
            <td><?= htmlspecialchars($u->email ?: '-') ?></td>
            <td><?= $u->created_at ? date('d/m/Y', strtotime($u->created_at)) : '-' ?></td>
             <td>
               <?php 
               // Lấy khach_hang_id từ user_id
               $khStmt = $connection->prepare('SELECT id FROM khach_hang WHERE user_id = :user_id');
               $khStmt->execute([':user_id' => $u->id]);
               $khRow = $khStmt->fetch(PDO::FETCH_OBJ);
               $khach_hang_id = $khRow ? $khRow->id : null;
               ?>
               <?php if ($khach_hang_id): ?>
                 <a href="edit_customer.php?id=<?= $khach_hang_id ?>" class="btn btn-info">Sửa</a>
                 <a href="manage_addresses.php?khach_hang_id=<?= $khach_hang_id ?>" class="btn badge-success">📍 Địa chỉ</a>
               <?php endif; ?>
               <a href="user_admin.php?delete=<?= $u->id ?>" class="btn btn-danger" onclick="return confirm('Xóa tài khoản khách hàng này?')">Xóa</a>
             </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($customerUsers)): ?>
          <tr>
            <td colspan="7" class="table-center">
              Chưa có khách hàng nào
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script src="../JS/admin_tabs.js"></script>
</body>
</html>
