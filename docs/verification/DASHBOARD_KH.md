# Dashboard hành trình KH — 04/10/2026

## Phần đã áp dụng

Áp dụng nội dung thiết kế Superdesign trong tệp chủ dự án gửi cho `/khach-hang/tong-quan`. Không lấy sidebar/header/mascot của HTML mẫu; giữ `CaNhanLayout`, logo, menu thu gọn, chuông, tin nhắn, avatar và chatbot hiện có.

- `FE/src/views/TongQuan/HanhTrinhKhachHang.vue`: lời chào, bốn thống kê, buổi hôm nay, giáo án đang dùng, lịch sắp tới, biểu đồ buổi tự tập 7/30/90 ngày, gói snapshot, BMI 30 ngày, PT/AI và lối tắt. Vue Options API, Bootstrap Icons hiện có, SVG biểu đồ, CSS token sáng/tối, responsive và reduced-motion. Trạng thái trống rõ cho KH mới.
- `FE/src/views/TongQuan/index.vue`, `FE/src/services/tongQuanService.js`: KH dùng component mới và gửi khoảng ngày; PT/Admin giữ màn hình hiện có. Request cũ được hủy/bỏ qua khi đổi khoảng/refresh/rời trang. Không cache dữ liệu dashboard ở localStorage.
- `BE/app/Http/Requests/TongQuanRequest.php`, `BE/app/Services/TongQuanKhachHangService.php`, `BE/app/Http/Controllers/Api/TongQuanController.php`: aggregate chỉ đọc theo KH từ session; dùng transaction/khóa KH hiện có. Nút mở buổi chỉ điều hướng, không tự ghi nghiệp vụ. Không gọi AI/payOS, không suy diễn calories, thời lượng, tuần lộ trình, trạng thái online PT hoặc mục tiêu cân nặng.
- Tests: `BE/tests/Feature/TongQuanTest.php`, fixture riêng trong `BE/tests/Support/hanh-trinh-*.php`, `FE/tests/hanhTrinhKhachHang.spec.js`, `FE/tests/tongQuan.spec.js`.

Không thêm migration, thư viện, CDN hoặc seed dữ liệu vào database ứng dụng. [Hợp đồng/API và cách tính](../features/TONG_QUAN.md).

## Kiểm thử đã chạy

Windows, PHP 8.4/Laravel 13, MariaDB 10.4.32, Vue 3/Vite.

- `php artisan test --compact --filter='TongQuanTest|ChiSoCoTheTest'`: **24 tests / 370 assertions đạt** trên database ngẫu nhiên riêng. Kiểm tra quyền ba vai trò/tài khoản khóa, scope KH, ngày Việt Nam qua nửa đêm UTC, lịch hủy/tương lai, cả lịch và phiên hoàn thành, quota đang giữ, gói snapshot/hết hạn/chưa kích hoạt, PT khóa, dữ liệu trống và 7/30/90. Kiểm tra GET không tạo yêu cầu AI/không đổi trạng thái. Bao gồm các kiểm thử báo cáo Admin và chỉ số cơ thể hiện có.
- Sau khi bổ sung trường hợp đổi giáo án khi đang tập, chạy lại `TongQuanTest`: **16 tests / 240 assertions đạt**. Buổi đang tập được ưu tiên trước lịch chưa bắt đầu có ID nhỏ hơn, dùng snapshot hai bài của phiên cũ trong khi thẻ giáo án hiện tại là bản mới một bài.
- Frontend toàn bộ: **235 tests / 26 files đạt**. Có kiểm thử số liệu trống, liên kết nhật ký theo ID, mốc BMI không nội suy/thiếu chiều cao, giờ Việt Nam, đổi khoảng hợp lệ, hủy request và bỏ response cũ.
- Lint, format, build và Pint cho các file PHP thay đổi: đạt. Không chạy toàn bộ Backend suite trong lần này.

## Giao diện đã kiểm tra

FE5291/BE8017 với database `kiem_tra_hanh_trinh_ui_0b2d7f1b9bf76abb`, tài khoản và số đo giả. Không dùng database chính, không gửi email/AI/thanh toán thật. Server thử đặt Gemini key rỗng, nên thông báo AI chưa sẵn sàng là đúng cấu hình QA.

Đã đóng tab QA, khôi phục viewport, dọn database và script tạm. Hai cổng QA không còn server lắng nghe; không dừng server ứng dụng chính.

- Desktop 1440×1000, tablet 768×1024, mobile 390×844: đã xem sáng/tối. Mobile 375×812 và landscape 844×390: không tràn ngang toàn trang. Width/scrollWidth tương ứng 1440/1425, 390/375, 375/360 và 844/829.
- Đổi 7 ngày thấy hai buổi; 30/90 ngày thấy ba buổi. KPI tháng/đang dùng không bị thay theo biểu đồ. Ngày fixture theo thời gian chạy thực, khác ngày đông cứng trong test API; số tháng tại QA là một buổi/33%.
- Mở nhật ký hôm nay đúng lịch #6, xem giáo án đúng bản #1, mở chỉ số đúng hai số đo 70/69 kg và BMI22,86/22,53. Chỉ điều hướng/đọc, không lưu hoặc hoàn thành buổi.
- KH mới thấy 0 buổi, tỷ lệ `—`, không gói/PT/số đo; có đường dẫn tự tạo giáo án và ghi chỉ số miễn phí. Không sinh số mẫu 12 buổi/85%/tuần3/8 từ HTML.
- Sửa màu chữ cam/xanh cho light theme, màu tên gói trên nền tương phản và nhãn biểu đồ mobile. Sidebar trong mẫu không được đưa vào; menu/header hiện tại đồng bộ khi chuyển trang.

Ảnh: [desktop sáng](dashboard-kh-desktop-light.png), [desktop tối](dashboard-kh-desktop-dark.png), [tablet sáng](dashboard-kh-tablet-light.png), [tablet tối](dashboard-kh-tablet-dark.png), [mobile sáng](dashboard-kh-mobile-light.png), [mobile tối](dashboard-kh-mobile-dark.png), [KH mới](dashboard-kh-empty.png).

## Xem kết quả và giới hạn

Chạy dự án như hiện tại, đăng nhập KH → Tổng quan (`/khach-hang/tong-quan`), tải lại nếu cần. Không migrate/seed lại cho bước này. Không coi toàn M09 đã hoàn thành: dashboard nghiệp vụ PT, thông báo bổ sung và nghiệm thu còn theo ROADMAP. Chưa đo tải database lớn, chưa kiểm tra MySQL8/production. Mascot vẫn là chatbot kéo được hiện có của dự án.
