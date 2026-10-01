# BE — Laravel API

**Trạng thái:** đã bootstrap Laravel **13.34.0**, cài dependencies và lưu composer.lock. Đã kiểm tra trên Windows, PHP **8.4.0**, Composer **2.8.12**. Có pdo_mysql, mbstring, openssl, curl, fileinfo, dom, xml, zip.

Đã có GET /api/v1/health công khai và /up. Đã triển khai Sanctum 4.3.3 cho đăng ký KH, đăng nhập/đăng xuất, đọc tài khoản/hồ sơ, quyền ba vai trò và Admin tạo PT/Admin. TaiKhoan ánh xạ bảng tai_khoan; không tạo users. Có [seeder 3 tài khoản demo](database/seeders/README.md) cho local/testing. CORS hỗ trợ credentials và origin FRONTEND_URL. Xem [hợp đồng tài khoản](../docs/features/TAI_KHOAN.md); quên/đặt lại mật khẩu và sửa hồ sơ chưa triển khai.

Giữ nguyên [SQL thiết kế](database/design/README.md), [28 migrations nghiệp vụ](database/migrations/README.md), [catalog 1.324 bài tập](database/data/README.md) và 2.648 media ở public/media/bai-tap/. Thêm 3 migrations kỹ thuật Laravel cho session/reset password/cache/queue. Đã chạy migrations trên MariaDB 10.4.32 và kiểm tra migrate/rollback/migrate trên database riêng; [bằng chứng](../docs/verification/MARIADB_MIGRATIONS.md). Đã nhập catalog và có API bài tập công khai; chưa kiểm thử trên MySQL thật.

## Chạy Backend

Từ BE/:

```powershell
rtk proxy composer install
rtk proxy php artisan serve --host=localhost --port=8000
```

Hoặc chạy rtk proxy composer run dev. Frontend chạy riêng trong FE/; Backend không cần npm. Endpoint kiểm tra: [localhost:8000/api/v1/health](http://localhost:8000/api/v1/health).

Đã tạo .env local và APP_KEY mà không in key. Trên máy mới, chép .env.example thành .env rồi chạy php artisan key:generate một lần. Giữ APP_KEY hiện có khi ứng dụng đã có dữ liệu mã hóa. Cấu hình mẫu đã ghép các biến Laravel với biến dự án, không chứa credentials thật.

Local bootstrap dùng SESSION_DRIVER=file, CACHE_STORE=file, QUEUE_CONNECTION=sync, BROADCAST_CONNECTION=log để chạy trước khi có database; DB_CONNECTION vẫn là mysql. Điền DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD trong .env bằng tài khoản MySQL của bạn, chọn database mới rồi làm theo [hướng dẫn migrations](database/migrations/README.md). Không nhập SQL thiết kế để tạo trùng bảng.

### Lỗi CSRF cookie 500 sau khi clone

Nếu request `GET /sanctum/csrf-cookie` lỗi 500, kiểm tra lỗi mới nhất trong `storage/logs/laravel.log`. Bản đóng gói trước đây bỏ qua cả thư mục `storage/framework/sessions`, khiến bản clone thiếu nơi ghi session khi `SESSION_DRIVER=file`. Quy tắc Git đã được sửa để giữ `.gitignore` trong thư mục này và tiếp tục bỏ qua mọi file session.

Với bản clone cũ, từ `BE/` chạy PowerShell:

```powershell
New-Item -ItemType Directory -Force -Path storage/framework/sessions | Out-Null
php artisan config:clear
```

Khởi động lại Backend rồi thử đăng ký. Request CSRF cookie thành công trả HTTP 204, không có nội dung. Nếu vẫn lỗi 500, đối chiếu log: `No application encryption key has been specified` cần tạo APP_KEY bằng `php artisan key:generate` trên máy mới khi chưa có key; lỗi ghi file cần kiểm tra đường dẫn/quyền ghi. Không gửi `.env`, APP_KEY hoặc cookie để chẩn đoán.

## Danh mục bài tập

Từ BE chạy `rtk proxy php artisan db:seed --class=BaiTapSeeder` sau migrations để nhập 19 nhóm cơ/1.324 bài. Chạy lại giữ nội dung/trạng thái đã sửa. `DatabaseSeeder` gọi seeder tài khoản demo và bài tập; muốn chỉ nhập catalog thì chọn class riêng.

API công khai: GET /api/v1/bai-tap, /bai-tap/bo-loc, /bai-tap/{id}. Danh sách mặc định 12/trang, tối đa 48; query tu_khoa/nhom_co_id/dung_cu_nguon/page/per_page. Chỉ bài/nhóm hoạt động được hiển thị. [Hợp đồng bài tập](../docs/features/BAI_TAP.md). Từ gốc chạy `rtk proxy php scripts/kiemTraCatalogDatabase.php` để đối chiếu DB với JSON.

## Kiểm tra đã chạy

```powershell
rtk proxy composer validate --strict
rtk proxy php artisan test
rtk proxy php artisan route:list --path=api
```

Đạt 36 tests/3.040 assertions về health/CORS, tài khoản, seeder và API bài tập. XacThucTest/BaiTapTest tự tạo database ngẫu nhiên riêng bằng kết nối MySQL/MariaDB hiện tại, migrate và transaction cho từng ca, chỉ DROP database vừa tạo. Cần quyền CREATE/DROP DATABASE, không refresh database ứng dụng. Đã kiểm thử trên MariaDB 10.4.32; chưa kiểm thử tranh chấp đồng thời trên MySQL thật. Pint kiểm tra code PHP. [Bằng chứng tài khoản](../docs/verification/M01_AUTH.md), [kiểm tra seeder](database/seeders/README.md).

## Cấu trúc và phạm vi tiếp theo

Giữ các thư mục app/Http/Controllers/Api, app/Http/Requests, app/Models, app/Policies, app/Services, app/Events, app/Jobs, routes, database/migrations, database/factories, database/seeders, tests/Feature, tests/Unit. Controller → FormRequest/Policy → Service nếu có transaction → Eloquent → response contract.

Đã có Sanctum cookie SPA. Tạo Admin đầu tiên bằng lệnh tai-khoan:tao-admin theo [hướng dẫn](../docs/features/TAI_KHOAN.md); lệnh hỏi mật khẩu ẩn. Môi trường local có thể chạy seeder demo thay thế. SANCTUM_STATEFUL_DOMAINS=localhost:5173 và FRONTEND_URL=http://localhost:5173 cho local. Không cài migration personal_access_tokens vì SPA chỉ dùng session. Phần tiếp theo: quản trị danh mục/gói/giáo án mẫu, quên/đặt lại mật khẩu/sửa hồ sơ, rồi gói/lịch và Reverb/payOS/Gemini theo phạm vi. Chưa tích hợp dịch vụ ngoài. Catalog đã seed; xem [kiểm chứng bài tập](../docs/verification/M02_BAI_TAP.md).

Tham khảo [CODE_STYLE.md](../CODE_STYLE.md), [hợp đồng API](../docs/API_CONVENTIONS.md), [thiết kế database](../docs/DATABASE_DRAFT.md), [quyết định](../docs/DECISIONS.md) và [mẫu Backend](../templates/README.md).
