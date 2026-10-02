# BE — Laravel API

**Trạng thái:** đã bootstrap Laravel **13.34.0**, cài dependencies và lưu composer.lock. Đã kiểm tra trên Windows, PHP **8.4.0**, Composer **2.8.12**. Có pdo_mysql, mbstring, openssl, curl, fileinfo, dom, xml, zip.

Đã có GET /api/v1/health công khai và /up. Đã triển khai Sanctum 4.3.3 cho đăng ký KH, đăng nhập/đăng xuất, đọc/sửa hồ sơ, quyền ba vai trò và Admin tạo PT/Admin, khóa/mở khóa tài khoản. Có quên/đặt lại mật khẩu qua email. TaiKhoan ánh xạ bảng tai_khoan; không tạo users. Có [seeder 3 tài khoản demo](database/seeders/README.md) cho local/testing. CORS hỗ trợ credentials và origin FRONTEND_URL. Xem [hợp đồng tài khoản](../docs/features/TAI_KHOAN.md) và [kiểm chứng M01 bổ sung](../docs/verification/M01_HO_SO_KHOI_PHUC.md).

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

Đã có Admin quản lý nhóm cơ qua GET/POST `/api/v1/admin/nhom-co`, GET/PUT `/{id}`, PATCH `/{id}/trang-thai`. Mã unique/bất biến, phiên bản micro giây, CSRF và quyền Admin hoạt động; ngừng/khôi phục không đổi bài tập hoặc snapshot. Không thêm migration hoặc seed lại; [hợp đồng](../docs/features/NHOM_CO.md), [kiểm chứng](../docs/verification/M02_NHOM_CO.md).

Từ BE chạy `rtk proxy php artisan db:seed --class=BaiTapSeeder` sau migrations để nhập 19 nhóm cơ/1.324 bài. Chạy lại giữ nội dung/trạng thái đã sửa. `DatabaseSeeder` gọi lần lượt seeder tài khoản demo, bài tập và giáo án mẫu; muốn chỉ nhập catalog thì chọn class riêng.

API công khai: GET /api/v1/bai-tap, /bai-tap/bo-loc, /bai-tap/{id}. Danh sách mặc định 12/trang, tối đa 48; query tu_khoa/nhom_co_id/dung_cu_nguon/page/per_page. Chỉ bài/nhóm hoạt động được hiển thị. [Hợp đồng bài tập](../docs/features/BAI_TAP.md). Từ gốc chạy `rtk proxy php scripts/kiemTraCatalogDatabase.php` để đối chiếu DB với JSON.

Admin hoạt động có thể GET danh sách/bộ lọc/chi tiết, POST thêm, PUT sửa và PATCH trạng thái tại `/api/v1/admin/bai-tap`. Endpoint ghi dùng CSRF; sửa/trạng thái yêu cầu `updated_at` từ response để phát hiện bản cũ (409). Chỉ sửa nội dung tiếng Việt cho bài nhập, giữ nguồn/media/ngôn ngữ khác. Bài mới nguồn admin có mã riêng gồm 4 chữ/số, chưa có ảnh/GIF. Không có DELETE. Không cần migration hoặc seed lại cho phần quản trị này. Xem [hợp đồng](../docs/features/BAI_TAP.md).

## Danh mục gói tập

Chạy `rtk proxy php artisan migrate` khi cập nhật code để thêm `ma_yeu_cau_tao`/unique index chống tạo trùng T04. Migration bổ sung giữ dữ liệu cũ, không seed giá thương mại. Admin nhập gói rồi mở bán trên giao diện.

GET công khai `/api/v1/goi-tap`, `/goi-tap/{id}` chỉ trả gói hợp lệ đang bán. ADMIN hoạt động có GET/POST danh sách, GET/PUT chi tiết, PATCH `/{id}/trang-thai` tại `/api/v1/admin/goi-tap`; ghi yêu cầu CSRF. POST có UUID `client_request_id`, retry cùng nội dung trả gói cũ, khác nội dung 409. PUT/PATCH dùng `updated_at` micro giây từ server. Không DELETE; không đổi snapshot T05 hoặc cấp gói khi sửa catalog. [Hợp đồng gói tập](../docs/features/GOI_TAP.md), [kiểm chứng](../docs/verification/M02_GOI_TAP.md). Thanh toán/đặt mua/kích hoạt chưa triển khai.

## Kiểm tra toàn bộ runtime

```powershell
rtk proxy composer validate --strict
rtk proxy php artisan test
rtk proxy php artisan route:list --path=api
```

