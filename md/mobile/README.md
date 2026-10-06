# FitForge Mobile — React Native + Expo

App dành cho `KHACH_HANG` và `HUAN_LUYEN_VIEN`, được khởi tạo theo C40 ngày 05/10/2026. UI1 dựng giao diện theo Stitch tạm chốt; MB1 nối xác thực/hồ sơ, MB2 nối tổng quan/lịch hẹn/học viên, MB3 nối catalog/giáo án/tự tập/nhật ký/số đo, MB4 nối chat chữ/ảnh và thông báo, MB5 nối đăng ký/khôi phục, gói/thanh toán và chatbot vào Laravel. Website Vue ở `../FE/`, Backend ở `../BE/`; Admin tiếp tục dùng web.

## Màn đăng nhập hiện tại

Ngày 06/10/2026, theo yêu cầu chủ dự án, màn Đăng nhập đã bỏ khung **Tài khoản demo**, hai nút Học viên/Huấn luyện viên và mô tả bản xem trước. Dùng tài khoản đã đăng ký để đăng nhập; các đường dẫn Đăng ký, Quên mật khẩu, Xem gói tập và Trợ giúp vẫn có. Bản xem UI1 trong các biên bản cũ là bằng chứng giai đoạn trước, không còn điểm vào từ màn đăng nhập.

Form đăng nhập trên Android gửi yêu cầu tới Laravel. Phiên cố định 30 ngày, nhiều thiết bị, lưu bằng SecureStore; đăng xuất chỉ thu hồi phiên thiết bị hiện tại. Tổng quan KH/PT dùng số liệu thật. KH đặt/hủy lịch; PT mở/đóng giờ, xác nhận/từ chối và ghi hoàn thành/vắng mặt theo quyền máy chủ, xem/tìm học viên và hồ sơ chỉ đọc. C44 bổ sung tín hiệu lịch private qua Reverb để tự tải lại; giữ tải bù khi trở về app/kéo làm mới và polling 45 giây khi app mở. Trình duyệt dùng kiểm tra UI công khai; đăng nhập thật cần Expo Go hoặc bản cài Android. Điểm vào bản demo đã bỏ. Xem [MOBILE_MB2](../verification/MOBILE_MB2.md), [MOBILE_MB1](../verification/MOBILE_MB1.md).

## Chạy với LDPlayer hiện tại

Trong `BE/`, chạy `rtk proxy php artisan migrate`, rồi `rtk proxy php artisan serve --host=127.0.0.1 --port=8000`. Migration bổ sung bảng token; không chạy `migrate:fresh` trên dữ liệu hiện có.

Trong `Mobile/`, sao chép `.env.example` thành `.env.local`, đặt `EXPO_PUBLIC_API_URL=http://127.0.0.1:8000/api/v1`. Khi LDPlayer đã mở, chạy từ gốc dự án:

```powershell
rtk proxy D:/LDPlayer/LDPlayer9/adb.exe -s emulator-5554 reverse tcp:8000 tcp:8000
rtk proxy D:/LDPlayer/LDPlayer9/adb.exe -s emulator-5554 reverse tcp:8081 tcp:8081
```

Trong `Mobile/`, chạy `rtk proxy npx expo start --web --lan --port 8081`. Sau khi Metro sẵn sàng, mở Expo Go từ gốc:

```powershell
rtk proxy D:/LDPlayer/LDPlayer9/adb.exe -s emulator-5554 shell am start -a android.intent.action.VIEW -d exp://127.0.0.1:8081 -p host.exp.exponent
```

ADB/serial trên dành cho máy hiện tại; kiểm tra thiết bị khi đổi máy. Demo local đã có: `khachhang@example.test`, `pt@example.test`, mật khẩu ban đầu `Demo123456!` nếu chưa đổi. Xem [seeder](../backend/SEEDERS.md); không seed/reset lại chỉ để thử.

## Tập luyện đã tích hợp — MB3

