# Kiểm chứng M01 — Hồ sơ, khôi phục mật khẩu, khóa tài khoản

Ngày cập nhật: 02/10/2026. Bổ sung cho [M01 xác thực](M01_AUTH.md), theo [hợp đồng tài khoản](../features/TAI_KHOAN.md) và [lộ trình](../../ROADMAP.md).

## Phạm vi đã triển khai

- KH/PT/Admin đọc và sửa hồ sơ chính mình. Backend chỉ nhận trường theo vai trò; email, mật khẩu, trạng thái, vai trò và ID không được sửa qua form hồ sơ. Loại bỏ các số liệu/chứng chỉ giả trong trang hồ sơ.
- Admin khóa/mở tài khoản qua hộp thoại xác nhận, không tự khóa chính mình. Thu hồi phiên khi khóa; mở khóa không khôi phục phiên cũ, không đổi lịch sử hoặc quyền lợi đã mua. PUT/PATCH dùng version micro giây, transaction và row lock.
- Quên/đặt lại mật khẩu qua SMTP, token hash trong DB, hạn 60 phút, dùng một lần. Thông báo yêu cầu khôi phục chung cho email không tồn tại/bị khóa; CSRF, rate limit, validation và lỗi SMTP 503 có response chuẩn. Reset thành công thu hồi mọi phiên, không tự đăng nhập.
- Phiên dùng dấu xác thực từ password hash/remember_token để thu hồi trên cả file/array/database driver. Phiên tạo trước thay đổi này cần đăng nhập lại. Đăng xuất một thiết bị không xoay dấu xác thực của thiết bị khác.
- FE Vue Options API, gọi service Axios tập trung; khóa gửi trùng, giữ bản nháp khi lỗi, không tự retry 409, bỏ qua response tới muộn. Token reset đọc từ URL fragment rồi xóa khỏi URL; chỉ nằm trong bộ nhớ component.

Không thêm dependency, migration hoặc seed lại. File local BE/.env chứa cấu hình SMTP do chủ dự án cung cấp, bị Git bỏ qua. Không ghi credentials thật vào tài liệu/code/ảnh.

## Code chính

BE: HoSoTaiKhoanController/KhoiPhucMatKhauController, SuaHoSoRequest/TrangThaiTaiKhoanRequest/KhoiPhucMatKhauRequest, HoSoTaiKhoanService/KhoiPhucMatKhauService, notification KhoiPhucMatKhau, TaiKhoan, middleware KiemTraTaiKhoanHoatDong, routes và config/mail.php/config/cors.php. Test tại [HoSoTaiKhoanTest.php](../../BE/tests/Feature/HoSoTaiKhoanTest.php).

FE: [form hồ sơ](../../FE/src/views/CaNhan/Sua/index.vue), [trang khôi phục](../../FE/src/views/KhoiPhucMatKhau/index.vue), [hồ sơ](../../FE/src/views/CaNhan/index.vue), [quản lý tài khoản](../../FE/src/views/Admin/TaiKhoan/index.vue), services/router/layout. Test tại [hoSoTaiKhoan.spec.js](../../FE/tests/hoSoTaiKhoan.spec.js).

## Kiểm tra thực sự đã chạy

Windows, PHP 8.4.0, Laravel 13.34.0, MariaDB 10.4.32; FE Vue 3.5.43, Vite 8.3.1, Vitest 5.0.3.

| Kiểm tra | Kết quả |
| --- | --- |
| BE: php artisan test --compact | 93 tests, 4.185 assertions, exit 0 |
| PHP Pint cho file bổ sung/thay đổi | PASS |
| FE: npm run test | 67 tests, 7 files, exit 0 |
| FE: npm run build | PASS, 151 modules |
| FE: npm run lint:check | PASS |
| FE: npm run format:check | PASS |

Backend tests tự tạo database ngẫu nhiên riêng, migrate, transaction và chỉ drop DB do từng bộ test tạo. Bao gồm quyền ba vai trò, injection trường ngoài whitelist, nullable version/no-op/stale 409, rollback hồ sơ, thu hồi phiên sau khóa→mở và reset, CSRF thực tế, token sai/hết hạn/dùng lại/tài khoản khóa, password tối đa 72 bytes, rate limit, không lộ token, notification URL fragment, lỗi mailer và rollback password/token/session khi event thất bại. Regression đăng xuất chỉ một thiết bị cũng đã chạy. Test notification dùng fake, không gửi email.

FE tests kiểm tra payload từng vai trò, sao chép hồ sơ, khóa gửi trùng, 422/409/mạng/419 và response tới muộn, xóa fragment, token không lưu vào auth store, gửi email chuẩn hóa, 503, khóa/mở tài khoản và bản cũ không tự gửi lại. Đây là test Vitest; chưa có bộ E2E tự động cho Vue.

