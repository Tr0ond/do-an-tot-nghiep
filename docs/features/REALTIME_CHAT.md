# Chat PT–KH realtime — Bản đầu

## Phạm vi

Hội thoại 1–1 theo phân công PT. Văn bản, history phân trang, unread/read cursor, reconnect và thông báo trong app. Typing/online/gửi ảnh là phần sau; không voice/video/chat nhóm.

M07 phụ thuộc auth/resource scope và phân công. Không chỉ ghép WebSocket vào UI trước khi xác định người được đọc/gửi.

## Quyền đã chốt — D07

| Actor | Đọc | Gửi |
| --- | --- | --- |
| KH | Hội thoại của chính mình, kể cả phân công đã kết thúc | Hội thoại của phân công còn hiệu lực, không phụ thuộc gói/lượt PT |
| PT hiện tại | Hội thoại gắn phân công đang hiệu lực của mình | Cùng điều kiện |
| PT cũ | Không còn quyền scope khi phân công kết thúc | Không |
| PT mới | Hội thoại mới của mình; không chat với PT cũ | Hội thoại mới |
| Admin | Không mặc định đọc tin riêng | Không tham gia thay PT |
| AI | Không lấy chat PT làm nguồn | Không tự gửi tin |

Không tiêu hao buổi PT khi chat; hết gói/hết buổi vẫn gửi được nếu phân công còn hiệu lực. Đây là chat với PT, khác chatbot cần quyền lợi gói/hạn mức ngày. Giao diện giờ Việt Nam, DB lưu UTC; giữ lịch sử suốt đồ án, không tự purge theo D09.

## Giao nhận

1. FE tạo `client_message_id` ổn định và hiển thị trạng thái đang gửi.
2. HTTP API kiểm tra account, ownership, phân công và input.
3. Unique key chống retry trùng; lưu message đầy đủ ID/body/timestamp.
4. Sau commit mới phát event, qua queue nếu cấu hình.
5. FE đối chiếu client/server IDs để tránh nhân đôi optimistic message và event.
6. Offline/reconnect: tải các tin sau cursor cuối từ HTTP, rồi hợp nhất với events; không giả định WebSocket đã nhận đủ.
7. Cursor đã đọc tăng đơn điệu, chỉ nhận ID thuộc hội thoại. Event giao đến socket chưa có nghĩa người dùng đã đọc.

Socket ID/`toOthers()` chỉ tránh echo trong một số tình huống, không thay unique/message deduplication.

## Private channel và đổi PT

Laravel Reverb + Echo là baseline. Kênh private được BE authorize; tên channel không chứa secrets. Chỉ biết ID hội thoại không tạo quyền.

Kiểm tra quyền lúc subscribe **không tự thu hồi socket đã mở**. Trước implementation phải chọn và test cách thu hồi: ngắt/revoke phía server hoặc dispatch tới kênh cá nhân với kiểm tra người nhận theo phân công hiện tại. Event có nội dung mới không được tiếp tục đến PT cũ sau mốc quyền kết thúc, kể cả queue/retry còn chờ. Chỉ gọi `leave()` ở client chưa đủ bảo vệ trước client không hợp tác.

Đổi PT ghi transaction + audit. Xử lý event/response đồng thời phải kiểm tra phân công nguồn, tránh phát theo assignment cũ sau khi đã mất hiệu lực. Event thông báo mất quyền không mang nội dung tin mới.

## UI và trải nghiệm

- Hiển thị rõ đang gửi/đã lưu/lỗi; retry giữ client ID, không tạo thao tác gửi mới.
- Unread tính theo cursor; tải page cũ giữ vị trí scroll.
- Sắp xếp ổn định theo thứ tự server; hiển thị ngày/giờ theo timezone.
- Logout/khóa tài khoản dọn Echo/state; hủy listeners/timers trong lifecycle.
- Tin văn bản được escape; không render raw HTML/AI content.
- Giới hạn độ dài/rate; giá trị cụ thể được cấu hình khi bootstrap.

## Bằng chứng kiểm thử

- Feature tests: người ngoài đọc/gửi/subscribe bị từ chối; retry chỉ một record; read cursor hợp lệ.
- Integration Reverb thật: hai trình duyệt gửi/nhận; offline rồi tải bù; nhiều tab không tạo duplicate.
- Giữ socket PT cũ mở, đổi phân công, gửi message mới: PT cũ không nhận nội dung.
- Queue job chờ trước lúc đổi PT không bỏ qua quyền hiện tại lúc phát.
- Nhận event và HTTP response trái thứ tự vẫn chỉ một tin UI.

Event fake test chỉ xác minh event được phát trong code, không chứng minh WebSocket hoạt động ngoài mạng.
