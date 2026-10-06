# M08 — Chatbot FitForge AI, tài liệu và FAQ

Ngày kiểm tra: 04/10/2026, Windows, PHP/Laravel13, Vue3 Options API, MariaDB10.4.32. Code triển khai và kiểm thử tự động đã hoàn tất; chất lượng AI thật mới được đánh giá một phần, chưa nghiệm thu toàn bộ 40 câu.

## Giao diện và cách xem

- KH: **FitForge AI** trong menu, `/khach-hang/chatbot`. Có hội thoại riêng, lịch sử phân trang, hạn mức ngày, retry và các thẻ gói/mẫu/bài tập/tài liệu lấy từ database.
- Admin: **Tài liệu & AI**, `/admin/tai-lieu-tu-van`. Soạn nháp FAQ/chính sách/hướng dẫn, sửa có version, xuất bản/ngừng dùng; thống kê tổng hợp hôm nay không chứa chat KH.
- Khách chưa đăng nhập và mọi vai trò: `/faq`, miễn phí; chỉ tài liệu đã xuất bản. Dùng chung header/sidebar theo session của các trang catalog.

Khởi động lại dự án bằng [start.bat](../../start.bat), mở `http://localhost:5173`. Key/model đã cấu hình trên máy này trong `BE/.env`; máy clone cần tự cấu hình `GEMINI_API_KEY`, `GEMINI_MODEL=gemini-3.1-flash-lite` và chạy `php artisan migrate` trong BE. Không commit `.env`. Admin cần có gói đang bán với chatbot và lượt/ngày; KH cần gói đã kích hoạt còn hạn với quyền snapshot để gửi câu hỏi. Chưa mua gói vẫn đọc FAQ/catalog và tự tạo giáo án theo quy tắc hiện có. Không tạo đơn thanh toán hoặc seed gói thật để thử module.

Mascot giữ nguyên GIF robot do chủ dự án gửi, sao chép vào `FE/public/images/fitforge-ai.gif`; PNG tĩnh là khung đầu, không sửa artwork. Có nút tạm dừng/chạy và ưu tiên ảnh tĩnh khi hệ thống chọn reduced motion. Avatar câu trả lời dùng ảnh tĩnh để đọc thuận tiện. Ô nhập gọn, hỗ trợ Enter/Shift+Enter, light/dark và mobile. Checkbox **Dùng dữ liệu tập của tôi** mặc định tắt; phần giải thích dữ liệu gửi Google nằm ngay cạnh ô nhập.

## File/module đã đổi

Backend: `ChatbotController`, `ChatbotRequest`, `ChatbotService`, `GeminiService`, bốn model hội thoại/request/tin nhắn/tài liệu; `TaiLieuTuVanController/Request/Service`; `config/chatbot.php`, API routes và `.env.example`. Migration000041 thêm chống trùng/lần thử/lease/fence/opt-in/prompt version; 000042 thêm UUID/hash tạo tài liệu. Cả hai đã migrate database local, giữ dữ liệu cũ, không fresh/rollback/seed lại. Rollback từ chối khi có lịch sử mới.

Frontend: `views/Chatbot/index.vue`, `views/Admin/TaiLieuTuVan/index.vue`, `views/Faq/index.vue`, `MascotTroLy.vue`, services dùng API client chung, `assets/chatbot.css`, menu/router và `DanhMucLayout.vue`. Không chuyển sang Composition API/TypeScript hoặc thêm thư viện AI vào Vue.

Các kiểm thử mới ở `BE/tests/Feature/ChatbotTest.php`, `FE/tests/chatbot*.spec.js`, `FE/tests/taiLieuTuVan*.spec.js`; worker/fixture/smoke/eval trong `BE/tests/Support/`. Support live chỉ chạy khi có `--live`; không gọi Gemini trong test mặc định.

## Hành vi và quyền

KH chỉ đọc/gửi hội thoại của mình; PT/Admin không có endpoint đọc chat AI. Backend kiểm tra role/tài khoản hoạt động, quyền snapshot, thời hạn và lượt. Một câu đang xử lý/KH; giữ lượt120 giây, tối đa3 lần thử cùng UUID. Replay cùng payload trả kết quả đã lưu, không tính lần hai; khác payload trả409. Khóa KH trong transaction ngắn trước/sau gọi Gemini; không giữ transaction lúc chờ HTTP. Fence chặn worker cũ sau khi lease hết. Lỗi Gemini/schema/ID/DB không ghi thành công hoặc tiêu lượt.

Lượt hoàn tất gắn ngày Việt Nam/gói lúc tiếp nhận; qua 00:00 hoặc hết gói trong lúc chờ không chuyển sang hạn mức mới. Tài khoản bị khóa trong lúc xử lý không nhận câu trả lời. Hết buổi PT nhưng gói còn hạn và có chatbot vẫn hỏi được. FAQ/catalog độc lập với quyền chatbot.

Nguồn là catalog hiện hành, mẫu đã duyệt, bài đang hiển thị, tài liệu đã xuất bản cùng version và quy tắc hệ thống. Nguồn đã ngừng hoặc tài liệu đổi version không hiển thị thẻ cũ như nguồn hiện hành; snapshot lưu lịch sử không bị viết lại. Output phải đúng schema/năm trường, IDs thuộc ứng viên; FE không chạy HTML/URL/action từ AI. Giá gói chỉ lấy từ card database.

