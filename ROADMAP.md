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

## Trạng thái triển khai ngày 02/10/2026

Bootstrap FE/BE, lockfiles, cấu hình local, migrations trên MariaDB 10.4.32 và luồng tài khoản đã chạy. Có Admin tạo PT/Admin, command Admin đầu tiên và seeder demo; [kiểm chứng tài khoản](docs/verification/M01_AUTH.md). Có 1.324 bài/19 nhóm, API/giao diện công khai và Admin thêm/sửa/đổi trạng thái bài tập. Có quản lý gói, bảng giá/quyền lợi; [kiểm chứng gói](docs/verification/M02_GOI_TAP.md). Đã có quản lý nhóm cơ M02; [kiểm chứng](docs/verification/M02_NHOM_CO.md). Đã bổ sung M01: quên/đặt lại mật khẩu, sửa hồ sơ KH/PT/Admin và khóa/mở khóa qua UI theo [kiểm chứng](docs/verification/M01_HO_SO_KHOI_PHUC.md); Admin đã quản lý giáo án, PT đọc giáo án đã duyệt theo [kiểm chứng](docs/verification/M02_GIAO_AN_MAU.md); đã có đặt mua/payOS/kích hoạt/đối soát và phân công PT M03; [kiểm chứng](docs/verification/M03_MUA_GOI.md).

## Những việc cần làm tiếp

- D01–D10 đã chốt chính sách chính; cụ thể hóa use case/ERD/API/quyền/trạng thái theo quyết định đó trước module tương ứng.
- Xác minh môi trường và dependencies tương thích; thông tin tài khoản/kênh payOS, tài khoản/quota Gemini và ngày bảo vệ chưa được cung cấp. Chỉ dùng API AI miễn phí; không tự bật billing.
- Đã có quản trị bài tập Admin (thêm/sửa/ngừng/khôi phục hiển thị) và quản lý gói/bảng giá/quyền lợi công khai; [kiểm chứng bài tập](docs/verification/M02_ADMIN_BAI_TAP.md), [gói tập](docs/verification/M02_GOI_TAP.md). Đã có Admin soạn/duyệt giáo án và PT đọc thư viện. Đã có quản lý nhóm cơ M02. Đã có sửa hồ sơ, quên/đặt lại mật khẩu và khóa/mở khóa tài khoản qua UI cho M01. Đã có M03/phân công PT và lịch M04; [kiểm chứng M04](docs/verification/M04_LICH_HUAN_LUYEN.md). Đã có chat realtime M07, giáo án cá nhân M05 và nhật ký tự tập M06; bước kế tiếp là chatbot M08. [Kiểm chứng M07](docs/verification/M07_CHAT.md). Webhook công khai ngrok đã nhận HTTP200 khi xác minh kết nối; chuyển tiền thật vẫn cần nghiệm thu môi trường.
- Tạo Admin đầu tiên bằng command với mật khẩu riêng khi triển khai thật; local có seeder demo. Tài khoản PT/Admin tiếp theo do Admin tạo.
- Kiểm thử tranh chấp trên MySQL thật khi triển khai gói/lịch; kết quả MariaDB hiện tại chỉ chứng minh phần đã thử.

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

## Bổ sung theo yêu cầu giao diện ngày 02/10/2026
Đã có dashboard KH/PT/Admin và / chỉ dành cho giới thiệu khi chưa đăng nhập; [hợp đồng](docs/features/TONG_QUAN.md), [kiểm chứng](docs/verification/DASHBOARD.md). Phần tổng quan M09 triển khai sớm với số liệu M01/M02, chưa coi báo cáo tổng hợp M09 hoàn thành. M03 đã có API/giao diện mua gói/payOS/phân công; M04 đã hoàn tất phần lịch PT; đã triển khai chat M07 local, giáo án M05 và lịch/nhật ký M06. Tiến độ theo bài có trong M06, báo cáo tổng hợp M09 còn sau chatbot M08.

## Giáo án cá nhân M05 — 03/10/2026

Đã có PT tạo/sửa nháp, gửi và hủy đề xuất; KH xem/xác nhận trong 24 giờ, thay thế và lưu lịch sử. Hai sự kiện chuông đã bật, kiểm thử tranh chấp trên MariaDB đạt. [Kiểm chứng và giới hạn UI](docs/verification/M05_KE_HOACH_TAP.md).

## Lịch và nhật ký M06 — 03/10/2026

Đã có KH tự lên lịch/ghi hiệp/lưu nháp/hoàn thành/hủy, PT xem và nhận xét, thống kê phiên hoàn thành và mức tạ theo bài. Không yêu cầu mua gói, không trừ buổi PT, giữ lịch sử khi đổi giáo án. Toàn BE195tests/5.583assertions trên MariaDB10.4.32, FE182tests/lint/build/format PASS; UI KH/PT và responsive/light/dark đã kiểm tra. [Hợp đồng](docs/features/NHAT_KY_TAP.md), [kiểm chứng](docs/verification/M06_NHAT_KY_TAP.md). Tiếp theo M08 chatbot tư vấn có nguồn và hạn mức; M09 báo cáo tổng hợp sau đó. Chưa coi M08/M09 hoàn thành.
