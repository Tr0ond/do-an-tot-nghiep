# M06 — Lịch tự tập, nhật ký và tiến độ — 03/10/2026

## Kết quả đã triển khai

Theo C35: KH tự lên lịch từ giáo án đang áp dụng, gồm bản tự tạo hoặc PT giao, không bắt buộc mua gói/PT. KH bắt đầu, ghi hiệp thực tế, lưu nháp, hoàn thành/hủy; giữ snapshot và lịch sử khi đổi giáo án. PT hiện phụ trách đọc tất cả nhật ký và thêm nhận xét riêng sau hoàn thành. Thống kê từ phiên hoàn thành gồm buổi, hiệp, lần, tổng tạ × lần và mức tạ cao nhất từng ngày theo bài.

File/module: migration000040; models LichTap/PhienTap/BaiTapTrongPhien/HiepTap/GhiChuHuanLuyen; NhatKyTapRequest/Service/Controller; routes KH/PT; FE NhatKyTap/index và ChiTiet, nhatKyTapService, utils, CSS; menu KH, lối tắt học viên PT và giáo án đang dùng. [Hợp đồng và payload](../features/NHAT_KY_TAP.md).

## Kiểm thử thực sự đã chạy

- Toàn Backend: `php artisan test` **195 tests / 5.583 assertions PASS**, database riêng **MariaDB10.4.32**, không SQLite/Event fake cho tranh chấp. Thời lượng khoảng178 giây. M06 có13 tests; dùng các database ngẫu nhiên `kiem_tra_nhat_ky_*` và worker PHP riêng.
- Kiểm tra hai process thật: tạo cùng lịch, bắt đầu, hoàn thành, nhận xét cùng UUID chỉ một hiệu ứng; hai bản ghi kết quả khác nhau cùng phiên bản có một409; ngừng giáo án trong lúc tạo lịch được tuần tự hóa bằng khóa KH.
- Kiểm tra quyền KH khác/Admin/PT hết phân công; PT mới đọc lịch sử, PT cũ bị thu hồi; KH không gói vẫn dùng; hết gói/đổi giáo án giữ nhật ký; không trừ counterPT.
- Kiểm tra phiên bản cũ, retry mất phản hồi, ID bài sai, ngày tương lai, trường lạ, tạ null/0, chỉ tiêu không trở thành kết quả; hoàn thành bất biến; lịch hủy giữ nháp và không cộng thống kê.
- Fault injection chứng minh rollback khi bắt đầu/lưu/hoàn thành lỗi giữa transaction. Migration up/down giữ dữ liệu cũ; down từ chối lịch mới. Chạy migration000040 trên database ứng dụng thành công, không reset/seed lại.
- Toàn Frontend: `npm run test -- --run` **182 tests /18 files PASS**; M06 thêm11 cases kể cả service. `npm run lint`, `npm run build`, format src và Pint phần BE thay đổi PASS. Test FE gồm UUID ổn định, số liệu chưa nhập, lưu trước hoàn thành/dùng version mới, lỗi giữ draft, double-submit, stale response, chỉ đọc KH/PT và xác nhận inline chưa gửi request trước khi đồng ý.

## Kiểm tra trình duyệt local

Dùng database fixture riêng, BE8017/FE5291, không tác động người dùng thật. Đã đăng nhập KH, tạo lịch hôm nay, bắt đầu, nhập1hiệp12lần10,50kg/nghỉ60giây, lưu nháp/tải lại, xác nhận hoàn thành ngay trong trang. Trang kết quả khóa input và danh sách thống kê đúng1buổi/1hiệp/12lần/126kg, mức tạ cao nhất10,5kg. PT qua lối tắt học viên đọc kết quả, gửi nhận xét và thấy tên/thời điểm/nội dung được lưu.

Desktop1440×1000, tablet768×1024, mobile390×844; kiểm tra light/dark, danh sách/bộ lọc/chi tiết/nhận xét, không tràn ngang. Không có console error trong luồng đã kiểm tra. Các tệp ảnh cùng thư mục: `m06-nhat-ky-desktop.png`, `m06-nhat-ky-mobile.png`, `m06-nhat-ky-pt.png`. Dữ liệu mẫu là kiểm thử, không phải lịch tập thật của chủ dự án.

Sau kiểm tra đã đóng hai tab QA, khôi phục viewport, xóa đúng database fixture và xác nhận cổng8017/5291 không còn lắng nghe. Không dừng server/ngrok của chủ dự án. Tài liệu đã kiểm tra UTF-8 và link nội bộ; git diff không có lỗi whitespace.

## Giới hạn

- Chưa chạy MySQL8; kết quả trên chứng minh MariaDB10.4.32. Chưa benchmark dữ liệu lớn/tải đồng thời; thống kê giới hạn366ngày và10bài.
- M06 hiện biểu đồ mức tạ trong trang nhật ký; báo cáo tổng hợp M09, số đo cơ thể, xuất báo cáo và chatbot M08 chưa hoàn thành. Không suy ra calo hoặc %mục tiêu.
- Chưa phát chuông mới cho lịch/nhận xét M06. Các thông báo M05 và chat hiện có giữ nguyên.
- Kết quả hoàn thành không chỉnh lại qua API này; nhận xét PT là bản ghi riêng. Lịch hủy giữ lịch sử, không tạo lại cùng tổ hợp giáo án/ngày/ngày trong giáo án.

## Xem trên dự án

KH mở **Lịch & nhật ký tập**; PT mở **Học viên & giáo án → Lịch & nhật ký học viên**. Dùng start.bat như trước. Máy khác cập nhật bằng `php artisan migrate` trong BE, không cần seeder mới. Bản áp dụng giáo án không tự sinh lịch; cần chọn ngày và tạo lịch chủ động.