Đạt 123 tests/4.505 assertions về health/CORS, tài khoản, seeder, bài tập, gói tập và giáo án mẫu. XacThucTest/HoSoTaiKhoanTest/TongQuanTest/BaiTapTest/GoiTapTest/GiaoAnMauTest/NhomCoTest tự tạo database ngẫu nhiên riêng bằng kết nối MySQL/MariaDB hiện tại, migrate và transaction cho từng ca, chỉ DROP database vừa tạo. Cần quyền CREATE/DROP DATABASE, không refresh database ứng dụng. Đã kiểm thử trên MariaDB 10.4.32; đã kiểm thử hai process tạo đơn/cấp gói M03; chưa kiểm thử MySQL thật. Pint kiểm tra code PHP. [Bằng chứng tài khoản](../docs/verification/M01_AUTH.md), [quản trị bài tập](../docs/verification/M02_ADMIN_BAI_TAP.md), [gói tập](../docs/verification/M02_GOI_TAP.md), [kiểm tra seeder](database/seeders/README.md).

## Cấu trúc và phạm vi tiếp theo

Giữ các thư mục app/Http/Controllers/Api, app/Http/Requests, app/Models, app/Policies, app/Services, app/Events, app/Jobs, routes, database/migrations, database/factories, database/seeders, tests/Feature, tests/Unit. Controller → FormRequest/Policy → Service nếu có transaction → Eloquent → response contract.

Đã có Sanctum cookie SPA. Tạo Admin đầu tiên bằng lệnh tai-khoan:tao-admin theo [hướng dẫn](../docs/features/TAI_KHOAN.md); lệnh hỏi mật khẩu ẩn. Môi trường local có thể chạy seeder demo thay thế. SANCTUM_STATEFUL_DOMAINS=localhost:5173 và FRONTEND_URL=http://localhost:5173 cho local. Không cài migration personal_access_tokens vì SPA chỉ dùng session. Đã có quản trị bài tập/gói/giáo án và PT đọc thư viện; đã bổ sung sửa hồ sơ/quên/đặt lại mật khẩu/khóa tài khoản qua UI. Đã có đặt mua/payOS/phân công PT M03. Đã có lịch M04; phần tiếp theo là Reverb/chat realtime M07 rồi giáo án cá nhân M05 theo phạm vi. SMTP đã cấu hình local và xác minh xác thực STARTTLS; chưa kiểm tra thư đến hộp thư thật, payOS đã tích hợp và tạo/đọc link thật chưa thanh toán; Gemini chưa tích hợp. Catalog bài tập đã seed; giá gói do Admin nhập.

Tham khảo [CODE_STYLE.md](../CODE_STYLE.md), [hợp đồng API](../docs/API_CONVENTIONS.md), [thiết kế database](../docs/DATABASE_DRAFT.md), [quyết định](../docs/DECISIONS.md) và [mẫu Backend](../templates/README.md).

## Giáo án mẫu

Chạy `rtk proxy php artisan migrate` sau khi cập nhật để thêm UUID nullable/unique T12 (migration 000030), giữ giáo án cũ. Admin có GET/POST `/api/v1/admin/giao-an-mau`, GET/PUT `/{id}`, PATCH `/{id}/trang-thai`; PT có GET `/api/v1/pt/giao-an-mau` và `/{id}` chỉ đọc đã duyệt. FormRequest/Service kiểm tra ngày/thứ tự, bài/nhóm hoạt động, UUID và phiên bản; ghi parent/child atomic. Duyệt do server đặt actor/thời gian; sửa nội dung thu hồi duyệt. Không DELETE hoặc sửa kế hoạch/snapshot khách hàng. [Hợp đồng](../docs/features/GIAO_AN_MAU.md), [kiểm chứng](../docs/verification/M02_GIAO_AN_MAU.md). Không cần seed lại catalog.

Để có dữ liệu trình diễn, chạy `rtk proxy php artisan db:seed --class=GiaoAnMauSeeder` từ BE sau khi có Admin demo và catalog: tạo 5 giáo án đã duyệt/60 dòng bài tập. Chạy lại giữ các giáo án đã có, kể cả bản tự sửa/ngừng; chỉ dùng local/testing, không gán cho khách hàng. Máy mới chạy `db:seed` để nạp phụ thuộc theo thứ tự. Xem [hướng dẫn seeders](database/seeders/README.md), [kiểm chứng](../docs/verification/M02_GIAO_AN_MAU_SEEDER.md).

## Hồ sơ, khóa tài khoản và email khôi phục
PUT /api/v1/me/ho-so sửa hồ sơ của người đăng nhập; PATCH /api/v1/admin/tai-khoan/{id}/trang-thai khóa/mở khóa do Admin thực hiện. Hai API nhận updated_at để chống ghi đè bản cũ; đều yêu cầu CSRF. Khóa tài khoản thu hồi phiên trên mọi session driver, mở khóa không phục hồi phiên cũ. Admin không tự khóa chính mình.

