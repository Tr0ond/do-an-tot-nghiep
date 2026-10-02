# M03 — Mua gói, payOS và phân công PT

Theo yêu cầu chủ dự án ngày 02/10/2026 và R05–R08: KH đặt mua gói đang bán; Backend lưu snapshot giá/quyền lợi, chờ 15 phút. Mỗi KH một gói còn hiệu lực và một đơn chờ. UUID retry giữ nguyên đơn, UUID khác khi còn đơn chờ trả 409. Gói hết hạn tính theo thời gian server, không phụ thuộc cron.

## API và quyền

- KH: GET/POST `/api/v1/khach-hang/don-hang`, GET `/{id}`, POST `/{id}/link-thanh-toan`, POST `/{id}/dong-bo`; chỉ đơn của chính KH. GET `/khach-hang/goi-cua-toi` trả gói còn hiệu lực và PT hiện tại. Không tin giá, quyền hoặc trạng thái từ client.
- payOS: POST `/api/v1/payos/webhook`, không cookie/CSRF; bắt buộc chữ ký HMAC. Thông báo chỉ kích hoạt đồng bộ dữ liệu có chữ ký từ payOS; đối chiếu mã đơn/link/số tiền/giao dịch và thời điểm tiền. Webhook mẫu/đơn không thuộc ứng dụng được ACK, không tạo dữ liệu.
- Admin: GET `/admin/don-hang`, GET `/{id}`; PATCH `/admin/thanh-toan/{id}/doi-soat` ghi lý do/kết quả hoàn tiền thủ công, không gọi API hoàn tiền/cấp gói thủ công. GET `/admin/phan-cong`, GET `/admin/phan-cong/pt`, POST `/admin/phan-cong` phân công/đổi PT có UUID và phiên bản phân công hiện tại.

## Trạng thái và nguyên tử

Đơn: CHO_THANH_TOAN → DANG_SU_DUNG / HET_HAN_THANH_TOAN / DA_HUY / CAN_DOI_SOAT. Gói DANG_SU_DUNG hết hạn → HET_HAN. Khoản thu: DA_XAC_MINH / CAN_DOI_SOAT / DA_HOAN_TIEN. Giá/quyền lợi đã mua không thay khi catalog sửa.

Không giữ transaction lúc gọi payOS. Khóa profile KH trước đơn khi tạo/cấp gói/đổi PT; unique mã đơn/link/giao dịch/UUID/gói đang dùng. Khoản thu và kích hoạt ghi cùng transaction; retry không cấp lại hoặc dời thời hạn. Đúng số tiền, tất cả thời điểm giao dịch trong hạn và không có gói khả dụng thì kích hoạt ở lúc Backend ghi nhận; tiền thiếu/thừa/quá hạn hoặc có gói khác được lưu đối soát. Dữ liệu return URL không chứng minh thanh toán. Tiền thiếu được giữ đối soát; nếu tổng các giao dịch sau đó đủ trong hạn và chưa hoàn tiền/chưa có gói khác thì xác minh lại khoản thu và kích hoạt một lần. Khoản đã hoàn thủ công không được cấp gói khi đồng bộ lại.

PT và KH phải hoạt động, KH phải có gói PT còn hạn/còn buổi khi phân công mới. Danh sách Admin gồm KH có gói PT khả dụng hoặc phân công đang mở; đổi phân công đã có không cần mua lại gói, phù hợp chat theo phân công độc lập gói. Đổi PT đóng khoảng cũ, hủy lịch tương lai, vô hiệu đề xuất chưa duyệt, ghi audit trong cùng transaction. Chặn buổi đang diễn ra hoặc đã diễn ra chưa xử lý; giữ lịch sử/kế hoạch đã duyệt. PT cũ mất quyền qua kiểm tra phân công hiện tại; realtime bổ sung M07.

## Failure cases và kiểm thử

401/403 đúng vai trò/trạng thái, 404 tài nguyên khác KH, 422 payload sai, 409 UUID khác payload/đơn chờ/gói khả dụng/phiên bản cũ/buổi chưa xử lý; lỗi cổng thanh toán 503 và có thể retry cùng mã đơn. Kiểm tra chữ ký sai, link/mã/số tiền sai, tiền đến muộn, webhook trước lưu link, partial/overpaid, double-submit, webhook retry, rollback, quyền đối soát/phân công. Không tự chuyển tiền để kiểm thử.

## Tích hợp

Khóa chỉ trong BE/.env, config payos; không ghi response/log. Dùng Laravel HTTP Client theo [API payOS](https://payos.vn/docs/api/) và [quy tắc chữ ký](https://payos.vn/docs/tich-hop-webhook/kiem-tra-du-lieu-voi-signature/), không thêm SDK. Kết nối dùng CA công khai tại BE/resources/certs/cacert.pem, giữ kiểm tra HTTPS và không theo redirect. Webhook cần URL HTTPS Backend truy cập được từ payOS; localhost tự đồng bộ qua nút kiểm tra thanh toán. Ngày không có múi giờ từ payOS được đọc theo Asia/Ho_Chi_Minh, DB lưu UTC. Cần xác minh luồng tiền thật và webhook công khai khi triển khai.
