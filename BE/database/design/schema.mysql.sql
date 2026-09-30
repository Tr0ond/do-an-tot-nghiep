-- Thiết kế MySQL 8.0.16+; chưa phải migration Laravel hoặc bằng chứng chạy DB.
-- Chạy trên database mới đã chọn. Không DROP, không tự xóa dữ liệu. Thời gian lưu UTC.
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE `tai_khoan` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ho_ten` VARCHAR(255) NULL,
  `email` VARCHAR(191) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `vai_tro` ENUM('KHACH_HANG','HUAN_LUYEN_VIEN','ADMIN') NOT NULL,
  `trang_thai` VARCHAR(32) NOT NULL,
  `remember_token` VARCHAR(100) NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ho_so_khach_hang` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tai_khoan_id` BIGINT UNSIGNED NOT NULL,
  `muc_tieu` VARCHAR(255) NULL,
  `kinh_nghiem` VARCHAR(255) NULL,
  `gioi_tinh` VARCHAR(32) NULL,
  `ngay_sinh` DATE NULL,
  `thoi_gian_co_the_tap` JSON NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ho_so_huan_luyen_vien` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tai_khoan_id` BIGINT UNSIGNED NOT NULL,
  `chuyen_mon` VARCHAR(255) NULL,
  `gioi_thieu` TEXT NULL,
  `anh_dai_dien` VARCHAR(255) NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (tai_khoan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `goi_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ten_goi` VARCHAR(255) NOT NULL,
  `gia` BIGINT UNSIGNED NOT NULL,
  `co_chatbot` BOOLEAN NOT NULL DEFAULT FALSE,
  `so_luot_chatbot_moi_ngay` INT UNSIGNED NOT NULL DEFAULT 0,
  `so_buoi_pt` INT UNSIGNED NOT NULL DEFAULT 0,
  `thoi_han_ngay` INT UNSIGNED NOT NULL DEFAULT 0,
  `trang_thai` VARCHAR(32) NOT NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  CHECK (thoi_han_ngay > 0),
  CHECK ((co_chatbot = 1 AND so_luot_chatbot_moi_ngay > 0) OR (co_chatbot = 0 AND so_luot_chatbot_moi_ngay = 0)),
  CHECK (co_chatbot = 1 OR so_buoi_pt > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dang_ky_goi_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `khach_hang_id` BIGINT UNSIGNED NOT NULL,
  `goi_tap_id` BIGINT UNSIGNED NOT NULL,
  `client_request_id` CHAR(36) NOT NULL,
  `ma_don_payos` BIGINT UNSIGNED NOT NULL,
  `ma_link_payos` VARCHAR(191) NULL,
  `ten_goi_snapshot` VARCHAR(255) NOT NULL,
  `gia_snapshot` BIGINT UNSIGNED NOT NULL,
  `co_chatbot_snapshot` BOOLEAN NOT NULL,
  `so_luot_chatbot_moi_ngay_snapshot` INT UNSIGNED NOT NULL DEFAULT 0,
  `so_buoi_pt_snapshot` INT UNSIGNED NOT NULL DEFAULT 0,
  `thoi_han_ngay_snapshot` INT UNSIGNED NOT NULL DEFAULT 0,
  `so_buoi_con_lai` INT UNSIGNED NOT NULL DEFAULT 0,
  `trang_thai` VARCHAR(32) NOT NULL,
  `han_thanh_toan` DATETIME(6) NULL,
  `kich_hoat_luc` DATETIME(6) NULL,
  `het_han_luc` DATETIME(6) NULL,
  `khach_dang_dung_id` BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN trang_thai = 'DANG_SU_DUNG' THEN khach_hang_id ELSE NULL END) STORED,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (ma_don_payos),
  UNIQUE (ma_link_payos),
  UNIQUE (khach_hang_id, client_request_id),
  UNIQUE (khach_dang_dung_id),
  CHECK (so_buoi_con_lai <= so_buoi_pt_snapshot),
  INDEX (khach_hang_id, trang_thai, het_han_luc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `thanh_toan` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dang_ky_goi_tap_id` BIGINT UNSIGNED NOT NULL,
  `ma_giao_dich` VARCHAR(191) NOT NULL,
  `so_tien` BIGINT UNSIGNED NOT NULL,
  `thanh_toan_luc` DATETIME(6) NULL,
  `xac_minh_luc` DATETIME(6) NULL,
  `trang_thai` VARCHAR(32) NOT NULL,
  `ly_do_doi_soat` TEXT NULL,
  `nguoi_doi_soat_id` BIGINT UNSIGNED NULL,
  `doi_soat_luc` DATETIME(6) NULL,
  `so_tien_hoan` BIGINT UNSIGNED NULL,
  `ma_hoan_tien` VARCHAR(191) NULL,
  `ly_do_hoan_tien` TEXT NULL,
  `hoan_tien_luc` DATETIME(6) NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (ma_giao_dich),
  CHECK (so_tien_hoan IS NULL OR so_tien_hoan <= so_tien)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phan_cong_huan_luyen_vien` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `khach_hang_id` BIGINT UNSIGNED NOT NULL,
  `huan_luyen_vien_id` BIGINT UNSIGNED NOT NULL,
  `nguoi_phan_cong_id` BIGINT UNSIGNED NOT NULL,
  `bat_dau_luc` DATETIME(6) NULL,
  `ket_thuc_luc` DATETIME(6) NULL,
  `ly_do_ket_thuc` TEXT NULL,
  `khach_dang_phan_cong_id` BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN ket_thuc_luc IS NULL THEN khach_hang_id ELSE NULL END) STORED,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (khach_dang_phan_cong_id),
  CHECK (ket_thuc_luc IS NULL OR ket_thuc_luc > bat_dau_luc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `khung_gio_huan_luyen_vien` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `huan_luyen_vien_id` BIGINT UNSIGNED NOT NULL,
  `bat_dau_luc` DATETIME(6) NOT NULL,
  `ket_thuc_luc` DATETIME(6) NOT NULL,
  `trang_thai` VARCHAR(32) NOT NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (huan_luyen_vien_id, bat_dau_luc),
  CHECK (TIMESTAMPDIFF(SECOND, bat_dau_luc, ket_thuc_luc) = 3600)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lich_hen_huan_luyen` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `khach_hang_id` BIGINT UNSIGNED NOT NULL,
  `huan_luyen_vien_id` BIGINT UNSIGNED NOT NULL,
  `phan_cong_id` BIGINT UNSIGNED NOT NULL,
  `khung_gio_id` BIGINT UNSIGNED NOT NULL,
  `dang_ky_goi_tap_id` BIGINT UNSIGNED NOT NULL,
  `client_request_id` CHAR(36) NOT NULL,
  `bat_dau_luc` DATETIME(6) NOT NULL,
  `ket_thuc_luc` DATETIME(6) NOT NULL,
  `trang_thai` VARCHAR(32) NOT NULL,
  `han_xac_nhan_dat_lich` DATETIME(6) NULL,
  `han_xac_nhan_hoan_thanh` DATETIME(6) NULL,
  `xac_nhan_luc` DATETIME(6) NULL,
  `tieu_hao_luc` DATETIME(6) NULL,
  `nguoi_huy_id` BIGINT UNSIGNED NULL,
  `huy_luc` DATETIME(6) NULL,
  `ly_do_huy` TEXT NULL,
  `nguoi_dong_xu_ly_id` BIGINT UNSIGNED NULL,
  `dong_xu_ly_luc` DATETIME(6) NULL,
  `ly_do_dong_xu_ly` TEXT NULL,
  `khung_gio_dang_giu_id` BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN trang_thai IN ('CHO_XAC_NHAN','DA_XAC_NHAN') THEN khung_gio_id ELSE NULL END) STORED,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (khung_gio_dang_giu_id),
  UNIQUE (khach_hang_id, client_request_id),
  INDEX (khach_hang_id, bat_dau_luc, ket_thuc_luc),
  CHECK (TIMESTAMPDIFF(SECOND, bat_dau_luc, ket_thuc_luc) = 3600)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `nhom_co` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ma_nhom_co` VARCHAR(64) NOT NULL,
  `ten_nhom_co` VARCHAR(255) NOT NULL,
  `ten_nguon` VARCHAR(255) NOT NULL,
  `trang_thai` VARCHAR(32) NOT NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (ma_nhom_co)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bai_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nhom_co_id` BIGINT UNSIGNED NOT NULL,
  `nguon_du_lieu` VARCHAR(64) NULL,
  `ma_nguon` CHAR(4) NULL,
  `ten_bai_tap` VARCHAR(255) NOT NULL,
  `ten_tieng_viet` VARCHAR(255) NULL,
  `bo_phan_co_the` VARCHAR(64) NULL,
  `dung_cu` VARCHAR(255) NULL,
  `dung_cu_nguon` VARCHAR(255) NULL,
  `huong_dan` JSON NULL,
  `cac_buoc` JSON NULL,
  `co_phu` JSON NULL,
  `co_ho_tro_nguon` VARCHAR(255) NULL,
  `anh_url` VARCHAR(255) NULL,
  `gif_url` VARCHAR(255) NULL,
  `duong_dan_anh_nguon` VARCHAR(255) NULL,
  `duong_dan_gif_nguon` VARCHAR(255) NULL,
  `ma_media_nguon` VARCHAR(64) NULL,
  `ghi_cong_media` VARCHAR(255) NULL,
  `nguon_tao_luc` DATETIME(6) NULL,
  `nguon_cap_nhat_luc` DATETIME(6) NULL,
  `trang_thai` VARCHAR(32) NOT NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (nguon_du_lieu, ma_nguon),
  INDEX (nhom_co_id, trang_thai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `giao_an_mau` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ten_giao_an` VARCHAR(255) NOT NULL,
  `muc_tieu` VARCHAR(255) NULL,
  `so_ngay_tap` INT UNSIGNED NOT NULL DEFAULT 0,
  `nguoi_tao_id` BIGINT UNSIGNED NOT NULL,
  `nguoi_duyet_id` BIGINT UNSIGNED NULL,
  `duyet_luc` DATETIME(6) NULL,
  `trang_thai` VARCHAR(32) NOT NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bai_tap_trong_giao_an_mau` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `giao_an_mau_id` BIGINT UNSIGNED NOT NULL,
  `bai_tap_id` BIGINT UNSIGNED NOT NULL,
  `ngay_thu` INT UNSIGNED NOT NULL DEFAULT 1,
  `thu_tu` INT UNSIGNED NOT NULL DEFAULT 1,
  `so_hiep` INT UNSIGNED NOT NULL DEFAULT 1,
  `so_lan_lap` INT UNSIGNED NOT NULL DEFAULT 1,
  `nghi_giay` INT UNSIGNED NOT NULL DEFAULT 0,
  `ghi_chu` TEXT NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (giao_an_mau_id, ngay_thu, thu_tu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ke_hoach_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `khach_hang_id` BIGINT UNSIGNED NOT NULL,
  `huan_luyen_vien_id` BIGINT UNSIGNED NOT NULL,
  `phan_cong_id` BIGINT UNSIGNED NOT NULL,
  `giao_an_mau_id` BIGINT UNSIGNED NULL,
  `thay_the_ke_hoach_id` BIGINT UNSIGNED NULL,
  `ten_ke_hoach` VARCHAR(255) NOT NULL,
  `muc_tieu` VARCHAR(255) NULL,
  `trang_thai` VARCHAR(32) NOT NULL,
  `gui_luc` DATETIME(6) NULL,
  `han_duyet` DATETIME(6) NULL,
  `duyet_luc` DATETIME(6) NULL,
  `khach_dang_ap_dung_id` BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN trang_thai = 'DANG_AP_DUNG' THEN khach_hang_id ELSE NULL END) STORED,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (khach_dang_ap_dung_id),
  INDEX (khach_hang_id, trang_thai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bai_tap_trong_ke_hoach` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ke_hoach_tap_id` BIGINT UNSIGNED NOT NULL,
  `bai_tap_id` BIGINT UNSIGNED NOT NULL,
  `ngay_thu` INT UNSIGNED NOT NULL DEFAULT 1,
  `thu_tu` INT UNSIGNED NOT NULL DEFAULT 1,
  `ten_bai_tap_snapshot` VARCHAR(255) NOT NULL,
  `noi_dung_snapshot` JSON NULL,
  `so_hiep` INT UNSIGNED NOT NULL DEFAULT 1,
  `so_lan_lap` INT UNSIGNED NOT NULL DEFAULT 1,
  `nghi_giay` INT UNSIGNED NOT NULL DEFAULT 0,
  `ghi_chu` TEXT NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (ke_hoach_tap_id, ngay_thu, thu_tu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lich_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ke_hoach_tap_id` BIGINT UNSIGNED NOT NULL,
  `khach_hang_id` BIGINT UNSIGNED NOT NULL,
  `ngay_thu` INT UNSIGNED NOT NULL DEFAULT 1,
  `ngay_tap` DATE NOT NULL,
  `trang_thai` VARCHAR(32) NOT NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (ke_hoach_tap_id, ngay_tap, ngay_thu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phien_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lich_tap_id` BIGINT UNSIGNED NOT NULL,
  `khach_hang_id` BIGINT UNSIGNED NOT NULL,
  `bat_dau_luc` DATETIME(6) NULL,
  `hoan_thanh_luc` DATETIME(6) NULL,
  `trang_thai` VARCHAR(32) NOT NULL,
  `ghi_chu` TEXT NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (lich_tap_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bai_tap_trong_phien` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phien_tap_id` BIGINT UNSIGNED NOT NULL,
  `bai_tap_id` BIGINT UNSIGNED NOT NULL,
  `bai_tap_trong_ke_hoach_id` BIGINT UNSIGNED NOT NULL,
  `thu_tu` INT UNSIGNED NOT NULL DEFAULT 1,
  `ten_bai_tap_snapshot` VARCHAR(255) NOT NULL,
  `noi_dung_snapshot` JSON NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (phien_tap_id, thu_tu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `hiep_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bai_tap_trong_phien_id` BIGINT UNSIGNED NOT NULL,
  `thu_tu` INT UNSIGNED NOT NULL DEFAULT 1,
  `so_lan_lap` INT UNSIGNED NOT NULL DEFAULT 0,
  `khoi_luong_kg` DECIMAL(7,2) UNSIGNED NULL,
  `nghi_giay` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (bai_tap_trong_phien_id, thu_tu),
  CHECK (thu_tu > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ghi_chu_huan_luyen` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phien_tap_id` BIGINT UNSIGNED NOT NULL,
  `huan_luyen_vien_id` BIGINT UNSIGNED NOT NULL,
  `phan_cong_id` BIGINT UNSIGNED NOT NULL,
  `noi_dung` TEXT NOT NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `hoi_thoai` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phan_cong_id` BIGINT UNSIGNED NOT NULL,
  `cursor_khach_da_doc` BIGINT UNSIGNED NULL,
  `cursor_pt_da_doc` BIGINT UNSIGNED NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (phan_cong_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tin_nhan` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_thoai_id` BIGINT UNSIGNED NOT NULL,
  `nguoi_gui_id` BIGINT UNSIGNED NOT NULL,
  `client_message_id` CHAR(36) NOT NULL,
  `noi_dung` TEXT NOT NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (hoi_thoai_id, nguoi_gui_id, client_message_id),
  INDEX (hoi_thoai_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `hoi_thoai_tro_ly` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `khach_hang_id` BIGINT UNSIGNED NOT NULL,
  `tieu_de` VARCHAR(255) NULL,
  `trang_thai` VARCHAR(32) NOT NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `yeu_cau_tro_ly` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `khach_hang_id` BIGINT UNSIGNED NOT NULL,
  `hoi_thoai_tro_ly_id` BIGINT UNSIGNED NOT NULL,
  `dang_ky_goi_tap_id` BIGINT UNSIGNED NOT NULL,
  `client_request_id` CHAR(36) NOT NULL,
  `ngay_han_muc` DATE NOT NULL,
  `provider` VARCHAR(64) NULL,
  `model` VARCHAR(128) NULL,
  `trang_thai` VARCHAR(32) NOT NULL,
  `giu_luot_den` DATETIME(6) NULL,
  `hoan_thanh_luc` DATETIME(6) NULL,
  `ma_loi` VARCHAR(64) NULL,
  `do_tre_ms` INT UNSIGNED NOT NULL DEFAULT 0,
  `input_tokens` INT UNSIGNED NOT NULL DEFAULT 0,
  `output_tokens` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (khach_hang_id, client_request_id),
  INDEX (khach_hang_id, ngay_han_muc, trang_thai, giu_luot_den)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tin_nhan_tro_ly` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_thoai_tro_ly_id` BIGINT UNSIGNED NOT NULL,
  `yeu_cau_tro_ly_id` BIGINT UNSIGNED NOT NULL,
  `vai_tro` ENUM('USER','ASSISTANT') NOT NULL,
  `noi_dung` TEXT NOT NULL,
  `nguon_da_kiem_tra` JSON NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (yeu_cau_tro_ly_id, vai_tro),
  INDEX (hoi_thoai_tro_ly_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tai_lieu_tu_van` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tieu_de` VARCHAR(255) NOT NULL,
  `loai` VARCHAR(32) NOT NULL,
  `noi_dung` LONGTEXT NOT NULL,
  `phien_ban` INT UNSIGNED NOT NULL DEFAULT 1,
  `nguoi_cap_nhat_id` BIGINT UNSIGNED NOT NULL,
  `trang_thai` VARCHAR(32) NOT NULL,
  `xuat_ban_luc` DATETIME(6) NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chi_so_co_the` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `khach_hang_id` BIGINT UNSIGNED NOT NULL,
  `ngay_ghi` DATE NOT NULL,
  `can_nang_kg` DECIMAL(6,2) UNSIGNED NULL,
  `chieu_cao_cm` DECIMAL(5,2) UNSIGNED NULL,
  `vong_eo_cm` DECIMAL(5,2) UNSIGNED NULL,
  `ghi_chu` TEXT NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  UNIQUE (khach_hang_id, ngay_ghi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `nhat_ky_he_thong` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tai_khoan_id` BIGINT UNSIGNED NULL,
  `hanh_dong` VARCHAR(128) NOT NULL,
  `loai_tai_nguyen` VARCHAR(128) NOT NULL,
  `tai_nguyen_id` BIGINT UNSIGNED NULL,
  `metadata_an_toan` JSON NULL,
  `created_at` DATETIME(6) NULL,
  `updated_at` DATETIME(6) NULL,
  PRIMARY KEY (id),
  INDEX (loai_tai_nguyen, tai_nguyen_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `ho_so_khach_hang` ADD FOREIGN KEY (`tai_khoan_id`) REFERENCES `tai_khoan` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `ho_so_huan_luyen_vien` ADD FOREIGN KEY (`tai_khoan_id`) REFERENCES `tai_khoan` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `dang_ky_goi_tap` ADD FOREIGN KEY (`khach_hang_id`) REFERENCES `ho_so_khach_hang` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `dang_ky_goi_tap` ADD FOREIGN KEY (`goi_tap_id`) REFERENCES `goi_tap` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `thanh_toan` ADD FOREIGN KEY (`dang_ky_goi_tap_id`) REFERENCES `dang_ky_goi_tap` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `thanh_toan` ADD FOREIGN KEY (`nguoi_doi_soat_id`) REFERENCES `tai_khoan` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `phan_cong_huan_luyen_vien` ADD FOREIGN KEY (`khach_hang_id`) REFERENCES `ho_so_khach_hang` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `phan_cong_huan_luyen_vien` ADD FOREIGN KEY (`huan_luyen_vien_id`) REFERENCES `ho_so_huan_luyen_vien` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `phan_cong_huan_luyen_vien` ADD FOREIGN KEY (`nguoi_phan_cong_id`) REFERENCES `tai_khoan` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `khung_gio_huan_luyen_vien` ADD FOREIGN KEY (`huan_luyen_vien_id`) REFERENCES `ho_so_huan_luyen_vien` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `lich_hen_huan_luyen` ADD FOREIGN KEY (`khach_hang_id`) REFERENCES `ho_so_khach_hang` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `lich_hen_huan_luyen` ADD FOREIGN KEY (`huan_luyen_vien_id`) REFERENCES `ho_so_huan_luyen_vien` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `lich_hen_huan_luyen` ADD FOREIGN KEY (`phan_cong_id`) REFERENCES `phan_cong_huan_luyen_vien` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `lich_hen_huan_luyen` ADD FOREIGN KEY (`khung_gio_id`) REFERENCES `khung_gio_huan_luyen_vien` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `lich_hen_huan_luyen` ADD FOREIGN KEY (`dang_ky_goi_tap_id`) REFERENCES `dang_ky_goi_tap` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `lich_hen_huan_luyen` ADD FOREIGN KEY (`nguoi_huy_id`) REFERENCES `tai_khoan` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `lich_hen_huan_luyen` ADD FOREIGN KEY (`nguoi_dong_xu_ly_id`) REFERENCES `tai_khoan` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `bai_tap` ADD FOREIGN KEY (`nhom_co_id`) REFERENCES `nhom_co` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `giao_an_mau` ADD FOREIGN KEY (`nguoi_tao_id`) REFERENCES `tai_khoan` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `giao_an_mau` ADD FOREIGN KEY (`nguoi_duyet_id`) REFERENCES `tai_khoan` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `bai_tap_trong_giao_an_mau` ADD FOREIGN KEY (`giao_an_mau_id`) REFERENCES `giao_an_mau` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `bai_tap_trong_giao_an_mau` ADD FOREIGN KEY (`bai_tap_id`) REFERENCES `bai_tap` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `ke_hoach_tap` ADD FOREIGN KEY (`khach_hang_id`) REFERENCES `ho_so_khach_hang` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `ke_hoach_tap` ADD FOREIGN KEY (`huan_luyen_vien_id`) REFERENCES `ho_so_huan_luyen_vien` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `ke_hoach_tap` ADD FOREIGN KEY (`phan_cong_id`) REFERENCES `phan_cong_huan_luyen_vien` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `ke_hoach_tap` ADD FOREIGN KEY (`giao_an_mau_id`) REFERENCES `giao_an_mau` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `ke_hoach_tap` ADD FOREIGN KEY (`thay_the_ke_hoach_id`) REFERENCES `ke_hoach_tap` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `bai_tap_trong_ke_hoach` ADD FOREIGN KEY (`ke_hoach_tap_id`) REFERENCES `ke_hoach_tap` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `bai_tap_trong_ke_hoach` ADD FOREIGN KEY (`bai_tap_id`) REFERENCES `bai_tap` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `lich_tap` ADD FOREIGN KEY (`ke_hoach_tap_id`) REFERENCES `ke_hoach_tap` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `lich_tap` ADD FOREIGN KEY (`khach_hang_id`) REFERENCES `ho_so_khach_hang` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `phien_tap` ADD FOREIGN KEY (`lich_tap_id`) REFERENCES `lich_tap` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `phien_tap` ADD FOREIGN KEY (`khach_hang_id`) REFERENCES `ho_so_khach_hang` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `bai_tap_trong_phien` ADD FOREIGN KEY (`phien_tap_id`) REFERENCES `phien_tap` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `bai_tap_trong_phien` ADD FOREIGN KEY (`bai_tap_id`) REFERENCES `bai_tap` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `bai_tap_trong_phien` ADD FOREIGN KEY (`bai_tap_trong_ke_hoach_id`) REFERENCES `bai_tap_trong_ke_hoach` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `hiep_tap` ADD FOREIGN KEY (`bai_tap_trong_phien_id`) REFERENCES `bai_tap_trong_phien` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `ghi_chu_huan_luyen` ADD FOREIGN KEY (`phien_tap_id`) REFERENCES `phien_tap` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `ghi_chu_huan_luyen` ADD FOREIGN KEY (`huan_luyen_vien_id`) REFERENCES `ho_so_huan_luyen_vien` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `ghi_chu_huan_luyen` ADD FOREIGN KEY (`phan_cong_id`) REFERENCES `phan_cong_huan_luyen_vien` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `hoi_thoai` ADD FOREIGN KEY (`phan_cong_id`) REFERENCES `phan_cong_huan_luyen_vien` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `tin_nhan` ADD FOREIGN KEY (`hoi_thoai_id`) REFERENCES `hoi_thoai` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `tin_nhan` ADD FOREIGN KEY (`nguoi_gui_id`) REFERENCES `tai_khoan` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `hoi_thoai_tro_ly` ADD FOREIGN KEY (`khach_hang_id`) REFERENCES `ho_so_khach_hang` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `yeu_cau_tro_ly` ADD FOREIGN KEY (`khach_hang_id`) REFERENCES `ho_so_khach_hang` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `yeu_cau_tro_ly` ADD FOREIGN KEY (`hoi_thoai_tro_ly_id`) REFERENCES `hoi_thoai_tro_ly` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `yeu_cau_tro_ly` ADD FOREIGN KEY (`dang_ky_goi_tap_id`) REFERENCES `dang_ky_goi_tap` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `tin_nhan_tro_ly` ADD FOREIGN KEY (`hoi_thoai_tro_ly_id`) REFERENCES `hoi_thoai_tro_ly` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `tin_nhan_tro_ly` ADD FOREIGN KEY (`yeu_cau_tro_ly_id`) REFERENCES `yeu_cau_tro_ly` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `tai_lieu_tu_van` ADD FOREIGN KEY (`nguoi_cap_nhat_id`) REFERENCES `tai_khoan` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `chi_so_co_the` ADD FOREIGN KEY (`khach_hang_id`) REFERENCES `ho_so_khach_hang` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `nhat_ky_he_thong` ADD FOREIGN KEY (`tai_khoan_id`) REFERENCES `tai_khoan` (id) ON DELETE RESTRICT ON UPDATE RESTRICT;
