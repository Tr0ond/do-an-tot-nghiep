# Kiểm chứng UI1 — giao diện mobile đầu tiên

Ngày: 05/10/2026. Môi trường: Windows, Node 22.20.0, Expo SDK 57, React Native 0.86.3, React 19.2.3. Bản xem chạy qua Expo Web tại cổng 8081. Sau đó đã kiểm tra khởi động trên LDPlayer 9/Android 9 với Expo Go 57.0.2; chưa nghiệm thu đầy đủ thao tác native hoặc điện thoại thật.

## Phạm vi đã thực hiện

- `Mobile/App.js`: nạp 5 độ đậm Be Vietnam Pro, safe area, trạng thái xem trước và điều hướng.
- `Mobile/src/components/GiaoDien.js`, `src/theme/`: nút/input/thẻ/avatar minh họa/modal, bảng màu sáng/tối, vùng chạm và bố cục dùng chung.
- `src/navigation/DieuHuong.js`: stack, 5 tab KH/PT, reset navigation khi đổi bản xem; ẩn nút thoát nhanh trong lúc sửa hồ sơ để dùng cảnh báo bỏ thay đổi.
- `src/screens/Auth/DangNhap.js`: kiểm tra email/mật khẩu, hiện/ẩn mật khẩu, thông báo chưa tích hợp; xóa mật khẩu sau khi bấm gửi hợp lệ. Duyệt giao diện bằng nút riêng, không giả đăng nhập.
- `src/screens/KhachHang/TongQuan.js`, `src/screens/PT/TongQuan.js`: hai tổng quan theo actor, lịch/thống kê/lối tắt bằng dữ liệu minh họa.
- `src/screens/CaNhan/HoSo.js`, `SuaHoSo.js`: hồ sơ theo actor, đổi theme, editor dùng trường tương ứng FormRequest Backend; kiểm tra tên/ngày sinh/khung giờ, cảnh báo rời form chưa lưu.
- `src/contexts/`, `src/data/`: dữ liệu chỉ giữ trong bộ nhớ; thoát bản xem rồi mở lại lấy mẫu mới. Không lưu token/mật khẩu/hồ sơ vào ổ đĩa và không gọi API.
- `package.json`, lockfile, `app.json`: React Navigation, safe area/screens, font/icon và plugin font. Tài liệu ghi lại C41 và trạng thái UI1.

## Căn cứ thiết kế

Stitch project `12280986189063614412`, **Tr0ond Fitness — Mobile App (KH · PT)**. Các màn tham chiếu: login `bdff8ab38a894b9da0f1e9db5562cfa1`, KH `8827d766a6924f7abeb82ef20c3e56d8`, PT `f711306cf6024f1db9a1463c2327b159`, profile `b94a0ed56ab343fca10719316a9b28dc`, editor KH `6f61490ab7f1470ebb98ac2bf3994e45`, editor PT `16526a877357407789ba26330db306dd`.

Giữ màu teal/lime, thẻ bo 8px, font Be Vietnam Pro và bố cục 5 tab. Thay ảnh người bằng avatar chữ cái minh họa. Không triển khai những chi tiết thiết kế chưa thuộc hợp đồng hiện có như 2FA, sửa email, số điện thoại bắt buộc, AI tự chia sẻ/áp dụng hoặc dữ liệu calorie suy đoán. Số đo được quản lý ở màn riêng; sửa hồ sơ không sửa số đo.

## Kiểm tra đã chạy

