# M04 — Lịch huấn luyện

Triển khai ngày 02/10/2026 theo R07–R11, R14, R24 của [PROJECT_RULES](../../PROJECT_RULES.md). Lịch PT tách biệt lịch tự tập và kế hoạch cá nhân M05. Có [bằng chứng kiểm thử](../verification/M04_LICH_HUAN_LUYEN.md).

## Use case, quyền và dữ liệu

| Actor | Use case | Dữ liệu / quyền theo tài nguyên |
| --- | --- | --- |
| PT | Mở/đóng/mở lại giờ rảnh | Chỉ khung giờ của chính PT, tương lai, 60 phút. Không mở chồng giờ; không đóng giờ đang được giữ. Không xóa lịch sử. |
| KH | Chọn giờ, gửi yêu cầu | Chỉ PT của phân công đang mở, PT hoạt động, gói PT còn hiệu lực/còn buổi, kết thúc buổi <= hạn gói. |
| KH | Xem/hủy lịch | Chỉ lịch của KH; hủy trước ít nhất 2 giờ, lý do bắt buộc. Giữ quyền đọc lịch sử khi hết gói. |
| PT | Xác nhận/từ chối | Chỉ lịch gắn với PT và phân công vẫn đang mở; yêu cầu chưa hết deadline. Từ chối lưu actor/thời gian/lý do, trạng thái DA_HUY. |
| PT | Hoàn thành/vắng mặt | Chỉ buổi DA_XAC_NHAN đã kết thúc, trước mốc kết thúc +24 giờ. Vắng mặt lưu lý do, không trừ lượt. |
| Admin | Xem tất cả/đóng quá hạn | Chỉ đóng buổi QUA_HAN_XAC_NHAN, có lý do/actor/thời gian/audit. Không hoàn thành thay PT, không sửa counter. |

Middleware kiểm tra session, tài khoản hoạt động và actor. Service giới hạn KH theo hồ sơ, PT theo hồ sơ + phân công đang mở; người ngoài nhận 404 khi truy cập tài nguyên bằng ID. Backend không tin role/ownership/state/counter do FE gửi.

## API đã triển khai

Prefix `/api/v1`; response theo [API_CONVENTIONS](../API_CONVENTIONS.md), private no-store. List phân trang, ISO 8601 UTC cho thời điểm; tham số `ngay=YYYY-MM-DD` được hiểu theo Asia/Ho_Chi_Minh.

| Method | Path | Payload / query |
| --- | --- | --- |
| GET | `/khach-hang/khung-gio` | `ngay` bắt buộc, `page`; chỉ giờ còn trống >=4h, kết thúc trong hạn gói; meta PT/số buổi/lý do chưa đủ quyền. |
| POST | `/khach-hang/lich-hen` | `khung_gio_id`, `client_request_id` UUID; không nhận KH/PT/gói/thời gian/trạng thái client. |
| GET | `/{khach-hang,pt,admin}/lich-hen` | `page`, `ngay`, `trang_thai` tùy chọn. Deadline tính động khi lọc. |
| GET | `/{khach-hang,pt,admin}/lich-hen/{id}` | Chi tiết, lý do/thời gian, quyền thao tác hiện tại trong `hanh_dong`. |
| POST | `/khach-hang/lich-hen/{id}/huy` | `ly_do` 1–1000 ký tự, không toàn khoảng trắng. |
| GET | `/pt/khung-gio` | `ngay` bắt buộc, `page`; giờ mở/đóng/đang được giữ. |
| POST | `/pt/khung-gio` | `bat_dau_luc` ISO có Z/offset; server cộng đúng 60 phút. |
| PATCH | `/pt/khung-gio/{id}` | `trang_thai` MO/DONG; không sửa thời gian đã tạo. |
| POST | `/pt/lich-hen/{id}/xac-nhan` | Không cần lý do. |
| POST | `/pt/lich-hen/{id}/tu-choi` | `ly_do`. |
| POST | `/pt/lich-hen/{id}/hoan-thanh` | Không nhận counter/thời gian xác nhận client. |
| POST | `/pt/lich-hen/{id}/vang-mat` | `ly_do`. |
| POST | `/admin/lich-hen/{id}/dong-xu-ly` | `ly_do`. |

401 mất session; 403 sai role/tài khoản khóa; 404 ngoài scope; 409 deadline/overlap/quyền gói/trạng thái thay đổi; 422 validation. Mutation đặt lịch có throttle 20/phút.

## Trạng thái, deadline và transaction

