# Tích hợp API và chức năng mobile

## Cách đọc

Đối chiếu ngày 05/10/2026 với [BE/routes/api.php](../../BE/routes/api.php), [BE/bootstrap/app.php](../../BE/bootstrap/app.php) và hợp đồng từng module. MB1 tích hợp `/me` và `/me/ho-so`; MB2 tích hợp Tổng quan, Giờ PT, Lịch KH/PT và Học viên/hồ sơ; MB3 tích hợp catalog bài tập, giáo án/mẫu, lịch tự tập/nhật ký/nhận xét và chỉ số cơ thể; MB4 tích hợp chat/thông báo; MB5 tích hợp đăng ký/khôi phục, gói/đơn/FAQ và chatbot/nháp AI. Xem [MB1](XAC_THUC.md), [MB2](../../docs/verification/MOBILE_MB2.md), [MB3](../../docs/verification/MOBILE_MB3.md), [MB4](../../docs/verification/MOBILE_MB4.md) và [MB5](../../docs/verification/MOBILE_MB5.md).

Mọi path trong bảng có prefix `/api/v1`. `{id}` là ID tài nguyên, `{khachId}` không thay thế việc kiểm tra phân công. Các path gộp trong bảng là cách viết tắt tài liệu, không truyền dấu `|` hoặc dấu ngoặc lên URL. Không lấy các endpoint minh họa cũ trong API_CONVENTIONS làm route thật nếu khác source.

## Ma trận API

| Module | Method và path hiện có | Contract nguồn |
| --- | --- | --- |
| Tài khoản | `GET /me`, `PUT /me/ho-so`, `GET /pt/ho-so` | [Tài khoản](../../docs/features/TAI_KHOAN.md) |
| Auth native | `POST /mobile/dang-nhap`, `/mobile/dang-xuat`, `/mobile/dang-ky`, `/mobile/quen-mat-khau`, `/mobile/dat-lai-mat-khau` | [Xác thực](XAC_THUC.md) |
| Tổng quan | `GET /khach-hang/tong-quan`, `GET /pt/tong-quan` | [Tổng quan](../../docs/features/TONG_QUAN.md) |
| Catalog công khai | `GET /bai-tap`, `/bai-tap/bo-loc`, `/bai-tap/{id}`; `GET /goi-tap`, `/goi-tap/{id}`; `GET /faq` | [Bài tập](../../docs/features/BAI_TAP.md), [Gói](../../docs/features/GOI_TAP.md) |
| Giờ PT | `GET/POST /pt/khung-gio`, `PATCH /pt/khung-gio/{id}`; `GET /khach-hang/khung-gio` | [Lịch hẹn](../../docs/features/LICH_HUAN_LUYEN.md) |
| Lịch KH | `GET/POST /khach-hang/lich-hen`, `GET /khach-hang/lich-hen/{id}`, `POST /khach-hang/lich-hen/{id}/huy` | [Lịch hẹn](../../docs/features/LICH_HUAN_LUYEN.md) |
| Lịch PT | `GET /pt/lich-hen`, `GET /pt/lich-hen/{id}`, `POST /pt/lich-hen/{id}/{hanhDong}` với `xac-nhan`, `tu-choi`, `hoan-thanh`, `vang-mat` | [Lịch hẹn](../../docs/features/LICH_HUAN_LUYEN.md) |
| Học viên/hồ sơ | `GET /pt/hoc-vien`; `GET /khach-hang/ho-so/{hoSoKhachHang}` theo quyền thực tế | [Giáo án](../../docs/features/KE_HOACH_TAP.md), [Tài khoản](../../docs/features/TAI_KHOAN.md) |
| Giáo án KH | `GET/POST /khach-hang/ke-hoach`, `GET/PUT /khach-hang/ke-hoach/{id}`; `POST /khach-hang/ke-hoach/{id}/{hanhDong}` với `xac-nhan`, `ap-dung`, `luu-tru`, `huy`, `an`, `hien-lai` | [Giáo án](../../docs/features/KE_HOACH_TAP.md) |
| Giáo án PT | `GET/POST /pt/hoc-vien/{khachId}/ke-hoach`, `GET/PUT /pt/ke-hoach/{id}`, `POST /pt/ke-hoach/{id}/gui` hoặc `/huy`; `GET /pt/giao-an-mau`, `/pt/giao-an-mau/{id}` | [Giáo án](../../docs/features/KE_HOACH_TAP.md), [Mẫu](../../docs/features/GIAO_AN_MAU.md) |
| Tập của KH | `GET/POST /khach-hang/lich-tap`, `GET/PUT /khach-hang/lich-tap/{id}`, `POST /khach-hang/lich-tap/{id}/{hanhDong}` với `bat-dau`, `hoan-thanh`, `huy` | [Nhật ký](../../docs/features/NHAT_KY_TAP.md) |
| PT theo dõi tập | `GET/POST /pt/hoc-vien/{khachId}/lich-tap`, `GET /pt/lich-tap/{id}`, `POST /pt/lich-tap/{id}/nhan-xet` | [Nhật ký](../../docs/features/NHAT_KY_TAP.md) |
| Chỉ số cơ thể | `GET/POST /khach-hang/chi-so-co-the`, `PUT /khach-hang/chi-so-co-the/{id}`, `GET /pt/hoc-vien/{khachId}/chi-so-co-the` | [Chỉ số](../../docs/features/CHI_SO_CO_THE.md) |
| Chat | `GET /hoi-thoai`, `GET/POST /hoi-thoai/{id}/tin-nhan`, `POST /hoi-thoai/{id}/da-doc`, `GET /hoi-thoai/{id}/tin-nhan/{tinId}/anh/{viTri}`, `POST /broadcasting/auth` | [Chat](../../docs/features/REALTIME_CHAT.md) |
| Thông báo | `GET /thong-bao`, `POST /thong-bao/{id}/da-doc`, `POST /thong-bao/da-doc-tat-ca` | [Thông báo](../../docs/features/NOTIFICATIONS.md) |
| Chatbot KH | `GET/POST /khach-hang/chatbot/hoi-thoai`, `GET /khach-hang/chatbot/hoi-thoai/{id}`, `POST /khach-hang/chatbot/hoi-thoai/{id}/tin-nhan` | [AI](../../docs/features/AI_CHATBOT.md) |
| Gói/đơn | `GET /khach-hang/goi-cua-toi`, `GET/POST /khach-hang/don-hang`, `GET /khach-hang/don-hang/{id}`, `POST /khach-hang/don-hang/{id}/link-thanh-toan` hoặc `/dong-bo` | [Thanh toán](../../docs/features/MUA_GOI_THANH_TOAN.md) |