| Kiểm tra | Kết quả thực tế |
| --- | --- |
| `npx expo install --check` | Dependencies tương thích Expo, up to date |
| `npx expo-doctor` | 21/21 checks passed |
| `npx expo export --platform all` sau chỉnh sửa cuối | Android/iOS/web đều export thành công; web khoảng 874KB, native khoảng 2MB/bundle |
| Formatter theo CODE_STYLE | Đã format App và toàn bộ `src/**/*.js`: 2 spaces, single quote, không semicolon, LF |
| Login trống/sai; hiện mật khẩu; gửi mẫu hợp lệ | Có lỗi inline, đổi hiện/ẩn, thông báo chưa kết nối và xóa mật khẩu |
| KH/PT và tab | Vai trò có tab đúng; chưa dựng thì hiện thông báo, không thực hiện mutation |
| Sửa KH | Tên rỗng/ngày không hợp lệ bị chặn; lưu mẫu trở về hồ sơ và thông báo dữ liệu chỉ trong bản xem |
| Bỏ sửa KH | Quay lại hiện cảnh báo; tiếp tục sửa giữ input; bỏ thay đổi giữ hồ sơ đã lưu trước đó |
| Sửa PT | Có chuyên môn/giới thiệu, không có trường riêng KH; quay lại khi chưa sửa không cảnh báo; lưu mẫu cập nhật hồ sơ |
| Theme và đổi vai trò | Chế độ tối đổi màu; thoát rồi mở KH lại lấy hồ sơ mẫu ban đầu, không mang hồ sơ PT sang KH |
| Viewport | Đã quan sát 390×844, 375×812; kiểm tra PT ở 768×1024, 1440×900, 844×390; không có phần tử tràn ngang ở các lần đo |
| Console Web | Không thấy runtime error; có cảnh báo deprecated `props.pointerEvents` từ thư viện Web, chưa ảnh hưởng thao tác đã kiểm tra |
| Tài liệu | 12 Markdown được kiểm tra UTF-8 và link nội bộ; không có link hỏng. `git diff --check` không có lỗi whitespace |
| Khởi động Android | LDPlayer 9, Android 9, màn 720×1280, Expo Go 57.0.2: đã mở app qua địa chỉ LAN, bundle tải xong và màn Đăng nhập hiển thị. Chưa coi đây là nghiệm thu các luồng Android |

Ảnh chụp từ bản chạy thực tế ở viewport 390×844:

- [Đăng nhập](screenshots/mobile-ui1-login.jpg)
- [Tổng quan khách hàng](screenshots/mobile-ui1-kh.jpg)
- [Tổng quan huấn luyện viên](screenshots/mobile-ui1-pt.jpg)

[Ảnh khởi động trên LDPlayer 9](screenshots/mobile-ui1-ldplayer-login.png) chụp màn Android thật của máy giả lập, khác các ảnh viewport Web ở trên. Logcat giới hạn ReactNativeJS/AndroidRuntime sau khởi động không trả thông báo lỗi trong mẫu đọc; kết quả này không chứng minh mọi thao tác đều không có lỗi.

## Giới hạn và bước tiếp theo

UI1 là bản xem giao diện, chưa phải MB1/MB2 hoàn chỉnh. Chưa có Laravel login, token/SecureStore, request hồ sơ thật, lịch/giáo án/chat/AI/thanh toán. Không sửa `FE/`, `BE/`, database hoặc migration trong bước này. Chính sách phiên 30 ngày/nhiều thiết bị/logout phiên hiện tại mới được ghi nhận theo C41.

Chưa kiểm tra bàn phím Android, nút Back phần cứng, safe area thiết bị thật, TalkBack, cỡ chữ hệ thống, foreground/background hoặc bản APK. LDPlayer đã chạy màn đầu tiên, chưa thay nghiệm thu điện thoại thật. Export không tạo APK/IPA. Các cảnh báo npm audit (23 mục từ bộ khung trước, 7 moderate/16 high) chưa được xử lý bằng nâng phiên bản cưỡng bức trong đợt UI này; cần đánh giá trước phát hành.

Mở terminal tại `Mobile/`, chạy `npm start`, dùng Expo Go tương thích SDK 57 trên Android cùng Wi-Fi để quét QR. Ở màn Đăng nhập, cuộn xuống phần Duyệt giao diện. Xem [hướng dẫn chạy](../../Mobile/README.md). Sau khi kiểm tra UI thiết bị, triển khai MB1 với xác thực và hồ sơ thật theo [XAC_THUC](../../Mobile/docs/XAC_THUC.md).
