# Thiết kế database

Tài liệu này mô tả thư mục `BE/database/design/` trong source dự án. Đường dẫn source/lệnh tính từ gốc dự án hoặc thư mục được ghi ở từng bước; tài liệu đã chuyển sang `md/backend/`.

**28 bảng nghiệp vụ, 52 khóa ngoại**, MySQL/InnoDB/utf8mb4. Đã có [28 migrations Laravel](MIGRATIONS.md), đạt kiểm tra cú pháp PHP, đối chiếu tĩnh thiết kế và chạy thực tế trên MariaDB 10.4.32; [bằng chứng](../verification/MARIADB_MIGRATIONS.md). Chưa chạy SQL/migrations trên MySQL thật. Không chứa dữ liệu tài khoản/khách hàng thật hoặc khóa API.

- [schema.mysql.sql](../../BE/database/design/schema.mysql.sql): DDL, PK/FK, UNIQUE, CHECK và index; bản thiết kế tham khảo. Khi dùng migrations không nhập file này để tạo trùng bảng. Không tự CREATE DATABASE, DROP hoặc tắt FK.
- [schema.json](../../BE/database/design/schema.json): mô tả cấu trúc máy đọc được.
- [Từ điển dữ liệu](../DATABASE_DICTIONARY.md): đầy đủ cột và ràng buộc.
- [Sơ đồ draw.io](../../docs/diagrams/database.drawio): đủ 28 bảng/303 cột/52 FK trên **một canvas**, theo mẫu `Database.drawio` chủ dự án cung cấp. Bảng có cột PK/FK riêng, tên trường từng hàng và đường nối chân quạ từ PK đến hàng FK. [SVG tổng thể](../../docs/diagrams/database-full.svg) để phóng to và [PNG tổng thể](../../docs/diagrams/database-full.png) để xem nhanh.
- [Bản chi tiết cũ](../../docs/diagrams/database-chi-tiet.drawio): giữ nguyên bản tổng quan + 28 tab trước khi đổi phong cách; không phải database khác.
- [Dữ liệu bài tập](DATA.md): hướng dẫn nhập catalog và dùng ảnh/GIF.

Nguồn thiết kế là `scripts/databaseSchema.mjs`; generator `scripts/chuanBiDuLieu.mjs` tạo SQL/JSON/từ điển/bản vẽ chi tiết đầu vào. `scripts/veDatabaseTongThe.mjs` tạo `docs/diagrams/database-single.generated.drawio` với một canvas theo mẫu, kiểm tra đường nối không xuyên bảng. Bản `database.drawio` xuất qua draw MCP có thể biên tập riêng; không tự ghi đè bản người dùng đã chỉnh khi chạy generator. Yêu cầu đổi phong cách ngày 01/10/2026 chỉ thay cách vẽ, không đổi cột/FK/SQL hoặc dữ liệu bài tập. File mẫu trong Downloads không bị sửa.

## Những phần SQL không thay thế được

`md/PROJECT_RULES.md` vẫn là nguồn quy tắc chính. SQL chỉ cung cấp lớp ràng buộc dữ liệu; khi triển khai Laravel phải kiểm tra quyền, trạng thái và thời gian trong service/transaction:

