<?php
    //PDO - PHP Data Object
    define('DATABASE_SERVER', 'localhost');
    define('DATABASE_USER', 'root');
    define('DATABASE_PASSWORD', '');
    define('DATABASE_NAME', 'qlbh_dt');
    
    // Load autoloader  
    require_once __DIR__ . '/../classes/autoload.php';
    
    // Khởi tạo Database connection (backward compatibility)
    try {
        $connection = Database::getInstance()->getConnection();
    } catch(Exception $e) {
        echo "Connection failed: " . $e->getMessage();
        $connection = null;
    }
    
?>