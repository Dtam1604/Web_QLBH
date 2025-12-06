<?php
/**
 * Script chạy migration database - Chuẩn hóa cấu trúc
 * Chạy file này một lần để tạo các bảng mới và migrate dữ liệu
 * 
 * Truy cập: http://localhost/webthuong/run_migration.php
 * Hoặc chạy từ command line: php run_migration.php
 */

require '../config/db.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-left: 4px solid green; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-left: 4px solid red; margin: 10px 0; }
        .info { color: #1976d2; padding: 10px; background: #e3f2fd; border-left: 4px solid #1976d2; margin: 10px 0; }
        .step { margin: 15px 0; padding: 10px; background: #f5f5f5; border-radius: 4px; }
        h1 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔄 Database Migration Tool</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<div class='info'><strong>Bước 1:</strong> Kiểm tra kết nối database...</div>";
    echo "<div class='success'>✓ Kết nối database thành công!</div>";
    
    // Đọc file SQL migration
    $sqlFile = __DIR__ . '/configuration/migrate_normalized_tables.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Không tìm thấy file migration: $sqlFile");
    }
    
    $sqlContent = file_get_contents($sqlFile);
    
    echo "<div class='info'><strong>Bước 2:</strong> Đọc file migration SQL...</div>";
    echo "<div class='success'>✓ Đã đọc file migration thành công!</div>";
    
    // Tách các câu lệnh SQL (loại bỏ comments và empty lines)
    $sqlStatements = array_filter(
        array_map('trim', explode(';', $sqlContent)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt) &&
                   strtoupper(substr(trim($stmt), 0, 2)) !== '/*';
        }
    );
    
    echo "<div class='info'><strong>Bước 3:</strong> Thực thi các câu lệnh SQL...</div>";
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($sqlStatements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        // Bỏ qua các câu lệnh đặc biệt (SET, PREPARE, EXECUTE, DEALLOCATE)
        if (preg_match('/^(SET|PREPARE|EXECUTE|DEALLOCATE|USE)/i', $statement)) {
            continue;
        }
        
        try {
            // Thực thi từng câu lệnh
            $db->exec($statement);
            $successCount++;
            
            // Hiển thị thông tin về câu lệnh đã chạy
            if (preg_match('/CREATE TABLE/i', $statement)) {
                preg_match('/CREATE TABLE.*?`(\w+)`/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "<div class='step'>✓ Đã tạo bảng: <strong>$tableName</strong></div>";
            } elseif (preg_match('/ALTER TABLE/i', $statement)) {
                preg_match('/ALTER TABLE.*?`(\w+)`/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "<div class='step'>✓ Đã cập nhật bảng: <strong>$tableName</strong></div>";
            }
        } catch (PDOException $e) {
            $errorCount++;
            $errorMsg = $e->getMessage();
            
            // Bỏ qua lỗi nếu bảng/cột đã tồn tại
            if (strpos($errorMsg, 'already exists') !== false || 
                strpos($errorMsg, 'Duplicate column name') !== false ||
                strpos($errorMsg, 'Duplicate key name') !== false) {
                echo "<div class='info'>⚠ Bảng/cột đã tồn tại, bỏ qua...</div>";
                $errorCount--; // Không tính là lỗi
            } else {
                $errors[] = "Câu lệnh #" . ($index + 1) . ": " . $errorMsg;
                echo "<div class='error'>✗ Lỗi: " . htmlspecialchars($errorMsg) . "</div>";
            }
        }
    }
    
    echo "<div class='info'><strong>Bước 4:</strong> Kiểm tra kết quả...</div>";
    
    // Kiểm tra các bảng đã được tạo
    $tablesToCheck = ['khach_hang', 'dia_chi', 'don_hang_chi_tiet'];
    $existingTables = [];
    
    foreach ($tablesToCheck as $table) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $existingTables[] = $table;
            }
        } catch (PDOException $e) {
            // Bỏ qua
        }
    }
    
    echo "<div class='success'><strong>Hoàn thành!</strong></div>";
    echo "<div class='info'>";
    echo "<strong>Kết quả:</strong><br>";
    echo "- Câu lệnh thành công: <strong>$successCount</strong><br>";
    if ($errorCount > 0) {
        echo "- Câu lệnh lỗi: <strong>$errorCount</strong><br>";
    }
    echo "- Bảng đã tạo: " . implode(', ', $existingTables) . "<br>";
    echo "</div>";
    
    if (!empty($errors)) {
        echo "<div class='error'><strong>Chi tiết lỗi:</strong><br>";
        foreach ($errors as $error) {
            echo "- " . htmlspecialchars($error) . "<br>";
        }
        echo "</div>";
    }
    
    // Kiểm tra các cột mới trong bảng don_hang
    echo "<div class='info'><strong>Bước 5:</strong> Kiểm tra cấu trúc bảng don_hang...</div>";
    try {
        $stmt = $db->query("DESCRIBE don_hang");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $newColumns = ['khach_hang_id', 'dia_chi_id', 'trang_thai', 'created_at'];
        $missingColumns = array_diff($newColumns, $columns);
        
        if (empty($missingColumns)) {
            echo "<div class='success'>✓ Tất cả các cột mới đã được thêm vào bảng don_hang</div>";
        } else {
            echo "<div class='error'>✗ Thiếu các cột: " . implode(', ', $missingColumns) . "</div>";
            echo "<div class='info'>Bạn có thể thêm thủ công các cột này trong phpMyAdmin</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>Không thể kiểm tra cấu trúc bảng: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // Chạy migration user system
    echo "<div class='info'><strong>Bước 6:</strong> Chạy migration hệ thống user...</div>";
    try {
        $userMigrationFile = __DIR__ . '/configuration/migrate_user_system.sql';
        if (file_exists($userMigrationFile)) {
            $userSqlContent = file_get_contents($userMigrationFile);
            $userStatements = array_filter(
                array_map('trim', explode(';', $userSqlContent)),
                function($stmt) {
                    return !empty($stmt) && 
                           !preg_match('/^--/', $stmt) && 
                           !preg_match('/^\/\*/', $stmt) &&
                           strtoupper(substr(trim($stmt), 0, 2)) !== '/*' &&
                           !preg_match('/^(USE|SET|PREPARE|EXECUTE|DEALLOCATE)/i', $stmt);
                }
            );
            
            foreach ($userStatements as $stmt) {
                $stmt = trim($stmt);
                if (empty($stmt)) continue;
                
                try {
                    $db->exec($stmt);
                    if (preg_match('/ALTER TABLE.*ADD COLUMN/i', $stmt)) {
                        preg_match('/ALTER TABLE.*?`(\w+)`/i', $stmt, $matches);
                        $tableName = $matches[1] ?? 'unknown';
                        echo "<div class='step'>✓ Đã cập nhật bảng: <strong>$tableName</strong></div>";
                    }
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate column') === false) {
                        echo "<div class='info'>⚠ " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                }
            }
            echo "<div class='success'>✓ Migration hệ thống user hoàn tất!</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>Lỗi migration user: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // Hướng dẫn bước tiếp theo
    echo "<div class='info' style='margin-top: 30px;'>";
    echo "<strong>📝 Bước tiếp theo:</strong><br>";
    echo "1. Nếu có dữ liệu đơn hàng cũ, chạy migration dữ liệu:<br>";
    echo "   → <a href='../config/migrate_to_normalized.php'>config/migrate_to_normalized.php</a><br><br>";
    echo "2. Kiểm tra database trong phpMyAdmin để đảm bảo tất cả bảng đã được tạo<br><br>";
    echo "3. Test website: <a href='../pages/san_pham.php'>san_pham.php</a><br>";
    echo "4. Test đăng ký khách hàng: <a href='../auth/register_customer.php'>register_customer.php</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>Lỗi:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'>";
    echo "<strong>Hướng dẫn thay thế:</strong><br>";
    echo "1. Mở phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a><br>";
    echo "2. Chọn database: <strong>project_demo</strong><br>";
    echo "3. Vào tab <strong>SQL</strong><br>";
    echo "4. Copy nội dung file: <code>configuration/migrate_normalized_tables.sql</code><br>";
    echo "5. Paste và Execute";
    echo "</div>";
}

echo "</body></html>";
?>

