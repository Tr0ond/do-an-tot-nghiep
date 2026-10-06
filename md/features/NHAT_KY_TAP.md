# M06 — Lịch tự tập và nhật ký

Theo yêu cầu chủ dự án ngày 03/10/2026 (C35), KH tự lên lịch từ giáo án đang áp dụng, dù chưa mua gói/chưa có PT. PT hiện phụ trách cũng có thể lên lịch cho KH. Lịch tự tập độc lập lịch hẹn và không trừ buổi PT.

- Tạo lịch: chọn giáo án đang áp dụng, ngày trong giáo án và ngày thực tế (hôm nay đến 365 ngày tới, giờ Việt Nam). Mỗi giáo án/ngày thực tế/ngày trong giáo án chỉ có một lịch; retry cùng mã và nội dung trả lịch cũ. Lịch đã hủy được giữ, không tạo lại cùng tổ hợp.
- Đổi/ngừng giáo án hoặc hết gói không xóa/hủy lịch tự tập đã tạo. KH vẫn ghi kết quả từ lịch đó; tạo lịch mới phải dùng giáo án đang áp dụng. Không tự tạo lịch khi áp dụng giáo án.
- `DA_LEN_LICH → DANG_TAP → HOAN_THANH`; lịch chưa hoàn thành có thể `DA_HUY`. Không bắt đầu trước ngày đã chọn. Ngày cũ có thể ghi bổ sung, không giả thời điểm hoàn thành.
- Bắt đầu tạo đúng một phiên, sao chép tên, hướng dẫn và số hiệp/lần/tạ dự kiến. Catalog đổi/ngừng không sửa snapshot.
- KH nhập hiệp thực tế (1–20 hiệp/bài, 1–1.000 lần/hiệp, tạ 0–1.000 kg hoặc chưa ghi, nghỉ 0–3.600 giây). Lưu nháp có thể chưa ghi một số bài; hoàn thành phải có ít nhất một hiệp hợp lệ cho mỗi bài. Không lấy chỉ tiêu dự kiến làm kết quả thực tế.
- Kết quả hoàn thành bất biến; retry bắt đầu/hoàn thành/hủy không sinh phiên hay hiệu ứng thứ hai. Lưu nháp kiểm tra phiên bản; cùng nội dung đã lưu là retry an toàn. Mỗi hiệp phải thuộc đúng bài trong phiên của KH.
- PT hiện phụ trách đọc lịch/phiên của KH và thêm nhận xét riêng cho phiên hoàn thành, không sửa kết quả. Đổi PT thu hồi quyền PT cũ; KH giữ lịch sử và nhận xét. Admin không được đọc nhật ký qua API KH/PT.
- Thống kê theo ngày lịch trong khoảng tối đa 366 ngày: chỉ phiên hoàn thành, số buổi/hiệp/lần và khối lượng tạ × số lần đã ghi. Thiếu tạ không coi là 0; không suy ra calo hoặc phần trăm mục tiêu. Mức tạ theo từng bài, không so sánh bài khác nhau.

Khóa KH trước phân công/giáo án/lịch/phiên, đồng bộ với đổi PT và áp dụng giáo án. Transaction chứa thay đổi trạng thái, hiệp, snapshot và nhận xét; unique chống lịch/phiên/nhận xét trùng. Không sửa T16–T20 nền; migration bổ sung giữ dữ liệu cũ.

## API đã triển khai

Prefix `/api/v1`, session Sanctum và tài khoản hoạt động; các thao tác ghi có CSRF. Tất cả phản hồi dữ liệu nhật ký có `Cache-Control: private, no-store`.

| Method | Path | Quyền |
| --- | --- | --- |
| GET/POST | `/khach-hang/lich-tap` | KH đọc/tạo lịch của chính mình |
| GET/PUT | `/khach-hang/lich-tap/{id}` | KH sở hữu; PUT chỉ phiên đang tập |
| POST | `/khach-hang/lich-tap/{id}/bat-dau` | KH sở hữu, ngày lịch đã đến |
| POST | `/khach-hang/lich-tap/{id}/hoan-thanh` | KH sở hữu, đủ kết quả thực tế |
| POST | `/khach-hang/lich-tap/{id}/huy` | KH sở hữu, chưa hoàn thành |
| GET/POST | `/pt/hoc-vien/{khachId}/lich-tap` | PT hiện phụ trách đọc/tạo lịch |
| GET | `/pt/lich-tap/{id}` | PT hiện phụ trách đọc kết quả |
| POST | `/pt/lich-tap/{id}/nhan-xet` | PT hiện phụ trách, phiên đã hoàn thành |

