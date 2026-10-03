# Bảng xem nhanh tin nhắn — 03/10/2026

## Thay đổi

- `FE/src/components/ThongBaoHeader.vue`: biểu tượng chat KH/PT là nút mở bảng xem nhanh, thay cho điều hướng trực tiếp. Bảng có tên/chữ cái đại diện, tin cuối được rút gọn, thời gian theo giờ Việt Nam, số chưa đọc và nhãn phân công cũ. Bấm hội thoại mở đúng ID, “Xem tất cả tin nhắn” mở trang danh sách. Chuông và chat thay nhau mở; Escape trả focus về đúng nút, bấm ngoài hoặc Tab rời bảng thì đóng.
- `FE/src/stores/chat.js`: dùng kết quả API hội thoại đang gọi để giữ tối đa sáu hội thoại mới nhất. Số badge vẫn là tổng mọi trang, không cộng chỉ sáu dòng. Đồng bộ qua cùng cơ chế Reverb/HTTP; xóa preview khi logout/đổi tài khoản/hết phiên/lỗi tải. Không thêm socket chứa nội dung hoặc API mới.
- `FE/tests/thongBao.spec.js`, `FE/tests/chat.spec.js`: kiểm tra KH/PT/Admin, không điều hướng/đánh dấu đọc khi mở preview, đúng liên kết hội thoại, escape văn bản, giới hạn sáu dòng, bỏ phản hồi phiên cũ, lỗi và phục hồi.
- Cập nhật `docs/features/REALTIME_CHAT.md`, `docs/features/NOTIFICATIONS.md`, `README.md`, `FE/README.md` theo hành vi mới.

Không thay đổi Backend, database hoặc chính sách đánh dấu đọc. Chỉ tải/xem preview chưa được coi là đã đọc; cursor vẫn do màn hình chat cập nhật theo R23. Chưa có dữ liệu ảnh/presence trong API nên dùng chữ cái đại diện và không gắn trạng thái online giả.

## Đã chạy

Môi trường Windows, Node 22, Vue 3 Options API, Vite 8.3.1:

| Kiểm tra | Kết quả |
| --- | --- |
| `npm test` | 132 tests / 13 files PASS |
| `npm run build` | PASS, 185 modules |
| `npm run lint:check` | PASS |
| `npm run format:check` | PASS |
| `git diff --check` | PASS |

Backend không sửa trong phiên này nên không chạy lại toàn bộ kiểm thử PHP; kết quả phiên Backend trước là 137 tests / 4.723 assertions trên MariaDB 10.4.32.

## Trình duyệt

Fixture riêng `kiem_tra_chat_ui_3e2e6a4d0085a841`, FE 5280/5281, BE 8011/8012, Reverb 8082:

- KH bấm biểu tượng vẫn ở `/khach-hang/tong-quan`, bảng hiện PT, tin cuối và 28 chưa đọc; mở nhiều lần vẫn 28.
- Bấm dòng PT mở `/khach-hang/tin-nhan/1`; đọc trong trang chat mới giảm badge về 0.
- “Xem tất cả tin nhắn” mở `/khach-hang/tin-nhan` và đóng bảng.
- PT bấm biểu tượng ở trang tổng quan mở preview KH; bấm KH mở `/pt/tin-nhan/1` với đúng tên người nhận.
- Bấm chuông đóng chat và ngược lại; Escape trả focus về biểu tượng chat; Tab đi qua dòng hội thoại/footer, ra nút chủ đề thì đóng bảng.
- Dark/Light desktop mặc định và viewport 390×844: preview/tên dài được rút gọn, bảng nằm trong màn hình, không tràn toàn trang.
- Tạm dừng riêng HTTP QA 8011: bảng hiện lỗi và “Thử lại”, không hiện lại nội dung cũ. Phục hồi server: đồng bộ HTTP định kỳ khôi phục preview.

Ảnh sử dụng dữ liệu mẫu, không phải tài khoản khách hàng thật:

- [KH chế độ sáng](header-message-preview-light.png)
- [KH chế độ tối](header-message-preview-dark.png)
- [Điện thoại](header-message-preview-mobile.png)
- [PT](header-message-preview-pt.png)
- [Lỗi tải](header-message-preview-error.png)

Đã đóng tab QA, dừng server QA và xóa đúng database fixture. Không reset database ứng dụng.

## Xem kết quả

Tải lại localhost:5173, đăng nhập KH/PT, bấm biểu tượng tin nhắn trên header. Nếu chưa có phân công/hội thoại, bảng có trạng thái trống; không seed hội thoại giả vào ứng dụng. Danh sách xem nhanh giới hạn sáu dòng; xem đầy đủ ở “Xem tất cả tin nhắn”.
