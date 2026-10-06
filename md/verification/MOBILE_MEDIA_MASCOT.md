# Ảnh bài tập và mascot mobile — 06/10/2026

## Thay đổi

- `Mobile/src/components/TapLuyen.js`: `AnhBaiTap` dùng `expo-image` đang có trong dự án; thumbnail vuông 64/72, ảnh đầy đủ ở chi tiết. Dùng ảnh/GIF thật từ API cùng origin; giữ ghi công Gym visual. Thiếu ảnh và lỗi tải có thông báo riêng; màn chi tiết cho thử lại. GIF hướng dẫn chỉ phát khi người dùng bấm xem.
- `Mobile/src/utils/media.js`: đọc media trực tiếp của catalog/kết quả PT hoặc `noi_dung` snapshot giáo án/nhật ký. Chỉ nhận đường dẫn media catalog trên origin API. Không tra lại catalog để tự thay ảnh lịch sử.
- Các màn `Catalog`, `SoanGiaoAn`, `ChiTietGiaoAn`, `BuoiTuTap`, `KetQuaBuoiPt` dùng ảnh thật thay SVG quả tạ cố định. Soạn giáo án giữ metadata ảnh của bài vừa chọn để hiển thị; payload lưu vẫn dùng whitelist hiện tại.
- `Mobile/assets/mascot/`: sao chép nguyên GIF và ảnh tĩnh `fitforge-ai-gundam-slow` từ `FE/public/images/`; SHA-256 hai bản trùng nhau. Không thêm thư viện.
- `MascotTroLy` và `useChuyenDong`: GIF robot ở màn chào hội thoại AI và các lối vào AI trên tổng quan, giáo án, tin nhắn, hồ sơ. Khi rời màn, app vào nền hoặc bật giảm chuyển động, dùng ảnh tĩnh. Tùy chọn hội thoại có nút bật/dừng mascot. `HangMenu` thêm tùy chọn minh họa, giữ cách dùng hiện tại cho các menu khác.
- Không sửa Backend, authentication, quyền KH/PT, API, quota AI hoặc dữ liệu nghiệp vụ trong thay đổi này.

## Kiểm tra đã chạy

- `rtk proxy npm test` tại `Mobile/`: **51/51 PASS**; thêm 5 kiểm tra media origin, URL không hợp lệ, snapshot, bài thiếu media và metadata không lọt vào payload giáo án.
- Export **Android và web PASS**; có GIF/PNG mascot trong assets. Export là bundle, không phải APK.
- `git diff --check`: PASS.
- API catalog thật và ảnh JPEG trả 200 tại backend cổng 8001.
- Native: Expo Go SDK 57 trên LDPlayer 9 / Android 9, ảnh chụp 720 × 1280. Dùng phiên đã đăng nhập trên app chính cổng 8086; chỉ đọc các màn hiện có, không tạo hội thoại, không gửi câu hỏi AI, không lưu/chốt bài tập hoặc đăng xuất tài khoản.
- Đã mở thư viện và giáo án đang áp dụng: ảnh khác nhau đúng theo từng bài; ghi công hiển thị.
- Đã mở chi tiết `3/4 sit-up`: ảnh tĩnh, bật GIF và dừng về ảnh tĩnh. So sánh vùng ảnh giữa các thời điểm xác nhận GIF chuyển động.
- Đã mở hội thoại AI rỗng có sẵn: mascot hiển thị và chuyển động. So sánh vùng mascot ở hai thời điểm có khác biệt; bấm dừng, ba ảnh cách nhau 0,7 giây không đổi; bật lại được. So sánh ảnh chuyển sang RGB để không bỏ qua khác biệt vì alpha của screenshot.

Ảnh kiểm chứng: [thư viện](../../docs/verification/mobile-media-mascot/catalog.png), [giáo án](../../docs/verification/mobile-media-mascot/plan.png), [GIF bài tập](../../docs/verification/mobile-media-mascot/exercise-gif.png), [mascot đang chạy](../../docs/verification/mobile-media-mascot/mascot-running.png), [mascot đã dừng](../../docs/verification/mobile-media-mascot/mascot-paused.png).

## Giới hạn và cách xem

Bài do Admin tạo hoặc snapshot lịch sử không có media vẫn hiện **Chưa có ảnh**; không tự gán ảnh bài khác hoặc sửa dữ liệu cũ. Ảnh bài tập cần kết nối Backend; mascot nằm trong app. Chưa nghiệm thu điện thoại thật; không tuyên bố đã kiểm tra lại toàn bộ luồng nghiệp vụ hoặc mọi cấu hình trợ năng/native lifecycle trong lần thay đổi này.

Trong mobile đang chạy: **Giáo án → Thư viện** hoặc mở giáo án → mở buổi để xem ảnh. **Hồ sơ → Trợ lý AI → hội thoại rỗng hiện có** để xem mascot; nút khiên ở đầu hội thoại mở tùy chọn bật/dừng.
