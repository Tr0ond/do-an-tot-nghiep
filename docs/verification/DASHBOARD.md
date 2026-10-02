# Kiểm chứng trang chủ và dashboard — 02/10/2026

Theo [yêu cầu/hợp đồng](../features/TONG_QUAN.md): giữ landing page hiện có cho người chưa đăng nhập, đưa KH/PT/Admin đã đăng nhập về dashboard khi bấm Trang chủ hoặc logo. Không thay đổi quy tắc kinh doanh, không thêm migration, seed hoặc dependency.

## Thay đổi

- FE: [kiểm tra điều hướng](../../FE/src/router/kiemTraDieuHuong.js), router, Pinia getter đích sau đăng nhập, CaNhanLayout/DanhMucLayout và [dashboard Options API](../../FE/src/views/TongQuan/index.vue), [service API](../../FE/src/services/tongQuanService.js). Root chờ /me trước khi quyết định màn hình; redirect replace loại bỏ hash landing cũ. Loading/error/retry, bỏ dữ liệu khi mất phiên/đăng xuất, hủy request cũ và bỏ response tới muộn. Hồ sơ vẫn có mục riêng, menu nhỏ cuộn ngang trong nav.
- BE: [TongQuanController](../../BE/app/Http/Controllers/Api/TongQuanController.php), ba GET routes auth/active/role. Chỉ Admin được trả thống kê tài khoản/các danh mục toàn DB; KH/PT được checklist hồ sơ chính mình và số catalog đang hiển thị; PT chỉ thấy số giáo án đã duyệt. Dùng scopes đang có để thống kê bài và gói hợp lệ; nhóm ngừng không làm bài xuất hiện trong số công khai. Không đọc hội thoại hoặc hồ sơ người khác, không ghi DB.
- Hồ sơ đầy đủ tính trên 6 mục KH, 3 mục PT; % là độ đầy đủ thông tin, không phải tiến độ thể chất. Admin có số theo trạng thái/role, toàn DB thay vì số dòng trên trang danh sách.

## Kiểm tra đã chạy

Windows, PHP 8.4.0, Laravel 13.34.0, MariaDB 10.4.32; Vue 3.5.43, Vite 8.3.1, Vitest 5.0.3.

| Kiểm tra | Kết quả |
| --- | --- |
| php artisan test --compact --filter=TongQuanTest | 5 tests, 52 assertions, PASS |
| php artisan test --compact | 98 tests, 4.237 assertions, PASS |
| Pint cho controller/routes/test mới | PASS |
| npm run test | 79 tests, 8 files, PASS |
| npm run build | PASS, 155 modules |
| npm run lint:check | PASS |
| npm run format:check | PASS |

Backend [TongQuanTest](../../BE/tests/Feature/TongQuanTest.php) tạo database ngẫu nhiên riêng, migrate, transaction và chỉ drop DB do lớp tạo. Đã kiểm tra guest 401, vai trò sai 403, người bị khóa 403; thống kê toàn DB đúng trạng thái; KH chỉ hồ sơ chính mình dù query chứa user ID/vai trò giả; PT không nhận số nháp/Admin; nhóm/bài/gói ngừng hoặc không hợp lệ không được tính công khai; dữ liệu rỗng trả 0, không tạo dữ liệu giả.

FE [tongQuan.spec.js](../../FE/tests/tongQuan.spec.js) bổ sung 12 ca: root khôi phục session cho ba vai trò; chờ response trước redirect, session hết hạn, URL trái quyền, mất kết nối không giả guest, trang đăng ký/login khi đã đăng nhập; 0/độ đầy đủ hồ sơ; hủy và bỏ request tới muộn, unmount, 401 xóa dữ liệu và mạng lỗi retry. Bộ FE còn lại chạy cùng và đạt. Chưa có E2E tự động.

## Trình duyệt thật local

Chạy Laravel localhost:8000 và Vue localhost:5173. Dùng tài khoản demo sẵn có, không tạo/chỉnh hồ sơ hoặc gói. Guest root vẫn giữ landing. KH đăng nhập tự vào tổng quan; bấm Trang chủ từ danh mục bài tập hoặc logo quay lại đúng dashboard; reload vẫn khôi phục session và giữ đúng trang. Admin đăng nhập vào thống kê, mở trực tiếp /#tinh-nang được chuyển về /admin/tong-quan. PT vào tổng quan riêng và logo giữ đúng vai trò. Đăng xuất rồi Về trang chủ trở lại landing guest. Cuối kiểm thử đăng xuất demo, reset viewport override và đóng tab QA do agent tạo.

