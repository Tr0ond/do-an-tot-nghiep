# Fitness Motion - Bộ mẫu giao diện sáng/tối

Ngày: 04/10/2026. Thiết kế bằng Stitch MCP theo yêu cầu chủ dự án: hiện đại, dễ dùng, năng động; hai nền sáng/tối với xanh ngọc + vàng chanh.

## Xem thiết kế

- [Trang xem và đổi nền/ vai trò](index.html), mở trực tiếp bằng trình duyệt, không cần chạy server.
- [Dự án Stitch](https://stitch.withgoogle.com/projects/10304506651660798975).
- [Danh sách nguồn màn hình](screens.json).

| Màn hình | Sáng | Tối |
| --- | --- | --- |
| Tổng quan Khách hàng | [Ảnh](kh-light.png) / [HTML](kh-light.html) | [Ảnh](kh-dark.png) / [HTML](kh-dark.html) |
| Tổng quan PT | [Ảnh](pt-light.png) / [HTML](pt-light.html) | [Ảnh](pt-dark.png) / [HTML](pt-dark.html) |
| Tổng quan Admin | [Ảnh](admin-light.png) / [HTML](admin-light.html) | [Ảnh](admin-dark.png) / [HTML](admin-dark.html) |
| Khách hàng trên điện thoại | [Ảnh](mobile-light.png) / [HTML](mobile-light.html) | [Ảnh](mobile-dark.png) / [HTML](mobile-dark.html) |

## Quy chuẩn thiết kế

| Token | Sáng | Tối |
| --- | --- | --- |
| Nền | `#F5F7F8` | `#111719` |
| Bề mặt | `#FFFFFF` | `#1A2326` |
| Chữ chính | `#182325` | `#F3F8F7` |
| Chữ phụ | `#526568` | `#B4C4C3` |
| Xanh ngọc | `#087F75`, chữ trắng | `#4ED8C5`, chữ `#062F2A` |
| Vàng chanh | `#D6EF52`, chữ `#263000` | `#D6EF52`, chữ `#263000` |
| Thông tin/lịch PT | `#2864D7` | `#89B4FF` |

Font Be Vietnam Pro, letter spacing 0; thang khoảng cách 8px; bo góc panel tối đa 8px. Chuyển động đề xuất 160-220ms ở hover, focus, đổi trạng thái; tôn trọng giảm chuyển động. Vàng chanh làm điểm nhấn chọn lọc, không phủ toàn bộ màn hình.

Khách hàng ưu tiên buổi tự tập hôm nay, ghi nhật ký, giáo án đang áp dụng, lịch PT và quyền lợi gói. PT ưu tiên lịch, yêu cầu chờ xác nhận, học viên phụ trách và nhận xét nhật ký. Admin ưu tiên tiền đã nhận/đã hoàn/sau hoàn, khoản cần đối soát, phân công và ngoại lệ cần xử lý. Lịch tự tập, lịch PT, số buổi PT và lượt AI là các thông tin riêng.

Điện thoại dùng một cột và điều hướng dưới màn hình; giữ lối vào các mục còn lại qua Thêm. Khi triển khai cần bảo đảm mọi vùng bấm tối thiểu 44px, focus rõ, nội dung không bị thanh điều hướng hoặc mascot che.

## Phạm vi và giới hạn

Đây là **8 bản mẫu của 4 bố cục tổng quan**, chưa phải thiết kế chi tiết toàn bộ các màn hình trong hệ thống. Chưa áp dụng vào `FE/src/`, chưa thay endpoint hay nghiệp vụ, chưa có dữ liệu thật hoặc thao tác gửi form. Tên, số liệu và hình ảnh là dữ liệu minh họa; không phải báo cáo vận hành.

HTML xuất từ Stitch dùng Tailwind CDN, Google Fonts/Material Symbols và ảnh mạng để xem mẫu. Đây là tài liệu tham khảo; khi đưa vào hệ thống phải dùng Vue 3 JavaScript Options API, dịch vụ hiện có, theme store hiện có và Bootstrap Icons theo quy ước dự án. Không chuyển framework hoặc đưa dependency CDN của bản mẫu vào runtime mặc định. Khi triển khai bài tập phải ánh xạ đúng media catalog hiện có; ảnh minh họa Stitch chưa được xác nhận đúng từng động tác.

`preview-polish.css` / `preview-polish.js` chỉnh bản xuất để bảng Admin không chật cột, khôi phục biểu đồ tiền nhận/hoàn bị mất khi xuất HTML, bỏ nhãn chứng nhận bảo mật không có căn cứ, bỏ hành động điểm danh ngoài phạm vi, đồng bộ yêu cầu lịch chờ và khoảng cách chữ. Các chỉnh sửa này chỉ áp dụng cho bản xem cục bộ, có thể khác lịch sử bản mẫu trên Stitch. Những bản thử và bản cũ trên Stitch được giữ để không xóa tài sản. Ảnh điện thoại đặt thanh điều hướng ở cuối bản vẽ dài; trong HTML, thanh này cố định ở đáy viewport.

Ảnh được kiểm tra trực quan ở cả hai nền. Kiểm tra trang xem và bản HTML thực tế bằng Chromium/Playwright được ghi tại [verification.json](verification.json). Đây là kiểm tra bản thiết kế, không phải kết quả test ứng dụng Laravel/Vue hoặc nghiệm thu WCAG toàn bộ.

Các màn hình chi tiết còn cần triển khai theo cùng quy chuẩn: xác thực, thư viện bài tập/gói, giáo án và biểu mẫu, lịch hẹn/khung giờ, nhật ký/chỉ số, chat PT/AI, hồ sơ và CRUD quản trị. Cần thiết kế trạng thái rỗng, tải, lỗi, hết quyền/hết gói theo từng luồng khi áp dụng thực tế.
