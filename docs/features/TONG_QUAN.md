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
- Điều hướng KH/Admin nằm trong header theo yêu cầu bổ sung ngày 02/10/2026, bỏ menu trùng trong nội dung. KH có Tổng quan/Hồ sơ của tôi/Gói tập/Thư viện bài tập; Admin có Tổng quan/Tài khoản/Hồ sơ của tôi/Bài tập/Nhóm cơ/Gói tập/Giáo án mẫu. Header vẫn giữ khi mở danh mục công khai trong phiên KH/Admin; truy cập trực tiếp danh mục khôi phục session. Màn hình nhỏ đưa menu xuống hàng thứ hai trong header, cuộn ngang riêng; PT giữ vị trí menu hiện có. Backend vẫn quyết định quyền.
- Kiểm thử: quyền cả ba endpoint, người bị khóa, aggregate phản ánh dữ liệu thật/trạng thái, chỉ hồ sơ chính mình, KH/PT không nhận thống kê Admin; điều hướng `/` khi mở mới/refresh/đăng nhập/đăng xuất, tránh flash trang giới thiệu; desktop/tablet/mobile.

Không thay đổi chính sách gói/thanh toán/phân công; không cần migration, seeder hoặc dependency mới. Phần M03 tiếp tục theo [ROADMAP](../../ROADMAP.md).
