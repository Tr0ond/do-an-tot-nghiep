# Biểu đồ tiến độ KH — tự tập và tập với PT

Thực hiện và kiểm chứng ngày06/10/2026 theo yêu cầu chủ dự án. [Hợp đồng tổng quan](../features/TONG_QUAN.md), [quyết định C42](../DECISIONS.md#c42--kết-quả-buổi-tập-với-pt-trên-web-06102026-đã-chốt).

## Hành vi và phạm vi

Biểu đồ **Tiến độ tập luyện** tại web `/khach-hang/tong-quan` có cột chồng hai màu: tự tập theo màu nhấn hiện có, PT theo màu thông tin của theme; cùng ngày cộng vào tổng, mỗi nguồn giữ số riêng. Bộ lọc **Tất cả / Tự tập / Với PT** cập nhật số tổng, các cột, tooltip, bảng theo ngày và trạng thái rỗng. Khoảng **7/30/90 ngày** giữ loại buổi đang chọn. Chú giải cho biết tổng từng loại trong khoảng; có đường dẫn nhật ký tự tập/lịch hẹn PT. Trục dùng số nguyên và không sinh buổi mẫu lúc trống.

- Tự tập yêu cầu cả lịch và phiên HOAN_THANH, tính theo ngày lịch như trước.
- PT lấy lịch HOAN_THANH đã kết thúc, tính theo **ngày bắt đầu ở Asia/Ho_Chi_Minh**, mỗi lịch một lần. Không join bài/hiệp để tránh nhân số buổi; buổi qua nửa đêm vẫn thuộc ngày bắt đầu. Không cộng vắng mặt/hủy/quá hạn/chờ xác nhận/tương lai hoặc chỉ chốt kết quả chưa hoàn thành.
- KH chỉ nhận dữ liệu của mình từ session. Hết gói hoặc phân công đã kết thúc vẫn đọc được lịch sử PT; không yêu cầu có bảng kết quả chi tiết ở lịch cũ.
- API giữ `tien_do.so_buoi`/`theo_ngay[].so_buoi` là tự tập, thêm `so_buoi_tu_tap`, `so_buoi_pt`, `tong_so_buoi`; Mobile đang dùng trường cũ giữ ý nghĩa. **Chỉ giao diện web thay đổi**. KPI/tỷ lệ tự tập tháng này và BMI30 ngày giữ cách tính; nhãn tỷ lệ ghi rõ tự tập.
- Không migration, không thêm thư viện, không thay auth/lượt/trạng thái/luồng ghi kết quả, không sửa dữ liệu demo hiện có.

## Files/module

| File | Thay đổi |
| --- | --- |
| `BE/app/Services/TongQuanKhachHangService.php` | Aggregate PT theo khoảng UTC rồi nhóm ngày Việt Nam, payload bổ sung tương thích |
| `FE/src/views/TongQuan/HanhTrinhKhachHang.vue` | Bộ lọc cục bộ, tổng chọn, hai chuỗi, chú giải/tooltip/bảng, link và nhãn |
| `BE/tests/Feature/TongQuanTest.php` | Kiểm thử dữ liệu/PT, ngày, scope, trạng thái, lịch sử và GET không ghi |
| `FE/tests/hanhTrinhKhachHang.spec.js` | Kiểm tra gộp/lọc, API cũ, SSR nhãn/bảng/tooltip; trục nguyên |
| `BE/tests/Support/hanh-trinh-ui-fixture.php` | Tùy chọn `with-pt` chỉ thêm2 buổi PT hoàn thành trong DB QA riêng |
| `md/features/TONG_QUAN.md`, `md/DECISIONS.md`, `md/API_CONVENTIONS.md` | Ghi hợp đồng và tính tương thích |

## Kiểm chứng đã chạy

Windows, MariaDB10.4.32, Laravel/PHP của project, Vue3 Options API, Vite8.3.1, Vitest5.0.3, Chrome headless với Playwright có sẵn.

| Kiểm tra | Kết quả |
| --- | --- |
| `TongQuanTest` trên MariaDB riêng | **22 tests / 382 assertions đạt** |
| Toàn bộ FE `npm test` | **272 tests / 28 files đạt** |
| FE lint, Prettier file sửa; PHP Pint | Đạt |
| FE production build | Đạt, 235 modules |
| API/trình duyệt 7/30/90, các loại, theme sáng/tối, màn hẹp và KH trống | Đạt |

Backend kiểm tra boundary đầu khoảng theo UTC+7, buổi qua nửa đêm, ngoài7/30/90 ngày, lịch có trạng thái hoàn thành nhưng ở tương lai, nguồn của KH khác, chốt kết quả chưa hoàn thành, trạng thái loại trừ và lịch sử sau hết gói/thu hồi phân công. Trường cũ/tỷ lệ tự tập giữ nguyên; đếm lịch/kết quả/thông báo/lượt trước và sau GET giữ nguyên.

UI QA dùng database ngẫu nhiên riêng, KH giả có **3 tự tập + 2 PT = 5 buổi trong30/90 ngày**, **2 tự tập + 2 PT = 4 buổi trong7 ngày**. PT lọc riêng =2, tự tập =3 trong30 ngày; đổi khoảng vẫn giữ lựa chọn. Có ngày cột chồng1 tự tập+1 PT, tooltip/bảng cùng số. KH trống không sinh dữ liệu. Desktop1440×1000, màn hẹp390×844 không tràn ngang (`scrollWidth = 390`). Không phát sinh `pageerror`. Hai màu khác nhau theo theme; xem ảnh thật, không tuyên bố pixel-perfect với Figma. Database/process QA đã dọn, không thay `.env` hoặc dừng server web người dùng.

Đăng nhập đọc trên **web hiện tại localhost5173** bằng `kh.ketqua@demo.test`: dữ liệu thực tại lúc kiểm tra là **0 tự tập + 2 PT = 2 buổi** trong30 ngày, lọc PT hiển thị2. Không sửa nháp/chốt/lượt của bộ demo chủ dự án đang thử.

Bằng chứng: [tất cả desktop tối](../../docs/verification/tien-do-tu-tap-pt/all-desktop-dark.png), [PT riêng](../../docs/verification/tien-do-tu-tap-pt/pt-only.png), [desktop sáng](../../docs/verification/tien-do-tu-tap-pt/all-desktop-light.png), [màn hẹp](../../docs/verification/tien-do-tu-tap-pt/all-narrow-light.png), [tài khoản demo hiện tại](../../docs/verification/tien-do-tu-tap-pt/demo-current.png), [kết quả UI QA](../../docs/verification/tien-do-tu-tap-pt/result.json), [kết quả demo hiện tại](../../docs/verification/tien-do-tu-tap-pt/live-demo-result.json).

## Cách xem và giới hạn

Refresh web → **KH → Tổng quan → Tiến độ tập luyện**; thử **Tất cả/Tự tập/Với PT** và **7/30/90 ngày**. Buổi vừa chốt chỉ xuất hiện sau khi PT **Xác nhận hoàn thành** lịch hẹn; bấm **Cập nhật dashboard** hoặc refresh để lấy số liệu mới.

Không thay màn Mobile ở bước này. Chưa nghiệm thu trên MySQL8/điện thoại thật; kiểm tra database dùng MariaDB, màn hẹp là responsive web. Không tự làm lại toàn bộ KPI tháng để tránh trộn cách tính tỷ lệ tự tập và lịch hẹn PT.
