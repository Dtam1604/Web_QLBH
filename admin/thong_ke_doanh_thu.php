<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d', strtotime('-29 days'));
$to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');
$granularity = isset($_GET['granularity']) && $_GET['granularity'] === 'month' ? 'month' : 'day';

$statsService = new StatisticsService();
$summary = $statsService->getSummary($from, $to);
$series = $statsService->getRevenueSeries($from, $to, $granularity);
$topProducts = $statsService->getTopProducts($from, $to);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=doanh-thu-' . $from . '-den-' . $to . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Thời gian', 'Tổng doanh thu', 'Đã thanh toán']);
    foreach ($series as $row) {
        fputcsv($output, [
            $row['bucket'],
            $row['total_revenue'],
            $row['paid_revenue']
        ]);
    }
    fclose($output);
    exit;
}

function formatCurrency($amount)
{
    return number_format($amount ?? 0, 0, '.', '.') . ' đ';
}
?>
<!doctype html>
<html lang="vi">
<head>
  <title>Thống kê doanh thu</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="../CSS/styles_admin.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="navbar">
    <div class="menu">
      <a href="san_pham_admin.php">Danh mục sản phẩm</a>
      <a href="don_hang_admin.php">Danh mục đơn hàng</a>
      <a href="user_admin.php">Quản lý tài khoản</a>
      <a href="thong_ke_doanh_thu.php" class="active">Thống kê</a>
    </div>
    <a href="../auth/logout.php" class="logout-btn">Logout</a>
  </div>

  <div class="container">
    <h2>Thống kê doanh thu</h2>

    <form method="get" class="admin-search-form flex-wrap">
      <div>
        <label>Từ ngày</label>
        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="admin-search-input">
      </div>
      <div>
        <label>Đến ngày</label>
        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="admin-search-input">
      </div>
      <div>
        <label>Kiểu hiển thị</label>
        <select name="granularity" class="admin-sort-select">
          <option value="day" <?= $granularity === 'day' ? 'selected' : '' ?>>Theo ngày</option>
          <option value="month" <?= $granularity === 'month' ? 'selected' : '' ?>>Theo tháng</option>
        </select>
      </div>
      <button class="btn-add-new mt-18" type="submit">Lọc dữ liệu</button>
      <a href="?from=<?= $from ?>&to=<?= $to ?>&granularity=<?= $granularity ?>&export=csv" class="btn-add-new mt-18 btn-green">
        Xuất CSV
      </a>
    </form>

    <div class="stats-grid">
      <div class="stat-card">
        <span>Tổng đơn hàng</span>
        <strong><?= number_format($summary['total_orders']) ?></strong>
      </div>
      <div class="stat-card">
        <span>Tổng doanh thu</span>
        <strong><?= formatCurrency($summary['total_revenue']) ?></strong>
      </div>
      <div class="stat-card">
        <span>Đã thanh toán</span>
        <strong><?= formatCurrency($summary['paid_revenue']) ?> (<?= number_format($summary['paid_orders']) ?> đơn)</strong>
      </div>
      <div class="stat-card">
        <span>Online / COD</span>
        <strong><?= formatCurrency($summary['online_revenue']) ?> / <?= formatCurrency($summary['cod_revenue']) ?></strong>
      </div>
    </div>

    <div class="chart-card">
      <h3>Biểu đồ doanh thu</h3>
      <div class="chart-container">
        <canvas id="revenueChart"></canvas>
      </div>
    </div>

    <div class="grid-two-column">
      <div class="panel">
        <h3>Doanh thu chi tiết</h3>
        <table class="revenue-table">
          <thead>
            <tr>
              <th>Thời gian</th>
              <th>Tổng doanh thu</th>
              <th>Đã thanh toán</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($series)): ?>
            <tr><td colspan="3">Chưa có dữ liệu</td></tr>
          <?php else: ?>
            <?php foreach ($series as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['bucket']) ?></td>
                <td><?= formatCurrency($row['total_revenue']) ?></td>
                <td><?= formatCurrency($row['paid_revenue']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="panel">
        <h3>Top sản phẩm bán chạy</h3>
        <ul class="top-products">
          <?php if (empty($topProducts)): ?>
            <li>Chưa có dữ liệu</li>
          <?php else: ?>
            <?php foreach ($topProducts as $item): ?>
              <li>
                <span><?= htmlspecialchars($item['ten_san_pham']) ?></span>
                <span><?= (int)$item['total_qty'] ?> sản phẩm - <?= formatCurrency($item['total_amount']) ?></span>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>

  <script>
    // Dữ liệu biểu đồ từ server
    const chartData = <?= json_encode($series) ?>;
  </script>
  <script src="../JS/revenue_chart.js"></script>
  <script>
    // Khởi tạo biểu đồ sau khi DOM ready
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof initRevenueChart === 'function') {
        initRevenueChart(chartData);
      }
    });
  </script>
</body>
</html>

