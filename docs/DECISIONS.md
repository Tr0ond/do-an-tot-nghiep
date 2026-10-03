# Quyết định và giả định

## Yêu cầu đã xác nhận từ cuộc trao đổi

| ID | Nội dung | Trạng thái |
| --- | --- | --- |
| C01 | Dự án mới nhỏ hơn Fitness, dành cho đồ án khoảng 6 tháng | ĐÃ XÁC NHẬN |
| C02 | Ba tác nhân Khách hàng, PT, Admin | ĐÃ XÁC NHẬN |
| C03 | FE Vue.js, BE Laravel | ĐÃ XÁC NHẬN |
| C04 | Chatbot tư vấn gói, mục tiêu, lịch tập cơ bản | ĐÃ XÁC NHẬN |
| C05 | Chat PT–KH 1–1 realtime | ĐÃ XÁC NHẬN |
| C06 | Tạo folder E:/Dự án tốt nghiệp, Base và tài liệu | ĐÃ XÁC NHẬN |
| C07 | Tham khảo cách code trong ba dự án đã nêu | ĐÃ XÁC NHẬN |
| C08 | Admin tự tạo các gói có quyền lợi khác nhau, gồm gói chatbot riêng và gói PT theo buổi kèm chatbot | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C09 | Admin đặt số lượt hỏi chatbot mỗi ngày cho từng gói | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C10 | Gói chatbot/gói kết hợp kích hoạt ngay khi thanh toán được xác nhận; PT và chatbot trong gói kết hợp dùng chung thời hạn | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C11 | Mỗi khách chỉ có một gói khả dụng tại một thời điểm | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C12 | Thanh toán trực tuyến qua cổng thanh toán | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C13 | Chatbot tính một lượt cho câu trả lời hợp lệ; lỗi dịch vụ không mất lượt, retry cùng request không tính lặp; cấp lại hạn mức lúc 00:00 giờ Việt Nam | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C14 | Hết buổi PT nhưng gói còn thời hạn thì chatbot tiếp tục theo hạn mức ngày | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C15 | Cổng thanh toán payOS; đơn giữ giá và chờ thanh toán 15 phút, hết hạn tạo đơn mới | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C16 | Catalog và FAQ miễn phí; chatbot cần gói có quyền chatbot | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C17 | Một PT phụ trách/KH; chặn đổi lúc buổi đang diễn ra, hủy lịch chưa bắt đầu/vô hiệu đề xuất chưa duyệt, giữ kế hoạch đã duyệt và lịch sử | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C18 | Buổi PT 60 phút; đặt trước ít nhất 4 giờ, KH hủy trước ít nhất 2 giờ; chờ xác nhận tối đa 2 giờ hoặc đến trước buổi 2 giờ; vắng mặt không trừ buổi/không phạt | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C19 | KH duyệt kế hoạch trong 24 giờ, PT lập lịch tự tập sau duyệt; một kế hoạch đang dùng, thay bằng bản mới và giữ lịch sử | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C20 | Thời hạn gói theo ngày đủ 24 giờ từ mốc Backend xác nhận thanh toán; lịch PT kết thúc <= hạn gói, xác nhận trong 24 giờ sau buổi hợp lệ dù gói vừa hết hạn | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C21 | Chat PT theo phân công còn hiệu lực, không phụ thuộc gói; KH giữ chat cũ, PT cũ mất quyền, PT mới không đọc chat cũ, Admin không đọc chat riêng | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C22 | Giao diện giờ Việt Nam, DB UTC; giữ lịch sử suốt đồ án, không tự purge/không xóa lịch sử khi khóa tài khoản, log không ghi đầy đủ dữ liệu riêng | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C23 | Tiền đúng/đủ trong hạn được tự cấp gói dù thông báo muộn; ngoại lệ tiền muộn/thiếu/thừa/đã có gói được Admin đối soát, hoàn tiền thủ công có ghi kết quả; chưa mua nối tiếp/nâng cấp | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C24 | Chatbot làm theo hướng CNPM: Gemini từ Laravel, dữ liệu database, structured response/validation/fallback; chỉ dùng hạn mức API miễn phí, hết hạn mức báo bận không mất lượt | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C25 | Làm một mình khoảng 8 giờ/ngày, một phòng gym; tên đề tài có thể đổi, Bootstrap 5.3 làm nền tảng và được bổ sung CSS/Tailwind khi cần | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C26 | Frontend Vue 3, JavaScript và Options API | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C27 | Chặn đổi PT khi buổi đã diễn ra chưa xử lý xong; hết gói vẫn xem kế hoạch/lịch sử và ghi nhật ký từ lịch tự tập hợp lệ đã có | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |
| C28 | Buổi quá 24 giờ chưa xác nhận được ghi quá hạn, không trừ buổi; Admin đóng xử lý có lý do/lịch sử, không sửa số buổi hoặc xác nhận thay PT; sau đó được đổi PT | ĐÃ XÁC NHẬN ngày 01/10/2026 bởi chủ dự án |

