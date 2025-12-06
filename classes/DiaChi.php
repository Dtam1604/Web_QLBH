<?php

/**
 * Class DiaChi - Model cho địa chỉ
 */
class DiaChi {
    private $id;
    private $khach_hang_id;
    private $dia_chi_chi_tiet;
    private $so_nha;
    private $ten_duong;
    private $phuong_xa;
    private $tinh_thanh;
    private $mac_dinh;
    private $created_at;
    
    public function __construct($id = null, $khach_hang_id = null, $dia_chi_chi_tiet = null, 
                                $so_nha = null, $ten_duong = null, $phuong_xa = null, 
                                $tinh_thanh = null, 
                                $mac_dinh = 0, $created_at = null) {
        $this->id = $id;
        $this->khach_hang_id = $khach_hang_id;
        $this->dia_chi_chi_tiet = $dia_chi_chi_tiet;
        $this->so_nha = $so_nha;
        $this->ten_duong = $ten_duong;
        $this->phuong_xa = $phuong_xa;
        $this->tinh_thanh = $tinh_thanh;
        $this->mac_dinh = $mac_dinh;
        $this->created_at = $created_at;
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getKhachHangId() { return $this->khach_hang_id; }
    public function getDiaChiChiTiet() { return $this->dia_chi_chi_tiet; }
    public function getSoNha() { return $this->so_nha; }
    public function getTenDuong() { return $this->ten_duong; }
    public function getPhuongXa() { return $this->phuong_xa; }
    public function getTinhThanh() { return $this->tinh_thanh; }
    public function getMacDinh() { return $this->mac_dinh; }
    public function getCreatedAt() { return $this->created_at; }
    
    // Setters
    public function setId($id) { $this->id = $id; }
    public function setKhachHangId($khach_hang_id) { $this->khach_hang_id = $khach_hang_id; }
    public function setDiaChiChiTiet($dia_chi_chi_tiet) { $this->dia_chi_chi_tiet = $dia_chi_chi_tiet; }
    public function setSoNha($so_nha) { $this->so_nha = $so_nha; }
    public function setTenDuong($ten_duong) { $this->ten_duong = $ten_duong; }
    public function setPhuongXa($phuong_xa) { $this->phuong_xa = $phuong_xa; }
    public function setTinhThanh($tinh_thanh) { $this->tinh_thanh = $tinh_thanh; }
    public function setMacDinh($mac_dinh) { $this->mac_dinh = $mac_dinh; }
    public function setCreatedAt($created_at) { $this->created_at = $created_at; }
    
    /**
     * Lấy địa chỉ đầy đủ
     */
    public function getDiaChiDayDu() {
        $parts = array_filter([
            $this->so_nha ? 'Số ' . $this->so_nha : null,
            $this->ten_duong,
            $this->phuong_xa,
            $this->tinh_thanh
        ]);
        
        // Nếu không có các trường chi tiết, dùng dia_chi_chi_tiet
        if (empty($parts) && $this->dia_chi_chi_tiet) {
            return $this->dia_chi_chi_tiet;
        }
        
        return implode(', ', $parts);
    }
    
    /**
     * Validate dữ liệu
     */
    public function validate() {
        $errors = [];
        if (empty($this->khach_hang_id)) {
            $errors[] = "Khách hàng không được để trống";
        }
        // Yêu cầu ít nhất có số nhà + tên đường HOẶC dia_chi_chi_tiet
        if (empty($this->dia_chi_chi_tiet) && empty($this->so_nha) && empty($this->ten_duong)) {
            $errors[] = "Vui lòng nhập địa chỉ (số nhà, tên đường hoặc địa chỉ chi tiết)";
        }
        if (empty($this->phuong_xa)) {
            $errors[] = "Phường/Xã không được để trống";
        }
        if (empty($this->tinh_thanh)) {
            $errors[] = "Tỉnh/Thành phố không được để trống";
        }
        return $errors;
    }
}

