# Kiểm chứng danh mục gói tập — 01/10/2026

Đã triển khai quản lý gói cho ADMIN và bảng giá/chi tiết quyền lợi công khai. [Hợp đồng](../features/GOI_TAP.md). Chỉ catalog, không đặt mua/payOS/kích hoạt/cấp quyền. Migration bổ sung UUID nullable + unique index, giữ nguyên 28 migrations tạo bảng gốc và SQL/Draw.io.

## Môi trường và kiểm thử đã chạy

Windows; PHP 8.4.0, Laravel 13.34.0; MariaDB 10.4.32 qua driver mysql. FE Vue 3 Options API/JavaScript, Node 22.20.0, Vite 8.3.1, Vitest 5.0.3. Không thêm dependencies. Các lớp kiểm thử tạo DB ngẫu nhiên riêng, migrate và transaction từng ca; chỉ xóa DB tự tạo, không refresh DB ứng dụng.

| Kiểm tra | Kết quả |
|---|---|
| `php artisan migrate --force` tại BE | Migration bổ sung 000029 đã chạy trên DB local |
| `php artisan test` | 56 tests / 3.387 assertions PASS; thêm 11 ca GoiTapTest |
| `npm run test` | 39 tests PASS; thêm 9 ca gói tập |
| `npm run build` | PASS, 133 modules |
| `npm run lint:check`, `npm run format:check` | PASS |
| Pint các file PHP thay đổi | PASS |
| Script đối chiếu migrations gốc / dữ liệu nguồn | PASS: 28 bảng / 303 cột thiết kế / 52 FK, 1.324 bài / 2.648 media |
| Tài liệu UTF-8 / link local / git diff whitespace | PASS: 10 tài liệu, 113 liên kết local; không lỗi whitespace |

Backend: toàn bộ endpoint Admin thiếu session 401, KH/PT hoặc account ngừng hoạt động 403; CSRF thiếu 419 và đúng token tạo thành công. Hai loại gói, dữ liệu sai/âm/thập phân/quota 0, quyền chatbot tắt, payload thừa, UUID sai trả 422. Retry UUID cùng nội dung giữ một gói; khác nội dung 409, UNIQUE thật tại DB chặn UUID trùng. Sửa/trạng thái bản cũ 409, không ghi đè. Sửa giá/quyền lợi rồi ngừng bán giữ toàn bộ snapshot T05 và tham chiếu; không tạo thanh toán/cấp quyền. Public chỉ thấy gói hợp lệ đang bán; ẩn/ID thiếu 404; kiểm tra tìm literal `%_=`/lọc/phân trang và DELETE 405. Đây là kiểm thử tuần tự trên DB thật, chưa kiểm thử cạnh tranh nhiều process hoặc MySQL thật.

Frontend Vitest chạy trong Node: URL/filter hợp lệ, public bỏ trạng thái Admin; whitelist payload, chuyển chatbot bỏ PT cũ; định dạng VND nguyên không bịa giá trống; hủy/bỏ response danh sách cũ; detail 404; double-submit, retry mạng giữ UUID/form, 409 chặn lưu giữ nội dung; nhận phiên bản mới và bỏ kết quả lưu sau đổi route; public không đổi trạng thái, Admin gửi đúng phiên bản. Không coi test method là kiểm thử DOM/E2E; phần giao diện được kiểm tra riêng trong trình duyệt.

## Kiểm tra giao diện local

Admin demo tạo hai gói QA, mặc định ngừng bán; sửa quota PT từ 40 lên 50 rồi mở bán từng gói. Public hiển thị đúng giá/ngày/buổi/quota, lọc PT lưu trên URL và liên kết chi tiết giữ bộ lọc. Kiểm tra desktop 1440×900, tablet 768×1024, mobile 390×844; sửa nhãn quyền lợi/nút điều hướng để không gãy dòng trên mobile; không tràn ngang ở các kích thước đã quan sát. Loading, trạng thái trống và điều hướng về bảng giá đã quan sát. Input số có min/max/step=1; đổi trang đưa về đầu trang, query filter giữ vị trí. Console warn/error thu được rỗng.

Sau đó ngừng bán cả hai gói bằng giao diện: danh mục Admin giữ record nhưng chi tiết công khai trả trạng thái không tìm thấy. Dọn đúng hai record QA tự tạo (#1/#2) bằng helper tạm: khóa dòng, đối chiếu tên/giá/ngày/buổi/quota/UUID/trạng thái và không có tham chiếu T05 trước xóa. Helper đã được xóa; không có DELETE API. DB ứng dụng còn 0 gói, không seed hay phát hành giá demo. Đã trả viewport về mặc định và mở biểu mẫu trống để chủ dự án nhập gói.

Ảnh có nhãn QA chỉ là dữ liệu thử, không phải bảng giá được chủ dự án chốt:

- [Admin desktop](../../docs/verification/m02-goi-tap-admin-desktop.jpg), [tablet](../../docs/verification/m02-goi-tap-admin-tablet.jpg), [Admin mobile](../../docs/verification/m02-goi-tap-admin-mobile.jpg).
- [Lưu quyền lợi](../../docs/verification/m02-goi-tap-luu-quyen-loi.jpg).
- [Biểu mẫu mobile](../../docs/verification/m02-goi-tap-bieu-mau-mobile.jpg), [biểu mẫu trống bàn giao](../../docs/verification/m02-goi-tap-them-goi.jpg).
- [Bảng giá desktop](../../docs/verification/m02-goi-tap-bang-gia-desktop.jpg), [tablet](../../docs/verification/m02-goi-tap-bang-gia-tablet.jpg), [mobile](../../docs/verification/m02-goi-tap-bang-gia-mobile.jpg).
- [Chi tiết desktop](../../docs/verification/m02-goi-tap-chi-tiet-desktop.jpg), [mobile](../../docs/verification/m02-goi-tap-chi-tiet-mobile.jpg).
- [Trạng thái chưa mở bán](../../docs/verification/m02-goi-tap-chua-mo-ban.jpg).
- [Gói ngừng bán không còn truy cập công khai](../../docs/verification/m02-goi-tap-ngung-ban.jpg).

Kiểm tra phụ `kiemTraCatalogDatabase.php` báo bài nguồn `5201` có `cac_buoc` khác JSON. Không seed/sửa để ghi đè nội dung đã biên tập. Module gói không thay đổi bài tập hay media; báo cáo kiểm chứng bài tập trước đây giữ kết quả lịch sử của thời điểm đó.

## Xem và chạy

Sau khi clone/pull, từ BE chạy `composer install`, cấu hình `.env`/APP_KEY/database theo README, rồi `php artisan migrate`; không `migrate:fresh`. Chạy Backend localhost:8000, FE localhost:5173. Vào `/admin/goi-tap` bằng Admin, thêm gói với giá/quyền lợi của chủ dự án và mở bán. Public `/goi-tap`; chi tiết `/goi-tap/:id`. Không tự seed hoặc phát hành giá thương mại. Mua/payOS/kích hoạt gói, giáo án mẫu và các module còn lại chưa triển khai.
