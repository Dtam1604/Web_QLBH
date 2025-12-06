<?php

/**
 * Tạo HTML invoice cho đơn hàng
 */
class InvoiceGenerator
{
    public static function renderHtml(DonHang $donHang): string
    {
        $khachHang = $donHang->getKhachHang();
        $diaChi = $donHang->getDiaChi();
        $chiTiet = $donHang->getChiTiet();

        ob_start();
        ?>
        <style>
            .invoice-wrapper {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 0 auto;
                padding: 24px;
                color: #333;
            }
            .invoice-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 2px solid #1976d2;
                padding-bottom: 12px;
                margin-bottom: 24px;
            }
            .invoice-title {
                font-size: 28px;
                color: #1976d2;
                margin: 0;
            }
            .invoice-meta {
                text-align: right;
            }
            .invoice-meta p {
                margin: 4px 0;
            }
            .invoice-section {
                margin-bottom: 24px;
            }
            .invoice-section h3 {
                margin-bottom: 10px;
                color: #1976d2;
            }
            .invoice-table {
                width: 100%;
                border-collapse: collapse;
            }
            .invoice-table th,
            .invoice-table td {
                border: 1px solid #ddd;
                padding: 10px;
            }
            .invoice-table th {
                background: #f5f7ff;
            }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .invoice-total {
                font-size: 20px;
                color: #d32f2f;
            }
            .invoice-actions {
                text-align: center;
                margin-top: 30px;
            }
            .invoice-actions button {
                padding: 12px 24px;
                background: #1976d2;
                color: #fff;
                border: none;
                border-radius: 6px;
                cursor: pointer;
            }
        </style>
        <div class="invoice-wrapper">
            <div class="invoice-header">
                <div>
                    <h1 class="invoice-title">Toét Store</h1>
                    <p>hoadon@toetstore.vn</p>
                    <p>0123 456 789</p>
                </div>
                <div class="invoice-meta">
                    <p><strong>Hóa đơn:</strong> <?= htmlspecialchars($donHang->getMaDonHang()) ?></p>
                    <p><strong>Ngày tạo:</strong> <?= date('d/m/Y H:i', strtotime($donHang->getCreatedAt() ?? date('Y-m-d H:i:s'))) ?></p>
                    <p><strong>Trạng thái:</strong> <?= htmlspecialchars($donHang->getTrangThai() ?? 'pending') ?></p>
                </div>
            </div>

            <div class="invoice-section">
                <h3>Thông tin khách hàng</h3>
                <p><strong>Họ tên:</strong> <?= htmlspecialchars($khachHang ? $khachHang->getHoTen() : 'N/A') ?></p>
                <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($khachHang ? $khachHang->getSoDienThoai() : 'N/A') ?></p>
                <?php if ($khachHang && $khachHang->getEmail()): ?>
                    <p><strong>Email:</strong> <?= htmlspecialchars($khachHang->getEmail()) ?></p>
                <?php endif; ?>
                <?php if ($diaChi): ?>
                    <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($diaChi->getDiaChiDayDu()) ?></p>
                <?php endif; ?>
            </div>

            <div class="invoice-section">
                <h3>Chi tiết đơn hàng</h3>
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th class="text-center">SL</th>
                            <th class="text-right">Đơn giá</th>
                            <th class="text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($chiTiet as $ct):
                        $sanPham = $ct->getSanPham();
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($sanPham ? $sanPham->getTenSanPham() : 'N/A') ?></td>
                            <td class="text-center"><?= $ct->getSoLuong() ?></td>
                            <td class="text-right"><?= number_format($ct->getGiaBan(), 0, '.', '.') ?> đ</td>
                            <td class="text-right"><?= $ct->getThanhTienFormatted() ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right invoice-total">Tổng cộng</td>
                            <td class="text-right invoice-total"><?= $donHang->getTongTienFormatted() ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }
}

