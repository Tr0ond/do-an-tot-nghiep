# Lộ trình triển khai mobile

Ngày lập/cập nhật: 05/10/2026. MB0, UI1 và MB1–MB5 đã có source/kiểm chứng; MB6 **chưa hoàn thành**. Điện thoại thật còn ở MB6; payOS/Gemini/email trong QA MB5 dùng dịch vụ giả lập.

| Bước | Phạm vi | Phụ thuộc | Điều kiện hoàn thành |
| --- | --- | --- | --- |
| MB0 — Bộ khung | Expo/React Native JavaScript, lockfile, hướng dẫn | C40 | Đã có cấu hình 21/21 và export ba nền tảng; chưa nghiệm thu thiết bị |
| UI1 — Giao diện đầu tiên | Theme, navigation, login UI, tổng quan KH/PT, hồ sơ/chỉnh sửa minh họa | Thiết kế Stitch tạm chốt | Đã kiểm tra thao tác trên web, export ba nền tảng và mở màn đầu trên LDPlayer 9; [biên bản UI1](../../docs/verification/MOBILE_UI1.md); chưa nghiệm thu đầy đủ Android |
| MB1 — Phiên và nền tảng | Token native, lưu an toàn, API client, navigation theo role server, hồ sơ thật | MB-D02 đã chốt | Đã triển khai và kiểm tra KH/PT trên LDPlayer, restart/đăng xuất/sửa hồ sơ; thu hồi/race và hồi quy web qua test; [biên bản MB1](../../docs/verification/MOBILE_MB1.md) |
| MB2 — Lịch và tổng quan | Dashboard, PT mở giờ, KH đặt/hủy, PT xác nhận/ghi nhận, học viên | MB1 | Đã chạy KH đặt → PT xác nhận → KH cập nhật/hủy trên LDPlayer; UUID retry và tranh chấp/trừ buổi qua kiểm thử MariaDB. [Biên bản MB2](../../docs/verification/MOBILE_MB2.md) |
| MB3 — Tập luyện | Catalog, giáo án KH/PT, lịch tự tập, nhật ký/nhận xét, số đo/tiến độ | MB1, dùng học viên từ MB2 | Đã chạy KH tự tạo/áp dụng/tự tập/số đo, PT nhận xét/sao chép mẫu/gửi và KH xác nhận trên LDPlayer; quyền/version/retry/thu hồi phân công qua MariaDB. Dùng chung dữ liệu và nghiệp vụ BE với web. [Biên bản MB3](../../docs/verification/MOBILE_MB3.md) |
| MB4 — Trao đổi | Chat chữ/ảnh, reconnect, chưa đọc, thông báo trong app | MB1, quan hệ KH/PT hợp lệ | Đã kiểm tra Android gửi/nhận chữ/ảnh, Reverb thật hai client, tải bù 123 tin, retry, thông báo và đổi PT khi socket còn mở. [Biên bản MB4](../../docs/verification/MOBILE_MB4.md) |
| MB5 — Hoàn thiện chức năng | Đăng ký/khôi phục, gói/thanh toán, chatbot và nháp AI | MB1; MB3 cho giáo án AI; contract return/reset link | Đã kiểm tra native trên LDPlayer, bearer/quyền/retry trên MariaDB; phản hồi lỗi sau khi lưu không tạo trùng đơn/lượt. AI chỉ tạo NHAP. Nhà cung cấp giả lập, chưa chuyển tiền/gọi Gemini thật. [Biên bản MB5](../../docs/verification/MOBILE_MB5.md) |
| MB6 — Nghiệm thu bản cài | UX thiết bị, hiệu năng cơ bản, build, tài liệu/demo | Các module được chọn đã đạt; MB-D01/04 | Bản cài chạy trên thiết bị mục tiêu, biên bản QA, giới hạn rõ; không coi export là APK/IPA |
| Tùy chọn — Push | Đăng ký thiết bị, gửi/thu hồi push, mở đích an toàn | MB-D03, cấu hình dịch vụ, MB4 | Thiết bị thật, từ chối quyền vẫn dùng được app, đổi tài khoản không nhận nhầm |

## Công việc đầu tiên nên giao

MB5 đã nối đăng ký/khôi phục tài khoản, gói/thanh toán và chatbot/nháp AI. Bước tiếp theo là MB6: nghiệm thu điện thoại Android thật, bàn phím, mạng yếu, trợ năng và chuẩn bị bản cài nội bộ sau khi chốt thông tin phát hành. Luồng nhà cung cấp thật cần nghiệm thu riêng.

Các màn chưa tích hợp vẫn ghi rõ đang chờ; dashboard mẫu chỉ nằm trong Duyệt giao diện, không dùng số liệu mẫu cho tài khoản đăng nhập thật.

## Checklist trước mỗi use case

- [ ] Actor và tài nguyên: của ai, phân công nào, ID hồ sơ hay tài khoản?
- [ ] Route thật, request/response, validation, phân trang, các mã lỗi.
- [ ] Trạng thái trước/sau, deadline/quota và dữ liệu phải giữ lịch sử.
- [ ] Transaction nằm ở BE; yêu cầu khóa/chống trùng/race với web.
- [ ] Timeout, mạng yếu, app xuống nền, response muộn, logout giữa request.
- [ ] Màn hình loading/empty/error, thông báo kết quả và thao tác thử lại.
- [ ] Test cần chạy, thiết bị/môi trường QA và tiêu chí hoàn thành cụ thể.

## Bàn giao mỗi bước

Ghi file/module thay đổi, hành vi mới, lệnh kiểm tra thực sự đã chạy, kết quả và môi trường; kèm cách mở màn hình, tài khoản QA được quản lý riêng, giới hạn và quyết định còn chờ. Không đưa secrets hoặc dữ liệu người thật vào biên bản.

Cập nhật trạng thái trong bảng này khi có bằng chứng, dẫn đến tài liệu kiểm chứng riêng. Không dùng kết quả bootstrap làm bằng chứng cho chức năng mới. Thay đổi Backend cần mô tả tác động web và migration/rollback nếu có.
