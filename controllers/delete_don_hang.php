<?php
require __DIR__ . '/../config/db.php';

$id = $_GET['id'] ?? null;
if ($id) {
    $donHangRepo = new DonHangRepository();
    $donHangRepo->delete($id);
}
header("Location: ../admin/don_hang_admin.php");
exit;