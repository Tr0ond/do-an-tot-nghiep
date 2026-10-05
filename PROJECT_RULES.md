# PROJECT_RULES — Quy tắc chính của dự án mới

**Version:** 0.2 · **Ngày:** 01/10/2026 · **Trạng thái:** chính sách chính đã xác nhận; trạng thái từng phần tại DECISIONS, chưa triển khai.

Tài liệu này là nguồn quy tắc chính trong `E:/Dự án tốt nghiệp`. Không thay đổi quy tắc của `E:/Fitness`. Các thiết kế đề xuất chỉ trở thành quy tắc được duyệt khi chủ dự án chốt trong [DECISIONS.md](docs/DECISIONS.md). Chưa được chốt thì chưa triển khai hành vi phụ thuộc.

## 1. Phạm vi và tác nhân

**C01 — Yêu cầu đã xác nhận:** Vue.js ở FE, Laravel ở BE, ba tác nhân Khách hàng/PT/Admin, kế hoạch 6 tháng, chatbot AI tư vấn và chat 1–1 realtime.

**C02 — Phạm vi đã chốt:** một phòng gym, huấn luyện cá nhân, một PT phụ trách/KH, mỗi KH một gói khả dụng; payOS/đơn chờ 15 phút. Một người khoảng 8 giờ/ngày; Vue 3 Options API/JavaScript và Bootstrap 5.3, được bổ sung CSS/Tailwind khi cần. Tên đề tài có thể đổi; ngày bảo vệ chưa được cung cấp.

**C03 — Tách ba khái niệm:** kế hoạch là nội dung dự kiến; lịch hẹn là cuộc gặp PT; phiên tập là kết quả thực tế. Các ID/quan hệ không được đồng nhất ba loại này.

**Gói dịch vụ — yêu cầu đã xác nhận C08–C16:** Admin tự tạo gói với quyền lợi khác nhau, gồm chatbot riêng và PT theo buổi kèm chatbot; đặt số lượt chatbot mỗi ngày cho từng gói. Quyền chatbot và số buổi PT là hai quyền lợi riêng; gói chatbot riêng không cấp buổi PT. Gói kích hoạt ngay khi thanh toán được xác nhận; gói kết hợp dùng chung thời hạn. Mỗi khách một gói khả dụng; hết buổi PT vẫn dùng chatbot theo hạn mức ngày đến hết thời hạn, không kết thúc toàn gói chỉ vì số buổi về 0 khi quyền chatbot còn hiệu lực.

**C40/C41 — Mobile (05/10/2026):** chủ dự án yêu cầu React Native + Expo/JavaScript trong `Mobile/` cho KH và PT, dùng chung Backend Laravel. Website Vue tiếp tục trong `FE/`; Admin dùng web. Có UI1 mẫu và MB1 xác thực/hồ sơ thật. Chủ dự án chọn Android và phiên cố định 30 ngày, cho nhiều thiết bị, logout chỉ thu hồi phiên trên thiết bị đó; hết hạn đăng nhập lại. Lịch/tập luyện/chat native chưa tích hợp. Chi tiết tại [DECISIONS.md](docs/DECISIONS.md).

## 2. Quy tắc nền tảng kỹ thuật

**R01 — Backend quyết định:** giá, số lượt, quyền, thời gian, chuyển trạng thái do Laravel tính/xác thực. Không tin giá hoặc số buổi FE gửi, không coi văn bản AI là dữ liệu đã đúng.

**R02 — Xác thực và quyền:** role là `KHACH_HANG`, `HUAN_LUYEN_VIEN`, `ADMIN`. Chỉ KH tự đăng ký; PT/Admin do Admin tạo. Mỗi thao tác kiểm tra thêm tài nguyên của chính KH hoặc phân công PT còn hiệu lực. Router guard chỉ hỗ trợ giao diện.

**R03 — Bảo toàn dữ liệu:** gói đã mua giữ snapshot giá/quyền chatbot/số buổi PT/thời hạn và số lượt chatbot mỗi ngày. Đơn đăng ký, khoản thu, lịch hẹn, phân công, kế hoạch đã áp dụng, kết quả và tin nhắn được giữ lịch sử; không cascade xóa khi khóa tài khoản/ngừng dùng danh mục.

