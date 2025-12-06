<?php

/**
 * Service tính toán thống kê doanh thu cho admin dashboard
 */
class StatisticsService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Tổng quan doanh thu trong khoảng thời gian
     */
    public function getSummary(?string $fromDate, ?string $toDate): array
    {
        $params = [];
        $whereSql = $this->buildDateRangeWhere($fromDate, $toDate, $params);

        $sql = "
            SELECT 
                COUNT(*) AS total_orders,
                SUM(tong_tien) AS total_revenue,
                SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_orders,
                SUM(CASE WHEN payment_status = 'paid' THEN tong_tien ELSE 0 END) AS paid_revenue,
                SUM(CASE WHEN payment_method = 'online' THEN tong_tien ELSE 0 END) AS online_revenue,
                SUM(CASE WHEN payment_method = 'cod' THEN tong_tien ELSE 0 END) AS cod_revenue
            FROM don_hang
            $whereSql
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch() ?: [];

        return [
            'total_orders'   => (int)($result['total_orders'] ?? 0),
            'total_revenue'  => (float)($result['total_revenue'] ?? 0),
            'paid_orders'    => (int)($result['paid_orders'] ?? 0),
            'paid_revenue'   => (float)($result['paid_revenue'] ?? 0),
            'online_revenue' => (float)($result['online_revenue'] ?? 0),
            'cod_revenue'    => (float)($result['cod_revenue'] ?? 0),
        ];
    }

    /**
     * Dữ liệu doanh thu theo ngày/tháng để vẽ biểu đồ
     */
    public function getRevenueSeries(?string $fromDate, ?string $toDate, string $granularity = 'day'): array
    {
        $params = [];
        $whereSql = $this->buildDateRangeWhere($fromDate, $toDate, $params);

        $groupExpr = $granularity === 'month'
            ? "DATE_FORMAT(created_at, '%Y-%m')"
            : "DATE(created_at)";

        $sql = "
            SELECT 
                $groupExpr AS bucket,
                SUM(tong_tien) AS total_revenue,
                SUM(CASE WHEN payment_status = 'paid' THEN tong_tien ELSE 0 END) AS paid_revenue
            FROM don_hang
            $whereSql
            GROUP BY bucket
            ORDER BY bucket ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Top sản phẩm bán chạy trong giai đoạn
     */
    public function getTopProducts(?string $fromDate, ?string $toDate, int $limit = 5): array
    {
        $params = [];
        $whereSql = $this->buildDateRangeWhere($fromDate, $toDate, $params, 'dh.');

        $sql = "
            SELECT 
                sp.ten_san_pham,
                SUM(ct.so_luong) AS total_qty,
                SUM(ct.thanh_tien) AS total_amount
            FROM don_hang_chi_tiet ct
            INNER JOIN don_hang dh ON dh.id = ct.don_hang_id
            INNER JOIN san_pham sp ON sp.id = ct.san_pham_id
            $whereSql
            GROUP BY sp.ten_san_pham
            ORDER BY total_amount DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    private function buildDateRangeWhere(
        ?string $fromDate,
        ?string $toDate,
        array &$params,
        string $prefix = ''
    ): string {
        $conditions = [];

        if ($fromDate) {
            $conditions[] = "{$prefix}created_at >= :from";
            $params[':from'] = $fromDate . ' 00:00:00';
        }

        if ($toDate) {
            $conditions[] = "{$prefix}created_at <= :to";
            $params[':to'] = $toDate . ' 23:59:59';
        }

        if (empty($conditions)) {
            return '';
        }

        return 'WHERE ' . implode(' AND ', $conditions);
    }
}

