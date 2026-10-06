# M02 — Kiểm chứng quản lý nhóm cơ

Ngày 01/10/2026, Windows, PHP 8.4.0/Laravel 13.34.0/Sanctum 4.3.3, MariaDB 10.4.32; Node 22.20.0/Vue 3.5.43/Vite 8.3.1/Vitest 5.0.3. [Hợp đồng](../features/NHOM_CO.md).

## Thay đổi

- BE: Controller, FormRequests list/ghi, Resource đếm bài, Service tạo/sửa/trạng thái, 5 routes Admin. NhomCo lưu timestamp micro giây đúng cột DATETIME(6) đã có. Không thêm migration hoặc sửa schema/SQL/Draw.io.
- FE: danh sách `/admin/nhom-co`, thêm `/admin/nhom-co/them`, sửa `/:id/sua`, service Axios chung và mục menu Admin. Tìm/lọc/phân trang theo URL, liên kết bài theo nhóm, xác nhận ngừng/khôi phục bằng dialog native, xử lý phiên hết hạn/409/422/mạng/phản hồi muộn.
- Giữ mã/tên nguồn khi sửa, không có DELETE. Ngừng nhóm ẩn bài công khai, không đổi trạng thái riêng của bài; khôi phục chỉ hiện lại bài còn hoạt động. Giáo án/plan/snapshot giữ nguyên.

## Kiểm tra đã chạy

| Kiểm tra | Kết quả |
| --- | --- |
| Riêng `NhomCoTest` | **8 tests / 169 assertions PASS** |
| `php artisan test` toàn Backend | **81 tests / 3.971 assertions PASS** |
| `npm run test` toàn Frontend | **57 tests PASS**, gồm 8 ca nhóm cơ |
| `npm run build` | PASS, 147 modules |
| `npm run lint:check`, `npm run format:check` | PASS |
| Pint các PHP thêm/sửa | PASS |

Test BE tự tạo database MariaDB ngẫu nhiên riêng, xác minh tên database trước migrate, transaction từng ca và chỉ DROP database vừa tạo. Kiểm tra đủ 5 endpoint với khách chưa đăng nhập/KH/PT/Admin bị khóa; mã chuẩn hóa/trùng/UNIQUE trực tiếp tại DB; field thừa/bất biến/validation; noop và phiên bản dưới đồng hồ đóng băng, 409 không ghi đè, legacy NULL; tìm literal `%`, `_`, `=`, `0`, filter/pagination/count; 404, không DELETE; CSRF thật; lỗi sau ghi rollback.

Ca tích hợp ngừng/khôi phục dùng dữ liệu thật trong DB kiểm thử: list/detail/bộ lọc công khai ẩn/khôi phục đúng; Admin vẫn đọc bài ngừng, không thêm bài vào nhóm ngừng hoặc tạo/duyệt giáo án chứa bài đó; PT thấy `kha_dung: false` trên giáo án cũ; toàn bộ bảng bài, giáo án, dòng giáo án, plan và snapshot so sánh nguyên trạng trước/sau.

FE kiểm tra payload tạo/sửa không lẫn metadata, copy form, submit trùng, giữ dữ liệu khi mất mạng/422/419, 409 khóa ghi và tải phiên bản mới, phiên bản NULL, bỏ response sau đổi nhóm/unmount, hủy list cũ, dialog không cho đóng bằng Escape lúc đang gửi, trạng thái lỗi và thông báo tạo thành công qua chuyển route.

## Trình duyệt thực tế

FE `localhost:5173`, BE `localhost:8000`, session Admin demo:

1. Tạo nhóm QA `qa_nhom_co_20261001`, ID 20, không có bài. Chuyển sang trang sửa, đổi tên; mã và tên nguồn vẫn giữ nguyên, có thông báo lưu thành công.
2. Tìm theo mã, ngừng rồi khôi phục qua dialog; danh sách phản ánh trạng thái và số bài. Link số bài mở đúng Admin bài tập với `nhom_co_id=20`.
3. Tạo lại cùng mã nhận 422, lỗi cạnh trường và nội dung vẫn còn. Thử tìm không có kết quả và đặt lại; phân trang chuyển đúng trang 2/2.
4. Kiểm tra danh sách/form tại 1440×900, 768×1024 và 390×844, dialog ở mobile. Đo không có tràn ngang; nút trong list/form tối thiểu 45,2px. Không có warn/error sau reload ở luồng thành công; request 422 do mã trùng là lỗi validation mong đợi.
5. Dọn đúng nhóm QA ID 20 sau khi đối chiếu mã/tên/tên nguồn/trạng thái và không có bài tham chiếu. Không xóa dữ liệu thật, không tắt FK hoặc đặt lại auto-increment. Database local còn 19 nhóm/1.324 bài/5 giáo án/60 dòng giáo án; không seed lại catalog.

Ảnh: [danh sách desktop](../../docs/verification/m02-nhom-co-desktop.png), [tablet](../../docs/verification/m02-nhom-co-tablet.png), [mobile](../../docs/verification/m02-nhom-co-mobile.png); [form tablet](../../docs/verification/m02-nhom-co-form-tablet.png), [form mobile](../../docs/verification/m02-nhom-co-form-mobile.png), [dialog mobile](../../docs/verification/m02-nhom-co-dialog-mobile.png), [ngừng nhóm QA](../../docs/verification/m02-nhom-co-ngung.png), [mã trùng](../../docs/verification/m02-nhom-co-trung-ma.png).

## Cách xem và giới hạn

Đăng nhập Admin rồi mở `/admin/nhom-co`; FE/BE chạy theo README. Không cần migration mới hoặc seed lại. KH/PT không có menu/quyền quản trị nhóm cơ.

Chưa kiểm thử chạy nhiều process đồng thời hoặc MySQL thật; UNIQUE/lock/rollback đã kiểm tra trên MariaDB. Browser QA là thao tác thực tế qua công cụ, chưa có E2E tự động. Không triển khai upload media, M01 sửa hồ sơ/quên/đặt lại mật khẩu/khóa qua UI hoặc mua/payOS M03 trong bước này.