- Profile phải khớp vai trò tài khoản; các FK KH/PT trỏ profile, người thao tác trỏ tài khoản. FK riêng lẻ không chứng minh các profile/slot/phân công/gói trong lịch thuộc cùng khách/PT.
- `khach_dang_dung_id` unique bảo vệ trạng thái `DANG_SU_DUNG`, không tự đọc đồng hồ. Dưới khóa hàng KH, kiểm tra hết hạn và đóng trạng thái cũ trước cấp gói mới. Gói có chatbot không kết thúc khi hết buổi PT.
- Snapshot trên đăng ký giữ giá/quyền lợi tại lúc tạo đơn, hạn thanh toán 15 phút. `ma_don_payos`/`ma_link_payos` trên đơn, một dòng `thanh_toan` cho mỗi giao dịch thực nhận, `ma_giao_dich` unique. Chỉ chèn sau xác minh chữ ký/nguồn; không lưu secrets hoặc payload nhạy cảm. Việc ghi khoản thu và kích hoạt đúng một lần cần transaction khóa KH/đơn; ngoại lệ chỉ đối soát, hoàn thủ công có số tiền/mã/lý do.
- Một phân công mở/KH nhờ generated key; chồng khoảng lịch sử vẫn phải kiểm tra dưới khóa KH. `bat_dau_luc` không để NULL khi tạo phân công thật.
- Unique slot chỉ giữ `CHO_XAC_NHAN`/`DA_XAC_NHAN`; service loại yêu cầu quá deadline ngay cả khi worker chưa chạy. KH/PT không chồng giờ phải kiểm tra dưới khóa ổn định, không chỉ unique slot. Kiểm tra số buổi còn lại và các lịch còn giữ quyền để tránh đặt vượt số buổi; không trừ buổi ngay khi đặt.
- Lịch gắn đúng đăng ký đã mua, kết thúc trong hạn gói. Xác nhận hoàn thành trong 24 giờ sau kết thúc, cập nhật `tieu_hao_luc` và counter gói nguyên tử đúng một lần. Quá hạn giữ trạng thái quá hạn; Admin chỉ đóng xử lý có lý do/audit, không xác nhận thay PT. Chặn đổi PT khi còn buổi đang diễn ra/chưa xử lý.
- Unique kế hoạch đang áp dụng, snapshot và bản thay thế không cho phép tự sửa lịch sử. KH duyệt trong 24 giờ; PT lập lịch sau duyệt; phiên hoàn thành bất biến, nhận xét lưu riêng. Các quan hệ KH/kế hoạch/lịch/phiên phải được đối chiếu trong service.
- Chat PT theo `phan_cong_id`, không theo hiệu lực gói. Cursor là ID tin nhắn trong đúng hội thoại, không phải FK tùy ý; validate không vượt ID được phép và không giảm cursor. Đổi PT thu hồi cả kết nối đang mở; Admin không đọc chat riêng. Unique client ID không thay authorization.
- Chatbot khóa KH để kiểm tra quyền và đếm request `THANH_CONG` cộng request `DANG_XU_LY` đang giữ lượt trong cùng `ngay_han_muc`. Lưu giữ chỗ `giu_luot_den`, commit rồi mới gọi AI; không giữ transaction trong lúc chờ mạng. Hoàn tất kiểm tra lại request/lease trước chuyển trạng thái và lưu câu trả lời. Lease hết hạn phải đóng request/cách ly phản hồi muộn; không cho phản hồi muộn lấn lượt đã cấp request khác. Lỗi không mất lượt, retry cùng client ID không sinh dòng mới; không đổi gói/ngày của request cũ để tính lặp. Dự kiến ngày quota tại lúc nhận request theo Việt Nam; cần đặc tả/test mốc qua nửa đêm trước implementation.
- `DATETIME` là UTC. `ngay_han_muc`, `ngay_tap`, `ngay_ghi` là ngày lịch nghiệp vụ theo Việt Nam, không phải timestamp cần đổi UTC.
- `ON DELETE RESTRICT` bảo toàn lịch sử; khóa tài khoản/ngừng catalog dùng trạng thái. Không ghi toàn bộ chat/hồ sơ riêng trong `metadata_an_toan` của audit.

Các trạng thái dạng VARCHAR là thiết kế kỹ thuật cần cụ thể hóa thành enum/transition và FormRequest khi triển khai; không mở endpoint cập nhật trạng thái tùy ý. Migrations hỗ trợ MySQL 8.0.16+ hoặc MariaDB 10.4+ có thực thi CHECK; chạy test transaction/cạnh tranh trên MySQL trước khi dùng. Bảng kỹ thuật Laravel như notifications/sessions/jobs/cache không tính vào 28 bảng nghiệp vụ.