- KH mở tab **Giáo án**: tự tạo nháp, chọn bài từ thư viện, sửa rồi áp dụng; xem/xác nhận đề xuất PT. Các thao tác gửi, hủy, lưu trữ, ẩn/hiện dựa trên quyền/trạng thái Backend.
- Mở **Lịch tự tập & nhật ký** từ Tổng quan, Lịch tập hoặc giáo án đang dùng: chọn ngày/ngày tập, bắt đầu, ghi từng hiệp thực tế, lưu nháp rồi hoàn thành. Chỉ tiêu giáo án không tự biến thành kết quả; tự tập không trừ buổi PT.
- Mở **Số đo & tiến độ** từ Tổng quan hoặc **Chỉ số cơ thể** trong Cá nhân: ghi/sửa cân nặng, chiều cao và ghi chú; xem lịch sử, BMI và biểu đồ những ngày thực sự có số đo.
- PT vào **Học viên → Hồ sơ**: tạo/gửi giáo án (có sao chép mẫu đã duyệt), tạo/xem lịch tự tập, nhận xét phiên hoàn thành và đọc số đo. PT không ghi nhật ký hay số đo thay KH.

Chi tiết kiểm tra và giới hạn: [MOBILE_MB3](../verification/MOBILE_MB3.md). Bản cài và nghiệm thu điện thoại thật thuộc MB6.

## Chat và thông báo đã tích hợp — MB4

Đăng nhập KH/PT, mở **Tin nhắn** để tìm/chọn hội thoại, gửi chữ hoặc tối đa 4 ảnh JPG/PNG/WebP (5 MB/ảnh). App tải tin mới qua Reverb + HTTP, tải bù khi nối lại; gửi lại cùng tin giữ UUID. KH xem chat phân công cũ ở chế độ chỉ đọc; PT cũ mất quyền, PT mới có hội thoại riêng.

Chuông ở Tổng quan/Tin nhắn mở danh sách thông báo thật. Bấm tin đánh dấu đọc trước khi mở đúng tài nguyên; đích chưa có trên app báo rõ. Push khi app tắt được tích hợp theo C44, cần cấu hình dịch vụ và bản cài Android riêng. Xem [MOBILE_MB4](../verification/MOBILE_MB4.md) và [tiện ích thiết bị](TIEN_ICH_THIET_BI.md).

Trong `.env.local`, đặt `EXPO_PUBLIC_REVERB_URL=ws://<HOST_THIET_BI_TRUY_CAP_DUOC>:8080`, `EXPO_PUBLIC_REVERB_APP_KEY` bằng public key của Reverb/web và `EXPO_PUBLIC_REVERB_ORIGIN=http://localhost` nếu server cho phép localhost. Không điền secret. Bản phát hành cần `wss://`/HTTPS. Thiếu socket vẫn có HTTP polling mỗi 45 giây khi app mở.

Trong BE mở `rtk proxy php artisan reverb:start --host=0.0.0.0 --port=8080` cho mạng local (hoặc `127.0.0.1` với adb reverse). LDPlayer dùng loopback cần thêm `rtk proxy D:/LDPlayer/LDPlayer9/adb.exe -s emulator-5554 reverse tcp:8080 tcp:8080`. Điện thoại qua Wi-Fi dùng host LAN phù hợp; khởi động lại Metro sau đổi env. Upload 4 ảnh cần server nhận body 24 MB/file 5 MB; local có `serve:local` theo [hợp đồng chat](../features/REALTIME_CHAT.md).

## Đăng ký, gói và trợ lý AI — MB5

- Ở Đăng nhập, mở **Tạo tài khoản**, **Quên mật khẩu**, **Trợ giúp** hoặc **Xem gói tập**. Đăng ký chỉ tạo KH, sau đó đăng nhập riêng. Đặt lại mật khẩu bằng email, mã/liên kết nhận qua email và mật khẩu mới; thành công thu hồi các phiên cũ.
- KH vào **Cá nhân → Gói của tôi & đơn hàng** để xem gói/quyền lợi, tạo đơn từ catalog và kiểm tra snapshot giá. Mở thanh toán ở trình duyệt, quay lại app rồi bấm **Kiểm tra thanh toán**; quyền lợi chỉ lấy từ Backend. App giữ UUID khi thử lại đơn chưa rõ kết quả.
- KH mở **Trợ lý AI** từ Tổng quan/Cá nhân: tạo hội thoại, xem hạn mức, gửi câu hỏi. Chia sẻ dữ liệu cá nhân mặc định tắt. Nút soạn giáo án chỉ soạn câu hỏi; nháp AI trả về mở trong màn giáo án MB3 để xem/sửa/xác nhận, không tự áp dụng.
- FAQ lấy nội dung công khai đã xuất bản. Thông báo đơn mở đúng đơn theo quyền của KH.

