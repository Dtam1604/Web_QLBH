<?php

/**
 * Class DonHang - Model cho đơn hàng
 */
class DonHang {
    private $id;
    private $ma_don_hang;
    private $khach_hang_id;
    private $dia_chi_id;
    private $tong_tien;
    private $trang_thai;
    private $created_at;
    private $payment_method;
    private $payment_status;
    private $payment_transaction_id;
    private $payment_response_raw;
    private $paid_at;
    
    // Related objects
    private $khach_hang;
    private $dia_chi;
    private $chi_tiet; // Array of DonHangChiTiet
    
    public function __construct($id = null, $ma_don_hang = null, $khach_hang_id = null, 
                                $dia_chi_id = null, $tong_tien = null, $trang_thai = 'pending', 
                                $created_at = null, $payment_method = 'cod', $payment_status = 'unpaid',
                                $payment_transaction_id = null, $paid_at = null, $payment_response_raw = null) {
        $this->id = $id;
        $this->ma_don_hang = $ma_don_hang;
        $this->khach_hang_id = $khach_hang_id;
        $this->dia_chi_id = $dia_chi_id;
        $this->tong_tien = $tong_tien;
        $this->trang_thai = $trang_thai;
        $this->created_at = $created_at;
        $this->payment_method = $payment_method ?: 'cod';
        $this->payment_status = $payment_status ?: 'unpaid';
        $this->payment_transaction_id = $payment_transaction_id;
        $this->paid_at = $paid_at;
        $this->payment_response_raw = $payment_response_raw;
        $this->chi_tiet = [];
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getMaDonHang() { return $this->ma_don_hang; }
    public function getKhachHangId() { return $this->khach_hang_id; }
    public function getDiaChiId() { return $this->dia_chi_id; }
    public function getTongTien() { return $this->tong_tien; }
    public function getTrangThai() { return $this->trang_thai; }
    public function getCreatedAt() { return $this->created_at; }
    public function getKhachHang() { return $this->khach_hang; }
    public function getDiaChi() { return $this->dia_chi; }
    public function getChiTiet() { return $this->chi_tiet; }
    public function getPaymentMethod() { return $this->payment_method; }
    public function getPaymentStatus() { return $this->payment_status; }
    public function getPaymentTransactionId() { return $this->payment_transaction_id; }
    public function getPaymentResponseRaw() { return $this->payment_response_raw; }
    public function getPaidAt() { return $this->paid_at; }
    
    // Setters
    public function setId($id) { $this->id = $id; }
    public function setMaDonHang($ma_don_hang) { $this->ma_don_hang = $ma_don_hang; }
    public function setKhachHangId($khach_hang_id) { $this->khach_hang_id = $khach_hang_id; }
    public function setDiaChiId($dia_chi_id) { $this->dia_chi_id = $dia_chi_id; }
    public function setTongTien($tong_tien) { $this->tong_tien = $tong_tien; }
    public function setTrangThai($trang_thai) { $this->trang_thai = $trang_thai; }
    public function setCreatedAt($created_at) { $this->created_at = $created_at; }
    public function setKhachHang($khach_hang) { $this->khach_hang = $khach_hang; }
    public function setDiaChi($dia_chi) { $this->dia_chi = $dia_chi; }
    public function setChiTiet($chi_tiet) { $this->chi_tiet = $chi_tiet; }
    public function setPaymentMethod($payment_method) { $this->payment_method = $payment_method ?: 'cod'; }
    public function setPaymentStatus($payment_status) { $this->payment_status = $payment_status ?: 'unpaid'; }
    public function setPaymentTransactionId($payment_transaction_id) { $this->payment_transaction_id = $payment_transaction_id; }
    public function setPaymentResponseRaw($payment_response_raw) { $this->payment_response_raw = $payment_response_raw; }
    public function setPaidAt($paid_at) { $this->paid_at = $paid_at; }
    
    /**
     * Thêm chi tiết đơn hàng
     */
    public function addChiTiet(DonHangChiTiet $chi_tiet) {
        $this->chi_tiet[] = $chi_tiet;
        $this->calculateTongTien();
    }
    
    /**
     * Tính tổng tiền từ chi tiết
     */
    public function calculateTongTien() {
        $tong = 0;
        foreach ($this->chi_tiet as $ct) {
            $tong += $ct->getThanhTien();
        }
        $this->tong_tien = $tong;
    }
    
    /**
     * Format tổng tiền
     */
    public function getTongTienFormatted() {
        return number_format($this->tong_tien, 0, '.', '.') . '₫';
    }
    
    /**
     * Tạo mã đơn hàng tự động
     */
    public static function generateMaDonHang() {
        return "DH" . date("YmdHis") . rand(100, 999);
    }
    
    /**
     * Validate dữ liệu
     */
    public function validate() {
        $errors = [];
        if (empty($this->khach_hang_id)) {
            $errors[] = "Khách hàng không được để trống";
        }
        if (empty($this->dia_chi_id)) {
            $errors[] = "Địa chỉ không được để trống";
        }
        if (count($this->chi_tiet) == 0) {
            $errors[] = "Đơn hàng phải có ít nhất một sản phẩm";
        }
        if (!in_array($this->payment_method, ['cod', 'online'], true)) {
            $errors[] = "Phương thức thanh toán không hợp lệ";
        }
        return $errors;
    }

    public function isPaid() {
        return $this->payment_status === 'paid';
    }
}

