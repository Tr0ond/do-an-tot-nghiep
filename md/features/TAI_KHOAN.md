# Tài khoản và phân quyền — phần đã triển khai

Cập nhật 02/10/2026, Laravel 13/Sanctum 4.3.3, Vue 3 Options API/Pinia Options Store. Đã có đăng ký KH, đăng nhập/đăng xuất, đọc/sửa hồ sơ của chính mình theo ba vai trò, Admin tạo PT/Admin và khóa/mở khóa tài khoản, khôi phục mật khẩu qua email. Tài khoản có trạng thái khác HOAT_DONG bị chặn đăng nhập/API. SMTP local đã xác thực với STARTTLS; chưa gửi email thật để xác nhận giao nhận. Bằng chứng bổ sung tại [M01_HO_SO_KHOI_PHUC.md](../verification/M01_HO_SO_KHOI_PHUC.md).

## Use case và hợp đồng

### Bổ sung M01 ngày 02/10/2026

- `PUT /api/v1/me/ho-so`: tài khoản hoạt động sửa hồ sơ của chính mình. Nhận `ho_ten`, `updated_at`; KH thêm `muc_tieu`, `kinh_nghiem`, `gioi_tinh`, `ngay_sinh`, `thoi_gian_co_the_tap` (mảng tối đa 14 ghi chú, mỗi ghi chú 120 ký tự); PT thêm `chuyen_mon`, `gioi_thieu`. Admin chỉ sửa tên. Không sửa email, mật khẩu, vai trò, ID hoặc trạng thái qua hồ sơ. Ngày sinh không ở tương lai; các mô tả tối đa 255 ký tự, giới thiệu PT tối đa 5.000. Ghi cả tài khoản/hồ sơ trong transaction, khóa tài khoản và đối chiếu phiên bản micro giây; bản cũ 409, giữ bản nhập ở FE.
- `PATCH /api/v1/admin/tai-khoan/{id}/trang-thai`: ADMIN hoạt động gửi `trang_thai` = `HOAT_DONG` hoặc `BI_KHOA` và `updated_at`. Không tự khóa; khóa hai tài khoản theo ID tăng dần, kiểm tra lại người thao tác còn hoạt động/quyền. Khóa đổi dấu phiên đăng nhập, xóa token khôi phục và thu hồi các phiên; mở khóa yêu cầu đăng nhập mới. Không xóa lịch sử hoặc thay phân công/gói/kế hoạch. No-op giữ phiên bản; stale 409. Không có DELETE hoặc sửa vai trò.
- `POST /quen-mat-khau`: khách gửi email chuẩn hóa; broker Laravel gửi liên kết về `FRONTEND_URL/dat-lai-mat-khau` có token/email trong fragment (không gửi token trên URL request tới Vite). Kết quả chung cho email không tồn tại, bị khóa, đã gửi gần đây; token không trả qua JSON/log. Giới hạn 5/phút/email+IP và 20/phút/IP; broker 60 giây. Mailer log không được dùng cho liên kết bí mật; SMTP phải cấu hình, thiếu dịch vụ trả 503 chung.
- `POST /dat-lai-mat-khau`: khách gửi email, token, password/password_confirmation; mật khẩu 8–72 ký tự và không quá 72 byte. Token hết hạn sau 60 phút, lưu hash và chỉ dùng một lần. Khóa tài khoản rồi token, broker kiểm tra lại trong transaction; thất bại rollback mật khẩu/token/thu hồi phiên. Tài khoản bị khóa không thể dùng reset để mở khóa. Thành công không tự đăng nhập; các phiên cũ và remember cookie mất hiệu lực. Sai/hết hạn/đã dùng trả 422 chung; giới hạn 20/phút/IP.
- Mọi ghi từ SPA web yêu cầu CSRF, từ chối trường ngoài hợp đồng; mật khẩu không đi vào URL. Phiên đăng nhập được gắn dấu của hash mật khẩu + remember token khi đăng ký/đăng nhập; Middleware so sánh trên mọi API bảo vệ. Các phiên cũ chưa có dấu cần đăng nhập lại khi cập nhật này được triển khai. NULL phiên bản chỉ hợp lệ nếu record cũ trong DB còn NULL. Native bearer theo hợp đồng MB1 bên dưới.


