# Chat PT–KH realtime — Bản đầu

## Phạm vi

Hội thoại 1–1 theo phân công PT. Văn bản, ảnh theo yêu cầu C30, history phân trang, unread/read cursor, reconnect và thông báo trong app. Typing/online là phần sau; không voice/video/chat nhóm.

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
4. Sau commit phát tín hiệu đồng bộ ngay bằng `ShouldBroadcastNow`; bản đầu không đưa nội dung chat vào queue.
5. FE đối chiếu client/server IDs để tránh nhân đôi optimistic message và event.
6. Offline/reconnect: tải các tin sau cursor cuối từ HTTP, rồi hợp nhất với events; không giả định WebSocket đã nhận đủ.
7. Cursor đã đọc tăng đơn điệu, chỉ nhận ID thuộc hội thoại. Event giao đến socket chưa có nghĩa người dùng đã đọc.

Socket ID/`toOthers()` chỉ tránh echo trong một số tình huống, không thay unique/message deduplication.

## Private channel và đổi PT

Laravel Reverb + Echo là baseline. Kênh private được BE authorize; tên channel không chứa secrets. Chỉ biết ID hội thoại không tạo quyền.

Web dùng Echo; mobile MB4 dùng WebSocket/Pusher 7 đã kiểm chứng với Reverb thật. Channel nhận guard Sanctum/web, nên cookie web và bearer mobile đều phải qua callback kiểm tra cùng ID, trạng thái và vai trò; Origin không cấp quyền. [Kiểm chứng MB4](../verification/MOBILE_MB4.md).

Kiểm tra quyền lúc subscribe **không tự thu hồi socket đã mở**. Bản đầu dùng kênh cá nhân `private-chat.tai-khoan.{id}`; event `chat.cap-nhat` chỉ chứa `{"can_dong_bo":true}`, không chứa nội dung, ID hội thoại, tên người hay preview. FE tải dữ liệu qua HTTP có kiểm tra phân công hiện tại. Socket PT cũ còn mở không nhận nội dung tin mới; API của PT cũ trả 404. Client events bị tắt ở Reverb để không gửi nội dung trực tiếp qua socket.

Đổi PT ghi transaction + audit. Xử lý event/response đồng thời phải kiểm tra phân công nguồn, tránh phát theo assignment cũ sau khi đã mất hiệu lực. Event thông báo mất quyền không mang nội dung tin mới.

## UI và trải nghiệm

Trang chat KH/PT dùng hết chiều rộng bên cạnh menu, chiều cao desktop 520–820px theo viewport. Thanh soạn dạng Zalo: nút ảnh/counter ở thanh công cụ phía trên, ô nhập một dòng và nút Gửi cùng hàng phía dưới. Không đính kèm thì tổng chiều cao khoảng102px; ô nhập44px tự giãn theo chữ đến116px rồi cuộn bên trong, xóa/gửi tin tự thu về44px. Preview56px chỉ hiện khi chọn/dán ảnh. Tin dài giới hạn bề ngang65ch; tin ngắn ôm nội dung. Ảnh đơn220×170px, bấm để xem lớn. Mobile giữ chữ16px, chuyển danh sách và hội thoại thành hai màn. [Kiểm chứng thanh soạn](../verification/CHAT_COMPOSER.md).

Biểu tượng tin nhắn trên header KH/PT mở bảng xem nhanh sáu hội thoại mới nhất theo thứ tự server. Bấm một dòng mở đúng `/khach-hang/tin-nhan/{id}` hoặc `/pt/tin-nhan/{id}`; “Xem tất cả tin nhắn” mở danh sách đầy đủ. Tải preview không đánh dấu đã đọc. Bảng dùng cùng HTTP đã kiểm tra scope và đồng bộ Reverb của store chat; logout/hết phiên xóa nội dung preview, không thêm kênh WebSocket chứa nội dung.

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

## API đang triển khai

