# Mẫu cách viết code

Các file `.example` là tài liệu có code minh họa, **không tự được build/autoload** và không chứng minh tính năng đã triển khai.

| Mẫu | Nội dung |
| --- | --- |
| [frontend/TrangDanhSach.vue.example](frontend/TrangDanhSach.vue.example) | Vue Options API, gọi service, loading/error, không lặp Axios |
| [frontend/goiTapService.js.example](frontend/goiTapService.js.example) | Module service dùng HTTP client chung |
| [backend/GoiTapController.php.example](backend/GoiTapController.php.example) | Controller mỏng, FormRequest/Policy/Resource |

Giữ cách code gần repo cũ: Options API, tiếng Việt không dấu, Laravel/Eloquent. Chuẩn hóa mới: camelCase, API services, đúng HTTP và quyền server. Quy ước đầy đủ nằm ở [CODE_STYLE.md](../CODE_STYLE.md).

`templates/` nằm ở thư mục gốc và chỉ dùng để tham khảo. Source chạy thật sau khi khởi tạo ứng dụng nằm trong `FE/` hoặc `BE/`.

Khi chuyển mẫu thành source cần thay đường dẫn/placeholder, implement dependencies thật và kiểm thử. Không sao chép ví dụ rồi báo module hoàn thành.
