# M02 — Kiểm chứng giáo án mẫu

Ngày 01/10/2026, Windows, PHP 8.4.0/Laravel 13.34.0/Sanctum 4.3.3, MariaDB 10.4.32, Node 22.20.0/Vue 3.5.43/Vite 8.3.1/Vitest 5.0.3. [Hợp đồng module](../features/GIAO_AN_MAU.md).

## Phần đã triển khai

- Backend: model T12/T13, FormRequests ghi/list, Resource, Service transaction, Controller Admin/PT, middleware auth/role/tài khoản hoạt động; migration 000030 thêm UUID nullable/unique T12. Tổng runtime 33 migrations; giữ SQL/Draw.io/28 migrations tạo bảng gốc.
- Frontend: 3 URL Admin (danh sách, thêm, sửa) và 2 URL PT (thư viện, chi tiết), service chung và components list/chọn bài. Soạn theo ngày, hiệp/lặp/nghỉ/ghi chú, reorder bằng nút, tìm bài theo trang; draft/duyệt/ngừng, retry/409/422 và phản hồi đến muộn.
- Sửa nội dung bản duyệt về nháp và thu hồi thông tin duyệt; không đổi plan/snapshot T14/T15. Không DELETE danh mục hoặc tự áp dụng kế hoạch.

## Kiểm tra đã chạy

| Kiểm tra | Kết quả |
| --- | --- |
| `php artisan test` toàn Backend | **67 tests / 3.586 assertions PASS** |
| Riêng `GiaoAnMauTest` | 11 ca mới / 199 assertions, database MariaDB ngẫu nhiên riêng, migrate/transaction/cleanup |
| `npm run test` toàn FE | **49 tests PASS**, gồm 10 ca giáo án |
| `npm run build` | PASS, 142 modules |
| `npm run lint:check`, `npm run format:check` | PASS |
| Pint các PHP đã thêm/sửa | PASS |
| `node scripts/kiemTraMigrations.mjs` | PASS cho 28 migrations gốc: 303 cột/52 FK RESTRICT |
| `php artisan migrate --force` local | 000030 chạy thành công, giữ dữ liệu cũ |

Các ca mới kiểm tra quyền cả hai namespace, Admin bị khóa, CSRF thật cho POST/PUT/PATCH; UUID retry và unique tại DB; field thừa/ngày/thứ tự/chỉ số sai; bài/nhóm ngừng và không nhân thêm bài ngừng qua ID cũ; thiếu ngày khi duyệt; dữ liệu cũ không hợp lệ; noop giữ duyệt; 409 không ghi đè; reorder không vi phạm unique; lỗi dòng thứ hai rollback cả parent/child; sửa/ngừng không đổi snapshot kế hoạch. Khi đổi actor trong test xóa guard cache để thực sự kiểm tra actor mới.

FE kiểm tra reorder không lẫn ngày/không sửa đầu vào, payload không lẫn thông tin duyệt/hiển thị, giảm ngày không âm thầm mất bài, chống gửi trùng, UUID giữ khi lỗi mạng, 409 giữ form, 422 khi duyệt, nhận phiên bản mới, bỏ phản hồi sau đổi route, hủy request list/chọn bài cũ và PT nhận 404/session hết hạn.

## Kiểm tra trình duyệt thực tế

Backend `localhost:8000`, FE `localhost:5173`, tài khoản demo Admin/PT có sẵn:

1. Admin tạo nháp 2 ngày, chọn bài từ catalog, đổi `air bike` lên đầu ngày 1, đặt 4 hiệp/12 lần/60 giây và ghi chú; ngày 2 chọn bài khác. Lưu nháp và duyệt thành công qua session/CSRF.
2. Tìm `push up` trong picker không gửi lưu giáo án; thêm bài và lưu bản đã duyệt làm trạng thái về nháp. Duyệt lại được, tổng 4 bài.
3. PT thấy đúng 1 giáo án duyệt, detail đúng ngày/thứ tự/khối lượng/ghi chú, có link hướng dẫn; không có nút sửa/áp dụng. PT mở URL Admin bị chuyển sang trang không có quyền.
4. Admin ngừng sử dụng, dữ liệu bài còn nguyên; PT mở detail cũ nhận thông báo giáo án không tồn tại/không còn được duyệt (404).
5. Kiểm tra form Admin và detail PT ở 1440×900, 768×1024, 390×844. Không tràn ngang; nút form đo được tối thiểu 44px. Console không warn/error ở luồng thành công; request 404 mong đợi của ca ngừng sử dụng không phải lỗi render.
6. Sau QA chỉ dọn giáo án `QA giáo án 2 ngày 20261001` ID 1 và 4 dòng do phiên kiểm tra này tạo, sau khi đối chiếu creator demo/trạng thái ngừng/UUID/no plan reference. Catalog 1.324 bài và tài khoản giữ nguyên; không seed lại hoặc đặt lại auto-increment. Trang danh sách/new form được mở để chủ dự án tạo giáo án thực tế.

Ảnh: [Admin desktop](m02-giao-an-admin-desktop.jpg), [tablet](m02-giao-an-admin-tablet.jpg), [mobile](m02-giao-an-admin-mobile.jpg); [PT desktop](m02-giao-an-pt-desktop.jpg), [tablet](m02-giao-an-pt-tablet.jpg), [mobile](m02-giao-an-pt-mobile.jpg); [ngừng sử dụng](m02-giao-an-ngung.jpg), [PT 404](m02-giao-an-pt-404.jpg).

## Giới hạn và cách xem

Chưa chạy trên MySQL thật, chưa đo race bằng nhiều process; rollback được kiểm tra bằng lỗi thật giữa các lần ghi trong database MariaDB, không dùng SQLite. Browser QA là thao tác thực tế thủ công qua công cụ, chưa có bộ E2E tự động. Không thêm provider, payment, tạo/áp dụng/duyệt kế hoạch khách hàng M05 hoặc seeder giáo án.

Máy khác sau khi cập nhật code: từ `BE/` chạy `php artisan migrate`, không `migrate:fresh`/seed đè. Chạy FE/BE theo README; Admin vào `/admin/giao-an-mau`, PT vào `/pt/giao-an-mau`. PT chỉ thấy giáo án sau khi Admin lưu đủ ngày và duyệt.

Bổ sung sau lần kiểm chứng giao diện này: đã thêm seeder giáo án demo, xem [kiểm chứng riêng](M02_GIAO_AN_MAU_SEEDER.md). Phạm vi và kết quả trong báo cáo bên trên ghi nhận thời điểm trước khi thêm seeder.

[Biểu mẫu tạo giáo án sẵn để nhập](m02-giao-an-them.jpg) được để mở sau QA, đã trả viewport về mặc định. Tài liệu cập nhật được kiểm tra UTF-8 và 137 liên kết nội bộ trước khi thêm liên kết ảnh bàn giao này; ảnh đã được đọc và xác nhận tồn tại.