Tên param `hoSoKhachHang` là ID hồ sơ, không mặc định bằng ID tài khoản. Kiểm tra response/service web để lấy đúng ID. Không bổ sung endpoint mới chỉ vì tên route hiện tại chưa đẹp.

## HTTP client và payload

- Base URL trỏ tới API thực sự truy cập được từ thiết bị. Gửi `Accept: application/json`; token native chỉ gửi tới host Backend tin cậy, không gắn vào URL ảnh/link ngoài.
- Giữ `status/message/data`, `meta` và `errors`; không bọc thêm response khác theo từng màn. Tham khảo [API_CONVENTIONS](../../docs/API_CONVENTIONS.md) và FormRequest trước xây form.
- 401 xử lý phiên; 403/404 xử lý quyền/tài nguyên; 409 tải lại trạng thái; 422 gắn lỗi trường; 429 báo giới hạn và tôn trọng `Retry-After` nếu có; lỗi mạng/5xx cho thử lại có kiểm soát.
- Không tự retry mọi POST. `client_request_id`, `client_message_id` và `updated_at` dùng đúng endpoint; không giả định Backend nhận một header idempotency chung cho tất cả.
- Đặt lịch gửi `khung_gio_id` và `client_request_id`, không gửi giá/quota/PT tự chọn. Hoàn thành buổi không gửi counter mới. Form chỉ gửi trường contract cho phép.
- Metadata phân trang theo module; chat dùng `before_id` hoặc `after_id`, không cùng lúc. Hủy request cũ khi đổi bộ lọc; bỏ response cũ khi rời màn/đổi phiên.

## Chat, ảnh và chạy nền

Mã web để tham khảo: [chatRealtime.js](../../FE/src/services/chatRealtime.js), [chatService.js](../../FE/src/services/chatService.js). Không copy cách dùng `import.meta.env`, blob URL hay cookie vào native.

Backend phát `chat.cap-nhat` trên kênh cá nhân `private-chat.tai-khoan.{id}`. Payload chỉ là tín hiệu đồng bộ, không có nội dung tin. MB4 dùng adapter WebSocket Pusher 7 (`kenhChat.js`) đã kiểm chứng Reverb thật. App xác thực kênh bằng bearer qua `/broadcasting/auth` (response auth raw), sau đó tải HTTP theo quyền hiện tại; channel Backend nhận Sanctum/web. Không copy Echo/pusher-js web vào Expo Go.

Retry giữ nguyên UUID, nội dung và bytes/thứ tự ảnh. Socket nhận event và HTTP trả về trái thứ tự vẫn chỉ hiển thị một tin. Khi foreground/reconnect, tải bù `after_id`; tải nhiều đợt nếu cần, không mất tin giữa các trang. Dừng polling khi nền; có thể dùng chu kỳ 45 giây lúc foreground tương tự web, dọn khi logout. Không đánh dấu đã đọc chỉ vì có preview/sự kiện.

