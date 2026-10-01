# M02 — Kiểm chứng seeder giáo án mẫu

Ngày 01/10/2026, Windows, PHP 8.4.0/Laravel 13.34.0, MariaDB 10.4.32. [Hợp đồng](../features/GIAO_AN_MAU.md), [hướng dẫn chạy](../../BE/database/seeders/README.md).

## Thay đổi

- Thêm `BE/database/seeders/GiaoAnMauSeeder.php`: 5 giáo án/60 dòng bài, tra nguồn + mã bài thay vì ID JSON, tạo nháp rồi duyệt qua Service bằng Admin demo đang hoạt động.
- `DatabaseSeeder` gọi tài khoản → bài tập → giáo án. Không thêm migration, dependency hoặc giao diện; Admin/PT dùng các trang giáo án hiện có.
- Chỉ local/testing. UUID cố định, bỏ qua toàn bộ giáo án đã có, giữ mọi bản chỉnh sửa/ngừng. Toàn bộ giáo án mới trong cùng transaction; không tạo kế hoạch/lịch tập hoặc sửa tài khoản.

## Kiểm tra đã chạy

| Kiểm tra | Kết quả |
| --- | --- |
| `php artisan test --filter=GiaoAnMauTest` | **17 tests / 415 assertions PASS** |
| `php artisan test` toàn Backend | **73 tests / 3.802 assertions PASS** |
| Pint seeder mới, DatabaseSeeder, GiaoAnMauTest | PASS |

Các test chạy trong database MariaDB ngẫu nhiên riêng do `GiaoAnMauTest` tạo/migrate và dọn; không refresh database ứng dụng. Sáu ca mới kiểm tra:

1. `DatabaseSeeder` nạp đúng phụ thuộc; 5 giáo án đã duyệt/60 dòng, mỗi ngày 4 bài liên tục, người tạo/duyệt đúng, PT list/detail đọc được và không sinh kế hoạch. Bài kiểm thử có sẵn làm lệch ID catalog; FK vẫn trỏ đúng bài nguồn.
2. Seed lại hai lần giữ nguyên toàn bộ parent/child, timestamps và bản tự soạn; giáo án demo đã đổi tên/ghi chú/hiệp/ngừng vẫn giữ nguyên dù bài nguồn sau đó ngừng.
3. Thiếu bài `1160` ở giáo án cuối rollback cả các giáo án mới đã tạo trước đó, giữ giáo án có sẵn.
4. Bài ngừng hoặc nhóm ngừng bị từ chối, không để lại parent/child mới.
5. Admin demo thiếu/sai vai trò/bị khóa bị từ chối; không tự tạo tài khoản, nâng quyền, mở khóa hoặc đổi mật khẩu.
6. Production bị từ chối trước khi ghi.

## Nạp database local thực tế

Trước khi nạp: môi trường `local`, 0 giáo án/0 dòng, 1.324 bài catalog, Admin demo đang hoạt động. Đã chạy từ `BE/`:

```powershell
rtk proxy php artisan db:seed --class=GiaoAnMauSeeder
```

Lần đầu báo tạo 5/giữ 0. Lần hai báo tạo 0/giữ 5. Truy vấn database sau hai lần xác nhận 5 giáo án đều `DA_DUYET`, 60 dòng bài, catalog vẫn 1.324 bài và không có kế hoạch khách hàng. ID giáo án local 2–6; không đặt lại auto-increment hoặc xóa dữ liệu cũ.

| Giáo án | Ngày | Dòng bài |
| --- | --- | --- |
| Toàn thân cơ bản (demo) | 3 | 12 |
| Thân trên - thân dưới 4 ngày (demo) | 4 | 16 |
| Đẩy - kéo - chân 3 ngày (demo) | 3 | 12 |
| Tập tại nhà không tạ (demo) | 3 | 12 |
| Cơ bụng và thể lực 2 ngày (demo) | 2 | 8 |

## Cách xem và giới hạn

Admin vào `/admin/giao-an-mau`, PT vào `/pt/giao-an-mau` sau khi đăng nhập. Máy mới chạy migrations rồi `db:seed` để nạp đủ phụ thuộc; máy đã có catalog/tài khoản có thể chọn class riêng. Seeder không mở khóa Admin demo đã bị khóa và không ghi đè giáo án đã sửa.

Các thông số là dữ liệu trình diễn, chưa gán cho khách hàng. Chưa kiểm thử chạy seed đồng thời hoặc trên MySQL thật. Không chạy lại kiểm tra/build Frontend vì không thay đổi code giao diện; PT list/detail đã được kiểm tra qua feature test. Các seeder phụ thuộc có transaction riêng, nên lỗi giáo án không rollback tài khoản/catalog đã nạp trước đó.
