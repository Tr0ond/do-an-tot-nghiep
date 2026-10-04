# Dữ liệu mô phỏng 15 ngày

`HeThong15NgaySeeder` tạo thêm một bộ dữ liệu lịch sử liên kết cho **15 ngày tính đến ngày chạy**, theo giờ Việt Nam, lưu timestamps UTC. Ví dụ chạy 04/10/2026 mô phỏng 20/09–04/10/2026. Có lịch dự kiến thêm 3 ngày để tiếp tục thao tác như hệ thống đang hoạt động.

Từ `BE/`, khi migrations và catalog đã có:

```powershell
rtk proxy php artisan db:seed --class=HeThong15NgaySeeder
```

Máy mới: chạy `rtk proxy php artisan migrate`, rồi `rtk proxy php artisan db:seed` để chuẩn bị tài khoản/catalog/giáo án mẫu; sau đó chạy class trên. Seeder 15 ngày là lựa chọn riêng, không tự gọi trong `DatabaseSeeder` và không chạy `migrate:fresh`.

## Nội dung mô phỏng

- 1 Admin, 4 PT, 30 KH có tên tiếng Việt, hồ sơ/mục tiêu/kinh nghiệm và đăng ký rải theo ngày.
- 5 gói: AI 30 ngày, PT 8 buổi, PT 12 buổi, PT 4 buổi và AI 7 ngày. Đơn giữ snapshot giá/quyền lợi và hạn thanh toán 15 phút.
- 29 đơn, 27 khoản thu: 25 gói từng kích hoạt (1 đã hết hạn), 1 đơn hết hạn thanh toán, 1 khoản tiền muộn đã hoàn, 1 khoản tiền muộn chờ đối soát, 1 đơn đang chờ thanh toán. Có KH chưa mua gói.
- 19 phân công, 1 KH có gói PT đang chờ phân công; lịch PT 60 phút, đã hoàn thành/hủy/từ chối/vắng mặt/hết hạn và lịch tương lai đã xác nhận/chờ xác nhận. Có khung giờ tương lai còn trống để đặt lịch.
- 26 giáo án đang áp dụng, mỗi giáo án 3 ngày × 4 bài từ catalog thật, có snapshot media/hướng dẫn. KH không có PT dùng giáo án tự tạo. Lịch tự tập, phiên hoàn thành, từng hiệp và nhận xét PT độc lập lịch PT.
- Số đo chiều cao/cân nặng có biến động nhẹ; hội thoại KH–PT có trạng thái đã đọc/chưa đọc; chatbot có lịch sử thành công/lỗi; 3 FAQ đã xuất bản, thông báo và audit.
- `kh19` đã dùng hết 4 buổi PT nhưng chatbot còn hiệu lực; `kh25` hết gói AI 7 ngày và vẫn giữ giáo án/lịch sử. Hủy/vắng mặt/tự tập/chat không trừ lượt PT.

## Đăng nhập và xem

Mật khẩu ban đầu của cả nhóm: `Demo123456!`.

| Vai trò | Email | Màn hình gợi ý |
| --- | --- | --- |
| Admin | `admin@demo15.example.test` | Tổng quan (lọc 15 ngày), đơn hàng, phân công, lịch hẹn |
| PT | `pt1@demo15.example.test` đến `pt4@demo15.example.test` | Tổng quan, học viên, lịch hẹn, nhật ký, tin nhắn |
| KH | `kh01@demo15.example.test` đến `kh30@demo15.example.test` | Tổng quan, gói của tôi, giáo án, nhật ký, chỉ số, tin nhắn, AI |

Các trường hợp: `kh20` chờ PT, `kh21`–`kh24` dùng AI riêng, `kh26` chưa mua gói, `kh27` hết hạn thanh toán, `kh28` đã hoàn tiền, `kh29` chờ đối soát, `kh30` chờ thanh toán. Trạng thái hiện tại tính động theo thời gian; đơn chờ sẽ hết hạn sau 15 phút, yêu cầu chờ lịch hẹn sau 2 giờ.

## Chạy lại và giới hạn

