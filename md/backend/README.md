# BE — Laravel API

MB1 mobile: `POST /api/v1/mobile/dang-nhap|dang-xuat` cấp/thu hồi bearer KH/PT hạn cố định 30 ngày; `/me` và `/me/ho-so` dùng chung. MB5 thêm đăng ký/quên/đặt lại mật khẩu native bằng service/broker hiện có; không tự cấp token khi đăng ký/reset. Khóa/reset thu hồi token; web giữ cookie/CSRF. Migration `000043` tạo bảng token ở MB1, đã chạy local; máy clone chạy `rtk proxy php artisan migrate`, không reset/seed. MB5 không thêm migration. Scheduler dọn token hết hạn mỗi ngày. [Hợp đồng mobile](../mobile/XAC_THUC.md), [MB1](../verification/MOBILE_MB1.md), [MB5](../verification/MOBILE_MB5.md).

C34: POST KH `/ke-hoach/{id}/ap-dung` cho phép chọn lại bản PT đã gửi, đã xác nhận và đang lưu trữ, kiểm tra chủ sở hữu/version và giữ unique chung C33. Giữ snapshot/thời điểm duyệt, không tạo thông báo hoặc cấp lại quyền PT cũ; nháp/chờ duyệt/quá hạn/hủy không dùng đường này. Không cần migration mới. [Kiểm chứng](../verification/M05_AP_DUNG_LAI_PT.md).

C33/migration000039: unique bản đang dùng theo KH, chung hai nguồn; dữ liệu cũ có hai bản giữ bản được áp dụng gần nhất và lưu trữ bản còn lại. POST KH `/{id}/luu-tru` cho cả giáo án PT đã nhận của chính KH, kể cả phân công đã kết thúc. Máy clone/pull chạy migration trong maintenance (`php artisan down`, `php artisan migrate`, `php artisan up`), không reset/seed. [Hợp đồng](../features/KE_HOACH_TAP.md), [kiểm chứng](../verification/M05_MOT_GIAO_AN.md).

C32: migration000038 thêm `khach_an_luc` nullable, đã chạy local. POST KH `/{id}/an|hien-lai` chỉ cho bản tự tạo đã hủy/lưu trữ, khóa/kiểm tra version; GET danh sách KH mặc định chưa ẩn, `da_an=1` xem đã ẩn. PT vẫn đọc tất cả trong scope. Không xóa snapshot/lịch sử. Máy clone chạy `php artisan migrate`. [Kiểm chứng](../verification/M05_AN_GIAO_AN.md).

C31/M05: migration000037 đã chạy trên máy này; máy clone chạy `php artisan migrate`, không seed lại. KH có POST `/api/v1/khach-hang/ke-hoach`, PUT `/{id}`, POST `/{id}/ap-dung`, `/luu-tru`, `/huy` để quản lý giáo án tự tạo không cần gói/PT. PT đọc bản tự tạo theo phân công hiện tại, không sửa thay. Nguồn/quyền/unique bản đang dùng do Backend kiểm soát; giữ lịch sử và bản PT hiện có. [Hợp đồng](../features/KE_HOACH_TAP.md), [kiểm chứng](../verification/M05_TU_TAO_GIAO_AN.md).

**Trạng thái:** đã bootstrap Laravel **13.34.0**, cài dependencies và lưu composer.lock. Đã kiểm tra trên Windows, PHP **8.4.0**, Composer **2.8.12**. Có pdo_mysql, mbstring, openssl, curl, fileinfo, dom, xml, zip.

Đã có GET /api/v1/health công khai và /up. Đã triển khai Sanctum 4.3.3 cho đăng ký KH, đăng nhập/đăng xuất, đọc/sửa hồ sơ, quyền ba vai trò và Admin tạo PT/Admin, khóa/mở khóa tài khoản. Có quên/đặt lại mật khẩu qua email. TaiKhoan ánh xạ bảng tai_khoan; không tạo users. Có [seeder 3 tài khoản demo](SEEDERS.md) cho local/testing. CORS hỗ trợ credentials và origin FRONTEND_URL. Xem [hợp đồng tài khoản](../features/TAI_KHOAN.md) và [kiểm chứng M01 bổ sung](../verification/M01_HO_SO_KHOI_PHUC.md).