**R04 — Trạng thái có điều kiện:** không chuyển trạng thái tùy ý bằng API update chung. Các hành động xác nhận/hủy/hoàn thành có kiểm tra trạng thái trước, quyền và điều kiện nghiệp vụ. Retry trả kết quả cũ hoặc lỗi xung đột rõ ràng; không tạo hiệu ứng lần hai.

## 3. Gói dịch vụ và thanh toán — đã chốt D01/D02

**Cấu hình gói:** quyền chatbot và số buổi PT do Admin cấu hình; không suy ra quyền từ tên gói. Backend kiểm tra quyền lợi đã mua theo snapshot và trạng thái/hiệu lực được chốt trước thao tác cần quyền đó. Không gán buổi PT hoặc yêu cầu phân công PT chỉ vì khách mua gói chatbot riêng.

**R05 — Mua gói qua payOS:** KH tạo đơn; Backend lưu snapshot giá/quyền lợi và mốc hết hạn sau 15 phút từ lúc tạo đơn. Thay đổi catalog không đổi giá trong thời gian chờ; hết hạn khách tạo đơn mới theo catalog hiện tại. Backend tạo link payOS theo số tiền/mã đơn đã lưu và thời hạn của đơn; secrets chỉ ở BE.

Backend xác minh thông báo payOS bằng chữ ký, đối chiếu mã đơn/link/số tiền/trạng thái và thời điểm tiền trước ghi nhận thành công. Tiền đúng/đủ trong hạn được cấp gói dù thông báo tới muộn. Khoản thu và cấp/kích hoạt gói ghi trong một transaction với chống trùng; không giữ transaction khi chờ API cổng thanh toán. Trang quay về chỉ tải trạng thái server, không tự chứng minh đã trả tiền. Tiền trả sau hạn/thiếu/thừa hoặc KH đã có gói khả dụng được ghi nhận để Admin đối soát, không tự cấp thêm gói. Hoàn tiền thủ công có lưu kết quả/lý do; không tự gọi API hoàn tiền. Bản đầu chưa mua nối tiếp/nâng cấp khi còn gói; thu tiền thủ công không phải luồng mặc định.

**R06 — Kích hoạt đã chốt theo C10:** gói có hiệu lực ngay khi thanh toán được xác nhận, với mốc Backend ghi nhận trong transaction thanh toán/cấp gói. Gói kết hợp dùng chung thời hạn cho PT và chatbot. Retry xác nhận không tạo gói thứ hai hoặc đặt lại ngày kích hoạt/hết hạn. Dùng chatbot hay hoàn thành buổi PT không kích hoạt lại gói.

Admin nhập thời hạn theo ngày; mỗi ngày đủ 24 giờ từ mốc Backend xác nhận thanh toán. Tại mốc hết hạn không cấp quyền sử dụng mới. Thời hạn tính động, không giảm cột số ngày còn lại bằng cron. Gói chưa được xác nhận thanh toán chưa cấp quyền sử dụng; catalog/FAQ miễn phí, chatbot cần gói có quyền chatbot.

**R07 — Lượt PT:** chỉ PT đang có quyền với buổi được xác nhận mới hoàn thành buổi và tiêu hao đúng một lượt. KH không tự xác nhận, Admin không tự sửa counter. Chat và tự tập không trừ lượt.

Lưu `dang_ky_goi_tap_id` trên lịch hẹn. Chỉ đặt lịch có giờ kết thúc <= hạn gói và còn buổi PT hợp lệ. PT xác nhận sau khi buổi diễn ra, trong 24 giờ sau giờ kết thúc; buổi diễn ra trong hạn được tiêu hao gói đã gắn lịch dù gói vừa hết hạn. Không mượn gói mới/backdate thời điểm xác nhận hoặc để âm lượt. Quá 24 giờ chưa xác nhận thì ghi quá hạn, không trừ buổi; Admin xem xét/đóng xử lý có lý do và audit, không sửa counter hoặc xác nhận thay PT. Đóng xử lý không biến record thành buổi hoàn thành; sau đó không còn chặn đổi PT vì record này.

## 4. Phân công và lịch hẹn — đã chốt D03/D04

