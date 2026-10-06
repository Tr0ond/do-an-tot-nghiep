# Kiểm chứng dữ liệu mô phỏng 15 ngày

Ngày thực hiện: **04/10/2026**. Môi trường: Windows, PHP 8.4.0, MariaDB 10.4.32, kết nối Laravel `mysql`.

## Thay đổi

- [HeThong15NgaySeeder](../../BE/database/seeders/HeThong15NgaySeeder.php): một bộ dữ liệu lịch sử có liên kết cho 15 ngày gần nhất theo giờ Việt Nam, DB UTC; lịch tương lai thêm 3 ngày. Chỉ local/testing, khóa tiến trình theo database, một transaction, dấu hoàn tất để giữ nguyên khi chạy lại.
- [Kiểm thử](../../BE/tests/Feature/HeThong15NgaySeederTest.php): database MariaDB ngẫu nhiên riêng, migrate schema thật, transaction cho mỗi ca và chỉ xóa database kiểm thử vừa tạo.
- [Hướng dẫn seeder](../backend/SEEDERS.md): lệnh chạy, tài khoản, kịch bản và giới hạn. Không thay đổi `DatabaseSeeder`, schema, API hoặc Frontend.

## Kiểm tra đã chạy

Từ `BE/`:

```powershell
rtk proxy php artisan test --filter=HeThong15NgaySeederTest
rtk proxy php vendor/bin/pint --test database/seeders/HeThong15NgaySeeder.php tests/Feature/HeThong15NgaySeederTest.php
```

Kết quả cuối: **8 tests / 485 assertions đạt**, khoảng 60 giây; Pint đạt cho hai file PHP mới. Không chạy lại toàn bộ bộ test ứng dụng hoặc build FE vì không đổi runtime/giao diện và seeder mới không được gọi mặc định.

Các ca xác minh:

1. Dữ liệu đúng liên kết, timestamps không ở tương lai và đúng thứ tự; chạy ở 00:01, 12:00 và 23:59 giờ Việt Nam. Lịch PT không chồng, đặt trước ít nhất 4 giờ, hủy trước ít nhất 2 giờ, hoàn thành trong hạn xác nhận, nằm trong hạn gói.
2. Số buổi còn lại = snapshot ban đầu − số lịch PT hoàn thành; hủy/vắng mặt/tự tập/chat không trừ buổi. Gói hết buổi PT vẫn đang dùng và còn quyền AI. Thời hạn đủ số ngày ×24 giờ.
3. Báo cáo 15 ngày khớp khoản thu/hoàn tiền/đối soát và biểu đồ. Dashboard KH đọc được giáo án 12 bài, tiến độ, gói và PT; dashboard PT đọc được học viên.
4. AI chỉ có dữ liệu `provider=demo`, không phát sinh sau hạn gói; yêu cầu lỗi không có câu trả lời thành công. HTTP không gửi request ngoài.
5. Chạy lại sau một ngày giữ nguyên dữ liệu, tên/mật khẩu/tài khoản bị khóa và thời điểm đã đọc thông báo; giữ tài khoản ngoài demo.
6. Gây lỗi khi ghi hiệp tập: rollback toàn bộ bộ mô phỏng, bỏ dấu hoàn tất; chạy lại thành công, chứng minh khóa đã được giải phóng.
7. Trùng email demo nhưng không có dấu hoàn tất, catalog ngừng hoặc môi trường production đều bị chặn, không ghi dữ liệu mô phỏng.

## Đã nạp vào local

Đã kiểm tra migrations đều chạy, rồi chạy `rtk proxy php artisan db:seed --class=HeThong15NgaySeeder` trong database local `duantotnghiep`, **giữ dữ liệu sẵn có**. Dấu hoàn tất ghi khoảng **20/09–04/10/2026**. Chạy class lần hai báo giữ nguyên, không nhân đôi bộ mô phỏng.

Số lượng trong nhóm `@demo15.example.test` tại lúc kiểm tra:

| Dữ liệu | Số lượng |
| --- | ---: |
| Tài khoản (1 Admin, 4 PT, 30 KH) | 35 |
| Đơn / khoản thu | 29 / 27 |
| Lịch PT / buổi PT hoàn thành | 88 / 50 |
| Giáo án / lịch tự tập | 26 / 146 |
| Phiên tập hoàn thành | 107 |
| Số đo cơ thể | 73 |
| Tin nhắn KH–PT | 154 |
| Yêu cầu chatbot mô phỏng | 90 |
| Thông báo | 135 |

Các con số có thể thay đổi khi người dùng thao tác hoặc scheduler xử lý deadline. Thời điểm chạy trong ngày ảnh hưởng số buổi hoàn thành và dữ liệu phát sinh hôm nay.

## Cách xem và giới hạn

Mở ứng dụng local sau khi chạy `start.bat`; đăng nhập `admin@demo15.example.test`, `pt1@demo15.example.test` hoặc `kh01@demo15.example.test`, mật khẩu ban đầu `Demo123456!`. Admin lọc Tổng quan từ 20/09 đến 04/10/2026; PT xem học viên/lịch/tin nhắn; KH xem giáo án/nhật ký/chỉ số/gói và AI. Xem thêm các kịch bản khác trong [hướng dẫn](../backend/SEEDERS.md).

Khoản thu và hoàn tiền đều giả lập, không có giao dịch/link payOS thật. Phản hồi AI được ghi rõ mô phỏng, không gọi Gemini, token = 0. Seeder ghi lịch sử trực tiếp, không phát WebSocket hoặc gửi email; không xác nhận thanh toán thật, chất lượng AI hoặc realtime qua những ca này. Dashboard Admin tính cả dữ liệu demo đã nạp. Chưa chạy trên MySQL 8 hoặc kiểm tra giao diện bằng trình duyệt trong task này; không có quyết định nghiệp vụ mới chờ chốt.
