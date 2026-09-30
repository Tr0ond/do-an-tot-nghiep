# Migrations nghiệp vụ

Đã tạo **28 migrations Laravel / 303 cột / 52 khóa ngoại**, đối chiếu cả [database.drawio ở thư mục gốc](../../../database.drawio) và [bản trong docs](../../../docs/diagrams/database.drawio). Hai bản khớp tên bảng, tên cột và quan hệ. Kiểu dữ liệu, nullable, default, biểu thức generated và CHECK lấy từ [schema.json](../design/schema.json) / [SQL thiết kế](../design/schema.mysql.sql), vì bản vẽ tổng thể chỉ hiển thị tên cột và PK/FK.

Mỗi bảng có một migration dạng anonymous class, có `up()` và `down()`. Tên file `2026_10_01_000001_…` đến `2026_10_01_000028_…` xác định thứ tự tạo; Laravel rollback theo thứ tự ngược. Khóa tự tham chiếu `ke_hoach_tap.thay_the_ke_hoach_id` được tạo cùng bảng. Cách viết theo [tài liệu migrations Laravel](https://laravel.com/framework/docs/13.x/migrations).

## Các ràng buộc đã giữ

- MySQL **8.0.16+** hoặc MariaDB **10.4+**, InnoDB, `utf8mb4_unicode_ci`. Migration đầu kiểm tra driver/phiên bản trước tạo bảng, kể cả `--pretend`; MariaDB phải bật `check_constraint_checks`. Không hỗ trợ SQLite. Đã chạy thực tế trên MariaDB 10.4.32; các phiên bản khác chưa được kiểm thử trong môi trường này.
- ID/FK `BIGINT UNSIGNED`; tiền VND là số nguyên. FK ghi rõ bảng đích: KH/PT trỏ hồ sơ, người thao tác trỏ tài khoản. Cả 52 FK dùng `RESTRICT` khi xóa/cập nhật, giữ lịch sử.
- 26 UNIQUE, 8 INDEX khai báo và 9 CHECK. Tên index/FK/CHECK ngắn, tường minh, không vượt giới hạn 64 ký tự của MySQL. Các index do MySQL tự bổ sung để hỗ trợ FK được tính riêng.
- 4 cột generated nullable, lưu `STORED` và có UNIQUE: gói đang dùng, phân công đang mở, slot đang giữ và kế hoạch đang áp dụng. Hủy lịch/đóng trạng thái giải phóng khóa generated, giữ record lịch sử.
- CHECK dùng `DB::statement()` sau `Schema::create()` để giữ biểu thức MySQL của thiết kế. Ràng buộc gồm quyền lợi gói, số buổi còn lại, tiền hoàn, khoảng phân công, slot/lịch PT 60 phút và thứ tự hiệp.
- Timestamp giữ `DATETIME(6)` nullable đúng SQL; không thay bằng `timestamps()` loại TIMESTAMP. Ứng dụng cần ghi UTC; các ngày nghiệp vụ vẫn là DATE theo giờ Việt Nam.

Các migrations chỉ tạo cấu trúc. Chúng không seed catalog, thêm bảng framework hoặc triển khai validation/quyền/transaction của use case. Giới hạn và quy tắc cần service nằm tại [README thiết kế](../design/README.md).

## Cách kiểm tra và chạy

**BE đã khởi tạo Laravel 13 và cài dependencies**. Ngày 01/10/2026 đã chạy đủ 28 migrations nghiệp vụ trên database `duantotnghiep` dùng MariaDB 10.4.32. Đạt migrate → rollback → migrate ở database thử riêng, kiểm tra metadata và ràng buộc dữ liệu; xem [bằng chứng MariaDB](../../../docs/verification/MARIADB_MIGRATIONS.md). Chưa kiểm thử transaction/tranh chấp của use case hoặc chạy trên MySQL thật.

Từ thư mục gốc, kiểm tra cấu trúc mà không cần Laravel:

```powershell
rtk proxy node scripts/kiemTraMigrations.mjs
rtk proxy node scripts/kiemTraDuLieu.mjs
```

Script migrations đối chiếu kiểu/độ dài/nullable/default, generated, UNIQUE/INDEX/CHECK/FK, thứ tự tạo/xóa theo quan hệ, tên ràng buộc và UTF-8/LF. Đây là kiểm tra tĩnh, không chứng minh database đã chạy thành công.

Cấu hình kết nối trong `BE/.env` tới **database MySQL/MariaDB mới, chưa nhập `schema.mysql.sql`**, rồi chạy từ `BE/`:

```powershell
rtk proxy php artisan migrate --pretend
rtk proxy php artisan migrate
rtk proxy php artisan migrate:status
```

Không chạy SQL thiết kế để tạo lại cùng 28 bảng trước/sau migrations. Bootstrap đã thêm 3 migrations kỹ thuật cho password_reset_tokens, sessions, cache, cache_locks, jobs, job_batches và failed_jobs; không thêm users. Script đối chiếu chỉ kiểm tra 28 migrations nghiệp vụ. Cần kiểm chứng migrate, rollback và migrate lại trên database kiểm thử riêng trước dùng dữ liệu thật; rollback xóa các bảng trong batch được chọn.

Kiểm thử database thật từ thư mục gốc:

```powershell
rtk proxy php scripts/kiemTraMigrationsDatabase.php
```

Script dùng kết nối BE hiện tại, yêu cầu quyền CREATE/DROP DATABASE. Tự tạo database ngẫu nhiên riêng, kiểm tra ràng buộc bằng dữ liệu giả và chỉ xóa database nó vừa tạo; không rollback database ứng dụng. Không in thông tin đăng nhập.
