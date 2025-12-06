<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../KhachHang.php';

/**
 * Class KhachHangRepository - Repository cho khách hàng
 */
class KhachHangRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Tìm khách hàng theo ID
     */
    public function findById($id) {
        $sql = 'SELECT * FROM khach_hang WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        
        return $row ? $this->mapToObject($row) : null;
    }
    
    /**
     * Tìm khách hàng theo số điện thoại
     */
    public function findBySoDienThoai($so_dien_thoai) {
        $sql = 'SELECT * FROM khach_hang WHERE so_dien_thoai = :so_dien_thoai LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':so_dien_thoai' => $so_dien_thoai]);
        $row = $stmt->fetch();
        
        return $row ? $this->mapToObject($row) : null;
    }
    
    /**
     * Tạo hoặc lấy khách hàng theo số điện thoại
     */
    public function findOrCreate($ho_ten, $so_dien_thoai, $email = null) {
        $khachHang = $this->findBySoDienThoai($so_dien_thoai);
        
        if ($khachHang) {
            // Cập nhật thông tin nếu có thay đổi
            if ($khachHang->getHoTen() !== $ho_ten || $khachHang->getEmail() !== $email) {
                $khachHang->setHoTen($ho_ten);
                if ($email) {
                    $khachHang->setEmail($email);
                }
                $this->update($khachHang);
            }
            return $khachHang;
        }
        
        // Tạo mới
        $khachHang = new KhachHang(null, $ho_ten, $so_dien_thoai, $email);
        return $this->create($khachHang);
    }
    
    /**
     * Thêm khách hàng mới
     */
    public function create(KhachHang $khachHang) {
        $sql = 'INSERT INTO khach_hang(ho_ten, so_dien_thoai, email) 
                VALUES(:ho_ten, :so_dien_thoai, :email)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':ho_ten' => $khachHang->getHoTen(),
            ':so_dien_thoai' => $khachHang->getSoDienThoai(),
            ':email' => $khachHang->getEmail()
        ]);
        
        $khachHang->setId($this->db->lastInsertId());
        return $khachHang;
    }
    
    /**
     * Cập nhật khách hàng
     */
    public function update(KhachHang $khachHang) {
        $sql = 'UPDATE khach_hang SET ho_ten=:ho_ten, so_dien_thoai=:so_dien_thoai, 
                email=:email WHERE id=:id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':ho_ten' => $khachHang->getHoTen(),
            ':so_dien_thoai' => $khachHang->getSoDienThoai(),
            ':email' => $khachHang->getEmail(),
            ':id' => $khachHang->getId()
        ]);
    }
    
    /**
     * Map database row to KhachHang object
     */
    private function mapToObject($row) {
        return new KhachHang(
            $row['id'],
            $row['ho_ten'],
            $row['so_dien_thoai'],
            $row['email'] ?? null,
            $row['created_at'] ?? null
        );
    }
}

