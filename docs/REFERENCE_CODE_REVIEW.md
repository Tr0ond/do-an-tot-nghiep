# Tham khảo phong cách code của chủ dự án

**Ngày đọc mẫu:** 01/10/2026. Đây là quan sát từ manifests, routing và một số controller/component/service/test đại diện, không phải kiểm toán toàn bộ ba codebase. Chưa chạy build/test của ba dự án.

## 1. E:/CNPM

Mẫu đã xem:

- `be_travel/composer.json`, `fe_travel/package.json`.
- `be_travel/app/Http/Controllers/ChatBotController.php` (các đoạn xử lý request, ngữ cảnh, schema và fallback).
- `fe_travel/src/components/Client/ChatBot/index.vue` (script/data/lifecycle và các điểm gọi API).
- `be_travel/app/Http/Controllers/KhachHangController.php` (tên methods).
- `fe_travel/src/router/checkAdmin.js`, `checkClient.js`, `checkHuongDanVien.js` (các đoạn guard/auth storage).

Quan sát: Laravel 12/Sanctum, Vue 3/Vite; chia admin/client/hướng dẫn viên. Vue Options API, có các khóa auth theo vai trò trong localStorage. Chatbot gọi Gemini qua HTTP ở Backend, đọc catalog từ DB, giới hạn ngữ cảnh và có structured response/ID kiểm tra/fallback. Đây là kinh nghiệm có thể áp dụng cho chatbot gói PT.

Điều chỉnh cho dự án mới: tách provider/context builder/response validator khỏi một controller lớn; lưu hội thoại server nếu cần lịch sử đáng tin cậy; cookie auth và API client chung; không copy nguyên prompt du lịch hay package list gồm nhiều thư viện UI.

## 2. E:/Cinema_project

Mẫu đã xem:

- `BE_K24/composer.json`, `FE_KHOA_24/package.json`.
- `BE_K24/app/Http/Controllers/BaseController.php` (tên methods/quyền/DB connection qua tìm kiếm).
- `BE_K24/app/Http/Controllers/DonHangController.php` (các đoạn trả response, đọc đơn và quyền).
- `BE_K24/routes/api.php` (đoạn đầu).
- `FE_KHOA_24/src/components/Admin/Phim/index.vue` (script CRUD).

Quan sát: Laravel 12/Sanctum, Vue Options API. State tạo/sửa/xóa tách rõ; CRUD dùng modal, `.then/.catch`, toast và Axios trực tiếp. Naming DB/payload tiếng Việt không dấu; một số methods như `loadDataPhim/addPhim`, state `list_phim/create_phim`. Nhiều route dạng `get-data/add-data/update` và response `status/message/data`.

Điều chỉnh: giữ cách chia state/form và nghiệp vụ dễ đọc; thống nhất camelCase; service API chung; HTTP methods/status chuẩn và response bool thống nhất; quyền theo tài nguyên, không chỉ mã chức năng. Không sao chép logic đổi default database connection từ BaseController.

## 3. E:/CS 445/Travel_Master

Tên folder là Travel_Master nhưng README và các mẫu code cho thấy hệ thống **EduManage quản lý học tập/điểm danh**, không phải dự án du lịch.

Mẫu đã xem:

- `README.md`, `BE/composer.json`, `FE/package.json`.
- `FE/src/utils/axios.js`, `FE/src/stores/auth.js`.
- `FE/src/views/admin/phan-cong.vue` (phần component/script).
- `FE/src/router/index.js` (phần đầu).
- `BE/app/Http/Controllers/Api/TinNhanPhongController.php`.
- `BE/app/Services/QuyenPhongService.php` (phần quyền).
- `BE/tests/Feature/ThongBaoRealtimeTest.php` (phần đầu và một số assertions).
- Inventory services, requests và feature tests.

Quan sát: Laravel 13/PHP ^8.3, Reverb/Sanctum; FE tách `views/layout/utils/stores`, vẫn dùng Options API. API instance có timeout/interceptors/socket ID; Pinia cho auth/thông báo, lazy routes, cleanup Echo khi logout. Backend có service kiểm tra resource scope, lưu tin rồi broadcast và feature tests. Manifest Backend còn có frontend Inertia của starter; dự án mới chỉ chọn một Vue SPA độc lập.

Điều chỉnh: kế thừa cách tách views/store/services và cleanup realtime. Dùng cookie SPA thay token localStorage; phân trang tin, message ID/chống trùng, timestamp đầy đủ và test Reverb thật. Không coi Event fake test là chứng minh giao nhận realtime trên mạng.

## 4. Kết luận cho bộ khung mới

| Giữ để gần thói quen | Chuẩn hóa để dễ bảo trì |
| --- | --- |
| Vue Options API/JavaScript | Naming nghiệp vụ camelCase nhất quán |
| Laravel/Eloquent, FE/BE tách riêng | Controller/FormRequest/Policy/Service đúng trách nhiệm |
| Tiếng Việt không dấu ở dữ liệu | Một response contract và đúng HTTP status |
| Layout theo actor, modal CRUD | Page ở views; components dùng chung |
| Axios, Pinia, Chart.js | API client/service chung, không lặp headers |
| Chatbot dữ liệu thật, Reverb | Kiểm tra IDs/nguồn, chống trùng và thu hồi quyền |
| Validation và feature tests | Bổ sung MySQL concurrency/live realtime/AI eval |

Không khẳng định mọi file trong dự án cũ đều tuân thủ những mẫu này. Không có dữ liệu cá nhân, API key hay nội dung `.env` được sao chép vào bộ tài liệu mới.
