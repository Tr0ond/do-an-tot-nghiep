# Kiểm chứng thông báo nghiệp vụ — 04/10/2026

## Phần đã thay đổi

`ThongBaoService` bổ sung ghi thông báo cùng transaction, UUID theo sự kiện/người nhận và nhắc PT sau buổi. Tích hợp tại `MuaGoiService`, `PhanCongService`, `LichHenService`, `NhatKyTapService`, `GiaoAnMauService` và command/scheduler hiện có. Bảng/API/header/store hiện có tiếp tục được sử dụng; không migration hoặc thư viện mới.

- KH: thanh toán/kích hoạt, phân công/đổi PT, xác nhận/từ chối/hết hạn/hủy do đổi PT, hoàn thành/vắng buổi PT, nhận xét nhật ký. Giữ giáo án PT gửi trong M05.
- PT: phân công/kết thúc phân công, đặt/hủy lịch, buổi cần ghi nhận, nhật ký mới. Giữ xác nhận giáo án lần đầu trong M05.
- Admin: gói PT cần phân công, khoản thu cần đối soát, buổi quá hạn, nháp giáo án mẫu cần xem/duyệt.

Không sao chép chat/nhận xét/số đo vào thông báo Admin; PT cũ mất quyền tài nguyên. Tài liệu hợp đồng tại [NOTIFICATIONS.md](../features/NOTIFICATIONS.md), quyết định C39 tại [DECISIONS.md](../DECISIONS.md).

## Kiểm thử thực sự chạy

- Windows / PHP 8.4 / Laravel 13 / MariaDB 10.4.32; database ngẫu nhiên riêng, teardown/drop sau kiểm thử. Nhóm `ThongBaoTest|MuaGoiTest|LichHenTest|GiaoAnMauTest|NhatKyTapTest`: **71 tests / 1.046 assertions đạt**.
- Hồi quy toàn Backend sau sửa: `php artisan test --compact` đạt **259 tests / 6.337 assertions**, 217 giây. Kết quả được lưu vào log riêng khi chạy để kiểm chứng kể cả phiên đọc output kết thúc.
- Các ca mới kiểm tra retry giữ `read_at`, rollback thông báo/đổi PT/hủy lịch, kết nối khác chỉ thấy sau commit, phân công đúng người/thu hồi, tài khoản khóa, nhật ký và nhận xét không lộ nội dung, thời hạn đúng mốc, worker không lặp, tiền ngoại lệ không báo kích hoạt, gói chatbot riêng không báo phân công và nháp mẫu không spam. Sửa lỗi query phân công mới bỏ phần micro giây khiến thông báo đặt lịch ngay sau phân công bị bỏ sót; ca đặt/xác nhận/hủy/từ chối bảo vệ hồi quy.
- Vue 3 / Vite 8.3.1: **253 tests / 27 files đạt**, trong đó 28 ca header/store. Đối chiếu 14 đích thông báo với router thật và đúng vai trò, điều hướng sau đánh dấu đọc, chặn đổi session/lỗi server. Lint, format và build đạt; Pint/diff whitespace đạt.
- Browser QA dùng `thong-bao-nghiep-vu-ui-fixture.php`, service thật tạo sự kiện trong database `kiem_tra_thong_bao_ui_*`, BE 8019 / FE 5301. KH bấm nhận xét → `/khach-hang/lich-tap/6`, thấy đúng nhận xét QA, số chưa đọc 3 → 2 → 0 sau đọc tất cả. PT bấm nhật ký → `/pt/lich-tap/6`, số 2 → 1. Admin bấm nháp mẫu → `/admin/giao-an-mau/1/sua`, tên mẫu đúng, số 1 → 0. Đổi tài khoản không trộn danh sách; không lỗi/warn ở console.
- Fixture không gửi mail, AI, payOS, tin nhắn hoặc thao tác thanh toán thật. Không sửa `.env`/database chính. Tab QA đã đóng; database và helper khởi động QA đã xóa, các port riêng không còn chạy.

## Ảnh kiểm chứng

- [KH](thong-bao-nghiep-vu-kh.png).
- [PT](thong-bao-nghiep-vu-pt.png).
- [Admin](thong-bao-nghiep-vu-admin.png).

## Cách xem và giới hạn

Khởi động lại bằng `start.bat`, đăng nhập và bấm chuông. Thực hiện thao tác nghiệp vụ mới để nhận sự kiện; mở chuông làm mới danh sách, hoặc chờ tối đa 45 giây khi tab đang hiện. Giữ cửa sổ `schedule:work` để nhắc buổi đã kết thúc/quá hạn mỗi phút; không cần migrate/seed lại.

Các thông báo cũ là lịch sử, bấm sẽ đọc trạng thái/quyền hiện tại. Chưa bật nhắc trước buổi hoặc gói gần hết vì chưa chốt ngưỡng; chưa có email/push/realtime chuông. Không tự tạo thông báo lịch sử cho mọi giao dịch cũ. Nghiệm thu môi trường MySQL 8, chuyển tiền thật và chất lượng Gemini thật còn thuộc bước tích hợp/triển khai. Chưa coi toàn M09 hoặc dự án đã nghiệm thu.