| Hành động | Actor/quyền | Endpoint | Trạng thái/kết quả |
| --- | --- | --- | --- |
| Đăng ký | Khách chưa đăng nhập; chỉ tạo KH | POST /dang-ky | Tạo tai_khoan + ho_so_khach_hang trong một transaction; tự đăng nhập, đổi session ID; 201 |
| Đăng nhập | Cả ba vai trò, tài khoản HOAT_DONG | POST /dang-nhap | Xác minh mật khẩu, đổi session ID; 200 |
| Đăng xuất | Có session web | POST /dang-xuat | Logout, invalidate session, đổi CSRF token; 200 |
| Đọc tài khoản | Có session + HOAT_DONG | GET /api/v1/me | Tài khoản và hồ sơ KH/PT của chính mình |
| Đọc hồ sơ KH | KH sở hữu, PT có phân công hiện tại, Admin | GET /api/v1/khach-hang/ho-so/{id} | Policy kiểm tra quyền tài nguyên; sai quyền 403, không tồn tại 404 |
| Đọc hồ sơ PT | Chỉ vai trò PT | GET /api/v1/pt/ho-so | Tài khoản và hồ sơ PT hiện tại |
| Danh sách tài khoản | Admin hoạt động | GET /api/v1/admin/tai-khoan?page=1 | Phân trang 20 bản ghi, metadata |
| Tạo PT/Admin | Admin hoạt động | POST /api/v1/admin/tai-khoan | Tạo tài khoản; PT có hồ sơ riêng; session Admin đang thao tác không đổi |

