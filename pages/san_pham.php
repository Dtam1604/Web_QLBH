<?php
session_start();

require '../config/db.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

$sanPhamRepo = new SanPhamRepository();
$list_san_pham = $sanPhamRepo->findAll($search, $sort);
 ?>
<?php require '../views/header_guest.php'; ?>

<!-- Banner ngang -->
  <div class="banner">
  <div class="logo">Toét Store</div>
  <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <?php if (isset($_SESSION['customer_id'])): ?>
      <span style="color: white;">Xin chào, <?= htmlspecialchars($_SESSION['customer_name'] ?? $_SESSION['customer_username']) ?>!</span>
      <a href="don_hang_customer.php" class="login-btn">📦 Đơn hàng của tôi</a>
      <a href="../auth/change_password_customer.php" class="login-btn">🔐 Đổi mật khẩu</a>
      <a href="../auth/logout_customer.php" class="login-btn">🚪 Đăng xuất</a>
    <?php else: ?>
      <a href="../auth/login_customer.php" class="login-btn">Đăng nhập</a>
      <a href="../auth/register_customer.php" class="login-btn">Đăng ký</a>
    <?php endif; ?>
  </div>
  </div>
  
<!-- Wrapper cho main và cart -->
<div class="page-wrapper">
  <!-- Nội dung chính -->
  <div class="main">
      <h1>Shop Điện Thoại</h1>
      <form id="searchSortForm" method="get" class="search-sort-form">
        <input type="text" name="search" id="searchInput" class="search-input" placeholder="🔍 Tìm kiếm sản phẩm..." value="<?= htmlspecialchars($search) ?>">
        <select name="sort" id="sortSelect" class="sort-select">
          <option value="">Sắp xếp</option>
          <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Giá tăng dần</option>
          <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Giá giảm dần</option>
        </select>
      </form>
      <script>
        // Biến để JavaScript biết trạng thái đăng nhập
        const isLoggedIn = <?= isset($_SESSION['customer_id']) ? 'true' : 'false' ?>;
        const loginUrl = '../auth/login_customer.php?redirect=' + encodeURIComponent(window.location.href);
      </script>
      <script src="../JS/project_demo.js"></script>
      <script src="../JS/cart_restore.js"></script>
      <div class="product-container">
        <!-- Danh sách Sản phẩm -->
        <?php foreach($list_san_pham as $san_pham): ?>
          <div class="product-card" data-name= "<?= htmlspecialchars($san_pham->getTenSanPham()); ?>" data-price= <?= $san_pham->getGiaSanPham(); ?> >
          <h2><?= htmlspecialchars($san_pham->getTenSanPham()); ?></h2>
          <img src="../Images/<?= htmlspecialchars($san_pham->getAnhSanPham());?>" alt= <?= htmlspecialchars($san_pham->getTenSanPham()); ?> >
          <p class="price">
            <?= $san_pham->getGiaFormatted(); ?>
          </p>
          <div class="quantity">
            <button onclick="changeQuantity(this, -1)">-</button>
            <span>1</span>
            <button onclick="changeQuantity(this, 1)">+</button>
          </div>
          <button class="add-btn" onclick="addToCart(this)">THÊM VÀO GIỎ HÀNG</button>
          </div>
        <?php endforeach; ?>
      </div>
  </div>

  <!-- Sidebar giỏ hàng bên phải -->
  <div class="cart" id="cart-sidebar">
      <div class="cart-header">
          <h2>🛒 Giỏ hàng</h2>
          <button class="cart-toggle-btn" onclick="toggleCart()" id="cart-toggle" title="Ẩn giỏ hàng">−</button>
      </div>
      <div class="cart-content">
          <ul id="cart-items"></ul>
          <p class="cart-total">Tổng tiền: <span id="cart-total">0</span> đ</p>
          <button class="checkout-btn" onclick="openPopup()">MUA HÀNG</button>
      </div>
  </div>
  
  <!-- Nút hiển thị giỏ hàng khi bị ẩn -->
  <button class="cart-show-btn" id="cart-show-btn" onclick="toggleCart()" style="display: none;" title="Hiển thị giỏ hàng">
      🛒
  </button>
