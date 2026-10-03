# Từ điển dữ liệu

Sinh từ `scripts/databaseSchema.mjs`. Đầy đủ 28 bảng/52 FK; sơ đồ tại [database.drawio](diagrams/database.drawio). SQL chưa chạy trên MySQL.

## tai_khoan

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `ho_ten` | `VARCHAR(255) NULL` |  |
| `email` | `VARCHAR(191) NOT NULL` |  |
| `password` | `VARCHAR(255) NOT NULL` |  |
| `vai_tro` | `ENUM('KHACH_HANG','HUAN_LUYEN_VIEN','ADMIN') NOT NULL` |  |
| `trang_thai` | `VARCHAR(32) NOT NULL` |  |
| `remember_token` | `VARCHAR(100) NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (email)`

## ho_so_khach_hang

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `tai_khoan_id` | `BIGINT UNSIGNED NOT NULL` | tai_khoan |
| `muc_tieu` | `VARCHAR(255) NULL` |  |
| `kinh_nghiem` | `VARCHAR(255) NULL` |  |
| `gioi_tinh` | `VARCHAR(32) NULL` |  |
| `ngay_sinh` | `DATE NULL` |  |
| `thoi_gian_co_the_tap` | `JSON NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (tai_khoan_id)`

## ho_so_huan_luyen_vien

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `tai_khoan_id` | `BIGINT UNSIGNED NOT NULL` | tai_khoan |
| `chuyen_mon` | `VARCHAR(255) NULL` |  |
| `gioi_thieu` | `TEXT NULL` |  |
| `anh_dai_dien` | `VARCHAR(255) NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (tai_khoan_id)`

## goi_tap

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `ten_goi` | `VARCHAR(255) NOT NULL` |  |
| `gia` | `BIGINT UNSIGNED NOT NULL` |  |
| `co_chatbot` | `BOOLEAN NOT NULL DEFAULT FALSE` |  |
| `so_luot_chatbot_moi_ngay` | `INT UNSIGNED NOT NULL DEFAULT 0` |  |
| `so_buoi_pt` | `INT UNSIGNED NOT NULL DEFAULT 0` |  |
| `thoi_han_ngay` | `INT UNSIGNED NOT NULL DEFAULT 0` |  |
| `trang_thai` | `VARCHAR(32) NOT NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `CHECK (thoi_han_ngay > 0)`; `CHECK ((co_chatbot = 1 AND so_luot_chatbot_moi_ngay > 0) OR (co_chatbot = 0 AND so_luot_chatbot_moi_ngay = 0))`; `CHECK (co_chatbot = 1 OR so_buoi_pt > 0)`

## dang_ky_goi_tap

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `khach_hang_id` | `BIGINT UNSIGNED NOT NULL` | ho_so_khach_hang |
| `goi_tap_id` | `BIGINT UNSIGNED NOT NULL` | goi_tap |
| `client_request_id` | `CHAR(36) NOT NULL` |  |
| `ma_don_payos` | `BIGINT UNSIGNED NOT NULL` |  |
| `ma_link_payos` | `VARCHAR(191) NULL` |  |
| `ten_goi_snapshot` | `VARCHAR(255) NOT NULL` |  |
| `gia_snapshot` | `BIGINT UNSIGNED NOT NULL` |  |
| `co_chatbot_snapshot` | `BOOLEAN NOT NULL` |  |
| `so_luot_chatbot_moi_ngay_snapshot` | `INT UNSIGNED NOT NULL DEFAULT 0` |  |
| `so_buoi_pt_snapshot` | `INT UNSIGNED NOT NULL DEFAULT 0` |  |
| `thoi_han_ngay_snapshot` | `INT UNSIGNED NOT NULL DEFAULT 0` |  |
| `so_buoi_con_lai` | `INT UNSIGNED NOT NULL DEFAULT 0` |  |
| `trang_thai` | `VARCHAR(32) NOT NULL` |  |
| `han_thanh_toan` | `DATETIME(6) NULL` |  |
| `kich_hoat_luc` | `DATETIME(6) NULL` |  |
| `het_han_luc` | `DATETIME(6) NULL` |  |
| `khach_dang_dung_id` | `BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN trang_thai = 'DANG_SU_DUNG' THEN khach_hang_id ELSE NULL END) STORED` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (ma_don_payos)`; `UNIQUE (ma_link_payos)`; `UNIQUE (khach_hang_id, client_request_id)`; `UNIQUE (khach_dang_dung_id)`; `CHECK (so_buoi_con_lai <= so_buoi_pt_snapshot)`; `INDEX (khach_hang_id, trang_thai, het_han_luc)`

