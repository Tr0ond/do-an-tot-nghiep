# Phạm vi đưa hệ thống lên mobile

## Mục tiêu và hiện trạng

Một app cho `KHACH_HANG` và `HUAN_LUYEN_VIEN`, dùng tài khoản, dữ liệu và Backend Laravel hiện có. `ADMIN` quản trị trên website Vue. Không tạo actor hoặc database nghiệp vụ mới. C40 đã xác nhận công nghệ và bootstrap; thứ tự triển khai dưới đây là đề xuất.

Backend đã có phần lớn nghiệp vụ web; app có UI1 mẫu và MB1–MB5: phiên/hồ sơ, lịch/tổng quan/học viên, tập luyện/số đo, chat/thông báo, đăng ký/khôi phục, gói/thanh toán và chatbot native. MB5 dùng nhà cung cấp giả lập khi QA; bản cài/điện thoại thật tiếp tục ở MB6. Component Vue, Pinia, Bootstrap, DOM và cấu hình cookie web không sao chép trực tiếp sang app.

## Ma trận chức năng

| Nhóm | KH | PT | Giai đoạn đề xuất |
| --- | --- | --- | --- |
| Tài khoản | Đăng nhập, đăng xuất, xem/sửa hồ sơ | Đăng nhập, đăng xuất, xem/sửa hồ sơ | MB1 |
| Tạo/khôi phục tài khoản | Chỉ KH tự đăng ký, quên/đặt lại mật khẩu | Khôi phục mật khẩu; tài khoản do Admin tạo | MB5 |
| Tổng quan | Lịch, giáo án, gói, PT, tiến độ của mình | Lịch hôm nay, việc chờ, học viên | MB2 |
| Lịch hẹn PT | Xem giờ trống, đặt, hủy, theo dõi lịch | Mở/đóng giờ, xác nhận/từ chối, hoàn thành/vắng mặt | MB2 |
| Học viên | Xem PT phụ trách qua dữ liệu hiện có | Danh sách, hồ sơ được phép, giáo án, nhật ký | MB2–MB3 |
| Catalog | Xem/lọc/chi tiết bài tập và ảnh minh họa | Tra bài và mẫu giáo án đã duyệt | MB3 |
| Giáo án | Xem, xác nhận bản PT; tự tạo/sửa nháp, áp dụng/ngừng/ẩn/hiện lại | Tạo/sửa nháp, gửi/hủy bản PT, đọc bản KH | MB3 |
| Lịch tự tập/nhật ký | Lên lịch, bắt đầu, lưu nháp, hoàn thành/hủy, tiến độ | Đọc/tạo lịch cho học viên, nhận xét phiên hoàn thành | MB3 |
| Chỉ số cơ thể | Ghi/sửa số đo, lịch sử và BMI | Chỉ đọc học viên hiện phụ trách | MB3 |
| Chat | Lịch sử, gửi chữ/ảnh, số chưa đọc | Chat với học viên hiện phụ trách | MB4 |
| Thông báo trong app | Danh sách, đọc, đi tới tài nguyên | Tương tự theo người nhận | MB4 |
| Chatbot | Hỏi theo gói, dùng dữ liệu cá nhân khi bật, tạo nháp theo yêu cầu | Không thêm quyền AI cho PT | MB5 |
| Gói/thanh toán | Xem gói, đơn, mở thanh toán và kiểm tra kết quả | Không quản trị thanh toán | MB5 |
| Push | Chỉ triển khai khi chốt phạm vi | Chỉ triển khai khi chốt phạm vi | Tùy chọn sau MB5 |

MB2 là lát cắt demo đầu tiên, không phải toàn bộ mục tiêu mobile. Chức năng chưa đưa vào app vẫn dùng trên web; không ghi “đầy đủ KH/PT” trước khi ma trận được nghiệm thu.

## Những quy tắc phải giữ

- Đặt lịch và ghi kết quả tự tập là hai luồng khác nhau. Chỉ PT hoàn thành lịch hẹn hợp lệ mới trừ một buổi; nhật ký/chat không trừ lượt.
- Một KH chỉ một giáo án đang áp dụng. KH tự tạo miễn phí; chỉ KH xác nhận bản PT lần đầu, trong thời hạn hiện có. Áp dụng lại bản PT đã xác nhận theo C34 không yêu cầu duyệt lần hai.
- Đổi PT không làm mất lịch sử KH; PT cũ mất quyền tài nguyên theo phân công, PT mới không đọc chat cũ. Admin không có màn hình đọc chat riêng.
- Chat PT theo phân công, không phụ thuộc còn gói. Chatbot theo quyền và hạn mức gói; lỗi không mất lượt.
- Trạng thái thanh toán, quota, deadline và quyền lấy từ Backend. Deep link, nút bị ẩn và dữ liệu lưu máy không chứng minh quyền.
- Không tự thêm offline sync, thanh toán tự động, video, IoT, nhiều chi nhánh, dinh dưỡng điều trị hoặc AI tự áp dụng kế hoạch.

## Quyết định còn thiếu

| ID | Trạng thái | Đề xuất để chủ dự án lựa chọn | Chặn phần nào |
| --- | --- | --- | --- |
| MB-D01 | ĐÃ CHỐT | Chủ dự án dùng Android; nghiệm thu Android trước, chưa cam kết nghiệm thu iOS | Đã xác định nền tảng đầu tiên; model/phiên bản thiết bị ghi khi QA |
| MB-D02 | ĐÃ CHỐT | Phiên có hạn 30 ngày, cho nhiều thiết bị; đăng xuất chỉ thu hồi phiên trên thiết bị đó, hết hạn đăng nhập lại | Đã triển khai và kiểm tra ở MB1 |
| MB-D03 | CHỜ CHỐT | Dùng thông báo trong app trước; push là phần riêng, chốt sự kiện và nội dung được hiển thị ngoài màn hình khóa | Push, dịch vụ gửi và migration thiết bị |
| MB-D04 | CHỜ CHỐT | Demo/bản cài nội bộ trước; store chỉ khi có tên app, package ID, tài khoản, chính sách và ngân sách | Ký và phát hành Android/iOS |

Các lựa chọn cấu trúc thư mục, service API, phân trang và cleanup là lựa chọn kỹ thuật, không cần biến thành bước xin phép riêng. Sau khi chủ dự án chốt một mục MB-D, cập nhật [DECISIONS](../../docs/DECISIONS.md) và tài liệu chịu tác động; không tự ghi đã chốt dựa vào bản đề xuất này.