Một người khoảng 8 giờ/ngày, một phòng gym, Vue Options API/JavaScript và Bootstrap 5.3 đã được xác nhận. Tên đề tài còn là tên tạm có thể đổi. 24 tuần, 28 màn hình/28 bảng và Laravel 13 là baseline lập kế hoạch/kỹ thuật; phiên bản tương thích cần xác minh khi bootstrap. Ngày bảo vệ và số ngày làm mỗi tuần chưa được cung cấp, không tự quy đổi 8 giờ/ngày thành 56 giờ/tuần hoặc cam kết ngày bàn giao. Thanh toán thủ công được thay bằng yêu cầu payOS theo C12/C15.

## Quyết định nghiệp vụ

| ID | Câu hỏi cần quyết định | Quyết định và trạng thái | Module liên quan |
| --- | --- | --- | --- |
| D01 | Thu tiền/giữ giá thế nào? Được dùng đồng thời bao nhiêu gói? | ĐÃ CHỐT C08/C11/C12/C15/C23: gói cấu hình, một gói khả dụng, payOS/chờ 15 phút; tiền đúng/đủ trong hạn tự cấp gói dù thông báo muộn, ngoại lệ đối soát/hoàn tiền thủ công có ghi kết quả, chưa mua nối tiếp/nâng cấp | M02/M03/M08 |
| D02 | Gói chatbot và gói có PT kích hoạt/thời hạn tính từ lúc nào? | ĐÃ CHỐT C10/C20: Backend xác nhận thanh toán thì kích hoạt; thời hạn số ngày ×24 giờ, gói kết hợp chung thời hạn; tại mốc hết hạn không cấp quyền sử dụng mới, ngoại lệ xác nhận buổi đã diễn ra theo D05 | M03/M04/M06/M08 |
| D03 | Đổi PT khi có lịch cũ/đề xuất chờ xử lý ra sao? | ĐÃ CHỐT C17/C27: một PT/KH, chặn đổi khi buổi đang diễn ra hoặc buổi đã diễn ra chưa xử lý xong; hủy lịch chưa bắt đầu/vô hiệu đề xuất chưa duyệt, giữ kế hoạch đã duyệt/kết quả/lịch sử, KH đặt lại. Buổi quá hạn xác nhận xử lý theo D05 | M03/M04/M05/M07 |
| D04 | Slot dài bao lâu, hủy trước mấy giờ, no-show có trừ lượt? | ĐÃ CHỐT theo C18: slot 60 phút, đặt trước >=4 giờ, KH hủy trước >=2 giờ; yêu cầu chờ hết hiệu lực ở mốc sớm hơn giữa sau tạo 2 giờ và trước buổi 2 giờ; vắng mặt ghi nhận, không trừ buổi/không phạt | M04/M06 |
| D05 | Gói hết hạn khi còn lịch hẹn, PT xác nhận muộn? | ĐÃ CHỐT C14/C20/C27/C28: hết buổi vẫn dùng chatbot đến hạn gói; buổi PT kết thúc <= hạn gói được xác nhận trong 24 giờ sau kết thúc, trừ đúng gói gắn lịch, không mượn gói mới. Quá 24 giờ ghi quá hạn/không trừ buổi; Admin đóng xử lý có lý do, không xác nhận thay PT/sửa counter, sau đóng được đổi PT. Hết gói vẫn xem/ghi nhật ký từ lịch hợp lệ | M03/M04/M06/M08 |
| D06 | Kế hoạch cần KH duyệt? TTL? Ai tạo lịch tự tập? | ĐÃ CHỐT theo C19: KH duyệt trong 24 giờ; PT lập lịch sau duyệt; một kế hoạch đang dùng, bản thay thế mới, giữ bản cũ/kết quả và kiểm tra đề xuất stale/phân công khi duyệt | M05/M06 |
| D07 | Chat khi gói hết hạn? Admin có đọc chat? | ĐÃ CHỐT C21: gửi theo phân công hiệu lực, không phụ thuộc gói; KH giữ lịch sử, PT cũ mất quyền/PT mới không đọc chat cũ, Admin không đọc chat riêng; thu hồi cả socket đang mở | M07 |
| D08 | Provider/model, ngân sách AI, hạn mức theo gói, khách chưa đăng nhập/chưa mua gói? | ĐÃ CHỐT C08/C09/C13/C14/C16/C24: Gemini như CNPM, chỉ API miễn phí; model cấu hình được với baseline fallback `gemini-3.1-flash-lite` từ mã tham khảo, xác minh tài khoản lúc bootstrap. Chatbot cần gói, hạn mức ngày Admin đặt; chỉ tính câu trả lời hợp lệ/lỗi không mất lượt/retry không tính lặp, cấp lại 00:00 giờ Việt Nam; hết buổi PT còn hạn vẫn dùng chatbot, catalog/FAQ miễn phí | M02/M03/M08 |
| D09 | Múi giờ và chính sách lưu/ẩn dữ liệu nhạy cảm? | ĐÃ CHỐT C22: giao diện giờ Việt Nam, DB UTC; giữ lịch sử suốt đồ án, không tự purge, khóa tài khoản không xóa lịch sử, log không ghi đầy đủ chat/hồ sơ riêng | M01–M09 |
| D10 | Một hay nhiều người, thời gian/tuần, tên đề tài và UI baseline? | ĐÃ CHỐT C25/C26: một người khoảng 8 giờ/ngày, một phòng gym, Vue Options API/JS, Bootstrap 5.3 được bổ sung CSS/Tailwind; tên tạm có thể đổi. Ngày bảo vệ/số ngày làm mỗi tuần chưa cung cấp; phiên bản framework xác minh khi bootstrap | Lập lịch/bootstrap |

