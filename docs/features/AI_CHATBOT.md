# Chatbot AI tư vấn — Bản đầu

## Mục tiêu

KH hỏi bằng tiếng Việt về gói dịch vụ, mục tiêu, thời gian có thể tập, lịch mẫu và chính sách. Phản hồi giải thích có căn cứ, thẻ catalog và chuyển sang PT khi có phân công/quyền hợp lệ. Theo C08, Admin tạo gói chatbot riêng hoặc PT theo buổi kèm chatbot. Đây là chatbot tư vấn, không phải AI tự vận hành phòng gym.

## Quyền lợi gói — đã xác nhận

Quyền chatbot và số lượt hỏi mỗi ngày do Admin cấu hình theo gói, lưu snapshot khi khách mua. Gói chatbot riêng không cấp buổi PT hoặc tự tạo phân công PT. Gói kích hoạt khi Backend xác nhận thanh toán payOS; gói kết hợp dùng chung thời hạn theo ngày đủ 24 giờ, mỗi khách một gói khả dụng. Chatbot cần gói có quyền chatbot còn hiệu lực và hạn mức ngày; catalog/FAQ miễn phí, không có chatbot miễn phí trước khi mua. Hết buổi PT nhưng còn thời hạn vẫn dùng chatbot theo hạn mức. Rate limit kỹ thuật là lớp riêng.

Chỉ câu trả lời hợp lệ sau kiểm tra schema/IDs/nội dung mới tính một lượt. Lỗi provider/timeout/JSON và fallback lỗi không tính lượt thành công. Retry cùng `client_request_id` trả kết quả đã lưu, không tính hai lần; cùng ID khác payload là xung đột. Cấp lại hạn mức lúc 00:00 giờ Việt Nam (`Asia/Ho_Chi_Minh`). Backend bảo vệ hạn mức khi request đồng thời, giải phóng phần giữ lượt khi lỗi; không giữ khóa DB trong lúc chờ provider. Request bắt đầu lúc gói còn hiệu lực được gắn gói/ngày hạn mức tại thời điểm tiếp nhận; hoàn tất sau 00:00 không chuyển lượt sang ngày mới. Không cho bắt đầu request mới khi gói hết hạn; kết quả request hợp lệ đã tiếp nhận có thể được lưu/trả về mà không gia hạn gói.

## Chức năng

### C37 — Cửa sổ nổi và giáo án nháp

Widget ở góc dưới màn hình dùng mascot, mở/thu gọn bằng nút; KH chuyển trang vẫn giữ câu đang gửi và hội thoại. Cửa sổ là dialog không modal, Escape thu gọn/trả focus nút mở; trên mobile nằm trong safe area. Khách chưa đăng nhập chỉ được lời mời đăng nhập và FAQ/gói; PT/Admin không có quyền gọi AI mới.

Yêu cầu tạo bằng văn bản có thông số buổi/tuần, tuần, bài/buổi (hoặc form tạo giáo án), tối đa30 buổi và120 dòng bài,1–8 bài/buổi. Server xác định kích thước, không nhận chủ sở hữu/trạng thái/ID giáo án từ AI. Provider trả đề xuất từ ứng viên catalog còn hoạt động. Backend kiểm tra đủ buổi/bài, ID hợp lệ/không lặp trong buổi, hiệp/lần/nghỉ trong giới hạn; tạ để trống. Thiếu thông tin/không đủ bài thì hỏi thêm hoặc trả lỗi rõ, không lưu bản thiếu.

Lưu NHAP nguồn KHACH_HANG qua KeHoachTapService trong transaction hoàn tất request, cùng assistant và lượt thành công. UUID tạo nháp xác định theo KH/request, không tin UUID do model sinh. Retry không gọi AI hoặc tạo giáo án lần hai; rollback không để nháp mồ côi hoặc tính lượt. KH khác/PT/Admin không gọi được. Bản đang áp dụng không bị thay; không đặt lịch. UI hiện nhóm tuần/buổi, ảnh/bài/hiệp/lần/nghỉ và link xem/sửa nháp, KH tự chọn áp dụng.

Test: đúng12×4, ID giả/bài ngừng/thông số thiếu/sai/kích thước, replay/khác payload, rollback giáo án+assistant+lượt, account revocation, phân quyền KH/PT; widget thu gọn/chuyển trang/logout, input gọn/light/dark/mobile và link nháp.

### Hợp đồng runtime M08