- Slot MO/DONG. Mở cùng PT/cùng giờ bắt đầu là retry trả record cũ nếu đang MO; muốn mở lại giờ đã đóng phải PATCH. Không đổi giờ/xóa record cũ.
- Đặt hợp lệ tạo CHO_XAC_NHAN. Deadline là min(thời điểm tạo +2h, bắt đầu −2h). Tại đúng deadline, yêu cầu HET_HAN, PT không thể xác nhận dù scheduler chưa chạy. CHO_XAC_NHAN/DA_XAC_NHAN giữ slot; HET_HAN/DA_HUY giải phóng generated UNIQUE.
- Xác nhận → DA_XAC_NHAN. Hủy/từ chối → DA_HUY, lưu người/thời điểm/lý do. Hết hạn tự động có lý do, actor NULL vì hệ thống xử lý.
- Hoàn thành → HOAN_THANH, lưu PT/thời điểm + tieu_hao_luc, trừ đúng 1 buổi ở dang_ky_goi_tap_id gốc; counter/phần lịch/audit cùng transaction. Không mượn gói mới, không âm lượt. Gói vừa hết hạn vẫn được trừ nếu buổi diễn ra trong hạn và xác nhận đúng hạn.
- Vắng mặt → VANG_MAT; lưu PT/thời điểm/lý do, audit, không trừ/không phạt. Không sửa kết quả thành loại khác sau xử lý.
- Tại kết thúc +24h, DA_XAC_NHAN trở thành QUA_HAN_XAC_NHAN theo thời gian thực. Admin đóng vẫn giữ trạng thái này, lưu dong_xu_ly_luc/actor/lý do/audit; sau đó không chặn đổi PT.
- Buổi chờ/đã xác nhận chưa quá hạn giữ quyền đặt trong số buổi còn lại; không giảm counter lúc đặt. Hủy, từ chối, hết hạn hoặc quá hạn xác nhận giải phóng quyền giữ này. Chặn tạo thêm lịch vượt số buổi hợp lệ để các buổi đã được nhận đều có lượt hoàn thành.
- Thứ tự ghi lịch: khóa hồ sơ KH → hồ sơ PT → record liên quan. Khóa KH tuần tự hóa overlap/quota/đổi phân công; khóa PT tuần tự hóa slot và overlap giữa KH. UNIQUE generated giữ slot là lớp bảo vệ DB; không chỉ dựa vào exists ở FE. Transaction retry deadlock tối đa 3 lần.
- Dọn pending trong transaction giữ KH hoặc PT, chỉ cập nhật record hết deadline, không lấy thêm khóa hồ sơ theo thứ tự ngược. Worker từng record khóa KH trước PT. Phân công dọn pending của KH trước kiểm tra buổi quá khứ, tránh yêu cầu đã hết hạn chặn đổi PT.
- UUID `(khach_hang_id, client_request_id)` chống double-submit; cùng UUID/slot trả record cũ, đổi slot trả409. Hoàn thành lặp không trừ thêm/audit thêm. Hủy/đóng xử lý retry cùng actor/lý do; thông tin khác trả409. Ghi nhận vắng mặt retry cùng PT/lý do.
- DB giữ UTC DATETIME(6), so sánh deadline truy vấn giữ microsecond. FE chuyển ngày/giờ Việt Nam sang ISO UTC, không phụ thuộc múi giờ máy.

## Dữ liệu runtime và chạy local

Dùng T08/T09 từ Draw.io và migrations gốc. Migration000032 chỉ thêm `nguoi_ghi_nhan_id` FK RESTRICT, `ghi_nhan_luc` DATETIME(6), `ly_do_ghi_nhan` nullable; tách kết quả PT khỏi thông tin Admin đóng xử lý. Không sửa bản thiết kế gốc hay xóa dữ liệu.

Chạy từ BE sau khi pull code:

```powershell
php artisan migrate
php artisan serve
```

Ở terminal BE khác, chạy scheduler local:

```powershell
php artisan schedule:work
```

Đã đăng ký `lich-hen:don-qua-han` mỗi phút, withoutOverlapping. Có thể chạy một lần bằng `php artisan lich-hen:don-qua-han`. Khi deploy, cấu hình Laravel scheduler của môi trường. Không cần scheduler để ngăn đặt/xác nhận sai deadline; GET và mutation đều kiểm tra thời gian thực.

FE: `npm run dev` từ FE. KH mở `/khach-hang/dat-lich` hoặc header Lịch hẹn; PT `/pt/khung-gio`, `/pt/lich-hen`; Admin `/admin/lich-hen`. Có list/filter/paging, detail/action confirmation, trạng thái tải/rỗng/lỗi, responsive; guard chỉ điều hướng, quyền quyết định ở BE. Chưa thêm seeder lịch hẹn vì không tự cấp gói đã thanh toán hoặc gán lịch cho tài khoản thật.

## Kiểm thử và giới hạn

LichHenTest tạo/migrate database MariaDB ngẫu nhiên riêng, transaction từng test và hai process PHP thật. Kiểm tra thời hạn/ownership/role, slot60p/overlap, giữ quyền, pending hết hạn không cần worker, hủy/từ chối/vắng mặt, gói vừa hết hạn, zero quota, hoàn thành retry/rollback/audit, Admin quá hạn/đổi PT/thu hồi PT cũ. Worker chỉ chấp nhận tên DB riêng đúng prefix và kiểm tra SELECT DATABASE trước ghi.

Vitest kiểm tra đổi giờ VN, double-submit/UUID retry, response cũ, logout, quyền thao tác/lý do và render dữ liệu đủ cho ba vai trò. UI QA dùng component thật + fixture/mock service trong bộ nhớ, không sử dụng dữ liệu người thật để lấy ảnh; preview tạm được xóa sau QA. Kết quả MariaDB10.4.32 chưa chứng minh MySQL8. Chưa triển khai giáo án cá nhân, lịch tự tập/nhật ký, chat hoặc báo cáo lịch nâng cao.
