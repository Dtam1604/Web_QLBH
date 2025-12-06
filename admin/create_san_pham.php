<?php
// create_san_pham.php - Admin form to add a new product
require '../config/db.php';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Thêm sản phẩm mới</title>
  <link rel="stylesheet" href="../CSS/styles_admin.css">
</head>
<body>
  <div class="addProduct-container">
    <h2>Thêm sản phẩm mới</h2>
    <form action="../controllers/xu_ly_them_san_pham.php" method="post" enctype="multipart/form-data">
      <label for="ten_san_pham">Tên sản phẩm</label>
      <input type="text" id="ten_san_pham" name="ten_san_pham" required>

      <label for="gia_san_pham">Giá sản phẩm</label>
      <input type="number" id="gia_san_pham" name="gia_san_pham" step="0.01" required>

      <label for="anh_san_pham">Ảnh sản phẩm</label>
      <input type="file" id="anh_san_pham" name="anh_san_pham" accept="image/*" required>

      <!-- Preview area - JS project_demo.js will attach a handler if present -->
      <div class="preview">
        <img id="previewImg" src="" alt="Ảnh preview" style="display:none; max-width:300px; margin-top:10px;" />
      </div>

      <button type="submit" class="btn-submit">Thêm sản phẩm</button>
    </form>

    <a href="san_pham_admin.php" class="btn-back">← Quay lại danh mục</a>
  </div>

  <script src="../JS/project_demo.js"></script>
</body>
</html>