GET danh sách: `tu_ngay=2026-10-01&den_ngay=2026-10-31&page=1`, tùy chọn `trang_thai`. Khoảng tối đa 366 ngày, phân trang cố định 12 lịch. `data` là danh sách lịch; `meta` chứa phân trang, tên học viên, `hom_nay`, `giao_an_dang_dung` (các ngày/tên bài), `thong_ke`. Bộ lọc trạng thái chỉ lọc danh sách; thống kê vẫn gồm các phiên hoàn thành trong khoảng ngày đã chọn. GET chi tiết không nhận query bổ sung.

POST tạo (ID do API giáo án trả, UUID tạo ở client và giữ nguyên khi retry):

```json
{
  "ke_hoach_tap_id": 2,
  "ngay_thu": 1,
  "ngay_tap": "2026-10-03",
  "client_request_id": "772b1d03-31f7-4c48-9853-c0ee82433a24"
}
```

POST bắt đầu/hoàn thành/hủy nhận `{"updated_at":"2026-10-03 14:00:00.000001"}`. Giá trị là phiên bản UTC micro giây do API vừa trả, gửi nguyên vẹn; không tự tạo theo đồng hồ client. Thời điểm bắt đầu/hoàn thành/nhận xét trả ISO 8601; `ngay_tap` là ngày lịch Việt Nam.

PUT lưu toàn bộ bài trong phiên; `id` là **ID bài trong phiên**, không phải ID catalog. Thứ tự phần tử hiệp thành thứ tự hiệp, không gửi ID hiệp. Có thể gửi `hiep_tap: []` khi lưu nháp, nhưng khi hoàn thành mỗi bài phải có ít nhất một hiệp.

```json
{
  "updated_at": "2026-10-03 14:00:00.000001",
  "ghi_chu": "Giữ nhịp thở đều",
  "bai_tap": [
    {
      "id": 4,
      "hiep_tap": [
        {"so_lan_lap":12,"khoi_luong_kg":10.5,"nghi_giay":60},
        {"so_lan_lap":10,"khoi_luong_kg":null,"nghi_giay":60}
      ]
    }
  ]
}
```

Ghi chú và nhận xét tối đa 2.000 ký tự. Tạ null là chưa ghi; 0 là đã ghi 0 kg. Toàn bộ danh sách bài phải khớp snapshot phiên, không được chèn/xóa bài bằng payload kết quả. Trường lạ bị từ chối 422.

POST nhận xét: `{"noi_dung":"Giữ tư thế và nhịp thở đều","client_request_id":"9b7056c8-5e09-4f65-8d68-1e8a4f121d1e"}`. UUID + nội dung + người gửi chống trùng; cùng UUID khác nội dung/người gửi trả 409.

Response ghi/chi tiết dùng `status/message/data`. `data` có `id`, ngày, trạng thái, phiên bản, `phien`, `bai_tap` (snapshot và hiệp thực tế), `nhan_xet` và các cờ `co_the_bat_dau/co_the_ghi/co_the_huy/co_the_nhan_xet` do server tính. Tạo mới trả 201, retry hợp lệ trả 200. FE sử dụng cờ cho trình bày; BE vẫn kiểm tra lại quyền/trạng thái.

Failure cases: 401 chưa đăng nhập; 403 sai vai trò/phân công; 404 tài nguyên không tồn tại; 409 phiên bản cũ, giáo án không còn áp dụng, tổ hợp lịch trùng, payload retry không khớp, ngày chưa đến hoặc trạng thái không cho phép; 422 ngày/số liệu/payload không hợp lệ; 429 vượt rate limit. Lỗi transaction rollback toàn bộ. Khi 409, FE giữ phần đang nhập và cho tải lại có xác nhận; không ghi đè âm thầm.

## Cách xem

- KH: **Lịch & nhật ký tập** trong menu; chọn ngày trong giáo án đang dùng → tạo lịch → mở lịch → bắt đầu → thêm hiệp thực tế → lưu nháp/hoàn thành. Ngày tương lai chưa có nút bắt đầu. Hoàn thành/hủy xác nhận ngay trong trang.
- PT: **Học viên & giáo án → Lịch & nhật ký học viên**; xem thống kê, lên lịch và mở phiên hoàn thành để nhận xét. Không có nút ghi kết quả thay KH.
- Giáo án đang dùng có lối tắt **Lên lịch tự tập**. Chưa có giáo án đang dùng thì hiển thị hướng dẫn sang giáo án, không chặn vì chưa mua gói.
- Migration `2026_10_03_000040_bo_sung_nhat_ky_tap.php` đã chạy local. Máy clone/pull chạy `php artisan migrate` trong BE; không cần seed lại/fresh. `down` từ chối khi có UUID lịch/nhận xét mới để bảo toàn lịch sử.

[Bằng chứng kiểm thử và giới hạn](../verification/M06_NHAT_KY_TAP.md).
