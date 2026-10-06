# Xác thực mobile và bảo toàn phiên web

**Cập nhật hiện trạng 06/10/2026:** đọc [README Mobile](README.md) và [C44](TIEN_ICH_THIET_BI.md) cho theme được lưu, deep link, realtime lịch và push đã tích hợp nhưng chưa nghiệm thu thật. Điểm vào Tài khoản demo đã bỏ; các đoạn mô tả UI1/MB1–MB5 bên dưới là lịch sử/thiết kế theo giai đoạn, không thay quyết định mới.

## Hiện trạng được đối chiếu

- [routes web](../../BE/routes/web.php) có `/dang-nhap`, `/dang-ky`, `/dang-xuat`, quên/đặt lại mật khẩu theo session và CSRF.
- [routes API](../../BE/routes/api.php) bảo vệ nghiệp vụ bằng `auth:sanctum`, tài khoản hoạt động và vai trò.
- [TaiKhoan](../../BE/app/Models/TaiKhoan.php) đã dùng `HasApiTokens`; migration `000043` bổ sung bảng `personal_access_tokens` sau khi kiểm tra schema local.
- [Middleware tài khoản](../../BE/app/Http/Middleware/KiemTraTaiKhoanHoatDong.php) có kiểm tra dấu phiên của session web. Dấu này không tự thu hồi bearer token.
- [Broadcast auth](../../BE/bootstrap/app.php) ở `/api/v1/broadcasting/auth`, kiểm tra tài khoản và KH/PT; MB4 đã kiểm tra bearer và giữ cookie web. [Channel](../../BE/routes/channels.php) dùng Sanctum/web, vẫn giới hạn đúng ID cá nhân. Xem [MB4](../verification/MOBILE_MB4.md).

## MB1 đã triển khai ngày 05/10/2026

