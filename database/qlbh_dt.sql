-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 19, 2025 lúc 12:40 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `qlbh_dt`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dia_chi`
--

CREATE TABLE `dia_chi` (
  `id` int(11) NOT NULL,
  `khach_hang_id` int(11) NOT NULL,
  `dia_chi_chi_tiet` text DEFAULT NULL COMMENT 'Địa chỉ chi tiết (có thể chứa số nhà và tên đường nếu chưa tách)',
  `so_nha` varchar(50) DEFAULT NULL COMMENT 'Số nhà',
  `ten_duong` varchar(200) DEFAULT NULL COMMENT 'Tên đường/phố',
  `phuong_xa` varchar(100) DEFAULT NULL COMMENT 'Phường/Xã',
  `tinh_thanh` varchar(100) DEFAULT NULL COMMENT 'Tỉnh/Thành phố',
  `mac_dinh` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `dia_chi`
--

INSERT INTO `dia_chi` (`id`, `khach_hang_id`, `dia_chi_chi_tiet`, `so_nha`, `ten_duong`, `phuong_xa`, `tinh_thanh`, `mac_dinh`, `created_at`) VALUES
(1, 1, NULL, 'Số 96', 'ngõ 4 tổ 5', 'Xuân Mai', 'Hà Nội', 1, '2025-11-19 16:42:45'),
(2, 2, 'adsda', NULL, NULL, NULL, NULL, 0, '2025-11-19 16:45:49'),
(3, 3, 'aád', NULL, NULL, NULL, NULL, 0, '2025-11-19 16:45:49'),
(4, 4, 'kịị', NULL, NULL, NULL, NULL, 0, '2025-11-19 16:45:49');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `don_hang`
--

CREATE TABLE `don_hang` (
  `id` int(11) NOT NULL,
  `khach_hang_id` int(11) DEFAULT NULL,
  `dia_chi_id` int(11) DEFAULT NULL,
  `ma_don_hang` varchar(50) NOT NULL,
  `thong_tin_don_hang` varchar(500) DEFAULT NULL,
  `tong_tien` decimal(12,2) DEFAULT NULL,
  `trang_thai` varchar(50) DEFAULT 'pending',
  `payment_method` varchar(20) DEFAULT 'cod',
  `payment_status` varchar(20) DEFAULT 'unpaid',
  `payment_transaction_id` varchar(100) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `payment_response_raw` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `thong_tin_nguoi_mua` varchar(350) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `don_hang`
--

INSERT INTO `don_hang` (`id`, `khach_hang_id`, `dia_chi_id`, `ma_don_hang`, `thong_tin_don_hang`, `tong_tien`, `trang_thai`, `payment_method`, `payment_status`, `payment_transaction_id`, `paid_at`, `payment_response_raw`, `created_at`, `thong_tin_nguoi_mua`) VALUES
(19, 2, 2, 'DH20251028134657991', '<ul><li>14 Ultra x 1 = 13.790.000 đ</li><li>13 Ultra x 1 = 9.690.000 đ</li></ul>', 23480000.00, 'shipped', 'cod', 'paid', NULL, '2025-11-19 10:52:06', NULL, '2025-11-19 16:33:09', '<ul><li>123</li><li>2423423</li><li>adsda</li></ul>'),
(22, 3, 3, 'DH20251029083948186', '<ul><li>13 Ultra x 1 = 9.690.000 đ</li><li>14 Ultra x 1 = 13.790.000 đ</li></ul>', 23480000.00, 'pending', 'cod', 'unpaid', NULL, NULL, NULL, '2025-11-19 16:33:09', '<ul><li>dsdf</li><li>4654654</li><li>aád</li></ul>'),
(27, 4, 4, 'DH20251029090847639', '<ul><li>17 Pro x 1 = 19.890.000 đ</li></ul>', 19890000.00, 'pending', 'cod', 'unpaid', NULL, NULL, NULL, '2025-11-19 16:33:09', '<ul><li>123469</li><li>13264</li><li>kịị</li></ul>'),
(29, 1, 1, 'DH20251119104305546', NULL, 13980000.00, 'completed', 'cod', 'paid', NULL, '2025-11-19 10:51:00', NULL, '2025-11-19 16:43:05', NULL),
(30, 1, 1, 'DH20251119105822793', NULL, 19570000.00, 'pending', 'cod', 'unpaid', NULL, NULL, NULL, '2025-11-19 16:58:22', NULL),
(31, 1, 1, 'DH20251119121059811', NULL, 18480000.00, 'shipped', 'online', 'paid', NULL, '2025-11-19 12:11:00', NULL, '2025-11-19 18:10:59', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `don_hang_chi_tiet`
--

CREATE TABLE `don_hang_chi_tiet` (
  `id` int(11) NOT NULL,
  `don_hang_id` int(11) NOT NULL,
  `san_pham_id` int(11) NOT NULL,
  `so_luong` int(11) NOT NULL DEFAULT 1,
  `gia_ban` decimal(12,2) NOT NULL,
  `thanh_tien` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `don_hang_chi_tiet`
--

INSERT INTO `don_hang_chi_tiet` (`id`, `don_hang_id`, `san_pham_id`, `so_luong`, `gia_ban`, `thanh_tien`) VALUES
(1, 29, 19, 1, 4690000.00, 4690000.00),
(2, 29, 18, 1, 9290000.00, 9290000.00),
(3, 19, 21, 1, 13790000.00, 13790000.00),
(4, 19, 20, 1, 9690000.00, 9690000.00),
(5, 22, 20, 1, 9690000.00, 9690000.00),
(6, 22, 21, 1, 13790000.00, 13790000.00),
(7, 27, 22, 1, 19890000.00, 19890000.00),
(8, 30, 25, 1, 5790000.00, 5790000.00),
(9, 30, 24, 1, 4490000.00, 4490000.00),
(10, 30, 18, 1, 9290000.00, 9290000.00),
(11, 31, 19, 1, 4690000.00, 4690000.00),
(12, 31, 21, 1, 13790000.00, 13790000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khach_hang`
--

