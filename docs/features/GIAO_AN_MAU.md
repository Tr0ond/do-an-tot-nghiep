# Giáo án mẫu — M02

Admin quản lý danh mục; PT chỉ xem giáo án đã duyệt. KH không có quyền truy cập. Đây là giáo án tham khảo, chưa tạo hoặc áp dụng kế hoạch cho khách hàng (M05).

## Use case và dữ liệu

- T12 `giao_an_mau`: tên, mục tiêu, số ngày tập, người tạo, người duyệt/thời điểm duyệt, trạng thái. T13 lưu bài theo ngày/thứ tự, hiệp, lần lặp, nghỉ và ghi chú.
- Trạng thái danh mục: `NHAP`, `DA_DUYET`, `NGUNG_SU_DUNG`. Tạo mới luôn là nháp. Admin duyệt khi mỗi ngày có bài và toàn bộ bài/nhóm cơ đang hoạt động. Backend đặt người duyệt/thời gian; client không được gửi các trường này.
- Sửa nội dung giáo án đã duyệt đưa về nháp; mọi lần sửa nội dung xóa thông tin duyệt hiện tại, giáo án đang ngừng vẫn giữ trạng thái ngừng. Lưu không đổi giữ trạng thái/phiên bản. Ngừng sử dụng ẩn khỏi PT; duyệt lại kiểm tra đầy đủ như lần đầu.
- Danh sách/detail PT chỉ trả `DA_DUYET`; bài sau đó ngừng sử dụng vẫn được trình bày cùng cảnh báo khả dụng. Không có thao tác tự áp dụng kế hoạch.
- Không xóa giáo án. Chỉ thay các dòng T13 trong transaction; không có FK trỏ đến T13. T14/T15 và snapshot kế hoạch đã dùng luôn giữ nguyên.

## Dữ liệu giáo án demo

`GiaoAnMauSeeder` chỉ chạy trong `local/testing`, tạo 5 giáo án tham khảo với 60 dòng bài tập. Tạo nháp rồi duyệt qua `GiaoAnMauService` bằng Admin demo `admin@example.test` đang hoạt động, để PT thấy trong thư viện. Đây là dữ liệu trình diễn, chưa gán cho khách hàng hoặc sinh kế hoạch/lịch tập.

Mỗi giáo án có UUID cố định để chạy lại không tạo trùng. Giáo án đã tồn tại theo UUID được bỏ qua toàn bộ, kể cả khi đã đổi tên, sửa bài hoặc ngừng sử dụng; không tự duyệt lại. Tra bài bằng cặp `nguon_du_lieu = exercises-dataset` và `ma_nguon`, không dùng ID từ JSON. Cần seed tài khoản và bài tập trước. Thiếu Admin đúng vai trò/đang hoạt động, thiếu bài hoặc bài/nhóm ngừng sẽ báo lỗi; toàn bộ giáo án mới của lần chạy được rollback, dữ liệu cũ giữ nguyên. Không mở khóa, nâng quyền hoặc đặt lại mật khẩu tài khoản.

`DatabaseSeeder` gọi lần lượt tài khoản → bài tập → giáo án. Không chạy nhiều lệnh seed đồng thời; kiểm chứng chạy đồng thời hoặc trên MySQL thật chưa thực hiện. Hướng dẫn chạy nằm trong [README seeders](../../BE/database/seeders/README.md).

## Endpoint và quyền

Tất cả dưới `/api/v1`, yêu cầu session Sanctum, tài khoản hoạt động, đúng vai trò tại Backend.

| Endpoint | Quyền | Hành vi |
| --- | --- | --- |
| GET `/admin/giao-an-mau` | Admin | Tìm tên/mục tiêu, trạng thái, phân trang |
| POST `/admin/giao-an-mau` | Admin | Tạo nháp với `client_request_id` UUID |
| GET/PUT `/admin/giao-an-mau/{id}` | Admin | Đọc/lưu nội dung |
| PATCH `/admin/giao-an-mau/{id}/trang-thai` | Admin | Duyệt hoặc ngừng sử dụng |
| GET `/pt/giao-an-mau` | PT | Danh sách đã duyệt |
| GET `/pt/giao-an-mau/{id}` | PT | Chi tiết đã duyệt, 404 nếu nháp/ngừng |