Các endpoint dưới `/api/v1`, session Sanctum, tài khoản hoạt động và chỉ KH/PT:

| Method | Đường dẫn | Dữ liệu |
| --- | --- | --- |
| GET | `/hoi-thoai` | `page`, `tu_khoa`; 20 hội thoại/trang, `meta.so_chua_doc` là tổng mọi trang |
| GET | `/hoi-thoai/{id}/tin-nhan` | 50 tin mới nhất; `before_id` tải cũ, `after_id` tải bù, không dùng cả hai |
| POST | `/hoi-thoai/{id}/tin-nhan` | `client_message_id` UUID, `noi_dung` tối đa 4.000 ký tự, `anh[]` tùy chọn qua multipart; có chữ hoặc ảnh; tối đa 30 request/phút/tài khoản |
| GET | `/hoi-thoai/{id}/tin-nhan/{tinId}/anh/{viTri}` | Bytes ảnh (vị trí từ 0); session/quyền hội thoại hiện tại, 404 khi ngoài scope/tệp thiếu |
| POST | `/hoi-thoai/{id}/da-doc` | `tin_nhan_id` thuộc hội thoại; chỉ tăng cursor |
| POST | `/broadcasting/auth` | `socket_id`, `channel_name`; chỉ kênh cá nhân của chính tài khoản |

Gửi mới và retry cùng UUID/nội dung đều trả HTTP200 cùng ID. UUID đã dùng với nội dung khác trả409; không tự đổi UUID khi retry. Phân công kết thúc/đối phương bị khóa trả409 khi gửi; hội thoại ngoài scope trả404; tài khoản bị khóa/Admin trả403; validation422, giới hạn429. POST nghiệp vụ lấy CSRF cookie qua service. Phân công mới tạo hội thoại trong transaction; migration `000033` bổ sung hội thoại cho phân công đã có, không sửa lịch sử. Rollback migration không xóa hội thoại hoặc tin nhắn.

Tin chỉ có chữ gửi JSON không chứa `anh`. Backend cũng chấp nhận `anh: []` từ client cũ khi có chữ hợp lệ; mảng rỗng được coi là không đính kèm. Tin không có chữ lẫn ảnh vẫn trả422 ở `noi_dung`. Không thay giới hạn/chính sách ảnh hoặc chống trùng UUID. Kiểm chứng sửa lỗi và thu gọn chat (biên bản/chỉ mục cũ đã bỏ).

GET tin/gửi/đánh dấu đọc khóa cùng hàng KH với `PhanCongService` rồi đọc lại scope bằng locking read, chống snapshot cũ của MariaDB/MySQL. Chỉ phát tín hiệu sau commit. Reverb tạm ngừng không khiến tin đã lưu báo gửi thất bại; FE tự tải bù khi kết nối lại, tab hiện lại, có mạng hoặc mỗi45 giây khi tab đang hiện.

## Gửi ảnh — C30

Tối đa 4 ảnh/tin, 5 MB/ảnh, JPG/PNG/WebP, tối đa 8.000 pixel mỗi chiều. BE kiểm tra MIME/nội dung ảnh, kích thước và giới hạn số lượng; không nhận SVG/GIF/PDF. Chú thích tùy chọn; tin chỉ có ảnh lưu `noi_dung=''`. FE chọn ảnh qua nút ảnh, kéo thả hoặc dán từ clipboard vào vùng soạn; có xem trước/bỏ ảnh, grid ảnh và hộp xem lớn đóng bằng Escape/bấm ngoài. Preview hội thoại không có chữ hiển thị “Đã gửi ảnh”.

Metadata JSON `tin_nhan.anh` chứa đường dẫn ngẫu nhiên trên disk local riêng tư, tên gốc đã bỏ đường dẫn, MIME, dung lượng và SHA-256. HTTP chỉ trả vị trí/tên/MIME/dung lượng. FE tải bytes bằng Axios có session, tạo blob URL tạm; hủy request và dọn blob khi rời trang/hết phiên/thu hồi quyền. Ảnh lịch sử chỉ tải khi gần vùng nhìn thấy. GET trả `Cache-Control: private, no-store`, không công khai bằng `storage:link`.

