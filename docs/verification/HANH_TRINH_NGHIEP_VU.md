# Kiểm chứng hành trình KH–PT–Admin — 04/10/2026

## Phần triển khai

Thêm [HanhTrinhNghiepVuTest.php](../../BE/tests/Feature/HanhTrinhNghiepVuTest.php) để nối API thật qua nhiều vai trò, thay vì chỉ kiểm thử riêng từng module. Chỉ phản hồi payOS/Gemini được giả lập; schema, middleware, validation, quyền, service, transaction và trạng thái nghiệp vụ dùng code thật.

Thêm [fixture demo](../../BE/tests/Support/hanh-trinh-demo.php), [launcher PowerShell](../../scripts/start-demo.ps1) và [kịch bản trình diễn](../DEMO_SCRIPT.md). Database ngẫu nhiên riêng, kiểm tra tên trước xóa, không chạy fixture/migration trên dữ liệu ứng dụng chính; không sửa `.env`. Launcher mở server riêng và dọn đúng tiến trình/database khi bấm Enter.

## Lỗi đã sửa

Middleware giới hạn tần suất dạng số trước đây dùng chung khóa theo người dùng, khiến yêu cầu hợp lệ ở thao tác này có thể tiêu hao hạn mức của thao tác khác. Ví dụ, tạo nhiều hội thoại AI có thể làm bước gửi câu hỏi bị 429 trước hạn mức gửi; ghi nhật ký có thể ảnh hưởng tạo link thanh toán.

[routes/api.php](../../BE/routes/api.php) đã đặt tiền tố theo controller/thao tác cho các limiter dạng số. Giữ nguyên số lượt và cửa sổ thời gian; limiter đăng nhập/chat có tên giữ nguyên. Không dùng ID tài nguyên trong khóa, nên đổi hội thoại không mở thêm hạn mức gửi cho cùng người dùng. Kiểm thử hồi quy chứng minh 7 lần tạo hội thoại không khóa thao tác gửi, 6 lần gửi vẫn chạm giới hạn gửi và yêu cầu thứ 7 trả 429 dù đổi hội thoại.

## Kiểm thử đã chạy

Windows, PHP 8.4, Laravel 13, MariaDB 10.4.32, Vue 3 Options API, Vitest. Database riêng được migrate rồi rollback từng ca và xóa sau bộ kiểm thử. Thời điểm nghiệp vụ cố định trong môi trường kiểm thử; không đổi đồng hồ máy.

| Hành trình tích hợp | Kết quả kiểm tra |
| --- | --- |
| Mua gói PT/AI → webhook/đồng bộ → phân công → giáo án → nhật ký → lịch PT → hoàn thành → báo cáo | Chữ ký sai bị từ chối; callback/retry không cấp gói/trừ buổi lặp; snapshot gói giữ nguyên; tự tập không tiêu hao PT; báo cáo từ khoản thu xác minh |
| KH miễn phí → giáo án tự tạo/BMI → mua gói → giáo án PT → ngừng/áp dụng lại | Không cần gói cho tự tập/BMI; chỉ một giáo án đang dùng; lịch sử giữ; PT đọc được số đo đúng học viên |
| Đổi PT khi có lịch tương lai/đề xuất/chat/nhật ký | Hủy lịch tương lai, vô hiệu đề xuất cũ; PT cũ mất quyền; PT mới chỉ đọc tài nguyên được phép; Admin không đọc chat/nhật ký riêng |
| Gói hết hạn | Nhật ký hợp lệ còn ghi được; buổi PT kết thúc trong hạn được hoàn thành trong cửa sổ 24 giờ; không cấp AI mới |
| Tiền thừa → đối soát → hoàn tiền giả lập | Không kích hoạt gói sai; retry không hoàn lặp; báo cáo sau hoàn bằng 0; không phân công PT |
| Gói AI → provider 429 → nháp 12 buổi → retry → KH áp dụng | Lỗi không trừ quota; chỉ một lần thành công/quota; không tự áp dụng hay tạo lịch; KH chủ động áp dụng và lưu bản cũ |
| Hạn mức theo thao tác | Tạo hội thoại không tiêu hao lượt gửi; đổi ID hội thoại không vượt hạn mức gửi; đọc thông báo không bị chung bộ đếm |
| KH khác/tài khoản khóa | Bị từ chối truy cập; kết quả nhật ký đã hoàn thành giữ nguyên |

