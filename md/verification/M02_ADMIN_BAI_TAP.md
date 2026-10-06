# Kiểm chứng quản trị bài tập — 01/10/2026

Đã có Admin thêm/sửa bài tập và đặt trạng thái hiển thị. [Hợp đồng](../features/BAI_TAP.md). Dùng schema hiện có, không thêm migration, không seed lại catalog.

## Môi trường và kiểm thử

Windows, PHP 8.4.0, Laravel 13.34.0, MariaDB 10.4.32 qua driver mysql; Vue 3 Options API/JavaScript, Node 22.20.0, Vite 8.3.1, Vitest 5.0.3. PHPUnit dùng database ngẫu nhiên riêng do BaiTapTest/XacThucTest tạo, migrate và transaction, chỉ DROP database kiểm thử sở hữu.

| Kiểm tra thực sự chạy | Kết quả |
| --- | --- |
| php artisan test | 45 tests / 3.211 assertions PASS; thêm 9 ca Admin trong BaiTapTest |
| npm test | 30 tests PASS; thêm 8 ca Admin |
| npm run build | PASS, 121 modules |
| npm run lint:check, format:check | PASS |
| Pint các file PHP thay đổi | PASS |
| php scripts/kiemTraCatalogDatabase.php sau dọn QA | PASS: đủ 1.324 bài theo nguồn, 19 nhóm, JSON/thời điểm nguồn và 2.648 media |

Backend kiểm tra tất cả endpoint quản trị với session thiếu (401), KH/PT (403), Admin bị khóa (403); CSRF thiếu/sai (419), đúng token tạo thành công. Thêm bài giữ nguồn admin, mã chuẩn hóa/unique, không có ảnh hay ghi công giả; UNIQUE database chuyển lỗi trùng sang 422 ngay cả khi gọi service không qua FormRequest. Đây là kiểm thử trùng tuần tự, chưa phải kiểm thử hai process đồng thời.

Sửa chỉ ghi trường cho phép; giữ tên gốc/media/mã/nguồn/co_phu và các ngôn ngữ khác, xóa vi quay về tiếng Anh. Kiểm tra giới hạn/kiểu dữ liệu, trường ngoài hợp đồng, nhóm ngừng dùng, tên bài do Admin tạo, tìm kiếm literal `%`/`_`, lọc/phân trang, 404. Sửa/trạng thái bản cũ trả 409; đặt trạng thái không đổi là no-op. Ngừng/khôi phục hiển thị giữ tham chiếu giáo án trong database thật; không có DELETE (405).

Frontend tests chạy Vitest Node: URL, bản sao form, payload whitelist, hủy/bỏ kết quả danh sách cũ, giữ form khi 422/409, khóa gửi trùng, cập nhật phiên bản sau lưu và bỏ kết quả lưu khi đã tải biểu mẫu khác. Đây là unit tests gọi Options API methods/service giả lập, không phải E2E tự động.

## Kiểm tra trình duyệt thật

Đăng nhập Admin demo qua Sanctum ở localhost:5173/8000. Kiểm tra danh sách 1.324 bài, ảnh hiện có, bộ lọc, trạng thái rỗng/từ khóa ở URL, biểu mẫu và lỗi cạnh trường khi gửi thiếu. Kiểm tra bố cục desktop 1440×900, tablet 768×1024, mobile 390×844; viewport tạm được reset sau kiểm tra. Thanh điều hướng dùng một dòng ở màn hình nhỏ, form chuyển một cột.

Tạo duy nhất bài QA nguồn admin/mã ZQ01 trong trạng thái ngừng hiển thị, thêm hai bước text, lưu tên Việt qua form thành công. Bật hiển thị qua danh sách: API công khai trả 200, vi/2 bước, media và ghi công null. Ngừng hiển thị: API công khai trả 404, Admin vẫn thấy bài. Sau kiểm tra, script tạm chỉ dọn ID 1325/nguồn admin/mã ZQ01 vừa tạo khi tên/trạng thái còn khớp và không có tham chiếu; script đã được bỏ. Không chỉnh sửa các bài dataset hoặc tài khoản. Đối chiếu catalog sau dọn PASS; không reset AUTO_INCREMENT.

Ảnh bằng chứng:

- [Danh sách desktop](../../docs/verification/m02-admin-danh-sach-desktop.jpg)
- [Danh sách tablet](../../docs/verification/m02-admin-danh-sach-tablet.jpg)
- [Danh sách mobile](../../docs/verification/m02-admin-danh-sach-mobile.jpg)
- [Lưu nội dung thành công trên bài QA](../../docs/verification/m02-admin-luu-noi-dung.jpg)
- [Không có kết quả](../../docs/verification/m02-admin-ket-qua-rong.jpg)
- [Biểu mẫu dataset desktop](../../docs/verification/m02-admin-bieu-mau-desktop.jpg)
- [Biểu mẫu mobile](../../docs/verification/m02-admin-bieu-mau-mobile.jpg)

## Giới hạn và cách xem

Chưa có upload ảnh/GIF, quản lý nhóm cơ/gói/giáo án, audit người biên tập hoặc kiểm thử tranh chấp nhiều process trên MySQL thật. Không có cơ chế tự gộp hai bản sửa: người gặp 409 phải tải bản mới rồi biên tập lại. Seeder vẫn giữ nội dung Admin đã sửa; script đối chiếu JSON sẽ báo khác biệt biên tập có chủ ý, không phải công cụ tự sửa catalog.

Giao diện dùng frontend-design/ui-ux-pro-max: truy vấn design-system và retry vẫn đưa mẫu marketing không phù hợp màn hình quản trị; không lưu/áp bảng màu hoặc typography đó. Dùng phong cách và màu sẵn có của dự án cùng hướng dẫn chung về nhãn, focus, responsive; không thêm thư viện validation mới, server trả lỗi theo FormRequest.

Chạy Backend và Frontend theo README, đăng nhập Admin rồi mở `/admin/bai-tap`. Local demo: admin@example.test / Demo123456!. Không cần chạy migrate hoặc seed lại cho phần quản trị. Source tại BE/app/Http/Controllers/Api/BaiTapAdminController.php, BE/app/Http/Requests/*BaiTap*Request.php, BE/app/Services/BaiTapService.php; FE/src/views/Admin/BaiTap, services/baiTapAdminService.js và utils/baiTapAdmin.js.
