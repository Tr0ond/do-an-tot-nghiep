# Kiểm chứng dashboard PT — 04/10/2026

Đã áp dụng phần nội dung mẫu HTML Superdesign cung cấp; sidebar/header vẫn là của dự án. Component `FE/src/views/TongQuan/TongQuanPt.vue`, tích hợp ở `TongQuan/index.vue`; BE bổ sung `TongQuanPtService` qua endpoint tổng quan hiện có. Dọn phần template/computed/CSS dashboard catalog cũ không còn được dùng. [Hợp đồng dữ liệu](../features/TONG_QUAN.md#dashboard-pt-theo-thiết-kế-cung-cấp--04102026).

## Kiểm thử thực sự chạy

- Windows, PHP 8.4 / Laravel 13, MariaDB 10.4.32: `php artisan test --compact --filter=TongQuanTest` đạt **21 tests / 321 assertions**, trong database ngẫu nhiên riêng, rollback/drop sau kiểm thử. Bao gồm 5 ca PT mới: trống, scope/nguồn giáo án/cursor, kết thúc phân công/khóa KH, deadline bằng hiện tại/buổi hoàn thành chưa tiêu hao/tương lai, giới hạn hàng không cắt số tổng. Giữ các ca KH/Admin trước đó.
- Vue 3 / Vite 8.3.1: toàn Frontend **238 tests / 27 files** đạt; ba kiểm thử PT về trạng thái rỗng, tổng việc/đường dẫn/ngày Việt Nam, nhãn hết hạn và progress. Lint, format, build đạt. Pint các file PHP thay đổi và diff whitespace đạt.
- Trình duyệt: desktop 1440×1000, tablet 768×1024, mobile 390×844 ở light/dark. Đọc tên học viên, thẻ giáo án, biểu đồ/khung giờ; chỉnh bố cục học viên trên mobile để tên không bị ép nhỏ. Không tràn ngang tại các kích thước đã đo. “Xem lịch hôm nay” mở đúng bộ lọc ngày; nháp PT mở đúng trang sửa.
- UI dùng fixture giả từ `pt-dashboard-ui-fixture.php` trong database `kiem_tra_pt_dashboard_ui_*`, Backend 8017 và FE 5291; không sửa `.env`, database chính hoặc dữ liệu khách thật. Không gọi AI, mail, payOS, không gửi tin nhắn/hoàn thành hẹn trong lúc QA. Đã kiểm tra PT chưa có học viên hiển thị trống.
- Sau QA đã đóng tab riêng, trả lại kích thước trình duyệt mặc định, dừng hai server kiểm thử và xóa database/helper tạm. Các ảnh kiểm chứng được giữ lại; link ảnh và UTF-8 đã kiểm tra.

## Ảnh kiểm chứng

- [Desktop sáng](dashboard-pt-desktop-light.png), [desktop tối](dashboard-pt-desktop-dark.png).
- [Tablet sáng](dashboard-pt-tablet-light.png), [tablet tối](dashboard-pt-tablet-dark.png).
- [Mobile sáng](dashboard-pt-mobile-light.png), [mobile tối](dashboard-pt-mobile-dark.png), [chi tiết mobile](dashboard-pt-mobile-detail-light.png).
- [PT chưa có học viên](dashboard-pt-empty.png).

## Cách xem và giới hạn

Chạy dự án thường bằng `start.bat`, đăng nhập tài khoản PT, mở `/pt/tong-quan`. Không cần migration/seed mới; refresh để xem số liệu mới. Dữ liệu tổng quan cập nhật khi mở trang hoặc bấm cập nhật; các hành động nghiệp vụ tiếp tục nằm ở màn hình riêng. Chỉ số “cần chú ý” là gợi ý từ dữ liệu ghi trong hệ thống. Dashboard này không bổ sung sự kiện thông báo hay nghiệm thu toàn M09; bước tiếp theo vẫn là bổ sung thông báo và kiểm thử đầu-cuối theo lộ trình.
