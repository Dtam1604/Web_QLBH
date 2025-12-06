<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../SanPham.php';

/**
 * Class SanPhamRepository - Repository cho sản phẩm
 */
class SanPhamRepository {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Đếm tổng số sản phẩm
     */
    public function count($search = '') {
        $sql = 'SELECT COUNT(*) as total FROM san_pham';
        $params = [];
        
        if ($search !== '') {
            $sql .= " WHERE ten_san_pham LIKE :search";
            $params[':search'] = "%$search%";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)$row['total'];
    }
    
    /**
     * Lấy tất cả sản phẩm
     */
    public function findAll($search = '', $sort = '') {
        $sql = 'SELECT * FROM san_pham';
        $params = [];
        
        if ($search !== '') {
            $sql .= " WHERE ten_san_pham LIKE :search";
            $params[':search'] = "%$search%";
        }
        
        if ($sort === 'price_asc') {
            $sql .= ' ORDER BY gia_san_pham ASC';
        } elseif ($sort === 'price_desc') {
            $sql .= ' ORDER BY gia_san_pham DESC';
        }
        // } elseif ($sort === 'name_asc') {
        //     $sql .= ' ORDER BY ten_san_pham ASC';
        // } elseif ($sort === 'name_desc') {
        //     $sql .= ' ORDER BY ten_san_pham DESC';
        // }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = $this->mapToObject($row);
        }
        return $results;
    }
    
    /**
     * Lấy sản phẩm có phân trang
     */
    public function findAllPaginated($page = 1, $perPage = 10, $search = '', $sort = '') {
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT * FROM san_pham';
        $params = [];
        
        if ($search !== '') {
            $sql .= " WHERE ten_san_pham LIKE :search";
            $params[':search'] = "%$search%";
        }
        
        if ($sort === 'price_asc') {
            $sql .= ' ORDER BY gia_san_pham ASC';
        } elseif ($sort === 'price_desc') {
            $sql .= ' ORDER BY gia_san_pham DESC';
        }
        
        $sql .= ' LIMIT :limit OFFSET :offset';
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = $this->mapToObject($row);
        }
        return $results;
    }
    
    /**
     * Tìm sản phẩm theo ID
     */
    public function findById($id) {
        $sql = 'SELECT * FROM san_pham WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        
        return $row ? $this->mapToObject($row) : null;
    }
    
    /**
     * Tìm sản phẩm theo mã sản phẩm
     */
    public function findByMaSanPham($ma_san_pham) {
        $sql = 'SELECT * FROM san_pham WHERE ma_san_pham = :ma_san_pham';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ma_san_pham' => $ma_san_pham]);
        $row = $stmt->fetch();
        
        return $row ? $this->mapToObject($row) : null;
    }
    
    /**
     * Tìm sản phẩm theo tên (dùng cho giỏ hàng)
     */
    public function findByName($ten_san_pham) {
        $sql = 'SELECT * FROM san_pham WHERE ten_san_pham = :ten_san_pham LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ten_san_pham' => $ten_san_pham]);
        $row = $stmt->fetch();
        
        return $row ? $this->mapToObject($row) : null;
    }
    
    /**
     * Thêm sản phẩm mới
     */
    public function create(SanPham $sanPham) {
        $sql = 'INSERT INTO san_pham(ma_san_pham, ten_san_pham, gia_san_pham, anh_san_pham) 
                VALUES(:ma_san_pham, :ten_san_pham, :gia_san_pham, :anh_san_pham)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':ma_san_pham' => $sanPham->getMaSanPham(),
            ':ten_san_pham' => $sanPham->getTenSanPham(),
            ':gia_san_pham' => $sanPham->getGiaSanPham(),
            ':anh_san_pham' => $sanPham->getAnhSanPham()
        ]);
        
        $sanPham->setId($this->db->lastInsertId());
        return $sanPham;
    }
    
    /**
     * Cập nhật sản phẩm
     */
    public function update(SanPham $sanPham) {
        $sql = 'UPDATE san_pham SET ten_san_pham=:ten_san_pham, gia_san_pham=:gia_san_pham, 
                anh_san_pham=:anh_san_pham WHERE id=:id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':ten_san_pham' => $sanPham->getTenSanPham(),
            ':gia_san_pham' => $sanPham->getGiaSanPham(),
            ':anh_san_pham' => $sanPham->getAnhSanPham(),
            ':id' => $sanPham->getId()
        ]);
    }
    
    /**
     * Xóa sản phẩm
     */
    public function delete($id) {
        $sql = 'DELETE FROM san_pham WHERE id=:id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Map database row to SanPham object
     */
    private function mapToObject($row) {
        return new SanPham(
            $row['id'],
            $row['ma_san_pham'],
            $row['ten_san_pham'],
            $row['gia_san_pham'],
            $row['anh_san_pham']
        );
    }
}

