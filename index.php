<?php 
    session_start();
    // Kiểm tra đăng nhập
    if(isset($_SESSION['user']) && isset($_SESSION['is_admin'])) {
        // Admin đã đăng nhập
        header("Location: admin/san_pham_admin.php");
        exit;
    } elseif(isset($_SESSION['customer_id'])) {
        // Customer đã đăng nhập
        header("Location: pages/san_pham.php");
        exit;
    } else {   
        // Chưa đăng nhập
        header("Location: pages/san_pham.php");
        exit;
    }
?>
