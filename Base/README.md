# Base — Chỉ mục thành phần dự án

`Base/` hiện chỉ chứa chỉ mục này. Frontend, Backend và mẫu code đã được đặt trực tiếp ở thư mục gốc. Chưa có ứng dụng Laravel/Vue chạy được.

| Thư mục | Mục đích |
| --- | --- |
| [FE](../FE/README.md) | Vue SPA, pages, layouts, services, stores |
| [BE](../BE/README.md) | Laravel API, requests, policies, services, events/jobs |
| [templates](../templates/README.md) | Ví dụ cách code theo thói quen đã tham khảo |
| [docs](../docs/README.md) | Thiết kế, API, bản nháp dữ liệu và kiểm thử |

Khi khởi tạo ứng dụng: tạo framework vào thư mục trống tạm trong dự án, xác minh file được sinh, rồi tích hợp vào `FE/` hoặc `BE/` và giữ tài liệu hiện có. Không xóa thư mục đang có nội dung để làm trống cho công cụ khởi tạo.

Dependencies, lockfiles và runtime được tạo ở giai đoạn M01, không tự sao chép từ ba dự án tham khảo. Bản nháp database nằm ở [docs/DATABASE_DRAFT.md](../docs/DATABASE_DRAFT.md); migration chạy thật thuộc `BE/database/migrations/`.
