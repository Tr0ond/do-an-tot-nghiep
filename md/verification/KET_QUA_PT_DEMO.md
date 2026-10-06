# Dữ liệu demo để chủ dự án thử kết quả buổi PT

Đã tạo ngày 06/10/2026 trên database local của web hiện tại, giữ lại cho chủ dự án thử. Tách tài khoản KH/PT demo khỏi tài khoản người dùng; không reset database, không gọi cổng thanh toán, không tạo giao dịch tiền thật. Dùng catalog/gói hiện có, đơn demo có tên snapshot bắt đầu `[DEMO]`; các kết quả mẫu đều ghi rõ dữ liệu giả.

## Đăng nhập

Mở <http://localhost:5173/dang-nhap>. Mật khẩu chung: `Demo123456!`.

| Vai trò | Email | Tên hiển thị |
| --- | --- | --- |
| PT | `pt.ketqua@demo.test` | [DEMO] PT ghi kết quả |
| KH | `kh.ketqua@demo.test` | [DEMO] Khách hàng kết quả PT |

Muốn thử hai vai trò cùng lúc, dùng hai trình duyệt hoặc một cửa sổ thường và một cửa sổ riêng tư để tách cookie đăng nhập.

## Bốn buổi mẫu

| Buổi | Trạng thái khi tạo | Cách thử với PT |
| --- | --- | --- |
| [#96](http://localhost:5173/pt/lich-hen/96/ket-qua) | Đã kết thúc, đã xác nhận, chưa có kết quả | Chọn bài → thêm hiệp → nhập số lần/tạ/nghỉ → lưu nháp → chốt |
| [#97](http://localhost:5173/pt/lich-hen/97/ket-qua) | Bản nháp 2 bài, mỗi bài 2 hiệp | Sửa số liệu, thêm/bỏ hiệp, lưu rồi chốt |
| [#98](http://localhost:5173/pt/lich-hen/98/ket-qua) | Đã chốt 3 bài, lịch hoàn thành | Kiểm tra chỉ đọc và kết quả không sửa được |
| [#99](http://localhost:5173/pt/lich-hen/99/ket-qua) | Lịch tương lai, đã xác nhận | Xem được lịch, chưa được ghi trước giờ bắt đầu |

KH mở **Lịch hẹn → Chi tiết → Xem kết quả buổi tập**; các URL trên đổi `/pt/` thành `/khach-hang/` khi đăng nhập KH. Ví dụ [KH xem nháp #97](http://localhost:5173/khach-hang/lich-hen/97/ket-qua).

Gói được cấp **8 lượt thử**, còn **7 lượt** sau buổi mẫu #98 hoàn thành. Lưu/chốt #96 hoặc #97 giữ nguyên lượt; PT quay về chi tiết lịch và **Xác nhận hoàn thành** mới trừ một lượt. Chốt kết quả không thay cho xác nhận lịch.

Thời hạn ghi/chốt giữ đúng quy tắc hệ thống: trước kết thúc+24 giờ. #96 hạn **07/10/2026 03:42**, #97 hạn **07/10/2026 01:12**, giờ Việt Nam. Qua hạn hai buổi này sẽ khóa ghi; không gia hạn tự động để tránh ghi đè dữ liệu chủ dự án đã thử.

## Kiểm chứng và tạo lại ở database mới

Seeder riêng `BE/database/seeders/KetQuaBuoiPtDemoSeeder.php`, chỉ chạy local, không gắn vào seeder mặc định. Toàn bộ tạo dữ liệu trong transaction, có khóa chống chạy trùng. Chạy lại giữ bộ dữ liệu hiện có, không đặt lại mật khẩu/nháp/chốt/giờ; email không đúng bộ demo sẽ báo lỗi và không ghi đè.

Chạy từ `BE/`:

```powershell
rtk proxy php artisan db:seed --class=KetQuaBuoiPtDemoSeeder
```

Cần có Admin đang hoạt động, gói PT đang hiển thị với ít nhất8 buổi và ít nhất3 bài catalog. ID mới ở database khác có thể khác bảng trên, seeder in ID/quyền/thời hạn thực tế sau khi chạy.

Đã chạy seeder hai lần: lần đầu tạo, lần sau không sinh thêm record hoặc thay dữ liệu; cùng ID96–99/còn7 lượt. Đã đăng nhập thật hai tài khoản trên `localhost:5173`, mở4 buổi PT kiểm tra quyền ghi và số bài, KH xem nháp97 không có input sửa, không phát sinh `pageerror`. Kiểm tra chỉ đọc, không sửa nháp/chốt dành cho chủ dự án thử. PHP Pint đạt; không chạy lại toàn bộ ứng dụng vì chỉ thêm dữ liệu và seeder local.