Khi bật checkbox, context cá nhân chỉ gồm mục tiêu/kinh nghiệm/giáo án hiện tại/tổng số buổi hoàn thành30 ngày. Không gửi tên/email, hồ sơ người khác hoặc chat PT. Khi tắt, lịch sử câu từng dùng dữ liệu cá nhân bị loại khỏi prompt mới. Đây là tối thiểu hóa dữ liệu, không bảo đảm nội dung KH tự nhập không chứa dữ liệu cá nhân.

Admin tạo tài liệu bằng UUID chống trùng; sửa có version và đưa bản xuất bản về nháp, cần xuất bản lại. Xuất bản/ngừng kiểm tra version/state và retry hữu hạn. Không có xóa lịch sử. Thống kê chỉ tổng số yêu cầu/thành công/lỗi/token theo ngày Việt Nam, không lộ nội dung hoặc IDs KH. Token của phản hồi provider không hợp lệ có thể không được ghi vào thống kê; số token này không dùng để tính tiền hoặc chứng minh chi phí Google.

## Kiểm thử thực sự chạy

| Kiểm tra | Kết quả |
| --- | --- |
| Toàn bộ Backend | 216 tests, 5.772 assertions PASS; MariaDB10.4.32, database test tách riêng |
| M08 Backend sau cập nhật prompt v2 | 21 tests, 189 assertions PASS |
| Toàn bộ Frontend sau sửa cuối | 22 files, 193 tests PASS |
| FE lint, build, format | PASS |
| PHP Pint dirty | PASS |
| Browser KH/Admin/khách | Đã kiểm tra gửi thật, lịch sử, dừng mascot, nguồn, tạo nháp/xuất bản, FAQ và thống kê |
| Browser responsive | 375px/768px không tràn ngang; lịch sử mobile thu gọn; light/dark |

API tests bao gồm role/ownership/revocation, snapshot/hạn gói, lỗi429/timeout/JSON/schema/nguồn, UUID/retry, đổi ngày/gói trong lúc xử lý, lease hết chính xác đến microsecond, rollback ghi assistant, rút nguồn/version tài liệu, publicFAQ không lộ nháp, aggregate không lộ chat. Hai PHP process thực sự tranh lượt cuối trên MariaDB, không chỉ Event fake/SQLite. Test dùng Http fake và chặn request ngoài dự kiến. Không nghiệm thu MySQL8 hoặc production/load ở phiên này.

## Gemini thật và đánh giá nội dung

Key chủ dự án cung cấp được lưu local BE, model `gemini-3.1-flash-lite`. Smoke gọi dữ liệu giả lập nhận HTTP200, structured response hợp lệ; 310 input/111 output tokens. PHP ban đầu thiếu CA mặc định; provider dùng bundle sẵn có `BE/resources/certs/cacert.pem`, giữ xác minh TLS, không tắt certificate validation.

[Bộ40 câu và rubric](../../docs/verification/m08-cau-hoi.json) bao gồm gói/quota, giáo án tự tạo/PT, lịch, dữ liệu thiếu, phạm vi và trái quyền. [Kết quả live](../../docs/verification/m08-live-eval.json) dùng database QA giả lập riêng, prompt `tr0ond-v1`, không gửi hồ sơ hay chat khách thật: 18 câu đã thử, 17 phản hồi qua kiểm tra schema/IDs và được lưu; câu18 HTTP429 không mất lượt, dừng toàn đợt; 22 câu chưa chạy. **17 phản hồi hợp lệ kỹ thuật không đồng nghĩa 17 câu đúng nghiệp vụ.**

Rà nội dung thấy câu hỏi ngừng giáo án PT hướng dẫn liên hệ PT thay vì thao tác KH, câu PT sửa giáo án tự tạo thiếu kết luận rõ, và câu chat PT bị trộn với chatbot. Một số câu lộ cách diễn đạt kỹ thuật như ID tài liệu. Đã bổ sung context quy tắc C31–C34 và phân biệt hai loại chat, sửa prompt thành `tr0ond-v2`. Sau sửa, kiểm tra qua UI với câu **PT có thể sửa giáo án tôi tự tạo không?** nhận phản hồi đúng: PT hiện tại chỉ xem, không sửa/áp dụng thay KH; lượt42→41/60 trên gói QA. Văn bản vẫn có nhắc “tài liệu ID1”, cần tiếp tục cải thiện cách diễn đạt. Không tuyên bố v2 đã đạt toàn bộ rubric40 câu; không tự retry hàng loạt khi429 hoặc chuyển provider trả phí.

HTTP200 không chứng minh tài khoản Google còn bao nhiêu quota hoặc đang bật/tắt billing; chỉ dùng key được cung cấp cho yêu cầu này, không đổi cấu hình billing. Hạn mức ứng dụng và rate/quota Gemini là hai lớp khác nhau. Không gửi yêu cầu AI tự áp dụng giáo án, đặt lịch hay thanh toán.

## Ảnh kiểm chứng

- [KH light](../../docs/verification/m08-chatbot-light.png), [KH dark](../../docs/verification/m08-chatbot-dark.png), [mobile](../../docs/verification/m08-chatbot-mobile.png).
- [Mascot chào mừng](../../docs/verification/m08-mascot-welcome.png).
- [Câu trả lời sau cập nhật v2](../../docs/verification/m08-chatbot-v2.png).
- [Admin tài liệu & AI](../../docs/verification/m08-admin-tai-lieu.png), [FAQ công khai](../../docs/verification/m08-faq-cong-khai.png).

QA dùng ports8017/5291 và database tên riêng có guard, không thay server5173/8000 hoặc dữ liệu khách thật. Sau bàn giao dừng các server QA, đóng tab QA và xóa đúng database giả lập đã tạo; giữ server/tab người dùng.
