# Kiểm chứng migrations trên MariaDB — 01/10/2026

## Thay đổi và nguyên nhân

Máy chủ hiện tại là MariaDB 10.4.32, kết nối Laravel `mysql`. Guard ban đầu chặn mọi MariaDB nên migration `tai_khoan` dừng trước khi tạo bảng. Sửa guard hỗ trợ MySQL 8.0.16+ hoặc MariaDB 10.4+, nhận diện tiền tố tương thích `5.5.5-`, kiểm tra CHECK của MariaDB đang bật. Đọc phiên bản qua PDO để `migrate --pretend` cũng chạy được. Giữ nguyên 28 bảng và toàn bộ ràng buộc thiết kế.

MariaDB hỗ trợ [CHECK](https://mariadb.com/docs/server/reference/sql-statements/data-definition/constraint) và [generated STORED/index](https://mariadb.com/docs/server/reference/sql-statements/data-definition/create/generated-columns). Khả năng tương thích của bộ migrations được kiểm tra trực tiếp bằng các bước dưới đây.

## Kiểm thử thực tế

Môi trường: Windows, PHP 8.4.0, Laravel 13.34.0, MariaDB 10.4.32; InnoDB, utf8mb4_unicode_ci, strict SQL mode và `check_constraint_checks=1`.

- `node scripts/kiemTraMigrations.mjs`: PASS, đối chiếu 28 migrations/303 cột/52 FK/26 UNIQUE/8 INDEX/9 CHECK/4 generated với thiết kế.
- `php artisan migrate --pretend`: PASS trên kết nối hiện tại.
- `php scripts/kiemTraMigrationsDatabase.php`: PASS ở database ngẫu nhiên riêng. Chạy đủ 31 migrations (28 nghiệp vụ + 3 kỹ thuật), rollback cả batch và migrate lại. Kiểm tra đủ bảng/cột, 52 FK RESTRICT, 26 UNIQUE, 9 CHECK nghiệp vụ và 4 generated STORED qua information_schema.
- Kiểm tra MariaDB tắt CHECK: guard từ chối trước khi tạo `tai_khoan`.
- 8 CHECK được thử bằng dữ liệu sai: 3 điều kiện gói, số buổi còn lại, tiền hoàn, thời gian phân công, thời lượng khung giờ và lịch hẹn. Tất cả từ chối đúng constraint.
- FK từ chối hồ sơ không có tài khoản và từ chối xóa tài khoản còn hồ sơ.
- Cả 4 generated UNIQUE chặn record đang mở trùng nhau; đóng gói/phân công/lịch/kế hoạch giải phóng khóa, cho tạo record mới và giữ lịch sử cũ.
- `php artisan migrate` trên `duantotnghiep`: đủ 28 migrations nghiệp vụ DONE, 3 migrations kỹ thuật đã chạy trước đó.

Script thử chỉ tạo dữ liệu giả ở database riêng; tên ngẫu nhiên do script sinh, không nhận tên database bên ngoài, không dùng CREATE IF NOT EXISTS, chỉ DROP database vừa tạo. Lần thử đầu phát hiện cấu hình kết nối thử kế thừa tên `mysql`; đã sửa tên kết nối riêng và thêm xác minh cách ly trước khi chạy migrations. Lần đầu dừng tại CREATE bảng kỹ thuật đã tồn tại, không thay đổi bảng ứng dụng.

## Chạy lại và giới hạn

Từ thư mục gốc, dùng `rtk proxy php scripts/kiemTraMigrationsDatabase.php`. Script lấy kết nối hiện tại của BE, cần quyền CREATE/DROP DATABASE và không in credentials. Kiểm thử CHECK cuối `hiep_tap.thu_tu > 0` hiện mới đối chiếu metadata/cấu trúc, chưa thử dữ liệu sai.

Chưa kiểm thử trên MySQL thật, các phiên bản MariaDB khác hoặc transaction/tranh chấp nghiệp vụ. Chưa seed catalog và chưa triển khai use case. Không dùng kết quả này để kết luận kiểm thử cạnh tranh theo PROJECT_RULES đã hoàn tất.
