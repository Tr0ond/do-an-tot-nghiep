# Môi trường, bản cài và phát hành

## Phát triển hiện tại

Máy bootstrap: Windows, Node 22.20.0, npm 10.9.3; Expo SDK 57, React Native 0.86.3. Chạy theo [Mobile README](../README.md); dùng `npm ci` khi clone, không copy node_modules từ máy/dự án khác. Dùng Node phù hợp yêu cầu Expo/RN tại thời điểm nâng cấp và luôn giữ lockfile.

`npm start` mở Metro. `npm run android` cần thiết bị/emulator cấu hình sẵn; `npm run ios` cần iOS Simulator trên macOS. Expo Go chỉ dùng khi tương thích SDK và module đang dùng. Khi cần native module/config riêng, chuyển sang development build; đây là app phát triển riêng có thể cấu hình native. [Tài liệu Expo](https://docs.expo.dev/develop/development-builds/introduction/).

## Kết nối từ điện thoại

| Địa chỉ | Ý nghĩa |
| --- | --- |
| `localhost` trên điện thoại | Chính điện thoại, không phải máy chạy Laravel |
| IP LAN của máy tính | Dùng khi điện thoại cùng mạng và server lắng nghe trên giao diện mạng thích hợp |
| `10.0.2.2` trên Android Emulator chuẩn | Địa chỉ truy cập loopback máy host; thiết bị/emulator khác cần xác minh riêng |
| Domain HTTPS/WSS thử nghiệm | Dùng khi cần thiết bị ngoài LAN, HTTPS hoặc luồng thanh toán quay về |

Nguồn Android Emulator: [Networking](https://developer.android.com/studio/run/emulator-networking).

Backend local hiện được `start.bat` mở ở localhost; Reverb cũng cấu hình loopback theo [hợp đồng chat](../../docs/features/REALTIME_CHAT.md). Trước thử điện thoại, chọn IP/host và kiểm tra `/api/v1/health` từ thiết bị, rồi kiểm tra WSS/WS riêng. Tunnel HTTP cho payOS không đồng nghĩa đã expose Reverb.

Không tắt firewall/TLS toàn máy hoặc bỏ authorization để thử kết nối. Nếu HTTP local bị chính sách nền tảng chặn, dùng HTTPS thử nghiệm hoặc cấu hình chỉ dành cho development build. Không đưa ngoại lệ cleartext vào bản production theo mặc định.

## Cấu hình app dự kiến

MB1 đọc API URL; MB4 đã dùng các biến Reverb sau trong `.env.example`:

| Biến | Giá trị sử dụng |
| --- | --- |
| `EXPO_PUBLIC_API_URL` | URL tuyệt đối kết thúc `/api/v1`, ví dụ `https://api.example.com/api/v1` là placeholder |
| `EXPO_PUBLIC_REVERB_APP_KEY` | Public app key để kết nối Reverb |
| `EXPO_PUBLIC_REVERB_URL` | URL `ws://host:port` local hoặc `wss://host:port` phát hành; host phải truy cập được từ thiết bị |
| `EXPO_PUBLIC_REVERB_ORIGIN` | Origin công khai nằm trong allowed origins của Reverb, ví dụ `http://localhost` local |

`EXPO_PUBLIC_*` được đưa vào app và có thể đọc được, không dùng cho Gemini key, payOS key, Reverb secret hoặc token người dùng. [Tài liệu biến môi trường Expo](https://docs.expo.dev/guides/environment-variables/).

Không hardcode IP của máy hiện tại hoặc credentials thật trong Git. Dev/staging/production dùng API và dữ liệu tương ứng; app kiểm tra config thiếu và báo lỗi rõ, không tự chuyển sang production khi thiếu URL. Không gửi bearer sang host media/redirect khác mà chưa kiểm tra.

## Build và phân phối dự kiến

| Mức | Kết quả | Đã có? |
| --- | --- | --- |
| Metro/Expo Go | Xem app khi phát triển với runtime tương thích | Đã kiểm tra MB1 trên LDPlayer 9/Expo Go 57.0.2; chưa nghiệm thu điện thoại thật |
| MB5 native | Đăng ký/reset, đơn và chatbot/nháp AI qua Laravel | Đã kiểm tra trên LDPlayer/Android 9 với database QA riêng và nhà cung cấp giả lập; chưa nghiệm thu luồng dịch vụ thật |
| `expo export` | JavaScript/assets cho các nền tảng | Đã kiểm tra; không phải APK/IPA |
| Development build | Bản native phục vụ phát triển | Chưa cấu hình/build |
| Bản cài nội bộ | APK Android hoặc cơ chế phân phối iOS phù hợp | Chưa cấu hình/ký/phân phối |
| Store | Google Play/App Store | Chưa được yêu cầu phát hành |

Có thể chọn EAS Build hoặc công cụ native phù hợp; chưa chốt tài khoản/chi phí, chưa có `eas.json`, app identifier hoặc credentials ký. Android build local cần Android SDK/JDK tương thích; iOS build local cần macOS/Xcode. Không cài toolchain hoặc mở tài khoản có phí chỉ vì có tài liệu này. Tham khảo [EAS Build](https://docs.expo.dev/build/introduction/) khi thực sự chuẩn bị build.

## Điều kiện trước phát hành

- Chốt MB-D01/04: nền tảng/phiên bản OS nghiệm thu, tên app, Android package/iOS bundle ID, owner tài khoản, kênh phân phối và chi phí.
- HTTPS/WSS, API công khai hoạt động, kiểm tra quyền/token và không dùng tài khoản/dữ liệu demo nhạy cảm trong bản phát hành.
- App icon/splash chính thức, version/build number và môi trường API đúng; quyền camera/thư viện/thông báo chỉ xin khi chức năng cần.
- Hoàn tất ma trận [kiểm thử](KIEM_THU.md), kiểm tra cảnh báo dependencies; theo dõi 23 cảnh báo ở [bootstrap](../../docs/verification/MOBILE_BOOTSTRAP.md), không `audit fix --force` làm hỏng SDK để có số đẹp.
- Đối chiếu chính sách store hiện hành khi chuẩn bị nộp, khai báo dữ liệu/quyền riêng tư, yêu cầu tài khoản và phương thức thanh toán cho gói PT/chatbot. Tài liệu này chưa kết luận payOS phù hợp với mọi loại hàng hóa/kênh store.
- Ghi cách nâng cấp/rollback app và tương thích API: Backend phục vụ được bản app đang lưu hành; không xóa field/route ngay khi web đã đổi.

Push là phạm vi riêng theo MB-D03: cần cấu hình nền tảng, đăng ký thiết bị, thu hồi liên kết khi logout, xử lý delivery error và tránh lộ nội dung nhạy cảm. Dùng hướng dẫn hiện hành của [Expo Push](https://docs.expo.dev/push-notifications/overview/) khi triển khai; không coi chuông thông báo web là push đã có.

Tài liệu này không thực hiện build cloud, upload binary, ký app, đổi firewall hoặc phát hành store.