**R08 — Phân công:** một KH tối đa một PT hiệu lực tại một thời điểm. Chặn đổi khi có buổi đang diễn ra hoặc buổi đã diễn ra chưa xử lý xong; đổi PT đóng khoảng cũ, mở khoảng mới, hủy lịch chưa bắt đầu, vô hiệu đề xuất chưa duyệt và ghi audit trong cùng transaction. Giữ kế hoạch đã duyệt/kết quả/lịch sử; KH đặt lại với PT mới, không tự chuyển lịch. PT cũ mất quyền hồ sơ/chat khi phân công kết thúc, không xác nhận buổi qua phân công cũ.

**R09 — Khung giờ:** PT tạo slot theo ngày, dài 60 phút. KH đặt với PT phụ trách trước ít nhất 4 giờ; `CHO_XAC_NHAN` và `DA_XAC_NHAN` cùng giữ slot. Yêu cầu chờ hết hiệu lực ở mốc sớm hơn giữa sau tạo 2 giờ và trước buổi 2 giờ; Backend kiểm tra deadline kể cả worker dọn hết hạn chưa chạy. KH và PT không có lịch chồng thời gian.

**R10 — Tranh chấp đặt lịch:** transaction + khóa hàng/các tài khoản theo thứ tự ổn định + ràng buộc unique giữ slot. Hai request đồng thời cho cùng slot tối đa một thành công. Kiểm tra frontend hoặc `exists()` không khóa là chưa đủ.

**R11 — Hủy/vắng mặt:** KH được hủy trước ít nhất 2 giờ. Hủy/yêu cầu chờ hết hiệu lực giải phóng slot nhưng giữ record, người thao tác, thời điểm và lý do. Khách vắng mặt được ghi nhận, không trừ buổi và không thu phí phạt. Không cho KH hủy bằng API sau hạn; hủy do đổi PT theo R08 là hành động Admin riêng, không giả làm khách hủy đúng hạn.

## 5. Kế hoạch và kết quả — đã chốt D06

**R12 — Kế hoạch:** PT tạo kế hoạch từ catalog/mẫu cho KH đang phụ trách; KH xác nhận trong 24 giờ từ lúc gửi đề xuất. PT lập lịch tự tập sau khi KH duyệt; tối đa một kế hoạch PT đang dùng/KH. Đề xuất quá hạn không được apply dù worker chưa cập nhật trạng thái.

Theo yêu cầu C31 ngày 03/10/2026, KH được tự tạo giáo án từ catalog kể cả chưa mua gói/chưa có PT. KH sở hữu quyền tạo/sửa nháp/hủy và tự áp dụng; PT phụ trách hiện tại được đọc tất cả giáo án tự tạo, kể cả nháp, không sửa hoặc áp dụng thay KH. PT cũ mất quyền khi phân công kết thúc. Giáo án tự tạo phân biệt với PT giao bằng nguồn do Backend gán. C33 thay quy tắc hai bản theo nguồn: mỗi KH tối đa một giáo án đang áp dụng, chung cho PT và KH; chọn bản mới lưu trữ bản cũ bất kể nguồn. KH được ngừng bản PT đã nhận của mình mà không cần PT duyệt, kể cả khi đổi PT. Áp dụng/lưu trữ giữ snapshot và lịch sử; không cấp quyền PT/chatbot hoặc trừ buổi.

Theo C32, KH được ẩn/hiện lại giáo án tự tạo đã hủy hoặc lưu trữ. Đây là lựa chọn hiển thị của KH, không xóa lịch sử hay thu hồi quyền đọc của PT phụ trách. Giáo án đang áp dụng/nháp phải ngừng áp dụng/hủy trước khi ẩn; bản đã ẩn phải hiện lại trước khi áp dụng.

Theo C34, KH được áp dụng lại bản PT của mình đã gửi và đã xác nhận trước đây, hiện đang lưu trữ; không cần PT duyệt lại, gói hoặc phân công cũ còn hiệu lực. Không áp dụng lại nháp, đề xuất chưa xác nhận/quá hạn hoặc bản đã hủy qua thao tác này. Áp dụng lại giữ nội dung và thời điểm xác nhận ban đầu, lưu trữ bản đang dùng trong cùng transaction; không khôi phục quyền PT cũ hay tạo lịch/trừ lượt/thông báo xác nhận mới.

