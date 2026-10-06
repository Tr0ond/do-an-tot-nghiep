# FitForge — đổi tên thương hiệu (06/10/2026)

## Phạm vi đã cập nhật

- **Web (`FE/`)**: thương hiệu trang chủ/header/footer, title trình duyệt, sidebar KH/PT/Admin, layout đăng nhập và danh mục, FAQ, quyền gói, tổng quan và toàn bộ cửa sổ/trang chatbot. Nhãn và mô tả trợ năng là FitForge/FitForge AI; ID của cửa sổ và aria-controls đổi đồng bộ.
- **Mobile (`Mobile/`)**: tên ứng dụng Expo là FitForge; logo chữ đăng nhập thành `LogoFitForge`; đăng ký, hồ sơ, thiết bị, nhãn phiên mới, trạng thái/ô nhập AI và mascot dùng tên mới. Giữ slug để SecureStore trong Expo Go vẫn nhận đúng phiên cũ.
- **Backend (`BE/`)**: APP_NAME và MAIL_FROM_NAME local là FitForge; `.env.example` và fallback cấu hình dùng tên mới. Đã xóa cache cấu hình. Chỉ dẫn Gemini, phiên bản prompt cho yêu cầu mới, thông báo API/lỗi và tên chính sách dùng FitForge AI/FitForge. Chính sách của hội thoại cũ đổi nhãn khi trả về, giữ snapshot và số phiên bản trong database.
- **Asset**: các file logo/mascot chứa tên thương hiệu cũ được đổi thành `fitforge-*` trong `FE/public/images/` và `Mobile/assets/mascot/`; cập nhật tham chiếu. So SHA-256 với 6 file đã lưu trong Git: bytes không đổi. Không thiết kế lại logo hay sửa GIF.
- **Công cụ/tài liệu**: đổi tên package frontend/mobile và root lockfile tương ứng, README, hướng dẫn chatbot, dữ liệu test mới, script bản xem và bản thiết kế HTML ở workspace. Không đổi dependency/framework.
- **Nhãn phiên trong dữ liệu local**: đổi 6 tên thiết bị bắt đầu bằng thương hiệu cũ thành FitForge trong transaction. Không sửa hash token, quyền, thời hạn hoặc thu hồi phiên. Lưu bản tên trước thay đổi trong thư mục tạm của máy, ngoài repository.

## Những tên cũ còn giữ có chủ đích

| Vị trí | Lý do |
| --- | --- |
| Key SecureStore | Đổi key trực tiếp sẽ làm app mất phiên đang lưu |
| Namespace UUID thông báo, giáo án AI và seed demo | ID phải ổn định để retry/seed không tạo bản ghi trùng |
| Tên cookie/cache local và Expo slug | Bảo toàn phiên/cache và định danh app hiện có |
| JSON đánh giá Gemini cũ, prompt version/snapshot đã lưu và ảnh kiểm chứng lịch sử | Giữ bằng chứng đúng thời điểm kiểm thử, không giả mạo kết quả cũ |
| Figma URL, tên ZIP nguồn và file sao lưu ngoài workspace | Đây là tài nguyên gốc; sửa chuỗi tên không đổi tài nguyên thật và có thể làm link sai |

Rà database local chỉ đọc trước sửa nhãn: không còn tên cũ trong gói/tài liệu hay các trường nội dung hiện hành; có 6 nhãn token, 5 snapshot nguồn AI và 5 phiên bản prompt lịch sử. Chỉ nhãn token được cập nhật; snapshot/prompt lịch sử giữ nguyên. Không đổi nội dung khách đã nhắn, tiền/gói, lượt tập hoặc câu trả lời AI đã nhận.

## Kiểm tra đã thực sự chạy

| Kiểm tra | Kết quả |
| --- | --- |
| Web Vitest | **272/272 PASS**, 28 files |
| Web lint | PASS |
| Web build | PASS |
| Mobile tests | **51/51 PASS** |
| Export mobile Android + web | PASS; assets mascot có tên mới, không phải APK |
| Backend Chatbot/ThongBao, gồm giáo án AI | **38 tests / 390 assertions PASS**, chạy lại sau sửa nhãn chính sách trả về |
| Backend PhienMobileTest | **9 tests / 167 assertions PASS**; tổng kiểm tra backend liên quan: **47/47** |
| Pint các file PHP sửa thương hiệu | PASS |
| Prettier 13 file Vue sửa thương hiệu | PASS |
| git diff --check | PASS |

Kiểm tra format toàn FE ban đầu báo 4 file; file trang chủ đã format lại, 3 file còn lại (`lichHen.css`, `LichHen/KhungGio/index.vue`, `NhatKyTap/index.vue`) có lỗi format từ trước và không có diff trong thay đổi này. Không báo format toàn FE PASS.

Trình duyệt tại localhost:5173 đã mở trang chủ, đăng nhập và cửa sổ chatbot: tên FitForge, FitForge AI và mascot tải được. [Trang chủ](../../docs/verification/fitforge-brand/home.png), [chatbot](../../docs/verification/fitforge-brand/chatbot.png).

Native Expo Go SDK 57 / LDPlayer Android 9: tên FitForge xuất hiện khi khởi động; manifest trả name FitForge và giữ slug. Khởi động lại Expo Go vẫn khôi phục tài khoản đã đăng nhập và dữ liệu tổng quan thật. Hồ sơ hiển thị FitForge ở cuối màn, mascot tải từ tên asset mới. Không logout/reset phiên hoặc gửi câu hỏi AI để QA. [Hồ sơ mobile](../../docs/verification/fitforge-brand/mobile-profile.png).

## Xem kết quả

Tải lại website đang chạy; mở lại app Expo Go đang dùng để cập nhật tên trên thông tin ứng dụng. Không cần seed, migration hoặc đăng nhập lại chỉ vì đổi tên. Chưa build APK hoặc thử điện thoại thật trong lần đổi thương hiệu này.

## Logo hệ thống hiện tại

Logo PNG neon do chủ dự án cung cấp, dùng nguyên artwork ở `FE/public/images/logo-fitness-neon.png` qua `LogoThuongHieu.vue`. Các logo cam có chữ thương hiệu cũ không được UI hiện tại tham chiếu.

Mobile dùng bản sao byte-identical ở `Mobile/assets/brand/logo-fitforge.png` trong `KhungTaiKhoan.js`, thay icon quả tạ cạnh chữ FitForge trên màn đăng nhập/đăng ký/khôi phục. Đã kiểm tra trên LDPlayer ngày 06/10/2026. Cùng ngày, khung Tài khoản demo và hai nút xem mẫu đã bỏ khỏi đăng nhập theo yêu cầu; form đăng nhập/đăng ký/reset giữ nguyên.
