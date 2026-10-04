# Đối chiếu Stitch và ứng dụng

## Nguyên tắc

Áp dụng bố cục, thứ bậc nội dung, điều hướng và vị trí thao tác, không chỉ bảng màu. Giữ Vue 3 JavaScript Options API, Bootstrap và Bootstrap Icons. Không đưa HTML Tailwind gốc vào chạy thay ứng dụng. Không đổi endpoint, store, router hoặc phân quyền Backend trong lượt áp dụng này.

Trang chủ giữ điện thoại có ảnh giải phẫu, Dynamic Island, ba huy hiệu và hiệu ứng nghiêng hiện có. Màu chung là xanh ngọc và vàng chanh, với hai nền sáng/tối. Chi tiết dùng dữ liệu thật từ API; demo chỉ chứa dữ liệu giả trong MySQL riêng.

## Bản đồ áp dụng

Số mẫu bên dưới tương ứng tiền tố 01-32 trong [bản lưu](manifest.json). Một mẫu dùng cho nhiều đường dẫn cùng loại, gồm các biến thể KH/PT/Admin, tạo/sửa và danh sách/chi tiết.

| Mẫu | Màn hình ứng dụng | Thay đổi bố cục |
| --- | --- | --- |
| 01-06 | Tổng quan KH, PT, Admin | Điều hướng chia nhóm; thanh chỉ số; nội dung chính và vùng lịch/công việc bên cạnh; biểu đồ và bảng dùng số liệu hiện có |
| 07-08 | Khung màn hình điện thoại | Điều hướng nhanh dưới cùng, menu đầy đủ trong hộp thoại, đầu trang gọn; bảng tự cuộn riêng, không làm tràn cả trang |
| 09 | Thư viện bài tập | Thanh tìm/lọc ngang, nhóm cơ chọn nhanh, lưới ảnh 4/3/2 cột theo kích thước |
| 10 | Lịch & nhật ký tự tập KH/PT | Lịch bảy ngày, chọn tuần đồng bộ bộ lọc; danh sách và biểu đồ bên dưới, form tạo lịch bên cạnh |
| 11 | Soạn giáo án KH/PT | Bảng thông số theo bài trên máy tính, ô nhập hai cột trên điện thoại, thứ tự và ghi chú giữ nguyên; thư viện chọn bài bên cạnh |
| 12 | Chat KH/PT | Danh sách hội thoại, tin nhắn và thông tin người trao đổi thành ba vùng; điện thoại giữ chế độ danh sách/hội thoại riêng |
| 13 | Gói tập công khai/Admin | Bảng quản trị so sánh giá, thời hạn và quyền lợi; gói công khai giữ thẻ quyền lợi có thao tác đăng ký hiện có |
| 14 | Đăng nhập/đăng ký KH | Thanh thương hiệu và đổi nền trên cùng; form giữa trang, hình tập luyện bên cạnh trên máy tính |
| 15-16 | Hồ sơ và sửa hồ sơ ba vai trò | Dải nhận diện, nhóm thông tin và liên kết theo vai trò; chỉnh sửa có nội dung chính và vùng bên cạnh |
| 17 | Chỉ số KH và PT xem học viên | Biểu đồ/lịch sử bên trái, form nhập bên phải; PT vẫn chỉ xem, không có form nhập |
| 18 | Học viên PT | Bảng học viên, liên kết chỉ số/nhật ký/giáo án ngay trên hàng, tìm kiếm và phân trang giữ nguyên |
| 19 | Lịch hẹn ba vai trò | Bảng lịch và vùng xem nhanh; xác nhận/hủy/ghi nhận vẫn ở trang chi tiết hiện có |
| 20 | Khung giờ PT và đặt lịch KH | Dải ngày, danh sách khung giờ và vùng chọn/tạo; giữ kiểm tra thời gian, trạng thái và chống trùng hiện có |
| 21 | Đơn hàng KH/Admin | Bảng đơn với vùng xem nhanh; thanh toán, đối soát và hoàn tiền chỉ ở trang chi tiết có quyền tương ứng |
| 22 | Gói KH đang dùng | Dải gói/thời hạn, quyền lợi PT và AI riêng, thông tin PT và liên kết chat/lịch |
| 23 | Phân công Admin | Bảng học viên và vùng phân công/đổi PT bên cạnh, giữ cảnh báo tác động trước thao tác |
| 24 | Tài khoản Admin | Bảng danh sách toàn chiều ngang; tạo tài khoản chuyển sang hộp thoại bên phải, giữ xác nhận khóa/mở khóa |
| 25 | Tài liệu AI Admin | Thanh thống kê, danh sách bên trái, vùng soạn/sửa bên phải; lưu nháp và xác nhận xuất bản giữ nguyên |
| 26 | FAQ | Điều hướng thông tin liên quan và danh sách nội dung mở/đóng, tìm kiếm/phân trang hiện có |
| 27 | Chi tiết bài tập | Tên/nhóm cơ thành đầu trang toàn chiều ngang, minh họa và thông số/hướng dẫn thành hai vùng |
| 28 | Chi tiết gói | Nội dung quyền lợi và quy định cạnh vùng giá/đăng ký, không thay điều kiện mua |
| 29 | Biểu mẫu danh mục Admin | Nhóm nhập liệu phẳng, vùng tóm tắt/ảnh xem trước bên cạnh; quy tắc validation, xung đột phiên bản và lưu vẫn giữ |
| 30 | Chi tiết giáo án và giáo án mẫu | Chọn ngày theo ô cố định, danh sách bài gọn, hình minh họa và thông số; không tự sửa trạng thái giáo án |
| 31 | Khôi phục mật khẩu | Dùng khung xác thực mới; giữ các trạng thái gửi yêu cầu và đặt lại mật khẩu hiện có |
| 32 | Không có quyền/không kết nối | Đồng bộ nền, chữ, biểu tượng và nút xử lý trong bố cục trạng thái gọn hiện có |