**Các chính sách chính D01–D10 đã được xác nhận theo C08–C28; không còn quyết định nghiệp vụ CHỜ CHỐT trong danh sách này.** Tài khoản/kênh payOS, Gemini và ngày bảo vệ chưa được cung cấp; đó là thông tin môi trường/lập lịch, không tự suy ra từ xác nhận nghiệp vụ. Hành vi mới ngoài các quyết định này vẫn phải được chốt trước khi triển khai.

## Ghi nhận xác nhận C08 — 01/10/2026

- Người xác nhận: chủ dự án, trong cuộc trao đổi về chốt nghiệp vụ.
- Nội dung: Admin tự tạo gói có quyền lợi khác nhau; có gói dùng chatbot riêng và có gói PT theo buổi kết hợp chatbot.
- Lý do: chủ dự án yêu cầu quyền lợi gói linh hoạt, thay giả định ban đầu chỉ có gói PT và chatbot không gắn gói.
- Trạng thái tại thời điểm ghi nhận: mô hình quyền lợi đã xác nhận; các phần khác chờ D01/D02/D05/D08. Các xác nhận tiếp theo được ghi bên dưới.
- Tác động: M02/M03/M08 và các nghiệp vụ kiểm tra quyền lợi liên quan; đã đồng bộ `README.md`, `PROJECT_RULES.md`, `SCOPE.md`, `ROADMAP.md`, `docs/DATABASE_DRAFT.md`, `docs/API_CONVENTIONS.md`, `docs/ARCHITECTURE.md`, `docs/features/AI_CHATBOT.md`, `docs/TEST_PLAN.md`.
- Dữ liệu: chưa có runtime/migration hoặc dữ liệu khách hàng để chuyển đổi. Khi triển khai cần giữ snapshot quyền lợi đã mua; không để sửa catalog thay đổi quyền lợi lịch sử.