Chỉ cho `local/testing` và MySQL/MariaDB. Một transaction cho toàn bộ bộ mô phỏng, không tắt FK. Khóa theo database chặn hai tiến trình seed đồng thời. Dấu hoàn tất trong `nhat_ky_he_thong` giúp chạy lại bỏ qua **toàn bộ** bộ dữ liệu: không tạo trùng, đặt lại mật khẩu, mở khóa, cập nhật timestamps hay ghi đè nội dung đã sửa. Ngày chạy lại không kéo lịch sử tiến lên ngày mới. Không xóa riêng dấu hoàn tất để ép seed lại.

Email `@demo15.example.test` đã tồn tại nhưng thiếu dấu hoàn tất sẽ báo lỗi và giữ nguyên dữ liệu. Thiếu bài nguồn hoặc nhóm/bài đã ngừng hoạt động cũng báo lỗi trước khi ghi. Không nâng quyền hoặc sửa các tài khoản có sẵn. Thông báo chỉ ghi cho nhóm demo.

**Mọi khoản thu/hoàn tiền và phản hồi chatbot là dữ liệu giả lập.** Seeder ghi lịch sử trực tiếp, không gọi payOS/Gemini, không gửi email, không broadcast, không có link thanh toán thật. Provider AI là `demo`, token và chi phí không giả làm số liệu provider thật. Báo cáo Admin sẽ cộng cả dữ liệu demo đã nạp; không dùng các con số này làm doanh thu thực tế. Đây là dữ liệu trình diễn, không phải bằng chứng thanh toán/AI/realtime đã chạy thật.

Kiểm tra riêng: `rtk proxy php artisan test --filter=HeThong15NgaySeederTest`. Các ca dùng database MariaDB/MySQL ngẫu nhiên riêng: liên kết/counter/deadline/timestamps, báo cáo tiền, dashboard KH/PT, chạy lại giữ dữ liệu đã sửa, lỗi giữa lúc ghi rollback rồi retry, trùng email, catalog ngừng và chặn production. [Kết quả kiểm chứng](../../../docs/verification/SEEDER_15_NGAY.md).

# Seeder tài khoản demo

`TaiKhoanSeeder` tạo 3 tài khoản hoạt động để thử giao diện và quyền của từng vai trò. `DatabaseSeeder` gọi lần lượt `TaiKhoanSeeder` → `BaiTapSeeder` → `GiaoAnMauSeeder` khi chạy `db:seed`. Seeder tài khoản/giáo án demo chỉ chạy trong môi trường `local` hoặc `testing`.

| Vai trò | Email | Mật khẩu demo |
| --- | --- | --- |
| Admin | admin@example.test | Demo123456! |
| Huấn luyện viên | pt@example.test | Demo123456! |
| Khách hàng | khachhang@example.test | Demo123456! |

Từ thư mục `BE/`, sau khi cấu hình kết nối database và chạy migrations:

```powershell
rtk proxy php artisan migrate
rtk proxy php artisan db:seed
```

Hoặc chỉ chạy seeder tài khoản:

```powershell
rtk proxy php artisan db:seed --class=TaiKhoanSeeder
```