## Khác biệt có chủ đích

- Không thêm 2FA, E2E, gọi video, xuất PDF, phần trăm thành công học viên, số bước, calories, vòng eo hoặc body-fat nếu API chưa hỗ trợ.
- Không dùng biểu đồ minh họa hay chỉ tiêu bài tập làm kết quả đã tập. Không tự hiển thị gói PT là “phổ biến nhất” khi không có dữ liệu chứng minh.
- Không sao chép nhận xét chuyên môn hoặc gợi ý AI tĩnh của Stitch thành kết quả tư vấn thật. Trợ lý AI giữ quota và trạng thái dịch vụ hiện có.
- Lịch tuần hiển thị các bản ghi trong trang API đã tải, có ghi rõ phạm vi và phân trang. Không suy diễn ô trống thành không có lịch trong toàn hệ thống.
- Không thêm trường tìm kiếm chung nếu không có dịch vụ tìm xuyên module. Dùng bộ lọc thật ở từng màn hình.
- Không đổi logo hiện có thành logo mẫu. Khung xác thực dùng ảnh tập luyện sẵn có thay ảnh minh họa người tập từ Stitch.
- Không thêm menu cho tác nhân thứ tư hoặc mở quyền xem chat cho Admin.

## File Chính

- `FE/src/assets/styles/stitchLayout.css`: bố cục và responsive dùng chung theo nhóm màn hình.
- `FE/src/layouts/CaNhanLayout.vue`, `XacThucLayout.vue`, `FE/src/components/MenuCaNhan.vue`: khung điều hướng/xác thực.
- Các page/component tương ứng trong bảng trên: cấu trúc template mới; service và xử lý nghiệp vụ được giữ lại.
- `FE/tests/dieuHuong.spec.js`, `nhatKyTap.spec.js`: kiểm tra nhóm menu, điều hướng nhanh, focus và tuần lịch.
- `scripts/verify-motion.cjs`, `verify-stitch-layout.cjs`: kiểm tra toàn bộ đường dẫn và tương tác mới trong demo riêng.

## Xem và kiểm tra

Bản chạy: <http://localhost:5310>. Tài khoản demo và hướng dẫn bật/tắt nằm trong [hướng dẫn ứng dụng](../fitness-motion-app/README.md).

Kết quả thực chạy, ảnh và giới hạn kiểm thử được ghi ở [verification.json](../fitness-motion-app/verification.json) và [kiểm tra bố cục/tương tác](../stitch-layout-verification/verification.json). Không coi kiểm tra thị giác là kiểm thử đầy đủ giao dịch, email, Reverb hoặc AI thật.
