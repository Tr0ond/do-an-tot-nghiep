# Kiểm chứng header thông báo — 03/10/2026

## Phạm vi thay đổi

- `FE/src/components/ThongBaoHeader.vue`: chuông, badge, lối tắt chat KH/PT và bảng thông báo thích ứng màn hình.
- `FE/src/layouts/CaNhanLayout.vue`: tích hợp trên header dùng chung; thu gọn nhãn đăng xuất trên điện thoại.
- `FE/src/services/thongBaoService.js`, `FE/src/stores/thongBao.js`, `FE/src/App.vue`: API, đồng bộ định kỳ, quản lý phiên và hủy request.
- `BE/app/Http/Controllers/Api/ThongBaoController.php`, `BE/app/Http/Requests/ThongBaoRequest.php`, `BE/routes/api.php`: danh sách/đánh dấu đọc theo người nhận hiện tại.
- `BE/database/migrations/2026_10_03_000034_create_notifications_table.php`: bảng Laravel notifications; đã migrate bổ sung trên database ứng dụng, không reset dữ liệu.
- `BE/tests/Feature/ThongBaoTest.php`, `FE/tests/thongBao.spec.js`: quyền, phân trang, chống bấm trùng, XSS và phản hồi tới muộn sau đổi phiên.
- `BE/tests/Support/chat-ui-fixture.php`: tùy chọn `thong-bao` chỉ tạo dữ liệu mẫu trong database QA riêng.

## Kiểm tra đã chạy

Môi trường: Windows, PHP 8.4, Laravel 13, MariaDB 10.4.32; Node 22, Vue 3, Vite 8.3.1.

| Kiểm tra | Kết quả |
| --- | --- |
| `php artisan test --filter=ThongBaoTest` | 4 tests, 69 assertions PASS trên database MariaDB riêng |
| `php artisan test` | 137 tests, 4.723 assertions PASS; gồm kiểm tra chat/socket và các nghiệp vụ trước đó |
| `npm test` | 125 tests / 13 files PASS, gồm 9 kiểm tra header/thông báo |
| `npm run build` | PASS, 185 modules |
| `npm run lint:check` | PASS |
| Prettier và Pint phần thay đổi | PASS |
| `git diff --check` | PASS |

Trình duyệt dùng fixture `kiem_tra_chat_ui_b1a707b3e74beb9b`, Backend 8011/8012, Frontend 5280/5281, Reverb 8082; không seed thông báo vào database ứng dụng. Đã kiểm tra:

- KH/PT có chuông, tin nhắn và số chưa đọc thật từ dữ liệu fixture; Admin có chuông và không có chat riêng.
- “Đọc tất cả” giảm số thông báo KH từ 1 về 0, giữ số tin nhắn riêng.
- PT đọc từng thông báo: giảm 1 về 0, mở đúng `/pt/lich-hen`.
- Lối tắt chat KH mở `/khach-hang/tin-nhan`.
- Escape trả focus về chuông; bấm bên ngoài đóng bảng.
- Desktop mặc định và viewport điện thoại 390×844: biểu tượng 44×44, panel trong màn hình, không tràn toàn trang; menu vẫn cuộn trong hàng riêng.
- Dark KH/PT và Light Admin hiển thị đúng biến chủ đề.
- Ứng dụng thật tại localhost:5173 hiển thị trạng thái “Bạn chưa có thông báo”, không tạo số/dữ liệu giả.

## Ảnh kiểm tra

Các ảnh dùng tài khoản và thông báo mẫu, không chứng minh đã bật thông báo nghiệp vụ tự động.

- [KH desktop](header-thong-bao-kh.png)
- [KH điện thoại](header-thong-bao-mobile.png)
- [PT desktop](header-thong-bao-pt.png)
- [Admin chế độ sáng](header-thong-bao-admin-light.png)

## Giới hạn và bước tiếp theo

Trung tâm thông báo đã đọc/đánh dấu được dữ liệu Laravel notifications, nhưng **chưa gắn sự kiện nghiệp vụ tự động**. Các đề xuất theo vai trò và thứ tự ưu tiên tại [NOTIFICATIONS.md](../features/NOTIFICATIONS.md). Tin nhắn M07 vẫn cập nhật realtime; chuông dùng HTTP mỗi 45 giây khi tab hiện và tải lại khi mở. Chưa nghiệm thu chuông trên production/MySQL 8.

Đăng nhập ứng dụng và bấm chuông cạnh tên tài khoản để xem. Clone mới chạy `php artisan migrate` trong `BE` trước; máy hiện tại đã migrate bổ sung. Môi trường QA, tab QA và database fixture được dọn sau kiểm tra.