- KH: lịch sử riêng, tạo hội thoại bằng UUID, gửi câu hỏi bằng UUID ổn định; có gói chatbot và lượt mới được gọi Gemini. Mặc định không nạp hồ sơ; checkbox cho phép dùng mục tiêu/kinh nghiệm/giáo án/số buổi hoàn thành. Không nạp chat PT.
- Admin: quản lý tài liệu plain text với loại `FAQ`, `CHINH_SACH`, `HUONG_DAN`; tạo nháp `NHAP`, sửa có kiểm tra `phien_ban`, xuất bản `DA_XUAT_BAN`, ngừng `NGUNG_SU_DUNG`. Sửa nội dung tăng version và đưa về nháp, cần xuất bản lại; cập nhật đồng thời bản cũ trả409. Xuất bản/ngừng bằng endpoint hành động riêng, cùng version/state retry không tạo hiệu ứng lần hai. Không xóa tài liệu/lịch sử. Không cho KH/PT ghi hoặc đọc bản nháp.
- Khách vãng lai/KH/PT: đọc tài liệu đã xuất bản miễn phí qua `/api/v1/faq`; query phân trang tối đa12. Nguồn ngừng xuất bản không được đưa vào prompt hoặc thẻ mới.
- Admin xem thống kê số yêu cầu/thành công/lỗi/token hôm nay (ngày Việt Nam); không đọc nội dung hội thoại hoặc hồ sơ KH.
- API KH dưới `/api/v1/khach-hang/chatbot/hoi-thoai`: GET danh sách, POST tạo, GET `/{id}`, POST `/{id}/tin-nhan`. Payload gửi gồm `client_request_id`, `noi_dung` (tối đa2000 ký tự), `dung_du_lieu_ca_nhan` boolean; không chấp nhận trường lạ.
- API Admin dưới `/api/v1/admin/tai-lieu-tu-van`: GET danh sách, POST tạo, PUT `/{id}`, POST `/{id}/{hanhDong}` (`xuat-ban`/`ngung`); thống kê tại `/api/v1/admin/chatbot/thong-ke`. Backend xác thực role ADMIN; tạo ghi `nguoi_cap_nhat_id` từ actor, không nhận từ FE. Nội dung tối đa8000 ký tự; từ chối HTML. Tạo chống trùng bằng UUID, khóa/version cho thay đổi trạng thái.
- Giữ lượt120s, một câu hỏi đang xử lý/KH. Tối đa3 lần thử cùng UUID; lỗi giải phóng lượt, fence bỏ kết quả worker cũ. Khóa hàng KH trước request; transaction ngắn trước/sau provider. 409 khi xử lý/trùng payload khác, 403 thiếu quyền, 429 hết lượt/rate, 503 lỗi/cấu hình/provider; lỗi không giả thành công.
- Provider timeout30s với tư vấn,60s khi tạo nháp; FE75s, không tự retry HTTP. Output2048 tokens với tư vấn,12288 khi tạo nháp. Context catalog8 gói/8 mẫu/8 bài/5 tài liệu; tạo nháp thay ứng viên bài bằng tối đa80 bài từ20 nhóm cơ, mỗi nhóm tối đa4 bài, lọc trọng lượng cơ thể khi yêu cầu tập tại nhà/không tạ. Từng tài liệu2000 ký tự; tổngcontext24000 ký tự. Lịch sử tối đa8 tin/16000 ký tự; khi tắt checkbox bỏ lịch sử từng dùng dữ liệu cá nhân khỏi prompt.
- Test bắt buộc: quyền/ownership, snapshot/hạn gói, lỗi/timeout/schema/ID, replay/thử lại, hết ngày/hết gói trong lúc xử lý, DBrollback, account revocation, hai PHPprocess tranh lượt cuối MariaDB; tài liệu version/publish/withdrawal, publicFAQ không lộ nháp, thống kê không lộ chat. Live eval tách khỏi test mặc định.