## thanh_toan

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `dang_ky_goi_tap_id` | `BIGINT UNSIGNED NOT NULL` | dang_ky_goi_tap |
| `ma_giao_dich` | `VARCHAR(191) NOT NULL` |  |
| `so_tien` | `BIGINT UNSIGNED NOT NULL` |  |
| `thanh_toan_luc` | `DATETIME(6) NULL` |  |
| `xac_minh_luc` | `DATETIME(6) NULL` |  |
| `trang_thai` | `VARCHAR(32) NOT NULL` |  |
| `ly_do_doi_soat` | `TEXT NULL` |  |
| `nguoi_doi_soat_id` | `BIGINT UNSIGNED NULL` | tai_khoan |
| `doi_soat_luc` | `DATETIME(6) NULL` |  |
| `so_tien_hoan` | `BIGINT UNSIGNED NULL` |  |
| `ma_hoan_tien` | `VARCHAR(191) NULL` |  |
| `ly_do_hoan_tien` | `TEXT NULL` |  |
| `hoan_tien_luc` | `DATETIME(6) NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (ma_giao_dich)`; `CHECK (so_tien_hoan IS NULL OR so_tien_hoan <= so_tien)`

## phan_cong_huan_luyen_vien

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `khach_hang_id` | `BIGINT UNSIGNED NOT NULL` | ho_so_khach_hang |
| `huan_luyen_vien_id` | `BIGINT UNSIGNED NOT NULL` | ho_so_huan_luyen_vien |
| `nguoi_phan_cong_id` | `BIGINT UNSIGNED NOT NULL` | tai_khoan |
| `bat_dau_luc` | `DATETIME(6) NULL` |  |
| `ket_thuc_luc` | `DATETIME(6) NULL` |  |
| `ly_do_ket_thuc` | `TEXT NULL` |  |
| `khach_dang_phan_cong_id` | `BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN ket_thuc_luc IS NULL THEN khach_hang_id ELSE NULL END) STORED` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (khach_dang_phan_cong_id)`; `CHECK (ket_thuc_luc IS NULL OR ket_thuc_luc > bat_dau_luc)`

## khung_gio_huan_luyen_vien

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `huan_luyen_vien_id` | `BIGINT UNSIGNED NOT NULL` | ho_so_huan_luyen_vien |
| `bat_dau_luc` | `DATETIME(6) NOT NULL` |  |
| `ket_thuc_luc` | `DATETIME(6) NOT NULL` |  |
| `trang_thai` | `VARCHAR(32) NOT NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (huan_luyen_vien_id, bat_dau_luc)`; `CHECK (TIMESTAMPDIFF(SECOND, bat_dau_luc, ket_thuc_luc) = 3600)`

## lich_hen_huan_luyen

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `khach_hang_id` | `BIGINT UNSIGNED NOT NULL` | ho_so_khach_hang |
| `huan_luyen_vien_id` | `BIGINT UNSIGNED NOT NULL` | ho_so_huan_luyen_vien |
| `phan_cong_id` | `BIGINT UNSIGNED NOT NULL` | phan_cong_huan_luyen_vien |
| `khung_gio_id` | `BIGINT UNSIGNED NOT NULL` | khung_gio_huan_luyen_vien |
| `dang_ky_goi_tap_id` | `BIGINT UNSIGNED NOT NULL` | dang_ky_goi_tap |
| `client_request_id` | `CHAR(36) NOT NULL` |  |
| `bat_dau_luc` | `DATETIME(6) NOT NULL` |  |
| `ket_thuc_luc` | `DATETIME(6) NOT NULL` |  |
| `trang_thai` | `VARCHAR(32) NOT NULL` |  |
| `han_xac_nhan_dat_lich` | `DATETIME(6) NULL` |  |
| `han_xac_nhan_hoan_thanh` | `DATETIME(6) NULL` |  |
| `xac_nhan_luc` | `DATETIME(6) NULL` |  |
| `tieu_hao_luc` | `DATETIME(6) NULL` |  |
| `nguoi_huy_id` | `BIGINT UNSIGNED NULL` | tai_khoan |
| `huy_luc` | `DATETIME(6) NULL` |  |
| `ly_do_huy` | `TEXT NULL` |  |
| `nguoi_dong_xu_ly_id` | `BIGINT UNSIGNED NULL` | tai_khoan |
| `dong_xu_ly_luc` | `DATETIME(6) NULL` |  |
| `ly_do_dong_xu_ly` | `TEXT NULL` |  |
| `khung_gio_dang_giu_id` | `BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN trang_thai IN ('CHO_XAC_NHAN','DA_XAC_NHAN') THEN khung_gio_id ELSE NULL END) STORED` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (khung_gio_dang_giu_id)`; `UNIQUE (khach_hang_id, client_request_id)`; `INDEX (khach_hang_id, bat_dau_luc, ket_thuc_luc)`; `CHECK (TIMESTAMPDIFF(SECOND, bat_dau_luc, ket_thuc_luc) = 3600)`

