# Tài khoản và phân quyền — phần đã triển khai

Ngày 01/10/2026, Laravel 13/Sanctum 4.3.3, Vue 3 Options API/Pinia Options Store. Phạm vi đợt này: đăng ký KH, đăng nhập/đăng xuất, đọc hồ sơ, phân quyền ba vai trò và Admin tạo PT/Admin. Quên/đặt lại mật khẩu, sửa hồ sơ và quản lý khóa tài khoản qua giao diện chưa triển khai. Tài khoản có trạng thái khác HOAT_DONG đã bị chặn đăng nhập/API.

## Use case và hợp đồng

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

Mọi POST dùng CSRF. SPA lấy GET /sanctum/csrf-cookie, gửi cookie/credentials và X-XSRF-TOKEN; không dùng bearer/localStorage để xác thực. CORS cho origin FRONTEND_URL, Sanctum nhận origin trong SANCTUM_STATEFUL_DOMAINS. Hostname FE/BE thống nhất localhost khi phát triển. [Hướng dẫn Sanctum chính thức](https://laravel.com/framework/docs/13.x/sanctum).

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

Lệnh hỏi họ tên và nhập mật khẩu ẩn hai lần; chỉ chạy khi chưa có Admin. Không có Admin/mật khẩu mặc định. Sau đó Admin đăng nhập và tạo PT/Admin tại /admin/tai-khoan. Không dùng lệnh này để tạo tài khoản từ frontend hoặc tự cấp quyền cho KH.

## Tổ chức implementation

- Backend: XacThucController/TaiKhoanController/HoSoKhachHangController; FormRequest; TaiKhoanService transaction; TaiKhoanResource; middleware trạng thái/vai trò; HoSoKhachHangPolicy.
- Frontend: services dùng Axios chung; Pinia chỉ giữ tài khoản trả từ server trong bộ nhớ. Router tải lại /me trước khi vào trang vai trò, khôi phục session sau refresh và có trang báo mất kết nối/sai quyền.
- Form đăng nhập/đăng ký và trang hồ sơ KH/PT dùng Options API. Admin có danh sách phân trang và form tạo tài khoản. Ngày sinh là DATE, hiển thị ngày/tháng/năm mà không chuyển múi giờ.

Kiểm thử và ảnh giao diện nằm tại [M01_AUTH.md](../verification/M01_AUTH.md).
