<?php

/**
 * Class DonHangChiTiet - Model cho chi tiết đơn hàng
 */
class DonHangChiTiet {
    private $id;
    private $don_hang_id;
    private $san_pham_id;
    private $so_luong;
    private $gia_ban;
    private $thanh_tien;
    private $san_pham; // Đối tượng SanPham (lazy load)
    
    public function __construct($id = null, $don_hang_id = null, $san_pham_id = null, 
                                $so_luong = 1, $gia_ban = null, $thanh_tien = null) {
        $this->id = $id;
        $this->don_hang_id = $don_hang_id;
        $this->san_pham_id = $san_pham_id;
        $this->so_luong = $so_luong;
        $this->gia_ban = $gia_ban;
        $this->thanh_tien = $thanh_tien;
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getDonHangId() { return $this->don_hang_id; }
    public function getSanPhamId() { return $this->san_pham_id; }
    public function getSoLuong() { return $this->so_luong; }
    public function getGiaBan() { return $this->gia_ban; }
    public function getThanhTien() { return $this->thanh_tien; }
    public function getSanPham() { return $this->san_pham; }
    
    // Setters
    public function setId($id) { $this->id = $id; }
    public function setDonHangId($don_hang_id) { $this->don_hang_id = $don_hang_id; }
    public function setSanPhamId($san_pham_id) { $this->san_pham_id = $san_pham_id; }
    public function setSoLuong($so_luong) { 
        $this->so_luong = $so_luong;
        $this->calculateThanhTien();
    }
    public function setGiaBan($gia_ban) { 
        $this->gia_ban = $gia_ban;
        $this->calculateThanhTien();
    }
    public function setThanhTien($thanh_tien) { $this->thanh_tien = $thanh_tien; }
    public function setSanPham($san_pham) { $this->san_pham = $san_pham; }
    
    /**
     * Tính thành tiền
     */
    public function calculateThanhTien() {
        if ($this->so_luong > 0 && $this->gia_ban !== null) {
            $this->thanh_tien = $this->so_luong * $this->gia_ban;
        }
    }
    
    /**
     * Format thành tiền
     */
    public function getThanhTienFormatted() {
        return number_format($this->thanh_tien, 0, '.', '.') . '₫';
    }
    
    /**
     * Validate dữ liệu
     */
    public function validate() {
        $errors = [];
        if (empty($this->san_pham_id)) {
            $errors[] = "Sản phẩm không được để trống";
        }
        if ($this->so_luong <= 0) {
            $errors[] = "Số lượng phải lớn hơn 0";
        }
        if ($this->gia_ban === null || $this->gia_ban < 0) {
            $errors[] = "Giá bán không hợp lệ";
        }
        return $errors;
    }
}