Kế hoạch thay thế là record mới chứa nội dung snapshot; kế hoạch cũ lưu trữ, không sửa nội dung đã gắn với phiên tập. Khi xác nhận cần đọc lại phân công, kế hoạch gốc và trạng thái; đề xuất cũ không được ghi đè thay đổi mới.

Theo C35, KH cũng được tự lên lịch từ giáo án đang áp dụng của mình (KH tự tạo hoặc PT giao), không cần gói/PT. PT hiện phụ trách có thể lên lịch; lịch tự tập đã có tiếp tục hợp lệ khi đổi giáo án, không tự tạo lịch hoặc tiêu hao lượt PT. Xem [NHAT_KY_TAP.md](docs/features/NHAT_KY_TAP.md).

**R13 — Nhật ký:** KH chỉ nhập kết quả của mình từ lịch tập hợp lệ. Phiên hoàn thành bất biến; PT thêm nhận xét ở dữ liệu riêng. Hủy/hoàn thành giữ lịch sử; hoàn thành lặp không sinh hai phiên/hiệu ứng.

Bổ sung C38 đã được chủ dự án xác nhận: KH ghi chiều cao/cân nặng theo ngày, hệ thống tính BMI và giữ lịch sử; miễn phí, không cần lịch/gói/PT. PT hiện phụ trách chỉ đọc. Không dùng vòng eo. AI chỉ nhận số đo khi KH bật dữ liệu cá nhân, BMI chỉ tham khảo. [Hợp đồng](docs/features/CHI_SO_CO_THE.md).

**R14 — Hết gói đã chốt:** KH giữ quyền đọc kế hoạch/lịch sử và ghi nhật ký từ lịch cá nhân hợp lệ đã có; không cấp buổi PT/chatbot mới khi gói hết hạn. Ngoại lệ xác nhận buổi PT đã diễn ra trong hạn theo R07; chat với PT theo phân công ở R21, không phụ thuộc gói. Nhật ký tự tập không tiêu hao buổi PT.

## 6. Chatbot AI — đã chốt D08

Gemini theo hướng CNPM qua Laravel HTTP Client/context database/structured response/validation/fallback. Baseline model cấu hình được, dùng `gemini-3.1-flash-lite` như fallback trong mã tham khảo và xác minh lại khi bootstrap. Chỉ dùng hạn mức API miễn phí, không bật billing/calls có phí; hết quota provider báo bận/fallback rõ và không mất lượt gói. Không đồng nhất quota provider với hạn mức chatbot Admin bán theo gói.

**R15 — Tư vấn có nguồn:** dùng catalog đang bán, tài liệu đã xuất bản, mục tiêu KH cung cấp và lịch mẫu được duyệt. Laravel lọc tài liệu, giới hạn ngữ cảnh, kiểm tra IDs và dựng thẻ gói bằng dữ liệu database hiện tại. AI có thể sai; prompt không thay thế validation.

**R16 — Giới hạn hành động:** chatbot chỉ đọc/tư vấn; không cập nhật gói, tài khoản, thanh toán, kế hoạch, lịch hẹn hay kết quả. Gợi ý lịch mẫu không phải lịch hẹn được đặt. Không tư vấn chẩn đoán/điều trị; câu hỏi vượt phạm vi được hướng sang hỗ trợ phù hợp.

Ngoại lệ C37 ngày04/10/2026 do chủ dự án yêu cầu: chatbot được tạo giáo án **nháp KH tự tạo** từ catalog khi KH yêu cầu rõ và có thông số hợp lệ. Backend kiểm tra bài, quyền và chống trùng; lưu nháp/câu trả lời/thành công trong cùng transaction. Không tự áp dụng hoặc thay giáo án đang dùng, không tạo lịch/phiên tập/đơn thanh toán. KH xem, sửa và áp dụng bằng luồng C31–C34; PT hiện phụ trách chỉ đọc.

**R17 — Quyền dữ liệu:** nguồn dữ liệu cá nhân là tài khoản xác thực ở server. Không dùng user ID do AI/FE đưa để truy vấn tùy ý; không nạp hội thoại PT riêng, dữ liệu người khác hoặc toàn bộ database vào prompt.

**R18 — Lỗi và chi phí:** API key ở BE; hạn request/độ dài/ngữ cảnh/output/timeout. Ghi log provider/model/token khi có, không hardcode giá tiền. Retry có giới hạn và cùng ID nghiệp vụ. Lỗi API dùng phản hồi rõ ràng/FAQ thay thế; không giả câu trả lời đó là AI thành công.

