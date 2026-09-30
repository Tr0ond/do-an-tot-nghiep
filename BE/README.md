# BE — Laravel API

**Trạng thái:** đã bootstrap Laravel **13.34.0**, cài dependencies và lưu composer.lock. Đã kiểm tra trên Windows, PHP **8.4.0**, Composer **2.8.12**. Có pdo_mysql, mbstring, openssl, curl, fileinfo, dom, xml, zip.

Đã có GET /api/v1/health công khai, trả JSON status/message/data và không truy vấn database; /up là health check framework. Trang gốc chuyển tới Frontend. CORS hỗ trợ credentials và chỉ cho origin ở FRONTEND_URL. TaiKhoan ánh xạ bảng tai_khoan; không tạo bảng users/tài khoản demo. Đây chưa phải module xác thực hoàn chỉnh.

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

Đạt 3 tests/14 assertions về JSON health và CORS đúng/sai origin. Tests này không truy cập DB; cấu hình test hướng tới MySQL/database riêng fitness_tot_nghiep_test. Chưa kiểm thử transaction hoặc tranh chấp trên MySQL. Pint đã kiểm tra các file PHP bootstrap đã sửa. [Bằng chứng và giới hạn](../docs/verification/BOOTSTRAP.md).

## Cấu trúc và phạm vi tiếp theo

Giữ các thư mục app/Http/Controllers/Api, app/Http/Requests, app/Models, app/Policies, app/Services, app/Events, app/Jobs, routes, database/migrations, database/factories, database/seeders, tests/Feature, tests/Unit. Controller → FormRequest/Policy → Service nếu có transaction → Eloquent → response contract.

Baseline tiếp theo: Sanctum cookie SPA, MySQL, Reverb/database queue, payOS và Gemini theo các quyết định đã chốt. Chưa cài Sanctum/Reverb hoặc tích hợp dịch vụ ngoài; biến AI/Reverb chỉ chuẩn bị cho module sau. Không lấy credentials từ các dự án cũ. Chưa có endpoint nghiệp vụ hoặc seeder chạy để tạo dữ liệu.

Tham khảo [CODE_STYLE.md](../CODE_STYLE.md), [hợp đồng API](../docs/API_CONVENTIONS.md), [thiết kế database](../docs/DATABASE_DRAFT.md), [quyết định](../docs/DECISIONS.md) và [mẫu Backend](../templates/README.md).