CREATE TABLE `khach_hang` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `so_dien_thoai` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khach_hang`
--

INSERT INTO `khach_hang` (`id`, `user_id`, `ho_ten`, `so_dien_thoai`, `email`, `created_at`) VALUES
(1, 7, '123', '4654654665', 'sbasdakj@gmail.com', '2025-11-19 16:42:45'),
(2, NULL, '123', '2423423', NULL, '2025-11-19 16:45:49'),
(3, NULL, 'dsdf', '4654654', NULL, '2025-11-19 16:45:49'),
(4, NULL, '123469', '13264', NULL, '2025-11-19 16:45:49');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `san_pham`
--

CREATE TABLE `san_pham` (
  `id` int(11) NOT NULL,
  `ma_san_pham` varchar(30) NOT NULL,
  `ten_san_pham` varchar(100) DEFAULT NULL,
  `gia_san_pham` decimal(12,2) DEFAULT NULL,
  `anh_san_pham` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `san_pham`
--

INSERT INTO `san_pham` (`id`, `ma_san_pham`, `ten_san_pham`, `gia_san_pham`, `anh_san_pham`) VALUES
(17, 'SP20251028133617323', 'Note 13 Pro', 5000000.00, 'c7ba5a0fc547ee29c1725883.jpg'),
(18, 'SP20251028134111957', 'K80 Pro', 9290000.00, '77ca0974adeeb17e8008b1df.png'),
(19, 'SP20251028134138773', 'Turbo 4', 4690000.00, '4ade9d3acf8f05359f7e6049.png'),
(20, 'SP20251028134219283', '13 Ultra', 9690000.00, 'd0aacf07eff30fd6130c845d.png'),
(21, 'SP20251028134306714', '14 Ultra', 13790000.00, 'f1bf75ae451b3bfcf8d1eefd.png'),
(22, 'SP20251028134402969', '17 Pro', 19890000.00, '8aa548fe8a9ef2cba9998fb7.png'),
(23, 'SP20251028134431687', '17 Pro Max', 24290000.00, 'c1e629b5ef61ab6f2f8002d2.png'),
(24, 'SP20251028140103640', 'K60', 4490000.00, 'ef6e981f44df9ccb0fb4da1b.jpg'),
(25, 'SP20251028140248525', 'K70', 5790000.00, '3c309abed0b65ed00da93724.png');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `ten_dang_nhap` varchar(100) NOT NULL,
  `mat_khau` varchar(255) DEFAULT NULL,
  `loai_user` varchar(20) DEFAULT 'admin',
  `email` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `user`
--

INSERT INTO `user` (`id`, `ten_dang_nhap`, `mat_khau`, `loai_user`, `email`, `created_at`) VALUES
(3, 'admin1', '$2y$10$0h03/E1z/D7A6wCGTWkwbOmxwWcRt.qKXWjfb5rQiJ2lMIuFwHwoO', 'admin', NULL, '2025-11-19 16:32:27'),
(4, 'admin', '$2y$10$.wyhByDRLmzAlxdkHM99MO431CrnK4tp6lTEy08KxqGbhEaguJtOO', 'admin', NULL, '2025-11-19 16:32:27'),
(5, 'admin2', '$2y$10$7AyIyWbSVqYESgFWeGGJ2ub66UkEUGV/eRQh4xJEMh0sEQEsJQpe.', 'admin', NULL, '2025-11-19 16:32:27'),
(6, 'admin3', '$2y$10$yXOJJQttfvtOIvbUp4niNO2CpRMtlW4Bqu3FcokWjatKLubw7fQjy', 'admin', NULL, '2025-11-19 16:32:27'),
(7, 'user', '$2y$10$RH.x1LfAoeIOAh6hqeqIgOVMG/MCIbjBJ76ubVsTMHmjuueddu/YK', 'customer', 'sbasdakj@gmail.com', '2025-11-19 16:42:45');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `dia_chi`
--
ALTER TABLE `dia_chi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_khach_hang` (`khach_hang_id`);

--
-- Chỉ mục cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_don_hang` (`ma_don_hang`);

--
-- Chỉ mục cho bảng `don_hang_chi_tiet`
--
ALTER TABLE `don_hang_chi_tiet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_don_hang` (`don_hang_id`),
  ADD KEY `idx_san_pham` (`san_pham_id`);

--
-- Chỉ mục cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `so_dien_thoai` (`so_dien_thoai`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Chỉ mục cho bảng `san_pham`
--
ALTER TABLE `san_pham`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_san_pham` (`ma_san_pham`);

--
-- Chỉ mục cho bảng `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`),
  ADD KEY `idx_loai_user` (`loai_user`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `dia_chi`
--
ALTER TABLE `dia_chi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT cho bảng `don_hang_chi_tiet`
--
ALTER TABLE `don_hang_chi_tiet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `san_pham`
--
ALTER TABLE `san_pham`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT cho bảng `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `dia_chi`
--
ALTER TABLE `dia_chi`
  ADD CONSTRAINT `fk_dia_chi_khach_hang` FOREIGN KEY (`khach_hang_id`) REFERENCES `khach_hang` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `don_hang_chi_tiet`
--
ALTER TABLE `don_hang_chi_tiet`
  ADD CONSTRAINT `fk_don_hang_chi_tiet_don_hang` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_don_hang_chi_tiet_san_pham` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`);

--
-- Các ràng buộc cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  ADD CONSTRAINT `fk_khach_hang_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
