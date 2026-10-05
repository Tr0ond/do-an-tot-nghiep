# Tài liệu triển khai mobile

Cập nhật: **05/10/2026**. Dành cho app React Native + Expo/JavaScript của KH và PT. Bộ tài liệu này là kế hoạch triển khai từ hệ thống hiện có, không phải bằng chứng đã hoàn thành chức năng.

## Đọc theo thứ tự

| Tài liệu | Dùng khi |
| --- | --- |
| [PHAM_VI.md](PHAM_VI.md) | Xác định chức năng KH/PT, phần dùng lại và quyết định còn thiếu |
| [KIEN_TRUC.md](KIEN_TRUC.md) | Tổ chức source, điều hướng, state và vòng đời app |
| [XAC_THUC.md](XAC_THUC.md) | Bổ sung xác thực mobile mà vẫn giữ đăng nhập web |
| [TICH_HOP_API.md](TICH_HOP_API.md) | Ánh xạ API thực tế, chat, ảnh, AI, thanh toán và thông báo |
| [LO_TRINH.md](LO_TRINH.md) | Chọn bước thực hiện, phụ thuộc và điều kiện hoàn thành |
| [KIEM_THU.md](KIEM_THU.md) | Kiểm thử quyền, retry, chạy nền và hành trình web–mobile |
| [MOI_TRUONG_PHAT_HANH.md](MOI_TRUONG_PHAT_HANH.md) | Kết nối thiết bị, build thử và chuẩn bị phát hành |

## Trạng thái và nguồn sự thật

- **Đã có:** bộ khung Expo, navigation KH/PT, theme sáng/tối, bản xem UI1; MB1 nối Laravel để đăng nhập, phiên 30 ngày/SecureStore/khôi phục/đăng xuất riêng thiết bị, HTTP client và hồ sơ thật. Xem [bootstrap](../../docs/verification/MOBILE_BOOTSTRAP.md), [UI1](../../docs/verification/MOBILE_UI1.md), [MB1](../../docs/verification/MOBILE_MB1.md).
- **MB2:** tổng quan thật, lịch hẹn KH/PT, đặt/hủy, mở/đóng giờ, xác nhận/từ chối/hoàn thành/vắng mặt, tìm học viên và hồ sơ chỉ đọc. Xem [MB2](../../docs/verification/MOBILE_MB2.md).
- **MB3:** catalog, giáo án KH/PT và mẫu đã duyệt, lịch tự tập/nhật ký/nhận xét, số đo/lịch sử/tiến độ. Xem [MB3](../../docs/verification/MOBILE_MB3.md).
- **MB4:** chat chữ/ảnh, Reverb/reconnect/tải bù/chưa đọc và thông báo trong app; quyền khi đổi PT. Xem [MB4](../../docs/verification/MOBILE_MB4.md).
- **MB5:** đăng ký/khôi phục, gói/đơn/thanh toán, FAQ và chatbot/nháp AI; retry giữ UUID, AI không tự áp dụng. Kiểm tra native dùng payOS/Gemini/email giả lập. Xem [MB5](../../docs/verification/MOBILE_MB5.md).
- **Chưa có:** push, APK/IPA, nghiệm thu điện thoại thật và luồng nhà cung cấp thật của MB5. Kiểm tra LDPlayer không đại diện toàn app.
- Quy tắc nghiệp vụ lấy từ [PROJECT_RULES](../../PROJECT_RULES.md) và [DECISIONS](../../docs/DECISIONS.md). Tài liệu mobile không tự thay chính sách đã chốt.
- Nguồn endpoint là [routes API](../../BE/routes/api.php), [routes web](../../BE/routes/web.php) và [bootstrap broadcasting](../../BE/bootstrap/app.php). Hợp đồng chi tiết nằm trong [docs/features](../../docs/features/README.md).

## Cách sử dụng khi giao việc

Chọn một giai đoạn trong LO_TRINH, đọc tài liệu chức năng tương ứng, đối chiếu lại source rồi mô tả actor, dữ liệu, endpoint, quyền, validation, trạng thái, failure cases, transaction, chống trùng và kiểm thử trước khi sửa code. Ghi kết quả thực tế khi bàn giao, không đánh dấu hoàn thành chỉ vì có màn hình hoặc mock.

Sau bước tài liệu, chủ dự án đã chọn Android, chốt chính sách phiên và tạm chốt thiết kế Stitch để dựng UI1. Mục **CHỜ CHỐT** chỉ chặn phần phụ thuộc; vẫn tiếp tục việc độc lập. Không mặc định đã được phép phát hành lên store hoặc dùng dịch vụ có phí.
