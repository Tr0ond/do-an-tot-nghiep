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

Đạt build/lint/format và 91 tests trên 9 file bằng Vitest 5.0.3 (npm run test), gồm auth/session, hồ sơ/khôi phục/khóa tài khoản và các module catalog. dist/ là kết quả build local. Đã kiểm tra trình duyệt: đăng ký/đăng nhập KH, refresh session, sai mật khẩu, chặn trang Admin, đăng xuất, Admin tạo PT và PT đăng nhập. Kiểm tra responsive 1440/768/390px. Chưa thêm bộ E2E chạy tự động cho Vue. [Bằng chứng tài khoản](../docs/verification/M01_AUTH.md).

## Cấu trúc và phạm vi

Trang đăng nhập/đăng ký tại views/XacThuc, hồ sơ KH/PT dùng chung views/CaNhan, quản trị tại views/Admin/TaiKhoan. components/TruongNhap và layouts dùng chung; page dùng data/computed/methods. Pinia chỉ giữ auth; form/loading/errors nằm ở component. Router tải /me trước trang cần vai trò; Backend vẫn quyết định quyền.

Đã có luồng tài khoản và màn hình theo vai trò; hồ sơ mới chưa có mục tiêu/chuyên môn hiển thị Chưa cung cấp. Có [danh mục bài tập](http://localhost:5173/bai-tap) công khai và chi tiết tại /bai-tap/:id, tìm kiếm/lọc/phân trang theo URL, ảnh/GIF từ Backend và ghi công nguồn. Code tại views/BaiTap, services/baiTapService.js, components/AnhBaiTap.vue, layouts/DanhMucLayout.vue. Mỗi component giữ trạng thái cục bộ, hủy request cũ và chặn kết quả tới muộn. GIF chỉ tải khi bấm xem, có nút dừng.

Đã có `/admin/bai-tap` để tìm/lọc/phân trang, thêm/sửa và bật/tắt hiển thị. Biểu mẫu tại `/admin/bai-tap/them` và `/admin/bai-tap/:id/sua`, Vue Options API; service riêng `baiTapAdminService.js`, trạng thái form cục bộ. Khi lưu lỗi giữ nội dung; khi 409 chặn gửi lại và cho tải bản mới. Chỉ ADMIN được vào trang; API vẫn kiểm tra quyền ở server.

Toàn bộ frontend đạt **91 Vitest tests**, build/lint/format. Có bảng giá `/goi-tap`, chi tiết `/goi-tap/:id`; Admin quản lý tại `/admin/goi-tap`, thêm `/admin/goi-tap/them`, sửa `/admin/goi-tap/:id/sua`. Options API/service dùng chung, bộ lọc URL, loading/empty/error/409, khóa gửi trùng, giữ UUID khi retry mạng và xử lý 409. Admin tự nhập giá/quyền lợi; không có giá giả hoặc cấp gói từ UI. [Hợp đồng gói](../docs/features/GOI_TAP.md), [kiểm chứng](../docs/verification/M02_GOI_TAP.md).

Đã có mua/thanh toán gói M03. Chưa có upload media, chatbot hoặc realtime. Tham khảo [CODE_STYLE.md](../CODE_STYLE.md), [quyết định](../docs/DECISIONS.md) và [mẫu Frontend](../templates/README.md).

## Giáo án mẫu Admin/PT

Admin có `/admin/giao-an-mau`, `/admin/giao-an-mau/them`, `/admin/giao-an-mau/:id/sua`; PT có `/pt/giao-an-mau`, `/pt/giao-an-mau/:id`. Trình soạn chọn bài từ API đang hoạt động, phân trang 6 bài/lần, thêm/bỏ/đổi thứ tự trong từng ngày, nhập hiệp/lặp/nghỉ/ghi chú; lưu nháp rồi duyệt/ngừng. Sửa bản duyệt về nháp, giữ form khi 422/409/mất mạng. PT chỉ đọc và mở hướng dẫn bài, chưa tạo kế hoạch khách hàng. Vue JavaScript Options API, state cục bộ và service chung. [Hợp đồng](../docs/features/GIAO_AN_MAU.md), [kiểm chứng](../docs/verification/M02_GIAO_AN_MAU.md).

## Quản lý nhóm cơ Admin

Danh sách tại `/admin/nhom-co`, form thêm `/admin/nhom-co/them`, sửa `/admin/nhom-co/:id/sua`. Tìm/lọc/phân trang theo URL, xem tổng bài và số đang hiển thị, liên kết bài theo nhóm; ngừng/khôi phục có xác nhận ảnh hưởng. Form giữ mã/tên nguồn, lỗi cạnh trường, không gửi trùng, bảo vệ bản cũ và giữ nội dung khi lỗi. Backend quyết định quyền/trạng thái; không cần seed lại. Đã kiểm tra desktop/tablet/mobile, toàn FE đạt 79 tests và build/lint/format. [Hợp đồng](../docs/features/NHOM_CO.md), [kiểm chứng](../docs/verification/M02_NHOM_CO.md).

## Sửa hồ sơ và khôi phục mật khẩu M01
KH/PT/Admin có trang hồ sơ và sửa tại /khach-hang/ho-so/sua, /pt/ho-so/sua, /admin/ho-so/sua. KH nhập mục tiêu, kinh nghiệm, ngày sinh, giới tính và thời gian tập; PT nhập chuyên môn/giới thiệu; Admin sửa họ tên. Dữ liệu hiển thị từ Backend, không có thông số demo giả. Form giữ bản nháp khi lỗi; bản cũ 409 yêu cầu tải lại rõ ràng trước khi lưu.

Trang /quen-mat-khau gửi yêu cầu khôi phục; /dat-lai-mat-khau đọc token từ fragment của liên kết email rồi xóa khỏi URL. Nếu tải lại trang sau khi fragment bị xóa, cần mở lại liên kết email. Token chỉ giữ trong component, không localStorage. Admin khóa/mở tài khoản tại /admin/tai-khoan với hộp thoại xác nhận và phiên bản cập nhật.

Đạt 79 frontend tests, build/lint/format; đã kiểm tra hồ sơ và khôi phục ở desktop 1440, tablet 768, mobile 390. Backend kiểm tra quyền/CSRF/phiên, frontend không tự cấp quyền. [Kiểm chứng M01 bổ sung](../docs/verification/M01_HO_SO_KHOI_PHUC.md).

## Trang chủ và dashboard
Người chưa đăng nhập vào / thấy trang giới thiệu. Router chờ /me trước khi chuyển người đã đăng nhập về /khach-hang/tong-quan, /pt/tong-quan hoặc /admin/tong-quan; áp dụng cả truy cập trực tiếp, refresh và URL cũ có hash. Đăng nhập/đăng ký thành công vào tổng quan, hồ sơ có link riêng trong menu. Nếu không xác minh được session do mất kết nối, hiển thị trang lỗi kết nối, không giả coi người dùng là guest.

Dashboard KH/PT có thống kê thư viện và checklist mức đầy đủ hồ sơ; Admin có tổng tài khoản, phân bố vai trò/trạng thái và các danh mục. Thống kê lấy từ API thật, không lưu cache cá nhân vào localStorage. Trang tại views/TongQuan, service tongQuanService; loading/error/retry, hủy request và bỏ qua kết quả tới muộn. Menu KH/Admin nằm trong header, không lặp trong nội dung; danh mục Gói tập/Thư viện cũng giữ header theo vai trò khi đăng nhập. Trên màn hình nhỏ menu nằm ở hàng thứ hai của header và cuộn ngang trong vùng riêng, không làm tràn toàn trang. [Hợp đồng](../docs/features/TONG_QUAN.md), [ảnh và kiểm thử](../docs/verification/DASHBOARD.md).

## Mua gói và quản trị thanh toán M03

KH vào chi tiết /goi-tap/:id, bấm **Đặt mua gói này** để tạo đơn giữ giá 15 phút. Có /khach-hang/don-hang và /khach-hang/don-hang/:id, tạo link/mở payOS/kiểm tra thanh toán; /khach-hang/goi-cua-toi hiển thị snapshot quyền lợi, thời hạn, buổi còn lại và PT phụ trách. Trở về từ payOS đọc trạng thái Backend; chưa đủ chứng cứ chỉ từ query URL.

Admin có /admin/don-hang, /admin/don-hang/:id xem khoản thu và ghi kết quả hoàn tiền thủ công; /admin/phan-cong phân công/đổi PT với phiên bản và UUID. Menu KH/Admin được thêm trong header. Options API, muaGoiService tập trung, khóa gửi trùng, bỏ response cũ khi chuyển trang/mất phiên; không đưa khóa payOS vào FE.

Toàn FE đạt 91 tests, build/lint/format. Các ảnh có dữ liệu trong [kiểm chứng M03](../docs/verification/M03_MUA_GOI.md) dùng fixture demo riêng, giữ nguyên phiên người dùng; không phải chứng cứ đã chuyển tiền. Lịch/chat/chatbot thực tế được triển khai ở module sau.
