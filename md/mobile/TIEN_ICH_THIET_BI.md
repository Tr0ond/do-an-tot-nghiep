# Tiện ích thiết bị — C44

Chủ dự án yêu cầu triển khai cả bốn mục ngày 06/10/2026. Android cho KH/PT; giữ quyền, luật đặt lịch, trừ lượt, xác thực web và trạng thái thanh toán.

## Giao diện

Lưu `sang`, `toi`, `heThong` theo thiết bị vào SecureStore, độc lập với phiên. Web xem thử dùng localStorage. Đọc chậm không ghi đè lựa chọn mới; ghi tuần tự bảo đảm chọn nhanh vẫn giữ lựa chọn cuối. `automatic` + expo-system-ui cho bản cài theo hệ thống. Logout không xóa sở thích.

## Link vào app

- Bản cài: `fitforge://dat-lai-mat-khau#token=<64 ký tự hex>&email=<email mã hóa>`, `fitforge://don-hang/<id>`, `fitforge://lich-hen/<id>`.
- Expo Go QA: `exp://<host>:<port>/--/dat-lai-mat-khau#...` hoặc `/--/don-hang/<id>`. Scheme của bản cài cần bản cài mới.
- Email yêu cầu từ mobile dùng nút mở FitForge, kèm liên kết web dự phòng. Giữ mã trong fragment, không ghi vào log/kho lưu trên máy; xóa params sau khi điền form. BE xác minh thời hạn và một lần sử dụng.
- Chưa đăng nhập: giữ đích đơn/lịch trong bộ nhớ đến khi đăng nhập. Đổi/thoát tài khoản hủy đích riêng còn chờ. Link chỉ chứa ID; BE kiểm tra ownership. Không tin `status`, `amount`, `PAID` trong URL.
- Link payOS mới tạo từ bearer mobile quay về trang công khai `/mo-ung-dung/don-hang/<id>`; nút Mở FitForge đưa vào đúng đơn. Trang không hiển thị dữ liệu đơn, không cần cookie web, không xác nhận đã trả tiền. Trong app gọi endpoint đồng bộ hiện có; chỉ BE xác minh/cấp gói. Khi mở trình duyệt từ app rồi trở về, app cũng kiểm tra lại. Link payOS đã tạo giữ nguyên URL; có thể trở về app bằng tay và bấm kiểm tra.
- Chưa cấu hình verified HTTPS Android App Links vì chưa có domain triển khai/certificate bản phát hành.

## Lịch realtime

Reverb dùng kênh private cá nhân đã xác thực, sự kiện `lich.cap-nhat` chỉ có `can_dong_bo: true`. Phát sau commit khi đặt/xác nhận/hủy/từ chối/ghi nhận/hết hạn/đổi PT và khi PT mở/đóng khung; lịch tự tập tạo/đổi trạng thái cũng báo lại. Rollback không phát. KH/PT liên quan tải HTTP để kiểm tra quyền mới. Không broadcast lịch, tên hoặc kết quả cá nhân.

Mobile gộp tín hiệu trong 200ms, tải lại lịch/tổng quan/khung/chi tiết/lịch tự tập ở màn đang mở. Giữ ngày/bộ lọc và nội dung form cục bộ. Trở về app/reconnect tải bù; mất socket có polling 45 giây khi app mở. Kết quả PT đang sửa không bị thay nháp bởi tín hiệu lịch.

## Push theo thiết bị

Vào **Hồ sơ → Thông báo trên điện thoại → Bật thông báo**. Không tự hỏi quyền lúc đăng nhập. Từ chối vẫn dùng app. Tắt chỉ tắt thiết bị/phiên hiện tại. Trở lại app đọc quyền và làm mới token nếu đã bật.

| Endpoint bearer | Ý nghĩa |
| --- | --- |
| GET `/api/v1/mobile/thong-bao-day` | Trạng thái phiên hiện tại; không trả token |
| PUT cùng đường dẫn | `expo_token` hợp lệ, tài khoản/phiên lấy từ server |
| DELETE cùng đường dẫn | Tắt, đổi phiên bản đăng ký để hủy hàng chờ |

Migration `000045` thêm `thiet_bi_push`, `hang_doi_push`: dữ liệu vận chuyển, không là lịch sử nghiệp vụ. Thu hồi token xóa đăng ký/hàng chờ theo FK; hết hạn/khóa/đổi mật khẩu được kiểm tra trước gửi. Đổi tài khoản cùng máy đổi phiên bản, hàng cũ không gửi sang tài khoản mới. Chat chỉ gửi người nhận; retry cùng tin/sự kiện không thêm hàng. Lịch theo thông báo nghiệp vụ đã có; trước gửi đọc lại quyền lịch/phân công chat.

Outbox ghi cùng transaction nghiệp vụ; HTTP Expo chạy trong worker sau commit. Bấm push chỉ nhận đúng tài khoản và route trong allowlist, đọc lại tài nguyên từ BE. Nội dung generic báo có tin/cập nhật; không chứa tên, chat, hồ sơ hay ảnh. Không đánh dấu chat đã đọc khi nhận push.

Worker `mobile:gui-push`, scheduler mỗi phút; lock chống hai worker cùng gửi, retry có giới hạn/backoff cho mạng/429/5xx, kiểm tra receipts sau 15 phút, tắt token `DeviceNotRegistered`. Hàng quá 24 giờ hủy. Ticket/receipt OK không chứng minh người dùng đã thấy. Expo có thể phát trùng; không cam kết exactly-once khi mất phản hồi sau nhà cung cấp đã nhận.

## Cấu hình push thật

Expo Go Android không nhận remote push. Cần bản cài riêng và Android có Google Play Services/FCM. Máy hiện chưa có EAS project ID, cấu hình Firebase hoặc bản cài riêng được kiểm chứng.

1. Tạo/chọn dự án Expo/EAS và Firebase của chủ dự án, đăng ký Android package `vn.fitforge.app`. Không đưa service account/private key vào source hoặc `EXPO_PUBLIC_*`.
2. Đặt UUID công khai `EXPO_PUBLIC_EAS_PROJECT_ID` ở môi trường build, `GOOGLE_SERVICES_FILE` trỏ file cấu hình client Firebase cho đúng package. `app.config.js` đọc hai giá trị; không giả lập ID.
3. Cấu hình FCM v1 credential trên EAS bằng tài khoản chủ dự án. Nếu bật Expo Push Security, khóa chỉ tại BE `EXPO_PUSH_ACCESS_TOKEN`.
4. Tạo APK nội bộ theo `eas.json` profile `preview`, hoặc build Android local đã cấu hình. Chưa tự chạy cloud build/publish/store trong lần này; tài khoản và dự án thuộc chủ dự án.
5. BE đặt `EXPO_PUSH_ENABLED=true` sau khi có credential/bản cài; chạy scheduler (`rtk proxy php artisan schedule:work` ở local, cron schedule:run trên server). Production cần HTTPS API/WSS Reverb và frontend URL truy cập được từ điện thoại.
6. Bật thông báo tài khoản QA, đưa app về nền/đóng bằng recents, gửi tin/đổi lịch từ QA kia. Kiểm tra receipt và bấm push cold/warm. Sau logout/expire/đổi tài khoản không nhận nhầm. Android force-stop trong Settings có thể chặn FCM tới khi mở lại app.

Nguồn: [Expo push setup](https://docs.expo.dev/push-notifications/push-notifications-setup/), [gửi và receipts](https://docs.expo.dev/push-notifications/sending-notifications/), [deep linking](https://docs.expo.dev/linking/into-your-app/).
