# Kiểm chứng danh mục bài tập — 01/10/2026

Báo cáo này ghi nhận mốc danh mục công khai. Phần quản trị bài tập được triển khai tiếp nối; xem [kiểm chứng Admin](M02_ADMIN_BAI_TAP.md) cho trạng thái và kết quả kiểm thử mới hơn.

Phần bài tập của M02 đã có NhomCo/BaiTap, BaiTapSeeder, FormRequest/resources/API công khai và giao diện danh sách/chi tiết. [Hợp đồng](../features/BAI_TAP.md). Không đổi schema/Draw.io. Chưa có CRUD Admin, gói dịch vụ hay giáo án mẫu.

## Môi trường và kiểm tra thực sự đã chạy

Windows, PHP 8.4.0, Laravel 13.34.0, MariaDB 10.4.32 qua driver mysql; Node 22.20.0, Vue 3 Options API/JavaScript, Vite 8.3.1, Vitest 5.0.3. Database ứng dụng local duantotnghiep. Feature tests tự tạo database ngẫu nhiên riêng, migrate/transaction và chỉ dọn database do chính lớp test sở hữu, không refresh database ứng dụng.

| Kiểm tra | Kết quả |
| --- | --- |
| php artisan test | 36 tests/3.040 assertions PASS; 8 ca mới của BaiTapTest |
| npm run test | 22 tests PASS; 15 ca mới về query/media và yêu cầu cũ tới muộn/hủy/mất mạng/đổi bài |
| npm run build, lint:check, format:check | PASS |
| Pint các file PHP thay đổi | PASS |
| node scripts/kiemTraDuLieu.mjs | 1.324 bài, 19 nhóm, 2.648 media nguyên bản/SHA-256, schema/Draw.io PASS |
| php artisan db:seed --class=BaiTapSeeder | Đã nhập catalog vào DB ứng dụng local, giữ tài khoản |
| php scripts/kiemTraCatalogDatabase.php | Đối chiếu toàn bộ 1.324 bài với JSON: nội dung, nhóm, JSON 10 ngôn ngữ, thời điểm nguồn DATETIME(6), đường dẫn media PASS |
| HTTP localhost:8000 | 1.324 bài, mặc định 12/trang, 111 trang; bộ lọc 19 nhóm/28 dụng cụ |

Backend tests kiểm tra ánh xạ nhóm khi ID DB khác JSON, nhập lại giữ bản biên tập/trạng thái, rollback khi nhập lỗi; GET không cần session; bài/nhóm ngừng dùng không xuất hiện trong danh sách/chi tiết/bộ lọc; tìm tên Việt/tên gốc/mã nguồn, nhóm+dụng cụ kết hợp; escape wildcard LIKE và SQL input chỉ là văn bản; validation query dạng mảng, giới hạn phân trang, empty/404, ưu tiên vi/fallback en. Model giữ phần mili/micro giây của thời điểm nguồn.

Frontend tests dùng Vitest Node, gọi Options API methods với service giả lập và utilities; đây không phải test DOM hoặc E2E tự động. Kiểm tra giao diện dưới đây chạy trên trình duyệt thật.

## Trình duyệt

- Danh sách công khai và ảnh đúng origin Backend.
- sit-up + Cơ bụng + Trọng lượng cơ thể trả 11 kết quả, URL phản ánh bộ lọc.
- Chi tiết 3/4 sit-up có 5 bước tiếng Anh, nhãn chưa có bản Việt; bật GIF tải đúng localhost:8000, dừng về ảnh.
- Quay lại danh sách và reload giữ bộ lọc/kết quả.
- Từ khóa không có kết quả hiển thị empty state, đặt lại về toàn bộ catalog.
- Trang 2 có URL page=2, chỉ báo 2/111, bài đầu assisted prone hamstring; có nút về trang trước.
- Đã đọc console trước khi kết thúc kiểm tra: không có warning/error. Tìm kiếm và chuyển trang cuộn về khu vực kết quả, có trạng thái focus cho điều khiển bàn phím.
- Desktop 1440×900, tablet 768×1024, mobile 390×844: ảnh/thẻ/nội dung dài đọc được, không tràn ngang (document width 753/375 tại viewport 768/390).

Ảnh: [desktop](m02-danh-sach-desktop.jpg), [chi tiết desktop](m02-chi-tiet-desktop.jpg), [tablet](m02-danh-sach-tablet.jpg), [mobile](m02-danh-sach-mobile.jpg), [thẻ mobile](m02-the-bai-tap-mobile.jpg), [chi tiết mobile](m02-chi-tiet-mobile.jpg).

## Chạy lại và giới hạn

Từ BE:

```powershell
rtk proxy php artisan migrate
rtk proxy php artisan db:seed --class=BaiTapSeeder
rtk proxy php artisan test
```

Từ gốc: `rtk proxy php scripts/kiemTraCatalogDatabase.php`, script chỉ đọc và báo khác nguồn khi có biên tập. Từ FE: npm run test/build/lint:check/format:check. Xem [danh mục](http://localhost:5173/bai-tap).

Chưa kiểm thử tranh chấp seed đồng thời trên MySQL thật; chạy seeder tuần tự. Nội dung chưa được dịch/biên tập toàn bộ sang Việt hoặc xác nhận chuyên môn. Chưa thử lỗi mạng/ảnh hỏng bằng thao tác trình duyệt; có xử lý ở component, mất mạng có Vitest. Giữ ghi công © Gym visual và LICENSE/NOTICE nguồn.

Frontend-design dùng media thật và nhận diện hiện có. Ui-ux-pro-max fitness library gợi ý marketing showcase, retry reference catalog thiên về tài liệu; chưa có match hoàn toàn cho thư viện động tác nên không lưu design-system. Bố cục dùng quy tắc chung của skill và màu/font dự án. Query Vue filter khớp hướng dẫn tránh v-if/v-for cùng phần tử.
