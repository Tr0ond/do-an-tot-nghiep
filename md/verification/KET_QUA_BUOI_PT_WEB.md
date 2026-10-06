# C42 — Kết quả buổi tập với PT trên web

Ngày kiểm tra: 06/10/2026. [Hợp đồng](../features/KET_QUA_BUOI_PT.md), [quyết định C42](../DECISIONS.md#c42--kết-quả-buổi-tập-với-pt-trên-web-06102026-đã-chốt).

## Đã triển khai

- PT chọn bài từ catalog phân trang, ghi số lần/tạ/nghỉ theo hiệp, ghi chú và nhận xét; thêm/bỏ bài và hiệp trong nháp. Không tạo kết quả từ chỉ tiêu giáo án. Tạ chưa ghi khác tạ 0 kg.
- Lưu nháp, tải lại tiếp tục; chốt có xác nhận, khóa chỉnh sửa. KH chỉ đọc kết quả của lịch mình, kể cả hết gói/đổi PT. PT khác hoặc phân công bị thu hồi không truy cập được.
- Phiên bản chống ghi đè giữa các tab, chống bấm trùng, giữ đúng payload khi mất phản hồi, cảnh báo rời trang, bỏ phản hồi đến muộn sau logout/đổi lịch. Backend quyết định scope, thời gian và trạng thái.
- Chốt gửi thông báo KH cùng transaction; không tự xác nhận lịch, không trừ lượt. Luồng hoàn thành/vắng mặt cũ giữ nguyên, lịch sử cũ không bắt buộc có kết quả chi tiết.

Các module mới: `BE/app/Http/Controllers/Api/KetQuaBuoiPtController.php`, `BE/app/Http/Requests/KetQuaBuoiPtRequest.php`, `BE/app/Models/KetQuaBuoiPt.php`, `BE/app/Services/KetQuaBuoiPtService.php`, `FE/src/views/LichHen/KetQua/index.vue`, `FE/src/services/ketQuaBuoiPtService.js`, `FE/src/utils/ketQuaBuoiPt.js`. Route API/Vue và chi tiết lịch hẹn đã thêm điểm mở màn hình; không sửa `LichHenService` hoặc luồng nhật ký tự tập.

Migration `2026_10_06_000044_create_ket_qua_buoi_pt_table.php` đã chạy trên database local bằng đúng đường dẫn migration này, tạo bảng mới với unique lịch hẹn và FK RESTRICT. Không fresh/rollback/seed database ứng dụng, không thay cấu hình `.env`.

## Kiểm thử thực sự đã chạy

Môi trường: Windows, PHP/Laravel của project, **MariaDB 10.4.32**, Vue 3 Options API, Vite 8.3.1, Vitest 5.0.3, Chrome headless qua Playwright có sẵn.

| Kiểm tra | Kết quả |
| --- | --- |
| Backend `KetQuaBuoiPtTest`, `LichHenTest`, `NhatKyTapTest` | **38 tests / 525 assertions đạt** |
| Frontend toàn bộ `npm test` | **269 tests / 28 files đạt** |
| Frontend lint và Prettier các file thay đổi | Đạt |
| Frontend production build | Đạt, 235 modules |
| PHP Pint các file C42 và route | Đạt |
| Migration000044 trên local | Đạt |

8 test backend mới kiểm tra role/ownership, thu hồi phân công, giới hạn thời gian, dữ liệu lạ/giới hạn/snapshot, version/retry, kết quả bất biến, rollback, không trừ lượt và hoàn thành lặp. Tranh chấp lưu/chốt thực sự dùng **hai PHP process trên MariaDB**, kiểm tra unique kết quả, request khác nội dung 409 và thông báo/audit không trùng; không suy từ SQLite/Event fake.

7 test frontend mới kiểm tra payload/validation, giao diện KH chỉ đọc/PT nhập được, double-submit, retry503 với cùng payload, 409 giữ nháp/tải lại có xác nhận, phản hồi muộn sau logout, cảnh báo dữ liệu chưa lưu và retry chốt, catalog/race/hiệp trống.

## Hành trình trình duyệt với API thật

Tạo database QA ngẫu nhiên riêng, tài khoản KH/PT giả, gói thử 8 lượt, lịch đã xác nhận và đã kết thúc. Không gọi payOS, không dùng tài khoản/dữ liệu của người dùng. Backend8014 và frontend5178 chỉ dùng biến môi trường tiến trình.

1. Đăng nhập PT → chi tiết lịch → Ghi / xem kết quả; chọn 2 bài từ catalog, ghi từng hiệp, giữ tạ bài1 chưa ghi, bài2 nhập7.5 kg, ghi nhận xét.
2. Lưu nháp → reload, dữ liệu vẫn còn. Chụp/kiểm tra desktop1440×1000 và màn hẹp390×844. Lần đầu phát hiện tràn ngang do nhãn ẩn trong bảng; thêm vùng định vị và dùng grid có sẵn, kiểm tra lại `scrollWidth = innerWidth = 390`.
3. Xác nhận chốt → không còn input ghi kết quả. KH đăng nhập riêng → mở lịch của mình → xem đúng bài, hiệp và nhận xét, không có control chỉnh sửa.
4. Kiểm tra giao diện tối/sáng bằng nút theme hiện có; chụp chế độ sáng sau khi transition hoàn tất.
5. Database sau lưu/chốt: **1 kết quả đã chốt, lịch DA_XAC_NHAN, còn8 lượt**. Qua giao diện lịch hẹn cũ xác nhận hoàn thành và gửi lại cùng thao tác: HTTP200, **lịch HOAN_THANH, còn7 lượt**, vẫn một kết quả. Đọc lại kết quả vẫn khóa sửa.
6. Không phát sinh `pageerror` trong hành trình PT/KH và xác nhận hoàn thành. Database QA đã xóa bằng helper kiểm tra prefix, các tiến trình QA đã dừng; app local giữ bảng mới và dữ liệu gốc.

Ảnh/bằng chứng: [nháp PT desktop](../../docs/verification/ket-qua-pt/pt-draft-desktop.png), [nháp PT màn hẹp](../../docs/verification/ket-qua-pt/pt-draft-narrow.png), [PT đã chốt](../../docs/verification/ket-qua-pt/pt-final-desktop.png), [chế độ sáng](../../docs/verification/ket-qua-pt/pt-final-light.png), [KH desktop](../../docs/verification/ket-qua-pt/kh-final-desktop.png), [KH màn hẹp](../../docs/verification/ket-qua-pt/kh-final-narrow.png), [sau hoàn thành lịch](../../docs/verification/ket-qua-pt/pt-after-attendance.png), [kết quả UI](../../docs/verification/ket-qua-pt/ui-result.json), [kết quả hoàn thành/retry](../../docs/verification/ket-qua-pt/attendance-result.json), [số lượt trong database QA](../../docs/verification/ket-qua-pt/database-result.json).

## Cách xem và giới hạn

- Tại web đang chạy: **PT → Lịch hẹn → Chi tiết → Ghi / xem kết quả**. Chỉ được ghi từ giờ bắt đầu, lịch đã xác nhận/hoàn thành, trước kết thúc+24 giờ; chốt sau kết thúc. Lịch tương lai vẫn mở xem được nhưng không có quyền nhập.
- **KH → Lịch hẹn → Chi tiết → Xem kết quả buổi tập**. Đây là lịch hẹn PT, khác màn lịch tự tập/nhật ký trong ảnh cũ.
- Máy clone/pull chạy `php artisan migrate` trong BE; không seed/fresh database đang có dữ liệu. Refresh frontend để nhận route mới.
- **Mobile chưa có màn này** theo phạm vi làm web trước. Chưa kiểm tra MySQL8 hoặc điện thoại thật cho C42; màn hẹp chỉ là responsive web. Không tuyên bố khớp Figma hoặc pixel-perfect cho phần bổ sung này.
