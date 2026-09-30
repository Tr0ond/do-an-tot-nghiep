# Kế hoạch 24 tuần

Chủ dự án làm một mình khoảng 8 giờ/ngày; số ngày làm mỗi tuần/ngày bảo vệ chưa cung cấp nên 24 tuần là baseline, không phải cam kết thời lượng. AI và realtime được thử sớm để phát hiện rủi ro, tích hợp sau khi auth/ownership ổn định.

| Giai đoạn | Tuần | Công việc | Điều kiện ra giai đoạn |
| --- | --- | --- | --- |
| P0 — Phân tích | 1–4 | Chốt scope/decision, use case, ERD, API; phác thảo 3 layout; thử AI/Reverb tối thiểu | Giảng viên/chủ dự án thống nhất phạm vi; không còn decision chặn M01–M03 |
| P1 — Nền tảng | 5–8 | Bootstrap Vue/Laravel, cookie auth, role/resource scope, profiles, catalog | Auth/authorization test đạt; KH không truy cập dữ liệu KH khác |
| P2 — Gói và lịch | 9–12 | Thanh toán payOS, phân công, slot/đặt lịch, chat văn bản cơ bản | Webhook/chống cấp gói lặp và double-booking được kiểm thử; chat lưu/nhận qua Reverb |
| P3 — Tập luyện và AI | 13–16 | Kế hoạch/xác nhận, nhật ký, xác nhận PT, chatbot có nguồn, thông báo | Luồng đầu-cuối chạy được; AI không sửa DB và không dựng gói giả |
| P4 — Tích hợp/QA | 17–20 | Reconnect/revocation, AI eval, biểu đồ/report, responsive, deploy | Không lỗi dữ liệu/quyền nghiêm trọng; có bằng chứng test môi trường triển khai |
| P5 — Nghiệm thu | 21–24 | Regression, seed/demo, backup/restore thử, báo cáo, slide, tập bảo vệ | Sản phẩm và hồ sơ có thể chạy/trình diễn lại |

## Thứ tự phụ thuộc module

M01 → M02 → M03 → M04/M07 → M05/M06 → M08/M09. AI có thể prototype sớm bằng dữ liệu giả; không mở quyền dữ liệu cá nhân trước M01. M07 cần phân công từ M03. Dữ liệu báo cáo chỉ được tính sau khi nghiệp vụ nguồn ổn định.

## Những việc cần làm ngay

- D01–D10 đã chốt chính sách chính; cụ thể hóa use case/ERD/API/quyền/trạng thái theo quyết định đó trước module tương ứng.
- Xác minh môi trường và dependencies tương thích; thông tin tài khoản/kênh payOS, tài khoản/quota Gemini và ngày bảo vệ chưa được cung cấp. Chỉ dùng API AI miễn phí; không tự bật billing.
- Khởi tạo framework trong thư mục trống an toàn; sau đó tích hợp vào `FE/` và `BE/` ở thư mục gốc, bảo toàn README và mẫu cấu hình hiện có.
- Khóa dependencies tương thích, tạo `.env.example` runtime thực tế và hướng dẫn chạy.
- Thiết kế một bảng tài khoản/ba role, policy ownership và seed tài khoản demo giả.

## Mốc đóng phạm vi

Sau tuần 16 ngừng thêm module lớn. Nếu chậm: bỏ typing/online, ảnh chat, xuất báo cáo nâng cao và UI animations trước; giữ chatbot tư vấn và chat văn bản vì đã được chủ dự án yêu cầu. Không bỏ validation/phân quyền/chống trùng để giữ số màn hình.

## Đầu ra học thuật viết song song

| Thời điểm | Tài liệu |
| --- | --- |
| Tuần 1–4 | Đề cương, bài toán, phạm vi, khảo sát, use case, quyết định nghiệp vụ |
| Tuần 5–8 | Kiến trúc, ERD, từ điển dữ liệu, API, phân quyền |
| Tuần 9–16 | Mô tả implementation và kết quả từng module |
| Tuần 17–20 | Test cases, AI eval, giới hạn, kết quả triển khai |
| Tuần 21–24 | Luận văn hoàn chỉnh, slide, video/kịch bản demo nếu cần |

## Kịch bản bảo vệ

1. KH xem catalog/FAQ và chọn gói có quyền lợi phù hợp.
2. Đăng ký/thanh toán payOS; Backend xác minh/cấp gói, Admin phân công nếu có PT. KH dùng chatbot trong hạn mức ngày và xem nguồn/thẻ gói.
3. KH và PT chat trên hai trình duyệt; người ngoài bị từ chối truy cập.
4. PT gửi kế hoạch, KH xác nhận; hai KH thử tranh cùng slot.
5. KH ghi kết quả; PT hoàn thành buổi; retry không trừ hai lượt.
6. Đổi PT theo chính sách đã chốt, kiểm tra thu hồi cả kết nối chat đang mở.
7. Xem biểu đồ và báo cáo tiền đã nhận, không cộng yêu cầu chưa thanh toán.

Chỉ trình diễn hành vi đã implement/test, không dùng dữ liệu UI giả để tuyên bố Backend hoàn thành.
