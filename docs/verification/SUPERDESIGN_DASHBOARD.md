# Dashboard Admin theo Superdesign — 04/10/2026

## Thay đổi

Áp dụng phần nội dung của Dasboard.txt do chủ dự án cung cấp cho /admin/tong-quan; không lấy sidebar/header trong HTML mẫu. Giữ CaNhanLayout, logo, menu, theme, thông báo và avatar của dự án. KH/PT tiếp tục dùng dashboard riêng.

- FE/src/views/TongQuan/BaoCaoAdmin.vue: sáu KPI, hai đường dòng tiền, việc cần xử lý, lịch hôm nay, gói snapshot, PT, AI, thành viên, nội dung và nhắc việc. Màu cam, thẻ bo góc, bóng nhẹ, icon Bootstrap có sẵn, hiệu ứng hover và tôn trọng reduced-motion. Chữ phụ tối thiểu 12px, số có độ rộng ổn định. Theme sáng/tối dùng token hiện tại.
- FE/src/views/TongQuan/index.vue: truyền dữ liệu tổng quan vào báo cáo, bỏ khối Admin trùng lặp, giữ dashboard KH/PT và nút cập nhật.
- FE/src/utils/baoCao.js: hai đường tiền dùng cùng miền giá trị, giữ số âm/0/một mốc.
- BE/app/Services/BaoCaoService.php: mở rộng aggregate chỉ đọc cho lịch hôm nay, việc cần xử lý, AI 7 ngày, PT có học viên, gói sắp hết hạn, tài liệu xuất bản. Không gọi payOS/Gemini và không ghi trạng thái lịch.
- Tests: BE/tests/Feature/TongQuanTest.php, FE/tests/baoCao.spec.js; fixture QA riêng ở BE/tests/Support/bao-cao-ui-fixture.php.
- Hợp đồng dữ liệu: [BAO_CAO.md](../features/BAO_CAO.md).

Không thêm thư viện, CDN, migration hoặc dữ liệu vào database chính. Không sao chép số liệu hoặc tỷ lệ tăng trưởng minh họa trong thiết kế.

## Kiểm tra đã chạy

Windows, PHP 8.4/Laravel 13, MariaDB 10.4.32; Vue 3 Options API, Vite.

- Backend: php artisan test --compact --filter=TongQuanTest đạt **12 tests / 158 assertions**. Database ngẫu nhiên riêng, migrate/rollback/dọn sau khi chạy. Test mới kiểm tra biên ngày Việt Nam, lịch giới hạn 8 dòng nhưng tổng không bị cắt, trạng thái hiệu lực khi worker chưa chạy, quá hạn đã đóng, KH hết lượt/hết hạn/chưa thanh toán/bị khóa, đối soát ngoài kỳ, AI đang xử lý/7 ngày/tokens, không lộ nội dung riêng tư và không ghi DB.
- Frontend: npm test đạt **220 tests / 24 files**, gồm miền biểu đồ chung, số âm/0, KPI thật, tỷ lệ AI không tính yêu cầu đang xử lý, thứ tự gói và bộ lọc nhanh; kiểm thử lỗi/quyền/phân trang/race/unmount hiện có vẫn đạt.
- npm run lint:check, npm run format:check, npm run build: đạt.
- Pint --test ba file PHP thay đổi: đạt. git diff --check không báo lỗi khoảng trắng.

Không chạy lại toàn bộ BE suite trong lần chỉnh giao diện này; kết quả suite đầy đủ của lần M09 trước nằm trong [M09_BAO_CAO_ADMIN.md](M09_BAO_CAO_ADMIN.md).

## Trình duyệt

Tab QA riêng FE5291/BE8017, database kiem_tra_bao_cao_ui_69de75eb9a95cb65 với dữ liệu giả. Không dùng database ứng dụng, không thanh toán/gửi email/gọi AI thật.

Sau kiểm tra đã đóng tab QA, đặt lại viewport, dừng server tạm và dọn database fixture. Server ứng dụng 5173/8000 được giữ nguyên.

- Desktop 1440×900: 3 cột KPI, biểu đồ/cần xử lý 2:1, PT/AI hai cột. Tablet 768×1024: 2 cột KPI. Mobile 390×844: 1 cột KPI, các bảng cuộn trong vùng riêng. Đã xem cả theme sáng/tối ở ba kích thước.
- Không tràn ngang toàn trang: scrollWidth desktop 1425, tablet 753, mobile 375 (phần còn lại là thanh cuộn). Sửa phần chữ ẩn của tiêu đề bảng thoát khỏi vùng cuộn trên mobile bằng cách đặt vùng bảng làm mốc định vị.
- Fixture mặc định: nhận 1.150.000, hoàn 120.000, sau hoàn 1.030.000; 3 gói hiện tại, 1 khách chờ PT, 2 lịch hôm nay, 10 yêu cầu AI/9 thành công/1 lỗi/tỷ lệ 90%.
- Chọn 7 ngày: nhận 350.000, hoàn 120.000, sau hoàn 230.000; mốc 03/10 hiển thị −120.000. Nhóm tháng: 09/2026 sau hoàn 800.000; 10/2026 sau hoàn 230.000.
- Chọn kỳ không phát sinh: tiền/đơn bằng 0, biểu đồ báo chưa có dữ liệu; gói hiện tại/lịch hôm nay/AI hôm nay không bị thay bằng 0.
- Kỳ quá 366 ngày: thông báo tiếng Việt, không giữ số liệu cũ; chọn lại 30 ngày tải được.
- Phân trang PT sang trang 2/2 thấy PT 21; bảng trong điện thoại cuộn được. Liên kết lịch hôm nay mở đúng chi tiết buổi #2, tên KH/PT và giờ 10:00–11:00. Nút cập nhật làm mới báo cáo.

Ảnh kiểm thử: [desktop sáng](superdesign-admin-desktop.png), [desktop tối](superdesign-admin-desktop-dark.png), [biểu đồ](superdesign-admin-chart.png), [PT/AI](superdesign-admin-pt-ai.png), [tablet sáng](superdesign-admin-tablet-light.png), [tablet tối](superdesign-admin-tablet-dark.png), [mobile sáng](superdesign-admin-mobile-light.png), [mobile tối](superdesign-admin-mobile-dark.png).

## Xem kết quả

Chạy dự án như hiện tại, đăng nhập Admin và mở Tổng quan (/admin/tong-quan), tải lại trang nếu cần. Không cần migrate hoặc seed lại. Giới hạn: chưa đo tải dữ liệu lớn/chưa kiểm tra trên MySQL 8 hoặc production; các khoản thu/hoàn là dòng tiền ghi nhận. Tỷ lệ tăng trưởng, chi phí AI và trạng thái máy chủ không được suy diễn khi hệ thống chưa có dữ liệu đó.
