# Kiểm chứng M04 — Lịch huấn luyện

Ngày02/10/2026. Laravel13/PHP8.4.0, MariaDB10.4.32, Vue3 JavaScript Options API, Node22.20.0/Vitest5/Vite8.3.1. Không thay phiên đăng nhập người dùng, không tạo/đổi lịch hoặc trừ buổi tài khoản thật để chụp ảnh.

## Đã thay đổi

- PT mở/đóng/mở lại khung giờ 60 phút; không mở chồng hoặc đóng giờ đang có lịch. KH chỉ đặt giờ của PT phụ trách bằng UUID, có gói còn hạn/còn lượt, đặt trước4h và kết thúc trong hạn gói.
- Danh sách/chi tiết theo actor, filter ngày Việt Nam/trạng thái và paging. KH hủy đúng hạn; PT xác nhận/từ chối, hoàn thành/vắng mặt; Admin đọc tất cả/đóng buổi quá24h có lý do, không xác nhận thay PT.
- Deadline tính động khi đọc/lọc/ghi, cleanup mỗi phút. Khóa KH→PT + UNIQUE giữ slot; pending/confirmed giữ quyền đặt, counter chỉ giảm đúng1 khi hoàn thành; rollback lịch/counter/audit cùng transaction.
- M03 đổi PT dọn pending hết deadline trước kiểm tra buổi chưa xử lý; không để yêu cầu hết hiệu lực chặn đổi. Buổi confirmed quá hạn chưa đóng vẫn chặn; Admin đóng xong cho phép đổi, PT cũ mất quyền.
- Migration000032 bổ sung người/thời gian/lý do PT ghi nhận, đã migrate thành công trên database ứng dụng; không rollback/fresh/seed lại dữ liệu ứng dụng.
- FE có KH đặt lịch và lịch hẹn, PT khung giờ/lịch hẹn, Admin lịch hẹn; có link header và từ Gói của tôi. Giữ Options API, Axios service chung, chống gửi hai lần, UUID retry lỗi mạng, bỏ response cũ/logout, confirmation trước ghi nhận và lý do khi cần.

Code chính: [LichHenService](../../BE/app/Services/LichHenService.php), [Controller](../../BE/app/Http/Controllers/Api/LichHenController.php), [M03 PhanCongService](../../BE/app/Services/PhanCongService.php), [migration000032](../../BE/database/migrations/2026_10_02_000032_add_ghi_nhan_to_lich_hen.php), [lịch hẹn FE](../../FE/src/views/LichHen/index.vue), [khung giờ FE](../../FE/src/views/LichHen/KhungGio/index.vue), [hợp đồng/endpoint/cách chạy](../features/LICH_HUAN_LUYEN.md).

## Kiểm tra thực sự đã chạy

| Kiểm tra | Kết quả |
| --- | --- |
| BE php artisan test --compact, toàn suite sau thay đổi cuối | 123 tests /4505 assertions PASS |
| BE LichHenTest riêng | 12 tests /139 assertions PASS |
| FE npm run test | 102 tests /10 file PASS |
| FE npm run build | PASS,169 modules |
| FE npm run lint:check | PASS |
| FE npm run format:check | PASS |
| Pint --test toàn bộ PHP thay đổi | PASS |
| php artisan migrate --force trên DB ứng dụng | Migration000032 DONE |
| php artisan schedule:list | lich-hen:don-qua-han mỗi phút đã đăng ký |
| git diff --check | PASS |

[LichHenTest](../../BE/tests/Feature/LichHenTest.php) tạo DB MariaDB ngẫu nhiên riêng/migrate, transaction từng case, chỉ DROP DB nó tạo. [Worker](../../BE/tests/Support/m04-worker.php) bắt buộc đúng prefix DB riêng/SELECT DATABASE và được chạy hai process PHP thật. Các race đã kiểm tra: cùng UUID trả một lịch; hai KH tranh cùng slot chỉ một thành công/record, người còn lại409; hai lần hoàn thành đồng thời chỉ trừ một lượt, ghi một audit. Có zero quota, rollback khi audit lỗi, gói vừa hết hạn vẫn trừ gói gốc trong24h và không mượn gói mới, các deadline chính xác, hủy/vắng mặt không trừ, scope/role/khóa tài khoản và thu hồi PT sau đổi phân công.