## nhom_co

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `ma_nhom_co` | `VARCHAR(64) NOT NULL` |  |
| `ten_nhom_co` | `VARCHAR(255) NOT NULL` |  |
| `ten_nguon` | `VARCHAR(255) NOT NULL` |  |
| `trang_thai` | `VARCHAR(32) NOT NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (ma_nhom_co)`

## bai_tap

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `nhom_co_id` | `BIGINT UNSIGNED NOT NULL` | nhom_co |
| `nguon_du_lieu` | `VARCHAR(64) NULL` |  |
| `ma_nguon` | `CHAR(4) NULL` |  |
| `ten_bai_tap` | `VARCHAR(255) NOT NULL` |  |
| `ten_tieng_viet` | `VARCHAR(255) NULL` |  |
| `bo_phan_co_the` | `VARCHAR(64) NULL` |  |
| `dung_cu` | `VARCHAR(255) NULL` |  |
| `dung_cu_nguon` | `VARCHAR(255) NULL` |  |
| `huong_dan` | `JSON NULL` |  |
| `cac_buoc` | `JSON NULL` |  |
| `co_phu` | `JSON NULL` |  |
| `co_ho_tro_nguon` | `VARCHAR(255) NULL` |  |
| `anh_url` | `VARCHAR(255) NULL` |  |
| `gif_url` | `VARCHAR(255) NULL` |  |
| `duong_dan_anh_nguon` | `VARCHAR(255) NULL` |  |
| `duong_dan_gif_nguon` | `VARCHAR(255) NULL` |  |
| `ma_media_nguon` | `VARCHAR(64) NULL` |  |
| `ghi_cong_media` | `VARCHAR(255) NULL` |  |
| `nguon_tao_luc` | `DATETIME(6) NULL` |  |
| `nguon_cap_nhat_luc` | `DATETIME(6) NULL` |  |
| `trang_thai` | `VARCHAR(32) NOT NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (nguon_du_lieu, ma_nguon)`; `INDEX (nhom_co_id, trang_thai)`

