# MB2 — Tổng quan, lịch hẹn và học viên trên mobile

Ngày kiểm chứng: **05/10/2026**. Phạm vi KH/PT trên Expo Go SDK 57, LDPlayer 9/Android 9, Laravel và MariaDB 10.4.32. MB2 đã hoàn thành phần tích hợp; chưa phải nghiệm thu toàn bộ app hoặc điện thoại thật.

## Thay đổi và cách xem

- Tổng quan KH/PT lấy dữ liệu thật: gói/lượt/PT, lịch sắp tới, thống kê tự tập của KH; lịch hôm nay, việc cần xử lý, học viên và thống kê tuần của PT. Phân biệt buổi tự tập và lịch PT.
- Tab **Lịch tập** có ngày/trạng thái, chi tiết, làm mới và phân trang. KH mở **Đặt lịch với PT**, chọn giờ, gửi yêu cầu hoặc hủy có lý do. PT mở **Quản lý khung giờ**, tạo giờ 60 phút, đóng/mở lại giờ được phép; xác nhận/từ chối/hoàn thành/vắng mặt từ chi tiết lịch.
- Tab **Học viên** dành cho PT có tìm kiếm, phân trang và hồ sơ chỉ đọc. ID là hồ sơ KH; quyền phân công do Backend kiểm tra.
- Màn hình xử lý loading/empty/error, mất mạng và xung đột. Nút hành động lịch lấy từ `hanh_dong` của máy chủ; tải lại sau thao tác, khi quay về màn và khi app về foreground. Có nút/kéo làm mới khi người khác cập nhật lịch; chưa dùng socket cho module lịch.

Source chính: [service huấn luyện](../../Mobile/src/services/huanLuyenService.js), [tổng quan](../../Mobile/src/screens/TongQuanTaiKhoan.js), [lịch](../../Mobile/src/screens/Lich/LichHen.js), [đặt lịch](../../Mobile/src/screens/Lich/DatLich.js), [chi tiết](../../Mobile/src/screens/Lich/ChiTietLich.js), [khung giờ](../../Mobile/src/screens/PT/KhungGio.js), [học viên](../../Mobile/src/screens/PT/HocVien.js), [hồ sơ học viên](../../Mobile/src/screens/PT/HoSoHocVien.js). Component dùng chung ở `Mobile/src/components/HuanLuyen.js`; hooks ở `Mobile/src/hooks/`.

Dùng lại API và quy tắc đã có, không thêm endpoint, migration hoặc thay nghiệp vụ web. `expo-crypto` tạo UUID trên native. UUID giữ nguyên khi gửi lại cùng khung sau timeout/mất phản hồi; đổi khung tạo UUID mới. Hai lần bấm đồng thời chỉ gửi một yêu cầu. API client giữ metadata phân trang và phiên hiện tại; phản hồi từ phiên cũ không ghi vào người mới. Không lưu danh sách/hồ sơ riêng trên đĩa.

## Kiểm tra tự động thực sự chạy

| Kiểm tra | Kết quả | Môi trường/phạm vi |
| --- | --- | --- |
| `rtk proxy npm test` tại Mobile | **14/14 đạt** | Node 22.20.0; vòng đời phiên, dịch vụ giữ metadata, 401/403, phản hồi muộn, ngày/giờ Việt Nam, UUID retry và bấm liên tiếp |
| `rtk proxy php artisan test --filter="LichHenTest\|TongQuanTest\|PhienMobileTest"` tại BE | **44 tests / 624 assertions đạt** | MariaDB, database kiểm thử riêng; có ca bearer KH/PT mở giờ → đặt/retry → danh sách/hồ sơ học viên → tổng quan → xác nhận → hoàn thành/retry, quota còn 7/8 |
| Các ca lịch hiện có trong cùng lượt chạy | **Đạt** | Quyền sở hữu, hạn 4h/2h/24h, quota, rollback, hủy/vắng mặt không trừ; hai process cạnh tranh slot và hoàn thành không tạo/trừ lặp |
| `rtk proxy npx expo install --check` | **Đạt** | Dependencies tương thích SDK 57 |
| `rtk proxy npx expo-doctor` | **21/21 đạt** | Cấu hình Expo |
| `rtk proxy npx expo export --platform all --output-dir .expo/mb2-export` | **Đạt** | Bundle Android, iOS và web; không phải APK/IPA |
| Prettier `--check --single-quote --no-semi src tests` và Pint cho LichHenTest | **Đạt** | Source/kiểm thử thay đổi |

