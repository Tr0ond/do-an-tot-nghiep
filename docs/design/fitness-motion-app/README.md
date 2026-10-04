# Tr0ond Fitness: giao diện ứng dụng

## Xem kết quả

- [Bộ ảnh toàn bộ màn hình](index.html): lọc theo vai trò, sáng/tối, máy tính/điện thoại; bấm ảnh để xem đầy đủ.
- Bản ứng dụng đang chạy: <http://localhost:5310>.
- [Dự án Stitch](https://stitch.withgoogle.com/projects/10304506651660798975).
- [32 mẫu gốc đã duyệt, lưu cả HTML và PNG](../stitch-approved/index.html).
- [Đối chiếu mẫu với bố cục triển khai](../stitch-approved/APPLIED.md).
- [Nguồn mẫu Stitch bổ sung](stitch-sources.json). Đây là tham chiếu thị giác, không phải đặc tả nghiệp vụ.

Tài khoản demo: `kh@hanh-trinh.example.test`, `pt@hanh-trinh.example.test`, `admin@hanh-trinh.example.test`. Mật khẩu chung: `Demo123456!`. Đây là dữ liệu giả trong database MySQL riêng, không phải tài khoản thật.

## Phạm vi triển khai

Áp dụng vào Vue 3 JavaScript Options API hiện có, giữ Bootstrap và Bootstrap Icons. Bao phủ 71 đường dẫn trong router, gồm các trang danh sách, chi tiết, tạo/sửa, xác thực và lỗi. Danh mục công khai cũng được kiểm tra trong khung điều hướng của từng vai trò.

| Nhóm | Màn hình |
| --- | --- |
| Công khai | Trang chủ, thư viện và chi tiết bài tập, danh sách và chi tiết gói, FAQ |
| Xác thực | Đăng nhập, đăng ký KH, quên/đặt lại mật khẩu, không có quyền, không kết nối |
| Khách hàng | Tổng quan, hồ sơ và sửa hồ sơ, chỉ số, giáo án danh sách/tạo/sửa/chi tiết, lịch và nhật ký/ghi kết quả, tin nhắn, chatbot, lịch hẹn/chi tiết/đặt lịch, đơn hàng/chi tiết, gói đang dùng |
| PT | Tổng quan, hồ sơ, học viên, chỉ số và nhật ký học viên, giáo án học viên/tạo/sửa/chi tiết, giáo án mẫu/chi tiết, khung giờ, lịch hẹn/chi tiết, tin nhắn |
| Admin | Tổng quan, tài khoản, hồ sơ, phân công PT, lịch hẹn, đơn hàng/đối soát, tài liệu và AI, danh mục bài tập/nhóm cơ/gói/giáo án mẫu cùng biểu mẫu tạo/sửa |

Các module thay đổi: `FE/src/assets/styles/fitnessMotion.css` (token và điều khiển chung), `stitchLayout.css` (bố cục triển khai từ mẫu), `homeMotion.css`, `main.css`, các stylesheet giáo án/lịch/nhật ký/chat/chatbot/mua gói; layout và template trong component/page tương ứng. Danh sách học viên/phân công/gói quản trị chuyển sang bảng; đơn/lịch có khung xem nhanh; hồ sơ/chỉ số/giáo án/chat/tài liệu AI được sắp xếp lại. Không đổi router, service API, store, phân quyền hoặc nghiệp vụ Backend.

### Nhận diện

| Token | Sáng | Tối |
| --- | --- | --- |
| Nền | `#F5F7F8` | `#111719` |
| Bề mặt | `#FFFFFF` | `#1A2326` |
| Chữ | `#182325` | `#F3F8F7` |
| Chữ phụ | `#526568` | `#B4C4C3` |
| Xanh ngọc | `#087F75` + chữ trắng | `#4ED8C5` + chữ `#062F2A` |
| Vàng chanh | `#D6EF52` + chữ `#263000` | Giữ nguyên |
| Thông tin | `#2864D7` | `#89B4FF` |

Font Be Vietnam Pro, sidebar 232px, header 64px, điều khiển chính tối thiểu 44px, bo góc phần lớn 6-8px, hỗ trợ reduced motion. Chế độ sáng/tối vẫn lưu theo tùy chọn hiện có.

Điện thoại trang chủ giữ nguyên markup, ảnh `exercise-anatomy.png`, Dynamic Island, ba huy hiệu Calories/Heart/Streak và hàm xử lý rê chuột. File `cinematicDark.css` và ảnh điện thoại không thay đổi. Chỉ chỉnh vùng đặt và tỷ lệ hiển thị trên điện thoại; tạm dừng animation khi rê chuột để hiệu ứng nghiêng tương tác hiện rõ. Các con số trên hình điện thoại là minh họa, không phải dữ liệu cảm biến thật.

Trang chủ và xác thực bỏ các đánh giá, chứng chỉ và cam kết bảo mật tuyệt đối không có căn cứ. Không đưa tuyên bố mã hóa E2E do Stitch tự sinh vào ứng dụng. Quyền lợi buổi PT và lượt AI/ngày vẫn tách biệt; Admin không được thêm quyền đọc chat.

## Kiểm tra đã chạy

- `npm run test`: 27 bộ kiểm thử, 262 kiểm thử đạt, gồm nhóm menu, điều hướng nhanh, focus và tuần lịch bổ sung.
- `npm run lint:check`, `npm run format:check`, `npm run build`: đạt.
- [verification.json](verification.json): Chrome thực, MySQL demo riêng; 344 lượt xem gồm 86 tổ hợp vai trò/trang, mỗi tổ hợp ở sáng/tối và 1440x900 / 390x844.
- Không phát hiện lỗi JavaScript, ảnh hỏng hoặc tràn ngang trong các lượt kiểm tra. Kiểm tra màu tự động bắt các trường hợp tương phản dưới 3:1 và đã sửa các trường hợp phát hiện; không coi đây là chứng nhận WCAG toàn bộ.
- Điện thoại có bitmap không trắng, đủ ba huy hiệu, thay đổi transform khi rê chuột; menu di động mở/đóng; đổi nền còn giữ sau tải lại.
- [Kiểm tra bố cục/tương tác](../stitch-layout-verification/verification.json): kiểm tra thêm máy tính bảng 768px, form tài khoản, biên tập AI, xem nhanh đơn/lịch, nhập thông số giáo án, đổi tuần và focus menu dưới cùng. Kiểm tra hash đủ 64 file Stitch nguyên bản.

Đây là kiểm tra trình bày và một số tương tác, không phải kiểm thử đầy đủ mọi trạng thái nghiệp vụ. Chưa thực hiện giao dịch payOS thật, gửi email thật, gọi Gemini hay kiểm thử Reverb end-to-end trong bản demo. Chat demo sử dụng tải lại/polling. Không chạy lại kiểm thử Backend vì không thay đổi Backend.

## Mở lại và dừng demo

Từ thư mục gốc, chạy `pwsh -File scripts/design-preview.ps1` để mở demo riêng; chạy thêm `-Stop` để dừng đúng hai tiến trình đã tạo và xóa đúng database demo. Không thay `.env`, không dừng server có sẵn.

Tạo lại ảnh: `rtk proxy node scripts/verify-motion.cjs` (chỉ hoạt động với demo riêng). Chụp lại không tạo dữ liệu: thêm `--refresh`. Tạo lại bộ xem ảnh: `rtk proxy node scripts/build-motion-gallery.cjs`. Kiểm tra tương tác bổ sung: `rtk proxy node scripts/verify-stitch-layout.cjs`. Playwright dùng Chrome và runtime sẵn có; không cài thư viện mới.

Các mẫu Stitch bổ sung được sinh ở nền sáng, dùng cặp token tối làm tham chiếu; bộ ảnh Vue thực tế có đầy đủ hai nền cho tất cả đường dẫn. Bộ 8 prototype trước đó nằm riêng tại `../stitch-fitness-motion/`, không thay thế kết quả ứng dụng này.
