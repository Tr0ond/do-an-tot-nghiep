# FE — Vue SPA

**Trạng thái:** đã bootstrap Vue **3.5.43**, JavaScript/Options API, Vite **8.3.1**, cài dependencies và lưu package-lock.json. Đã chạy trên Node **22.20.0**, npm **10.9.3**. package.json ghi Node tương thích ^22.18.0 hoặc >=24.12.0.

Đã cài Vue Router **5.3.1**, Pinia **4.0.3**, Axios **1.20.0**, Bootstrap **5.3.8**, ESLint/Oxlint/Prettier. Pinia Options Store giữ tài khoản trả từ Backend trong bộ nhớ; không lưu token/role vào localStorage. Chart.js/Echo sẽ được thêm khi làm module tương ứng.

## Chạy Frontend

Từ FE/:

```powershell
rtk proxy npm.cmd ci
rtk proxy npm.cmd run dev
```

Mở [đăng ký](http://localhost:5173/dang-ky) hoặc [đăng nhập](http://localhost:5173/dang-nhap) khi Laravel chạy ở localhost:8000. Vite dùng port cố định 5173; hai bên dùng cùng hostname localhost. Khách tự đăng ký; PT/Admin được Admin cấp tài khoản. Admin đầu tiên tạo bằng lệnh ở [hướng dẫn tài khoản](../docs/features/TAI_KHOAN.md).

Đã tạo .env local từ .env.example. VITE_API_BASE_URL được Axios instance ở src/utils/http.js đọc; component gọi qua services/xacThucService.js và taiKhoanService.js. Service lấy CSRF cookie trước POST, Axios gửi credentials/XSRF. Đổi .env cần khởi động lại Vite. Các biến VITE_* là công khai, không đặt AI key/Reverb secret vào đây. Biến Reverb vẫn là placeholders.

## Kiểm tra đã chạy

```powershell
rtk proxy npm.cmd run build
rtk proxy npm.cmd run lint:check
rtk proxy npm.cmd run format:check
```

Đạt build/lint/format và 7 tests Pinia bằng Vitest 5.0.3 (npm run test), gồm session mất hiệu lực và lỗi CSRF/mạng khi đăng xuất. dist/ là kết quả build local. Đã kiểm tra trình duyệt: đăng ký/đăng nhập KH, refresh session, sai mật khẩu, chặn trang Admin, đăng xuất, Admin tạo PT và PT đăng nhập. Kiểm tra responsive 1440/768/390px. Chưa thêm bộ E2E chạy tự động cho Vue. [Bằng chứng tài khoản](../docs/verification/M01_AUTH.md).

## Cấu trúc và phạm vi

Trang đăng nhập/đăng ký tại views/XacThuc, hồ sơ KH/PT dùng chung views/CaNhan, quản trị tại views/Admin/TaiKhoan. components/TruongNhap và layouts dùng chung; page dùng data/computed/methods. Pinia chỉ giữ auth; form/loading/errors nằm ở component. Router tải /me trước trang cần vai trò; Backend vẫn quyết định quyền.

Đã có luồng tài khoản và màn hình theo vai trò; hồ sơ mới chưa có mục tiêu/chuyên môn hiển thị Chưa cung cấp. Có [danh mục bài tập](http://localhost:5173/bai-tap) công khai và chi tiết tại /bai-tap/:id, tìm kiếm/lọc/phân trang theo URL, ảnh/GIF từ Backend và ghi công nguồn. Code tại views/BaiTap, services/baiTapService.js, components/AnhBaiTap.vue, layouts/DanhMucLayout.vue. Mỗi component giữ trạng thái cục bộ, hủy request cũ và chặn kết quả tới muộn. GIF chỉ tải khi bấm xem, có nút dừng.

Đã có `/admin/bai-tap` để tìm/lọc/phân trang, thêm/sửa và bật/tắt hiển thị. Biểu mẫu tại `/admin/bai-tap/them` và `/admin/bai-tap/:id/sua`, Vue Options API; service riêng `baiTapAdminService.js`, trạng thái form cục bộ. Khi lưu lỗi giữ nội dung; khi 409 chặn gửi lại và cho tải bản mới. Chỉ ADMIN được vào trang; API vẫn kiểm tra quyền ở server.

Toàn bộ frontend đạt **49 Vitest tests**, build/lint/format. Có bảng giá `/goi-tap`, chi tiết `/goi-tap/:id`; Admin quản lý tại `/admin/goi-tap`, thêm `/admin/goi-tap/them`, sửa `/admin/goi-tap/:id/sua`. Options API/service dùng chung, bộ lọc URL, loading/empty/error/404, khóa gửi trùng, giữ UUID khi retry mạng và xử lý 409. Admin tự nhập giá/quyền lợi; không có giá giả hoặc cấp gói từ UI. [Hợp đồng gói](../docs/features/GOI_TAP.md), [kiểm chứng](../docs/verification/M02_GOI_TAP.md).

Chưa có upload media, quản lý nhóm cơ, chỉnh sửa hồ sơ, quên/đặt lại mật khẩu, mua/thanh toán gói, chatbot hoặc realtime. Tham khảo [CODE_STYLE.md](../CODE_STYLE.md), [quyết định](../docs/DECISIONS.md) và [mẫu Frontend](../templates/README.md).

## Giáo án mẫu Admin/PT

Admin có `/admin/giao-an-mau`, `/admin/giao-an-mau/them`, `/admin/giao-an-mau/:id/sua`; PT có `/pt/giao-an-mau`, `/pt/giao-an-mau/:id`. Trình soạn chọn bài từ API đang hoạt động, phân trang 6 bài/lần, thêm/bỏ/đổi thứ tự trong từng ngày, nhập hiệp/lặp/nghỉ/ghi chú; lưu nháp rồi duyệt/ngừng. Sửa bản duyệt về nháp, giữ form khi 422/409/mất mạng. PT chỉ đọc và mở hướng dẫn bài, chưa tạo kế hoạch khách hàng. Vue JavaScript Options API, state cục bộ và service chung. [Hợp đồng](../docs/features/GIAO_AN_MAU.md), [kiểm chứng](../docs/verification/M02_GIAO_AN_MAU.md).