**Quyền chatbot theo gói:** cần gói có quyền chatbot còn hiệu lực; số lượt hỏi mỗi ngày do Admin đặt và lưu snapshot. Backend kiểm tra quyền/hiệu lực/hạn mức trước gọi provider. Một câu trả lời hợp lệ tính một lượt; lỗi dịch vụ/JSON hoặc fallback lỗi không là AI thành công và không mất lượt. Retry cùng request ID không tính lặp. Cấp lại hạn mức lúc 00:00 giờ Việt Nam (`Asia/Ho_Chi_Minh`); bảo vệ hạn mức khi request đồng thời. Rate limit/ngân sách kỹ thuật là lớp riêng. Hết buổi PT vẫn dùng chatbot khi gói còn thời hạn; chatbot không trừ buổi PT. Catalog/FAQ miễn phí, không cấp chatbot miễn phí theo C16.

Chi tiết nguồn, response schema và đánh giá nằm tại [AI_CHATBOT.md](docs/features/AI_CHATBOT.md).

## 7. Chat realtime — đã chốt D07

**R19 — Lưu trước phát sau:** xác thực/quyền → lưu message → commit → broadcast. Database là lịch sử chính; WebSocket chỉ chuyển sự kiện. Lưu timestamp/ID đầy đủ để tải bù sau mất mạng.

**R20 — Chống trùng:** mỗi lần gửi có `client_message_id` ổn định; unique theo hội thoại/người gửi/client ID. Retry không tạo message mới; FE gộp optimistic message, response và event theo IDs.

**R21 — Quyền hội thoại:** hội thoại gắn phân công; được gửi khi phân công còn hiệu lực, kể cả hết gói/hết buổi PT. KH đọc lịch sử của mình; PT cũ mất quyền khi phân công kết thúc, PT mới không đọc chat cũ. Kênh private; server kiểm tra quyền khi đọc/gửi/subscribe/phát sự kiện mới. Thu hồi có tác dụng với kết nối đã mở.

**R22 — Admin và AI:** Admin không mặc định đọc chat riêng; AI không tự lấy chat PT làm ngữ cảnh. Gửi tóm tắt AI cho PT chỉ thực hiện khi KH xem và xác nhận.

**R23 — Tin nhắn:** plain text, giới hạn độ dài/rate, phân trang; lịch sử không phụ thuộc người nhận online. `da_doc_den_tin_nhan_id` tăng đơn điệu và chỉ do người nhận hợp lệ cập nhật. Không xem “đã phát WebSocket” là “đã đọc”.

Chi tiết nằm tại [REALTIME_CHAT.md](docs/features/REALTIME_CHAT.md). Theo D09, giao diện giờ Việt Nam/DB UTC; giữ lịch sử suốt đồ án, không tự purge hoặc xóa khi khóa tài khoản; log không ghi đầy đủ chat/hồ sơ riêng.

## 8. Kiểm thử và định nghĩa hoàn thành

**R24 — Transaction:** thanh toán/cấp gói, đổi PT, giữ/hủy slot, hoàn thành buổi/trừ lượt, áp dụng kế hoạch và hoàn thành phiên cần đánh giá rollback + idempotency. Không giữ transaction/khóa DB suốt lúc gọi API AI.

**R25 — HTTP/API:** tuân thủ [API_CONVENTIONS.md](docs/API_CONVENTIONS.md), không trả 200 để che lỗi quyền/validation. Secrets không xuất hiện trong response/log.

**R26 — Bằng chứng:** module chỉ hoàn thành khi hành vi chính, lỗi và quyền được kiểm thử, tài liệu cập nhật và có bằng chứng. Realtime cần chạy qua Reverb thật; tranh chấp cần MySQL thật; AI cần mock tests và đánh giá live có phân biệt rõ.

**R27 — Không nở phạm vi:** bất kỳ thay đổi actor, quyền lợi, chính sách tiền/lượt, cơ chế áp dụng AI hoặc module lớn phải cập nhật decision và tài liệu liên quan trước implementation. Các số 28 màn hình/28 bảng là baseline đề xuất, không bắt buộc thêm bảng/màn hình chỉ để khớp số.
