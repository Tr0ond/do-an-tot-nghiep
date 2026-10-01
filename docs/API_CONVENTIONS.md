# Hợp đồng API đề xuất

Đã có `GET /api/v1/health`, Sanctum SPA đăng ký/đăng nhập/đăng xuất, /me, quyền hồ sơ và quản trị tạo tài khoản; [hợp đồng tài khoản](features/TAI_KHOAN.md). Có GET công khai /bai-tap, /bai-tap/bo-loc, /bai-tap/{id}; [hợp đồng bài tập](features/BAI_TAP.md). Exception cho API và routes xác thực đã chuẩn hóa status/message/data/code/errors. Các endpoint gói/lịch/chat bên dưới vẫn là đặc tả, chưa triển khai.

## Auth và namespace

Đã triển khai quản trị bài tập cho ADMIN hoạt động: GET `/admin/bai-tap`, `/admin/bai-tap/bo-loc`, `/admin/bai-tap/{id}`; POST `/admin/bai-tap`, PUT `/admin/bai-tap/{id}`, PATCH `/admin/bai-tap/{id}/trang-thai` dưới prefix `/api/v1`. Không có DELETE; sửa/trạng thái dùng phiên bản `updated_at`, xung đột 409. Chi tiết payload và bảo toàn dữ liệu tại [hợp đồng bài tập](features/BAI_TAP.md).

- Nghiệp vụ dưới `/api/v1`; routes theo resource, kebab-case tiếng Việt không dấu.
- Sanctum SPA: lấy cookie CSRF từ `/sanctum/csrf-cookie`, đăng nhập qua route session `/dang-nhap`, logout `/dang-xuat`. Cấu hình middleware stateful/CORS/cookie theo tài liệu tại bootstrap.
- Axios gửi credentials, XSRF theo cấu hình cùng domain/subdomain hợp lệ; không đưa API key AI vào browser.
- Role lấy từ server, ownership từ authenticated user; không cho client tự chọn vai trò khi đăng ký.

## HTTP

| Status | Nghĩa |
| --- | --- |
| 200 | Đọc/cập nhật/hành động thành công; retry trả record cũ |
| 201 | Tạo mới thành công |
| 401 | Chưa đăng nhập/session hết hiệu lực |
| 403 | Không có quyền |
| 404 | Không tồn tại hoặc ẩn tài nguyên trái quyền theo contract |
| 409 | Xung đột trạng thái/slot/phiên bản/idempotency payload |
| 422 | Input hoặc điều kiện nghiệp vụ không hợp lệ |
| 429 | Vượt rate limit/quota kỹ thuật |
| 502/503/504 | Lỗi dịch vụ ngoài/không sẵn sàng/timeout |

`GET` đọc; `POST` tạo/hành động; `PATCH` cập nhật; `DELETE` chỉ cho tài nguyên có chính sách xóa đã cho phép. Lịch/gói/phiên lịch sử dùng hành động hủy/lưu trữ, không DELETE chung.

## Response

Giữ `status/message/data` gần repo cũ nhưng `status` luôn boolean và HTTP status luôn đúng. Chuẩn hóa cả exception Laravel, không chỉ response thành công.

```json
{
  "status": true,
  "message": "Lấy danh sách thành công.",
  "data": [],
  "meta": { "current_page": 1, "per_page": 20, "total": 0, "last_page": 1 }
}
```

```json
{
  "status": false,
  "message": "Khung giờ đã được đặt.",
  "code": "LICH_HEN_TRUNG_KHUNG_GIO",
  "data": null,
  "errors": {}
}
```

Lỗi validation 422 dùng `errors` map field → array messages. FE chịu được lỗi mạng không có `response`/`errors`; không gọi `Object.values(undefined)`.

## Endpoint minh họa

