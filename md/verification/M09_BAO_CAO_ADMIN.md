# M09 — Báo cáo Admin, 04/10/2026

## Đã triển khai

Admin mở **Tổng quan hệ thống** tại /admin/tong-quan. Mặc định 30 ngày theo giờ Việt Nam; chọn khoảng 7 ngày/tháng/năm/tùy chọn, nhóm ngày/tháng rồi bấm **Xem báo cáo**. Nút **Cập nhật** làm mới cả báo cáo theo kỳ đã xem và thống kê tổng quan. Không cần migrate/seed lại.

- Backend: BaoCaoRequest, BaoCaoController, BaoCaoService, route GET /api/v1/admin/bao-cao; aggregate chỉ đọc từ khoản thu/hoàn đã xác minh, đơn snapshot, lịch PT và phân công. Không gọi payOS/Gemini, không thay trạng thái/counter.
- Frontend: views/TongQuan/BaoCaoAdmin.vue, utils/baoCao.js, services/tongQuanService.js, trang Tổng quan. Các chỉ số tiền nhận/hoàn/sau hoàn/đối soát, biểu đồ có mốc âm/0, bảng số liệu, gói snapshot, PT phân trang 20 dòng; số liệu trong kỳ tách khỏi chỉ số hiện tại.
- Không cộng giá đơn chờ trả tiền vào thực thu; khoản nhận cần đối soát chỉ tính một lần; hoàn trong kỳ cho tiền nhận kỳ trước vẫn được trừ. Buổi PT chỉ tính hoàn thành có tiêu hao, không cộng tự tập/vắng/quá hạn.
- Chặn guest/KH/PT/tài khoản khóa tại Backend; giới hạn tối đa 366 ngày, hủy request cũ/rời trang, lỗi không giữ số tiền cũ. Đổi trang PT giữ bố cục trong lúc chờ và dùng kỳ đã tải.
- Hợp đồng: [BAO_CAO.md](../features/BAO_CAO.md). Chưa cần thay đổi schema hoặc dependency.

## Kiểm tra đã chạy

Windows, PHP 8.4, Laravel 13, MariaDB 10.4.32; Vue Options API và Vite. Các feature tests tạo database ngẫu nhiên riêng, migrate, rollback từng test và dọn database; không chạy fixture trên dữ liệu ứng dụng.

- php artisan test --compact --filter=TongQuanTest: **11 tests / 127 assertions đạt**. Sáu test báo cáo mới kiểm tra quyền/validation/rỗng, nhiều chuyển tiền không nhân bản đơn, loại giao dịch chưa xác minh/thất bại, snapshot, hoàn ngoài kỳ nhận, biên UTC/VN đến microsecond, mặc định 30 ngày, gói hết hạn, không ghi dữ liệu, lịch PT lịch sử, phân trang/quyền dữ liệu.
- php artisan test --compact: **227 tests / 5.905 assertions đạt** trên MariaDB, gồm các module hiện có.
- npm test: **216 tests / 24 files đạt**, gồm 9 test báo cáo và test nút cập nhật. Kiểm tra ngày VN/năm nhuận, biểu đồ âm/0/một mốc, bộ lọc, phân trang theo kỳ đã xem, response đến muộn/hủy/unmount, 422/401/403/419/mạng/retry.
- npm run lint:check, npm run format:check, npm run build: đạt. Pint các file PHP thay đổi đạt.

## Kiểm tra trình duyệt

Tab QA riêng FE5291/BE8017; fixture tests/Support/bao-cao-ui-fixture.php tạo database kiem_tra_bao_cao_ui_<16 ký tự hex> và tài khoản/catalog/khoản thu giả, không gửi thanh toán hoặc gọi AI. QA database và các server tạm được dọn sau kiểm tra.

- Desktop 1440×900 light, tablet 768×1024 dark, mobile 390×844 light/dark. Không tràn trang ngang; bảng cuộn trong vùng riêng.
- Fixture có tiền nhận 850.000, hoàn 120.000 từ khoản nhận trước kỳ, sau hoàn 730.000, đối soát 50.000; đơn chờ 900.000 không được cộng. Hai học viên/PT và hai buổi hoàn thành.
- Tháng 09: nhận 800.000; tháng 10: nhận 50.000, hoàn 120.000, sau hoàn −70.000. Kỳ riêng 03/10 có thực thu −120.000; mốc biểu đồ hiển thị đúng.
- Chuyển trang PT thấy trang 2/2 và PT thứ 21. Kỳ không phát sinh hiển thị 0/rỗng nhưng số gói/học viên hiện tại vẫn 2. Khoảng quá 366 ngày trả thông báo tiếng Việt, đổi lại kỳ hợp lệ tải được.
- Đã kiểm tra liên kết đơn hàng, bộ lọc, nhóm tháng, bảng số liệu, nút cập nhật, theme và trạng thái loading.

Ảnh dữ liệu giả: [desktop](../../docs/verification/m09-admin-desktop.png), [tablet dark](../../docs/verification/m09-admin-tablet-dark.png), [mobile light](../../docs/verification/m09-admin-mobile-light.png), [mobile dark](../../docs/verification/m09-admin-mobile-dark.png).

## Giới hạn và bước tiếp theo

Hoàn thành bước báo cáo Admin của M09; chưa coi toàn module M09 hoàn thành. Dashboard nghiệp vụ KH/PT và các sự kiện thông báo bổ sung tiếp tục theo lộ trình. Chưa đo tải dữ liệu lớn, chưa kiểm chứng MySQL 8 hoặc production; không bổ sung xuất Excel/PDF trong bước này. “Thực thu sau hoàn” là dòng tiền ghi nhận, không phải kết luận doanh thu kế toán.