## Ghi nhận xác nhận C09–C11 — 01/10/2026

- Người xác nhận: chủ dự án, qua các lựa chọn trả lời trong cuộc trao đổi.
- Nội dung: số lượt chatbot mỗi ngày do Admin đặt cho từng gói; thanh toán được xác nhận thì kích hoạt ngay; gói kết hợp dùng chung thời hạn; mỗi khách một gói khả dụng.
- Lý do: chủ dự án chọn ba phương án đề xuất cho hạn mức, kích hoạt và số gói đồng thời.
- Trạng thái tại thời điểm ghi nhận: các nội dung trên đã xác nhận, các phần khác còn chờ. Các xác nhận tiếp theo được ghi bên dưới.
- Tác động: M02/M03/M04/M06/M08; các tài liệu được đồng bộ như danh sách tại C08.
- Thay thế: phương án kích hoạt ở buổi PT đầu tiên và chatbot không có hạn mức theo gói chưa từng được duyệt; không dùng làm mặc định triển khai.
- Dữ liệu: chưa có runtime/migration để chuyển đổi; khi triển khai phải giữ snapshot hạn mức ngày và kiểm tra một gói khả dụng tại một thời điểm ở Backend.

## Ghi nhận xác nhận C12–C19 — 01/10/2026

- Người xác nhận: chủ dự án, qua các lựa chọn và câu trả lời trong cuộc trao đổi.
- Nội dung: payOS, đơn giữ giá/chờ 15 phút; chỉ tính câu trả lời chatbot hợp lệ, lỗi không mất lượt/retry không tính lặp, cấp lại 00:00 giờ Việt Nam; hết buổi PT còn thời hạn vẫn dùng chatbot; catalog/FAQ miễn phí, chatbot cần gói. Quy tắc đổi PT, lịch hẹn và duyệt kế hoạch đã chọn như C17–C19.
- Lý do: chủ dự án chọn các phương án và nêu payOS trong câu trả lời về cổng thanh toán.
- Trạng thái tại thời điểm ghi nhận: các nội dung trên đã xác nhận; các phần khác được chốt ở bản ghi tiếp theo.
- Tác động: M02–M09; đồng bộ các tài liệu tại C08 và `TECHNOLOGY.md`, `docs/features/REALTIME_CHAT.md`.
- Thay thế: thanh toán thủ công chỉ là đề xuất cũ, không tiếp tục làm luồng mặc định. Admin xem/đối soát; Backend xác minh kết quả payOS để ghi nhận và cấp gói.
- Dữ liệu/môi trường: chưa có runtime, chưa gọi API payOS, chưa có giao dịch hay kết quả kiểm thử tích hợp. Tình trạng tài khoản/kênh payOS chưa được cung cấp; không giả định có sandbox hoặc credentials.

## Ghi nhận xác nhận C20–C27 — 01/10/2026

