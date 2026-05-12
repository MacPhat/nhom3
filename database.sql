-- ============================================
-- DO AN: QUAN LY KHACH SAN - NHOM 3
-- He thong quan ly khach san chuyen nghiep
-- Database Schema for phpMyAdmin
-- ============================================

CREATE DATABASE IF NOT EXISTS quanly_khachsan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quanly_khachsan;

-- Bang Tai Khoan (dang ky / dang nhap)
CREATE TABLE IF NOT EXISTS taikhoan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    hoten VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    sdt VARCHAR(15),
    quyen ENUM('admin', 'nhanvien', 'khachhang') DEFAULT 'khachhang',
    avatar VARCHAR(255) DEFAULT '',
    trangthai ENUM('hoat_dong', 'khoa') DEFAULT 'hoat_dong',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Bang Loai Phong
CREATE TABLE IF NOT EXISTS loaiphong (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenloai VARCHAR(100) NOT NULL,
    mota TEXT,
    giaban DECIMAL(12,2) NOT NULL DEFAULT 0,
    sophong_max INT DEFAULT 2,
    tienich TEXT,
    hinhanh VARCHAR(255) DEFAULT ''
) ENGINE=InnoDB;

-- Bang Phong
CREATE TABLE IF NOT EXISTS phong (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maphong VARCHAR(20) NOT NULL UNIQUE,
    tenphong VARCHAR(100) NOT NULL,
    loaiphong_id INT NOT NULL,
    tang INT DEFAULT 1,
    trangthai ENUM('trong', 'dang_thue', 'bao_tri', 'da_dat') DEFAULT 'trong',
    mota TEXT,
    hinhanh VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loaiphong_id) REFERENCES loaiphong(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Bang Khach Hang
CREATE TABLE IF NOT EXISTS khachhang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    makh VARCHAR(20) NOT NULL UNIQUE,
    taikhoan_id INT,
    hoten VARCHAR(100) NOT NULL,
    cmnd VARCHAR(20),
    sdt VARCHAR(15),
    email VARCHAR(100),
    diachi TEXT,
    gioitinh ENUM('Nam', 'Nu', 'Khac') DEFAULT 'Nam',
    ngaysinh DATE,
    quoctich VARCHAR(50) DEFAULT 'Việt Nam',
    ghichu TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (taikhoan_id) REFERENCES taikhoan(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Bang Nhan Vien
CREATE TABLE IF NOT EXISTS nhanvien (
    id INT AUTO_INCREMENT PRIMARY KEY,
    manv VARCHAR(20) NOT NULL UNIQUE,
    taikhoan_id INT,
    hoten VARCHAR(100) NOT NULL,
    sdt VARCHAR(15),
    email VARCHAR(100),
    diachi TEXT,
    chucvu VARCHAR(100),
    phongban VARCHAR(100) DEFAULT '',
    luong DECIMAL(12,2) DEFAULT 0,
    ngaysinh DATE,
    gioitinh ENUM('Nam', 'Nu', 'Khac') DEFAULT 'Nam',
    ngvaolam DATE,
    trangthai ENUM('hoat_dong', 'nghi_viec') DEFAULT 'hoat_dong',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (taikhoan_id) REFERENCES taikhoan(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Bang Dich Vu
CREATE TABLE IF NOT EXISTS dichvu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    madv VARCHAR(20) NOT NULL UNIQUE,
    tendv VARCHAR(100) NOT NULL,
    mota TEXT,
    gia DECIMAL(12,2) NOT NULL DEFAULT 0,
    donvitinh VARCHAR(30) DEFAULT 'lần',
    hinhanh VARCHAR(255) DEFAULT '',
    trangthai ENUM('hoat_dong', 'ngung') DEFAULT 'hoat_dong'
) ENGINE=InnoDB;

-- Bang Dat Phong
CREATE TABLE IF NOT EXISTS datphong (
    id INT AUTO_INCREMENT PRIMARY KEY,
    madatphong VARCHAR(20) NOT NULL UNIQUE,
    khachhang_id INT NOT NULL,
    phong_id INT NOT NULL,
    nhanvien_id INT,
    ngayden DATE NOT NULL,
    ngaydi DATE NOT NULL,
    songuoi INT DEFAULT 1,
    yeucau TEXT,
    trangthai ENUM('cho_xac_nhan', 'da_xac_nhan', 'da_nhan_phong', 'da_tra_phong', 'da_huy') DEFAULT 'cho_xac_nhan',
    ghichu TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (khachhang_id) REFERENCES khachhang(id) ON DELETE RESTRICT,
    FOREIGN KEY (phong_id) REFERENCES phong(id) ON DELETE RESTRICT,
    FOREIGN KEY (nhanvien_id) REFERENCES nhanvien(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Bang Su Dung Dich Vu
CREATE TABLE IF NOT EXISTS sudung_dichvu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    datphong_id INT NOT NULL,
    dichvu_id INT NOT NULL,
    soluong INT DEFAULT 1,
    thanhtien DECIMAL(12,2) DEFAULT 0,
    ngay_sd TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (datphong_id) REFERENCES datphong(id) ON DELETE CASCADE,
    FOREIGN KEY (dichvu_id) REFERENCES dichvu(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Bang Hoa Don
CREATE TABLE IF NOT EXISTS hoadon (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mahoadon VARCHAR(20) NOT NULL UNIQUE,
    datphong_id INT NOT NULL,
    nhanvien_id INT,
    ngaylap TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tienphong DECIMAL(12,2) DEFAULT 0,
    tiendichvu DECIMAL(12,2) DEFAULT 0,
    tongtien DECIMAL(12,2) DEFAULT 0,
    phuthu DECIMAL(12,2) DEFAULT 0,
    giamgia DECIMAL(12,2) DEFAULT 0,
    thanhtoan DECIMAL(12,2) DEFAULT 0,
    trangthai ENUM('chua_thanh_toan', 'da_thanh_toan', 'da_huy') DEFAULT 'chua_thanh_toan',
    pt_thanhtoan ENUM('tien_mat', 'chuyen_khoan', 'the') DEFAULT 'tien_mat',
    ghichu TEXT,
    FOREIGN KEY (datphong_id) REFERENCES datphong(id) ON DELETE RESTRICT,
    FOREIGN KEY (nhanvien_id) REFERENCES nhanvien(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Bang Danh Gia
CREATE TABLE IF NOT EXISTS danhgia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    khachhang_id INT NOT NULL,
    phong_id INT NOT NULL,
    datphong_id INT,
    diem INT DEFAULT 5 CHECK (diem >= 1 AND diem <= 5),
    nhanxet TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (khachhang_id) REFERENCES khachhang(id) ON DELETE CASCADE,
    FOREIGN KEY (phong_id) REFERENCES phong(id) ON DELETE CASCADE,
    FOREIGN KEY (datphong_id) REFERENCES datphong(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Bang Thong Bao
CREATE TABLE IF NOT EXISTS thongbao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    taikhoan_id INT,
    tieude VARCHAR(200) NOT NULL,
    noidung TEXT,
    loai ENUM('he_thong', 'dat_phong', 'thanhtoan', 'khuyen_mai') DEFAULT 'he_thong',
    daxem BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (taikhoan_id) REFERENCES taikhoan(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- DU LIEU MAU
-- ============================================

-- Tai khoan (password: 123456)
INSERT INTO taikhoan (username, password, hoten, email, sdt, quyen) VALUES ('admin', '123456', 'Quản trị viên', 'admin@hotel.com', '0900000000', 'admin');
INSERT INTO taikhoan (username, password, hoten, email, sdt, quyen) VALUES ('nhanvien1', '123456', 'Nguyễn Văn An', 'nva@hotel.com', '0901111222', 'nhanvien');
INSERT INTO taikhoan (username, password, hoten, email, sdt, quyen) VALUES ('nhanvien2', '123456', 'Trần Thị Bích', 'ntb@hotel.com', '0902222333', 'nhanvien');
INSERT INTO taikhoan (username, password, hoten, email, sdt, quyen) VALUES ('khachhang1', '123456', 'Trần Văn Bình', 'binh@email.com', '0901234567', 'khachhang');
INSERT INTO taikhoan (username, password, hoten, email, sdt, quyen) VALUES ('khachhang2', '123456', 'Nguy Thị Lan', 'lan@email.com', '0909876543', 'khachhang');

-- Loai phong
INSERT INTO loaiphong (tenloai, mota, giaban, sophong_max, tienich) VALUES ('Phòng Standard', 'Phòng tiêu chuẩn với tiện nghi cơ bản, phù hợp cho khách du lịch giá rẻ', 300000, 2, 'WiFi, TV, Điều hòa, Nóng lạnh');
INSERT INTO loaiphong (tenloai, mota, giaban, sophong_max, tienich) VALUES ('Phòng Superior', 'Phòng cao cấp với view thành phố, không gian rộng rãi', 500000, 3, 'WiFi, TV 43", Điều hòa, Bồn tắm, Minibar');
INSERT INTO loaiphong (tenloai, mota, giaban, sophong_max, tienich) VALUES ('Phòng Deluxe', 'Phòng sang trọng với view biển, thiết kế hiện đại', 800000, 3, 'WiFi, TV 55", Điều hòa, Bồn tắm, Minibar, Ban công');
INSERT INTO loaiphong (tenloai, mota, giaban, sophong_max, tienich) VALUES ('Phòng Suite', 'Phòng hạng sang với phòng khách riêng, tiện nghi đẳng cấp', 1500000, 4, 'WiFi, TV 65", Điều hòa, Bồn tắm, Minibar, Ban công, Phòng khách');
INSERT INTO loaiphong (tenloai, mota, giaban, sophong_max, tienich) VALUES ('Phòng VIP President', 'Phòng tổng thống, không gian xa hoa nhất khách sạn', 3000000, 6, 'Tất cả tiện ích cao cấp, Hồ bơi riêng, Phòng khách, Bếp');

-- Phong
INSERT INTO phong (maphong, tenphong, loaiphong_id, tang, trangthai) VALUES ('P101', 'Phòng 101', 1, 1, 'trong');
INSERT INTO phong (maphong, tenphong, loaiphong_id, tang, trangthai) VALUES ('P102', 'Phòng 102', 1, 1, 'trong');
INSERT INTO phong (maphong, tenphong, loaiphong_id, tang, trangthai) VALUES ('P103', 'Phòng 103', 1, 1, 'trong');
INSERT INTO phong (maphong, tenphong, loaiphong_id, tang, trangthai) VALUES ('P201', 'Phòng 201', 2, 2, 'trong');
INSERT INTO phong (maphong, tenphong, loaiphong_id, tang, trangthai) VALUES ('P202', 'Phòng 202', 2, 2, 'dang_thue');
INSERT INTO phong (maphong, tenphong, loaiphong_id, tang, trangthai) VALUES ('P203', 'Phòng 203', 2, 2, 'trong');
INSERT INTO phong (maphong, tenphong, loaiphong_id, tang, trangthai) VALUES ('P301', 'Phòng 301', 3, 3, 'trong');
INSERT INTO phong (maphong, tenphong, loaiphong_id, tang, trangthai) VALUES ('P302', 'Phòng 302', 3, 3, 'bao_tri');
INSERT INTO phong (maphong, tenphong, loaiphong_id, tang, trangthai) VALUES ('P401', 'Suite 401', 4, 4, 'trong');
INSERT INTO phong (maphong, tenphong, loaiphong_id, tang, trangthai) VALUES ('P402', 'Suite 402', 4, 4, 'da_dat');
INSERT INTO phong (maphong, tenphong, loaiphong_id, tang, trangthai) VALUES ('P501', 'VIP 501', 5, 5, 'trong');

-- Khach hang
INSERT INTO khachhang (makh, taikhoan_id, hoten, cmnd, sdt, email, diachi, gioitinh, ngaysinh, quoctich) VALUES ('KH001', 4, 'Trần Văn Bình', '123456789', '0901234567', 'binh@email.com', '123 Nguyễn Huệ, Q.1, TP.HCM', 'Nam', '1990-05-15', 'Việt Nam');
INSERT INTO khachhang (makh, taikhoan_id, hoten, cmnd, sdt, email, diachi, gioitinh, ngaysinh, quoctich) VALUES ('KH002', 5, 'Nguy Thị Lan', '987654321', '0909876543', 'lan@email.com', '456 Lê Lợi, Q.1, TP.HCM', 'Nu', '1995-08-20', 'Việt Nam');
INSERT INTO khachhang (makh, hoten, cmnd, sdt, email, diachi, gioitinh, ngaysinh, quoctich) VALUES ('KH003', 'Lê Minh Tuấn', '111222333', '0912345678', 'tuan@email.com', '789 Trần Hưng Đạo, Hà Nội', 'Nam', '1988-12-10', 'Việt Nam');
INSERT INTO khachhang (makh, hoten, cmnd, sdt, email, diachi, gioitinh, ngaysinh, quoctich) VALUES ('KH004', 'John Smith', 'PASS123456', '0912345001', 'john@email.com', 'New York, USA', 'Nam', '1985-03-25', 'Mỹ');
INSERT INTO khachhang (makh, hoten, cmnd, sdt, email, diachi, gioitinh, ngaysinh, quoctich) VALUES ('KH005', 'Yuki Tanaka', 'JP789012', '0912345002', 'yuki@email.com', 'Tokyo, Japan', 'Nu', '1992-07-14', 'Nhật Bản');

-- Nhan vien
INSERT INTO nhanvien (manv, taikhoan_id, hoten, sdt, email, diachi, chucvu, phongban, luong, ngaysinh, gioitinh, ngvaolam, trangthai) VALUES ('NV001', 2, 'Nguyễn Văn An', '0901111222', 'nva@hotel.com', 'Hà Nội', 'Lễ tân', 'Tiếp tân', 8000000, '1992-03-10', 'Nam', '2020-01-15', 'hoat_dong');
INSERT INTO nhanvien (manv, taikhoan_id, hoten, sdt, email, diachi, chucvu, phongban, luong, ngaysinh, gioitinh, ngvaolam, trangthai) VALUES ('NV002', 3, 'Trần Thị Bích', '0902222333', 'ntb@hotel.com', 'Hà Nội', 'Quản lý', 'Quản lý', 18000000, '1988-07-22', 'Nu', '2018-06-01', 'hoat_dong');
INSERT INTO nhanvien (manv, hoten, sdt, email, diachi, chucvu, phongban, luong, ngaysinh, gioitinh, ngvaolam, trangthai) VALUES ('NV003', 'Phạm Văn Cường', '0903333444', 'pvc@hotel.com', 'Hà Nội', 'Buồng phòng', 'Hậu cần', 7000000, '1995-11-05', 'Nam', '2021-03-20', 'hoat_dong');
INSERT INTO nhanvien (manv, hoten, sdt, email, diachi, chucvu, phongban, luong, ngaysinh, gioitinh, ngvaolam, trangthai) VALUES ('NV004', 'Lê Thị Hồng', '0904444555', 'lth@hotel.com', 'Hà Nội', 'Kế toán', 'Tài chính', 12000000, '1990-09-18', 'Nu', '2019-08-10', 'hoat_dong');
INSERT INTO nhanvien (manv, hoten, sdt, email, diachi, chucvu, phongban, luong, ngaysinh, gioitinh, ngvaolam, trangthai) VALUES ('NV005', 'Hoàng Minh Đức', '0905555666', 'hmd@hotel.com', 'Hà Nội', 'Bảo vệ', 'An ninh', 6000000, '1993-04-30', 'Nam', '2022-02-01', 'hoat_dong');

-- Dich vu
INSERT INTO dichvu (madv, tendv, mota, gia, donvitinh, trangthai) VALUES ('DV001', 'Ăn sáng buffet', 'Buffet ăn sáng với hơn 50 món Á - Âu', 150000, 'suất', 'hoat_dong');
INSERT INTO dichvu (madv, tendv, mota, gia, donvitinh, trangthai) VALUES ('DV002', 'Giặt ủi', 'Dịch vụ giặt ủi quần áo nhanh chóng', 50000, 'món', 'hoat_dong');
INSERT INTO dichvu (madv, tendv, mota, gia, donvitinh, trangthai) VALUES ('DV003', 'Spa & Massage', 'Dịch vụ spa massage thư giãn chuyên nghiệp', 500000, 'lần', 'hoat_dong');
INSERT INTO dichvu (madv, tendv, mota, gia, donvitinh, trangthai) VALUES ('DV004', 'Xe đưa đón sân bay', 'Xe đưa đón sân bay cao cấp', 300000, 'lần', 'hoat_dong');
INSERT INTO dichvu (madv, tendv, mota, gia, donvitinh, trangthai) VALUES ('DV005', 'Minibar', 'Sử dụng minibar trong phòng', 200000, 'lần', 'hoat_dong');
INSERT INTO dichvu (madv, tendv, mota, gia, donvitinh, trangthai) VALUES ('DV006', 'Hội phòng', 'Thuê phòng họp trang thiết bị đầy đủ', 2000000, 'ngày', 'hoat_dong');
INSERT INTO dichvu (madv, tendv, mota, gia, donvitinh, trangthai) VALUES ('DV007', 'Tour du lịch', 'Tour du lịch thành phố nửa ngày', 800000, 'người', 'hoat_dong');

-- Dat phong
INSERT INTO datphong (madatphong, khachhang_id, phong_id, nhanvien_id, ngayden, ngaydi, songuoi, trangthai, yeucau) VALUES ('DP001', 1, 5, 1, '2026-05-01', '2026-05-03', 2, 'da_tra_phong', 'Phòng view thành phố');
INSERT INTO datphong (madatphong, khachhang_id, phong_id, nhanvien_id, ngayden, ngaydi, songuoi, trangthai) VALUES ('DP002', 2, 1, 1, '2026-05-05', '2026-05-07', 1, 'da_nhan_phong', '');
INSERT INTO datphong (madatphong, khachhang_id, phong_id, nhanvien_id, ngayden, ngaydi, songuoi, trangthai) VALUES ('DP003', 3, 7, 2, '2026-05-10', '2026-05-12', 3, 'cho_xac_nhan', 'Cần giường phụ');
INSERT INTO datphong (madatphong, khachhang_id, phong_id, nhanvien_id, ngayden, ngaydi, songuoi, trangthai) VALUES ('DP004', 4, 9, 1, '2026-05-15', '2026-05-18', 2, 'da_xac_nhan', 'Phòng không hút thuốc');
INSERT INTO datphong (madatphong, khachhang_id, phong_id, nhanvien_id, ngayden, ngaydi, songuoi, trangthai) VALUES ('DP005', 5, 11, 2, '2026-05-20', '2026-05-25', 4, 'cho_xac_nhan', 'Cần xe đưa đón sân bay');

-- Su dung dich vu
INSERT INTO sudung_dichvu (datphong_id, dichvu_id, soluong, thanhtien) VALUES (1, 1, 4, 600000);
INSERT INTO sudung_dichvu (datphong_id, dichvu_id, soluong, thanhtien) VALUES (1, 3, 1, 500000);
INSERT INTO sudung_dichvu (datphong_id, dichvu_id, soluong, thanhtien) VALUES (2, 1, 2, 300000);
INSERT INTO sudung_dichvu (datphong_id, dichvu_id, soluong, thanhtien) VALUES (2, 5, 1, 200000);

-- Hoa don
INSERT INTO hoadon (mahoadon, datphong_id, nhanvien_id, tienphong, tiendichvu, tongtien, thanhtoan, trangthai, pt_thanhtoan) VALUES ('HD001', 1, 1, 1000000, 1100000, 2100000, 2100000, 'da_thanh_toan', 'chuyen_khoan');
INSERT INTO hoadon (mahoadon, datphong_id, nhanvien_id, tienphong, tiendichvu, tongtien, thanhtoan, trangthai, pt_thanhtoan) VALUES ('HD002', 2, 1, 600000, 500000, 1100000, 0, 'chua_thanh_toan', 'tien_mat');

-- Danh gia
INSERT INTO danhgia (khachhang_id, phong_id, datphong_id, diem, nhanxet) VALUES (1, 5, 1, 5, 'Phòng rất đẹp, nhân viên thân thiện, sẽ quay lại!');
INSERT INTO danhgia (khachhang_id, phong_id, diem, nhanxet) VALUES (2, 1, 4, 'Phòng sạch sẽ, giá hợp lý');

-- Thong bao
INSERT INTO thongbao (taikhoan_id, tieude, noidung, loai) VALUES (1, 'Chào mừng', 'Chào mừng bạn đến với hệ thống quản lý khách sạn Nhóm 3!', 'he_thong');
INSERT INTO thongbao (taikhoan_id, tieude, noidung, loai) VALUES (4, 'Đặt phòng thành công', 'Bạn đã đặt phòng thành công. Vui lòng chờ xác nhận.', 'dat_phong');