Ảnh chat tối đa 4 ảnh/tin, 5 MB/ảnh, JPG/PNG/WebP, 8.000 pixel mỗi chiều theo contract hiện có. Native picker cần xử lý URI/MIME thực tế; ảnh HEIC không được gửi giả nhãn JPEG, cần chuyển đổi có kiểm chứng hoặc báo định dạng không hỗ trợ. Multipart dùng `anh[]`; không tự đặt boundary sai. Server vẫn validate.

Ảnh riêng MB4 tải bytes qua endpoint kiểm tra quyền bằng bearer, thành data URI chỉ trong bộ nhớ, `expo-image` dùng `cachePolicy="none"`; dọn khi rời vùng hiển thị/màn/nền/phiên hoặc mất quyền. Multipart dùng `expo-file-system File` phù hợp Expo fetch SDK 57; cache picker chỉ xóa bản sao app sở hữu. Không công khai thư mục chat, không đặt bearer trong query. GIF bài tập là catalog công khai, không chịu chung cách lưu ảnh chat.

## Thông báo và liên kết

Dùng danh sách/đánh dấu đọc hiện có; push chưa có. Response `duong_dan` hiện hướng tới web: tạo bảng ánh xạ đường dẫn nội bộ đã cho phép → route mobile + ID đúng kiểu. Đường dẫn không nhận diện được hiển thị thông báo không hỗ trợ; không mở URL bất kỳ và không suy ra quyền từ đường dẫn. Vào màn đích luôn tải lại dữ liệu, xử lý PT đã mất quyền hoặc sự kiện đã được giải quyết trên web.

Deep link chỉ chọn màn hình; đăng nhập và resource scope vẫn bắt buộc. Logout rồi bấm thông báo không được làm lộ dữ liệu cache của người trước.

## Thanh toán và AI

MB5 gọi qua `hoanThienService.js` và HTTP client chung. FAQ chuyển `meta.page` thành `current_page` ở màn để dùng phân trang chung; các module khác giữ metadata BE. Màn gói/FAQ công khai, đơn và AI chỉ cho KH đã xác thực; mở thông báo đơn vẫn GET theo quyền hiện tại.

App chỉ mở link HTTPS đúng host `pay.payos.vn`, không credentials/port lạ. Foreground tải lại chi tiết đơn; nút **Kiểm tra thanh toán** mới yêu cầu BE đồng bộ payOS. Return/cancel URL tiếp tục là web; đóng trình duyệt rồi trở về app và kiểm tra đơn. Không tự tạo deep link mới hoặc kích hoạt gói từ URL.

Gửi AI timeout 90 giây, không tự gửi lại POST. Retry kiểm tra UUID đã thành công trong lịch sử trước khi gửi cùng UUID/nội dung/consent; chưa rõ kết quả thì khóa sửa payload. Consent dữ liệu cá nhân mặc định tắt; giáo án trả về mở ở màn MB3 dưới trạng thái NHAP để người dùng xem/sửa/xác nhận. Nội dung chỉ render chữ và nguồn đã được BE xác thực.

Thanh toán: app tạo đơn qua BE → nhận link hợp lệ → mở trình duyệt → khi quay lại/foreground thì đọc hoặc đồng bộ trạng thái đơn qua BE. Return/cancel URL chỉ là tín hiệu điều hướng; không cấp gói từ query hoặc ảnh chuyển khoản. Nếu người dùng đóng trình duyệt, vẫn có thể xem đơn và làm mới. Cấu hình deep link/return URL phải được kiểm tra với luồng web hiện có, không ghi đè `FRONTEND_URL` để sửa riêng mobile.

Nghiệp vụ giữ đơn 15 phút, snapshot giá/quyền, một gói khả dụng, webhook được xác minh và chống lặp; trường hợp tiền muộn/thiếu/thừa đi theo đối soát. Phát hành store cần đánh giá riêng loại dịch vụ bán và quy định thanh toán hiện hành trước chọn cách bán trong app, đặc biệt gói chatbot; xem [phát hành](MOI_TRUONG_PHAT_HANH.md).

AI chỉ gọi qua Laravel; PT không được mở quyền AI mới. Giữ request ID khi retry, hạn mức và lựa chọn chia sẻ dữ liệu cá nhân. App bị đóng/timeout không đồng nghĩa Backend chưa xử lý: tải lại hội thoại trước gửi lại. Giáo án AI là nháp, không tự áp dụng hoặc tạo lịch. Render nội dung an toàn, không thực thi HTML/link tùy ý từ câu trả lời.