- Người xác nhận: chủ dự án, qua các lựa chọn và phát biểu trực tiếp trong cuộc trao đổi.
- Nội dung: thời hạn gói/ngưỡng lịch PT/xác nhận 24 giờ, chat PT theo phân công, UTC/giờ Việt Nam và lưu lịch sử, xử lý ngoại lệ payOS, Gemini như CNPM chỉ miễn phí, một người khoảng 8 giờ/ngày và một phòng gym, Bootstrap 5.3/CSS bổ sung, tên tạm, Vue Options API/JS; chặn đổi PT khi buổi chưa xử lý và giữ quyền nhật ký sau hết gói.
- Lý do: chủ dự án chọn các phương án và xác nhận riêng Options API sau khi được giải thích.
- Trạng thái: đã xác nhận nội dung trên. Ngoại lệ buổi quá 24 giờ ở D05 còn chờ; ngày bảo vệ/số ngày làm mỗi tuần và thông tin tài khoản dịch vụ chưa được cung cấp.
- Tác động: M01–M09; đồng bộ `PROJECT_RULES.md`, `SCOPE.md`, `README.md`, `ROADMAP.md`, `CODE_STYLE.md`, `TECHNOLOGY.md`, thiết kế dữ liệu/API/kiến trúc, hai tài liệu tính năng, kế hoạch test và README FE/BE.
- Thay thế: phương án cũ từ chối mọi xác nhận sau khi gói hết hạn không còn dùng; chấp nhận buổi hợp lệ trong cửa sổ 24 giờ. Điều cấm trộn Tailwind với Bootstrap trong baseline cũ được thay bằng quyền dùng CSS/Tailwind bổ sung theo C25; cần kiểm soát xung đột khi triển khai.
- Dữ liệu/môi trường: chỉ cập nhật tài liệu, chưa có migration/runtime, chưa gửi dữ liệu tới dịch vụ ngoài. Mã CNPM đặt model fallback như trên; không đọc `.env` nên không xác nhận model/billing thực tế của dự án cũ.

## Ghi nhận xác nhận C28 — 01/10/2026

- Người xác nhận: chủ dự án, qua lựa chọn trả lời về buổi quá hạn.
- Nội dung/lý do: tránh chặn đổi PT vô thời hạn khi buổi đã quá 24 giờ chưa xác nhận; đánh dấu quá hạn/không trừ buổi, Admin xem xét và đóng xử lý có lý do/lịch sử. Admin không sửa số buổi hoặc xác nhận thay PT; sau đóng được đổi PT.
- Trạng thái: đã xác nhận; thay trạng thái chờ của phần còn lại D05.
- Tác động: M03/M04/M06, `PROJECT_RULES.md`, `SCOPE.md`, `docs/API_CONVENTIONS.md`, `docs/DATABASE_DRAFT.md`, `docs/TEST_PLAN.md`.
- Dữ liệu: chỉ đặc tả, chưa có runtime/migration hay test ứng dụng.

## Ghi nhận xác nhận C29 — 01/10/2026