| Method | Path (sau prefix `/api/v1`) | Actor/quyền |
| --- | --- | --- |
| GET | `/me` | Chính người đăng nhập |
| GET | `/goi-tap` | Catalog được phép hiển thị |
| POST | `/khach-hang/dang-ky-goi-tap` | KH hiện tại, giá/snapshot server |
| POST | `/khach-hang/dang-ky-goi-tap/{id}/thanh-toan` | KH sở hữu, tạo/lấy lại link payOS cho đơn còn hạn 15 phút, giá server |
| GET | `/khach-hang/dang-ky-goi-tap/{id}` | KH sở hữu, trạng thái đơn/gói từ server |
| POST | `/webhooks/payos` | Xác minh chữ ký payOS, mã đơn/link/số tiền/trạng thái; chống xử lý lặp, không dùng session người dùng |
| POST | `/admin/phan-cong` | Admin, transaction, D03 |
| GET/POST | `/pt/khung-gio` | PT hiện tại |
| POST | `/khach-hang/lich-hen` | KH + PT phụ trách + gói/slot hợp lệ |
| POST | `/pt/lich-hen/{id}/xac-nhan` | PT của lịch |
| POST | `/pt/lich-hen/{id}/hoan-thanh` | PT có quyền, chống trừ lặp |
| POST | `/lich-hen/{id}/huy` | Chính sách actor/thời hạn D04 |
| POST | `/pt/ke-hoach-tap` | KH đang được phân công |
| POST | `/khach-hang/ke-hoach-tap/{id}/xac-nhan` | KH sở hữu, đọc lại base/TTL |
| POST | `/khach-hang/phien-tap` | KH sở hữu lịch tập hợp lệ |
| POST | `/khach-hang/phien-tap/{id}/hoan-thanh` | KH sở hữu, không đổi phiên đã hoàn thành |
| GET | `/hoi-thoai/{id}/tin-nhan` | Đúng scope; cursor pagination |
| POST | `/hoi-thoai/{id}/tin-nhan` | Đúng scope + client message ID |
| POST | `/hoi-thoai/{id}/da-doc` | Người nhận hợp lệ, cursor tăng đơn điệu |
| POST | `/tro-ly/hoi-thoai/{id}/tin-nhan` | KH sở hữu, gói có chatbot còn hiệu lực/hạn mức ngày, client request ID/rate |
| GET/POST/PATCH | `/admin/tai-lieu-tu-van` | Admin, nội dung chưa duyệt không cấp cho AI |

## Query và chống trùng

- List HTTP: `page`, `per_page` giới hạn server, `tu_khoa`, `trang_thai`, khoảng ngày khi có ích.
- Chat: `before_id`/`after_id` hoặc cursor thứ tự tương đương, không chỉ timestamp định dạng giờ-phút.
- Hành động nhạy cảm dùng `Idempotency-Key` hoặc atomic status/unique có contract rõ. Cùng key khác payload là 409.
- Chat dùng `client_message_id`; AI dùng `client_request_id`, ổn định khi retry. Socket ID chỉ hỗ trợ tránh event echo, không thay message idempotency.
- API datetime ISO 8601. Giới hạn/snapshot/role do BE trả; FE không tự tính quyền từ ngày hiển thị.

## Contract trước implementation

Catalog mô tả riêng quyền chatbot, số lượt hỏi/ngày và số buổi PT; Backend kiểm tra snapshot đã mua, không nhận quyền từ FE hoặc suy ra từ tên gói. Gói kích hoạt khi Backend xác nhận thanh toán payOS, dùng chung thời hạn theo ngày đủ 24 giờ và mỗi KH một gói khả dụng. Chatbot cần gói phù hợp; câu trả lời hợp lệ tính một lượt/lỗi không mất lượt/retry không tính lặp, cấp lại 00:00 giờ Việt Nam. Hết buổi PT còn thời hạn vẫn dùng chatbot; catalog/FAQ miễn phí.

payOS: đơn giữ giá/chờ 15 phút, Backend đồng bộ `expiredAt` theo mốc đơn và xác minh chữ ký webhook bằng SDK/helper phù hợp. Trang return/cancel chỉ yêu cầu server đọc trạng thái, không cập nhật thành công từ query client. Webhook gửi lại không tạo khoản thu/gói mới; dữ liệu mẫu khi xác thực URL không phải tiền thật cho đơn nghiệp vụ. Tiền trả đúng/đủ trong hạn được xử lý bình thường dù thông báo tới muộn. Tiền trả sau hạn/thiếu/thừa hoặc khách đã có gói khả dụng được ghi nhận để Admin đối soát, không tự cấp gói; hoàn tiền thủ công có lưu kết quả, chưa hỗ trợ mua nối tiếp/nâng cấp. Nguồn: [API payOS](https://payos.vn/docs/api/), [chữ ký webhook](https://payos.vn/docs/tich-hop-webhook/kiem-tra-du-lieu-voi-signature/).

Lịch PT: đặt trước >=4 giờ, KH hủy trước >=2 giờ; deadline chờ là mốc sớm hơn giữa lúc tạo +2 giờ và lúc bắt đầu -2 giờ. Server kiểm tra deadline khi xác nhận/hủy/giữ slot dù worker chưa dọn. PT chỉ xác nhận sau buổi diễn ra, trong 24 giờ sau kết thúc, buổi kết thúc <= hạn gói; không mượn gói mới khi xác nhận muộn. Chặn đổi PT khi buổi đang diễn ra hoặc đã diễn ra chưa xử lý; quá 24 giờ chưa xác nhận thì ghi quá hạn/không trừ buổi, Admin đóng xử lý có lý do/audit để cho đổi PT, không xác nhận thay PT/sửa counter. KH hết gói vẫn đọc kế hoạch/lịch sử và ghi nhật ký từ lịch tự tập hợp lệ đã có.

Mỗi endpoint bổ sung: request/response mẫu, permission, validation, state transition, mã lỗi, idempotency và test. Sau bootstrap lưu OpenAPI nếu cần, không thêm Swagger package chỉ vì dự án cũ có.
