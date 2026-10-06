# Kiểm chứng M03 — Mua gói/payOS/phân công PT

Ngày 02/10/2026. Laravel 13/PHP 8.4.0, Vue 3 JavaScript Options API, database MariaDB 10.4.32 trên máy local. Giữ phiên KH đang đăng nhập; không tạo đơn/hoàn tiền/phân công bằng tài khoản thật để lấy ảnh.

## Đã triển khai

- KH: đặt mua từ chi tiết gói, danh sách/chi tiết đơn, tạo hoặc khôi phục link payOS, kiểm tra thanh toán, Gói của tôi. Giá/quyền lợi/thời hạn lấy snapshot Backend, không nhận giá client.
- Backend: chờ 15 phút, giới hạn đơn chờ/gói còn hiệu lực, UUID chống trùng, ký/kiểm tra chữ ký, xác minh dữ liệu GET payOS và thời điểm khoản thu. Webhook và nút kiểm tra đi qua cùng service; không cấp gói từ query return URL.
- Khoản thu/kích hoạt/audit cùng transaction. Retry không cộng buổi, đổi ngày kích hoạt hoặc gia hạn. Thiếu/thừa/muộn/gói khác được đối soát; nếu tiền bổ sung đủ trong hạn và chưa hoàn/chưa có gói khác thì kích hoạt một lần.
- Admin: danh sách/chi tiết đơn, ghi kết quả hoàn tiền thủ công; danh sách KH đủ điều kiện, danh sách PT hoạt động phân trang, phân công/đổi PT bằng UUID và phiên bản. Đổi PT hủy lịch tương lai, vô hiệu đề xuất chưa duyệt, giữ kế hoạch đã duyệt và lịch sử; chặn buổi đã bắt đầu chưa xử lý.
- Migration 000031 thêm dữ liệu/index M03, đã chạy thành công trên database ứng dụng; không rollback/fresh/seed lại dữ liệu ứng dụng. Tài liệu/Draw.io gốc 28 bảng vẫn được giữ.
- Khóa chỉ trong BE/.env bị Git bỏ qua, .env.example để trống. CA công khai dùng để xác minh HTTPS; không tắt kiểm tra chứng chỉ.

Code chính: [MuaGoiService](../../BE/app/Services/MuaGoiService.php), [PayosService](../../BE/app/Services/PayosService.php), [PhanCongService](../../BE/app/Services/PhanCongService.php), [migrations](../../BE/database/migrations/2026_10_02_000031_add_du_lieu_m03.php), [Đơn hàng](../../FE/src/views/DonHang/index.vue), [Gói của tôi](../../FE/src/views/KhachHang/GoiCuaToi/index.vue), [Phân công PT](../../FE/src/views/Admin/PhanCong/index.vue). [Hợp đồng](../features/MUA_GOI_THANH_TOAN.md).

## Kiểm thử đã chạy

| Kiểm tra | Kết quả |
| --- | --- |
| BE php artisan test --compact | 111 tests / 4.366 assertions PASS |
| BE MuaGoiTest + PayosChuKyTest sau sửa cuối | 13 tests / 129 assertions PASS |
| FE npm run test | 91 tests trên 9 file PASS |
| FE npm run build | PASS, 162 modules |
| FE npm run lint:check | PASS |
| FE npm run format:check | PASS |
| Pint các file PHP thay đổi | PASS |

[MuaGoiTest](../../BE/tests/Feature/MuaGoiTest.php) tự tạo database ngẫu nhiên riêng và migrate, transaction từng ca; chỉ DROP database của chính test. Kiểm tra quyền/ownership/khóa tài khoản, validation, giá giả, UUID/pending/gói trùng, chữ ký/link/tổng tiền sai, retry webhook, tiền thiếu/thừa/muộn, thông báo muộn nhưng tiền trong hạn, tiền bổ sung đủ, version phân công/retry khác lý do, đổi PT/hủy lịch/giữ kế hoạch/chặn buổi và rollback khi audit lỗi. Đối soát kiểm tra quyền, retry, kết quả khác và không cấp gói sau hoàn.

Hai process PHP thực chạy cùng lúc trên database MariaDB riêng: tạo một đơn cùng UUID, xác nhận chỉ một khoản thu/một audit kích hoạt. [Worker](../../BE/tests/Support/m03-worker.php) chỉ chấp nhận tên database riêng có prefix được kiểm tra, gateway giả lập; không gửi tiền/khóa thật từ test.

[Frontend tests](../../FE/tests/muaGoi.spec.js) kiểm tra double-submit, UUID retry, response tới muộn, mất phiên, hạn thanh toán và hostname checkout. Có render SSR với dữ liệu đầy đủ cho chi tiết/list đơn, đối soát Admin, gói/PT và form đổi PT để phát hiện lỗi template.