Đã kiểm tra các luồng trên LDPlayer với database QA riêng và nhà cung cấp payOS/Gemini/email giả lập. Chưa chuyển tiền, gọi Gemini thật hoặc nghiệm thu email thực tế. Chi tiết và ảnh: [MOBILE_MB5](../verification/MOBILE_MB5.md).

## Tài liệu triển khai

Đọc [chỉ mục Mobile](#chỉ-mục-tài-liệu-mobile) để xem phạm vi KH/PT, kiến trúc, xác thực, tích hợp API, lộ trình, kiểm thử và phát hành. Hướng dẫn cho các lần sửa mã nằm tại [AGENTS.md](AGENTS.md). Các tài liệu phân biệt rõ hiện trạng, đề xuất và quyết định chưa chốt; chưa có chức năng mới được triển khai chỉ từ việc soạn tài liệu.

## Công nghệ và phiên bản

- JavaScript, mẫu chính thức Expo `blank`.
- Expo SDK 57 (`~57.0.26`), React Native `0.86.3`, React `19.2.3`.
- npm với `package-lock.json` riêng. Máy khởi tạo: Windows, Node `22.20.0`, npm `10.9.3`.
- Có React Native Web để xem trước bộ khung trên trình duyệt.

## Chạy trên điện thoại

Trên Windows, nhấp đúp [start-mobile.bat](../../start-mobile.bat) ở gốc dự án. File tự lấy IP Wi-Fi/LAN, cập nhật API và URL Reverb trong `.env.local` (giữ các biến khác), mở Backend/chat nếu chưa chạy trên LAN, rồi hiện QR Expo ở cổng cố định **8082**. Bật MySQL trước và giữ cửa sổ chạy; nhấn `Ctrl+C` để dừng Expo cùng các server do lần chạy này mở. Server đã chạy từ trước không bị dừng. Hỗ trợ thực hiện bằng [script khởi động](../../scripts/start-mobile.ps1).

Chạy `start-mobile.bat --check` để chỉ kiểm tra công cụ, thư viện, IP và cổng, không sửa env hoặc mở server. Nếu cổng Expo đã bị chiếm, dừng phiên cũ hoặc dùng `start-mobile.bat -ExpoPort 8083`; file không tự chuyển cổng. Có nhiều card mạng thì chọn bằng `start-mobile.bat -Ip <IPv4 máy tính>`. Nếu Reverb cũ chỉ nghe localhost, dừng riêng cửa sổ Reverb đó rồi mở lại file này; dùng một server Reverb nhận cả LAN và loopback để app/web nhận cùng sự kiện.

Điện thoại cần cùng Wi-Fi; iPhone cần quyền Mạng cục bộ của Expo Go và cùng tài khoản Expo với CLI. File không cài thư viện, chạy migrations/seed, đổi firewall hay mở scheduler/ngrok. Các tiến trình scheduler và webhook thanh toán vẫn chạy riêng theo `start.bat`. Log Backend/chat nằm trong `Mobile/.expo/launcher/`. Cấu hình này dành cho mạng local; bản phát hành cần HTTPS/WSS.

Mở terminal tại thư mục này rồi chạy:

```powershell
npm start
```

Dùng Expo Go tương thích SDK 57. Điện thoại thật có thể dùng USB với `adb reverse` đúng serial hoặc cùng Wi-Fi: đổi API URL sang IP LAN, Laravel lắng nghe trên giao diện phù hợp, quét QR Metro. `localhost` trên điện thoại trỏ tới điện thoại. Khởi động lại Metro sau đổi `.env.local`. Bản phát hành yêu cầu HTTPS. Xem [môi trường](MOI_TRUONG_PHAT_HANH.md). Chưa cấu hình development build.

### iPhone hoặc điện thoại thật qua Wi-Fi

1. Kết nối điện thoại và máy tính cùng Wi-Fi; lấy IPv4 của máy tính bằng `rtk proxy ipconfig`.
2. Trong `BE/`, chạy `rtk proxy php artisan serve:local --host=<IP_WIFI_MAY_TINH> --port=8000 --tries=1`. Thay `<IP_WIFI_MAY_TINH>` bằng IP thực tế. Backend do `start.bat` mở ở localhost chưa nhận kết nối từ điện thoại; có thể giữ server đó và mở thêm server trên IP Wi-Fi.
3. Trong `Mobile/.env.local`, đặt `EXPO_PUBLIC_API_URL=http://<IP_WIFI_MAY_TINH>:8000/api/v1`, rồi khởi động lại Metro bằng `rtk proxy npx expo start --lan` và quét QR.
4. Mở `http://<IP_WIFI_MAY_TINH>:8000/api/v1/health` trên trình duyệt điện thoại để kiểm tra đường tới Backend trước khi đăng nhập. Nếu không mở được, kiểm tra cùng mạng, server và quyền truy cập qua Windows Firewall; không tắt firewall toàn máy.

Expo Go trên iPhone yêu cầu đăng nhập cùng tài khoản Expo với CLI trên máy tính: chạy `rtk proxy npx expo login`, rồi đăng nhập tài khoản đó trong Expo Go. Đây là tài khoản Expo, khác tài khoản KH/PT dùng đăng nhập ứng dụng. Xem [hướng dẫn Expo](https://docs.expo.dev/troubleshooting/expo-go-sign-in-required/).

`adb reverse` chỉ áp dụng cho thiết bị Android đã kết nối, không chuyển tiếp cổng cho iPhone. Sau khi đổi IP/mạng, cập nhật URL và chạy lại server/Metro. Khi quay lại cấu hình giả lập với `adb reverse`, có thể đặt API URL về `http://127.0.0.1:8000/api/v1` như phần LDPlayer phía trên.

Trên máy clone dự án, chạy `npm ci` trong `Mobile/` trước. Máy hiện tại đã cài dependencies. `start.bat` ở gốc vẫn chạy hệ thống web; mobile khởi động riêng.

Các lệnh khác:

```powershell
npm run android
npm run web
```

`android` cần máy ảo Android hoặc thiết bị Android kết nối đã được cấu hình. `web` mở bản xem trước trên trình duyệt. Lệnh `npm run ios` mở iOS Simulator và cần macOS; Windows có thể dùng điện thoại với Expo Go tương thích.

## Cấu trúc

- `App.js`: nạp font, safe area, context và điều hướng gốc.
- `src/navigation/`: stack và 5 tab riêng cho KH/PT.
- `src/screens/`: Auth, tổng quan/hồ sơ, huấn luyện, tập luyện/số đo, trao đổi và HoanThien (gói/đơn/FAQ/AI).
- `src/components/`, `src/theme/`: nút, thẻ, input, màu sáng/tối và font Be Vietnam Pro.
- `src/contexts/`, `src/data/`: phiên thật, theme và bản xem minh họa tách biệt.
- `src/services/`, `src/utils/`, `src/config/`, `src/hooks/`: API tài khoản/huấn luyện, SecureStore, vòng đời phiên, tải dữ liệu theo focus, chặn gửi trùng, thời gian Việt Nam và URL công khai.
- `tests/`: kiểm thử phiên, phản hồi cũ, ngày/giờ, payload tập luyện, chat, URL thanh toán/reset, quota/UUID retry bằng dependencies giả; không thay nghiệm thu native.
- `index.js`: đăng ký component gốc.
- `app.json`: cấu hình Expo, biểu tượng và nền tảng.
- `assets/`: ảnh mặc định từ template, chưa phải nhận diện chính thức.
- `package.json`, `package-lock.json`: lệnh chạy và phiên bản thư viện.

`node_modules/`, `.expo/`, `dist/` và các thư mục native được sinh tự động đã được loại khỏi Git. Khi thêm nghiệp vụ, tách màn hình/component và `services/` theo nhu cầu, giữ quy ước tên tiếng Việt không dấu. API key AI và secrets chỉ đặt ở Backend; app không tự quyết định quyền hoặc trạng thái thanh toán.

## Kết quả tập cùng PT (C42)

Tài khoản thật → **Lịch tập** (buổi cũ ở **Lịch sử**) → chọn lịch → **Kết quả tập cùng PT**. PT chọn bài từ catalog, nhập từng hiệp/số lần/tạ/nghỉ, ghi chú và nhận xét, **Lưu nháp**, rồi **Chốt kết quả** khi server cho phép. KH chỉ xem. Tạ bỏ trống là chưa ghi, khác 0 kg. Lưu/chốt không tự hoàn thành lịch hoặc trừ lượt; PT xác nhận hoàn thành riêng ở chi tiết lịch hẹn.

Màn hình giữ nguyên nội dung đã gửi khi mất phản hồi và khóa sửa đến khi thử lại. Phiên bản cũ yêu cầu tải lại có xác nhận bỏ nháp; không tự ghi đè. Dùng cùng API và dữ liệu với web, không có hàng đợi ghi offline. [Hợp đồng](../features/KET_QUA_BUOI_PT.md), [kiểm chứng Android](../verification/KET_QUA_BUOI_PT_MOBILE.md).

## Ảnh bài tập và mascot AI

Thư viện, giáo án và buổi tập dùng ảnh thật từ catalog/snapshot; bài thiếu media có thông báo riêng. Trợ lý AI dùng GIF robot giống web, có tùy chọn dừng chuyển động trong hội thoại và tôn trọng cài đặt giảm chuyển động. [Kiểm chứng Android và ảnh chụp](../verification/MOBILE_MEDIA_MASCOT.md).

## Kiểm tra bộ khung

```powershell
rtk proxy npm test
rtk proxy npx expo install --check
rtk proxy npx expo-doctor
rtk proxy npx expo export --platform all
```

Export tạo bundle và assets trong `dist/`, không tạo APK/IPA và không chứng minh app đã chạy trên thiết bị. Kiểm chứng chức năng theo [biên bản Mobile](../verification/README.md#mobile), không dùng lần export khởi tạo làm bằng chứng nghiệm thu.

Nguồn: [create-expo-app](https://docs.expo.dev/more/create-expo/).

## Chỉ mục tài liệu Mobile

Tài liệu đã gom vào `md/mobile/`; các lệnh chạy trong README vẫn thực hiện từ thư mục gốc hoặc `Mobile/` như ghi ở từng bước.

| Chủ đề | Tài liệu |
| --- | --- |
| Phạm vi KH/PT | [PHAM_VI.md](PHAM_VI.md) |
| Kiến trúc, vòng đời và state | [KIEN_TRUC.md](KIEN_TRUC.md) |
| Phiên 30 ngày và xác thực | [XAC_THUC.md](XAC_THUC.md) |
| Ánh xạ API | [TICH_HOP_API.md](TICH_HOP_API.md) |
| Lộ trình, phần còn phải nghiệm thu | [LO_TRINH.md](LO_TRINH.md) |
| Kiểm thử Android | [KIEM_THU.md](KIEM_THU.md) |
| Môi trường/build/phát hành | [MOI_TRUONG_PHAT_HANH.md](MOI_TRUONG_PHAT_HANH.md) |
| Theme/link/realtime/push C44 | [TIEN_ICH_THIET_BI.md](TIEN_ICH_THIET_BI.md) |
| Quy tắc nghiệp vụ chung | [PROJECT_RULES](../PROJECT_RULES.md), [DECISIONS](../DECISIONS.md), [features](../features/README.md) |
| Biên bản đã chạy | [verification](../verification/README.md) |

Đăng nhập không còn Tài khoản demo. Push thật cần EAS/FCM/bản cài riêng, chưa nghiệm thu trong Expo Go; verified HTTPS App Links và phát hành store còn cần môi trường/quyết định tương ứng. C44 là nguồn hiện trạng mới cho theme/link/realtime, các đoạn MB1–MB5 ghi lịch sử từng giai đoạn.
