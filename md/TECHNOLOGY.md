# Công nghệ áp dụng

Đối chiếu tài liệu chính thức ngày 01/10/2026. Đã bootstrap Laravel 13.34.0/Vue 3.5.43/Vite 8.3.1, cài Sanctum 4.3.3 và lưu lockfiles trong `BE/`/`FE/`. Đã kiểm tra trên PHP 8.4.0/Composer 2.8.12/Node 22.20.0/npm 10.9.3/MariaDB 10.4.32. Sanctum session/cookie và migrations đã chạy; Reverb/payOS/Gemini chưa triển khai. [Bằng chứng tài khoản](verification/M01_AUTH.md).

## Bộ công nghệ chính

| Thành phần | Lựa chọn | Lý do |
| --- | --- | --- |
| Frontend | Vue 3 + JavaScript + Options API | Giữ `data/computed/methods` quen thuộc trong cả ba dự án |
| Build | Vite bản ổn định tương thích Vue/Node | Công cụ đã dùng ở các dự án cũ |
| Runtime JS | Node.js LTS thỏa yêu cầu phiên bản Vite chọn | Kiểm tra tại bootstrap; chưa pin số phiên bản |
| Router/state | Vue Router + Pinia | Layout/role routing; trạng thái auth, chat, thông báo dùng chung |
| HTTP | Axios instance tập trung | Cookie, timeout, lỗi và socket ID xử lý chung |
| UI baseline | Bootstrap 5.3 + CSS scoped/design variables; được bổ sung Tailwind khi cần | C25 đã xác nhận; cần kiểm soát class/reset/style ownership khi kết hợp, chưa tự thêm dependency |
| Biểu đồ | Chart.js + vue-chartjs | Đã dùng trong dự án tham khảo |
| Backend | Laravel 13.x, PHP 8.3–8.5 tương thích dependencies | Gần stack EduManage; PHP tối thiểu của Laravel 13 là 8.3 |
| Xác thực | Sanctum SPA session/cookie | Website do cùng ứng dụng sở hữu; không mặc định lưu bearer token trong localStorage |
| Database | MySQL 8.4, InnoDB, utf8mb4 | Transaction/khóa/constraint cho lịch và quota |
| Thanh toán | payOS; tích hợp ở Backend, PHP SDK chính thức là ứng viên | Chủ dự án đã chọn; link hết hạn theo đơn 15 phút, xác minh webhook, chống cấp gói lặp. Chưa cài SDK/gọi API |
| Realtime | Laravel Reverb + Laravel Echo + pusher-js | Đã có ví dụ trong EduManage; pusher-js là client tương thích giao thức, không bắt buộc thuê Pusher |
| Queue | Laravel database queue + worker | Đủ bản đầu trên một máy; Redis là tùy chọn khi có bằng chứng cần |
| Chatbot | Laravel HTTP Client + provider adapter | Gần cách CNPM gọi Gemini; tránh khóa domain vào nhà cung cấp |
| AI provider | Gemini theo cách CNPM; model cấu hình được, baseline `gemini-3.1-flash-lite` | C24 đã chốt chỉ API miễn phí; hết quota báo bận/fallback rõ không mất lượt. Model là fallback trong mã CNPM, không khẳng định cấu hình `.env` thực tế |
| Test | PHPUnit/Laravel feature test; Vitest/Vue Test Utils; Playwright | Nghiệp vụ, component cần thiết, luồng trình duyệt và realtime |
| Format/lint | Laravel Pint; ESLint + eslint-plugin-vue; Prettier | Quy tắc nhất quán, tránh format thủ công |
| Deploy | Một máy chủ Linux, web server HTTPS, PHP-FPM, MySQL, Reverb, queue worker | Cấu trúc gọn cho đồ án; Windows có thể dùng để phát triển |

## Quyết định kiến trúc

- Vue SPA gọi Laravel API; không dùng thêm frontend Inertia nằm trong Backend.
- Một bảng tài khoản và ba role; profiles KH/PT riêng. Không ba cơ chế token rời nhau.
- Cookie auth triển khai cùng domain hoặc subdomain có cấu hình Sanctum phù hợp. Dev dùng hostname thống nhất; không trộn `localhost` và `127.0.0.1` tùy tiện.
- Backend giữ API key AI. Vue chỉ gọi API Laravel.
- payOS secrets chỉ ở BE; Backend đối chiếu kết quả đã xác minh với đơn đã lưu. Mốc hết hạn của đơn truyền qua `expiredAt`; không kích hoạt gói từ query trên trang quay về. Chưa xác nhận tài khoản/kênh hoặc môi trường test của chủ dự án.
- RAG/vector database, fine-tuning và Laravel AI SDK chưa là dependency bắt buộc. Bản đầu tra catalog + lọc FAQ/lịch mẫu, đưa ngữ cảnh có giới hạn vào mô hình.
- Composer/npm lockfiles phải được commit sau bootstrap; không copy dependency versions từ repo cũ một cách máy móc.

## Tài liệu chính thức

- [Vue — Options API và Composition API](https://vuejs.org/guide/introduction.html).
- [Vue — quản lý trạng thái/Pinia](https://vuejs.org/guide/scaling-up/state-management.html).
- [Vite — yêu cầu và bắt đầu](https://vite.dev/guide/).
- [Laravel — phiên bản và PHP hỗ trợ](https://laravel.com/framework/docs/releases).
- [Sanctum — xác thực SPA](https://laravel.com/framework/docs/sanctum).
- [Laravel Reverb](https://laravel.com/framework/docs/reverb), [Broadcasting/private channels](https://laravel.com/framework/docs/broadcasting).
- [MySQL 8.4](https://dev.mysql.com/doc/refman/8.4/en/).
- [payOS API — tạo link/`expiredAt`](https://payos.vn/docs/api/), [chữ ký webhook](https://payos.vn/docs/tich-hop-webhook/kiem-tra-du-lieu-voi-signature/), [PHP SDK](https://payos.vn/docs/sdks/back-end/php/). Đã đọc ngày 01/10/2026; chưa kiểm thử tích hợp.
- [Gemini — structured output](https://ai.google.dev/gemini-api/docs/structured-output).
- [Gemini 3.1 Flash-Lite](https://ai.google.dev/gemini-api/docs/models/gemini-3.1-flash-lite), [vòng đời model](https://ai.google.dev/gemini-api/docs/deprecations), [giá/hạn mức miễn phí](https://ai.google.dev/gemini-api/docs/pricing). Đã đối chiếu ngày 01/10/2026; tài khoản/quota thực tế phải kiểm tra lúc bootstrap, không bật billing cho baseline.
- [OpenAI — function calling](https://developers.openai.com/api/docs/guides/function-calling).
- [Playwright](https://playwright.dev/docs/writing-tests).

Các link là nguồn tham khảo kỹ thuật. Quy mô một phòng gym/UI/provider và ngân sách API đã được chủ dự án chốt; dependencies đã khóa phiên bản, nhưng thông tin tài khoản dịch vụ và kiểm thử tích hợp nghiệp vụ chưa có. Ước lượng thời gian không phải bảo đảm từ nhà cung cấp.
