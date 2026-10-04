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

## Sự kiện nghiệp vụ đã bật — 04/10/2026

Chủ dự án yêu cầu làm bước thông báo theo lộ trình. M05 giữ hai sự kiện đã có: KH nhận khi PT gửi giáo án, PT nhận khi KH xác nhận lần đầu; áp dụng lại bản PT theo C34 không tạo thông báo xác nhận mới. Tin nhắn có số chưa đọc riêng từ M07, không tạo thêm chuông cho từng tin.

| Người nhận | Chuyển trạng thái / sự kiện | Đích khi bấm |
| --- | --- | --- |
| KH | payOS đã được Backend đối chiếu và gói kích hoạt lần đầu | Chi tiết đơn của KH |
| KH | Phân công PT lần đầu hoặc đổi PT | Hồ sơ của KH |
| PT mới | Phân công học viên thành công | Giáo án của học viên |
| PT cũ | Phân công kết thúc do đổi PT | Danh sách học viên, không dẫn tới hồ sơ/chat đã mất quyền |
| PT hiện tại | KH đặt lịch hoặc hủy lịch hợp lệ | Chi tiết lịch hẹn |
| KH | PT xác nhận hoặc từ chối yêu cầu, yêu cầu hết hạn, hủy lịch do đổi PT | Chi tiết lịch hẹn của KH |
| KH | PT ghi nhận hoàn thành hoặc vắng mặt | Chi tiết lịch hẹn; hoàn thành trừ đúng một buổi, vắng không trừ |
| PT hiện tại | Buổi đã kết thúc và còn trong 24 giờ ghi nhận | Chi tiết lịch hẹn |
| Admin đang hoạt động | Gói PT mới kích hoạt nhưng KH chưa có phân công | Phân công PT |
| Admin đang hoạt động | Khoản thu ở `CAN_DOI_SOAT` | Chi tiết đơn của Admin |
| Admin đang hoạt động | Lịch hẹn chuyển `QUA_HAN_XAC_NHAN` | Chi tiết lịch hẹn để đóng xử lý theo R07 |
| PT hiện tại | KH hoàn thành nhật ký tự tập | Chi tiết nhật ký của học viên |
| KH | PT thêm nhận xét hợp lệ vào phiên đã hoàn thành | Chi tiết nhật ký của KH |
| Admin đang hoạt động | Giáo án mẫu mới ở `NHAP`, sửa bản đã duyệt hoặc đưa lại về nháp | Trang sửa/xem để duyệt; không thêm trạng thái chờ duyệt mới |

### Transaction, chống trùng và quyền

- `ThongBaoService` ghi vào bảng hiện có **trong cùng transaction** nghiệp vụ. Kết nối khác chỉ thấy sau commit; rollback lịch/gói/phân công/nhật ký cũng rollback thông báo. Không gọi mail, push, AI hay cổng thanh toán để gửi thông báo.
- UUID xác định theo khóa sự kiện + tài khoản nhận, PK `notifications.id` chống trùng ở database. Upsert chỉ cập nhật lại ID khi trùng, giữ nội dung, thời điểm tạo và `read_at`; không dùng `insertOrIgnore` để nuốt lỗi khác. Các thao tác đã thành công giữ nhánh retry hiện có.
- Thông báo giáo án mẫu tính một lần cho bản nháp mới và một lần cho mỗi vòng sửa sau duyệt; chỉnh liên tiếp cùng nháp không làm đầy chuông. Lưu timestamp duyệt trước khi xóa khỏi bản bị sửa để phân biệt vòng mới.
- Chỉ nhận tài khoản hoạt động. Nhật ký và lịch hẹn của PT phải đúng phân công hiện tại; worker khóa KH, đọc lại lịch và phân công trước gửi, giữ cùng thứ tự khóa với nghiệp vụ. So thời điểm phân công bằng đủ micro giây như DB, không bỏ thông báo đặt lịch ngay sau phân công.
- Thông báo nhận xét/nhật ký chỉ nêu sự kiện và đường dẫn, không sao chép nội dung nhận xét, chat, số đo cơ thể hoặc kết quả tập. Admin không nhận dữ liệu chat/nhật ký KH qua thông báo.
- Thông báo là lịch sử sự kiện, có thể đã được xử lý ở màn hình khác. Bấm thông báo luôn tải trạng thái hiện tại; tài nguyên vẫn kiểm tra quyền server. Sau đổi PT, thông báo lịch/nhật ký cũ không khôi phục quyền của PT cũ.
- Không có endpoint tạo thông báo từ Frontend. API danh sách/đọc một/đọc tất cả và chu kỳ làm mới 45 giây giữ nguyên. Chỉ điều hướng sau khi đánh dấu đọc thành công, bỏ phản hồi nếu session đã đổi.

### Worker và giới hạn

`lich-hen:don-qua-han` chạy mỗi phút theo scheduler hiện có: dọn yêu cầu hết hạn, thông báo Admin khi buổi quá 24 giờ, sau đó nhắc PT buổi đã kết thúc nhưng còn trong hạn ghi nhận. Mỗi lịch/sự kiện/người nhận tối đa một thông báo; chạy lại không gửi lặp và không tiêu hao lượt. Các đường dọn yêu cầu hết hạn khi đặt/đóng slot cũng ghi thông báo cùng transaction.

Chưa bật nhắc lịch **trước** giờ tập hoặc nhắc gói sắp hết hạn/gần hết lượt vì chưa chốt ngưỡng. Không tự backfill thông báo cho mọi giao dịch cũ, không seed số giả vào database chính; sự kiện mới tạo khi thao tác xảy ra, worker xử lý những buổi còn thuộc điều kiện hiện tại.

## Xem và kiểm tra

Chạy `start.bat`, đăng nhập rồi bấm chuông cạnh tài khoản trên dashboard hoặc thư viện bài tập. Clone mới cần chạy `php artisan migrate` trong `BE` trước khi mở ứng dụng. Không seed thông báo giả vào database ứng dụng.

Fixture kiểm tra riêng: `php tests/Support/chat-ui-fixture.php thong-bao` trong `BE` tạo database QA riêng và thông báo có nhãn mẫu cho ba vai trò. Xóa fixture bằng lệnh `drop` của script với đúng tên database nó trả về; script kiểm tra chặt tiền tố database trước khi xóa.

Kiểm chứng header cũ: [HEADER_NOTIFICATIONS.md](../verification/HEADER_NOTIFICATIONS.md). Phần nghiệp vụ mới và ảnh ba vai trò: [M09_THONG_BAO.md](../verification/M09_THONG_BAO.md). Fixture mới `php tests/Support/thong-bao-nghiep-vu-ui-fixture.php` thực hiện đặt/xác nhận lịch, hoàn thành nhật ký/nhận xét và tạo nháp mẫu bằng service thật trong database QA riêng; không gọi dịch vụ ngoài.