## Kết nối payOS thật

Đã gọi cổng payOS bằng khóa local do chủ dự án cung cấp: tạo một link thử 2.000 VND, đọc lại được trạng thái PENDING, xác minh chữ ký và mã đơn/số tiền khớp. Link thử không lưu vào database ứng dụng, không gửi tên/email của KH, chưa chuyển tiền; để hết hạn tự nhiên sau 15 phút. Script probe đã xóa. Kết quả này chứng minh tạo/đọc link và kết nối HTTPS; chưa chứng minh ngân hàng đã thanh toán.

Đối chiếu code/tài liệu payOS ở E:/IxtalTravel theo yêu cầu; chỉ xem service/routes/tài liệu, không đọc .env của dự án tham khảo. Tài liệu đó hướng dẫn domain HTTPS/tunnel bằng placeholder, chưa xác định được domain Backend công khai của dự án hiện tại. Không đổi webhook của kênh thanh toán.

## Giao diện

Ảnh dùng component thực với fixture demo và router/auth store trong bộ nhớ ở preview tạm; services được thay bằng fixture, không thay cookie/session/dữ liệu người dùng. Preview đã xóa. Đã đọc màn hình KH thật ở trạng thái rỗng, không lưu dữ liệu cá nhân vào ảnh. Các màn hình desktop/tablet/mobile được kiểm tra trực quan; nav/bảng cuộn trong vùng riêng, không tràn toàn trang. Kiểm tra console của preview phân công/đối soát không thấy lỗi.

| Màn hình demo | Ảnh |
| --- | --- |
| Chi tiết đơn KH | [Desktop](../../docs/verification/m03-chi-tiet-desktop.jpg), [Tablet](../../docs/verification/m03-chi-tiet-tablet.jpg), [Mobile](../../docs/verification/m03-chi-tiet-mobile.jpg) |
| Đơn hàng Admin | [Desktop](../../docs/verification/m03-don-admin-desktop.jpg), [Mobile](../../docs/verification/m03-don-mobile.jpg) |
| Gói của tôi | [Desktop](../../docs/verification/m03-goi-desktop.jpg), [Mobile](../../docs/verification/m03-goi-mobile.jpg) |
| Phân công/đổi PT | [Desktop](../../docs/verification/m03-phan-cong-desktop.jpg), [Tablet](../../docs/verification/m03-phan-cong-tablet.jpg), [Mobile](../../docs/verification/m03-phan-cong-mobile.jpg) |
| Ghi kết quả hoàn tiền Admin | [Desktop](../../docs/verification/m03-doi-soat-desktop.jpg) |

## Xem và chạy

1. Backend và Vue chạy như hướng dẫn [BE](../backend/README.md)/[FE](../frontend/README.md). Máy clone chạy migrate mới và tự cấu hình khóa trong BE/.env.
2. Admin tạo gói hoạt động, nhập giá/quyền lợi theo nhu cầu; không seed đơn thanh toán hoặc gói đã mua giả.
3. KH vào Gói tập → Chi tiết → Đặt mua → Tạo liên kết → Thanh toán qua payOS.
4. Khi quay về, bấm Kiểm tra thanh toán nếu local chưa có webhook; xem Gói của tôi khi Backend xác minh thành công.
5. Admin vào Đơn hàng để xem khoản thu/đối soát, Phân công PT để chọn khách có gói PT khả dụng.

Webhook triển khai: đăng ký https://<host-backend>/api/v1/payos/webhook cho ứng dụng khi có Backend HTTPS truy cập được từ payOS. FRONTEND_URL trỏ đúng Vue; cấu hình CORS/session/Sanctum theo hostname triển khai. Xem [API payOS](https://payos.vn/docs/api/), [kiểm tra chữ ký](https://payos.vn/docs/tich-hop-webhook/kiem-tra-du-lieu-voi-signature/).

## Giới hạn

Chưa chuyển tiền thật hoặc nhận webhook từ payOS trên HTTPS công khai; ca PAID/ngoại lệ trong automated tests dùng dữ liệu có chữ ký giả lập. Chưa kiểm thử MySQL 8 thật, deploy hoặc E2E giao dịch ngân hàng. Ghi hoàn tiền chỉ lưu một kết quả cho khoản thu, không gọi API chuyển tiền. Bản này không mua nối tiếp/nâng cấp khi còn gói. Lịch PT M04, chat realtime M07, kế hoạch/nhật ký/chatbot và báo cáo doanh thu thực tế thuộc các bước sau; M03 chưa cấp quyền sử dụng các chức năng chưa triển khai đó.
