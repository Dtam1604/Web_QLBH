<?php

/**
 * Class KhachHang - Model cho khách hàng
 */
class KhachHang {
    private $id;
    private $ho_ten;
    private $so_dien_thoai;
    private $email;
    private $created_at;
    
    public function __construct($id = null, $ho_ten = null, $so_dien_thoai = null, $email = null, $created_at = null) {
        $this->id = $id;
        $this->ho_ten = $ho_ten;
        $this->so_dien_thoai = $so_dien_thoai;
        $this->email = $email;
        $this->created_at = $created_at;
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getHoTen() { return $this->ho_ten; }
    public function getSoDienThoai() { return $this->so_dien_thoai; }
    public function getEmail() { return $this->email; }
    public function getCreatedAt() { return $this->created_at; }
    
    // Setters
    public function setId($id) { $this->id = $id; }
    public function setHoTen($ho_ten) { $this->ho_ten = $ho_ten; }
    public function setSoDienThoai($so_dien_thoai) { $this->so_dien_thoai = $so_dien_thoai; }
    public function setEmail($email) { $this->email = $email; }
    public function setCreatedAt($created_at) { $this->created_at = $created_at; }
    
    /**
     * Validate dữ liệu
     */
    public function validate() {
        $errors = [];
        if (empty($this->ho_ten)) {
            $errors[] = "Họ tên không được để trống";
        }
        if (empty($this->so_dien_thoai)) {
            $errors[] = "Số điện thoại không được để trống";
        } elseif (!preg_match('/^[0-9]{10,11}$/', $this->so_dien_thoai)) {
            $errors[] = "Số điện thoại không hợp lệ";
        }
        if (!empty($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email không hợp lệ";
        }
        return $errors;
    }
}