- Bộ tích hợp mới: **8 tests / 304 assertions đạt**.
- Toàn Backend: **267 tests / 6.641 assertions đạt**, khoảng 257 giây.
- Toàn Frontend: **253 tests / 27 files đạt**, khoảng 7,28 giây. Phiên này không thay đổi code Frontend.
- Pint cho test/fixture/routes, cú pháp PowerShell launcher và `git diff --check`: đạt. Tài liệu/launcher đọc được với UTF-8 nghiêm ngặt. Launcher từ chối cấu hình trùng cổng FE/BE trước khi tạo database.

## Kiểm tra giao diện

Demo FE5302/BE8022 dùng sáu tài khoản giả và database `kiem_tra_hanh_trinh_demo_<16 ký tự hex>`. Hai khoản thu giả tổng 198.000đ, catalog 1.324 bài, gói 4 buổi PT/10 lượt AI, không gọi dịch vụ ngoài.

- KH đăng nhập, mở buổi hôm nay, bắt đầu, thêm hiệp cho cả bốn bài, nhập 12 lần, lưu nháp và hoàn thành qua xác nhận trong trang.
- PT dashboard thấy học viên đã hoàn thành 1/1 buổi tự tập; mở thông báo nhật ký mới đi đúng trang chi tiết, giảm số chưa đọc và đọc được bốn kết quả.
- PT gửi nhận xét thành công. KH đăng nhập lại nhận thông báo, bấm đi đúng nhật ký và đọc được nội dung; số chưa đọc giảm 4 → 3. Kết quả tập giữ nguyên.
- Dashboard KH có một buổi hoàn thành, tỷ lệ 100%, giáo án 1/1 buổi tuần này, gói vẫn 4/4 lượt PT và 10/10 AI.
- Admin tổng quan thấy tiền nhận/thực thu sau hoàn 198.000đ, hai đơn kích hoạt, một KH chờ PT. Phân công KH `Demo cho-pt` cho `Demo pt2` thành công, danh sách cập nhật đúng PT.
- Không có lỗi/cảnh báo trong console của tab demo khi kiểm tra các bước trên. Không kiểm thử lại responsive/light trong phiên này; tab demo dùng dark theme và kích thước cửa sổ hiện có.

Ảnh dữ liệu giả: [nhận xét PT](hanh-trinh-pt-nhan-xet.png), [dashboard KH](hanh-trinh-kh-dashboard.png), [báo cáo Admin](hanh-trinh-admin-bao-cao.png). Tab kiểm thử riêng đã đóng. Database dùng cho QA được dọn bằng công cụ kiểm tra tên demo sau khi phiên terminal giữ launcher đã kết thúc; không xóa dữ liệu ứng dụng chính.

Đã mở launcher lại với database ngẫu nhiên mới và bấm Enter theo hướng dẫn: thoát mã 0, hai cổng 8022/5302 không còn lắng nghe, truy vấn `information_schema` không còn database hành trình demo. Cơ chế dọn bình thường đạt; nếu đóng cưỡng bức terminal vẫn cần bước dọn thủ công trong hướng dẫn.

## Giới hạn

Đây là kiểm thử tích hợp API và một hành trình giao diện demo; không tuyên bố mọi tình huống đã chạy qua trình duyệt. Ca tranh chấp/reconnect/revocation chuyên biệt vẫn nằm trong các bộ module, xem [M07](M07_CHAT.md), [M08](M08_CHATBOT.md) và [M09 thông báo](M09_THONG_BAO.md).

Chưa nghiệm thu chuyển tiền ngân hàng thật, chất lượng Gemini thật đầy đủ, MySQL 8, production, đo tải hoặc backup/restore trong phiên này. Demo tắt khóa payOS/Gemini/email và không mở Reverb riêng. Bước tiếp theo của lộ trình: hoàn tất bộ đánh giá AI thật, sau đó triển khai và nghiệm thu môi trường/backup.