## giao_an_mau

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `ten_giao_an` | `VARCHAR(255) NOT NULL` |  |
| `muc_tieu` | `VARCHAR(255) NULL` |  |
| `so_ngay_tap` | `INT UNSIGNED NOT NULL DEFAULT 0` |  |
| `nguoi_tao_id` | `BIGINT UNSIGNED NOT NULL` | tai_khoan |
| `nguoi_duyet_id` | `BIGINT UNSIGNED NULL` | tai_khoan |
| `duyet_luc` | `DATETIME(6) NULL` |  |
| `trang_thai` | `VARCHAR(32) NOT NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: PK và FK

## bai_tap_trong_giao_an_mau

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `giao_an_mau_id` | `BIGINT UNSIGNED NOT NULL` | giao_an_mau |
| `bai_tap_id` | `BIGINT UNSIGNED NOT NULL` | bai_tap |
| `ngay_thu` | `INT UNSIGNED NOT NULL DEFAULT 1` |  |
| `thu_tu` | `INT UNSIGNED NOT NULL DEFAULT 1` |  |
| `so_hiep` | `INT UNSIGNED NOT NULL DEFAULT 1` |  |
| `so_lan_lap` | `INT UNSIGNED NOT NULL DEFAULT 1` |  |
| `nghi_giay` | `INT UNSIGNED NOT NULL DEFAULT 0` |  |
| `ghi_chu` | `TEXT NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (giao_an_mau_id, ngay_thu, thu_tu)`

## ke_hoach_tap

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `khach_hang_id` | `BIGINT UNSIGNED NOT NULL` | ho_so_khach_hang |
| `huan_luyen_vien_id` | `BIGINT UNSIGNED NOT NULL` | ho_so_huan_luyen_vien |
| `phan_cong_id` | `BIGINT UNSIGNED NOT NULL` | phan_cong_huan_luyen_vien |
| `giao_an_mau_id` | `BIGINT UNSIGNED NULL` | giao_an_mau |
| `thay_the_ke_hoach_id` | `BIGINT UNSIGNED NULL` | ke_hoach_tap |
| `ten_ke_hoach` | `VARCHAR(255) NOT NULL` |  |
| `muc_tieu` | `VARCHAR(255) NULL` |  |
| `trang_thai` | `VARCHAR(32) NOT NULL` |  |
| `gui_luc` | `DATETIME(6) NULL` |  |
| `han_duyet` | `DATETIME(6) NULL` |  |
| `duyet_luc` | `DATETIME(6) NULL` |  |
| `khach_dang_ap_dung_id` | `BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN trang_thai = 'DANG_AP_DUNG' THEN khach_hang_id ELSE NULL END) STORED` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (khach_dang_ap_dung_id)`; `INDEX (khach_hang_id, trang_thai)`

## bai_tap_trong_ke_hoach

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `ke_hoach_tap_id` | `BIGINT UNSIGNED NOT NULL` | ke_hoach_tap |
| `bai_tap_id` | `BIGINT UNSIGNED NOT NULL` | bai_tap |
| `ngay_thu` | `INT UNSIGNED NOT NULL DEFAULT 1` |  |
| `thu_tu` | `INT UNSIGNED NOT NULL DEFAULT 1` |  |
| `ten_bai_tap_snapshot` | `VARCHAR(255) NOT NULL` |  |
| `noi_dung_snapshot` | `JSON NULL` |  |
| `so_hiep` | `INT UNSIGNED NOT NULL DEFAULT 1` |  |
| `so_lan_lap` | `INT UNSIGNED NOT NULL DEFAULT 1` |  |
| `nghi_giay` | `INT UNSIGNED NOT NULL DEFAULT 0` |  |
| `ghi_chu` | `TEXT NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (ke_hoach_tap_id, ngay_thu, thu_tu)`

## lich_tap

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `ke_hoach_tap_id` | `BIGINT UNSIGNED NOT NULL` | ke_hoach_tap |
| `khach_hang_id` | `BIGINT UNSIGNED NOT NULL` | ho_so_khach_hang |
| `ngay_thu` | `INT UNSIGNED NOT NULL DEFAULT 1` |  |
| `ngay_tap` | `DATE NOT NULL` |  |
| `trang_thai` | `VARCHAR(32) NOT NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (ke_hoach_tap_id, ngay_tap, ngay_thu)`

## phien_tap

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `lich_tap_id` | `BIGINT UNSIGNED NOT NULL` | lich_tap |
| `khach_hang_id` | `BIGINT UNSIGNED NOT NULL` | ho_so_khach_hang |
| `bat_dau_luc` | `DATETIME(6) NULL` |  |
| `hoan_thanh_luc` | `DATETIME(6) NULL` |  |
| `trang_thai` | `VARCHAR(32) NOT NULL` |  |
| `ghi_chu` | `TEXT NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (lich_tap_id)`

