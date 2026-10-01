# Seeder tài khoản demo

`TaiKhoanSeeder` tạo 3 tài khoản hoạt động để thử giao diện và quyền của từng vai trò. `DatabaseSeeder` gọi seeder tài khoản rồi `BaiTapSeeder` khi chạy `db:seed`. Seeder tài khoản chỉ chạy trong môi trường `local` hoặc `testing`.

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
