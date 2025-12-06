<?php

/**
 * Autoloader cho các classes
 */
spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/';
    
    // Map class names to file paths
    $classMap = [
        'Database' => 'Database.php',
        'SanPham' => 'SanPham.php',
        'KhachHang' => 'KhachHang.php',
        'DiaChi' => 'DiaChi.php',
        'DonHang' => 'DonHang.php',
        'DonHangChiTiet' => 'DonHangChiTiet.php',
        'SanPhamRepository' => 'repositories/SanPhamRepository.php',
        'KhachHangRepository' => 'repositories/KhachHangRepository.php',
        'DiaChiRepository' => 'repositories/DiaChiRepository.php',
        'DonHangRepository' => 'repositories/DonHangRepository.php',
        'StatisticsService' => 'services/StatisticsService.php',
        'InvoiceGenerator' => 'services/InvoiceGenerator.php',
    ];
    
    if (isset($classMap[$class])) {
        require_once $baseDir . $classMap[$class];
    }
});

