# Thay logo hệ thống — 04/10/2026

Chủ dự án cung cấp `Logo Fitness Neon Hiện Đại.png` và yêu cầu thay logo hệ thống. Sao chép nguyên ảnh PNG có nền trong suốt vào `FE/public/images/logo-fitness-neon.png`; dùng cùng logo cho sáng/tối, không sửa artwork.

- `LogoThuongHieu.vue`: trỏ tới ảnh mới qua BASE_URL, thay hai logo cũ theo theme; giữ kích thước, object-fit và liên kết/nhãn của phần tử cha.
- `MenuCaNhan.vue`: thay biểu tượng sét bằng component logo chung, áp dụng cho KH/PT/Admin, cả menu thu gọn và menu di động.
- `DanhMucLayout.vue`: header công khai dùng logo chung. Trang chủ/footer và xác thực dùng component sẵn có nên cập nhật cùng.
- `FE/index.html`: biểu tượng tab dùng PNG mới, hỗ trợ BASE_URL. Hai assets logo cũ không còn được giao diện tham chiếu; giữ file cũ để không ảnh hưởng tài liệu lịch sử.

Kiểm tra thực tế: build, lint và format các file thay đổi đạt trên Windows. Trình duyệt localhost5173: trang đăng ký sáng/tối và header FAQ tải đúng ảnh mới, favicon trỏ đúng PNG. Menu theo vai trò kiểm chứng bằng component dùng chung và build; không đăng nhập tài khoản thật hoặc thay dữ liệu nghiệp vụ. Không chạy lại toàn bộ kiểm thử nghiệp vụ cho thay đổi asset này.

[Ảnh giao diện](logo-fitness-neon.png). Tải lại trang để xem logo mới; không cần migration, seeder hoặc khởi động lại Backend.
