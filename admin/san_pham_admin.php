<?php
require '../config/db.php';
require_once '../utils/pagination.php';

// Handle search and sort
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10; // Số sản phẩm mỗi trang

$sanPhamRepo = new SanPhamRepository();
$totalItems = $sanPhamRepo->count($search);
$totalPages = ceil($totalItems / $perPage);
$list_san_pham = $sanPhamRepo->findAllPaginated($page, $perPage, $search, $sort);
 ?>

<!doctype html>
<html lang="en">
<head>
  <title>Danh mục sản phẩm</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="../CSS/styles_admin.css">  
</head>
<body>
  <!-- Thanh menu -->
  <div class="navbar">
    <div class="menu">
      <a href="san_pham_admin.php" class="active">Danh mục sản phẩm</a>
      <a href="don_hang_admin.php">Danh mục đơn hàng</a>
      <a href="user_admin.php">Quản lý tài khoản</a>
      <a href="thong_ke_doanh_thu.php">Thống kê</a>
    </div>
    <a href="../auth/logout.php" class="logout-btn">Logout</a>
  </div>

  <!-- Danh mục sản phẩm -->
  <div class="container">
    <h2>Danh mục sản phẩm</h2>
    <form id="searchSortForm" method="get" class="admin-search-form">
      <input type="text" name="search" id="searchInput" class="admin-search-input" placeholder="🔍 Tìm kiếm sản phẩm..." value="<?= htmlspecialchars($search) ?>">
      <select name="sort" id="sortSelect" class="admin-sort-select">
        <option value="">Sắp xếp</option>
        <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Giá tăng dần</option>
        <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Giá giảm dần</option>
      </select>
       <a href="create_san_pham.php" class="btn-add-new">+ Thêm sản phẩm mới</a>
    </form>
    <script src="../JS/project_demo.js"></script>
    <table>
      <thead>
        <tr>
          <th>Mã sản phẩm</th>
          <th>Tên sản phẩm</th>
          <th>Giá</th>
          <th>Ảnh SP</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($list_san_pham as $san_pham): ?>
      <tr>
       <td><?= htmlspecialchars($san_pham->getMaSanPham()); ?></td>
       <td><?= htmlspecialchars($san_pham->getTenSanPham()); ?></td>
       <td><?= $san_pham->getGiaFormatted(); ?></td>
       <td><img src="../Images/<?= htmlspecialchars($san_pham->getAnhSanPham()); ?>" alt="Ảnh sản phẩm" width="100"></td>
       <td>
        <a href="edit_san_pham.php?id=<?= $san_pham->getId() ?>" class="btn btn-info">Edit</a>
        <a onclick="return confirm('Bạn có thực sự muốn xóa sản phẩm này?')" href="../controllers/delete_san_pham.php?id=<?= $san_pham->getId() ?>" class='btn btn-danger'>Delete</a>
       </td>
       </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    
    <!-- Pagination -->
    <?php 
    if ($totalPages > 1) {
        echo renderPagination($page, $totalPages, 'san_pham_admin.php');
    }
    ?>
    
    <!-- Hiển thị thông tin phân trang -->
    <div class="pagination-info">
      Hiển thị <?= count($list_san_pham) ?> / <?= $totalItems ?> sản phẩm
      <?php if ($totalPages > 1): ?>
        (Trang <?= $page ?> / <?= $totalPages ?>)
      <?php endif; ?>
    </div>
   
  </div>
</body>
</html>
