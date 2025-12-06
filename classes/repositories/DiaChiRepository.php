<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../DiaChi.php';

/**
 * Class DiaChiRepository - Repository cho địa chỉ
 */
class DiaChiRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Tìm địa chỉ theo ID
     */
    public function findById($id) {
        $sql = 'SELECT * FROM dia_chi WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        
        return $row ? $this->mapToObject($row) : null;
    }
    
    /**
     * Lấy tất cả địa chỉ của khách hàng
     */
    public function findAllByKhachHangId($khach_hang_id) {
        $sql = 'SELECT * FROM dia_chi WHERE khach_hang_id = :khach_hang_id ORDER BY mac_dinh DESC, created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':khach_hang_id' => $khach_hang_id]);
        $rows = $stmt->fetchAll();
        
        $results = [];
        foreach ($rows as $row) {
            $results[] = $this->mapToObject($row);
        }
        return $results;
    }
    
    /**
     * Lấy địa chỉ mặc định của khách hàng
     */
    public function findDefaultByKhachHangId($khach_hang_id) {
        $sql = 'SELECT * FROM dia_chi WHERE khach_hang_id = :khach_hang_id AND mac_dinh = 1 LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':khach_hang_id' => $khach_hang_id]);
        $row = $stmt->fetch();
        
        return $row ? $this->mapToObject($row) : null;
    }
    
    /**
     * Tạo hoặc lấy địa chỉ
     * Nếu địa chỉ chi tiết giống nhau thì trả về địa chỉ cũ
     */
    public function findOrCreate($khach_hang_id, $dia_chi_chi_tiet = null, $so_nha = null, 
                                  $ten_duong = null, $phuong_xa = null, 
                                  $tinh_thanh = null) {
        // Tìm địa chỉ tương tự (dựa trên số nhà, tên đường, phường/xã, tỉnh/thành)
        $sql = 'SELECT * FROM dia_chi WHERE khach_hang_id = :khach_hang_id 
                AND COALESCE(so_nha, "") = COALESCE(:so_nha, "")
                AND COALESCE(ten_duong, "") = COALESCE(:ten_duong, "")
                AND COALESCE(phuong_xa, "") = COALESCE(:phuong_xa, "")
                AND COALESCE(tinh_thanh, "") = COALESCE(:tinh_thanh, "")
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':khach_hang_id' => $khach_hang_id,
            ':so_nha' => $so_nha,
            ':ten_duong' => $ten_duong,
            ':phuong_xa' => $phuong_xa,
            ':tinh_thanh' => $tinh_thanh
        ]);
        $row = $stmt->fetch();
        
        if ($row) {
            return $this->mapToObject($row);
        }
        
        // Tạo mới
        $diaChi = new DiaChi(null, $khach_hang_id, $dia_chi_chi_tiet, 
                            $so_nha, $ten_duong, $phuong_xa, $tinh_thanh);
        return $this->create($diaChi);
    }
    
    /**
     * Thêm địa chỉ mới
     */
    public function create(DiaChi $diaChi) {
        $sql = 'INSERT INTO dia_chi(khach_hang_id, dia_chi_chi_tiet, so_nha, ten_duong, phuong_xa, tinh_thanh, mac_dinh) 
                VALUES(:khach_hang_id, :dia_chi_chi_tiet, :so_nha, :ten_duong, :phuong_xa, :tinh_thanh, :mac_dinh)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':khach_hang_id' => $diaChi->getKhachHangId(),
            ':dia_chi_chi_tiet' => $diaChi->getDiaChiChiTiet(),
            ':so_nha' => $diaChi->getSoNha(),
            ':ten_duong' => $diaChi->getTenDuong(),
            ':phuong_xa' => $diaChi->getPhuongXa(),
            ':tinh_thanh' => $diaChi->getTinhThanh(),
            ':mac_dinh' => $diaChi->getMacDinh()
        ]);
        
        $diaChi->setId($this->db->lastInsertId());
        return $diaChi;
    }
    
    /**
     * Cập nhật địa chỉ
     */
    public function update(DiaChi $diaChi) {
        $sql = 'UPDATE dia_chi SET dia_chi_chi_tiet=:dia_chi_chi_tiet, so_nha=:so_nha, 
                ten_duong=:ten_duong, phuong_xa=:phuong_xa, tinh_thanh=:tinh_thanh, mac_dinh=:mac_dinh 
                WHERE id=:id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':dia_chi_chi_tiet' => $diaChi->getDiaChiChiTiet(),
            ':so_nha' => $diaChi->getSoNha(),
            ':ten_duong' => $diaChi->getTenDuong(),
            ':phuong_xa' => $diaChi->getPhuongXa(),
            ':tinh_thanh' => $diaChi->getTinhThanh(),
            ':mac_dinh' => $diaChi->getMacDinh(),
            ':id' => $diaChi->getId()
        ]);
    }
    
    /**
     * Xóa địa chỉ
     */
    public function delete($id) {
        $sql = 'DELETE FROM dia_chi WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Đặt địa chỉ mặc định (bỏ mặc định các địa chỉ khác)
     */
    public function setDefault($khach_hang_id, $dia_chi_id) {
        $this->db->beginTransaction();
        try {
            // Bỏ mặc định tất cả địa chỉ của khách hàng
            $sql1 = 'UPDATE dia_chi SET mac_dinh = 0 WHERE khach_hang_id = :khach_hang_id';
            $stmt1 = $this->db->prepare($sql1);
            $stmt1->execute([':khach_hang_id' => $khach_hang_id]);
            
            // Đặt địa chỉ được chọn làm mặc định
            $sql2 = 'UPDATE dia_chi SET mac_dinh = 1 WHERE id = :id';
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->execute([':id' => $dia_chi_id]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Map database row to DiaChi object
     */
    private function mapToObject($row) {
        return new DiaChi(
            $row['id'],
            $row['khach_hang_id'],
            $row['dia_chi_chi_tiet'] ?? null,
            $row['so_nha'] ?? null,
            $row['ten_duong'] ?? null,
            $row['phuong_xa'] ?? null,
            $row['tinh_thanh'] ?? null,
            $row['mac_dinh'] ?? 0,
            $row['created_at'] ?? null
        );
    }
}

