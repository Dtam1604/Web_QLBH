<?php
/**
 * Script migration dữ liệu từ cấu trúc cũ sang cấu trúc chuẩn hóa
 * Chạy script này một lần để migrate dữ liệu đơn hàng cũ
 */

require __DIR__ . '/db.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Migration Dữ Liệu</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-left: 4px solid green; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-left: 4px solid red; margin: 10px 0; }
        .info { color: #1976d2; padding: 10px; background: #e3f2fd; border-left: 4px solid #1976d2; margin: 10px 0; }
        .step { margin: 15px 0; padding: 10px; background: #f5f5f5; border-radius: 4px; }
        h1 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>📦 Migration Dữ Liệu</h1>";

echo "<div class='info'>Bắt đầu migration dữ liệu từ cấu trúc cũ sang cấu trúc chuẩn hóa...</div>";

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();
    
    // 1. Lấy tất cả đơn hàng cũ (chưa có khach_hang_id)
    $sql = "SELECT * FROM don_hang WHERE khach_hang_id IS NULL";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $donHangCu = $stmt->fetchAll();
    
    $totalOrders = count($donHangCu);
    echo "<div class='info'>Tìm thấy <strong>$totalOrders</strong> đơn hàng cần migrate</div>";
    
    $khachHangRepo = new KhachHangRepository();
    $diaChiRepo = new DiaChiRepository();
    
    foreach ($donHangCu as $dh) {
        // Parse thông tin người mua từ HTML string
        $thong_tin_nguoi_mua = $dh['thong_tin_nguoi_mua'] ?? '';
        
        // Extract thông tin từ HTML (format: <ul><li>Họ tên</li><li>SĐT</li><li>Địa chỉ</li></ul>)
        preg_match('/<li>(.*?)<\/li>/', $thong_tin_nguoi_mua, $matches);
        $ho_ten = $matches[1] ?? '';
        
        preg_match_all('/<li>(.*?)<\/li>/', $thong_tin_nguoi_mua, $allMatches);
        $so_dien_thoai = $allMatches[1][1] ?? '';
        $dia_chi_text = $allMatches[1][2] ?? '';
        
        if (empty($ho_ten) || empty($so_dien_thoai) || empty($dia_chi_text)) {
            echo "<div class='error'>⚠ Bỏ qua đơn hàng ID {$dh['id']} - thiếu thông tin</div>";
            continue;
        }
        
        // Tạo hoặc lấy khách hàng
        $khachHang = $khachHangRepo->findOrCreate($ho_ten, $so_dien_thoai);
        
        // Tạo địa chỉ
        $diaChi = $diaChiRepo->findOrCreate($khachHang->getId(), $dia_chi_text);
        
        // Cập nhật đơn hàng
        $updateSql = "UPDATE don_hang SET khach_hang_id = :khach_hang_id, dia_chi_id = :dia_chi_id, 
                     trang_thai = 'pending' WHERE id = :id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute([
            ':khach_hang_id' => $khachHang->getId(),
            ':dia_chi_id' => $diaChi->getId(),
            ':id' => $dh['id']
        ]);
        
        // Parse và tạo chi tiết đơn hàng từ thong_tin_don_hang
        $thong_tin_don_hang = $dh['thong_tin_don_hang'] ?? '';
        preg_match_all('/<li>(.*?) x (\d+) = ([\d.,]+)/', $thong_tin_don_hang, $spMatches);
        
        if (!empty($spMatches[1])) {
            $sanPhamRepo = new SanPhamRepository();
            
            for ($i = 0; $i < count($spMatches[1]); $i++) {
                $ten_sp = trim($spMatches[1][$i]);
                $so_luong = (int)$spMatches[2][$i];
                $thanh_tien_str = str_replace(['.', ','], '', $spMatches[3][$i]);
                $thanh_tien = (float)$thanh_tien_str;
                $gia_ban = $thanh_tien / $so_luong;
                
                // Tìm sản phẩm
                $sanPham = $sanPhamRepo->findByName($ten_sp);
                
                if ($sanPham) {
                    // Tạo chi tiết đơn hàng
                    $chiTietSql = "INSERT INTO don_hang_chi_tiet(don_hang_id, san_pham_id, so_luong, gia_ban, thanh_tien) 
                                  VALUES(:don_hang_id, :san_pham_id, :so_luong, :gia_ban, :thanh_tien)";
                    $chiTietStmt = $db->prepare($chiTietSql);
                    $chiTietStmt->execute([
                        ':don_hang_id' => $dh['id'],
                        ':san_pham_id' => $sanPham->getId(),
                        ':so_luong' => $so_luong,
                        ':gia_ban' => $gia_ban,
                        ':thanh_tien' => $thanh_tien
                    ]);
                }
            }
        }
        
        echo "<div class='step'>✓ Đã migrate đơn hàng ID {$dh['id']} - Khách hàng: " . htmlspecialchars($ho_ten) . "</div>";
    }
    
    $db->commit();
    echo "<div class='success'><strong>✓ Migration hoàn tất!</strong></div>";
    echo "<div class='info'>";
    echo "<strong>Kết quả:</strong><br>";
    echo "- Đã migrate: <strong>$totalOrders</strong> đơn hàng<br>";
    echo "- Dữ liệu đã được chuyển sang cấu trúc chuẩn hóa<br>";
    echo "- Kiểm tra trong phpMyAdmin để xác nhận<br>";
    echo "</div>";
    echo "<div class='info' style='margin-top: 20px;'>";
    echo "<a href='../admin/don_hang_admin.php'>← Xem danh sách đơn hàng</a> | ";
    echo "<a href='../pages/san_pham.php'>Trang chủ</a>";
    echo "</div>";
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "<div class='error'><strong>✗ Lỗi:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";