[Vitest](../../FE/tests/lichHen.spec.js) kiểm tra UTC/VN, ngày giờ sai, UUID retry/double-submit, stale response/logout, action permission/lý do, filter/paging URL và render có dữ liệu của cả ba vai trò. Chưa có bộ E2E tự động.

## Kiểm tra trình duyệt

CUA dùng component/layout thật với fixture và mock service trong bộ nhớ, memory router và Pinia riêng. Preview tạm FE/m04-preview.html + src/m04-preview.js đã xóa sau QA, không còn route demo trong ứng dụng. Không phát request mutation API bằng tài khoản thật. Ảnh bên dưới chứng minh giao diện/interaction; các kiểm thử MariaDB ở trên chứng minh Backend.

- Desktop1440, tablet768, mobile390. Đã xem ảnh bố cục, đọc DOM và kiểm tra chiều rộng nội dung không vượt viewport cho tablet/mobile. Menu header cuộn ngang riêng trên màn hình hẹp; nội dung không cuộn ngang.
- KH chọn ngày/giờ → gửi yêu cầu → chi tiết → nhập lý do hủy → trạng thái hủy. PT thêm giờ/đóng giờ chưa giữ; giờ đang có lịch không có nút đóng; xác nhận hoàn thành qua bước xác nhận → hiển thị1 buổi tiêu hao. Admin nhập lý do → đóng quá hạn → vẫn giữ trạng thái quá hạn, không trừ.
- Kiểm tra loading (nút gửi disabled), empty, error (không giả thành hết slot) và chưa được phân công PT. Không có lỗi/warning console ở tab QA mới sau sửa bản preview.
- 13 ảnh JPEG, lưu nguyên bản, dữ liệu demo; không chứa thông tin cá nhân thật hay secrets.

| Giao diện | Ảnh |
| --- | --- |
| KH đặt lịch | [Desktop](m04-kh-dat-lich-desktop.jpg) |
| KH chi tiết/hủy | [Desktop](m04-kh-chi-tiet-desktop.jpg), [Mobile](m04-kh-chi-tiet-mobile.jpg), [Đã hủy](m04-kh-huy-mobile.jpg) |
| PT khung giờ | [Desktop](m04-pt-khung-gio-desktop.jpg), [Mobile](m04-pt-khung-gio-mobile.jpg) |
| PT hoàn thành | [Desktop](m04-pt-hoan-thanh-desktop.jpg) |
| Admin đóng quá hạn | [Biểu mẫu](m04-admin-dong-xu-ly-desktop.jpg), [Đã đóng](m04-admin-da-dong-desktop.jpg) |
| Admin danh sách | [Tablet](m04-admin-danh-sach-tablet.jpg), [Mobile](m04-admin-danh-sach-mobile.jpg) |
| KH rỗng/lỗi | [Rỗng](m04-kh-rong-tablet.jpg), [Lỗi](m04-kh-loi-tablet.jpg) |

## Xem/chạy kết quả và giới hạn

Chạy FE npm run dev, BE php artisan serve; sau pull chạy BE php artisan migrate. Chạy thêm BE php artisan schedule:work để dọn trạng thái mỗi phút. Chưa bật scheduler nền lâu dài cho máy người dùng; API vẫn ngăn thao tác sai deadline nếu scheduler chưa chạy.

KH vào header Lịch hẹn hoặc /khach-hang/dat-lich, PT vào /pt/khung-gio và /pt/lich-hen, Admin /admin/lich-hen. Để đặt lịch thật, KH phải có gói đã được xác minh thanh toán và Admin phân công PT; không tự tạo dữ liệu thanh toán giả hoặc seed lịch vào tài khoản thật.

Đã kiểm thử MariaDB10.4.32, chưa kiểm thử MySQL8. M04 chưa gồm giáo án cá nhân/lịch tự tập/nhật ký, chat realtime hay báo cáo lịch nâng cao; các phần đó theo M07→M05/M06. Không tuyên bố nghiệm thu chuyển tiền thật từ ảnh UI hoặc HTTP200 xác minh webhook.
