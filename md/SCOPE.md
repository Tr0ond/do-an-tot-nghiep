# Phạm vi dự án — v0.2

Các yêu cầu và chính sách chính đã xác nhận nằm tại [DECISIONS.md](DECISIONS.md). Số màn hình/bảng và kế hoạch 24 tuần vẫn là baseline để điều chỉnh; tài liệu không chứng minh ứng dụng đã triển khai.

## 1. Mục tiêu

Xây dựng website quản lý huấn luyện cá nhân cho một phòng gym, nối liền đăng ký gói, phân công PT, kế hoạch tập, đặt lịch, kết quả, chatbot tư vấn và chat trực tiếp.

Chủ dự án xác nhận làm một mình khoảng 8 giờ/ngày, quy mô một phòng gym. Kế hoạch 24 tuần là baseline; số ngày làm mỗi tuần/ngày bảo vệ chưa được cung cấp, không tự suy ra số giờ/tuần hoặc cam kết ngày bàn giao.

## 2. Chín module

| ID | Module | Nội dung chính |
| --- | --- | --- |
| M01 | Tài khoản và phân quyền | Đăng ký KH, đăng nhập, đặt lại mật khẩu, role, ownership |
| M02 | Danh mục | Gói dịch vụ cấu hình quyền chatbot/số buổi PT, nhóm cơ, bài tập, giáo án mẫu |
| M03 | Đăng ký gói và phân công | Thanh toán payOS, snapshot gói/quyền lợi, phân công với gói có PT |
| M04 | Lịch huấn luyện | Khung giờ, đặt/xác nhận/hủy lịch, chống trùng |
| M05 | Kế hoạch tập | PT tạo từ mẫu/KH xác nhận; KH tự tạo miễn phí; mỗi KH một bản đang dùng, ngừng cả bản PT; ẩn/hiện lại bản tự tạo đã hủy/lưu trữ; PT phụ trách đọc giáo án KH, lịch tự tập |
| M06 | Nhật ký và tiến độ | Hiệp/lần lặp/tạ, hoàn thành, nhận xét, biểu đồ |
| M07 | Chat realtime | Tin văn bản, lịch sử, chưa đọc/đã đọc, reconnect |
| M08 | Chatbot AI | Gói/mục tiêu/lịch mẫu/FAQ, thẻ dữ liệu thật, chuyển sang PT |
| M09 | Thông báo và báo cáo | Thông báo trong app, dashboard và tiền thực thu đã ghi nhận |

## 3. Chức năng theo tác nhân

### Khách hàng

- Đăng ký, quản lý hồ sơ, mục tiêu, kinh nghiệm và thời gian có thể tập.
- Xem catalog/FAQ miễn phí, đăng ký và thanh toán gói qua payOS, xem gói cá nhân và lịch sử thanh toán.
- Xem PT được phân công, đặt lịch với PT đó, theo dõi trạng thái lịch.
- Xem/xác nhận kế hoạch PT đề xuất; tập theo lịch cá nhân và ghi kết quả.
- Xem lịch sử, biểu đồ cân nặng/số buổi hoàn thành/mức tạ theo bài.
- Chat với PT theo phân công/quyền D07, dùng chatbot theo quyền lợi gói và chính sách D08, nhận thông báo.

### PT

- Quản lý hồ sơ chuyên môn, khung giờ rảnh, học viên đang phụ trách.
- Xem mục tiêu và lịch sử tập được phép; tạo kế hoạch, gửi đề xuất, ghi nhận xét.
- Xác nhận/từ chối yêu cầu đặt lịch; xác nhận buổi huấn luyện đã hoàn thành.
- Trao đổi với học viên qua chat; không xác nhận thanh toán hay tự tăng quota.

### Admin

- Quản lý tài khoản, hồ sơ PT, gói, bài tập, nhóm cơ và giáo án mẫu. Admin tự tạo gói có quyền lợi khác nhau, gồm chatbot riêng và PT theo buổi kèm chatbot; đặt số lượt chatbot mỗi ngày cho từng gói.
- Xem/đối soát thanh toán payOS, phân công/đổi PT với gói có PT, xem lịch toàn phòng gym. Backend xác minh kết quả thanh toán để cấp gói.
- Quản lý tài liệu FAQ/chính sách cho chatbot.
- Xem báo cáo đăng ký, tiền đã nhận, số buổi hoàn thành, học viên theo PT.
- Không mặc định đọc chat riêng; quyền kiểm duyệt nếu có phải quyết định riêng.

## 4. Danh sách 28 màn hình đề xuất

Modal, tab và drawer không tính là màn hình riêng. Cùng một trang có thể hỗ trợ nhiều thao tác. Một màn hình không đồng nghĩa một API.

