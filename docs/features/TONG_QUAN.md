# Trang chủ và dashboard theo vai trò

Yêu cầu chủ dự án ngày 02/10/2026: trang giới thiệu chỉ dành cho người chưa đăng nhập; KH đã đăng nhập và Admin bấm Trang chủ phải vào dashboard thống kê. PT có tổng quan riêng để thống nhất điều hướng.

## Hợp đồng triển khai

- `/` kiểm tra session qua `/me` trước khi chọn màn hình. Khách chưa đăng nhập thấy trang giới thiệu hiện có; KH/PT/Admin vào `/khach-hang/tong-quan`, `/pt/tong-quan`, `/admin/tong-quan`. Đăng ký/đăng nhập thành công cũng vào dashboard. Hồ sơ vẫn có đường dẫn riêng.
- GET `/api/v1/{khach-hang|pt|admin}/tong-quan`: auth, tài khoản hoạt động, đúng vai trò ở Backend. Không nhận user ID/vai trò từ query để quyết định dữ liệu; 401/403 theo quyền, không ghi DB. Không cần transaction/idempotency cho hành động chỉ đọc.
- KH/PT: số bài tập đang hiển thị cùng nhóm đang hoạt động, số nhóm có bài công khai, gói hợp lệ đang mở bán; mức đầy đủ hồ sơ của chính tài khoản. PT thêm số giáo án đã duyệt. Không trả số tài khoản hoặc giáo án nháp cho KH/PT.
- Admin: tổng tài khoản, hoạt động/bị khóa và theo vai trò; tổng bài/nhóm/gói/giáo án và các trạng thái hiển thị/duyệt. Số liệu toàn DB, không phải số dòng trang danh sách; chỉ aggregate, không trả email hoặc hồ sơ người khác.
- Hồ sơ KH gồm họ tên/ngày sinh/giới tính/mục tiêu/kinh nghiệm/khung giờ, PT gồm họ tên/chuyên môn/giới thiệu. Phần trăm là số mục có dữ liệu chia tổng mục, không phải tiến độ tập luyện.
- Thống kê dựa trên các module đã triển khai M01/M02. Chưa có biểu đồ doanh thu, lịch, lượt PT hoặc tiến độ cơ thể vì luồng thanh toán/lịch/nhật ký chưa được triển khai. Không dùng dữ liệu giả.
- UI: bộ lọc vai trò do route/auth quyết định, loading/error/retry và trạng thái rỗng rõ; khi 401/403 bỏ số liệu và yêu cầu đăng nhập; response tới muộn/hủy route không cập nhật. Không lưu dashboard vào localStorage/Pinia.
- Điều hướng KH/PT/Admin chuyển sang menu dọc có thể thu gọn theo yêu cầu ngày 03/10/2026. Desktop mở rộng 264px hoặc thu gọn 84px; lưu tùy chọn giao diện và giữ khi chuyển trang/refresh. Màn hình dưới 1024px dùng ngăn menu modal, đóng bằng Escape/bấm nền/chọn mục và trả focus về nút mở. Header chỉ có nút điều khiển menu, đổi theme, chuông, tin nhắn KH/PT và avatar; hồ sơ/đăng xuất nằm trong avatar. Danh mục công khai trong phiên đăng nhập dùng cùng bố cục; Backend vẫn quyết định quyền. [Kiểm chứng](../verification/SIDEBAR_NAVIGATION.md).
- Kiểm thử: quyền cả ba endpoint, người bị khóa, aggregate phản ánh dữ liệu thật/trạng thái, chỉ hồ sơ chính mình, KH/PT không nhận thống kê Admin; điều hướng `/` khi mở mới/refresh/đăng nhập/đăng xuất, tránh flash trang giới thiệu; desktop/tablet/mobile.

Không thay đổi chính sách gói/thanh toán/phân công; không cần migration, seeder hoặc dependency mới. Phần M03 tiếp tục theo [ROADMAP](../../ROADMAP.md).
