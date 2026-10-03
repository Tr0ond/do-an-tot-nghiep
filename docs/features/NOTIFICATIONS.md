# Thông báo và tin nhắn trên header

## Đã triển khai ngày 03/10/2026

- Header dùng chung có chuông cho KH, PT và Admin; hiển thị số chưa đọc từ Backend, không gắn số giả.
- KH/PT bấm biểu tượng tin nhắn để mở bảng xem nhanh tối đa sáu hội thoại mới nhất: tên người trò chuyện, tin cuối, thời gian, số chưa đọc và nhãn phân công đã kết thúc. Bấm một dòng mới mở hội thoại; nút “Xem tất cả tin nhắn” mở danh sách đầy đủ. Chỉ xem preview chưa đánh dấu đã đọc. Admin không có chat riêng theo C21/D07/R22.
- Chuông mở 20 thông báo mới nhất, tải thêm, đánh dấu một thông báo hoặc tất cả đã đọc. Số chưa đọc tính trên toàn bộ thông báo của người nhận.
- Thông báo hiển thị dưới dạng văn bản; chỉ cho phép đường dẫn nội bộ. Tài nguyên đích vẫn được Backend kiểm tra quyền.
- Có trạng thái trống, lỗi/thử lại, đóng bằng Escape/bấm ngoài và trả focus về chuông khi đóng bằng bàn phím.
- Store cập nhật lúc đăng nhập, mở chuông, trở lại tab, có mạng và mỗi 45 giây khi tab hiện. Hủy yêu cầu và xóa dữ liệu khi đổi tài khoản/đăng xuất/hết phiên.
- Dùng bảng kỹ thuật Laravel `notifications` theo `docs/DATABASE_DRAFT.md`; migration bổ sung `2026_10_03_000034_create_notifications_table.php`.

## API

Các endpoint thuộc `/api/v1`, yêu cầu cookie Sanctum và tài khoản hoạt động:

| Endpoint | Hành vi |
| --- | --- |
| `GET /thong-bao?page=1` | Danh sách của chính người đăng nhập; `meta.so_chua_doc`, `current_page`, `last_page` |
| `POST /thong-bao/{uuid}/da-doc` | Đọc một thông báo thuộc chính người nhận; lặp lại không đổi thời điểm đọc |
| `POST /thong-bao/da-doc-tat-ca` | Đọc các thông báo chưa đọc của chính người nhận tại lúc thực hiện |

Payload hiển thị: `id`, `tieu_de`, `noi_dung`, `duong_dan`, `da_doc_luc`, `tao_luc`. Không trả toàn bộ `data` của Laravel. Không có API cho người dùng tự tạo hoặc đọc thông báo của tài khoản khác; Admin cũng không được quyền này.

## Đề xuất cần chủ dự án chọn trước khi bật

M05 đã gắn hai sự kiện: KH nhận khi PT gửi giáo án, PT nhận khi KH xác nhận áp dụng. Bản ghi cùng transaction giáo án, chỉ thấy sau commit và không nhân bản khi retry. [Hợp đồng M05](KE_HOACH_TAP.md). Các sự kiện bên dưới chưa bật. Tin nhắn có số chưa đọc riêng từ M07, không tạo thêm chuông cho từng tin.

| Vai trò | Nên nhận thông báo | Giai đoạn |
| --- | --- | --- |
| KH | PT được phân công/thay đổi; lịch hẹn được xác nhận, từ chối hoặc hủy; thanh toán thành công và gói được kích hoạt | Nghiệp vụ đã có, có thể gắn sự kiện tiếp theo |
| PT | Có KH mới được phân công hoặc kết thúc phân công; lịch hẹn mới chờ xác nhận, KH hủy lịch; buổi tập cần ghi nhận kết quả | Nghiệp vụ đã có, có thể gắn sự kiện tiếp theo |
| Admin | KH đã có gói PT nhưng chưa được phân công; khoản thanh toán cần đối soát; lịch hẹn quá hạn cần đóng xử lý | Nghiệp vụ đã có, có thể gắn sự kiện tiếp theo |
| KH/PT | Cập nhật tiến độ tập luyện | Sau module M06 |
| KH/PT | Nhắc lịch sắp tới; gói sắp hết hạn hoặc gần hết lượt | Chốt thời điểm, ngưỡng và cách chống gửi lặp trước khi bật |

Ưu tiên gắn phân công PT và thay đổi lịch hẹn, sau đó thanh toán/kích hoạt gói và ngoại lệ đối soát. Khi gắn sự kiện: chỉ gửi sau transaction commit, chống trùng bằng khóa sự kiện, không đưa nội dung chat riêng vào thông báo Admin. Các nhắc lịch không làm thay đổi trạng thái/trừ lượt.

## Xem và kiểm tra

Chạy `start.bat`, đăng nhập rồi bấm chuông cạnh tài khoản trên dashboard hoặc thư viện bài tập. Clone mới cần chạy `php artisan migrate` trong `BE` trước khi mở ứng dụng. Không seed thông báo giả vào database ứng dụng.

Fixture kiểm tra riêng: `php tests/Support/chat-ui-fixture.php thong-bao` trong `BE` tạo database QA riêng và thông báo có nhãn mẫu cho ba vai trò. Xóa fixture bằng lệnh `drop` của script với đúng tên database nó trả về; script kiểm tra chặt tiền tố database trước khi xóa.

Kết quả kiểm tra và ảnh giao diện nằm tại `docs/verification/HEADER_NOTIFICATIONS.md`.
