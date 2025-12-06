<?php
require __DIR__ . '/../config/db.php';

$id = $_GET['id'] ?? null;
if ($id) {
    $sanPhamRepo = new SanPhamRepository();
    $sanPhamRepo->delete($id);
}
header("Location: ../admin/san_pham_admin.php");
exit;