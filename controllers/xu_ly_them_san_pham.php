<?php
require __DIR__ . '/../config/db.php';

$message = '';
$uploadDir = __DIR__ . '/../Images/';

if (isset($_POST['ten_san_pham']) && isset($_FILES['anh_san_pham'])) {
    $file = $_FILES['anh_san_pham'];
    if ($file['error'] !== 0) {
        exit('Lỗi upload!');
    }

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
    
    // Lưu file vào uploads
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        exit('Không lưu được file!');
    }
    
    // Tạo đối tượng SanPham
    $sanPham = new SanPham();
    $sanPham->setMaSanPham(SanPham::generateMaSanPham());
    $sanPham->setTenSanPham($_POST['ten_san_pham']);
    $sanPham->setGiaSanPham(number_format($_POST['gia_san_pham'], 2, '.', ''));
    $sanPham->setAnhSanPham($filename);
    
    // Validate
    $errors = $sanPham->validate();
    if (empty($errors)) {
        $sanPhamRepo = new SanPhamRepository();
        $sanPhamRepo->create($sanPham);
        header("Location: ../admin/san_pham_admin.php");
        exit;
    } else {
        // Xóa file đã upload nếu có lỗi
        if (file_exists($target)) {
            unlink($target);
        }
        echo "Lỗi: " . implode(", ", $errors);
    }
}

?>