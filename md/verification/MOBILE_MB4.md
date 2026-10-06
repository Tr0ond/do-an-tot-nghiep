# MB4 — Chat và thông báo trên mobile

Ngày kiểm chứng: **05/10/2026**. MB4 đã tích hợp vào app KH/PT và kiểm tra các luồng dưới đây. Điện thoại thật, bản cài và push chưa nghiệm thu.

## Phần thay đổi

- `Mobile/src/screens/TraoDoi/`: danh sách hội thoại có tìm kiếm/phân trang/chưa đọc; chi tiết chat chữ/ảnh, xem ảnh lớn, lịch sử cũ, trạng thái gửi/đọc; danh sách thông báo, đọc từng tin hoặc tất cả.
- `TraoDoiContext`, `useHoiThoai`, `kenhChat`, `traoDoiService`: socket cá nhân chỉ nhận tín hiệu; HTTP tải dữ liệu/quyền thật. Foreground/có mạng nối lại, tải hết các trang `after_id`; polling 45 giây chỉ khi hoạt động. Rời màn/nền/đổi phiên hủy request và bỏ response cũ. Phiên mới có context và lịch sử riêng.
- `AnhChat`: nhận bytes bằng bearer qua API kiểm tra quyền, chuyển data URI trong bộ nhớ; `expo-image` dùng `cachePolicy="none"`. Không đưa bearer vào URL hoặc lưu lịch sử ảnh xuống file. Ảnh rời vùng nhìn/rời màn/nền không tiếp tục hiển thị. Bản sao picker được dọn khi bỏ ảnh/gửi thành công/rời phiên, chỉ trong thư mục `ImagePicker` thuộc cache app.
- HTTP chung bổ sung multipart, raw auth, response ảnh và thời hạn upload. SDK 57 dùng `File` trong `FormData`; không dùng object `{ uri }` vốn bị Expo fetch từ chối. Giữ UUID, chữ và thứ tự/file ảnh cho retry; khóa sửa nội dung khi chưa rõ kết quả. Lỗi 429 dùng `Retry-After`, lỗi quyền tải lại trạng thái.
- Tab Tin nhắn có badge thật; chuông tại Tổng quan và Tin nhắn. Thông báo chỉ điều hướng sau khi server đánh dấu đọc; ánh xạ theo role, ID hợp lệ và route có thật. Đích chưa hỗ trợ báo rõ, không mở URL tùy ý.
- `BE/routes/channels.php`: thêm guard `sanctum` bên cạnh `web` cho private channel. Trước sửa, bearer mobile được middleware nhận nhưng broadcaster trả 403 vì channel chỉ chọn `web`. Quyền kênh vẫn kiểm tra đúng ID, trạng thái và vai trò. Không thêm migration hoặc đổi nghiệp vụ chat/thông báo.
- Thêm `expo-image-picker`, `expo-image`, `expo-file-system`, NetInfo tương thích SDK 57; plugin picker không yêu cầu camera/microphone. Thiết kế đối chiếu project Stitch `12280986189063614412`, giữ theme hiện có và chỉ dùng hành vi API thực.

## Kiểm tra đã chạy

Môi trường: Windows, Node 22.20.0, PHP 8.4, Laravel hiện có, MariaDB 10.4.32; LDPlayer 9 Android 9/API 28, Expo Go SDK 57. Dữ liệu QA là KH, PT cũ, PT mới và Admin giả lập trong database tên ngẫu nhiên; API cổng 8002, Reverb 8082. Không seed/reset database chính hoặc gửi chat vào tài khoản chính.

| Kiểm tra | Kết quả thực tế |
| --- | --- |
| `rtk proxy npm test` trong Mobile | **32 tests PASS**, gồm HTTP multipart/raw/ảnh, merge, 123 tin qua ba trang, response phiên cũ, UUID retry, ảnh quá giới hạn, liên kết theo role, socket/auth muộn/heartbeat/reconnect/cleanup |
| `rtk proxy php artisan test --compact --filter="ChatTest\|ThongBaoTest\|PhienMobileTest"` trong BE | **35 tests / 516 assertions PASS** trên MariaDB; gồm cookie web, bearer KH/PT, auth kênh, ảnh riêng, unread, đổi PT, thu hồi phiên, quyền người nhận và Reverb thật hai client |
| Test bearer mới trong `ChatTest` | 1 test / 26 assertions PASS: chữ/ảnh và UUID, đọc, own-channel auth raw, chặn kênh người khác, thông báo người khác, PT hết phân công 404, KH chỉ đọc, logout chặn ảnh/auth |
| Expo install check / Expo Doctor | Dependencies đúng SDK; **21/21 checks PASS** |
| Expo export Android/iOS/web | **PASS**; bundle/assets, chưa phải APK/IPA |
| Pint hai file BE thay đổi | Đã sửa format và kiểm tra lại |
| Tài liệu / format | 23 tài liệu kiểm tra UTF-8/link không lỗi; Prettier các file Mobile thay đổi và `git diff --check` đạt |

Các luồng native thực tế:

