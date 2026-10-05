# MB1 — Xác thực và hồ sơ thật trên mobile

Ngày kiểm chứng: **05/10/2026**. Chủ dự án yêu cầu thực hiện sau khi UI1 mở được trên LDPlayer; chính sách C41/MB-D02 giữ nguyên. Không mở rộng sang MB2 trong lần này.

## Hành vi và source

| Module/file | Thay đổi |
| --- | --- |
| `BE/app/Models/TaiKhoan.php`, migration `2026_10_05_000043_create_personal_access_tokens_table.php` | HasApiTokens, bảng hash token/dấu phiên/thời hạn. Đã đối chiếu schema và chạy migration bổ sung local, không reset/seed |
| `BE/app/Http/Requests/DangNhapMobileRequest.php`, `Controllers/Api/PhienMobileController.php`, `Services/PhienMobileService.php`, `routes/api.php` | POST cấp/thu hồi bearer; chỉ KH/PT hoạt động, validation/throttle, transaction/khóa primary ID, logout chỉ token hiện tại |
| `BE/app/Providers/AppServiceProvider.php`, `Services/HoSoTaiKhoanService.php`, `config/sanctum.php`, `routes/console.php` | Kiểm tra actor/abilities/dấu/expiry mỗi request; khóa/reset xóa token; hạn cố định 30 ngày, daily prune. Cookie/CSRF web giữ nguyên |
| `Mobile/src/services/`, `utils/`, `config/` | API client timeout/hủy request, credentials omit, config công khai, SecureStore chỉ token/hạn; thế hệ response và hàng đợi kho tránh response/ghi muộn sau đổi phiên |
| `Mobile/src/contexts/XemTruocContext.js`, `navigation/DieuHuong.js` | Khôi phục qua `/me`, role từ server, foreground kiểm tra lại, reset private navigation khi mất phiên; bản mẫu tách biệt |
| `Mobile/src/screens/Auth/DangNhap.js`, `CaNhan/HoSo.js`, `CaNhan/SuaHoSo.js`, `TongQuanTaiKhoan.js` | Đăng nhập thật, hồ sơ thật, form đúng actor/version; 422, 409 giữ draft, chặn gửi lặp/Back khi đang lưu, xác nhận bỏ draft. Tổng quan thật chưa hiển thị số liệu lịch/tập luyện mẫu |
| `BE/tests/Feature/PhienMobileTest.php`, `tests/Support/mobile-worker.php`, `Mobile/tests/phien.test.mjs` | Kiểm tra quyền/thu hồi/rollback/tranh chấp và vòng đời kho/request |
| README/root docs/`Mobile/docs/` | Cập nhật hiện trạng, hợp đồng, cách chạy và giới hạn |

Frontend Vue không đổi luồng đăng nhập. Hai endpoint native loại khỏi middleware SPA stateful; không tắt CSRF toàn ứng dụng. PUT hồ sơ dùng lại validation/authorization/transaction/version hiện có. Payload không chứa email/vai trò/ID.

## Môi trường và kiểm tra đã chạy

Windows; PHP **8.4.0**, Laravel **13.34.0**, Sanctum **4.3.3**, MariaDB **10.4.32** (driver mysql). Node **22.20.0**, npm **10.9.3**, Expo **57.0.26**, React Native **0.86.3**, React **19.2.3**.

| Kiểm tra | Kết quả thực tế |
| --- | --- |
| `BE/: rtk proxy php artisan test --filter="PhienMobileTest\|XacThucTest\|HoSoTaiKhoanTest"` | **44 tests / 568 assertions PASS**. Có 7 ca MB1; database ngẫu nhiên riêng trên MariaDB |
| `BE/: rtk proxy php artisan test` | **282 tests / 7.247 assertions PASS**, khoảng 442 giây; bao gồm hồi quy nghiệp vụ và auth web |
| `BE/: rtk proxy vendor/bin/pint --dirty` | Format các file BE thay đổi thành công |
| `Mobile/: rtk proxy npm test` | **9/9 PASS**: kho chỉ token/hạn, restore mạng lỗi/thử lại/401/expiry, response cũ, ghi kho muộn khi logout, kho lỗi, double-submit/Admin, 409/401 khi lưu, logout lỗi mạng |
| `Mobile/: rtk proxy npx expo install --check` | Dependencies tương thích SDK |
| `Mobile/: rtk proxy npx expo-doctor` | **21/21 PASS** |
| `Mobile/: rtk proxy npx expo export --platform all` | Android/iOS/web export thành công; không phải APK/IPA |
| `rtk proxy git diff --check` và kiểm tra Markdown/UTF-8/link | Không lỗi whitespace; liên kết local/encoding được kiểm tra |