## bai_tap_trong_phien

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `phien_tap_id` | `BIGINT UNSIGNED NOT NULL` | phien_tap |
| `bai_tap_id` | `BIGINT UNSIGNED NOT NULL` | bai_tap |
| `bai_tap_trong_ke_hoach_id` | `BIGINT UNSIGNED NOT NULL` | bai_tap_trong_ke_hoach |
| `thu_tu` | `INT UNSIGNED NOT NULL DEFAULT 1` |  |
| `ten_bai_tap_snapshot` | `VARCHAR(255) NOT NULL` |  |
| `noi_dung_snapshot` | `JSON NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (phien_tap_id, thu_tu)`

## hiep_tap

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `bai_tap_trong_phien_id` | `BIGINT UNSIGNED NOT NULL` | bai_tap_trong_phien |
| `thu_tu` | `INT UNSIGNED NOT NULL DEFAULT 1` |  |
| `so_lan_lap` | `INT UNSIGNED NOT NULL DEFAULT 0` |  |
| `khoi_luong_kg` | `DECIMAL(7,2) UNSIGNED NULL` |  |
| `nghi_giay` | `INT UNSIGNED NOT NULL DEFAULT 0` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (bai_tap_trong_phien_id, thu_tu)`; `CHECK (thu_tu > 0)`

## ghi_chu_huan_luyen

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `phien_tap_id` | `BIGINT UNSIGNED NOT NULL` | phien_tap |
| `huan_luyen_vien_id` | `BIGINT UNSIGNED NOT NULL` | ho_so_huan_luyen_vien |
| `phan_cong_id` | `BIGINT UNSIGNED NOT NULL` | phan_cong_huan_luyen_vien |
| `noi_dung` | `TEXT NOT NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: PK và FK

## hoi_thoai

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `phan_cong_id` | `BIGINT UNSIGNED NOT NULL` | phan_cong_huan_luyen_vien |
| `cursor_khach_da_doc` | `BIGINT UNSIGNED NULL` |  |
| `cursor_pt_da_doc` | `BIGINT UNSIGNED NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (phan_cong_id)`

## tin_nhan

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `hoi_thoai_id` | `BIGINT UNSIGNED NOT NULL` | hoi_thoai |
| `nguoi_gui_id` | `BIGINT UNSIGNED NOT NULL` | tai_khoan |
| `client_message_id` | `CHAR(36) NOT NULL` |  |
| `noi_dung` | `TEXT NOT NULL` |  |
| `anh` | `JSON NULL` | Runtime migration000035; metadata tối đa 4 ảnh riêng tư |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (hoi_thoai_id, nguoi_gui_id, client_message_id)`; `INDEX (hoi_thoai_id, id)`

Tin chỉ có ảnh lưu nội dung rỗng. JSON gồm đường dẫn local ngẫu nhiên, MIME, tên gốc, dung lượng, SHA-256; API chỉ trả vị trí/tên/MIME/dung lượng. Cột bổ sung không có trong SQL/Draw.io baseline 28 bảng; xem migration000035 và [hợp đồng chat](features/REALTIME_CHAT.md).

## hoi_thoai_tro_ly

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `khach_hang_id` | `BIGINT UNSIGNED NOT NULL` | ho_so_khach_hang |
| `tieu_de` | `VARCHAR(255) NULL` |  |
| `trang_thai` | `VARCHAR(32) NOT NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: PK và FK

