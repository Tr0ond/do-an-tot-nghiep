# Header PT và khởi động ngrok — 03/10/2026

- `CaNhanLayout.vue`: chuyển menu PT từ nội dung lên header chung, giữ các mục Tổng quan, Hồ sơ PT, Giáo án mẫu, Khung giờ, Lịch hẹn; đưa Thư viện bài tập từ nút riêng vào menu. Chi tiết lịch hẹn vẫn đánh dấu mục Lịch hẹn đang mở.
- `start.bat`: thêm kiểm tra ngrok trong PATH và cửa sổ `ngrok http http://localhost:8000`. Ngrok dùng cấu hình/authtoken đã lưu trên máy. Script hướng dẫn lấy URL HTTPS và thêm `/api/v1/payos/webhook`, không tự sửa kênh payOS.
- Windows, Node 22.20.0: `npm.cmd run build`, `npm.cmd run lint:check`, Prettier kiểm tra layout và 12 tests trong `tongQuan.spec.js` đều đạt. `start.bat --check` đạt, kể cả khi gọi từ thư mục ngoài dự án. Đã đối chiếu lệnh tunnel với `ngrok http --help` của bản cài trên máy; chưa khởi chạy tunnel mới hoặc kiểm thử thanh toán trong lần này.
- Kiểm tra trình duyệt bằng layout thật và tài khoản PT mẫu trong Pinia, router bộ nhớ; không gọi API, không đổi session người dùng. Ở 1280px menu nằm hàng thứ hai của header; ở 1600px nằm cùng hàng logo/tài khoản, không chồng lấn. Ở 390px chỉ menu cuộn ngang, trang không tràn ngang. Không còn menu PT trong `main`, có trạng thái đang mở trên chi tiết lịch hẹn và điều hướng bàn phím tới nút đăng xuất. File preview tạm đã xóa sau kiểm tra.

![Header PT 1280px](header-pt-desktop.png)

![Header PT 390px](header-pt-mobile.png)