## Validation, transaction và lỗi

Tên tối đa 255, mục tiêu nullable tối đa 255. Giới hạn tải trình soạn: 1–30 ngày, tối đa 500 dòng; mỗi dòng có `bai_tap_id`, `ngay_thu`, `thu_tu` liên tục từ 1 trong ngày, hiệp 1–100, lần lặp 1–1000, nghỉ 0–3600 giây, ghi chú tối đa 2000. Có thể dùng cùng bài nhiều ngày/vị trí. Nháp có thể chưa có bài; duyệt cần tất cả ngày có ít nhất một bài.

Bài thêm mới phải đang hoạt động trong nhóm hoạt động; có thể giữ số dòng bài cũ đã ngừng trong nháp để chỉnh sửa/thay thế, không được nhân thêm bằng ID cũ. Các ID/trạng thái/người tạo/người duyệt do server quản lý; chặn field thừa ở cả payload và từng dòng.

Ghi parent/child cùng transaction, khóa parent và bài/nhóm liên quan khi kiểm tra. UUID unique riêng T12 chống tạo trùng; retry cùng dữ liệu trả cùng giáo án, khác dữ liệu/actor trả 409. PUT/PATCH yêu cầu `updated_at` DATETIME(6), khóa parent và đối chiếu phiên bản; 409 giữ form và yêu cầu tải lại. Không cho thao tác thứ hai trong lúc FE đang gửi. 401/403/419 yêu cầu đăng nhập lại; 422 giữ dữ liệu/lỗi trường; 404 báo không tìm thấy; lỗi mạng cho thử lại cùng UUID. Không có DELETE.

## Kiểm chứng

Kiểm thử trên database MariaDB tạm riêng: quyền/session/CSRF, UUID và unique index, validation ngày/thứ tự, bài/nhóm ngừng, duyệt/thu hồi duyệt, 409 dữ liệu cũ, atomic rollback và không sửa snapshot kế hoạch. FE kiểm tra reorder theo ngày, payload không lẫn dữ liệu hiển thị, chống gửi trùng, retry/409 và phản hồi đến muộn. Kiểm tra thực tế trình duyệt desktop/tablet/mobile cho luồng Admin soạn/duyệt và PT đọc.

## Ví dụ payload

POST tạo nháp (ID bài minh họa phải tồn tại và hoạt động):

```json
{
  "client_request_id": "8584c37a-cea0-40fb-b14d-34aa89a76726",
  "ten_giao_an": "Toàn thân cho người mới",
  "muc_tieu": "Tăng sức mạnh cơ bản",
  "so_ngay_tap": 1,
  "bai_tap": [{"bai_tap_id": 3, "ngay_thu": 1, "thu_tu": 1, "so_hiep": 3, "so_lan_lap": 12, "nghi_giay": 60, "ghi_chu": null}]
}
```

PUT dùng cùng nội dung nhưng thay `client_request_id` bằng `updated_at` lấy từ detail Admin, ví dụ `2026-10-01 08:00:00.000001`. PATCH chỉ gửi `trang_thai` (`DA_DUYET`/`NGUNG_SU_DUNG`) và `updated_at`. Phiên bản này là mã đối chiếu kỹ thuật; `duyet_luc` trả ISO 8601 theo quy ước API.

Response ghi/detail: `{status: true, message, data: {id, ten_giao_an, muc_tieu, so_ngay_tap, so_bai_tap, trang_thai, duyet_luc, bai_tap}}`. Mỗi dòng bổ sung tên bài, tên nhóm và `kha_dung`; Admin nhận thêm người tạo/người duyệt/phiên bản. List không tải các dòng, trả `meta.current_page/per_page/total/last_page`, mặc định 12/trang, tối đa 48. Từ khóa tối đa 100; ký tự `%`, `_`, `=` được tìm theo nghĩa đen. PT không được dùng bộ lọc trạng thái để đọc nháp.

GET detail khóa chia sẻ parent trong transaction đến khi serialize xong, tránh trả nội dung nháp khi Admin sửa cùng lúc. Chưa kiểm chứng tranh chấp bằng nhiều process hoặc trên MySQL thật; xem [bằng chứng](../verification/M02_GIAO_AN_MAU.md).