| ID | Khu vực | Màn hình |
| --- | --- | --- |
| S01 | Chung | Đăng nhập |
| S02 | Chung | Đăng ký khách hàng |
| S03 | Chung | Quên mật khẩu |
| S04 | Chung | Đặt lại mật khẩu |
| S05 | Chung | Không tìm thấy/không có quyền |
| S06 | KH | Dashboard sau đăng nhập và truy cập danh mục gói; trang giới thiệu riêng cho guest |
| S07 | KH | Hồ sơ, mục tiêu và tiến độ cơ thể |
| S08 | KH | Gói của tôi và đăng ký/thanh toán |
| S09 | KH | PT phụ trách và thông tin chuyên môn |
| S10 | KH | Lịch hẹn huấn luyện |
| S11 | KH | Kế hoạch và lịch tập cá nhân |
| S12 | KH | Nhật ký buổi tập, lịch sử và biểu đồ |
| S13 | KH | Chatbot tư vấn |
| S14 | KH | Chat với PT |
| S15 | PT | Tổng quan và hồ sơ PT |
| S16 | PT | Danh sách/chi tiết học viên |
| S17 | PT | Khung giờ rảnh |
| S18 | PT | Lịch hẹn và xác nhận buổi |
| S19 | PT | Soạn kế hoạch và nhận xét kết quả |
| S20 | PT | Hội thoại với học viên |
| S21 | Admin | Tổng quan/báo cáo |
| S22 | Admin | Tài khoản KH/PT |
| S23 | Admin | Gói dịch vụ và quyền lợi chatbot/PT |
| S24 | Admin | Nhóm cơ và bài tập |
| S25 | Admin | Giáo án mẫu |
| S26 | Admin | Đăng ký, thanh toán và phân công PT |
| S27 | Admin | Lịch hẹn toàn hệ thống |
| S28 | Admin | Tài liệu tư vấn/FAQ và thống kê AI |

Thông báo dùng component chung trong layout. Không cần thêm một trang thông báo riêng ở bản đầu.

## 5. Giới hạn giúp hoàn thành trong sáu tháng

- Admin cấu hình gói chatbot riêng hoặc PT theo buổi kèm chatbot, đặt hạn mức chatbot theo ngày; kích hoạt khi thanh toán được xác nhận, thời hạn chung theo ngày đủ 24 giờ, mỗi khách một gói khả dụng. Chatbot cần gói phù hợp, chỉ tính câu trả lời hợp lệ, cấp lại 00:00 giờ Việt Nam; hết buổi PT còn thời hạn vẫn dùng chatbot. Gói Gym tự tập riêng chưa được xác nhận.
- Thanh toán payOS, đơn giữ giá/chờ 15 phút; Backend xác minh kết quả để cấp gói. Ngoại lệ tiền đến muộn/thiếu/thừa, đơn trùng và hoàn tiền phải theo D01. Không mặc định thu tiền thủ công.
- Bản đầu chưa mua nối tiếp/nâng cấp khi còn gói, không chia sẻ gói, không tính lương/hoa hồng PT.
- Mỗi bài tập gắn một nhóm cơ chính ở bản đầu; thiết bị có thể mô tả bằng văn bản.
- Kế hoạch PT giao cần KH duyệt trong 24 giờ; KH tự tạo/áp dụng không cần PT theo C31–C34, mỗi KH một bản đang dùng. KH hoặc PT hiện phụ trách lên lịch từ bản đang áp dụng theo C35; nhật ký tự tập không trừ lượt PT. Thay/ngừng bản cũ vẫn giữ lịch/kết quả; chưa xây dựng diff phức tạp.
- Buổi PT 60 phút, đặt trước >=4 giờ, KH hủy trước >=2 giờ; chờ xác nhận tối đa 2 giờ và không muộn hơn trước buổi 2 giờ. Vắng mặt không trừ buổi/không phạt. Buổi kết thúc trong hạn gói được xác nhận trong 24 giờ sau kết thúc dù gói vừa hết hạn; không mượn gói mới.
- Một PT/KH; chặn đổi khi buổi đang diễn ra hoặc buổi đã diễn ra chưa xử lý xong, hủy lịch chưa bắt đầu/vô hiệu đề xuất chưa duyệt, giữ kế hoạch đã duyệt/lịch sử. Buổi quá 24 giờ chưa xác nhận được ghi quá hạn/không trừ buổi; Admin đóng xử lý có lý do, không xác nhận thay PT/sửa số buổi; sau đó được đổi PT. Chat PT theo phân công, không phụ thuộc gói; KH giữ chat cũ, PT cũ mất quyền, PT mới không đọc chat cũ, Admin không đọc chat riêng.
- Gói hết hạn vẫn xem kế hoạch/lịch sử và ghi nhật ký từ lịch tự tập hợp lệ đã có. Gemini theo cách CNPM, chỉ hạn mức API miễn phí; hết quota báo bận không mất lượt gói. Bootstrap 5.3 làm nền tảng, được thêm CSS/Tailwind khi cần; Vue Options API/JavaScript đã xác nhận.
- Chat văn bản; gửi ảnh, typing/online là bổ sung sau. Không video/voice/chat nhóm.
- AI tư vấn dựa trên catalog/FAQ/lịch mẫu, không tự ghi kế hoạch hoặc đặt lịch.
- Theo C40 (05/10/2026), bổ sung app React Native + Expo trong `Mobile/` cho KH và PT; bước đầu chỉ khởi tạo bộ khung, dùng chung Backend Laravel khi tích hợp. Không thêm IoT, quản lý kho, nhiều chi nhánh hoặc dinh dưỡng điều trị.

## 6. Quy mô dữ liệu và kết quả

28 bảng nghiệp vụ dự kiến trong `md/DATABASE_DRAFT.md`, có thể đổi sau thiết kế được duyệt. Tables session/jobs/cache/password reset của Laravel không thuộc con số này.

Seed/demo: 50–100 KH, 5–10 PT, 30–50 bài tập, 3–5 gói và 5–10 mẫu. Test realtime tối thiểu hai trình duyệt và một client trái quyền. Chưa có kết quả benchmark, không tuyên bố đáp ứng hàng nghìn người đồng thời.

Sản phẩm bàn giao: ứng dụng triển khai được, đặc tả/ERD/API, bằng chứng test, bộ câu hỏi đánh giá AI, báo cáo đồ án, slide và kịch bản demo.
