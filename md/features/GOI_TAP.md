# M02 — Danh mục gói tập

## Hợp đồng triển khai

- Actor: khách chưa đăng nhập xem bảng giá/chi tiết; ADMIN đang hoạt động quản lý toàn bộ danh mục. KHACH_HANG và HUAN_LUYEN_VIEN không được ghi catalog.
- Dữ liệu T04: `ten_goi`, `gia` (VND nguyên), `co_chatbot`, `so_luot_chatbot_moi_ngay`, `so_buoi_pt`, `thoi_han_ngay`, `trang_thai`. Chỉ hai loại đã chốt: chatbot riêng (0 buổi PT), PT kèm chatbot (>0 buổi). Cả hai phải bật chatbot, hạn mức ngày >0 và thời hạn >0.
- Trạng thái: `HOAT_DONG` hiển thị bảng giá; `NGUNG_SU_DUNG` ẩn. Tạo mặc định trên giao diện là ngừng bán để Admin kiểm tra trước khi mở bán. Không có DELETE.
- Validation kỹ thuật: tên 1–255 ký tự; giá 1–9.007.199.254.740.991 VND (số nguyên an toàn JavaScript); thời hạn 1–36.500 ngày để tránh tràn phép tính thời gian; số lượt chatbot 1–4.294.967.295; buổi PT 0–4.294.967.295. Backend không suy quyền lợi từ tên. Không nhận các trường ngoài hợp đồng.
- Không tự đặt giá thương mại hoặc phát hành gói demo. Khi chưa có gói đang bán, trang công khai hiển thị trạng thái trống.

## Endpoint

| Phương thức | URL dưới `/api/v1` | Quyền |
|---|---|---|
| GET | `/goi-tap`, `/goi-tap/{id}` | Công khai, chỉ gói hợp lệ đang bán |
| GET | `/admin/goi-tap`, `/admin/goi-tap/{id}` | ADMIN |
| POST | `/admin/goi-tap` | ADMIN, CSRF, thêm `client_request_id` UUID |
| PUT | `/admin/goi-tap/{id}` | ADMIN, CSRF, thêm `updated_at` |
| PATCH | `/admin/goi-tap/{id}/trang-thai` | ADMIN, CSRF, chỉ trạng thái + `updated_at` |

Danh sách phân trang 12 mặc định, tối đa 48; `page` 1–100.000; lọc `tu_khoa` tối đa 100 ký tự, `loai_goi` CHATBOT / PT_CHATBOT; Admin có thêm `trang_thai`. Tìm tên với `%`, `_` literal; thứ tự ID ổn định. Response theo API_CONVENTIONS; Admin mới thấy trạng thái/phiên bản. Gói ẩn hoặc ID không tồn tại trả 404 công khai.

## Transaction, chống trùng và lịch sử

- Migration bổ sung nullable `ma_yeu_cau_tao` UUID + unique index T04; không sửa migration đã chạy. Một UUID tạo tối đa một gói, kể cả retry hoặc request song song. Gửi lại cùng UUID/cùng nội dung trả gói cũ; khác nội dung trả 409. UUID không xuất ra public API.
- Sửa/trạng thái dùng transaction + khóa dòng + so sánh `updated_at` micro giây. Phiên bản cũ trả 409, giao diện yêu cầu tải lại, không tự ghi đè.
- Chuyển trạng thái không hợp lệ, payload thừa, giá thập phân/âm, chatbot tắt hoặc quota 0 trả 422; bật bán gói cũ cũng phải kiểm tra quyền lợi hợp lệ.
- Chỉ cập nhật T04, không ghi T05/T06, không thay snapshot hoặc số buổi còn lại của đơn/gói đã cấp. FK và ID được giữ nguyên khi ngừng bán.
- Bảng giá/chi tiết chỉ đọc danh mục. Chưa có đặt mua, payOS, kích hoạt gói hay cấp quyền chatbot/PT trong use case này.

## Frontend và kiểm thử

- Options API; service Axios dùng chung; CSRF trước ghi. Tạo giữ UUID trong một lần mở biểu mẫu, khóa double-submit, retry lỗi mạng giữ nội dung/UUID; reload/mở biểu mẫu mới là yêu cầu tạo mới.
- URL công khai `/goi-tap`, `/goi-tap/:id`; Admin `/admin/goi-tap`, `/admin/goi-tap/them`, `/admin/goi-tap/:id/sua`. Bộ lọc/page lưu trên URL, hủy bỏ response cũ khi đổi route. Có loading, empty, lỗi/thử lại, 404 và 409.
- Kiểm thử trên DB MySQL driver/MariaDB riêng: toàn bộ endpoint 401/403, account ngừng hoạt động, CSRF, hai loại gói, giá/quota/thời hạn, public ẩn gói, phân trang/filter literal, UUID retry/khác payload, phiên bản cũ, snapshot T05 giữ nguyên, không ghi thanh toán/cấp quyền. Không tuyên bố kiểm thử cạnh tranh nhiều process khi chưa chạy.
