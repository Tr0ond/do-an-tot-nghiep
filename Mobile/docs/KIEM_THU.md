# Kế hoạch kiểm thử mobile

**Trạng thái:** MB1 đã kiểm tra auth/thu hồi/quyền hồ sơ/vòng đời; MB2 lịch/tổng quan/học viên; MB3 giáo án/tự tập/nhật ký/nhận xét/số đo; MB4 chat chữ/ảnh/Reverb/reconnect/chưa đọc/thông báo; MB5 đăng ký/reset, gói/đơn và chatbot/nháp AI. Có kiểm thử quyền/version/retry/tranh chấp trên MariaDB và luồng chính trên LDPlayer. Phạm vi và giới hạn tại [MB1](../../docs/verification/MOBILE_MB1.md), [MB2](../../docs/verification/MOBILE_MB2.md), [MB3](../../docs/verification/MOBILE_MB3.md), [MB4](../../docs/verification/MOBILE_MB4.md), [MB5](../../docs/verification/MOBILE_MB5.md). MB5 giả lập nhà cung cấp; ma trận vẫn có ca chưa nghiệm thu native đầy đủ. Push/điện thoại thật/bản cài còn kế hoạch. [Bootstrap](../../docs/verification/MOBILE_BOOTSTRAP.md), [UI1](../../docs/verification/MOBILE_UI1.md) không thay bằng chứng tích hợp.

MB5 đã thử lỗi 503 sau khi BE commit đơn/câu trả lời rồi retry: không tăng số đơn/lượt, không đổi UUID. Đã kiểm tra AI lỗi rồi thử lại, NHAP 12 buổi/48 bài, hai tài khoản không lộ lịch sử, sáng/tối và composer ở 375 dp/chữ 1,3. Đây không phải kiểm tra mất kết nối TCP, bàn phím phần mềm, TalkBack hoặc chuyển tiền/gọi Gemini thật. Nhãn tab ở chữ lớn cần rà lại trong MB6.

## Môi trường và dữ liệu

- QA riêng với KH A/B, PT A/B, Admin; phân công/gói/lịch ở các trạng thái hợp lệ và hết hạn. Dùng database test, không seed lại hoặc làm mất dữ liệu đang dùng.
- Ghi rõ OS/thiết bị, phiên bản app/Expo, Backend commit, database engine và mạng. Emulator hữu ích cho phát triển; ít nhất một điện thoại thật thuộc nền tảng nghiệm thu cần được kiểm tra trước bàn giao.
- Tranh chấp dùng MySQL/MariaDB thật và request/process đồng thời; ghi đúng engine/version. Kết quả MariaDB không tự chứng minh MySQL 8.
- Reverb phải chạy thật, kết hợp app KH với web/app PT. Fake event chỉ kiểm tra code, không chứng minh socket đi qua mạng.
- AI mock và AI thật có báo cáo riêng. Chưa được phép suy ra chất lượng AI hoặc thanh toán thật từ test giả lập.

## Ma trận bắt buộc