Chỉ thêm ca tích hợp vào [LichHenTest](../../BE/tests/Feature/LichHenTest.php); không chạy lại toàn bộ Backend trong MB2. Các kiểm tra cấu hình/bundle không thay thế thao tác native.

## Kiểm tra trực tiếp trên LDPlayer

1. KH đang dùng app đặt một khung trống; PT đăng nhập trên cùng giả lập, nhìn thấy yêu cầu ở Tổng quan và xác nhận trong chi tiết. PT xem được học viên/hồ sơ thật và mở/đóng khung giờ mới. Lịch thử ban đầu ID 94 đã được hủy qua service, giữ lịch sử và không trừ buổi; khung 08:00 ngày 06/10 do kiểm thử tạo đã đóng.
2. Với database riêng `kiem_tra_mobile_mb2_*` và API tạm ở cổng 8002: KH đăng nhập, thấy gói 8/8, đặt khung 13:00–14:00; phía PT xác nhận bằng bearer API. App KH bấm cập nhật và hiển thị **Đã xác nhận**; form hủy chặn lý do trống, sau nhập lý do thì hủy thành công.
3. PT đăng nhập trên LDPlayer với hai lịch fixture đã kết thúc: ghi **Hoàn thành** cho một lịch, **Vắng mặt** có lý do cho lịch kia. Kiểm tra database: một `HOAN_THANH` có `tieu_hao_luc`, một `VANG_MAT` và một `DA_HUY` đều không có tiêu hao; gói còn **7**. Các nút ghi kết quả biến mất sau chuyển trạng thái.
4. Kiểm tra KH chưa có phân công/gói: hiển thị lý do từ máy chủ, không có giờ để đặt. Kiểm tra sáng/tối, màn khoảng 375 điểm với chữ 130% trên tổng quan/khung giờ/lịch chọn ngày: chữ xuống dòng, form và lịch cuộn được. App đang khóa hướng dọc; chưa nghiệm thu ngang hoặc screen reader đầy đủ. Log native được đọc sau luồng không có lỗi ReactNativeJS/AndroidRuntime trong phần đọc.

Fixture không gọi thanh toán thật. Đã dừng server 8002, xóa đúng database riêng, bỏ chuyển tiếp 8002 và khôi phục chính xác API URL trước kiểm thử. Density/font scale giả lập đã khôi phục. Không reset/seed lại database ứng dụng. Tài khoản fixture không còn tồn tại sau dọn.

Ảnh kiểm chứng:

- [KH nhận xác nhận](screenshots/mobile-mb2-kh-da-xac-nhan.png), [KH hủy lịch](screenshots/mobile-mb2-kh-huy-lich.png).
- [PT hoàn thành](screenshots/mobile-mb2-pt-hoan-thanh.png), [PT vắng mặt](screenshots/mobile-mb2-pt-vang-mat.png).
- [Khung giờ](screenshots/mobile-mb2-pt-khung-gio.png), [tổng quan chữ lớn](screenshots/mobile-mb2-pt-chu-lon.png), [lịch chọn ngày chữ lớn](screenshots/mobile-mb2-chon-ngay-chu-lon.png).

## Giới hạn và bước tiếp theo

- Chưa kiểm tra trên điện thoại Android thật, chưa tạo APK/IPA, chưa phát hành store. iOS mới export, chưa nghiệm thu native iOS trong MB2.
- Mất mạng/response muộn và double-submit được kiểm tra bằng dependencies giả/Backend; chưa có biên bản mô phỏng mạng yếu toàn bộ trên điện thoại thật.
- Giáo án, lịch tự tập, nhật ký, số đo/tiến độ native thuộc MB3; chat/thông báo native thuộc MB4. Push và phát hành vẫn theo quyết định chờ chốt. Bản Duyệt giao diện minh họa giữ riêng.
- Cách chạy/tài khoản demo local: [Mobile README](../../Mobile/README.md). Khởi động Laravel và Metro, mở lại Expo Go rồi đăng nhập tài khoản KH/PT của mình; vào Tổng quan/Lịch tập/Học viên để xem MB2.