Backend test cấp hai phiên, logout một giữ một, sai mật khẩu/actor/trạng thái, throttle, token thiếu dấu/abilities/expiry bị từ chối, ranh giới hết hạn, ownership hồ sơ, cấm sửa role/ID, 409 bản cũ, khóa/mở/reset không hồi sinh token, rollback khi ghi token/reset lỗi và cookie/CSRF web. Race đăng nhập chờ khóa tài khoản và reset dùng worker/kết nối riêng trên MariaDB; phát hiện và sửa thứ tự khóa index email/primary ID trước khi đạt kết quả trên.

## LDPlayer đã kiểm tra

LDPlayer 9, Android 9, Expo Go **57.0.2**, màn **720×1280**. Kết nối local qua `adb reverse` cổng 8000/8081; Laravel và Metro chạy trên host. Dùng tài khoản demo public, không dùng tài khoản khách thật.

1. Đăng nhập `khachhang@example.test`: đúng role KH, `/me` trả hồ sơ thật.
2. Đổi tên demo thêm `QA` từ form app và lưu: API đọc từ phiên thứ hai thấy tên đã đổi cùng version micro giây mới.
3. Force-stop Expo Go rồi mở lại: tự khôi phục KH và tên mới, không phải đăng nhập lại.
4. Đăng xuất từ Cá nhân: quay về Auth và báo đã đăng xuất thiết bị. Phiên thứ hai vẫn đọc/sửa được hồ sơ; dùng phiên đó khôi phục tên demo ban đầu rồi thu hồi, xóa trạng thái QA tạm.
5. Force-stop/mở lại sau logout: vẫn ở Auth, không tự vào KH. Đăng nhập `pt@example.test`: đúng role PT/tab Học viên/hồ sơ chuyên môn, không còn dữ liệu KH.
6. Mở sửa hồ sơ PT, nhập draft rồi Back: xác nhận bỏ thay đổi; discard trở về hồ sơ cũ. Draft PT không ghi database.

Ảnh: [KH sau khôi phục](screenshots/mobile-mb1-restored.png), [đăng xuất](screenshots/mobile-mb1-logout.png), [PT đăng nhập](screenshots/mobile-mb1-pt.png), [xác nhận bỏ draft](screenshots/mobile-mb1-back.png).

## Giới hạn và cách xem

- Điện thoại Android thật, APK/development build, iOS native và phát hành store chưa kiểm tra/thực hiện. Export không thay nghiệm thu thiết bị.
- Network/401/409 và kho lỗi có test tự động; chưa chạy đầy đủ ma trận mất mạng/khóa/reset thủ công trên điện thoại thật. Không thay thời gian hệ thống để giả lập đủ 30 ngày trên thiết bị.
- Backend race chạy MariaDB, chưa chứng minh riêng MySQL 8. API/chat/web hồi quy tự động không chứng minh socket native đã tích hợp.
- Đăng ký/khôi phục mật khẩu trên app, lịch/tổng quan nghiệp vụ, học viên, tập luyện, chat, chatbot, thanh toán và push còn chờ theo lộ trình. Không có số liệu mẫu trên tổng quan tài khoản thật.
- Web Expo chỉ duyệt UI, không lưu token trong localStorage. Native lưu token/hạn bằng SecureStore; lỗi logout server được báo đúng trạng thái chưa xác nhận thu hồi.
- Giữ theo dõi 23 cảnh báo dependencies từ bootstrap; không nâng cấp phá SDK bằng audit fix force.
- Rollback migration chỉ phù hợp trước khi có người dùng phiên mobile: drop bảng token sẽ mất các phiên mobile; không tự rollback dữ liệu đang dùng. Việc triển khai staging/production chưa thực hiện.

Xem [Mobile README](../../Mobile/README.md) để chạy BE/Metro, cấu hình `.env.local` và mở Expo Go bằng ADB. `start.bat` gốc tiếp tục dành cho web. Bước tiếp theo là **MB2: tổng quan và lịch KH/PT dùng dữ liệu thật**.
