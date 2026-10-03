# Màu card giáo án mẫu — 03/10/2026

Sửa `FE/src/assets/giaoAnMau.css`: nền card/hover/ảnh nhỏ, ô ghi chú, màu thông số và nút chọn ngày lấy từ biến theme thay màu đen cố định. Nút hướng dẫn có chữ cam đậm trên light. Không thay dữ liệu hoặc nghiệp vụ.

Kiểm tra bằng trình duyệt với PT giả lập, giáo án mẫu 2 ngày trong database fixture riêng, FE localhost:5291 / BE localhost:8017:

- Light: card `rgb(248, 250, 252)`, ghi chú trắng, tên bài `rgb(15, 23, 42)`.
- Dark: card `rgb(24, 24, 24)`, tên bài trắng; chuyển lại light đúng.
- Không có console error trong luồng kiểm tra. Đã dọn fixture, tab và máy chủ thử.
- Vite build, Prettier check file CSS và git diff --check PASS. Không chạy lại test ứng dụng vì chỉ đổi CSS.

[Ảnh light](sample-plan-light-cards.png) · [Ảnh dark](sample-plan-dark-cards.png)