POST /quen-mat-khau và POST /dat-lai-mat-khau là route session ngoài /api/v1, có CSRF/rate limit/CORS. Token lưu hash trong DB, hết hạn 60 phút, dùng một lần; đặt lại thành công thu hồi phiên và không tự đăng nhập. Không thêm migration hoặc seed lại cho phần này.

Trên máy mới, cấu hình SMTP trong BE/.env: MAIL_MAILER=smtp, MAIL_SCHEME=smtp, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS và MAIL_FROM_NAME; MAIL_REQUIRE_TLS=true mặc định. Gmail dùng port 587 và mật khẩu ứng dụng. FRONTEND_URL phải là URL Vue để tạo liên kết đặt lại. Chạy php artisan config:clear sau khi thay đổi; khởi động lại tiến trình Backend dài hạn nếu có. .env là file local bị Git bỏ qua, không đưa thông tin SMTP thật vào .env.example.

Đã xác thực SMTP Gmail bằng STARTTLS tại local, chưa gửi thư thật. Test dùng Notification::fake; trình duyệt chỉ yêu cầu khôi phục cho email không tồn tại. Xem [bằng chứng và giới hạn](../docs/verification/M01_HO_SO_KHOI_PHUC.md).

## API tổng quan theo vai trò
GET /api/v1/khach-hang/tong-quan, /pt/tong-quan, /admin/tong-quan có auth/tài khoản hoạt động/vai trò tương ứng. KH/PT chỉ nhận mức đầy đủ hồ sơ chính mình và catalog công khai; PT thêm số giáo án đã duyệt. Admin nhận aggregate tài khoản và danh mục toàn DB, không trả thông tin cá nhân người khác. GET chỉ đọc, không cần migration/seed mới. [Hợp đồng](../docs/features/TONG_QUAN.md), [kiểm chứng](../docs/verification/DASHBOARD.md).

## Mua gói, payOS và phân công PT M03

Chạy `rtk proxy php artisan migrate` để thêm migration 000031; không cần seed lại hoặc xóa dữ liệu. Khóa local đã lưu trong BE/.env bị Git bỏ qua. Máy khác tự cấu hình PAYOS_CLIENT_ID, PAYOS_API_KEY, PAYOS_CHECKSUM_KEY và PAYOS_API_URL=https://api-merchant.payos.vn; chạy `rtk proxy php artisan config:clear` sau khi sửa. Không đưa khóa vào Vue hoặc Git.

PHP dùng CA công khai tại resources/certs/cacert.pem để xác minh HTTPS; có thể cấu hình PAYOS_CA_BUNDLE tới bộ CA của môi trường triển khai. Xem [nguồn và cách cập nhật CA](resources/certs/README.md). Không tắt xác minh TLS.

Đăng ký webhook của ứng dụng tại `https://<host-backend>/api/v1/payos/webhook` khi có Backend HTTPS công khai. Route kiểm tra chữ ký, rồi đọc dữ liệu có chữ ký từ payOS trước ghi nhận khoản thu/kích hoạt. Ngày02/10/2026 đã nhận POST xác minh webhook qua ngrok HTTP200. Đây là bằng chứng kết nối; chưa nghiệm thu khoản chuyển tiền thật. Local KH dùng nút **Kiểm tra thanh toán** để đồng bộ cùng luồng xác minh; query return/cancel không cấp gói. FRONTEND_URL phải trỏ Vue để payOS quay về đúng trang đơn; triển khai cấu hình hostname/CORS/Sanctum tương ứng.

API KH chỉ cho đơn của chính mình, Admin xem đơn/ghi kết quả hoàn tiền thủ công/phân công PT. Đối soát chỉ ghi kết quả sau khi đã hoàn tiền ngoài hệ thống. Test MuaGoiTest dùng database riêng, có worker hai process để kiểm tra tranh chấp; không dùng database ứng dụng. [Hợp đồng và endpoint](../docs/features/MUA_GOI_THANH_TOAN.md), [kiểm chứng và giới hạn](../docs/verification/M03_MUA_GOI.md).

## Lịch huấn luyện M04

Đã có giờ rảnh PT, đặt/hủy lịch KH, PT xác nhận/từ chối/hoàn thành/vắng mặt, Admin xem/đóng buổi quá hạn. Chống chồng giờ/UUID/quota bằng khóa hàng + UNIQUE; chỉ hoàn thành mới trừ một buổi gói gốc cùng transaction/audit. Migration000032 đã chạy local, không reset dữ liệu.

Sau khi pull, chạy php artisan migrate. Chạy php artisan schedule:work ở terminal BE riêng để dọn deadline mỗi phút; lệnh một lần php artisan lich-hen:don-qua-han. API luôn kiểm tra deadline ngay cả khi scheduler chưa chạy. [Hợp đồng và quyền](../docs/features/LICH_HUAN_LUYEN.md), [kiểm thử và giao diện](../docs/verification/M04_LICH_HUAN_LUYEN.md).
