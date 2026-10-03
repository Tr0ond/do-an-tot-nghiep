# Migrations nghiệp vụ

Ngoài 28 migrations tạo bảng gốc, có migrations bổ sung từ 000029 đến 000039 cho UUID, dữ liệu nghiệp vụ M03/M05 và chat/thông báo. Không sửa SQL/Draw.io gốc hoặc migration đã chạy. Runtime hiện có 42 migrations (28 tạo bảng nghiệp vụ + 3 framework + 11 bổ sung); script đối chiếu schema chỉ kiểm tra 28 migration `create_…_table`. Migration000037 giữ bản PT cũ, thêm nguồn và liên kết nullable cho KH tự tạo; unique theo nguồn tại thời điểm đó đã được000039 thay bằng unique chung theo KH. Rollback000037 từ chối khi có bản tự tạo. Migration000038 thêm `khach_an_luc` nullable, giữ bản cũ chưa ẩn; down từ chối khi còn bản đã ẩn. Migration000039 giữ bản áp dụng gần nhất, lưu trữ bản trùng và đổi unique về KH; chạy trong maintenance, đã migrate local rồi mở lại ứng dụng. Down chỉ đổi index, không tái kích hoạt giáo án. [Hợp đồng gói](../../../docs/features/GOI_TAP.md), [giáo án mẫu](../../../docs/features/GIAO_AN_MAU.md), [giáo án cá nhân](../../../docs/features/KE_HOACH_TAP.md).

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

**BE đã khởi tạo Laravel 13 và cài dependencies**. Ngày 01/10/2026 đã chạy đủ 28 migrations nghiệp vụ trên database `duantotnghiep` dùng MariaDB 10.4.32. Đạt migrate → rollback → migrate ở database thử riêng, kiểm tra metadata và ràng buộc dữ liệu; xem [bằng chứng MariaDB](../../../docs/verification/MARIADB_MIGRATIONS.md). Đã kiểm thử transaction/tranh chấp M03 bằng hai process trên MariaDB; chưa chạy trên MySQL thật.

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

## Migration bổ sung M03

2026_10_02_000031_add_du_lieu_m03.php thêm URL checkout và khóa một đơn chờ của T05, UUID và mã phiên bản cũ của T07. Giữ record cũ, không thêm bảng hoặc sửa migration gốc. Nếu database trước đó có nhiều đơn CHO_THANH_TOAN của cùng KH, UNIQUE sẽ từ chối migrate để tránh chọn/xóa đơn tùy tiện; cần kiểm tra/xử lý dữ liệu đó trước. Runtime local ngày 02/10/2026 đã migrate thành công. down chỉ gỡ cột/index bổ sung; không chạy rollback trên database ứng dụng để thử. [Chi tiết và kiểm chứng](../../../docs/verification/M03_MUA_GOI.md).

## Migration bổ sung M04

2026_10_02_000032_add_ghi_nhan_to_lich_hen.php thêm nguoi_ghi_nhan_id (FK RESTRICT), ghi_nhan_luc DATETIME(6), ly_do_ghi_nhan nullable trên T09, giữ dữ liệu cũ. Đã migrate trên database ứng dụng MariaDB10.4.32 ngày02/10/2026; không rollback/fresh/seed lại database thật. LichHenTest migrate trên DB riêng và kiểm tra transaction/hai process đặt lịch/trừ buổi. [Hợp đồng](../../../docs/features/LICH_HUAN_LUYEN.md), [kiểm chứng](../../../docs/verification/M04_LICH_HUAN_LUYEN.md).

## Migration ảnh chat M07 — 000035

`2026_10_03_000035_add_anh_to_tin_nhan_table.php` thêm JSON nullable `tin_nhan.anh`, tin cũ vẫn null. Đã chạy trên database ứng dụng ngày03/10/2026, không reset/seed lại. Chạy `php artisan migrate` khi clone/pull; `down` từ chối nếu có ảnh để tránh mất metadata lịch sử. Tệp ở disk local riêng tư, không xóa khi rollback. [Hợp đồng ảnh](../../../docs/features/REALTIME_CHAT.md), [kiểm chứng](../../../docs/verification/CHAT_IMAGES.md).

## Runtime M05 — 03/10/2026

Migration 000036 bổ sung số ngày tập và UUID/hash tạo cho T14, mức tạ nullable cho T15. Giáo án cũ giữ dữ liệu, số ngày backfill từ các bài; không reset hoặc seed lại. down từ chối khi có UUID hoặc mức tạ mới để tránh mất thông tin. Đã migrate database ứng dụng và thử giữ dữ liệu cũ/up/down trên database MariaDB riêng. [Kiểm chứng](../../../docs/verification/M05_KE_HOACH_TAP.md).

## Runtime M06 — 000040

`2026_10_03_000040_bo_sung_nhat_ky_tap.php` thêm UUID/hash/nguoi_tao_id và index ngày/KH vào lich_tap, UUID/hash vào ghi_chu_huan_luyen. Nullable giữ record cũ. Không tạo lại bảng gốc T16–T20; down giữ index FK và từ chối khi có UUID mới. Đã migrate local trên MariaDB10.4.32; test up/down/bảo toàn dữ liệu và hai process đạt. Máy clone/pull chạy `php artisan migrate`; không dùng fresh/rollback trên dữ liệu ứng dụng. [Kiểm chứng](../../../docs/verification/M06_NHAT_KY_TAP.md).