Mọi POST từ SPA web dùng CSRF. SPA lấy GET /sanctum/csrf-cookie, gửi cookie/credentials và X-XSRF-TOKEN; không dùng bearer/localStorage để xác thực. CORS cho origin FRONTEND_URL, Sanctum nhận origin trong SANCTUM_STATEFUL_DOMAINS. Hostname FE/BE thống nhất localhost khi phát triển. [Hướng dẫn Sanctum chính thức](https://laravel.com/framework/docs/13.x/sanctum).

MB1 native dùng `POST /api/v1/mobile/dang-nhap|dang-xuat` và bearer cho `/me`/`/me/ho-so`, không gửi cookie/Origin web. MB5 bổ sung ba endpoint đăng ký/quên/đặt lại mật khẩu; nhóm `/mobile` loại khỏi middleware SPA stateful, web giữ CSRF. Chỉ KH/PT hoạt động, hạn cố định 30 ngày, nhiều thiết bị, logout phiên hiện tại; khóa/reset xóa mọi token cũ cùng thu hồi session. Migration `000043` bổ sung bảng hash token ở MB1; MB5 không thêm migration. Xem [hợp đồng mobile](../mobile/XAC_THUC.md), [MB1](../verification/MOBILE_MB1.md), [MB5](../verification/MOBILE_MB5.md). Đăng ký chỉ tạo KH, không tự đăng nhập; reset dùng broker một lần/60 phút, không trả mã trong JSON. Quyền/validation/version hồ sơ không đổi.

Payload đăng ký:

```json
{
  "ho_ten": "Nguyễn Minh An",
  "email": "khach@example.test",
  "password": "MatKhauViDu123!",
  "password_confirmation": "MatKhauViDu123!"
}
```

Admin tạo tài khoản dùng cùng payload và thêm vai_tro = HUAN_LUYEN_VIEN hoặc ADMIN. POST đăng nhập chỉ nhận email/password. Email được trim và chuyển chữ thường; tên tối đa 255 ký tự, email tối đa 191, mật khẩu ít nhất 8 ký tự và tối đa 72 byte theo giới hạn bcrypt. Mật khẩu không được gửi trong URL. Trường vai_tro/trang_thai/tai_khoan_id bị cấm khi khách đăng ký; server đặt KHACH_HANG/HOAT_DONG.

Response tuân thủ status/message/data; không trả password/remember_token. Lỗi có code/errors. Validation 422, chưa đăng nhập 401, sai quyền hoặc tài khoản bị khóa 403, xung đột session đã đăng nhập 409, CSRF 419, vượt rate limit 429; lỗi 500 trả thông báo chung. Đăng nhập sai/email không tồn tại/tài khoản bị khóa dùng cùng thông báo để tránh lộ trạng thái email.

## Quyền và bảo toàn dữ liệu

- Khách chỉ đọc hồ sơ có tai_khoan_id trùng tài khoản xác thực. Không lấy quyền từ role/ID FE gửi.
- PT chỉ đọc KH có phân công tới hồ sơ PT của mình, bat_dau_luc <= hiện tại và chưa kết thúc. Đóng phân công hoặc phân công tương lai không cấp quyền. Admin được đọc hồ sơ KH theo phạm vi quản lý; quy tắc này không cấp quyền đọc chat riêng.
- Middleware kiểm tra trạng thái tại mỗi API được bảo vệ; khóa tài khoản làm mất quyền ở request tiếp theo và invalidate session hiện tại. Không xóa tài khoản/lịch sử khi khóa.
- UNIQUE email chặn cả trường hợp validation đã qua nhưng request khác vừa tạo trước; service chuyển lỗi UNIQUE email thành 422. Tạo hồ sơ thất bại rollback tài khoản. Không thay cấu trúc 28 bảng.
- Nút submit bị vô hiệu khi đang gửi; backend vẫn validate và có UNIQUE. Đăng nhập giới hạn 5 lần/phút theo email+IP, thêm 20 lần/phút theo IP; đăng ký 10 lần/giờ/IP.

## Tạo Admin đầu tiên

Từ BE, chạy:

```powershell
rtk proxy php artisan tai-khoan:tao-admin email-cua-ban@example.com
```

Lệnh hỏi họ tên và nhập mật khẩu ẩn hai lần; chỉ chạy khi chưa có Admin. Sau đó Admin đăng nhập và tạo PT/Admin tại /admin/tai-khoan. Không dùng lệnh này để tạo tài khoản từ frontend hoặc tự cấp quyền cho KH.

Để thử giao diện local, đã có [TaiKhoanSeeder](../backend/SEEDERS.md) tạo 3 tài khoản demo Admin/PT/KH khi chạy `php artisan db:seed`. Seeder chỉ chạy ở local/testing, tạo hồ sơ đúng vai trò và không ghi đè dữ liệu khi chạy lại. Trùng email khác vai trò sẽ rollback. Lệnh tạo Admin đầu tiên sẽ từ chối khi seeder đã tạo Admin demo.

## Tổ chức implementation

- Backend: XacThucController/TaiKhoanController/HoSoKhachHangController; FormRequest; TaiKhoanService transaction; TaiKhoanResource; middleware trạng thái/vai trò; HoSoKhachHangPolicy.
- Frontend: services dùng Axios chung; Pinia chỉ giữ tài khoản trả từ server trong bộ nhớ. Router tải lại /me trước khi vào trang vai trò, khôi phục session sau refresh và có trang báo mất kết nối/sai quyền.
- Form đăng nhập/đăng ký và trang hồ sơ KH/PT dùng Options API. Admin có danh sách phân trang và form tạo tài khoản. Ngày sinh là DATE, hiển thị ngày/tháng/năm mà không chuyển múi giờ.

Kiểm thử và ảnh giao diện nằm tại [M01_AUTH.md](../verification/M01_AUTH.md).