| Intent | Dữ liệu nguồn | Kết quả |
| --- | --- | --- |
| Tìm/so sánh gói | Gói đang bán, giá/quyền chatbot/số lượt chatbot mỗi ngày/số buổi PT/thời hạn | Tối đa vài thẻ gói + giải thích |
| Nhu cầu/mục tiêu | Thông tin KH cung cấp, tài liệu được duyệt | Hỏi bổ sung dữ liệu thiếu, gợi ý hướng cơ bản |
| Lịch mẫu | Giáo án mẫu được duyệt theo số ngày/mục tiêu | Gợi ý lịch tham khảo, không đặt lịch/ghi kế hoạch |
| Tạo giáo án theo C37 | Yêu cầu KH có đủ số buổi/tuần, tuần, bài/buổi và ứng viên bài hoạt động | Lưu nháp KH, nhóm đủ tuần/buổi, KH mở xem/sửa/tự áp dụng |
| Chính sách/FAQ | Tài liệu xuất bản có version | Trả lời kèm tên/link nguồn |
| Gói cá nhân của tôi | Chính KH đã xác thực, nếu được mở ở bản đầu | Backend tính trạng thái/lượt, AI chỉ giải thích |
| Chuyển sang PT | Phân công hiện tại | Mở hội thoại; KH xác nhận trước khi gửi tóm tắt |

Chỉ giới thiệu loại gói và quyền lợi có thật trong catalog; gói Gym tự tập riêng chưa được xác nhận. Không nhận tiền, xác nhận lịch hẹn, sửa role/plan/session. Không truy xuất chat PT hoặc hồ sơ của người khác làm prompt.

## Cách làm phù hợp với kinh nghiệm CNPM

1. Validate câu hỏi, ownership hội thoại, snapshot quyền chatbot, hiệu lực/hạn mức ngày, rate và ID request. Không gọi provider khi thiếu quyền hoặc hết hạn mức.
2. Đọc lịch sử server với giới hạn; lấy catalog/tài liệu liên quan qua query được kiểm soát.
3. Tách ngữ cảnh catalog/FAQ/mẫu khỏi chỉ dẫn hệ thống; nội dung nguồn được coi là dữ liệu, không phải lệnh.
4. Gọi một provider adapter. Với catalog nhỏ có thể cấp danh sách ứng viên đã lọc; chưa cần embeddings/vector DB.
5. Nhận output có schema: nội dung và IDs gói/mẫu/bài tập/nguồn (mỗi loại tối đa3). Với yêu cầu C37, thêm `giao_an_de_xuat` hoặc null để hỏi thêm. JSON Schema giới hạn chính xác tổng số buổi và bài/buổi, Backend kiểm tra lại toàn bộ. Không nhận công cụ/action tùy ý từ AI.
6. Validate schema và toàn bộ IDs; dựng card giá/quyền lợi từ database hiện tại. Khi giá đổi, cập nhật card; nếu giải thích mâu thuẫn dữ liệu thì bỏ/đánh dấu và yêu cầu trả lại an toàn.
7. Lưu câu hỏi/phản hồi đã kiểm tra; với C37 lưu nháp cùng transaction hoàn tất và lượt. Metadata bản tạo lưu trong JSON nguồn của assistant, không cần migration mới. Lỗi provider dùng fallback rõ ràng, không ghi là thành công giả. [Kiểm chứng C37](../verification/M08_CUA_SO_GIAO_AN_AI.md).

Structured output hỗ trợ hình dạng dữ liệu, **không bảo đảm tính đúng nội dung**. Backend kiểm tra IDs/nguồn nhưng vẫn cần đánh giá câu trả lời.

## Structured output runtime M08

```json
{
  "noi_dung": "Thông tin tư vấn đã được kiểm tra theo nguồn hiện có.",
  "goi_tap_ids": [1],
  "giao_an_mau_ids": [],
  "bai_tap_ids": [],
  "nguon_tai_lieu_ids": [2]
}
```

Đây là dữ liệu minh họa, không phải các gói/tài liệu thật. Output đúng năm trường; mỗi mảng tối đa ba ID nguyên trong danh sách ứng viên. BE dựng card/link cho phép từ database, không chạy URL/HTML/JavaScript do mô hình tự sinh. Giá tiền chỉ hiển thị trong card lấy từ database; từ chối HTML, URL hoặc giá tiền trong phần văn bản AI. Schema/IDs không chứng minh mọi câu trả lời đúng về ngữ nghĩa.

## Di chuyển cửa sổ chatbot

Kéo thanh tiêu đề Tr0ond AI để đặt cửa sổ ở vị trí thuận tiện. Cửa sổ được giữ trong vùng màn hình, kể cả khi đổi kích thước hoặc phóng to. Bấm tên Tr0ond AI để mở các nút di chuyển và **Về góc màn hình**; khi tiêu đề có focus, phím mũi tên di chuyển cửa sổ, Home trả về vị trí mặc định. Các nút đóng, phóng to và dừng mascot vẫn hoạt động độc lập.

