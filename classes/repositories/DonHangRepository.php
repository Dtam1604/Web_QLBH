<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../DonHang.php';
require_once __DIR__ . '/../DonHangChiTiet.php';
require_once __DIR__ . '/../KhachHang.php';
require_once __DIR__ . '/../DiaChi.php';
require_once __DIR__ . '/../SanPham.php';

/**
 * Class DonHangRepository - Repository cho đơn hàng
 */
class DonHangRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Đếm tổng số đơn hàng
     */
    public function count() {
        $sql = 'SELECT COUNT(*) as total FROM don_hang';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch();
        return (int)$row['total'];
    }
    
    /**
     * Lấy tất cả đơn hàng
     */
    public function findAll() {
        $sql = 'SELECT * FROM don_hang ORDER BY created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $donHang = $this->mapToObject($row);
            $this->loadRelations($donHang);
            $results[] = $donHang;
        }
        return $results;
    }
    
    /**
     * Lấy đơn hàng có phân trang
     */
    public function findAllPaginated($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT * FROM don_hang ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $donHang = $this->mapToObject($row);
            $this->loadRelations($donHang);
            $results[] = $donHang;
        }
        return $results;
    }
    
    /**
     * Tìm đơn hàng theo ID
     */
    public function findById($id) {
        $sql = 'SELECT * FROM don_hang WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        
        if (!$row) {
            return null;
        }
        
        $donHang = $this->mapToObject($row);
        $this->loadRelations($donHang);
        return $donHang;
    }
    
    /**
     * Lấy tất cả đơn hàng của một khách hàng
     */
    public function findByKhachHangId($khach_hang_id) {
        $sql = 'SELECT * FROM don_hang WHERE khach_hang_id = :khach_hang_id ORDER BY created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':khach_hang_id' => $khach_hang_id]);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $donHang = $this->mapToObject($row);
            $this->loadRelations($donHang);
            $results[] = $donHang;
        }
        return $results;
    }
    
    /**
     * Tạo đơn hàng mới
     */
    public function create(DonHang $donHang) {
        $this->db->beginTransaction();
        try {
            // Insert đơn hàng
            $sql = 'INSERT INTO don_hang(ma_don_hang, khach_hang_id, dia_chi_id, tong_tien, trang_thai, payment_method, payment_status) 
                    VALUES(:ma_don_hang, :khach_hang_id, :dia_chi_id, :tong_tien, :trang_thai, :payment_method, :payment_status)';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':ma_don_hang' => $donHang->getMaDonHang(),
                ':khach_hang_id' => $donHang->getKhachHangId(),
                ':dia_chi_id' => $donHang->getDiaChiId(),
                ':tong_tien' => $donHang->getTongTien(),
                ':trang_thai' => $donHang->getTrangThai(),
                ':payment_method' => $donHang->getPaymentMethod(),
                ':payment_status' => $donHang->getPaymentStatus()
            ]);
            
            $donHang->setId($this->db->lastInsertId());
            
            // Insert chi tiết đơn hàng
            foreach ($donHang->getChiTiet() as $chiTiet) {
                $chiTiet->setDonHangId($donHang->getId());
                $this->createChiTiet($chiTiet);
            }
            
            $this->db->commit();
            return $donHang;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Tạo chi tiết đơn hàng
     */
    private function createChiTiet(DonHangChiTiet $chiTiet) {
        $sql = 'INSERT INTO don_hang_chi_tiet(don_hang_id, san_pham_id, so_luong, gia_ban, thanh_tien) 
                VALUES(:don_hang_id, :san_pham_id, :so_luong, :gia_ban, :thanh_tien)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':don_hang_id' => $chiTiet->getDonHangId(),
            ':san_pham_id' => $chiTiet->getSanPhamId(),
            ':so_luong' => $chiTiet->getSoLuong(),
            ':gia_ban' => $chiTiet->getGiaBan(),
            ':thanh_tien' => $chiTiet->getThanhTien()
        ]);
        
        $chiTiet->setId($this->db->lastInsertId());
    }
    
    /**
     * Cập nhật đơn hàng
     */
    public function update(DonHang $donHang) {
        $this->db->beginTransaction();
        try {
            // Update đơn hàng
            $sql = 'UPDATE don_hang SET khach_hang_id = :khach_hang_id, dia_chi_id = :dia_chi_id, 
                    tong_tien = :tong_tien, trang_thai = :trang_thai, payment_method = :payment_method,
                    payment_status = :payment_status
                    WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':khach_hang_id' => $donHang->getKhachHangId(),
                ':dia_chi_id' => $donHang->getDiaChiId(),
                ':tong_tien' => $donHang->getTongTien(),
                ':trang_thai' => $donHang->getTrangThai(),
                ':payment_method' => $donHang->getPaymentMethod(),
                ':payment_status' => $donHang->getPaymentStatus(),
                ':id' => $donHang->getId()
            ]);
            
            // Xóa chi tiết cũ và thêm chi tiết mới
            $deleteStmt = $this->db->prepare('DELETE FROM don_hang_chi_tiet WHERE don_hang_id = :don_hang_id');
            $deleteStmt->execute([':don_hang_id' => $donHang->getId()]);
            
            // Insert chi tiết mới
            foreach ($donHang->getChiTiet() as $chiTiet) {
                $chiTiet->setDonHangId($donHang->getId());
                $this->createChiTiet($chiTiet);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Cập nhật trạng thái đơn hàng (đơn giản hơn)
     */
    public function updateTrangThai($id, $trang_thai) {
        $sql = 'UPDATE don_hang SET trang_thai = :trang_thai WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':trang_thai' => $trang_thai,
            ':id' => $id
        ]);
    }
    
    /**
     * Cập nhật địa chỉ giao hàng của đơn hàng
     * Tạo địa chỉ mới hoặc cập nhật địa chỉ hiện tại
     */
    public function updateDiaChiGiaoHang($don_hang_id, $so_nha, $ten_duong, $phuong_xa, $tinh_thanh, $dia_chi_chi_tiet = null) {
        $this->db->beginTransaction();
        try {
            // Lấy đơn hàng hiện tại
            $donHang = $this->findById($don_hang_id);
            if (!$donHang) {
                throw new Exception('Không tìm thấy đơn hàng');
            }
            
            $khach_hang_id = $donHang->getKhachHangId();
            if (!$khach_hang_id) {
                throw new Exception('Đơn hàng không có thông tin khách hàng');
            }
            
            // Lấy địa chỉ hiện tại của đơn hàng
            $diaChiRepo = new DiaChiRepository();
            $diaChiHienTai = $donHang->getDiaChiId() ? $diaChiRepo->findById($donHang->getDiaChiId()) : null;
            
            // Nếu có địa chỉ hiện tại, cập nhật nó (chỉ cập nhật địa chỉ của đơn hàng này)
            if ($diaChiHienTai) {
                $diaChiHienTai->setSoNha($so_nha ?: null);
                $diaChiHienTai->setTenDuong($ten_duong);
                $diaChiHienTai->setPhuongXa($phuong_xa);
                $diaChiHienTai->setTinhThanh($tinh_thanh);
                $diaChiHienTai->setDiaChiChiTiet($dia_chi_chi_tiet ?: null);
                $diaChiRepo->update($diaChiHienTai);
            } else {
                // Tạo địa chỉ mới
                $diaChiMoi = new DiaChi(null, $khach_hang_id, $dia_chi_chi_tiet, 
                                       $so_nha ?: null, $ten_duong, $phuong_xa, $tinh_thanh, 0);
                $diaChiMoi = $diaChiRepo->create($diaChiMoi);
                
                // Cập nhật đơn hàng với địa chỉ mới
                $sql = 'UPDATE don_hang SET dia_chi_id = :dia_chi_id WHERE id = :id';
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':dia_chi_id' => $diaChiMoi->getId(),
                    ':id' => $don_hang_id
                ]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Cập nhật số điện thoại liên hệ của khách hàng trong đơn hàng
     * Cảnh báo: Chỉ cập nhật số điện thoại, không thay đổi họ tên
     */
    public function updateSoDienThoai($don_hang_id, $so_dien_thoai) {
        $donHang = $this->findById($don_hang_id);
        if (!$donHang || !$donHang->getKhachHangId()) {
            throw new Exception('Không tìm thấy thông tin khách hàng');
        }
        
        $khachHangRepo = new KhachHangRepository();
        $khachHang = $khachHangRepo->findById($donHang->getKhachHangId());
        if (!$khachHang) {
            throw new Exception('Không tìm thấy khách hàng');
        }
        
        $khachHang->setSoDienThoai($so_dien_thoai);
        return $khachHangRepo->update($khachHang);
    }
    
    /**
     * Xóa đơn hàng
     */
    public function delete($id) {
        $sql = 'DELETE FROM don_hang WHERE id=:id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Load các quan hệ (khách hàng, địa chỉ, chi tiết)
     */
    private function loadRelations(DonHang $donHang) {
        // Load khách hàng
        if ($donHang->getKhachHangId()) {
            $sql = 'SELECT * FROM khach_hang WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $donHang->getKhachHangId()]);
            $row = $stmt->fetch();
            if ($row) {
                $khachHang = new KhachHang($row['id'], $row['ho_ten'], $row['so_dien_thoai'], 
                                          $row['email'] ?? null, $row['created_at'] ?? null);
                $donHang->setKhachHang($khachHang);
            }
        }
        
        // Load địa chỉ
        if ($donHang->getDiaChiId()) {
            $sql = 'SELECT * FROM dia_chi WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $donHang->getDiaChiId()]);
            $row = $stmt->fetch();
            if ($row) {
                $diaChi = new DiaChi($row['id'], $row['khach_hang_id'], $row['dia_chi_chi_tiet'] ?? null,
                                    $row['so_nha'] ?? null, $row['ten_duong'] ?? null,
                                    $row['phuong_xa'] ?? null,
                                    $row['tinh_thanh'] ?? null, $row['mac_dinh'] ?? 0,
                                    $row['created_at'] ?? null);
                $donHang->setDiaChi($diaChi);
            }
        }
        
        // Load chi tiết đơn hàng
        $sql = 'SELECT d.*, s.ten_san_pham, s.anh_san_pham 
                FROM don_hang_chi_tiet d 
                INNER JOIN san_pham s ON d.san_pham_id = s.id 
                WHERE d.don_hang_id = :don_hang_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':don_hang_id' => $donHang->getId()]);
        
        $chiTietList = [];
        while ($row = $stmt->fetch()) {
            $chiTiet = new DonHangChiTiet(
                $row['id'],
                $row['don_hang_id'],
                $row['san_pham_id'],
                $row['so_luong'],
                $row['gia_ban'],
                $row['thanh_tien']
            );
            
            // Load sản phẩm
            $sanPham = new SanPham($row['san_pham_id'], null, $row['ten_san_pham'], 
                                  null, $row['anh_san_pham']);
            $chiTiet->setSanPham($sanPham);
            
            $chiTietList[] = $chiTiet;
        }
        $donHang->setChiTiet($chiTietList);
    }
    
    /**
     * Map database row to DonHang object
     */
    private function mapToObject($row) {
        return new DonHang(
            $row['id'],
            $row['ma_don_hang'],
            $row['khach_hang_id'] ?? null,
            $row['dia_chi_id'] ?? null,
            $row['tong_tien'],
            $row['trang_thai'] ?? 'pending',
            $row['created_at'] ?? null,
            $row['payment_method'] ?? 'cod',
            $row['payment_status'] ?? 'unpaid',
            $row['payment_transaction_id'] ?? null,
            $row['paid_at'] ?? null,
            $row['payment_response_raw'] ?? null
        );
    }

    public function findByMaDonHang(string $maDonHang)
    {
        $sql = 'SELECT * FROM don_hang WHERE ma_don_hang = :ma';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ma' => $maDonHang]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $donHang = $this->mapToObject($row);
        $this->loadRelations($donHang);
        return $donHang;
    }

    public function updatePaymentStatus(
        int $id,
        string $paymentStatus,
        ?string $transactionId = null,
        ?string $paidAt = null,
        ?string $rawResponse = null
    ): bool {
        $sql = 'UPDATE don_hang 
                SET payment_status = :payment_status, 
                    payment_transaction_id = :transaction_id,
                    paid_at = :paid_at,
                    payment_response_raw = :raw_response
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':payment_status' => $paymentStatus,
            ':transaction_id' => $transactionId,
            ':paid_at' => $paidAt,
            ':raw_response' => $rawResponse,
            ':id' => $id
        ]);
    }

    public function updatePaymentInfo(
        int $id,
        string $paymentMethod,
        string $paymentStatus,
        ?string $transactionId = null,
        ?string $paidAt = null
    ): bool {
        $sql = 'UPDATE don_hang 
                SET payment_method = :payment_method,
                    payment_status = :payment_status,
                    payment_transaction_id = :transaction_id,
                    paid_at = :paid_at
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':payment_method' => $paymentMethod,
            ':payment_status' => $paymentStatus,
            ':transaction_id' => $transactionId,
            ':paid_at' => $paidAt,
            ':id' => $id
        ]);
    }
}