Đã quan sát KH/Admin ở 1440×900, 768×1024 và 390×844; PT ở desktop/mobile. Không tràn ngang toàn trang hoặc chữ đè nhau; menu nhỏ có vùng cuộn riêng. Có dữ liệu thực local: 4 tài khoản (2 KH/1 PT/1 Admin), 1.324 bài, 19 nhóm, 1 gói đang bán, 5 giáo án đã duyệt. KH demo có 1/6 mục hồ sơ (17%), PT demo 1/3 (33%). Log lỗi trình duyệt của tab QA trả rỗng khi kiểm tra. Không mô phỏng doanh thu, số buổi hoàn thành hoặc calories.

| Dashboard | Desktop | Tablet | Mobile |
| --- | --- | --- | --- |
| Khách hàng | [1440px](dashboard-khach-hang-desktop.png) | [768px](dashboard-khach-hang-tablet.png) | [390px](dashboard-khach-hang-mobile.png) |
| Admin | [1440px](dashboard-admin-desktop.png) | [768px](dashboard-admin-tablet.png) | [390px](dashboard-admin-mobile.png) |
| PT | [1440px](dashboard-pt-desktop.png) | — | [390px](dashboard-pt-mobile.png) |

[Trang chủ khi chưa đăng nhập](dashboard-khach-chua-dang-nhap.png).

## Giới hạn và cách xem

Chỉ thống kê các chức năng M01/M02 đã triển khai. Doanh thu/payOS, buổi PT, lịch, nhật ký và biểu đồ tiến độ chưa có luồng dữ liệu nghiệp vụ; bổ sung theo M03–M06, không coi M09 hoàn tất. Kiểm thử DB trên MariaDB; chưa chạy MySQL thật hoặc đo tải. Các số đếm là dữ liệu hiện tại, không báo cáo tăng trưởng theo thời gian.

Chạy ứng dụng theo [BE README](../../BE/README.md), [FE README](../../FE/README.md). Không cần migrate/seed mới cho dashboard. Đăng nhập rồi bấm Trang chủ/logo hoặc mở [localhost:5173](http://localhost:5173/); route chọn đúng dashboard theo tài khoản server. KH/PT/Admin không thể dùng query để xem số liệu của vai trò khác.

## Bổ sung: menu KH/Admin trên header

Theo yêu cầu tiếp theo ngày 02/10/2026, sửa [CaNhanLayout](../../FE/src/layouts/CaNhanLayout.vue) để chuyển menu KH/Admin từ nội dung lên header và bỏ nút điều hướng trùng. [DanhMucLayout](../../FE/src/layouts/DanhMucLayout.vue) dùng cùng header khi KH/Admin đã đăng nhập; khôi phục session khi mở trực tiếp danh mục và giữ ghi nguồn minh họa. PT giữ vị trí menu hiện có. Không thay đổi Backend hoặc quy tắc phân quyền.

Đã quan sát header tại desktop 1440px, tablet 768px và mobile 390px. Desktop có menu cùng hàng; màn hình nhỏ có menu ở hàng thứ hai trong header, cuộn ngang riêng, không tràn ngang toàn trang. Đã bấm Gói tập/Thư viện trong phiên KH local và reload danh mục để xác minh header vẫn giữ đúng. Giữ nguyên phiên đăng nhập đang có của chủ dự án, không sửa tài khoản/dữ liệu.

Ảnh dưới chụp component thật với tài khoản demo trong bản xem trước độc lập, router chỉ ở bộ nhớ và không gọi API. Admin được kiểm tra bố cục/active link trong bản xem trước; không đăng nhập Admin thật ở lượt này. Không dùng dữ liệu cá nhân trong ảnh. Đã xóa các file xem trước, reset viewport và đóng tab QA; log lỗi tab QA trả rỗng. Chạy lại 79 tests FE trên 8 file đạt; build (155 modules), lint và format đạt; không chạy lại Backend vì chỉ sửa layout.

| Header | Desktop | Tablet | Mobile |
| --- | --- | --- | --- |
| Khách hàng | [1440px](header-khach-hang-desktop.png) | [768px](header-khach-hang-tablet.png) | [390px](header-khach-hang-mobile.png) |
| Admin | [1440px](header-admin-desktop.png) | [768px](header-admin-tablet.png) | [390px](header-admin-mobile.png) |

Tải lại trang đang chạy để xem header mới; không cần cài thêm thư viện hoặc migrate/seed.
