# Hợp đồng API đề xuất

C33 thay unique theo nguồn ở C31: mỗi KH một giáo án đang áp dụng, chọn bản KH/xác nhận PT lưu trữ bản đang dùng bất kể nguồn. POST KH `/{id}/luu-tru` với version dùng cho cả bản PT của chính KH đã nhận, không cần phân công còn hiệu lực để ngừng. C34 mở POST KH `/{id}/ap-dung` cho bản PT đã gửi, đã xác nhận trước đây và đang lưu trữ, không cần PT duyệt lại; không bỏ qua xác nhận lần đầu hoặc phục hồi bản đã hủy. [Hợp đồng](features/KE_HOACH_TAP.md).

C32: KH POST `/khach-hang/ke-hoach/{id}/an|hien-lai` với `updated_at`; chỉ bản tự tạo đã hủy/lưu trữ. GET danh sách mặc định chưa ẩn, `da_an=1` lấy đã ẩn, trả `da_an`, `khach_an_luc` và cờ quyền. PT không bị lọc theo lựa chọn ẩn của KH. [Hợp đồng](features/KE_HOACH_TAP.md#ẩn--hiện-lại-giáo-án-tự-tạo--c32).

C31/M05 bổ sung KH tự tạo miễn phí: POST `/khach-hang/ke-hoach`, PUT `/{id}`, POST `/{id}/ap-dung|luu-tru|huy` cho bản tự tạo của chính KH; không cần gói/PT duyệt. GET danh sách nhận `nguon_tao=PT|KHACH_HANG`. PT đọc bản tự tạo theo phân công hiện tại nhưng không ghi thay. Nguồn/quyền do BE gán, UUID khi tạo/version khi ghi, khóa KH và unique đang áp dụng chung theo C33. [Hợp đồng chi tiết](features/KE_HOACH_TAP.md#kh-tự-tạo--c31).

Đã có `GET /api/v1/health`, Sanctum SPA và quản trị tài khoản; [hợp đồng tài khoản](features/TAI_KHOAN.md). Có GET công khai /bai-tap, /bai-tap/bo-loc, /bai-tap/{id}; [hợp đồng bài tập](features/BAI_TAP.md). Đã có GET công khai `/goi-tap`, `/goi-tap/{id}` và ADMIN quản lý tại `/admin/goi-tap`: GET danh sách/chi tiết, POST tạo với UUID, PUT sửa/PATCH trạng thái với phiên bản `updated_at`, CSRF cho ghi; [hợp đồng gói](features/GOI_TAP.md). Exception đã chuẩn hóa status/message/data/code/errors. Đặt mua/payOS/kích hoạt/đối soát/phân công M03 đã triển khai theo [hợp đồng](features/MUA_GOI_THANH_TOAN.md); lịch/chat bên dưới vẫn là đặc tả.

Đã triển khai giáo án mẫu: ADMIN có GET/POST `/admin/giao-an-mau`, GET/PUT `/{id}`, PATCH `/{id}/trang-thai`; PT có GET `/pt/giao-an-mau` và `/{id}` chỉ cho giáo án đã duyệt. Tất cả có auth/role/tài khoản hoạt động, ghi yêu cầu CSRF, UUID khi tạo và phiên bản khi sửa/duyệt. [Hợp đồng giáo án mẫu](features/GIAO_AN_MAU.md). M05 đã có PT soạn/gửi giáo án theo phân công và KH xác nhận trong 24 giờ; endpoint, scope, UUID/phiên bản và trạng thái nằm tại [hợp đồng giáo án cá nhân](features/KE_HOACH_TAP.md).

## Auth và namespace

Đã có Admin quản lý nhóm cơ: GET/POST `/admin/nhom-co`, GET/PUT `/{id}`, PATCH `/{id}/trang-thai`. Ghi yêu cầu CSRF; PUT/PATCH đối chiếu `updated_at` micro giây (NULL chỉ cho record cũ còn NULL); trùng mã 422, bản cũ 409. Không DELETE hoặc nhận sửa mã/tên nguồn; [hợp đồng nhóm cơ](features/NHOM_CO.md).

Đã triển khai quản trị bài tập cho ADMIN hoạt động: GET `/admin/bai-tap`, `/admin/bai-tap/bo-loc`, `/admin/bai-tap/{id}`; POST `/admin/bai-tap`, PUT `/admin/bai-tap/{id}`, PATCH `/admin/bai-tap/{id}/trang-thai` dưới prefix `/api/v1`. Không có DELETE; sửa/trạng thái dùng phiên bản `updated_at`, xung đột 409. Chi tiết payload và bảo toàn dữ liệu tại [hợp đồng bài tập](features/BAI_TAP.md).

- Nghiệp vụ dưới `/api/v1`; routes theo resource, kebab-case tiếng Việt không dấu.
- Sanctum SPA: lấy cookie CSRF từ `/sanctum/csrf-cookie`, đăng nhập qua route session `/dang-nhap`, logout `/dang-xuat`. Cấu hình middleware stateful/CORS/cookie theo tài liệu tại bootstrap.
- MB1 native: `/api/v1/mobile/dang-nhap|dang-xuat`, bearer KH/PT hạn cố định 30 ngày; web giữ cookie/CSRF. Quyền/ownership vẫn kiểm tra tại Backend. [Hợp đồng mobile](mobile/XAC_THUC.md).
- MB5 bổ sung `POST /api/v1/mobile/dang-ky`, `/mobile/quen-mat-khau`, `/mobile/dat-lai-mat-khau`; tái sử dụng service/broker, throttle riêng, response `data: null`, không tự cấp token. Đơn/AI native dùng API KH hiện có và UUID trong payload, không thêm header chống trùng chung. [Kiểm chứng MB5](verification/MOBILE_MB5.md).
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
| GET/POST | `/khach-hang/don-hang` | KH hiện tại, chỉ đơn của mình; tạo với UUID, giá/snapshot server |
| POST | `/khach-hang/don-hang/{id}/link-thanh-toan` | KH sở hữu, tạo/lấy lại link payOS cho đơn còn hạn 15 phút, giá server |
| GET | `/khach-hang/don-hang/{id}` | KH sở hữu, trạng thái đơn/gói từ server |
| POST | `/khach-hang/don-hang/{id}/dong-bo` | KH sở hữu; đọc payOS có chữ ký và xác minh khoản thu |
| GET | `/khach-hang/goi-cua-toi` | Gói còn hiệu lực và PT hiện tại của chính KH |
| GET | `/admin/don-hang`, `/admin/don-hang/{id}` | Admin, phân trang/lọc trạng thái và chi tiết khoản thu |
| PATCH | `/admin/thanh-toan/{id}/doi-soat` | Admin, ghi kết quả/lý do hoàn tiền thủ công, không chuyển tiền qua API |
| POST | `/payos/webhook` | Xác minh chữ ký payOS, mã đơn/link/số tiền/trạng thái; chống xử lý lặp, không dùng session người dùng |
| GET/POST | `/admin/phan-cong` | Admin, danh sách/transaction phân công, UUID + phiên bản, D03 |
| GET | `/admin/phan-cong/pt` | Admin, PT hoạt động, phân trang |
| GET/POST | `/pt/khung-gio` | PT hiện tại |
| POST | `/khach-hang/lich-hen` | KH + PT phụ trách + gói/slot hợp lệ |
| POST | `/pt/lich-hen/{id}/xac-nhan` | PT của lịch |
| POST | `/pt/lich-hen/{id}/hoan-thanh` | PT có quyền, chống trừ lặp |
| POST | `/lich-hen/{id}/huy` | Chính sách actor/thời hạn D04 |
| POST | `/pt/ke-hoach-tap` | KH đang được phân công |
| POST | `/khach-hang/ke-hoach-tap/{id}/xac-nhan` | KH sở hữu, đọc lại base/TTL |
| GET/POST | `/khach-hang/lich-tap` | KH sở hữu, lịch từ giáo án đang áp dụng |
| GET/PUT | `/khach-hang/lich-tap/{id}` | KH sở hữu; PUT kết quả nháp với phiên bản |
| POST | `/khach-hang/lich-tap/{id}/{hanhDong}` | KH sở hữu: bat-dau/hoan-thanh/huy |
| GET/POST | `/pt/hoc-vien/{khachId}/lich-tap` | PT hiện phụ trách đọc/tạo lịch |
| GET | `/pt/lich-tap/{id}` | PT hiện phụ trách đọc kết quả |
| POST | `/pt/lich-tap/{id}/nhan-xet` | PT hiện phụ trách, phiên hoàn thành, UUID |
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

## Endpoint M01 đã bổ sung ngày 02/10/2026
| Method | Path đầy đủ | Quyền và dữ liệu |
| --- | --- | --- |
| PUT | /api/v1/me/ho-so | Chính người hoạt động; ho_ten, updated_at và trường hồ sơ theo vai trò |
| PATCH | /api/v1/admin/tai-khoan/{id}/trang-thai | Admin hoạt động; trang_thai, updated_at; cấm tự khóa |
| POST | /quen-mat-khau | Chưa đăng nhập; email; trả thông báo chung cho email không tồn tại/bị khóa |
| POST | /dat-lai-mat-khau | Chưa đăng nhập; email, token, password, password_confirmation |

Tất cả endpoint ghi trên có CSRF. CORS bao gồm cả hai route session mới. API hồ sơ/trạng thái nhận version micro giây hoặc NULL cho record cũ còn NULL; bản cũ trả 409, dữ liệu ngoài danh sách cho phép trả 422. Khôi phục có rate limit 429, SMTP không khả dụng trả 503 chung không lộ credentials. Reset token hết hạn/sai/đã dùng/bị khóa trả lỗi 422 chung; thành công thu hồi phiên, không tự đăng nhập. Chi tiết request, giới hạn, transaction và kiểm thử tại [hợp đồng tài khoản](features/TAI_KHOAN.md), [kiểm chứng](verification/M01_HO_SO_KHOI_PHUC.md).

## Tổng quan KH/PT/Admin đã triển khai
GET /api/v1/khach-hang/tong-quan, /api/v1/pt/tong-quan và /api/v1/admin/tong-quan yêu cầu đúng vai trò và tài khoản hoạt động. Response status/message/data, Cache-Control: private, no-store. Data gồm vai_tro/cap_nhat_luc/thu_vien; KH/PT có ho_so (số mục/checklist), PT thêm giao_an_da_duyet; chỉ Admin có quan_tri (aggregate tài khoản và catalog). Query user ID/vai trò không thay đổi scope. 401/403 theo quyền; không ghi DB, không phát sinh gói/quyền sử dụng. [Hợp đồng chi tiết](features/TONG_QUAN.md).

KH bổ sung `data.hanh_trinh`: lịch và phiên tự tập, giáo án đang áp dụng, gói snapshot/hạn mức AI, PT hiện tại, BMI và tiến độ. Query `so_ngay=7|30|90`, mặc định 30, sai định dạng/khoảng trả 422. Khoảng này đổi biểu đồ tiến độ; BMI vẫn 30 ngày. Các aggregate lấy KH từ session, không nhận ID để mở rộng quyền; GET không bắt đầu buổi tập, gọi Gemini hoặc trừ lượt. Chi tiết cách tính tỷ lệ, khoảng ngày Việt Nam và trạng thái trống trong hợp đồng tổng quan.

Ngày06/10/2026 biểu đồ web bổ sung cả PT: `hanh_trinh.tien_do` và từng hàng `theo_ngay` thêm `so_buoi_tu_tap`, `so_buoi_pt`, `tong_so_buoi`. PT chỉ tính lịch HOAN_THANH đã kết thúc, theo ngày bắt đầu ở giờ Việt Nam; không yêu cầu gói/phân công hiện tại hoặc kết quả chi tiết. Bộ lọc khoảng áp dụng cho cả hai chuỗi; BMI vẫn30 ngày. Giữ trường `so_buoi` là tự tập để không đổi client Mobile cũ; KPI/tỷ lệ tự tập tháng này giữ nguyên. Không thêm quyền ghi hoặc hiệu ứng khi GET.

PT bổ sung `data.huan_luyen`: lịch hôm nay, việc chờ xử lý, học viên, giáo án, preview chat, tuần PT và khung giờ 7 ngày. Backend lấy PT từ session/phân công hiện tại; bỏ tham số ID tùy ý, không mở rộng quyền. Preview chat dùng cursor PT, không đánh dấu đọc. Deadline được tính động; GET không xác nhận hẹn, gửi giáo án hay tiêu hao buổi. [Cách tính và giới hạn hàng](features/TONG_QUAN.md#dashboard-pt-theo-thiết-kế-cung-cấp--04102026).

## M04 lịch huấn luyện đã triển khai

C42 bổ sung GET `/{khach-hang,pt}/lich-hen/{id}/ket-qua`, PUT `/pt/lich-hen/{id}/ket-qua`, POST `/pt/lich-hen/{id}/ket-qua/chot`. Quyền PT theo lịch/phân công, KH chỉ đọc; `updated_at` micro giây chống ghi đè, retry nội dung trùng không sinh audit/thông báo mới. Response private/no-store gồm lịch, kết quả nullable, `co_the_ghi`, `co_the_chot`, `ly_do_khoa`. Không đổi endpoint xác nhận lịch hoặc counter; [hợp đồng C42](features/KET_QUA_BUOI_PT.md).

Danh sách endpoint/payload/quyền/trạng thái/retry/deadline tại [LICH_HUAN_LUYEN.md](features/LICH_HUAN_LUYEN.md). Ba khu vực KH/PT/Admin dùng scope riêng. Ghi lịch/ghi nhận/trừ lượt/audit đi qua transaction; giờ Việt Nam chỉ ở UI/query ngày, thời điểm API lưu UTC. Bằng chứng chạy MariaDB, kiểm thử hai process và UI responsive tại [M04_LICH_HUAN_LUYEN.md](verification/M04_LICH_HUAN_LUYEN.md).

## Tiện ích mobile C44

C44 bổ sung bearer GET/PUT/DELETE `/api/v1/mobile/thong-bao-day`: đăng ký/tắt push chỉ phiên thiết bị hiện tại, PUT nhận `expo_token`; không nhận ID tài khoản từ client và không trả token ở GET. Reset yêu cầu từ mobile gửi link scheme FitForge; link payOS mới tạo từ bearer mobile quay về cầu nối công khai. Sự kiện private `lich.cap-nhat` chỉ báo cần tải lại, không truyền dữ liệu lịch và không thay quyền HTTP. [Hợp đồng và cấu hình C44](mobile/TIEN_ICH_THIET_BI.md).

## Chỉ số cơ thể C38 đã triển khai

KH GET/POST `/api/v1/khach-hang/chi-so-co-the`, PUT `/{id}`; PT GET `/api/v1/pt/hoc-vien/{khachId}/chi-so-co-the`. KH chỉ truy cập bản thân; PT phải đang được phân công, không có quyền ghi. Payload không nhận vòng eo, BMI hoặc ID khách hàng; BMI tính ở server. POST cùng ngày/cùng dữ liệu trả bản cũ, khác dữ liệu trả409; PUT kiểm tra phiên bản micro giây và transaction. Danh sách có khoảng7/30/90 ngày, ngày kết thúc, phân trang và `private,no-store`. [Hợp đồng đầy đủ](features/CHI_SO_CO_THE.md).