## Kiểm tra trình duyệt local

Laravel localhost:8000, Vue localhost:5173. Tạo tài khoản QA riêng chỉ dùng cho kiểm thử, cập nhật mục tiêu/kinh nghiệm/ngày sinh/giới tính/khung giờ, xác minh hồ sơ đọc lại đúng dữ liệu. Admin khóa rồi mở tài khoản QA; đăng nhập lại thành công. Kiểm tra form chuyên môn PT, hộp thoại xác nhận và focus vào Hủy. Không chỉnh hồ sơ hoặc mật khẩu tài khoản thật.

Request quên mật khẩu từ trình duyệt ban đầu bị CORS vì thiếu hai route mới trong config. Đã bổ sung route vào paths, kiểm tra preflight/Origin/credentials bằng Backend test và gửi lại thành công từ trình duyệt. Email thử không tồn tại nên không gửi thư. Form reset được kiểm tra bằng token giả chỉ để xem giao diện và xác minh token bị xóa khỏi URL; không đổi mật khẩu qua browser.

Đã kiểm tra trang hồ sơ/sửa hồ sơ và khôi phục ở 1440×900, 768×1024, 390×844. Sửa lưới form ngày sinh tránh tràn mobile; bố cục khôi phục tablet/mobile giữ form trong vùng nhìn ban đầu. Viewport override đã được reset, phiên demo đăng xuất. Tài khoản QA đã xóa bằng kiểm tra chính xác ID/email/tên/vai trò trong transaction. Sau dọn: 4 tài khoản, 19 nhóm cơ, 1.324 bài tập, 5 giáo án mẫu; không reseed hoặc truncate dữ liệu.

### Ảnh bằng chứng

| Màn hình | Desktop | Tablet | Mobile |
| --- | --- | --- | --- |
| Sửa hồ sơ KH | [Đã lưu thành công](m01-sua-ho-so-desktop.png) | [768px](m01-sua-ho-so-tablet.png) | [390px](m01-sua-ho-so-mobile.png) |
| Quên mật khẩu | [Thông báo chung sau gửi](m01-quen-mat-khau-desktop.png) | [768px](m01-quen-mat-khau-tablet.png) | [390px](m01-quen-mat-khau-mobile.png) |
| Đặt lại mật khẩu | [Token giả, chưa gửi](m01-dat-lai-mat-khau-desktop.png) | [768px](m01-dat-lai-mat-khau-tablet.png) | [390px](m01-dat-lai-mat-khau-mobile.png) |

[Hồ sơ KH mobile](m01-ho-so-mobile.png), [hộp thoại khóa](m01-khoa-dialog-desktop.png), [kết quả khóa tài khoản QA](m01-tai-khoan-khoa-desktop.png).

## SMTP và giới hạn

Cấu hình local Gmail SMTP port 587, MAIL_SCHEME=smtp, MAIL_REQUIRE_TLS=true; đã xóa cache cấu hình. Xác minh bằng transport bắt buộc STARTTLS và đăng nhập SMTP thành công rồi đóng kết nối, không gửi MAIL/DATA. Chưa kiểm tra gửi thư thật hoặc thư đến hộp thư người dùng. MAIL_ENCRYPTION=tls theo cấu hình chủ dự án được giữ, nhưng cấu hình Laravel hiện tại dùng scheme/require_tls để điều khiển transport. Mật khẩu ứng dụng không có trong tài liệu hoặc output.

Chưa chạy trên MySQL thật, chưa kiểm tra tranh chấp nhiều process đồng thời. Row lock/transaction/version và rollback đã được test trên MariaDB, không coi đó là chứng minh tải hoặc race đa process. Token chỉ giữ trong bộ nhớ: refresh sau khi fragment bị xóa phải mở lại liên kết email. SMTP lỗi trả 503 chung; log/array mailer ngoài testing không được dùng để xuất token hoặc giả báo đã gửi.

## Cách xem/chạy

Chạy Backend/Frontend theo [BE README](../../BE/README.md) và [FE README](../../FE/README.md), không cần migration mới cho M01 bổ sung. Đăng nhập demo rồi vào Hồ sơ → Cập nhật hồ sơ; Admin vào Tài khoản để khóa/mở. Trang [quên mật khẩu](http://localhost:5173/quen-mat-khau) dùng SMTP trong BE/.env; chủ dự án có thể gửi yêu cầu bằng email tài khoản của mình để kiểm tra thư thật và tự đặt mật khẩu.

Bước tiếp theo theo lộ trình: M03 đặt mua, xác minh payOS và kích hoạt quyền lợi; sau đó phân công PT. Chưa triển khai payOS trong lần này.
