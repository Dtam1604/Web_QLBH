<?php

/**
 * Class SanPham - Model cho sản phẩm
 */
class SanPham {
    private $id;
    private $ma_san_pham;
    private $ten_san_pham;
    private $gia_san_pham;
    private $anh_san_pham;
    
    public function __construct($id = null, $ma_san_pham = null, $ten_san_pham = null, $gia_san_pham = null, $anh_san_pham = null) {
        $this->id = $id;
        $this->ma_san_pham = $ma_san_pham;
        $this->ten_san_pham = $ten_san_pham;
        $this->gia_san_pham = $gia_san_pham;
        $this->anh_san_pham = $anh_san_pham;
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getMaSanPham() { return $this->ma_san_pham; }
    public function getTenSanPham() { return $this->ten_san_pham; }
    public function getGiaSanPham() { return $this->gia_san_pham; }
    public function getAnhSanPham() { return $this->anh_san_pham; }
    
    // Setters
    public function setId($id) { $this->id = $id; }
    public function setMaSanPham($ma_san_pham) { $this->ma_san_pham = $ma_san_pham; }
    public function setTenSanPham($ten_san_pham) { $this->ten_san_pham = $ten_san_pham; }
    public function setGiaSanPham($gia_san_pham) { $this->gia_san_pham = $gia_san_pham; }
    public function setAnhSanPham($anh_san_pham) { $this->anh_san_pham = $anh_san_pham; }
    
    /**
     * Format giá tiền
     */
    public function getGiaFormatted() {
        return number_format($this->gia_san_pham, 0, '.', '.') . '₫';
    }
    
    /**
     * Tạo mã sản phẩm tự động
     */
    public static function generateMaSanPham() {
        return "SP" . date("YmdHis") . rand(100, 999);
    }
    
    /**
     * Validate dữ liệu
     */
    public function validate() {
        $errors = [];
        if (empty($this->ten_san_pham)) {
            $errors[] = "Tên sản phẩm không được để trống";
        }
        if ($this->gia_san_pham === null || $this->gia_san_pham < 0) {
            $errors[] = "Giá sản phẩm không hợp lệ";
        }
        return $errors;
    }
}

