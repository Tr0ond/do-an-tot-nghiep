# Phong cách code

Nguồn quan sát: [REFERENCE_CODE_REVIEW.md](REFERENCE_CODE_REVIEW.md). Mục tiêu là giữ cách viết dễ nhận ra với chủ dự án, đồng thời giảm lặp và bảo vệ dữ liệu.

## 1. Frontend Vue

**Mặc định Options API + JavaScript**, không chuyển sang `script setup` chỉ vì framework cho phép. Pinia dùng Options Store; API Vue/framework giữ tên chuẩn.

Chủ dự án đã xác nhận Vue Options API/JavaScript ngày 01/10/2026 (C26). Bootstrap 5.3 làm nền tảng; được bổ sung CSS/Tailwind khi cần theo C25. Giữ quy ước styles chung, tránh để hai hệ class/reset làm thay đổi cùng component ngoài dự kiến; chỉ cấu hình dependency thực tế khi bootstrap.

Thứ tự trong file Vue: `template`, `script`, `style scoped`. Trong `export default`: `name`, `components`, `data`, `computed`, `watch` nếu cần, lifecycle hooks, `methods`.

Ví dụ tên: `danhSachGoiTap`, `duLieuTaoMoi`, `duLieuCapNhat`, `dangTai`, `dangLuu`, `loiTruong`, `taiDanhSach`, `taoGoiTap`, `xuLyXacNhan`. Dùng tên tiếng Việt không dấu camelCase; không trộn `list_goi_tap`, `createGoi`, `loadDataGoi` trong cùng module.

- Pages đặt trong `FE/src/views/`, ví dụ `Admin/GoiTap/index.vue`, `PT/LichHen/index.vue`, `KhachHang/KeHoachTap/index.vue`.
- `components/` chỉ chứa thành phần dùng chung, tránh đặt mọi page vào `components/`.
- Giữ các layout theo tác nhân như repo cũ, nhưng dùng `layouts/` với một quy ước thống nhất.
- Component gọi `services/goiTapService.js`; service dùng `utils/http.js`. Không lặp base URL/headers trong từng method.
- Pinia cho auth, hội thoại và thông báo; trạng thái form/filter cục bộ ở component.
- Dùng `async/await`, `try/catch/finally`; phân biệt lỗi mạng, 401, 403, 409, 422 và 429.
- Nút đang gửi phải bị vô hiệu; backend vẫn có chống trùng riêng.
- Copy dữ liệu khi mở form sửa, không để form thay đổi trực tiếp hàng trong bảng trước khi API thành công.
- Listener/timer/socket được dọn trong `beforeUnmount`; logout hủy kết nối và state nhạy cảm.
- Danh sách lớn phân trang server; không tải toàn bộ để lọc nếu API đã hỗ trợ query.
- Tránh `v-html` với tin nhắn/AI. Dùng plain text hoặc renderer có sanitization.
- UI tiếng Việt có dấu; labels đầy đủ, trạng thái loading/empty/error; ưu tiên responsive cho KH.
- CSS chung qua variables; không lặp hàng trăm inline styles. Không jQuery thao tác DOM do Vue quản lý.

## 2. Backend Laravel

Class PascalCase: `GoiTapController`, `DatLichRequest`, `LichHenService`, `KhachHangPolicy`. Method CRUD kỹ thuật giữ `index/store/show/update`; method nghiệp vụ dùng `xacNhanHoanThanh`, `phanCongHuanLuyenVien`.

- Controller mỏng: validate → authorize → gọi service nếu có nghiệp vụ → trả resource/JSON.
- FormRequest dùng cho create/update có nhiều validation; không nhận thẳng `$request->all()` vào model.
- Policy hoặc service quyền kiểm tra ownership và phân công còn hiệu lực, không chỉ role.
- Eloquent cho query/relations; eager loading và pagination, tránh N+1.
- Service cho transaction nhiều bước; không tạo Repository cho mọi model.
- Tiền VND dùng số nguyên; không dùng float cho giá/thực thu. Thời gian do backend ghi.
- Không tắt foreign key hoặc đổi default DB connection trong controller nghiệp vụ.
- Không dùng HTTP 200 cho mọi lỗi; hợp đồng thống nhất tại `md/API_CONVENTIONS.md`.
- Secrets qua config/environment; logs không chứa API key, session cookie hoặc đầy đủ nội dung chat riêng.
- Dependency được inject ở constructor khi phù hợp, dễ fake khi test.

## 3. Database và payload

- Tiếng Việt không dấu snake_case: `tai_khoan`, `lich_hen_huan_luyen`, `khach_hang_id`.
- IDs nội bộ integer/bigint theo quyết định schema; FK đặt `<tai_nguyen>_id`.
- Payload snake_case; frontend chuyển sang camelCase cho state khi có ích, không đổi tên hàng loạt tùy tiện.
- Enum nghiệp vụ viết hoa: `CHO_XAC_NHAN`, `HOAN_THANH`; role dùng ba giá trị trong `md/PROJECT_RULES.md`.
- Ngoại lệ kỹ thuật framework như `created_at`, `updated_at`, `jobs`, `sessions` giữ tên framework.
- Ngừng dùng catalog/tài khoản thay vì cascade xóa lịch sử. Index dựa trên query thực tế.

## 4. Comment và formatting

Comment tiếng Việt có dấu, ghi lý do: “Khóa gói để hai lần xác nhận không cùng tiêu hao lượt cuối.” Tránh comment thừa “Tăng biến thêm một”.

Indent JS/Vue/CSS 2 spaces, PHP 4 spaces. UTF-8, LF. JavaScript dùng single quote, không semicolon theo baseline; formatter sẽ kiểm soát sau bootstrap.

## 5. Mẫu tham khảo

Các mẫu nằm trong [templates/](../templates/): [Vue page](../templates/frontend/TrangDanhSach.vue.example), [service JavaScript](../templates/frontend/goiTapService.js.example) và [Laravel Controller](../templates/backend/GoiTapController.php.example). Các file `.example` không phải implementation và không được xem là endpoint/component đã hoàn thành. Khi sử dụng phải thay placeholders, thêm validation/authorization và test phù hợp.
