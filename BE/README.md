# BE — Laravel API

**Trạng thái:** đã bootstrap Laravel **13.34.0**, cài dependencies và lưu composer.lock. Đã kiểm tra trên Windows, PHP **8.4.0**, Composer **2.8.12**. Có pdo_mysql, mbstring, openssl, curl, fileinfo, dom, xml, zip.

Đã có GET /api/v1/health công khai và /up. Đã triển khai Sanctum 4.3.3 cho đăng ký KH, đăng nhập/đăng xuất, đọc tài khoản/hồ sơ, quyền ba vai trò và Admin tạo PT/Admin. TaiKhoan ánh xạ bảng tai_khoan; không tạo users hoặc tài khoản mặc định. CORS hỗ trợ credentials và origin FRONTEND_URL. Xem [hợp đồng tài khoản](../docs/features/TAI_KHOAN.md); quên/đặt lại mật khẩu và sửa hồ sơ chưa triển khai.

Giữ nguyên [SQL thiết kế](database/design/README.md), [28 migrations nghiệp vụ](database/migrations/README.md), [catalog 1.324 bài tập](database/data/README.md) và 2.648 media ở public/media/bai-tap/. Thêm 3 migrations kỹ thuật Laravel cho session/reset password/cache/queue. Đã chạy migrations trên MariaDB 10.4.32 và kiểm tra migrate/rollback/migrate trên database riêng; [bằng chứng](../docs/verification/MARIADB_MIGRATIONS.md). Chưa nhập catalog hoặc kiểm thử trên MySQL thật.

## Chạy Backend

Từ BE/:

```powershell
rtk proxy composer install
rtk proxy php artisan serve --host=localhost --port=8000
```

Hoặc chạy rtk proxy composer run dev. Frontend chạy riêng trong FE/; Backend không cần npm. Endpoint kiểm tra: [localhost:8000/api/v1/health](http://localhost:8000/api/v1/health).

Đã tạo .env local và APP_KEY mà không in key. Trên máy mới, chép .env.example thành .env rồi chạy php artisan key:generate một lần. Giữ APP_KEY hiện có khi ứng dụng đã có dữ liệu mã hóa. Cấu hình mẫu đã ghép các biến Laravel với biến dự án, không chứa credentials thật.

Local bootstrap dùng SESSION_DRIVER=file, CACHE_STORE=file, QUEUE_CONNECTION=sync, BROADCAST_CONNECTION=log để chạy trước khi có database; DB_CONNECTION vẫn là mysql. Điền DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD trong .env bằng tài khoản MySQL của bạn, chọn database mới rồi làm theo [hướng dẫn migrations](database/migrations/README.md). Không nhập SQL thiết kế để tạo trùng bảng.

## Kiểm tra đã chạy

```powershell
rtk proxy composer validate --strict
rtk proxy php artisan test
rtk proxy php artisan route:list --path=api
```

Đạt 23 tests/191 assertions về health/CORS và tài khoản. XacThucTest tự tạo database ngẫu nhiên riêng bằng kết nối MySQL/MariaDB hiện tại, migrate và transaction cho từng ca, chỉ DROP database vừa tạo. Cần quyền CREATE/DROP DATABASE, không refresh database ứng dụng. Đã kiểm thử trên MariaDB 10.4.32; chưa kiểm thử tranh chấp đồng thời trên MySQL thật. Pint kiểm tra code PHP. [Bằng chứng tài khoản](../docs/verification/M01_AUTH.md).

## Cấu trúc và phạm vi tiếp theo

Giữ các thư mục app/Http/Controllers/Api, app/Http/Requests, app/Models, app/Policies, app/Services, app/Events, app/Jobs, routes, database/migrations, database/factories, database/seeders, tests/Feature, tests/Unit. Controller → FormRequest/Policy → Service nếu có transaction → Eloquent → response contract.

Đã có Sanctum cookie SPA. Tạo Admin đầu tiên bằng lệnh tai-khoan:tao-admin theo [hướng dẫn](../docs/features/TAI_KHOAN.md); lệnh hỏi mật khẩu ẩn, không có Admin mặc định. SANCTUM_STATEFUL_DOMAINS=localhost:5173 và FRONTEND_URL=http://localhost:5173 cho local. Không cài migration personal_access_tokens vì SPA chỉ dùng session. Phần tiếp theo: catalog, quên/đặt lại mật khẩu/sửa hồ sơ, rồi gói/lịch và Reverb/payOS/Gemini theo phạm vi. Chưa tích hợp dịch vụ ngoài hoặc seed catalog.

Tham khảo [CODE_STYLE.md](../CODE_STYLE.md), [hợp đồng API](../docs/API_CONVENTIONS.md), [thiết kế database](../docs/DATABASE_DRAFT.md), [quyết định](../docs/DECISIONS.md) và [mẫu Backend](../templates/README.md).
