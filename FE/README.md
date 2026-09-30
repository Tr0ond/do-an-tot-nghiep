# FE — Vue SPA

**Trạng thái:** đã bootstrap Vue **3.5.43**, JavaScript/Options API, Vite **8.3.1**, cài dependencies và lưu package-lock.json. Đã chạy trên Node **22.20.0**, npm **10.9.3**. package.json ghi Node tương thích ^22.18.0 hoặc >=24.12.0.

Đã cài Vue Router **5.3.1**, Pinia **4.0.3**, Axios **1.20.0**, Bootstrap **5.3.8**, ESLint/Oxlint/Prettier. Router/Pinia đã đăng ký; không giữ counter Composition API mẫu, chưa tạo state nghiệp vụ dùng chung. Chart.js/Echo sẽ được thêm khi làm module tương ứng.

## Chạy Frontend

Từ FE/:

```powershell
rtk proxy npm.cmd ci
rtk proxy npm.cmd run dev
```

Mở [localhost:5173](http://localhost:5173), bấm **Kiểm tra kết nối** khi Laravel chạy ở localhost:8000. Đã kiểm tra bằng trình duyệt tích hợp và nhận kết nối thành công. Vite dùng port cố định 5173; hai bên dùng cùng hostname localhost.

Đã tạo .env local từ .env.example. VITE_API_BASE_URL được Axios instance ở src/utils/http.js đọc; component gọi qua src/services/heThongService.js. Đổi .env cần khởi động lại Vite. Các biến VITE_* là công khai, không đặt AI key/Reverb secret vào đây. Biến Reverb vẫn là placeholders.

## Kiểm tra đã chạy

```powershell
rtk proxy npm.cmd run build
rtk proxy npm.cmd run lint:check
rtk proxy npm.cmd run format:check
```

Đạt build/lint/format; dist/ là kết quả build local. Chạy rtk proxy npm.cmd run preview để xem build. Chưa thêm component/E2E tests cho các use case chưa triển khai. [Bằng chứng bootstrap](../docs/verification/BOOTSTRAP.md).

## Cấu trúc và phạm vi

Giữ src/views/Admin, src/views/PT, src/views/KhachHang và thêm src/views/TrangChu/index.vue; các thư mục components, layouts, router, stores, services, utils, assets/styles giữ theo quy tắc dự án. Pages dùng data/computed/methods. Pinia chỉ dành cho trạng thái dùng chung; trạng thái kiểm tra kết nối nằm ở component.

Trang hiện tại chỉ kiểm tra kết nối API. Chưa có đăng nhập, màn hình theo vai trò, catalog, thanh toán, chatbot hoặc realtime. Tham khảo [CODE_STYLE.md](../CODE_STYLE.md), [quyết định](../docs/DECISIONS.md) và [mẫu Frontend](../templates/README.md).