Giữ nguyên [SQL thiết kế](DATABASE.md), [28 migrations nghiệp vụ](MIGRATIONS.md), [catalog 1.324 bài tập](DATA.md) và 2.648 media ở public/media/bai-tap/. Thêm 3 migrations kỹ thuật Laravel cho session/reset password/cache/queue. Đã chạy migrations trên MariaDB 10.4.32 và kiểm tra migrate/rollback/migrate trên database riêng; [bằng chứng](../verification/MARIADB_MIGRATIONS.md). Đã nhập catalog và có API bài tập công khai; chưa kiểm thử trên MySQL thật.

## Chạy Backend

Từ BE/:

```powershell
rtk proxy composer install
rtk proxy php artisan serve:local --host=localhost --port=8000 --tries=1
```

Hoặc chạy rtk proxy composer run dev. Frontend chạy riêng trong FE/; Backend không cần npm. Endpoint kiểm tra: [localhost:8000/api/v1/health](http://localhost:8000/api/v1/health).

Đã tạo .env local và APP_KEY mà không in key. Trên máy mới, chép .env.example thành .env rồi chạy php artisan key:generate một lần. Giữ APP_KEY hiện có khi ứng dụng đã có dữ liệu mã hóa. Cấu hình mẫu đã ghép các biến Laravel với biến dự án, không chứa credentials thật.

Local bootstrap dùng SESSION_DRIVER=file, CACHE_STORE=file, QUEUE_CONNECTION=sync, BROADCAST_CONNECTION=reverb cho chat realtime; DB_CONNECTION vẫn là mysql. Điền DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD trong .env bằng tài khoản MySQL của bạn, chọn database mới rồi làm theo [hướng dẫn migrations](MIGRATIONS.md). Không nhập SQL thiết kế để tạo trùng bảng.

### Lỗi CSRF cookie 500 sau khi clone

Nếu request `GET /sanctum/csrf-cookie` lỗi 500, kiểm tra lỗi mới nhất trong `storage/logs/laravel.log`. Bản đóng gói trước đây bỏ qua cả thư mục `storage/framework/sessions`, khiến bản clone thiếu nơi ghi session khi `SESSION_DRIVER=file`. Quy tắc Git đã được sửa để giữ `.gitignore` trong thư mục này và tiếp tục bỏ qua mọi file session.

Với bản clone cũ, từ `BE/` chạy PowerShell:

```powershell
New-Item -ItemType Directory -Force -Path storage/framework/sessions | Out-Null
php artisan config:clear
```

Khởi động lại Backend rồi thử đăng ký. Request CSRF cookie thành công trả HTTP 204, không có nội dung. Nếu vẫn lỗi 500, đối chiếu log: `No application encryption key has been specified` cần tạo APP_KEY bằng `php artisan key:generate` trên máy mới khi chưa có key; lỗi ghi file cần kiểm tra đường dẫn/quyền ghi. Không gửi `.env`, APP_KEY hoặc cookie để chẩn đoán.

## Danh mục bài tập

Đã có Admin quản lý nhóm cơ qua GET/POST `/api/v1/admin/nhom-co`, GET/PUT `/{id}`, PATCH `/{id}/trang-thai`. Mã unique/bất biến, phiên bản micro giây, CSRF và quyền Admin hoạt động; ngừng/khôi phục không đổi bài tập hoặc snapshot. Không thêm migration hoặc seed lại; [hợp đồng](../features/NHOM_CO.md), [kiểm chứng](../verification/M02_NHOM_CO.md).

Từ BE chạy `rtk proxy php artisan db:seed --class=BaiTapSeeder` sau migrations để nhập 19 nhóm cơ/1.324 bài. Chạy lại giữ nội dung/trạng thái đã sửa. `DatabaseSeeder` gọi lần lượt seeder tài khoản demo, bài tập và giáo án mẫu; muốn chỉ nhập catalog thì chọn class riêng.

API công khai: GET /api/v1/bai-tap, /bai-tap/bo-loc, /bai-tap/{id}. Danh sách mặc định 12/trang, tối đa 48; query tu_khoa/nhom_co_id/dung_cu_nguon/page/per_page. Chỉ bài/nhóm hoạt động được hiển thị. [Hợp đồng bài tập](../features/BAI_TAP.md). Từ gốc chạy `rtk proxy php scripts/kiemTraCatalogDatabase.php` để đối chiếu DB với JSON.

Admin hoạt động có thể GET danh sách/bộ lọc/chi tiết, POST thêm, PUT sửa và PATCH trạng thái tại `/api/v1/admin/bai-tap`. Endpoint ghi dùng CSRF; sửa/trạng thái yêu cầu `updated_at` từ response để phát hiện bản cũ (409). Chỉ sửa nội dung tiếng Việt cho bài nhập, giữ nguồn/media/ngôn ngữ khác. Bài mới nguồn admin có mã riêng gồm 4 chữ/số, chưa có ảnh/GIF. Không có DELETE. Không cần migration hoặc seed lại cho phần quản trị này. Xem [hợp đồng](../features/BAI_TAP.md).

## Danh mục gói tập

Chạy `rtk proxy php artisan migrate` khi cập nhật code để thêm `ma_yeu_cau_tao`/unique index chống tạo trùng T04. Migration bổ sung giữ dữ liệu cũ, không seed giá thương mại. Admin nhập gói rồi mở bán trên giao diện.

GET công khai `/api/v1/goi-tap`, `/goi-tap/{id}` chỉ trả gói hợp lệ đang bán. ADMIN hoạt động có GET/POST danh sách, GET/PUT chi tiết, PATCH `/{id}/trang-thai` tại `/api/v1/admin/goi-tap`; ghi yêu cầu CSRF. POST có UUID `client_request_id`, retry cùng nội dung trả gói cũ, khác nội dung 409. PUT/PATCH dùng `updated_at` micro giây từ server. Không DELETE; không đổi snapshot T05 hoặc cấp gói khi sửa catalog. [Hợp đồng gói tập](../features/GOI_TAP.md), [kiểm chứng](../verification/M02_GOI_TAP.md). Thanh toán/đặt mua/kích hoạt chưa triển khai.

## Kiểm tra toàn bộ runtime

```powershell
rtk proxy composer validate --strict
rtk proxy php artisan test
rtk proxy php artisan route:list --path=api
```

Đạt 123 tests/4.505 assertions về health/CORS, tài khoản, seeder, bài tập, gói tập và giáo án mẫu. XacThucTest/HoSoTaiKhoanTest/TongQuanTest/BaiTapTest/GoiTapTest/GiaoAnMauTest/NhomCoTest tự tạo database ngẫu nhiên riêng bằng kết nối MySQL/MariaDB hiện tại, migrate và transaction cho từng ca, chỉ DROP database vừa tạo. Cần quyền CREATE/DROP DATABASE, không refresh database ứng dụng. Đã kiểm thử trên MariaDB 10.4.32; đã kiểm thử hai process tạo đơn/cấp gói M03; chưa kiểm thử MySQL thật. Pint kiểm tra code PHP. [Bằng chứng tài khoản](../verification/M01_AUTH.md), [quản trị bài tập](../verification/M02_ADMIN_BAI_TAP.md), [gói tập](../verification/M02_GOI_TAP.md), [kiểm tra seeder](SEEDERS.md).

## Cấu trúc và phạm vi tiếp theo

Giữ các thư mục app/Http/Controllers/Api, app/Http/Requests, app/Models, app/Policies, app/Services, app/Events, app/Jobs, routes, database/migrations, database/factories, database/seeders, tests/Feature, tests/Unit. Controller → FormRequest/Policy → Service nếu có transaction → Eloquent → response contract.

Đã có Sanctum cookie SPA. Tạo Admin đầu tiên bằng lệnh tai-khoan:tao-admin theo [hướng dẫn](../features/TAI_KHOAN.md); lệnh hỏi mật khẩu ẩn. Môi trường local có thể chạy seeder demo thay thế. SANCTUM_STATEFUL_DOMAINS=localhost:5173 và FRONTEND_URL=http://localhost:5173 cho local. Không cài migration personal_access_tokens vì SPA chỉ dùng session. Đã có quản trị bài tập/gói/giáo án và PT đọc thư viện; đã bổ sung sửa hồ sơ/quên/đặt lại mật khẩu/khóa tài khoản qua UI. Đã có đặt mua/payOS/phân công PT M03. Đã có lịch M04; đã có Reverb/chat realtime M07 và giáo án cá nhân M05; phần tiếp theo là nhật ký M06 theo phạm vi. SMTP đã cấu hình local và xác minh xác thực STARTTLS; chưa kiểm tra thư đến hộp thư thật, payOS đã tích hợp và tạo/đọc link thật chưa thanh toán; Gemini chưa tích hợp. Catalog bài tập đã seed; giá gói do Admin nhập.

Tham khảo [CODE_STYLE.md](../CODE_STYLE.md), [hợp đồng API](../API_CONVENTIONS.md), [thiết kế database](../DATABASE_DRAFT.md) và [quyết định](../DECISIONS.md). Repository không còn file mẫu Controller `.example`; đối chiếu controller, FormRequest, policy/service và test của module hiện có trước khi thêm API.

## Giáo án mẫu

Chạy `rtk proxy php artisan migrate` sau khi cập nhật để thêm UUID nullable/unique T12 (migration 000030), giữ giáo án cũ. Admin có GET/POST `/api/v1/admin/giao-an-mau`, GET/PUT `/{id}`, PATCH `/{id}/trang-thai`; PT có GET `/api/v1/pt/giao-an-mau` và `/{id}` chỉ đọc đã duyệt. FormRequest/Service kiểm tra ngày/thứ tự, bài/nhóm hoạt động, UUID và phiên bản; ghi parent/child atomic. Duyệt do server đặt actor/thời gian; sửa nội dung thu hồi duyệt. Không DELETE hoặc sửa kế hoạch/snapshot khách hàng. [Hợp đồng](../features/GIAO_AN_MAU.md), [kiểm chứng](../verification/M02_GIAO_AN_MAU.md). Không cần seed lại catalog.

Để có dữ liệu trình diễn, chạy `rtk proxy php artisan db:seed --class=GiaoAnMauSeeder` từ BE sau khi có Admin demo và catalog: tạo 5 giáo án đã duyệt/60 dòng bài tập. Chạy lại giữ các giáo án đã có, kể cả bản tự sửa/ngừng; chỉ dùng local/testing, không gán cho khách hàng. Máy mới chạy `db:seed` để nạp phụ thuộc theo thứ tự. Xem [hướng dẫn seeders](SEEDERS.md), [kiểm chứng](../verification/M02_GIAO_AN_MAU_SEEDER.md).

## Hồ sơ, khóa tài khoản và email khôi phục
PUT /api/v1/me/ho-so sửa hồ sơ của người đăng nhập; PATCH /api/v1/admin/tai-khoan/{id}/trang-thai khóa/mở khóa do Admin thực hiện. Hai API nhận updated_at để chống ghi đè bản cũ; đều yêu cầu CSRF. Khóa tài khoản thu hồi phiên trên mọi session driver, mở khóa không phục hồi phiên cũ. Admin không tự khóa chính mình.

POST /quen-mat-khau và POST /dat-lai-mat-khau là route session ngoài /api/v1, có CSRF/rate limit/CORS. Token lưu hash trong DB, hết hạn 60 phút, dùng một lần; đặt lại thành công thu hồi phiên và không tự đăng nhập. Không thêm migration hoặc seed lại cho phần này.

Trên máy mới, cấu hình SMTP trong BE/.env: MAIL_MAILER=smtp, MAIL_SCHEME=smtp, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS và MAIL_FROM_NAME; MAIL_REQUIRE_TLS=true mặc định. Gmail dùng port 587 và mật khẩu ứng dụng. FRONTEND_URL phải là URL Vue để tạo liên kết đặt lại. Chạy php artisan config:clear sau khi thay đổi; khởi động lại tiến trình Backend dài hạn nếu có. .env là file local bị Git bỏ qua, không đưa thông tin SMTP thật vào .env.example.

Đã xác thực SMTP Gmail bằng STARTTLS tại local, chưa gửi thư thật. Test dùng Notification::fake; trình duyệt chỉ yêu cầu khôi phục cho email không tồn tại. Xem [bằng chứng và giới hạn](../verification/M01_HO_SO_KHOI_PHUC.md).

## API tổng quan theo vai trò
GET /api/v1/khach-hang/tong-quan, /pt/tong-quan, /admin/tong-quan có auth/tài khoản hoạt động/vai trò tương ứng. KH/PT chỉ nhận mức đầy đủ hồ sơ chính mình và catalog công khai; PT thêm số giáo án đã duyệt. Admin nhận aggregate tài khoản và danh mục toàn DB, không trả thông tin cá nhân người khác. GET chỉ đọc, không cần migration/seed mới. [Hợp đồng](../features/TONG_QUAN.md). Kiểm chứng dashboard nghiệp vụ xem [chỉ mục](../verification/README.md).

## Mua gói, payOS và phân công PT M03

Chạy `rtk proxy php artisan migrate` để thêm migration 000031; không cần seed lại hoặc xóa dữ liệu. Khóa local đã lưu trong BE/.env bị Git bỏ qua. Máy khác tự cấu hình PAYOS_CLIENT_ID, PAYOS_API_KEY, PAYOS_CHECKSUM_KEY và PAYOS_API_URL=https://api-merchant.payos.vn; chạy `rtk proxy php artisan config:clear` sau khi sửa. Không đưa khóa vào Vue hoặc Git.

PHP dùng CA công khai tại resources/certs/cacert.pem để xác minh HTTPS; có thể cấu hình PAYOS_CA_BUNDLE tới bộ CA của môi trường triển khai. Xem [nguồn và cách cập nhật CA](CERTIFICATES.md). Không tắt xác minh TLS.

Đăng ký webhook của ứng dụng tại `https://<host-backend>/api/v1/payos/webhook` khi có Backend HTTPS công khai. Route kiểm tra chữ ký, rồi đọc dữ liệu có chữ ký từ payOS trước ghi nhận khoản thu/kích hoạt. Ngày02/10/2026 đã nhận POST xác minh webhook qua ngrok HTTP200. Đây là bằng chứng kết nối; chưa nghiệm thu khoản chuyển tiền thật. Local KH dùng nút **Kiểm tra thanh toán** để đồng bộ cùng luồng xác minh; query return/cancel không cấp gói. FRONTEND_URL phải trỏ Vue để payOS quay về đúng trang đơn; triển khai cấu hình hostname/CORS/Sanctum tương ứng.

API KH chỉ cho đơn của chính mình, Admin xem đơn/ghi kết quả hoàn tiền thủ công/phân công PT. Đối soát chỉ ghi kết quả sau khi đã hoàn tiền ngoài hệ thống. Test MuaGoiTest dùng database riêng, có worker hai process để kiểm tra tranh chấp; không dùng database ứng dụng. [Hợp đồng và endpoint](../features/MUA_GOI_THANH_TOAN.md), [kiểm chứng và giới hạn](../verification/M03_MUA_GOI.md).

## Lịch huấn luyện M04

Đã có giờ rảnh PT, đặt/hủy lịch KH, PT xác nhận/từ chối/hoàn thành/vắng mặt, Admin xem/đóng buổi quá hạn. Chống chồng giờ/UUID/quota bằng khóa hàng + UNIQUE; chỉ hoàn thành mới trừ một buổi gói gốc cùng transaction/audit. Migration000032 đã chạy local, không reset dữ liệu.

Sau khi pull, chạy php artisan migrate. Chạy php artisan schedule:work ở terminal BE riêng để dọn deadline mỗi phút; lệnh một lần php artisan lich-hen:don-qua-han. API luôn kiểm tra deadline ngay cả khi scheduler chưa chạy. [Hợp đồng và quyền](../features/LICH_HUAN_LUYEN.md), [kiểm thử và giao diện](../verification/M04_LICH_HUAN_LUYEN.md).

## Chat realtime M07

Đã có API hội thoại KH/PT, phân trang/cursor, gửi chống trùng và kênh cá nhân Reverb. Chạy migrations để bổ sung hội thoại cho phân công hiện có. Cấu hình khóa Reverb trong BE/FE rồi chạy php artisan reverb:start --host=127.0.0.1 --port=8080, hoặc dùng start.bat ở thư mục gốc. [Hợp đồng và cấu hình](../features/REALTIME_CHAT.md), [kiểm chứng](../verification/M07_CHAT.md).

Đã bổ sung ảnh chat theo C30: chạy `php artisan migrate` để thêm migration000035, giữ tin cũ. 4 ảnh/tin, 5 MB/ảnh, JPG/PNG/WebP, tối đa 8.000 pixel mỗi chiều; tải ảnh bằng endpoint có session/quyền hiện tại, lưu tại `storage/app/private/chat`. Không cần `storage:link`. `serve:local` đặt PHP con nhận tệp 5M/body 24M; máy triển khai phải cấu hình giới hạn tương ứng ở PHP và web server. Không dùng rollback/fresh để cập nhật; rollback000035 từ chối khi đã có ảnh. [Kiểm chứng gửi ảnh](../verification/CHAT_IMAGES.md).

## Giáo án cá nhân M05

Chạy `php artisan migrate` để bổ sung migration 000036, giữ giáo án cũ; máy này đã migrate thành công. PT có `/api/v1/pt/hoc-vien`, danh sách/tạo `/pt/hoc-vien/{khachId}/ke-hoach`, chi tiết/sửa `/pt/ke-hoach/{id}` và thao tác `/gui`, `/huy`. KH có `/khach-hang/ke-hoach`, chi tiết `/{id}` và `/{id}/xac-nhan`. Quyền theo phân công hiện tại, bản gửi bất biến, hạn 24 giờ, UUID/phiên bản/chống tranh chấp và hai thông báo transactional. Không seed lại. [Hợp đồng](../features/KE_HOACH_TAP.md), [kiểm chứng](../verification/M05_KE_HOACH_TAP.md).

## Lịch tự tập và nhật ký M06

Migration000040 đã chạy local, giữ dữ liệu T16–T20; máy clone/pull chạy `php artisan migrate`, không seed lại. API lịch KH/PT, kết quả nháp/hoàn thành bất biến, nhận xét PT và thống kê thật theo [hợp đồng](../features/NHAT_KY_TAP.md). KH không gói vẫn dùng giáo án đang áp dụng; tự tập không trừ counterPT. NhatKyTapService khóa KH/lịch/phiên cùng transaction, UUID/version chống retry/tranh chấp. Toàn BE195tests/5.583assertions PASS trên MariaDB10.4.32, gồm worker hai process; [bằng chứng và giới hạn](../verification/M06_NHAT_KY_TAP.md).
