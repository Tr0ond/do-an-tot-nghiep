# Kiểm chứng menu dọc — 03/10/2026

## Thay đổi

- `FE/src/layouts/CaNhanLayout.vue`: thay menu ngang bằng thanh bên cố định; header một hàng có nút mở/thu gọn menu, đổi theme, chuông, tin nhắn KH/PT và avatar. Hồ sơ/đăng xuất chuyển vào bảng avatar, lỗi đăng xuất vẫn được báo và cho phép thử lại.
- `FE/src/components/MenuCaNhan.vue`: menu riêng KH/PT/Admin, biểu tượng và nhãn truy cập khi thu gọn; mục cha giữ trạng thái ở trang chi tiết, Lịch hẹn KH cũng được chọn trên trang đặt lịch.
- `FE/src/stores/dieuHuong.js`: Pinia Options Store giữ tùy chọn mở rộng/thu gọn qua chuyển trang và refresh; localStorage chỉ chứa tùy chọn giao diện. Khi trình duyệt chặn lưu trữ vẫn dùng menu được.
- `FE/src/components/ThongBaoHeader.vue`: phát sự kiện mở bảng để đóng menu avatar; dữ liệu và hành vi xem nhanh/đánh dấu đọc giữ nguyên.
- Desktop mở rộng 264px, thu gọn 84px. Dưới 1024px dùng native modal dialog: nền bên ngoài không tương tác, giữ điều hướng bàn phím trong modal, Escape/nút đóng/bấm nền/chọn mục để đóng; trả focus về nút mở khi layout còn tồn tại. Cuộn nền bị khóa khi menu mở; menu có vùng cuộn riêng.
- Danh mục bài tập/gói tập trong phiên đăng nhập vẫn dùng CaNhanLayout; khách chưa đăng nhập tiếp tục dùng giao diện công khai.

## Kiểm tra đã chạy

Môi trường: Windows, Node/Vite/Vue hiện có của dự án; browser IAB. Backend QA dùng MariaDB và database riêng `kiem_tra_chat_ui_9a543cdd10b6a3a5`; không thêm dữ liệu mẫu vào database ứng dụng.

- `npm test`: **141 tests / 14 files PASS**. Bổ sung kiểm tra menu theo vai trò, nhãn thu gọn, khớp route chi tiết/đặt lịch, tránh khớp tiền tố sai, lưu tùy chọn và lỗi storage, đóng drawer/trả focus, lỗi đăng xuất. Điều chỉnh kiểm tra active menu trong chi tiết đơn và sự kiện mở bảng tin nhắn.
- `npm run lint:check`, `npm run format:check`, `npm run build`: PASS; build 188 modules.
- `git diff --check`: PASS.
- Browser desktop 1440px: KH/PT/Admin đúng menu; sáng/tối; header không còn tên tài khoản dài; thu gọn còn icon và nhãn; refresh rồi mở thư viện vẫn giữ rail 84px, đúng mục active.
- Browser tablet 768px và mobile 390px: KH mở/đóng drawer, chọn Tổng quan tự đóng; Escape trả focus về nút mở; header giữ icon cùng hàng; không tràn ngang.
- Mobile Admin: đủ mười mục, có vùng cuộn, không tràn ngang.
- Bảng xem nhanh tin nhắn KH trên thư viện và mobile; PT chọn hội thoại mở `/pt/tin-nhan/1`, trang chat có đủ vùng danh sách/nội dung trong bố cục mới.
- Chuông Admin tải thông báo; mở avatar đóng chuông, hồ sơ trỏ `/admin/ho-so`; Escape đóng avatar. KH đăng xuất qua avatar thành công về trang đăng nhập.
- Console KH/PT/Admin: không có error/warn trong lượt kiểm tra.

## Ảnh

- [KH tối, mở rộng](../../docs/verification/sidebar-kh-dark.png)
- [Thu gọn](../../docs/verification/sidebar-collapsed.png)
- [Avatar và giao diện sáng](../../docs/verification/sidebar-avatar.png)
- [Thư viện và bảng tin nhắn](../../docs/verification/sidebar-catalog-chat.png)
- [PT](../../docs/verification/sidebar-pt.png)
- [Admin](../../docs/verification/sidebar-admin.png)
- [Header mobile](../../docs/verification/sidebar-mobile.png)
- [Menu mobile](../../docs/verification/sidebar-mobile-menu.png)
- [Tin nhắn mobile](../../docs/verification/sidebar-mobile-chat.png)
- [Menu Admin mobile](../../docs/verification/sidebar-admin-mobile.png)

## Giới hạn và cách xem

Thay đổi Frontend, không sửa API/quyền Backend, không thêm migration hay dependency. Admin vẫn không có chat riêng theo C21/D07/R22. Không chạy lại PHPUnit vì lần này không sửa Backend.

Chạy `start.bat` như hiện tại rồi tải lại trang và đăng nhập bằng tài khoản KH/PT/Admin. Nút góc trái header mở hoặc thu gọn menu; bấm avatar để mở hồ sơ/đăng xuất.
