<?php
require '../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: san_pham_admin.php");
    exit;
}

$sanPhamRepo = new SanPhamRepository();
$san_pham = $sanPhamRepo->findById($id);

if (!$san_pham) {
    header("Location: san_pham_admin.php");
    exit;
}

 ?>

<!doctype html>
<html lang="en">
<head>
  <title>Sửa sản phẩm </title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="../CSS/styles_admin.css">  
</head>
<body>
  <div class="addProduct-container">
    <h2>Sửa sản phẩm <?= htmlspecialchars($san_pham->getMaSanPham()); ?> </h2>
    <form action="../controllers/xu_ly_sua_san_pham.php" method="post" enctype="multipart/form-data" >
     
      <label for="ten_san_pham">Tên sản phẩm</label>
      <input type="text" id="ten_san_pham" name="ten_san_pham" value="<?= htmlspecialchars($san_pham->getTenSanPham()); ?>" required>

      <label for="gia_san_pham">Giá sản phẩm</label>
      <input type="number" id="gia_san_pham" name="gia_san_pham" value="<?= $san_pham->getGiaSanPham(); ?>" required>
       <!-- Truyền tên ảnh cũ sản phẩm ẩn -->
      <input type="hidden" id="anh_san_pham_old" name="anh_san_pham_old" value="<?= htmlspecialchars($san_pham->getAnhSanPham()); ?>" required>

      <label for="anh_san_pham">Ảnh sản phẩm</label>
      <input type="file" id="anh_san_pham" name="anh_san_pham" accept="image/*" >

      <!-- Hiển thị ảnh preview -->
      <div class="preview">
        <img id="previewImg" src="../Images/<?= htmlspecialchars($san_pham->getAnhSanPham()); ?>"  alt="Ảnh sản phẩm"
		     style="<?= empty($san_pham->getAnhSanPham()) ? 'display:none;' : 'display:block'; ?>"	>
      </div>
	  <!-- Truyền ID sản phẩm ẩn -->
      <input type="hidden" name="id_san_pham" value="<?= $san_pham->getId(); ?>">
      <button type="submit" class="btn-submit">Sửa sản phẩm</button>
    </form>

    <a href="san_pham_admin.php" class="btn-back">← Quay lại danh mục</a>
  </div>

  <script src="../JS/project_demo.js"></script>
</body>
</html>