## yeu_cau_tro_ly

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `khach_hang_id` | `BIGINT UNSIGNED NOT NULL` | ho_so_khach_hang |
| `hoi_thoai_tro_ly_id` | `BIGINT UNSIGNED NOT NULL` | hoi_thoai_tro_ly |
| `dang_ky_goi_tap_id` | `BIGINT UNSIGNED NOT NULL` | dang_ky_goi_tap |
| `client_request_id` | `CHAR(36) NOT NULL` |  |
| `ngay_han_muc` | `DATE NOT NULL` |  |
| `provider` | `VARCHAR(64) NULL` |  |
| `model` | `VARCHAR(128) NULL` |  |
| `trang_thai` | `VARCHAR(32) NOT NULL` |  |
| `giu_luot_den` | `DATETIME(6) NULL` |  |
| `hoan_thanh_luc` | `DATETIME(6) NULL` |  |
| `ma_loi` | `VARCHAR(64) NULL` |  |
| `do_tre_ms` | `INT UNSIGNED NOT NULL DEFAULT 0` |  |
| `input_tokens` | `INT UNSIGNED NOT NULL DEFAULT 0` |  |
| `output_tokens` | `INT UNSIGNED NOT NULL DEFAULT 0` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (khach_hang_id, client_request_id)`; `INDEX (khach_hang_id, ngay_han_muc, trang_thai, giu_luot_den)`

## tin_nhan_tro_ly

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `hoi_thoai_tro_ly_id` | `BIGINT UNSIGNED NOT NULL` | hoi_thoai_tro_ly |
| `yeu_cau_tro_ly_id` | `BIGINT UNSIGNED NOT NULL` | yeu_cau_tro_ly |
| `vai_tro` | `ENUM('USER','ASSISTANT') NOT NULL` |  |
| `noi_dung` | `TEXT NOT NULL` |  |
| `nguon_da_kiem_tra` | `JSON NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (yeu_cau_tro_ly_id, vai_tro)`; `INDEX (hoi_thoai_tro_ly_id, id)`

## tai_lieu_tu_van

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `tieu_de` | `VARCHAR(255) NOT NULL` |  |
| `loai` | `VARCHAR(32) NOT NULL` |  |
| `noi_dung` | `LONGTEXT NOT NULL` |  |
| `phien_ban` | `INT UNSIGNED NOT NULL DEFAULT 1` |  |
| `nguoi_cap_nhat_id` | `BIGINT UNSIGNED NOT NULL` | tai_khoan |
| `trang_thai` | `VARCHAR(32) NOT NULL` |  |
| `xuat_ban_luc` | `DATETIME(6) NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: PK và FK

## chi_so_co_the

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `khach_hang_id` | `BIGINT UNSIGNED NOT NULL` | ho_so_khach_hang |
| `ngay_ghi` | `DATE NOT NULL` |  |
| `can_nang_kg` | `DECIMAL(6,2) UNSIGNED NULL` |  |
| `chieu_cao_cm` | `DECIMAL(5,2) UNSIGNED NULL` |  |
| `vong_eo_cm` | `DECIMAL(5,2) UNSIGNED NULL` |  |
| `ghi_chu` | `TEXT NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `UNIQUE (khach_hang_id, ngay_ghi)`

## nhat_ky_he_thong

| Cột | Kiểu SQL | Khóa/tham chiếu |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` | PK |
| `tai_khoan_id` | `BIGINT UNSIGNED NULL` | tai_khoan |
| `hanh_dong` | `VARCHAR(128) NOT NULL` |  |
| `loai_tai_nguyen` | `VARCHAR(128) NOT NULL` |  |
| `tai_nguyen_id` | `BIGINT UNSIGNED NULL` |  |
| `metadata_an_toan` | `JSON NULL` |  |
| `created_at` | `DATETIME(6) NULL` |  |
| `updated_at` | `DATETIME(6) NULL` |  |

Ràng buộc: `INDEX (loai_tai_nguyen, tai_nguyen_id, created_at)`

## Bổ sung runtime M04 — migration000032

Bảng thiết kế gốc T09 ở trên giữ nguyên để đối chiếu Draw.io/SQL. Runtime thêm ba cột nullable trên lich_hen_huan_luyen:

| Cột | Kiểu | Ý nghĩa |
| --- | --- | --- |
| nguoi_ghi_nhan_id | BIGINT UNSIGNED NULL | FK RESTRICT tới tai_khoan; PT ghi nhận hoàn thành/vắng mặt |
| ghi_nhan_luc | DATETIME(6) NULL | Thời điểm Backend ghi nhận, UTC |
| ly_do_ghi_nhan | TEXT NULL | Lý do vắng mặt, không dùng lẫn thông tin Admin đóng xử lý |

Không tăng số bảng nghiệp vụ. Hợp đồng runtime tại [LICH_HUAN_LUYEN.md](features/LICH_HUAN_LUYEN.md), [migration000032](../BE/database/migrations/2026_10_02_000032_add_ghi_nhan_to_lich_hen.php). Các cột bổ sung M03/T05/T07 và migration000029–000030 được mô tả trong [hướng dẫn migrations](../BE/database/migrations/README.md); từ điển gốc không thay thế các migrations runtime.