</div>

<!-- Popup đặt hàng -->
<div class="popup" id="checkout-popup">
    <div class="popup-content">
      <h3>📝 Xác nhận đơn hàng</h3>
      <div id="order-summary"></div>
      <hr>
      <form action="../controllers/xu_ly_don_hang.php" method="post" onsubmit="return sendCart();">
        <h4>Thông tin khách hàng</h4>
        <?php 
        // Nếu đã đăng nhập, lấy thông tin từ session
        $prefill_name = '';
        $prefill_phone = '';
        $prefill_so_nha = '';
        $prefill_ten_duong = '';
        $prefill_phuong_xa = '';
        $prefill_tinh_thanh = '';
        
        if (isset($_SESSION['customer_id']) && isset($_SESSION['khach_hang_id'])) {
            $khachHangRepo = new KhachHangRepository();
            $khachHang = $khachHangRepo->findById($_SESSION['khach_hang_id']);
            if ($khachHang) {
                $prefill_name = htmlspecialchars($khachHang->getHoTen());
                $prefill_phone = htmlspecialchars($khachHang->getSoDienThoai());
                
                // Lấy địa chỉ mặc định
                $diaChiRepo = new DiaChiRepository();
                $diaChiDefault = $diaChiRepo->findDefaultByKhachHangId($khachHang->getId());
                if ($diaChiDefault) {
                    $prefill_so_nha = htmlspecialchars($diaChiDefault->getSoNha() ?? '');
                    $prefill_ten_duong = htmlspecialchars($diaChiDefault->getTenDuong() ?? '');
                    $prefill_phuong_xa = htmlspecialchars($diaChiDefault->getPhuongXa() ?? '');
                    $prefill_tinh_thanh = htmlspecialchars($diaChiDefault->getTinhThanh() ?? '');
                }
            }
        }
        ?>
        <label>Họ tên *</label>
        <input type="text" id="customer-name" placeholder="Nhập họ tên" name="ho_ten" 
               value="<?= $prefill_name ?>" required>

        <label>Số điện thoại *</label>
        <input type="text" id="customer-phone" placeholder="Nhập số điện thoại" name="so_dien_thoai" 
               value="<?= $prefill_phone ?>" required>

        <h5 class="section-title">Địa chỉ giao hàng</h5>
        
        <div class="address-grid">
          <label>Số nhà</label>
          <input type="text" id="so-nha" placeholder="Ví dụ: 123" name="so_nha" 
                 value="<?= $prefill_so_nha ?>">
          
          <label>Tên đường/Phố *</label>
          <input type="text" id="ten-duong" placeholder="Ví dụ: Nguyễn Văn Linh" name="ten_duong" 
                 value="<?= $prefill_ten_duong ?>" required>
          
          <label>Phường/Xã *</label>
          <input type="text" id="phuong-xa" placeholder="Ví dụ: Phường 1" name="phuong_xa" 
                 value="<?= $prefill_phuong_xa ?>" required>
          
          <label>Tỉnh/Thành phố *</label>
          <input type="text" id="tinh-thanh" placeholder="Ví dụ: TP. Hồ Chí Minh" name="tinh_thanh" 
                 value="<?= $prefill_tinh_thanh ?>" required>
        </div>
        
        <h5 class="section-title">Phương thức thanh toán</h5>
        <div class="payment-method-group">
          <label class="payment-option">
            <input type="radio" name="payment_method" value="cod" checked>
            <span>💵 Thanh toán khi nhận hàng (COD)</span>
          </label>
          <label class="payment-option">
            <input type="radio" name="payment_method" value="online">
            <span>💳 Thanh toán online</span>
          </label>
        </div>
        
        <!-- input ẩn để chứa giỏ hàng JSON -->
        <input type="hidden" name="cart_json" id="cart_json">

        <div class="actions">
          <button class="cancel-btn" type="button" onclick="closePopup()">Quay lại</button>
          <button class="confirm-btn" type="submit">Xác nhận mua hàng</button>
        </div>
      </form>
    </div>
</div>

<?php
	require_once '../views/footer.php';
?>

  </body>
</html>