Đăng nhập tại [localhost:5173/dang-nhap](http://localhost:5173/dang-nhap). Tài khoản KH có `ho_so_khach_hang`, PT có `ho_so_huan_luyen_vien`; Admin không có hai hồ sơ này. Mật khẩu được mã hóa qua model `TaiKhoan`, tài khoản/hồ sơ mới được tạo qua `TaiKhoanService`. Seeder chưa tạo phân công PT, gói tập hoặc lịch hẹn.

Chạy lại giữ nguyên ID, tên, mật khẩu, vai trò, trạng thái và nội dung hồ sơ đã có; chỉ bổ sung hồ sơ KH/PT còn thiếu. Trùng email nhưng khác vai trò sẽ báo lỗi và rollback toàn bộ lần seed; không tự nâng quyền, mở khóa hay đặt lại mật khẩu. Không xóa bảng, không tắt khóa ngoại. UNIQUE email/hồ sơ ở database tiếp tục chặn trùng; không chạy song song nhiều lệnh seed.

Tài khoản demo phục vụ phát triển local. Khi triển khai thật, tạo Admin bằng lệnh `tai-khoan:tao-admin` với mật khẩu riêng theo [hướng dẫn tài khoản](../../../docs/features/TAI_KHOAN.md).

Kiểm tra bước seeder tài khoản ngày 01/10/2026: `php artisan test` đạt **28 tests/246 assertions** trên PHP 8.4.0/Laravel 13.34.0/MariaDB 10.4.32, trong database kiểm thử ngẫu nhiên riêng. Có 5 ca mới cho seed ba vai trò/đăng nhập, chạy lại giữ dữ liệu, bổ sung hồ sơ thiếu, rollback khi trùng email khác vai trò và từ chối production. Đã chạy `php artisan db:seed` thành công trong database ứng dụng local, xác minh ba tài khoản hoạt động, hồ sơ và mật khẩu demo. Chưa kiểm thử chạy seed đồng thời trên MySQL thật.

## Seeder bài tập

`BaiTapSeeder` nhập 19 nhóm cơ/1.324 bài từ JSON, kiểm tra media trước ghi, transaction và giữ dữ liệu đã có theo mã nguồn. Chạy riêng từ BE: `rtk proxy php artisan db:seed --class=BaiTapSeeder`; không phụ thuộc tài khoản demo hoặc môi trường local. Đã nhập catalog local; toàn bộ Backend sau module bài tập đạt **36 tests/3.040 assertions**. Xem [dữ liệu](../data/README.md), [hợp đồng](../../../docs/features/BAI_TAP.md), [kiểm chứng](../../../docs/verification/M02_BAI_TAP.md).

## Seeder giáo án mẫu

`GiaoAnMauSeeder` tạo 5 giáo án đã duyệt, mỗi ngày 4 bài; tổng 60 dòng bài tập có hiệp/lần lặp/nghỉ/ghi chú:

| Giáo án demo | Ngày tập | Dòng bài |
| --- | --- | --- |
| Toàn thân cơ bản | 3 | 12 |
| Thân trên - thân dưới | 4 | 16 |
| Đẩy - kéo - chân | 3 | 12 |
| Tập tại nhà không tạ | 3 | 12 |
| Cơ bụng và thể lực | 2 | 8 |

Nếu đã có tài khoản demo và catalog bài tập, từ `BE/` chạy riêng:

```powershell
rtk proxy php artisan migrate
rtk proxy php artisan db:seed --class=GiaoAnMauSeeder
```

Máy mới dùng `rtk proxy php artisan db:seed` sau migrations để nạp cả ba seeder theo đúng thứ tự. Nếu chỉ muốn chuẩn bị các phụ thuộc còn thiếu, chạy `--class=TaiKhoanSeeder` và `--class=BaiTapSeeder` trước giáo án. Không chạy `migrate:fresh` trên database có dữ liệu.

Seeder chỉ chạy trong local/testing, cần `admin@example.test` đúng vai trò Admin và đang hoạt động; không tạo/nâng quyền/mở khóa/đổi mật khẩu tài khoản. Giáo án mới được tạo nháp rồi duyệt qua Service, không bỏ qua quy tắc ngày/thứ tự hoặc bài/nhóm hoạt động. Thiếu bài nguồn báo mã bài; bài/nhóm ngừng báo lỗi validation. Mọi giáo án mới trong lần chạy nằm cùng transaction và rollback khi có lỗi. Các seeder phụ thuộc có transaction riêng; lỗi giáo án không xóa tài khoản hoặc bài tập đã nhập thành công.

UUID cố định giúp chạy lại bỏ qua toàn bộ giáo án đã có, giữ ID, tên, nội dung bài, trạng thái/metadata duyệt và timestamps kể cả sau khi sửa/ngừng. Tra bài bằng nguồn + mã, không phụ thuộc ID JSON. Không seed kế hoạch, lịch tập hay phân công cho khách hàng; các thông số chỉ phục vụ demo. Không chạy nhiều lệnh seed đồng thời.

Sau khi chạy, đăng nhập Admin vào `/admin/giao-an-mau` để quản lý; PT vào `/pt/giao-an-mau` để xem các bản đã duyệt. [Hợp đồng](../../../docs/features/GIAO_AN_MAU.md), [kiểm chứng seeder](../../../docs/verification/M02_GIAO_AN_MAU_SEEDER.md).
