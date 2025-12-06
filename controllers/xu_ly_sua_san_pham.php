<?php
require __DIR__ . '/../config/db.php';

$message = '';
$uploadDir = __DIR__ . '/../Images/';

if (isset($_POST['id_san_pham'])) {
    $id = $_POST['id_san_pham'];
    $sanPhamRepo = new SanPhamRepository();
    $sanPham = $sanPhamRepo->findById($id);
    
    if (!$sanPham) {
        header("Location: ../admin/san_pham_admin.php");
        exit;
    }
    
    $sanPham->setTenSanPham($_POST['ten_san_pham']);
    $sanPham->setGiaSanPham(number_format($_POST['gia_san_pham'], 2, '.', ''));
    $anh_san_pham = $_POST['anh_san_pham_old'];
    
    $file = $_FILES['anh_san_pham'];
    if ($file['error'] == 0) {
        // Kiểm tra MIME cho ảnh
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
        if (!array_key_exists($mime, $allowed)) {
            exit('Sai định dạng ảnh!');
        }

        // Tạo tên file an toàn
        $ext = $allowed[$mime];
        $unique = bin2hex(random_bytes(12));
        $filename = $unique . '.' . $ext;
        $target = $uploadDir . $filename;
        
        // Lưu file vào thư mục ảnh
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            exit('Không lưu được file ảnh!');
        }
        $anh_san_pham = $filename;
    }
    
    $sanPham->setAnhSanPham($anh_san_pham);
    
    // Validate
    $errors = $sanPham->validate();
    if (empty($errors)) {
        if ($sanPhamRepo->update($sanPham)) {
            header("Location: ../admin/san_pham_admin.php");
            exit;
        }
    }
}

?>