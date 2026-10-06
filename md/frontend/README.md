# FE — Vue SPA

C34: chi tiết giáo án PT đã xác nhận đang lưu trữ có **Áp dụng lại giáo án** theo cờ quyền API. Xác nhận thay bản hiện tại và thông báo thành công dùng chung với giáo án tự tạo, giữ layout/theme. [Kiểm chứng](../verification/M05_AP_DUNG_LAI_PT.md).

C33: KH chỉ có một giáo án đang áp dụng bất kể nguồn; xác nhận PT hoặc tự áp dụng bản mới lưu trữ bản cũ. Chi tiết bản PT đã nhận có **Ngừng áp dụng**, không cần PT duyệt; trạng thái chuyển sang lưu trữ và giữ bài/lịch sử. [Hợp đồng](../features/KE_HOACH_TAP.md#một-giáo-án-đang-áp-dụng--ngừng-giáo-án-pt--c33), [kiểm chứng](../verification/M05_MOT_GIAO_AN.md).

C32: trong **Giáo án của tôi**, mở bản tự tạo đã hủy/lưu trữ → **Ẩn giáo án**. Bộ lọc **Hiển thị → Đã ẩn** cho phép xem và **Hiện lại giáo án**; hiện lại giữ nguyên trạng thái, không tự áp dụng. PT thấy nhãn **KH đã ẩn** nhưng chỉ đọc. [Kiểm chứng](../verification/M05_AN_GIAO_AN.md).

C31/M05: KH mở **Giáo án của tôi → Tự tạo giáo án** để chọn bài từ catalog, lưu nháp, sửa và tự áp dụng không cần gói/PT duyệt. PT mở **Học viên & giáo án → học viên** để xem bản tự tạo và trạng thái đang dùng. Danh sách lọc theo nguồn; dùng cùng layout/theme và trình soạn Options API của PT. [Hợp đồng](../features/KE_HOACH_TAP.md), [kiểm chứng](../verification/M05_TU_TAO_GIAO_AN.md).

**Trạng thái:** đã bootstrap Vue **3.5.43**, JavaScript/Options API, Vite **8.3.1**, cài dependencies và lưu package-lock.json. Đã chạy trên Node **22.20.0**, npm **10.9.3**. package.json ghi Node tương thích ^22.18.0 hoặc >=24.12.0.

Đã cài Vue Router **5.3.1**, Pinia **4.0.3**, Axios **1.20.0**, Bootstrap **5.3.8**, ESLint/Oxlint/Prettier. Pinia Options Store giữ tài khoản trả từ Backend trong bộ nhớ; không lưu token/role vào localStorage. Đã cài Laravel Echo/Pusher cho chat realtime; Chart.js sẽ được thêm khi làm thống kê tương ứng.

## Chạy Frontend

Từ FE/:

```powershell
rtk proxy npm.cmd ci
rtk proxy npm.cmd run dev
```

Mở [đăng ký](http://localhost:5173/dang-ky) hoặc [đăng nhập](http://localhost:5173/dang-nhap) khi Laravel chạy ở localhost:8000. Vite dùng port cố định 5173; hai bên dùng cùng hostname localhost. Khách tự đăng ký; PT/Admin được Admin cấp tài khoản. Admin đầu tiên tạo bằng lệnh ở [hướng dẫn tài khoản](../features/TAI_KHOAN.md).

Menu KH/PT/Admin nằm trong thanh bên dọc của `CaNhanLayout.vue`, thu gọn còn biểu tượng; dưới 1024px mở dạng ngăn menu. PT có Tổng quan, Tin nhắn, Hồ sơ PT, Học viên & giáo án, Giáo án mẫu, Khung giờ, Lịch hẹn và Thư viện bài tập. Hướng dẫn start.bat nằm tại [README chung](../README.md); bố cục menu hiện tại có [kiểm chứng sidebar](../verification/SIDEBAR_NAVIGATION.md).

Đã tạo .env local từ .env.example. VITE_API_BASE_URL được Axios instance ở src/utils/http.js đọc; component gọi qua services/xacThucService.js và taiKhoanService.js. Service lấy CSRF cookie trước POST, Axios gửi credentials/XSRF. Đổi .env cần khởi động lại Vite. Các biến VITE_* là công khai, không đặt AI key/Reverb secret vào đây. Máy này đã cấu hình Reverb local; máy mới đặt public key khớp BE theo [hướng dẫn chat](../features/REALTIME_CHAT.md).

## Kiểm tra đã chạy

```powershell
rtk proxy npm.cmd run build
rtk proxy npm.cmd run lint:check
rtk proxy npm.cmd run format:check
```

Đạt build/lint/format và 102 tests trên 10 file bằng Vitest 5.0.3 (npm run test), gồm auth/session, hồ sơ/khôi phục/khóa tài khoản và các module catalog. dist/ là kết quả build local. Đã kiểm tra trình duyệt: đăng ký/đăng nhập KH, refresh session, sai mật khẩu, chặn trang Admin, đăng xuất, Admin tạo PT và PT đăng nhập. Kiểm tra responsive 1440/768/390px. Chưa thêm bộ E2E chạy tự động cho Vue. [Bằng chứng tài khoản](../verification/M01_AUTH.md).

## Cấu trúc và phạm vi

Trang đăng nhập/đăng ký tại views/XacThuc, hồ sơ KH/PT dùng chung views/CaNhan, quản trị tại views/Admin/TaiKhoan. components/TruongNhap và layouts dùng chung; page dùng data/computed/methods. Pinia chỉ giữ auth; form/loading/errors nằm ở component. Router tải /me trước trang cần vai trò; Backend vẫn quyết định quyền.

Đã có luồng tài khoản và màn hình theo vai trò; hồ sơ mới chưa có mục tiêu/chuyên môn hiển thị Chưa cung cấp. Có [danh mục bài tập](http://localhost:5173/bai-tap) công khai và chi tiết tại /bai-tap/:id, tìm kiếm/lọc/phân trang theo URL, ảnh/GIF từ Backend và ghi công nguồn. Code tại views/BaiTap, services/baiTapService.js, components/AnhBaiTap.vue, layouts/DanhMucLayout.vue. Mỗi component giữ trạng thái cục bộ, hủy request cũ và chặn kết quả tới muộn. GIF chỉ tải khi bấm xem, có nút dừng.

Đã có `/admin/bai-tap` để tìm/lọc/phân trang, thêm/sửa và bật/tắt hiển thị. Biểu mẫu tại `/admin/bai-tap/them` và `/admin/bai-tap/:id/sua`, Vue Options API; service riêng `baiTapAdminService.js`, trạng thái form cục bộ. Khi lưu lỗi giữ nội dung; khi 409 chặn gửi lại và cho tải bản mới. Chỉ ADMIN được vào trang; API vẫn kiểm tra quyền ở server.

Toàn bộ frontend đạt **102 Vitest tests**, build/lint/format. Có bảng giá `/goi-tap`, chi tiết `/goi-tap/:id`; Admin quản lý tại `/admin/goi-tap`, thêm `/admin/goi-tap/them`, sửa `/admin/goi-tap/:id/sua`. Options API/service dùng chung, bộ lọc URL, loading/empty/error/409, khóa gửi trùng, giữ UUID khi retry mạng và xử lý 409. Admin tự nhập giá/quyền lợi; không có giá giả hoặc cấp gói từ UI. [Hợp đồng gói](../features/GOI_TAP.md), [kiểm chứng](../verification/M02_GOI_TAP.md).

Đã có mua/thanh toán gói M03. Chưa có upload media, chatbot. Đã có chat realtime KH/PT tại /khach-hang/tin-nhan và /pt/tin-nhan; [kiểm chứng M07](../verification/M07_CHAT.md). Tham khảo [CODE_STYLE.md](../CODE_STYLE.md) và [quyết định](../DECISIONS.md); repository không còn page mẫu `.example`, nên đối chiếu page/service cùng loại trong `FE/src/` và test tương ứng.

## Giáo án mẫu Admin/PT

Admin có `/admin/giao-an-mau`, `/admin/giao-an-mau/them`, `/admin/giao-an-mau/:id/sua`; PT có `/pt/giao-an-mau`, `/pt/giao-an-mau/:id`. Trình soạn chọn bài từ API đang hoạt động, phân trang 6 bài/lần, thêm/bỏ/đổi thứ tự trong từng ngày, nhập hiệp/lặp/nghỉ/ghi chú; lưu nháp rồi duyệt/ngừng. Sửa bản duyệt về nháp, giữ form khi 422/409/mất mạng. PT đọc và mở hướng dẫn bài; M05 đã có tạo giáo án cá nhân từ mẫu đã duyệt. Vue JavaScript Options API, state cục bộ và service chung. [Hợp đồng](../features/GIAO_AN_MAU.md), [kiểm chứng](../verification/M02_GIAO_AN_MAU.md).

## Quản lý nhóm cơ Admin

Danh sách tại `/admin/nhom-co`, form thêm `/admin/nhom-co/them`, sửa `/admin/nhom-co/:id/sua`. Tìm/lọc/phân trang theo URL, xem tổng bài và số đang hiển thị, liên kết bài theo nhóm; ngừng/khôi phục có xác nhận ảnh hưởng. Form giữ mã/tên nguồn, lỗi cạnh trường, không gửi trùng, bảo vệ bản cũ và giữ nội dung khi lỗi. Backend quyết định quyền/trạng thái; không cần seed lại. Đã kiểm tra desktop/tablet/mobile, toàn FE đạt 79 tests và build/lint/format. [Hợp đồng](../features/NHOM_CO.md), [kiểm chứng](../verification/M02_NHOM_CO.md).

## Sửa hồ sơ và khôi phục mật khẩu M01
KH/PT/Admin có trang hồ sơ và sửa tại /khach-hang/ho-so/sua, /pt/ho-so/sua, /admin/ho-so/sua. KH nhập mục tiêu, kinh nghiệm, ngày sinh, giới tính và thời gian tập; PT nhập chuyên môn/giới thiệu; Admin sửa họ tên. Dữ liệu hiển thị từ Backend, không có thông số demo giả. Form giữ bản nháp khi lỗi; bản cũ 409 yêu cầu tải lại rõ ràng trước khi lưu.

Trang /quen-mat-khau gửi yêu cầu khôi phục; /dat-lai-mat-khau đọc token từ fragment của liên kết email rồi xóa khỏi URL. Nếu tải lại trang sau khi fragment bị xóa, cần mở lại liên kết email. Token chỉ giữ trong component, không localStorage. Admin khóa/mở tài khoản tại /admin/tai-khoan với hộp thoại xác nhận và phiên bản cập nhật.

Đạt 79 frontend tests, build/lint/format; đã kiểm tra hồ sơ và khôi phục ở desktop 1440, tablet 768, mobile 390. Backend kiểm tra quyền/CSRF/phiên, frontend không tự cấp quyền. [Kiểm chứng M01 bổ sung](../verification/M01_HO_SO_KHOI_PHUC.md).

## Trang chủ và dashboard
Người chưa đăng nhập vào / thấy trang giới thiệu. Router chờ /me trước khi chuyển người đã đăng nhập về /khach-hang/tong-quan, /pt/tong-quan hoặc /admin/tong-quan; áp dụng cả truy cập trực tiếp, refresh và URL cũ có hash. Đăng nhập/đăng ký thành công vào tổng quan, hồ sơ có link riêng trong menu. Nếu không xác minh được session do mất kết nối, hiển thị trang lỗi kết nối, không giả coi người dùng là guest.

Dashboard KH/PT có thống kê thư viện và checklist mức đầy đủ hồ sơ; Admin có tổng tài khoản, phân bố vai trò/trạng thái và các danh mục. Thống kê lấy từ API thật, không lưu cache cá nhân vào localStorage. Trang tại views/TongQuan, service tongQuanService; loading/error/retry, hủy request và bỏ qua kết quả tới muộn. Menu KH/PT/Admin nằm trong thanh bên dọc, không lặp trong nội dung; danh mục Gói tập/Thư viện và trang chi tiết cũng giữ header theo vai trò khi đăng nhập. Khi KH/PT mở chi tiết bài tập, mục Thư viện bài tập vẫn được đánh dấu đang mở. Trên màn hình nhỏ menu mở dạng ngăn bên cạnh, không làm tràn toàn trang. [Hợp đồng](../features/TONG_QUAN.md), [kiểm chứng dashboard nghiệp vụ](../verification/README.md).

## Mua gói và quản trị thanh toán M03

KH vào chi tiết /goi-tap/:id, bấm **Đặt mua gói này** để tạo đơn giữ giá 15 phút. Có /khach-hang/don-hang và /khach-hang/don-hang/:id, tạo link/mở payOS/kiểm tra thanh toán; /khach-hang/goi-cua-toi hiển thị snapshot quyền lợi, thời hạn, buổi còn lại và PT phụ trách. Trở về từ payOS đọc trạng thái Backend; chưa đủ chứng cứ chỉ từ query URL.

Admin có /admin/don-hang, /admin/don-hang/:id xem khoản thu và ghi kết quả hoàn tiền thủ công; /admin/phan-cong phân công/đổi PT với phiên bản và UUID. Menu KH/Admin nằm trong thanh bên. Options API, muaGoiService tập trung, khóa gửi trùng, bỏ response cũ khi chuyển trang/mất phiên; không đưa khóa payOS vào FE.

Toàn FE đạt 102 tests, build/lint/format. Các ảnh có dữ liệu trong [kiểm chứng M03](../verification/M03_MUA_GOI.md) dùng fixture demo riêng, giữ nguyên phiên người dùng; không phải chứng cứ đã chuyển tiền. Lịch PT M04 đã triển khai; chat/chatbot là module sau.

## Lịch huấn luyện M04

KH đặt lịch tại /khach-hang/dat-lich, xem/hủy ở /khach-hang/lich-hen và /khach-hang/lich-hen/:id; có nút đặt lịch từ Gói của tôi. PT mở/đóng giờ tại /pt/khung-gio, xử lý yêu cầu/kết quả tại /pt/lich-hen và /pt/lich-hen/:id. Admin xem/đóng quá hạn ở /admin/lich-hen và /admin/lich-hen/:id. Navigation có Lịch hẹn; Options API, service Axios chung, filter URL/paging, confirmation/lý do, loading/empty/error và bỏ response cũ. Giờ hiển thị/chuyển đổi theo Asia/Ho_Chi_Minh. [Hợp đồng](../features/LICH_HUAN_LUYEN.md), [ảnh QA và kết quả](../verification/M04_LICH_HUAN_LUYEN.md).

## Chat KH/PT M07

Đã triển khai Tin nhắn trong menu và bảng xem nhanh trên header, danh sách/tìm kiếm/phân trang, lịch sử/soạn/gửi lại, badge chưa đọc và đồng bộ Reverb. Frontend hiện đạt116tests; kiểm chứng API/socket/trình duyệt và cấu hình nằm tại [M07_CHAT.md](../verification/M07_CHAT.md).

Bổ sung gửi ảnh: nút ảnh, xem trước/bỏ ảnh, kéo thả và Ctrl+V, chú thích tùy chọn, grid và xem lớn. Tối đa 4 JPG/PNG/WebP, 5 MB/ảnh. Retry giữ File/UUID; hủy upload/tải ảnh và dọn blob khi rời trang/mất phiên. FE sau phần ảnh đạt 146 tests; [kiểm chứng](../verification/CHAT_IMAGES.md).

Chat giữ toàn bộ chiều rộng và chiều cao desktop520–820px; thu gọn riêng thanh soạn theo mẫu Zalo. Thanh công cụ và hàng nhập/Gửi khoảng102px; textarea một dòng44px tự giãn tối đa116px, thu lại khi xóa/gửi. Preview56px chỉ hiện khi có ảnh. Đã sửa gửi chữ bị validation ảnh rỗng ở FE/BE; thêm kiểm thử payload HTTP thực tế. FE đạt149tests, lint/build và format các file thay đổi PASS; [thanh soạn mới](../verification/CHAT_COMPOSER.md). Không cần migration cho các thay đổi này.

## Chuông và lối tắt tin nhắn trên header

Biểu tượng tin nhắn KH/PT hiện mở bảng xem nhanh hội thoại, bấm từng người mới mở chat; có “Xem tất cả tin nhắn”. Bảng tối đa sáu dòng, đồng bộ dữ liệu thật, không đánh dấu đọc chỉ vì xem preview. [Kiểm chứng bảng xem nhanh](../verification/HEADER_MESSAGE_PREVIEW.md). FE sau cập nhật đạt 132 tests và build/lint/format PASS.

Chuông KH/PT/Admin có danh sách phân trang, đánh dấu đọc và số chưa đọc từ Backend. KH/PT có biểu tượng vào chat; Admin không đọc chat riêng. Có trạng thái trống/lỗi, Escape/bấm ngoài, dark/light và điện thoại. M05 đã gắn thông báo giáo án mới/xác nhận áp dụng; các sự kiện khác chưa bật; [phạm vi và đề xuất](../features/NOTIFICATIONS.md), [kiểm chứng thông báo nghiệp vụ](../verification/M09_THONG_BAO.md). Sau bổ sung bảng xem nhanh: 132 tests và build/lint/format PASS.

## Menu dọc có thể thu gọn

Khung nội dung KH/PT/Admin tại `CaNhanLayout.vue` đã bỏ giới hạn 1.240px, dùng hết chiều rộng còn lại bên cạnh menu. Giữ padding 32px desktop, 24px tablet và 16px hai bên mobile; chat giữ khoảng đệm riêng. QA PT1920px: main bằng stage (khoảng1.641px mở menu, 1.821px thu gọn), không tràn ngang; mobile390px không tràn. Build và format phần thay đổi PASS; thay CSS nên không thêm test nghiệp vụ. Bằng chứng `docs/verification/content-full-width.png`, `content-full-width-mobile.png`.

Logo FitForge của chủ dự án thay chữ H ở trang chủ/header/footer, layout xác thực, danh mục công khai và menu KH/PT/Admin. Component dùng chung `src/components/LogoThuongHieu.vue` tự chọn ảnh theo Pinia chuDe: light dùng ảnh trước `public/images/logo-fitforge.png`, dark dùng ảnh mới `public/images/logo-fitforge-dark.png`. Hai ảnh giữ nguyên bản, nền trong suốt và object-fit contain; 56px ở thương hiệu công khai/xác thực, 48px trong sidebar mở/thu gọn. Kiểm tra trình duyệt PT chuyển dark → light → dark và tải lại, src đổi đúng và ảnh tải được; bằng chứng tại `docs/verification/logo-theme-dark.png`, `logo-theme-light.png`; phần mở/thu gọn ở `logo-sidebar.png`, `logo-sidebar-collapsed.png` là trước khi bổ sung hai biến thể. Sau cập nhật logo theo theme: FE 146 tests, build/lint/format PASS; không thay avatar tài khoản.

KH/PT/Admin có thanh bên riêng theo vai trò, giữ bố cục khi mở thư viện bài tập hoặc gói tập. Nút ở góc trái header mở/thu gọn menu; tùy chọn được lưu trên trình duyệt. Header giữ đổi theme, chuông, tin nhắn KH/PT và avatar. Bấm avatar để xem hồ sơ hoặc đăng xuất. Admin không có chat riêng theo phạm vi đã chốt. [Kiểm chứng giao diện](../verification/SIDEBAR_NAVIGATION.md).

## Giáo án cá nhân M05

PT vào `/pt/hoc-vien`, chọn học viên rồi tạo tại `/pt/hoc-vien/:khachId/ke-hoach/them`; sửa nháp `/pt/ke-hoach/:id/sua`, xem/gửi/hủy `/pt/ke-hoach/:id`. KH vào `/khach-hang/ke-hoach` và `/:id` để xem/xác nhận trong 24 giờ. Các trang giữ menu dọc/theme; service `keHoachTapService`, state form cục bộ, chống gửi trùng và giữ UUID/payload khi lỗi mạng. Toàn FE đạt 158 tests, lint/build và format các file thay đổi. [Hợp đồng](../features/KE_HOACH_TAP.md), [kiểm chứng và giới hạn trình duyệt](../verification/M05_KE_HOACH_TAP.md).

Chi tiết giáo án cá nhân KH/PT dùng bố cục tương ứng giáo án mẫu: tóm tắt, thanh chọn ngày, thẻ bài tập gọn và cửa sổ hướng dẫn có bật/dừng GIF. Hướng dẫn lấy từ snapshot giáo án, giữ nút nghiệp vụ theo quyền. FE 182 tests, lint/build/format PASS; [kiểm chứng desktop, mobile và dark theme](../verification/PLAN_DETAIL_LAYOUT.md).

## Lịch và nhật ký M06

KH mở `/khach-hang/lich-tap` từ menu; PT chọn học viên rồi mở `/pt/hoc-vien/:khachId/lich-tap`. Chi tiết `/khach-hang/lich-tap/:id` hoặc `/pt/lich-tap/:id`: kết quả thực tế tách chỉ tiêu, lưu nháp, xác nhận hoàn thành/hủy ngay trong trang; PT đọc và nhận xét riêng. Biểu đồ/tổng số từ API, không có dữ liệu giả. Hỗ trợ light/dark và mobile, lỗi giữ draft, UUID ổn định khi retry, phiên bản mới sau lưu. Toàn FE182tests, lint/build/format PASS; [hợp đồng](../features/NHAT_KY_TAP.md), [ảnh và kiểm chứng](../verification/M06_NHAT_KY_TAP.md).