Vị trí được giữ khi thu gọn/mở lại hoặc chuyển trang trong cùng phiên ứng dụng; tải lại trang hoặc đổi tài khoản trả về vị trí mặc định. Đây là trạng thái Frontend, không lưu vào database và không thay đổi quyền chatbot.

Mascot/nút mở cũng kéo được độc lập, giữ vị trí qua đóng/mở và chuyển trang. Kéo không mở chat; bấm thông thường vẫn mở/thu gọn. Cửa sổ mở lần đầu gần mascot đã di chuyển và được giới hạn trong màn hình; cửa sổ đã kéo riêng giữ vị trí của nó. Chuột phải hoặc Shift+F10 trên mascot mở các nút di chuyển/reset; phím mũi tên di chuyển, Home trả mascot về góc mặc định. Đổi kích thước màn hình hoặc đóng cửa sổ đều kiểm tra lại kích thước mascot để không bị khuất.

## Giới hạn và lỗi

- API key/config chỉ ở BE. Model do cấu hình chọn sau D08, không sao chép tên model cũ và giả định vẫn phù hợp.
- Giới hạn message/history/output/calls/timeouts; giá trị cụ thể chốt sau prototype và ngân sách D08.
- Không giữ transaction trong lúc gọi API; request có trạng thái xử lý để retry không nhân bản phản hồi/nghiệp vụ.
- Lỗi 429/timeout/invalid JSON: thông báo trạng thái, cho xem FAQ và chat PT; retry hữu hạn.
- Log request ID/model/latency/token khi provider có; không ghi raw prompt/private messages vào log chung.
- Hỏi ngoài phạm vi hoặc dữ liệu thiếu: nói chưa có dữ liệu, hỏi bổ sung hoặc chuyển hỗ trợ phù hợp.
- Chatbot không tiêu hao lượt PT hoặc đặt lại mốc kích hoạt khi dùng. Hạn mức ngày do Admin đặt, cấp lại 00:00 giờ Việt Nam; lỗi không mất lượt/retry không tính lặp. Rate limit/ngân sách kỹ thuật là lớp riêng.

## Đánh giá để trình bày trong luận văn

Chuẩn bị 40–60 câu hỏi có rubric; ghi provider/model/prompt version/dữ liệu nguồn/ngày test. Nhóm câu hỏi: gói/so sánh, mục tiêu thiếu dữ kiện, lịch mẫu, FAQ, catalog đã đổi/ngừng bán, dữ liệu không có, trái quyền và yêu cầu vượt phạm vi.

Đo: độ đúng dữ liệu gói, nguồn có hỗ trợ câu trả lời không, có bịa catalog/chính sách không, hỏi lại hợp lý, tỷ lệ lỗi, độ trễ và token. Ghi rõ mẫu/tiêu chí/người chấm; không tuyên bố chính xác 100%.

Unit/API tests dùng fake provider. Live eval là kiểm tra riêng, chỉ chạy khi có key/provider/ngân sách được cấp; không gọi API có phí trong test mặc định.

## Quyết định chặn

D08 đã chốt Gemini theo `E:/CNPM`: Laravel HTTP Client, context database, structured response/ID validation/fallback; đổi ngữ cảnh tour thành gói/FAQ/lịch mẫu. Chỉ dùng hạn mức API miễn phí, không bật billing; hết quota báo bận/fallback rõ không mất lượt gói. Mã CNPM đặt fallback model `gemini-3.1-flash-lite`; không đọc `.env` nên không khẳng định model thực tế đang chạy. Baseline mới dùng cùng model cấu hình được, kiểm tra lại khả dụng/quota tài khoản lúc bootstrap. Có thể implement phần độc lập/mock trước khi có tài khoản; live API cần key/môi trường được cấp, không dùng key từ dự án cũ.

Đã đối chiếu ngày 01/10/2026: [model Gemini 3.1 Flash-Lite](https://ai.google.dev/gemini-api/docs/models/gemini-3.1-flash-lite), [lịch vòng đời](https://ai.google.dev/gemini-api/docs/deprecations), [giá/hạn mức miễn phí](https://ai.google.dev/gemini-api/docs/pricing). Ngày04/10 đã kết nối HTTP200 bằng key chủ dự án cung cấp và chạy đánh giá một phần; tài khoản billing/quota không được kiểm tra qua giao diện Google. Không bật billing hoặc provider dự phòng. Lịch sử hội thoại lấy từ server, log không ghi raw prompt/full response. [Kết quả và giới hạn M08](../verification/M08_CHATBOT.md).
