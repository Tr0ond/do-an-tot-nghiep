# C42 — Kết quả buổi tập trực tiếp với PT

Chủ dự án xác nhận ngày 06/10/2026, làm web trước. Tách `lich_hen_huan_luyen` và kết quả khỏi `lich_tap`/nhật ký tự tập; không thay R07 trừ lượt.

Ngày 06/10/2026 chủ dự án tiếp tục yêu cầu áp dụng ghi kết quả PT lên mobile Android. Mobile dùng cùng API bearer, quy tắc quyền/validation/thời hạn/version/transaction bên dưới; PT ghi và chốt, KH chỉ đọc. Không thay luồng xác nhận hoàn thành lịch.

## Use cases và quyền

| Actor | Hành động | Quyền/trạng thái |
| --- | --- | --- |
| PT | Chọn bài catalog, ghi hiệp và nhận xét, lưu nháp | Đúng PT của lịch và phân công còn hiệu lực; lịch DA_XAC_NHAN/HOAN_THANH; từ bắt đầu đến trước kết thúc +24 giờ |
| PT | Chốt kết quả | Quyền ghi như trên, sau giờ kết thúc; ít nhất 1 bài và 1 hiệp/bài; đã chốt không sửa |
| KH | Đọc nháp/kết quả của lịch mình | Không cần gói/phân công còn hiệu lực, không được ghi |
| Admin/PT khác/PT bị thu hồi | Đọc/ghi kết quả riêng | Không cấp quyền từ router hoặc ID client; trả 403/404 theo role/scope |

Buổi hủy/vắng mặt/quá hạn giữ nháp nếu có, không cấp quyền sửa/chốt. Lịch đã hoàn thành vẫn có thể bổ sung/chốt trong hạn 24 giờ; không bắt buộc dữ liệu mới cho lịch sử cũ. Chốt kết quả không tự chuyển lịch, không trừ lượt. Hoàn thành/vắng mặt lịch tiếp tục qua endpoint hiện có, kể cả chưa có kết quả chi tiết.

## API

Prefix `/api/v1`, auth Sanctum + tài khoản hoạt động, CSRF cho web, `Cache-Control: private, no-store`.

| Method | Path | Dữ liệu |
| --- | --- | --- |
| GET | `/khach-hang/lich-hen/{id}/ket-qua` | Chỉ lịch của KH; không tạo record |
| GET | `/pt/lich-hen/{id}/ket-qua` | PT đúng scope; lịch, kết quả nullable, quyền ghi/chốt và lý do khóa |
| PUT | `/pt/lich-hen/{id}/ket-qua` | Toàn bộ nháp; `updated_at` nullable lúc chưa tạo, `ghi_chu`, `nhan_xet`, `bai_tap` |
| POST | `/pt/lich-hen/{id}/ket-qua/chot` | `updated_at` micro giây nhận từ GET/PUT |

Mỗi bài nhận `bai_tap_id`, `hiep_tap`; không nhận tên/ảnh/ID người ghi từ client. Mỗi hiệp nhận `so_lan_lap`, `khoi_luong_kg`, `nghi_giay`. 0–30 bài khi nháp, ID không trùng, 0–20 hiệp/bài; chốt bắt buộc ít nhất 1 bài/hiệp. Số lần nguyên 1–1.000; tạ null (chưa ghi) hoặc 0–1.000, tối đa 2 chữ số lẻ; nghỉ nguyên 0–3.600 giây. Ghi chú/nhận xét nullable, tối đa 2.000 ký tự. Trường lạ bị từ chối.

Tên và media snapshot từ catalog ở server, bài mới phải đang hiển thị. Bài đã có giữ snapshot, kể cả catalog đổi tên/ngừng hiển thị. Không suy diễn calo hoặc thể tích tạ cho hiệp chưa ghi tạ.

## Dữ liệu, transaction, lỗi và retry

Một record `ket_qua_buoi_pt` cho mỗi `lich_hen_id` (unique), FK RESTRICT lịch/người ghi, JSON bài/hiệp có giới hạn; không thay schema nhật ký cũ. Chốt lưu thời điểm, sau đó bất biến. Mọi lần ghi kiểm tra quyền lại trong transaction, khóa KH → PT → lịch → kết quả để đồng bộ với xác nhận/hủy/đổi PT. Lưu nội dung mới và audit cùng transaction.

PUT cùng nội dung đã lưu trả record cũ, không tạo lại phiên bản/audit. PUT khác nội dung với phiên bản cũ trả 409; lần tạo đầu nhận null, khác nháp đã tạo cũng trả 409. POST chốt lặp trả kết quả đã chốt sau kiểm tra quyền, không thêm thông báo/audit. Không ghi DB khi GET. Chốt và thông báo KH cùng transaction; rollback không để kết quả nửa chừng, không ảnh hưởng lượt PT.

401 chưa đăng nhập; 403 sai role; 404 sai tài nguyên/phân công; 409 sai thời gian/trạng thái/phiên bản; 422 payload/bài/hiệp không hợp lệ; 429 hạn request. Mất phản hồi: giữ nguyên nội dung đã gửi để thử lại; khi 409 cho tải lại có xác nhận bỏ nháp, không tự ghi đè.

## Web và kiểm thử

Từ chi tiết lịch hẹn KH/PT mở **Kết quả buổi tập**. PT có chọn bài catalog phân trang, hiệp thực tế, lưu nháp, chốt có xác nhận và cảnh báo rời trang khi chưa lưu. KH chỉ đọc. Không nhập dữ liệu mặc định như thể đã tập.

Kiểm tra trên MariaDB/MySQL: ownership/role/thu hồi, thời gian, schema validation, snapshot, stale version, retry, rollback, hai request đồng thời, chốt không trừ lượt và hoàn thành lịch trừ đúng một lượt. Kiểm tra Vue: readonly, chống bấm trùng, nháp/race/logout, tải lại, catalog và lỗi; chạy lint/build và mở UI thực tế. Không suy ra đạt điện thoại/mobile từ web.
