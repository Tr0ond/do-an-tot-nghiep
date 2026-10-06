# Kiến trúc và quy ước mobile

**Cập nhật hiện trạng 06/10/2026:** đọc [README Mobile](README.md) và [C44](TIEN_ICH_THIET_BI.md) cho theme được lưu, deep link, realtime lịch và push đã tích hợp nhưng chưa nghiệm thu thật. Điểm vào Tài khoản demo đã bỏ; các đoạn mô tả UI1/MB1–MB5 bên dưới là lịch sử/thiết kế theo giai đoạn, không thay quyết định mới.

## Ranh giới

Luồng mục tiêu: màn hình React Native → service theo module → HTTP client chung → Laravel auth/policy/service → database hiện có. Web Vue giữ client riêng; Backend là nơi dùng chung nghiệp vụ. App không truy cập database, payOS secret hoặc Gemini trực tiếp.

UI1 có navigation/screens/components/theme và bản xem. MB1 bổ sung HTTP chung, SecureStore và quản lý phiên; MB2/3 thêm hooks tải dữ liệu, thao tác và bản nháp. MB4 thêm `TraoDoiContext` theo phiên, hook hội thoại và adapter WebSocket Pusher 7 cho Reverb. `XemTruocContext` quản lý phiên thật và bản xem tách biệt; role thật lấy từ server. Ảnh chat riêng chỉ giữ data URI trong bộ nhớ lúc hiển thị, không ghi lịch sử xuống file.

MB5 thêm Auth đăng ký/khôi phục, screens `HoanThien/`, `hoanThienService.js` và các kiểm tra URL/payload trong `utils/hoanThien.js`. Gói/FAQ công khai; đơn/AI chỉ trong stack KH. UUID và payload đang chờ chỉ giữ trong bộ nhớ màn hình, không lưu hàng đợi bí mật xuống ổ đĩa. Mã reset không tự nhận qua deep link. Thanh toán mở ngoài app rồi đọc/đồng bộ trạng thái từ BE; AI chỉ lưu nháp theo service hiện có.

```text
Mobile/
├── App.js
├── index.js
├── app.json
├── assets/
├── docs/
└── src/
    ├── navigation/       # Stack đăng nhập, KH, PT và ánh xạ liên kết
    ├── screens/
    │   ├── Auth/
    │   ├── KhachHang/
    │   ├── PT/
    │   └── HoanThien/    # Gói, đơn, FAQ, chatbot/nháp AI
    ├── components/       # Thành phần dùng lại thực sự
    ├── services/         # lichHenService.js, chatService.js, ...
    ├── contexts/         # Tài khoản/phiên và trạng thái chung vừa đủ
    ├── hooks/            # Vòng đời, tải dữ liệu, reconnect khi cần
    ├── utils/            # HTTP client, thời gian, lỗi, UUID
    ├── config/           # URL công khai và cấu hình môi trường
    └── theme/            # Màu, chữ, khoảng cách thống nhất
```

## Lựa chọn kỹ thuật đề xuất

- JavaScript và function components/hooks cho React Native. Options API chỉ áp dụng Vue; không ép khuôn Vue vào React.
- UI1 dùng React Navigation 7 với native stack và bottom tabs, `react-native-screens`, safe area, font Be Vietnam Pro và Feather icons. Expo Router chưa sử dụng.
- Context/reducer cho phiên đăng nhập ban đầu, state form tại màn hình. Chỉ thêm thư viện state/query khi phát sinh nhu cầu rõ ràng; không cài Redux/Pinia theo thói quen.
- Một HTTP client có timeout, xử lý lỗi, hủy request và nhận token qua lớp quản lý phiên. Service không render UI; màn hình không tự lặp base URL/headers.
- Biến/hàm nghiệp vụ camelCase tiếng Việt không dấu; payload snake_case, enum giữ nguyên backend. Tên component PascalCase, comment tiếng Việt có dấu. JS 2 spaces, single quote, không semicolon theo baseline.

## Điều hướng dự kiến

| Trạng thái | Điểm vào | Ghi chú |
| --- | --- | --- |
| Đang khôi phục phiên | Màn hình chờ | Chờ kiểm tra tài khoản, không lóe dữ liệu người trước |
| Chưa đăng nhập | Stack Auth | Mở tài nguyên riêng phải đi qua đăng nhập rồi kiểm tra quyền |
| KH | Tổng quan, Lịch, Tập luyện, Tin nhắn, Cá nhân | Thông báo/AI/gói mở từ lối tắt hoặc stack con |
| PT | Tổng quan, Lịch, Học viên, Tin nhắn, Cá nhân | Giáo án/nhật ký trong chi tiết học viên |
| ADMIN trên app | Báo đăng nhập không hợp lệ | Backend không cấp token mobile cho Admin |

App bám thiết kế Stitch `12280986189063614412`: KH có Tổng quan, Lịch tập, Giáo án, Tin nhắn, Cá nhân; PT có Tổng quan, Lịch tập, Học viên, Tin nhắn, Cá nhân. MB1–MB5 đã tích hợp các tab thật và màn chức năng; bản xem vẫn chỉ minh họa UI1, không cấp quyền Backend. Các màn có loading/empty/error/retry, safe area, cỡ chữ hệ thống và nhãn trợ năng; nghiệm thu bàn phím phần mềm/TalkBack/điện thoại thật còn MB6. Danh sách bài/tin dùng danh sách ảo hóa, phân trang; không tải toàn catalog hoặc toàn bộ GIF cùng lúc.

## State và vòng đời

1. Mỗi phiên app có mã thế hệ nội bộ. Request từ phiên cũ không được ghi kết quả vào phiên mới.
2. Đăng nhập nhận tài khoản/hồ sơ từ server, khôi phục/foreground gọi `/me`; reset navigation theo vai trò server. Chọn vai trò chỉ dành bản mẫu.
3. Khi app trở lại foreground hoặc có mạng: kiểm tra phiên khi cần, tải lại quyền/trạng thái đang xem và tải bù chat. Không suy ra mất phiên chỉ từ lỗi mạng.
4. Khi chạy nền: dừng polling không cần thiết; không giả định socket hoặc timer tiếp tục chạy. Push là kênh riêng nếu được triển khai.
5. Logout/401: hủy request, bỏ listeners/timers, ngắt socket, xóa token và dữ liệu nhạy cảm trong bộ nhớ. 403/404 tài nguyên chỉ đóng hoặc làm mới tài nguyên, không logout toàn cục bừa bãi.
6. State sửa chưa lưu phải báo khi rời màn. Đề xuất bản đầu không lưu chat/hồ sơ hoặc hàng đợi ghi offline xuống ổ đĩa; retry thủ công cùng UUID khi app còn giữ thao tác.

## Chống trùng và thời gian

Nút submit bị khóa khi gửi nhưng Backend vẫn chống trùng. UUID tạo một lần cho một ý định, giữ nguyên khi timeout/retry; thay payload không dùng lại UUID cũ. Giữ `updated_at` nguyên chuỗi đầy đủ micro giây khi contract yêu cầu; không chuyển qua Date rồi gửi lại phiên bản đã bị cắt độ chính xác.

Giờ nghiệp vụ hiển thị `Asia/Ho_Chi_Minh`, thời điểm API có UTC/offset. Không dùng múi giờ thiết bị để quyết định slot hoặc deadline. Khi 409, tải lại trạng thái và cho người dùng xử lý, không tự lặp mutation.
