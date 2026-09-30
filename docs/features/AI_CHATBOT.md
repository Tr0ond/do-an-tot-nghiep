# Chatbot AI tư vấn — Bản đầu

## Mục tiêu

KH hỏi bằng tiếng Việt về gói dịch vụ, mục tiêu, thời gian có thể tập, lịch mẫu và chính sách. Phản hồi giải thích có căn cứ, thẻ catalog và chuyển sang PT khi có phân công/quyền hợp lệ. Theo C08, Admin tạo gói chatbot riêng hoặc PT theo buổi kèm chatbot. Đây là chatbot tư vấn, không phải AI tự vận hành phòng gym.

## Quyền lợi gói — đã xác nhận

Quyền chatbot và số lượt hỏi mỗi ngày do Admin cấu hình theo gói, lưu snapshot khi khách mua. Gói chatbot riêng không cấp buổi PT hoặc tự tạo phân công PT. Gói kích hoạt khi Backend xác nhận thanh toán payOS; gói kết hợp dùng chung thời hạn theo ngày đủ 24 giờ, mỗi khách một gói khả dụng. Chatbot cần gói có quyền chatbot còn hiệu lực và hạn mức ngày; catalog/FAQ miễn phí, không có chatbot miễn phí trước khi mua. Hết buổi PT nhưng còn thời hạn vẫn dùng chatbot theo hạn mức. Rate limit kỹ thuật là lớp riêng.

Chỉ câu trả lời hợp lệ sau kiểm tra schema/IDs/nội dung mới tính một lượt. Lỗi provider/timeout/JSON và fallback lỗi không tính lượt thành công. Retry cùng `client_request_id` trả kết quả đã lưu, không tính hai lần; cùng ID khác payload là xung đột. Cấp lại hạn mức lúc 00:00 giờ Việt Nam (`Asia/Ho_Chi_Minh`). Backend bảo vệ hạn mức khi request đồng thời, giải phóng phần giữ lượt khi lỗi; không giữ khóa DB trong lúc chờ provider. Request bắt đầu lúc gói còn hiệu lực được gắn gói/ngày hạn mức tại thời điểm tiếp nhận; hoàn tất sau 00:00 không chuyển lượt sang ngày mới. Không cho bắt đầu request mới khi gói hết hạn; kết quả request hợp lệ đã tiếp nhận có thể được lưu/trả về mà không gia hạn gói.

## Chức năng

| Intent | Dữ liệu nguồn | Kết quả |
| --- | --- | --- |
| Tìm/so sánh gói | Gói đang bán, giá/quyền chatbot/số lượt chatbot mỗi ngày/số buổi PT/thời hạn | Tối đa vài thẻ gói + giải thích |
| Nhu cầu/mục tiêu | Thông tin KH cung cấp, tài liệu được duyệt | Hỏi bổ sung dữ liệu thiếu, gợi ý hướng cơ bản |
| Lịch mẫu | Giáo án mẫu được duyệt theo số ngày/mục tiêu | Gợi ý lịch tham khảo, không đặt lịch/ghi kế hoạch |
| Chính sách/FAQ | Tài liệu xuất bản có version | Trả lời kèm tên/link nguồn |
| Gói cá nhân của tôi | Chính KH đã xác thực, nếu được mở ở bản đầu | Backend tính trạng thái/lượt, AI chỉ giải thích |
| Chuyển sang PT | Phân công hiện tại | Mở hội thoại; KH xác nhận trước khi gửi tóm tắt |

Chỉ giới thiệu loại gói và quyền lợi có thật trong catalog; gói Gym tự tập riêng chưa được xác nhận. Không nhận tiền, xác nhận lịch hẹn, sửa role/plan/session. Không truy xuất chat PT hoặc hồ sơ của người khác làm prompt.

## Cách làm phù hợp với kinh nghiệm CNPM

1. Validate câu hỏi, ownership hội thoại, snapshot quyền chatbot, hiệu lực/hạn mức ngày, rate và ID request. Không gọi provider khi thiếu quyền hoặc hết hạn mức.
2. Đọc lịch sử server với giới hạn; lấy catalog/tài liệu liên quan qua query được kiểm soát.
3. Tách ngữ cảnh catalog/FAQ/mẫu khỏi chỉ dẫn hệ thống; nội dung nguồn được coi là dữ liệu, không phải lệnh.
4. Gọi một provider adapter. Với catalog nhỏ có thể cấp danh sách ứng viên đã lọc; chưa cần embeddings/vector DB.
5. Nhận output có schema: nội dung, gói/mẫu/nguồn IDs, action enum.
6. Validate schema và toàn bộ IDs; dựng card giá/quyền lợi từ database hiện tại. Khi giá đổi, cập nhật card; nếu giải thích mâu thuẫn dữ liệu thì bỏ/đánh dấu và yêu cầu trả lại an toàn.
7. Lưu câu hỏi/phản hồi đã kiểm tra; trả UI. Lỗi provider dùng fallback rõ ràng, không ghi là thành công giả.

Structured output hỗ trợ hình dạng dữ liệu, **không bảo đảm tính đúng nội dung**. Backend kiểm tra IDs/nguồn nhưng vẫn cần đánh giá câu trả lời.

## Response tham khảo

```json
{
  "noi_dung": "Thông tin tư vấn đã được kiểm tra theo nguồn hiện có.",
  "goi_tap_ids": [1],
  "giao_an_mau_ids": [],
  "nguon_tai_lieu_ids": [2],
  "hanh_dong": ["XEM_GOI", "MO_CHAT_PT"]
}
```

Đây là dữ liệu minh họa, không phải các gói/tài liệu thật. BE ánh xạ action sang route cho phép; không chạy URL/HTML/JavaScript do mô hình tự sinh.

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

Đã đối chiếu ngày 01/10/2026: [model Gemini 3.1 Flash-Lite](https://ai.google.dev/gemini-api/docs/models/gemini-3.1-flash-lite), [lịch vòng đời](https://ai.google.dev/gemini-api/docs/deprecations), [giá/hạn mức miễn phí](https://ai.google.dev/gemini-api/docs/pricing). Chưa gọi API hoặc xác minh quota của tài khoản. Lịch sử hội thoại mới lấy từ server, log không ghi raw prompt/full response; không sao chép hai điểm này từ controller tham khảo.
