# FIGMA IMPLEMENTATION REPORT

Ngày thực hiện và kiểm chứng: 05–06/10/2026. Phạm vi: giao diện Mobile hiện tại, không thay Backend Laravel hoặc frontend Vue.

## Figma MCP

- Connected: OAuth đã xác minh trước khi đọc thiết kế. Không kiểm tra lại kết nối trong bước bàn giao.
- `get_design_context`: đã gọi đúng file Make `GlIleXhkEGzqOjmdVnA6rP`, node `0:1`; bị chặn bởi hạn mức Starter. Không nhận được design context từ lần gọi này.
- Variables: không lấy được variables qua MCP. Giá trị màu/theme, typography và kích thước được đọc trực tiếp từ CSS và component trong bản xuất người dùng cung cấp.
- Assets: dùng SVG hai vòng tròn/Dumbbell của bản xuất, đúng Lucide 1.52.0, đúng font Be Vietnam Pro 400/500/600/700/800 và media gốc của project. Không dùng emoji thay các icon giao diện. Emoji chào ở trang tổng quan vốn có trong bản xuất.
- Nguồn triển khai: `C:/Users/xtung/Downloads/Redesign Tr0ond Fitness App.zip`, xuất từ [Figma Make](https://www.figma.com/make/GlIleXhkEGzqOjmdVnA6rP/Redesign-Tr0ond-Fitness-App). Chủ dự án đã cung cấp ZIP để dùng thay cho context đang bị hạn mức; không dùng skill thiết kế để tạo kiểu mới.

## Implementation

Stack giữ nguyên: Expo SDK 57, React Native 0.86.3, JavaScript, React Navigation 7. Bản React/Tailwind trong ZIP chạy riêng làm tham chiếu; không thay ứng dụng bằng prototype hoặc nhập store dữ liệu giả của prototype.

### Screen

| Nhóm | Giao diện đã áp dụng |
| --- | --- |
| Tài khoản | Đăng nhập, đăng ký, khôi phục/đặt lại mật khẩu, hồ sơ, sửa hồ sơ, Light/Dark/System, phiên hiện tại |
| Tổng quan | KH và PT; thẻ lịch/gói, số liệu thật, truy cập nhanh, tiến độ |
| Giáo án | Danh sách, chi tiết, soạn/sửa, thư viện mẫu, catalog bài tập, hướng dẫn và chọn bài |
| Tự tập | Danh sách sắp tới/đã tập, tạo lịch, chi tiết, ghi hiệp, kết quả, nhận xét của HLV |
| Chỉ số | Thẻ cân nặng/chiều cao/BMI, biểu đồ dữ liệu thật, lịch sử, bảng nhập chỉ số |
| Lịch PT | Theo ngày/tuần/lịch sử, đặt lịch, chi tiết và các hành động theo quyền backend |
| Gói và đơn | Danh sách/chi tiết gói, mua gói qua API hiện có, danh sách/chi tiết đơn, trạng thái và link payOS thật |
| Trao đổi | Danh sách chat, hội thoại, ảnh riêng, thông báo, trạng thái đồng bộ hiện có |
| Trợ lý AI | Danh sách hội thoại, quota, consent, hội thoại, form yêu cầu giáo án và mở nháp thật |
| PT | Học viên, hồ sơ học viên, giáo án/tự tập của học viên, khung giờ, giáo án mẫu |

### Files modified / Components

44 file được chỉnh và 11 file được tạo so với bản sao lưu trước lần triển khai này. Danh sách đầy đủ: [files-modified.json](figma-mobile/files-modified.json). `package.json` và `package-lock.json` không đổi so với bản sao lưu đó; các dependency cần thiết đã có trong project.

- Components modified: `GiaoDien`, `HuanLuyen`, `TapLuyen`, `GoiTap`; theme, điều hướng, context bản xem; các màn trong bảng trên.
- Components created: `IconFigma`, `KhungTaiKhoan`, `ThanhDieuHuong`, `FigmaElements`, `TongQuanFigma`, `MinhHoaBaiTap`, `CongTacFigma`, `SoBuocFigma`.
- Hook created: `useHieuUngFigma` cho fade/slide và cài đặt giảm chuyển động.
- Screens created: `CaNhan/GiaoDien`, `CaNhan/PhienDangNhap`.
- `hoanThienService`: bổ sung tham số lọc trạng thái đơn tương thích với lời gọi cũ, dùng endpoint và pagination hiện có.
- `useDuLieu`: cho phép màn chỉ số giữ dữ liệu đọc dưới bảng nhập trong suốt; vẫn hủy request và bỏ phản hồi cũ theo lifecycle. Khi màn được focus lại sẽ tải dữ liệu/quyền mới.
- Các trường/nút nghiệp vụ bổ sung được giữ trong bảng tùy chọn của màn khi không có vị trí tương ứng trong mẫu, thay vì xóa chức năng.

Mapping được lập trước khi sửa: [FIGMA_MAKE_MOBILE_MAPPING.md](../design/FIGMA_MAKE_MOBILE_MAPPING.md).

## Functionality preserved

- Authentication: giữ API đăng nhập/đăng ký/reset, SecureStore, xử lý phiên hết hạn, phiên 30 ngày và logout thiết bị hiện tại. Đăng ký không tự đăng nhập khi backend không có hành vi đó.
- Navigation: giữ stack KH/PT, quyền theo tài nguyên và deep link thông báo được kiểm tra; vẫn có bản xem demo.
- API: giữ services và nguồn dữ liệu thật; không thay API bằng store mô phỏng của Make. Filter/pagination sử dụng tham số server; không tải toàn bộ dữ liệu không lọc để dựng UI.
- Validation: giữ email, giới hạn mật khẩu theo byte, dữ liệu lịch/giáo án/hiệp tập, thời hạn reset, giới hạn yêu cầu AI.
- Ghi dữ liệu: giữ UUID retry, chống bấm trùng, `updated_at`, cảnh báo mất thay đổi, xác nhận và lỗi 403/409. Không cấp quyền từ styling, router hoặc local state.
- Thanh toán/chat/AI: giữ link payOS hợp lệ, ảnh chat riêng và reconnect; consent AI không được tự bật. Không gửi câu hỏi AI, tạo thanh toán hoặc ghi dữ liệu khách hàng để lấy screenshot.

Đây là kết quả đọc source và kiểm thử hiện có, không phải chứng nhận E2E cho tất cả thao tác ghi sau redesign.

## Tests actually run

Môi trường: Windows, Node/Expo của project, Chrome headless và Expo Go trên LDPlayer/Android `emulator-5554`.

| Kiểm tra | Kết quả thực tế | Phạm vi |
| --- | --- | --- |
| `npm test` trong Mobile | PASS: 39/39, không skip | Auth/validation, lifecycle, UUID/retry, lịch, giáo án, chat/ảnh/socket, quyền và giới hạn AI/payOS |
| Kiểm tra AST/import/JSX | PASS: 74 file, `issues: []` | Cú pháp, import, theme, nội dung text hợp lệ trong React Native |
| `expo export --platform android --platform web` | PASS sau chỉnh sửa cuối | Android Hermes và web, 38 assets; không phải bản APK cài đặt |
| Playwright auth/reference | Không có `pageerror` trong các lần kiểm tra thành công | Đăng nhập, đăng ký, quên mật khẩu, validation rỗng, hiện/ẩn mật khẩu; không gửi đăng ký/email thật |
| Đo layout đăng nhập | PASS: sai lệch 0 ở x/y/width/height của cả hai input | [auth-geometry.json](figma-mobile/auth-geometry.json) |
| Đọc API bằng tài khoản demo KH/PT | Login/tổng quan/chỉ số/giáo án trả 200 trong kiểm tra trước đó; token kiểm tra được thu hồi | Không chứng minh mọi màn PT đã kiểm tra trực quan trên Android |
| Android tài khoản KH hiện có | Đã mở và chụp màn đọc, bảng tùy chọn, theme, kết quả đã hoàn thành; cold restart vẫn mở app | Không chạy thao tác đặt lịch/mua gói/gửi AI/hoàn thành buổi tập để tạo dữ liệu mới |

Project không có script lint hoặc typecheck riêng; không báo hai kiểm tra này PASS. Không chạy lại kiểm thử Backend/MySQL vì lần này không thay nghiệp vụ/backend.

## Visual verification

- Figma viewport: bản Make xuất ra chạy ở **390 × 844 CSS px**.
- App viewport: bản web auth **390 × 844 CSS px**. Android đặt **390 × 868 px, density 160**, trừ thanh trạng thái hệ điều hành 24px còn vùng app **390 × 844** để đối chiếu.
- Screenshots compared: xem [chỉ mục ảnh](figma-mobile/README.md). Dữ liệu thật và dữ liệu mẫu được ghi rõ; không dùng ảnh cùng kích thước làm bằng chứng mọi pixel đều khớp.
- Iterations completed: **ít nhất 4 vòng** implement → chạy → chụp → so sánh → sửa. Các vòng sửa gồm layout/theme/auth; khoảng trống scroll/nav; header/footer/date/chat/profile/sheet; SVG và khoảng đệm kết quả/chỉ số. Không phải mọi màn đều có đủ bốn ảnh riêng.
- Kiểm tra chính xác: input email x=24, y=256.5, width=342, height=48; input mật khẩu x=24, y=346, width=342, height=48 ở cả bản xuất và app web. Đây là phép đo giới hạn ở hai input.
- Bảng nhập chỉ số: nguồn có top y=459, Android top y=483; sau trừ 24px OS đều y=459. Input y=630/654 và nút lưu y=772/796 tương ứng cũng trùng vị trí. Đây là đối chiếu thủ công screenshot ở trạng thái bàn phím đóng.
- Đã sửa opacity SVG trên Android: chuyển opacity 0.12 vào nhóm vòng tròn, tránh bị áp dụng hai lần ở root; giữ geometry SVG bản xuất. Đã chụp lại kết quả sau sửa.
- Nút bánh răng xám nổi bên phải là Expo Go Tools, không phải thành phần của ứng dụng và được loại khỏi phần đánh giá giao diện.
- Sau kiểm tra đã trả độ phân giải LDPlayer về thiết lập trước đó: physical 1280 × 720, density override 230.

## Remaining differences / Limits

Không tuyên bố “100% match” cho toàn bộ app.

1. **Nội dung thật:** tên, lịch, số liệu, độ dài mã đơn, ngày và nhận xét khác fixture trong Make. Mã đơn thật dài có thể xuống dòng; không cắt hoặc thay mã nghiệp vụ để giống mã demo ngắn. Ảnh/hướng dẫn catalog hiện có và attribution được giữ, gồm nội dung tiếng Anh từ nguồn gốc.
2. **Biểu đồ chỉ số:** app giữ các điểm đo thật dạng scatter theo hợp đồng hiện tại, trong khi Make có đường nội suy. Không tự thêm nội suy dữ liệu chưa được nghiệp vụ chấp nhận. BMI hiển thị “Tham khảo”, không thêm kết luận phân loại sức khỏe từ mẫu.
3. **Trường/quyền thật:** form còn trường bắt buộc như nghỉ giữa hiệp và thông tin hồ sơ; các phần bổ sung nằm trong bảng tùy chọn. Consent AI, giới hạn tạo giáo án, phiên hiện tại, deadline và quyền hành động dựa trên backend nên có thể khác các nút/trạng thái mô phỏng trong Make.
4. **Thông báo quan trọng:** giữ bảng thông báo cần người dùng đóng; không đổi thành toast tự biến mất khiến mất thông tin lỗi/quy tắc. Giữ thao tác ảnh chat và một số liên kết tiến độ/thông tin thật bên cạnh giao diện mẫu.
5. **PT và trạng thái ghi/lỗi:** đã chuyển giao diện nhưng chưa kiểm tra trực quan mọi nhánh trên Android dưới phiên PT, cũng chưa chạy E2E các thao tác ghi, thanh toán hoặc AI có phí. Không coi test helper/services là thay thế cho kiểm tra này.
6. **Nền tảng:** font/SVG có khác biệt làm tròn và khử răng cưa giữa Chrome và Android. Safe area phụ thuộc thiết bị. Chưa kiểm chứng trên điện thoại vật lý hoặc mọi kích thước; chưa xác nhận pixel-perfect responsive toàn ứng dụng.
7. **Theme:** lựa chọn sáng/tối/hệ thống áp dụng khi app đang mở, như cơ chế hiện có; chưa lưu lựa chọn qua lần khởi động.
8. **Nguồn MCP:** chưa thể xác minh node/variables/asset trực tiếp trong file Figma vì hạn mức. Kết quả dựa trên ZIP được chủ dự án cung cấp.

Các mục trên được giữ để không thay business logic hoặc bịa dữ liệu; không trình bày chúng như đã khớp thiết kế hoàn toàn.

## Cách xem/chạy

LDPlayer đang mở Expo Go với app ở `exp://192.168.1.15:8086`. Tiến trình kiểm tra native dùng backend LAN cổng 8001; `.env.local` trong Mobile không bị sửa. Backend gốc cổng 8000 vẫn được giữ.

- Bản xem web UI: [localhost:8083](http://localhost:8083/). Web không thay cho kiểm tra đăng nhập thật vì cơ chế bảo vệ SecureStore hiện có yêu cầu thiết bị native.
- Bản tham chiếu ZIP: [127.0.0.1:5174](http://127.0.0.1:5174/).
- Khi mở lại theo cấu hình thông thường: chạy backend LAN đang cấu hình và `npm start` trong `Mobile/`, rồi mở địa chỉ Expo hiện ra bằng Expo Go. Cổng runtime có thể đổi nếu các phiên Expo cũ vẫn chạy.
- Bản sao lưu trước sửa: `C:/Users/xtung/.codex/tmp/tr0ond-mobile-before-figma.zip`.
- Bundle kiểm tra cuối: `C:/Users/xtung/.codex/tmp/tr0ond-final-export`.

Không commit, tạo PR, build APK hoặc phát hành ứng dụng trong lần triển khai này. Các thay đổi BE/FE/tài liệu có sẵn trước phiên không thuộc báo cáo thay đổi UI này.