1. KH thấy 124 tin chưa đọc từ fixture; danh sách/preview không đánh dấu đọc. Mở hội thoại tải 50 tin cuối, chỉ cập nhật cursor từ tin thực sự hiển thị.
2. KH gửi chữ trên Android, client PT thứ hai nhận tín hiệu Reverb; PT gửi trả qua API và KH thấy tin. KH chọn ảnh qua picker Android, xem preview, gửi chú thích và nhận ảnh từ API riêng. Database tăng đúng một tin và một ảnh; cùng ảnh cũng được hiển thị từ bearer của KH.
3. Tắt Reverb, tạo 123 tin khi socket ngắt, mở lại: app tải bù đến tin 123 và unread về 0 khi đã nhìn. Unit test xác minh đi đủ ba trang, không chỉ lấy trang 50 tin đầu.
4. Tắt API rồi gửi chữ: báo lỗi, giữ nguyên nội dung và ô **Thử lại**. Mở API, thử lại: database tăng một tin; nội dung được gửi và composer trống.
5. Thông báo: 23 chưa đọc, đọc riêng còn 22; đích thanh toán chưa hỗ trợ báo rõ; đích hồ sơ mở tab Cá nhân sau đánh dấu đọc. Đọc tất cả đưa số chưa đọc về 0. Đã sửa lỗi điều hướng tới tab nằm trong navigator con và kiểm tra lại trên app.
6. Đăng xuất KH, vào PT trên cùng LDPlayer: không giữ lịch sử/người nhận của phiên KH; PT thấy 3 tin chưa đọc, gửi chữ được. Đổi PT bằng service trong QA khi màn/socket PT cũ còn mở: HTTP trả 404, app xóa tên/nội dung/composer. KH vẫn đọc được lịch sử cũ, không có nút gửi; hội thoại PT mới riêng và gửi được.
7. KH tải trang tin cũ bằng nút **Xem tin nhắn cũ hơn**: vị trí đang đọc được giữ. Quay lại từ picker/nền mở phần mới nhất, ảnh tải lại qua quyền. Đã xem giao diện tối ở mật độ gốc và giao diện với bề ngang khoảng 375 dp/font scale 1,3; sau kiểm tra khôi phục density 230/font scale 1,0.

Ảnh dữ liệu thử: [KH chat](../../docs/verification/screenshots/mobile-mb4-kh-chat.png), [retry](../../docs/verification/screenshots/mobile-mb4-retry.png), [thông báo](../../docs/verification/screenshots/mobile-mb4-notifications.png), [PT mất quyền](../../docs/verification/screenshots/mobile-mb4-pt-revocation.png), [KH lịch sử](../../docs/verification/screenshots/mobile-mb4-kh-history.png). Đây là tài khoản/dữ liệu thử, không phải dữ liệu người dùng thật.

## Cách mở và cấu hình

Trên Expo Go, đăng nhập KH/PT → tab **Tin nhắn** → chọn hội thoại. Chuông nằm ở Tổng quan và Tin nhắn. Chưa phân công thì danh sách rỗng; Admin dùng web.

`.env.local` giữ nguyên API URL đang dùng, bổ sung public Reverb URL/key/origin từ cấu hình Backend. Không đưa app secret vào app. Reverb local đã mở cổng 8080; Metro ở 8081, Backend chính kiểm tra health HTTP 200 và Reverb có handshake thật. App cuối phiên ở màn đăng nhập, không còn tài khoản QA. Các server/database/file ảnh và port reverse QA đã dọn. Đổi môi trường phải khởi động lại Metro; xem [hướng dẫn Mobile](../mobile/README.md).

Nếu thiếu/sai cấu hình socket, app dùng HTTP mỗi 45 giây khi mở; màn ghi trạng thái tương ứng. Bản phát hành dùng HTTPS cho API và WSS cho Reverb. Origin native là cấu hình công khai phù hợp allowed origins của server; quyền vẫn do bearer/channel callback, không do Origin.

## Giới hạn và bước tiếp theo

- Chưa nghiệm thu điện thoại thật/iOS, mạng yếu kéo dài, ảnh lớn đồng thời ở mức tối đa 4 × 5 MB, TalkBack hoặc performance soak. Không coi export là bản cài.
- Retry giữ trong bộ nhớ; không có hàng đợi offline lưu trên ổ đĩa. Đóng app/rời bỏ bản nháp không tự gửi lại. Chưa kiểm tra native trường hợp server đã lưu nhưng toàn bộ response bị cắt giữa đường; tính chống trùng đã có API test và client giữ UUID.
- `npm audit --json` báo **23** mục (7 moderate, 16 high) trong cây Expo/React Native/tooling, không có critical. Phương án tự động đưa Expo về 44/RN về 0.72 không phù hợp SDK 57; chưa áp dụng downgrade/force fix. Cần rà dependency trước bản cài MB6; Doctor không thay audit.
- Push và phát hành store còn MB-D03/04; không có push khi app tắt. MB5 tiếp theo: đăng ký/khôi phục, gói/thanh toán, chatbot/nháp AI theo các contract hiện có.

Nguồn kỹ thuật đã đối chiếu: [giao thức Pusher 7](https://pusher.com/docs/channels/library_auth_reference/pusher-websockets-protocol/) cho frame/auth/heartbeat, [Expo Image Picker](https://docs.expo.dev/versions/latest/sdk/imagepicker/) cho chọn ảnh; multipart và cache đối chiếu source/types của Expo SDK 57 đang cài. Quyền/nghiệp vụ lấy từ [chat](../features/REALTIME_CHAT.md) và [thông báo](../features/NOTIFICATIONS.md).