| ID | Thao tác | Kết quả phải kiểm chứng |
| --- | --- | --- |
| AUTH01 | KH/PT đăng nhập, đóng/mở app | `/me` xác minh role; không hiện dữ liệu trước khi kiểm tra phiên |
| AUTH02 | Mật khẩu sai, tài khoản khóa, Admin vào mobile | Không cấp quyền/token sai; thông báo không dò được email |
| AUTH03 | Token hết hạn/thu hồi; mở khóa tài khoản | Token cũ không dùng lại; 401 xử lý một lần, không lặp request vô hạn |
| AUTH04 | Khóa/reset mật khẩu đồng thời với đăng nhập | Không tồn tại token hợp lệ được cấp từ trạng thái/mật khẩu cũ sau khi thao tác thu hồi hoàn tất |
| AUTH05 | Logout/đổi người khi HTTP/socket còn về | State, ảnh, navigation và request cũ không lộ cho người mới |
| AUTH06 | Logout khi offline, nhiều thiết bị | Báo đúng trạng thái thu hồi; logout thiết bị A không xóa nhầm B theo chính sách được chốt |
| WEB01 | Sau thay đổi auth, đăng nhập/CSRF/reset/khóa trên web | Luồng cookie vẫn hoạt động, không bỏ CSRF hoặc đổi quyền |
| ACL01 | KH A đổi ID sang tài nguyên KH B | Không đọc/ghi được lịch, đơn, giáo án, nhật ký, số đo, chat/ảnh |
| ACL02 | Đổi PT khi app PT cũ đang mở chi tiết | PT cũ mất quyền; UI bỏ dữ liệu đã mất quyền khi đồng bộ; PT mới không xem chat cũ |
| LICH01 | KH bấm đặt hai lần/retry timeout cùng UUID | Một lịch, cùng kết quả; đổi slot cùng UUID trả xung đột |
| LICH02 | Web và mobile đồng thời giữ cùng slot/lượt cuối | Tối đa thao tác hợp lệ theo khóa/unique, không vượt quota hoặc chồng lịch |
| LICH03 | Sát mốc đặt trước 4h/hủy trước 2h, deadline chờ | Đúng contract tại ranh giới, kể cả scheduler chưa chạy và múi giờ máy khác |
| LICH04 | PT hoàn thành lặp; rollback; buổi kết thúc ngoài hạn/đã quá 24h | Không trừ lặp/âm/mượn gói; lỗi rollback không để lại hiệu ứng nửa chừng; vắng mặt không trừ |
| GA01 | Áp dụng hai bản đồng thời từ web/app | Chỉ một giáo án đang dùng, giữ lịch sử bản cũ |
| GA02 | KH tự tạo khi chưa có gói; PT đọc bản KH; áp dụng lại PT | Đúng C31–C34; PT không sửa/áp dụng thay KH, không bỏ qua lần xác nhận đầu |
| NK01 | Lưu nháp/hoàn thành phiên, bấm lặp, phiên bản cũ | Không trừ buổi PT; phiên hoàn thành không sửa tùy ý; stale version xử lý rõ |
| CS01 | Ghi/sửa số đo, đổi ngày/phiên bản, PT ghi thay | BMI do BE tính, không thêm vòng eo, PT chỉ đọc, đúng một bản/ngày theo contract |
| CHAT01 | Tin đến trước/sau HTTP, retry chữ/ảnh, mất mạng rồi tải bù | Một tin/UUID, đúng thứ tự, đủ các trang bù, không đánh dấu đọc từ preview |
| CHAT02 | Giữ socket PT cũ, Admin đổi PT, KH gửi tiếp | PT cũ không nhận nội dung qua HTTP/socket/ảnh; tín hiệu không chứa dữ liệu riêng |
| CHAT03 | Ảnh quá cỡ/sai MIME/HEIC, hủy chọn, từ chối quyền thư viện | Lỗi rõ ràng, đúng giới hạn server, không gửi MIME giả, không upload ảnh đã bỏ |
| TB01 | Bấm thông báo cũ/đường dẫn lạ, đọc lại, đổi tài khoản | Chỉ route cho phép; quyền kiểm tra lại; không lộ hoặc mở tùy ý URL |
| PAY01 | Quay về link thành công giả, đóng trình duyệt, webhook lặp | Gói chỉ cấp từ BE xác minh; không nhân đôi đơn/khoản thu/quyền lợi |
| AI01 | Quota hết, provider lỗi, retry, rời app khi đang hỏi | Không trừ lượt sai; tải lại trước gửi lại; không mất/nhân đôi câu trả lời |
| AI02 | Tạo giáo án nháp, tắt dữ liệu cá nhân | Không tự áp dụng/tạo lịch; không lấy chat PT hoặc dữ liệu ngoài scope |
| LIFE01 | Nền/foreground nhiều lần, đổi Wi-Fi/di động, mất mạng | Không nhân đôi listener/timer; làm mới trạng thái và tải bù sau reconnect |
| UI01 | Bàn phím, màn hình nhỏ, chữ lớn, Android back, cuộn tin/bài | Không che nút lưu/gửi, không mất form vô cớ, nhãn trợ năng và trạng thái lỗi đọc được |

Nếu triển khai push: thêm ca từ chối quyền, token thiết bị thay đổi, logout/đổi tài khoản, thông báo khi app tắt, bấm đích mất quyền và nội dung màn hình khóa theo MB-D03.

## Cách chọn kiểm thử

Unit/component cho state phiên, xử lý lỗi, UUID retry, response cũ và form; integration Backend cho bearer + Policy + thu hồi; kiểm thử hành trình trên thiết bị cho lifecycle/socket/thanh toán quay về. Chỉ cài công cụ test khi triển khai module thực tế, không thêm bộ khung test chỉ để tăng số lượng.

Các kiểm tra bộ khung chạy trong Mobile: `npx expo install --check`, `npx expo-doctor`, `npx expo export --platform all`. Export chỉ chứng minh bundle, không thay chạy app hoặc build native. Khi thay BE, chạy test hiện có của module và auth web liên quan, không tuyên bố cả hệ thống PASS từ vài ca mobile.

## Kịch bản demo đầu tiên

1. PT đăng nhập và mở một khung giờ hợp lệ.
2. KH có gói/phân công hợp lệ đăng nhập trên điện thoại, chọn slot và đặt.
3. PT nhận thấy yêu cầu trên client khác và xác nhận.
4. KH trở lại app, tải lại và thấy trạng thái đã xác nhận.
5. Gửi lại request đặt cùng UUID: không sinh thêm lịch. Đọc lịch bằng KH khác: bị từ chối.

Kịch bản hoàn thành buổi dùng fixture thời gian ở môi trường QA; không sửa giờ/counter trực tiếp trong dữ liệu chính để ép demo.

## Mẫu ghi kết quả

| Ngày/build | Ca | Thiết bị/OS | BE/DB | Kết quả thực tế | Bằng chứng/lỗi còn lại |
| --- | --- | --- | --- | --- | --- |
| Chưa chạy | — | — | — | Chưa nghiệm thu | Điền sau khi thực hiện |
