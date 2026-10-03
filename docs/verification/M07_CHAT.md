# M07 — Chat KH/PT, kiểm chứng 03/10/2026

## Thay đổi

- Vue Options API: `views/TinNhan/index.vue`, `assets/chat.css`, service HTTP/realtime, store badge/socket và tiện ích hợp nhất tin. Routes KH/PT, menu Tin nhắn trên header dùng chung; giữ thiết kế tối hiện có và biến màu cho chủ đề sáng.
- Laravel: `ChatController`, `ChatRequest`, `ChatService`, event chỉ báo đồng bộ, private channel cá nhân, cấu hình Reverb/broadcasting. `PhanCongService` tạo hội thoại mới trong transaction. Migration000033 bổ sung hội thoại cho phân công cũ, không xóa/sửa tin.
- Lockfiles đã cập nhật cho Reverb1.12.0, Echo2.5.0, Pusher8.6.0; `ws` chỉ phục vụ kiểm thử. Cài Reverb cần điều chỉnh Guzzle về7.15.5 cùng dependencies tương thích; toàn bộ Backend đã kiểm tra lại.
- `start.bat` mở thêm Reverb; tổng5 cửa sổ. `.env` máy này đã cấu hình Reverb local, chỉ public app key nằm trong FE. Các `.env.example` giữ placeholder, không chứa credentials.

## Kiểm thử đã chạy

Windows, PHP8.4/Laravel13.34, MariaDB10.4.32, Node22.20/Vite8.3.1:

| Kiểm tra | Kết quả |
| --- | --- |
| Toàn bộ Backend `php artisan test --compact` | 133tests, 4.654assertions đạt |
| Riêng `ChatTest` | 10tests,149assertions đạt; database ngẫu nhiên riêng |
| Toàn bộ FE `npm test` | 116tests/12files đạt |
| FE build | Đạt,181modules |
| FE lint và format | Đạt |
| PHP Pint phần thay đổi | Đạt |
| `start.bat --check` | Đạt; không mở server hoặc xác minh DB/ngrok |
| `git diff --check` | Đạt; có cảnh báo chuyển newline của Git |

Backend kiểm tra ngoài scope, Admin/guest, subscribe nhầm kênh, tài khoản khóa, đổi PT và lịch sử KH/PT mới, validation/rate limit, retry cùng UUID/nội dung, xung đột UUID, cursor tăng/không nhận ID hội thoại khác, phân trang123tin, rollback/aftercommit. Hai PHP worker tranh khóa trên MariaDB cùng gửi UUID chỉ lưu1record. Cursor đọc và kiểm tra duplicate dùng current locking read để tránh snapshot cũ.

Integration tạo Reverb thật ở cổng ngẫu nhiên, hai WebSocket client `ws`, authorize qua API session và nhận event thật. Giữ socket PT cũ mở, đổi phân công rồi gửi tin với PT mới: PT cũ không nhận cập nhật của hội thoại mới và HTTP404; mọi socket payload chỉ có `can_dong_bo`, không chứa tin riêng. Không dùng Event fake để chứng minh phần này.

FE kiểm tra realtime/HTTP trái thứ tự, retry giữ UUID, GET cursor không bỏ qua tin đối phương khi POST trả trước, tải bù nhiều trang/hint khi đang tải, bỏ response cũ khi đổi trang/logout, tháo socket/listener/timer, badge toàn bộ trang, không đánh dấu đọc khi tab ẩn/cuộn lên và Enter khi IME đang soạn.

## Trình duyệt thực tế

Dùng database fixture `kiem_tra_chat_ui_<random>`, tài khoản demo riêng và5tiến trình local kiểm thử: KH5280→BE8011, PT5281→BE8012, Reverb8082; hai session cookie riêng. Không gửi tin cho khách hàng thật, không tạo gói/thanh toán/email. Fixture được dọn sau kiểm tra.

- Hai phiên KH/PT cùng mở hội thoại, gửi/nhận cả hai chiều mà không refresh; mỗi tin chỉ một bản trong vùng tin, trạng thái đã đọc cập nhật.
- Tải lịch sử trước từ50tin, giữ tin cũ đang nhìn; tìm tên không có hiển thị danh sách rỗng. Quan sát loading, active menu, bàn phím/nhãn form và back về danh sách trên mobile.
- Tạm ngừng riêng BE8011: gửi hiện lỗi/nút Gửi lại, nội dung không mất. Phục hồi server, bấm Gửi lại lưu thành công và vẫn chỉ một tin.
- Tạm ngừng riêng Reverb8082: HTTP vẫn lưu tin thành công. Phục hồi Reverb, KH tải bù tin còn thiếu mà không refresh; HTTP fallback và reconnect đều giữ lịch sử.
- Desktop1440, tablet768, mobile390: không tràn ngang toàn trang; sidebar chuyển thành danh sách riêng trên mobile, ô soạn vẫn sử dụng được. Chụp ở chủ đề tối hiện tại; chưa nghiệm thu chủ đề sáng bằng trình duyệt.

Ảnh chỉ có dữ liệu fixture:

- [KH desktop](chat-kh-desktop-dark.png), [PT desktop](chat-pt-desktop.png).
- [Tablet](chat-tablet.png), [Mobile hội thoại](chat-kh-mobile.png), [Mobile danh sách](chat-mobile-list.png).
- [Gửi lỗi và retry](chat-retry-error.png), [Tải bù](chat-reconnected.png).

## Chạy và giới hạn

Đăng nhập KH/PT tại localhost5173, chọn **Tin nhắn** trên header. Cần phân công PT để có hội thoại; chat không trừ lượt PT và không phụ thuộc gói sau khi phân công. Admin không có menu đọc chat riêng. Dùng [start.bat](../../start.bat) sau khi dừng các cửa sổ cũ. Máy clone mới cần cài dependencies, migrate và cấu hình public key/secret theo [hợp đồng chat](../features/REALTIME_CHAT.md).

Chạy lại `ChatTest` cần BE dependencies, MariaDB/MySQL với quyền tạo database kiểm thử và FE `npm ci`/Node để mở client WebSocket. Tái tạo UI fixture: từ BE chạy `php tests/Support/chat-ui-fixture.php` lấy tên DB vừa tạo, rồi từ gốc chạy `py -3 scripts/chat_ui_preview.py TEN_DB`. Ctrl+C dừng đúng các tiến trình fixture; dọn bằng `php tests/Support/chat-ui-fixture.php drop TEN_DB` từ BE. Chỉ chấp nhận tên DB thuộc prefix kiểm thử. Không chạy trên production.

Chưa nghiệm thu MySQL8, WSS/domain triển khai, tải nhiều người dùng hoặc mạng Internet. Bản đầu chỉ văn bản; typing/online, ảnh/đính kèm chưa triển khai theo phạm vi. Không có queue chứa nội dung chat. M05 giáo án cá nhân là bước nghiệp vụ kế tiếp.
