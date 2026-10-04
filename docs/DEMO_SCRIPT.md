# Kịch bản demo hành trình KH–PT–Admin

Ngày kiểm tra: 04/10/2026. Demo tạo database riêng trên MySQL/MariaDB đang cấu hình, dùng dữ liệu giả và không chỉnh `BE/.env` hay dữ liệu dự án chính.

## Mở và đóng demo

Yêu cầu: MySQL/MariaDB đang chạy; đã cài dependencies BE/FE; `php`, `node` và PowerShell 7 có trong PATH. Tài khoản database cần quyền tạo/xóa database test. Nếu đã cache cấu hình Laravel, chạy `php artisan config:clear` trong BE trước.

Từ thư mục gốc dự án:

```powershell
pwsh -NoProfile -File .\scripts\start-demo.ps1
```

Giữ cửa sổ này mở, truy cập **http://localhost:5302**. Backend riêng chạy ở 8022. Nếu cổng bận, chọn cổng khác:

```powershell
pwsh -NoProfile -File .\scripts\start-demo.ps1 -BackendPort 8023 -FrontendPort 5303
```

Bấm **Enter trong cửa sổ chạy demo** khi kết thúc: script dừng hai server của demo và xóa database riêng. Server chính 8000/5173 vẫn chạy. Không đóng cưỡng bức cửa sổ trước bước dọn dữ liệu.

Nếu cửa sổ bị đóng cưỡng bức, dừng đúng server demo theo cổng đã chọn; sau đó trong BE dùng tên database được script in lúc mở:

```powershell
php tests/Support/hanh-trinh-demo.php drop kiem_tra_hanh_trinh_demo_<16_ky_tu_hex>
```

Thay phần tên bằng tên thật. Công cụ chỉ cho xóa tên khớp tiền tố demo và 16 ký tự hex.

## Tài khoản giả

Mật khẩu chung: **Demo123456!**. Các tài khoản này chỉ tồn tại trong database demo.

| Email | Vai trò và dữ liệu ban đầu |
| --- | --- |
| kh@hanh-trinh.example.test | KH đã mua gói, có PT, giáo án đang áp dụng và lịch tự tập hôm nay |
| tu-tap@hanh-trinh.example.test | KH chưa mua gói, chưa có PT; dùng để tự tạo giáo án |
| cho-pt@hanh-trinh.example.test | KH đã mua gói, đang chờ Admin phân công PT |
| pt@hanh-trinh.example.test | PT đang phụ trách KH đầu tiên |
| pt2@hanh-trinh.example.test | PT thứ hai để thử phân công/đổi PT |
| admin@hanh-trinh.example.test | Admin quản trị và xem báo cáo |

Catalog có 1.324 bài tập. Gói demo có 4 buổi PT, 10 lượt AI/ngày, hạn 30 ngày. Hai khoản thanh toán **giả lập** đã xác minh qua dịch vụ nghiệp vụ, tổng 198.000đ; không có chuyển tiền ngân hàng. KH đầu tiên có số đo 70kg → 69kg, chiều cao 175cm.

## Thứ tự trình diễn

1. **KH có gói:** đăng nhập `kh`, xem dashboard, gói 4/4 buổi và BMI. Mở buổi hôm nay → Bắt đầu ghi kết quả → thêm ít nhất một hiệp cho mỗi bài, nhập số lần → Lưu nháp → Hoàn thành buổi → Đồng ý. Dashboard có một buổi tự tập hoàn thành; lượt PT vẫn 4/4.
2. **PT:** đăng nhập `pt`, mở Học viên & giáo án → KH Demo kh → Lịch & nhật ký → buổi vừa hoàn thành. Xem số liệu và thêm nhận xét. Nhận xét được lưu riêng, không sửa kết quả KH.
3. **KH:** đăng nhập lại `kh`, mở chuông thông báo → nhận xét của PT. Kiểm tra nội dung và đường dẫn vào buổi tập; đánh dấu đọc.
4. **Admin:** đăng nhập `admin`, vào Tổng quan hệ thống, chọn khoảng báo cáo có ngày hôm nay. Tiền nhận là 198.000đ giả lập. Mở phân công PT, giao KH `Demo cho-pt` cho `Demo pt2`; KH/PT có thông báo tương ứng.
5. **KH miễn phí:** đăng nhập `tu-tap`, tự tạo giáo án, chọn bài và thông số, lưu rồi áp dụng. Lên lịch tự tập hôm nay, ghi kết quả. Không cần mua gói hay PT duyệt.
6. **Giáo án:** thử áp dụng bản khác rồi ngừng/áp dụng lại bản đã lưu. Mỗi KH chỉ có một giáo án đang áp dụng; lịch sử nhật ký vẫn giữ.

Lịch PT phải tuân thủ thời gian đặt/hủy/hoàn thành đang áp dụng. Không đổi thời gian máy hoặc bỏ kiểm tra thời gian để trình diễn. Kiểm thử tích hợp bên dưới dùng đồng hồ kiểm thử để chứng minh hoàn thành sau buổi và retry chỉ trừ một lượt.

## Kiểm thử tích hợp có thể chạy lại

Trong BE:

```powershell
php artisan test --compact --filter=HanhTrinhNghiepVuTest
```

Bộ kiểm thử tự tạo và dọn database riêng; bao phủ mua gói → phân công → giáo án → nhật ký → lịch PT → hoàn thành → báo cáo, quyền truy cập, đổi PT, hết hạn, đối soát/hoàn tiền giả lập và nháp AI 12 buổi. Xem [bằng chứng và giới hạn](verification/HANH_TRINH_NGHIEP_VU.md).

## Giới hạn demo

Demo không gọi payOS, Gemini hay gửi email thật. Chat dùng tải lại/polling, không mở Reverb riêng. Chatbot báo chưa sẵn sàng trong demo; bộ kiểm thử dùng phản hồi AI giả lập để kiểm chứng cấu trúc, hạn mức và chống trùng. Chuyển tiền thật, chất lượng Gemini thật và realtime trên môi trường triển khai cần nghiệm thu riêng.