Giữ cookie/session/CSRF cho website. Bổ sung Sanctum bearer token cho native app; không chuyển web sang lưu token. Sanctum hỗ trợ xác thực mobile bằng token, còn quyền tài nguyên vẫn do Policy/Service quyết định. [Nguồn Laravel](https://laravel.com/framework/docs/13.x/sanctum#mobile-application-authentication).

Token native lưu bằng `expo-secure-store`, không lưu mật khẩu, không đặt token trong AsyncStorage, URL, log hoặc source. SecureStore không phải kho lưu dữ liệu nghiệp vụ; cần xử lý việc token không còn sau thay đổi môi trường/cài đặt. [Nguồn Expo](https://docs.expo.dev/versions/latest/sdk/securestore/).

## Endpoint đã triển khai

Nhóm endpoint native đã có trong Backend; `/me` và `/me/ho-so` dùng lại hợp đồng hiện có. Xem [biên bản MB1](../verification/MOBILE_MB1.md) và [MB5](../verification/MOBILE_MB5.md).

| Method/path | Request | Kết quả / kiểm soát |
| --- | --- | --- |
| `POST /api/v1/mobile/dang-nhap` | `email`, `password`, `ten_thiet_bi` | 200: `status/message/data`; data có `access_token`, `token_type=Bearer`, `expires_at`, `tai_khoan`. Chỉ tài khoản KH/PT hoạt động; giới hạn độ dài, throttle theo IP/email, không lộ email tồn tại qua thông báo lỗi |
| `POST /api/v1/mobile/dang-xuat` | Không cần payload | Thu hồi token đang dùng; cùng tài khoản trên thiết bị khác không bị xóa nhầm |
| `POST /api/v1/mobile/dang-ky` | `ho_ten`, `email`, `password`, `password_confirmation` | 201, `data: null`; chỉ tạo KH, không tự cấp token. Chặn trường lạ/vai trò, email trùng, mật khẩu ngắn hoặc quá 72 byte |
| `POST /api/v1/mobile/quen-mat-khau` | `email` | 200, cùng thông báo cho email có/không tồn tại; throttle và không trả token reset |
| `POST /api/v1/mobile/dat-lai-mat-khau` | `email`, `token`, `password`, `password_confirmation` | 200, `data: null`; broker dùng mã một lần/60 phút, thu hồi tất cả phiên cũ, không tự đăng nhập |
| `GET /api/v1/me` | Bearer header | Dùng lại endpoint hiện có sau khi kiểm chứng; tài khoản/role từ server |

Chủ dự án đã chốt MB-D02: phiên có hạn cố định 30 ngày từ lúc cấp, nhiều thiết bị cùng dùng, logout chỉ thu hồi phiên hiện tại, hết hạn đăng nhập lại. MB1 đã triển khai chính sách này; không có refresh endpoint hoặc tự gia hạn khi sử dụng. Rate limit và lỗi theo chuẩn [API](../API_CONVENTIONS.md).

`PUT /api/v1/me/ho-so` nhận trường hồ sơ đúng actor và `updated_at` nguyên chuỗi micro giây. Form giữ bản gốc, chặn gửi lặp, xác nhận bỏ thay đổi khi Back; 409 giữ bản đang nhập và cho chủ động tải lại, không tự ghi đè. Email/ID/vai trò không gửi trong payload.

MB5 tái sử dụng service đăng ký/khôi phục và password broker hiện có. Form native cho nhập mã 64 ký tự hex hoặc dán liên kết email web có path `/dat-lai-mat-khau` và token ở fragment; không nhận token ở query, không tự mở deep link hay tự điền email/mật khẩu. Mã và mật khẩu được che, chỉ giữ trong bộ nhớ màn hình và xóa khi thành công/rời màn. Liên kết email vẫn phục vụ web, không đổi `FRONTEND_URL`; email thật chưa nghiệm thu trong QA giả lập.

## Kiểm soát Backend và phần cần nghiệm thu tiếp

1. Migration chỉ tạo bảng token, không reset/seed database. Database lưu hash token; app lưu khóa và hạn trong SecureStore, không lưu mật khẩu/hồ sơ.
2. `PhienMobileService` dùng transaction và khóa theo ID tài khoản, cùng thứ tự với khóa/reset. Đã kiểm tra race trên MariaDB bằng kết nối/worker riêng.
3. Nhóm `/mobile` gồm năm endpoint auth loại khỏi middleware SPA stateful. HTTP client dùng `credentials: omit`, bearer và không gửi Origin web. Cookie/CSRF web giữ nguyên; đã kiểm thử hồi quy.
4. Khóa/reset xóa token cùng thu hồi session; dấu password/remember token chặn token cũ. Mở khóa không hồi sinh phiên.
5. Callback Sanctum kiểm tra thời hạn, actor KH/PT hoạt động, abilities đúng `['mobile']` và dấu phiên. Policy/Service tiếp tục quyết định quyền tài nguyên trong mỗi lần gọi.
6. MB1 chưa nghiệm thu private channel/ảnh chat; phần này đã kiểm tra bearer/Reverb/native ở [MB4](../verification/MOBILE_MB4.md), không suy ra socket đã chạy từ kiểm tra API hồ sơ.
7. Scheduler dọn token hết hạn mỗi ngày bằng `sanctum:prune-expired --hours=24`; token hết hạn bị từ chối ngay trước khi dọn. Không trả hash mật khẩu/remember token/phiên thiết bị khác.

## Vòng đời app

| Tình huống | Xử lý mục tiêu |
| --- | --- |
| Mở app, không có token | Vào Auth |
| Có token | Đọc an toàn, gọi `/me`; chỉ vào giao diện riêng sau khi xác minh |
| `/me` lỗi mạng/5xx | Hiển thị lỗi và thử lại; không coi là đăng xuất hoặc cấp quyền offline |
| 401 | Dọn phiên, về Auth; không lặp gọi đăng nhập tự động bằng mật khẩu đã lưu |
| 403 do tài nguyên | Hiển thị không có quyền; phân biệt với tài khoản khóa bằng trạng thái đã xác minh |
| Logout thành công | Đóng giao diện riêng, hủy request, xóa khóa lưu và thu hồi token hiện tại trên server |
| Logout khi mất mạng | Dọn máy và báo chưa xác nhận thu hồi server; không tuyên bố token đã bị thu hồi. Token server còn chịu TTL/chính sách phiên |
| Đổi tài khoản | Mã thế hệ bỏ response cũ; hàng đợi ghi/xóa kho chặn phiên cũ ghi trở lại sau logout |

Bản web preview trong Expo chỉ để kiểm tra UI khi chưa có adapter xác thực web; không lưu bearer vào localStorage để làm tắt. Kiểm thử xác thực native trên thiết bị/emulator.

Điều kiện nghiệm thu và các ca thu hồi cụ thể tại [KIEM_THU.md](KIEM_THU.md). Mọi thay đổi xác thực phải chạy hồi quy đăng nhập/CSRF/khóa/reset mật khẩu của website.