Quyền ảnh giống tin chữ: KH giữ lịch sử, PT cũ mất quyền, PT mới/Admin/người ngoài không đọc. Socket vẫn chỉ phát tín hiệu; ảnh tải qua HTTP kiểm tra quyền. Retry giữ nguyên UUID/tệp/chú thích; thay chữ hoặc bytes/thứ tự ảnh với UUID cũ trả409. Hai process gửi cùng ảnh/UUID chỉ lưu một tin/một bộ tệp; lỗi ghi DB/commit dọn tệp của thao tác. Tệp giữ theo lịch sử, không có tác vụ tự xóa. Bản này giữ ảnh gốc, chưa nén ảnh hoặc bỏ EXIF.

Migration `000035` thêm JSON nullable, giữ tin cũ; down từ chối khi cột đã chứa ảnh. [Kiểm chứng ảnh](../verification/CHAT_IMAGES.md).

## Cấu hình và chạy local

Máy này đã cấu hình BE/FE local. Máy clone mới chạy `composer install` trong BE và `npm ci` trong FE, tạo `.env`, cấu hình database rồi `php artisan migrate`. Trong `BE/.env` đặt `BROADCAST_CONNECTION=reverb`, `REVERB_APP_ID` riêng, `REVERB_APP_KEY` riêng và `REVERB_APP_SECRET` ngẫu nhiên. Có thể tạo giá trị ngẫu nhiên bằng `php -r "echo bin2hex(random_bytes(32));"`. Không commit `.env`; secret chỉ ở BE.

BE dùng `REVERB_HOST=127.0.0.1`, `REVERB_PORT=8080`, `REVERB_SCHEME=http`, `REVERB_SERVER_HOST=127.0.0.1`, `REVERB_SERVER_PORT=8080`, `REVERB_ALLOWED_ORIGINS=localhost`. FE dùng `VITE_REVERB_APP_KEY` bằng **public app key** của BE, `VITE_REVERB_HOST=127.0.0.1`, `VITE_REVERB_PORT=8080`, `VITE_REVERB_SCHEME=http`. Không đưa app secret vào biến `VITE_*`. Sau thay đổi BE chạy `php artisan config:clear`; khởi động lại Vite và Reverb.

Chạy [start.bat](../../start.bat) để mở Backend, Frontend, Reverb, scheduler và ngrok. Backend dùng `php artisan serve:local --host=localhost --port=8000 --tries=1`, đặt giới hạn PHP con `upload_max_filesize=5M`, `post_max_size=24M`; lệnh `serve` mặc định có thể bị php.ini chặn ảnh từ 2 MB. Hoặc mở riêng terminal BE chạy `php artisan reverb:start --host=127.0.0.1 --port=8080` cùng hai server. KH mở `/khach-hang/tin-nhan`, PT mở `/pt/tin-nhan` từ mục **Tin nhắn** trong menu dọc hoặc bảng xem nhanh trên header. Chưa được phân công thì danh sách rỗng. KH xem lại chat cũ nhưng không gửi khi phân công kết thúc. Máy triển khai cần cấu hình PHP/web server nhận body ít nhất 24 MB và đủ 4 tệp; lệnh local không sửa php.ini toàn máy.

Khi triển khai HTTPS phải cấu hình WSS/TLS và allowed origins theo domain thật; ngrok cho payOS không tự công khai Reverb. Tham khảo [Reverb Laravel13](https://laravel.com/framework/docs/13.x/reverb) và [Broadcasting](https://laravel.com/framework/docs/13.x/broadcasting). [Bằng chứng M07](../verification/M07_CHAT.md) phân biệt rõ kiểm thử API, socket thật và trình duyệt.