- Người xác nhận: chủ dự án, yêu cầu dùng dữ liệu tại `E:/Dự án tốt nghiệp/exercises-dataset` và vẽ database bằng draw MCP; trả lời đã có quyền sử dụng ảnh/GIF.
- Nội dung: chuẩn bị catalog bài tập từ dataset và đưa ảnh/GIF vào tài nguyên dự án; giữ nguồn, ghi công và điều kiện media riêng. Không coi ảnh/GIF là MIT hoặc tự suy đoán thông số tập luyện/biên dịch tiếng Việt.
- Trạng thái: đã xác nhận phạm vi dữ liệu/quyền sử dụng media theo phát biểu của chủ dự án; không kiểm tra hoặc lưu tài liệu giấy phép riêng của chủ dự án.
- Tác động: M02, `BE/database/data/`, `BE/public/media/bai-tap/`, thiết kế 28 bảng/52 FK tại `BE/database/design/`, `docs/DATABASE_DICTIONARY.md`, bản vẽ `docs/diagrams/database.drawio` và scripts chuẩn bị/kiểm tra.
- Dữ liệu/môi trường: đã chuẩn hóa 1.324 bài/19 nhóm cơ/28 nhãn dụng cụ, sao chép 2.648 media và đối chiếu nguồn. Chưa khởi tạo Laravel/Vue, chưa chạy SQL/MySQL hoặc kiểm thử ứng dụng. Các quy tắc tiền/lượt/quyền hiện có không thay đổi.
- Đóng gói GitHub: repository nguồn [hasaneyldrm/exercises-dataset](https://github.com/hasaneyldrm/exercises-dataset) không được lồng vào repository dự án; catalog, giấy phép/ghi công và media đã chuẩn bị vẫn được giữ trong `BE/`. Cần clone nguồn vào `exercises-dataset/` ở thư mục gốc để chạy lại scripts; hướng dẫn tại `BE/database/data/README.md`.

## Ghi nhận xác nhận C30 — 03/10/2026

- Người xác nhận: chủ dự án hỏi cách gửi ảnh như nền tảng chat và yêu cầu “nếu bạn làm được hãy làm cho tôi”.
- Nội dung: bổ sung ảnh trong chat KH–PT, chọn nhiều ảnh/xem trước/bỏ ảnh, kéo thả/dán, chú thích tùy chọn, xem lớn và gửi lại khi lỗi. Giới hạn triển khai 4 ảnh/tin, 5 MB/ảnh, JPG/PNG/WebP; chiều rộng/cao tối đa 8.000 pixel.
- Trạng thái: yêu cầu mở rộng ảnh đã xác nhận; các giới hạn cụ thể là lựa chọn triển khai đã thông báo. Thay phạm vi văn bản của bản chat đầu, giữ quyền C21/D07, không thêm Admin đọc chat, video hoặc tệp tùy ý.
- Tác động: M07, migration000035 thêm JSON nullable `tin_nhan.anh`, API upload/đọc ảnh riêng tư, Vue chat, cách chạy PHP local và tài liệu module. Tin cũ giữ nguyên; rollback từ chối gỡ cột nếu đã có ảnh để tránh mất lịch sử.
- Ảnh lưu disk local riêng tư; retry so UUID, chú thích và SHA-256 theo thứ tự ảnh. Không trả đường dẫn lưu/hash ra FE; khi transaction thất bại dọn tệp của thao tác đó. Giữ ảnh cùng lịch sử suốt đồ án, không tự purge theo D09.

## Ghi nhận yêu cầu C31 — 03/10/2026

- Chủ dự án yêu cầu KH tự tạo giáo án dù chưa mua gói, PT xem được tất cả giáo án của KH đang phụ trách. Mở rộng M05, thay giới hạn chỉ PT tạo của R12/D06 trong phạm vi giáo án tự tạo.
- Backend gán nguồn `KHACH_HANG` hoặc `PT`; KH được soạn từ catalog, lưu/sửa nháp, tự áp dụng/hủy/lưu trữ. PT chỉ đọc bản tự tạo theo phân công hiện tại, không có quyền sửa thay; KH khác/Admin không được truy cập qua endpoint KH/PT.
- Chủ dự án trả lời: “KH có thể tự áp dụng giáo án nào mình muốn không cần PT phải duyệt PT có thể xem được KH đang dùng giáo án nào”. KH chọn nháp tự tạo hoặc áp dụng lại bản tự tạo đã lưu trữ; một bản tự tạo đang dùng riêng với một bản PT giao như phương án đã đề xuất. Bản đã áp dụng bất biến; tạo bản mới nếu cần sửa nội dung, giữ lịch sử. Không thêm quyền lợi gói, lịch hoặc nhật ký ở phần này.
- Tác động: migration000037 giữ giáo án PT cũ, nullable liên kết PT/phân công cho bản tự tạo, unique theo nguồn; Service/Request/routes và giao diện giáo án dùng chung, kiểm thử ownership/revocation/rollback/tranh chấp; cập nhật tài liệu M05.

## Ghi nhận yêu cầu C32 — 03/10/2026

- Chủ dự án chấp thuận ẩn giáo án tự tạo không còn tập và hiện lại trong mục “Đã ẩn”, thay vì xóa dữ liệu.
- Chỉ KH sở hữu được ẩn bản tự tạo đã hủy hoặc lưu trữ. Bản nháp cần hủy trước; bản đang áp dụng cần ngừng áp dụng trước. Giáo án PT giao không có thao tác ẩn này.
- Việc ẩn không đổi trạng thái nghiệp vụ, snapshot, lịch sử tập hoặc quyền đọc của PT phụ trách. KH cần hiện lại bản lưu trữ trước khi áp dụng lại.
- Lưu thời điểm `khach_an_luc`, kiểm tra phiên bản và khóa KH/giáo án khi ẩn/hiện lại. Danh sách KH mặc định bỏ bản đã ẩn; mục “Đã ẩn” cho phép xem và hiện lại.

## Ghi nhận yêu cầu C33 — 03/10/2026

- Chủ dự án đồng ý đề xuất sửa: mỗi KH chỉ một giáo án đang áp dụng, chung cho nguồn PT và KH; KH được ngừng áp dụng giáo án PT giao, không cần PT duyệt. Thay quy tắc hai bản theo nguồn của C31.
- Áp dụng tự tạo hoặc xác nhận đề xuất PT lưu trữ bản đang dùng bất kể nguồn trong cùng transaction. KH được ngừng bản PT đã nhận của mình kể cả sau đổi PT; PT vẫn chỉ đọc bản KH và không ngừng thay KH.
- Migration000039 chuyển unique về KH duy nhất; nếu dữ liệu cũ có hai bản đang dùng thì giữ bản có thời điểm cập nhật/áp dụng gần nhất, tie chọn ID lớn hơn, lưu trữ bản còn lại, không xóa bài/snapshot/lịch sử. Khi triển khai chạy migration trong maintenance để tránh ghi theo quy tắc cũ trong lúc đổi index.
- Giáo án PT đã lưu trữ không tự áp dụng lại qua endpoint tự tạo; tiếp tục nhận/xác nhận đề xuất mới từ PT. C32 ẩn/hiện lại vẫn chỉ cho giáo án tự tạo.

## Ghi nhận yêu cầu C34 — 03/10/2026

- Chủ dự án phản hồi không thể chọn lại giáo án PT sau khi dừng bản khác, tiếp nối yêu cầu KH tự chọn giáo án muốn áp dụng. KH được áp dụng lại giáo án PT của chính mình đã xác nhận trước đây và đang lưu trữ, không phải xin PT duyệt lại. Thay giới hạn không áp dụng lại PT trong C33.
- Chỉ bản PT có `gui_luc` và `duyet_luc`, trạng thái `LUU_TRU`, được áp dụng lại; retry khi đã `DANG_AP_DUNG` trả hiện trạng. Nháp, đề xuất chưa xác nhận, quá hạn hoặc đã hủy không được dùng đường này để bỏ qua duyệt lần đầu.
- Bản đã nhận/xác nhận là nội dung KH sở hữu quyền sử dụng; áp dụng lại không cần gói, phân công cũ còn hiệu lực hoặc PT cũ đang hoạt động. Không cấp quyền đọc/sửa lại cho PT cũ. PT hiện tại vẫn đọc giáo án KH đang dùng theo scope hiện có.
- Khóa KH rồi giáo án, kiểm tra version, lưu trữ bản đang dùng và áp dụng bản chọn trong cùng transaction; unique chung C33 giữ tối đa một bản. Giữ nguyên snapshot, ngày xác nhận lần đầu, hạn đề xuất và thông báo cũ; không tạo lịch, trừ buổi hay thêm thông báo xác nhận. Không cần migration mới.

## Cách ghi quyết định sau này

Với mỗi decision: trạng thái, phương án chọn, lý do, người xác nhận, ngày xác nhận, file/module bị tác động. Nếu thay đổi phương án đã chốt, ghi thay thế và tác động migration/dữ liệu lịch sử.

## Những lựa chọn kỹ thuật có thể thực hiện trong phạm vi

Tách API client, chuẩn hóa response, cleanup listeners, tổ chức docs, format và test không thay đổi quyền/tiền/lượt. Agent không cần hỏi lại cho từng file hoặc thao tác đọc/sửa tài liệu đã được yêu cầu.
