<p align="center">
  <img src="Mobile/assets/brand/logo-fitforge.png" alt="Logo FitForge" width="112" />
</p>

# FitForge

**Nền tảng quản lý huấn luyện cá nhân và theo dõi tập luyện trên web và mobile.**

FitForge kết nối khách hàng, huấn luyện viên cá nhân (PT) và quản trị viên trong một hệ thống thống nhất: quản lý gói dịch vụ, đặt lịch, xây dựng giáo án, ghi kết quả tập luyện, theo dõi tiến độ và trao đổi trực tiếp. Trợ lý **FitForge AI** hỗ trợ tư vấn và soạn nháp giáo án dựa trên ngữ cảnh được phép sử dụng.

Dự án được phát triển trong khuôn khổ **đồ án tốt nghiệp**, với phạm vi quản lý huấn luyện cá nhân cho một phòng gym. Website và ứng dụng Android dùng chung Backend Laravel, bảo đảm dữ liệu, quyền truy cập và quy tắc nghiệp vụ được quản lý tập trung.

[Chức năng](#chức-năng-chính) · [Công nghệ](#công-nghệ) · [Khởi chạy](#khởi-chạy-dự-án) · [Tài liệu](#tài-liệu-dự-án) · [Trạng thái](#trạng-thái-triển-khai)

## Mục tiêu

- Tập trung thông tin khách hàng, gói dịch vụ, PT phụ trách và lịch huấn luyện.
- Liên kết giáo án với kết quả thực tế, giúp khách hàng và PT theo dõi quá trình tập luyện.
- Hỗ trợ cả tự tập và tập cùng PT, với lịch sử và quyền thao tác riêng cho từng hình thức.
- Giảm thao tác quản lý thủ công thông qua lịch hẹn, thông báo, thanh toán và báo cáo.
- Bổ sung AI như công cụ hỗ trợ tư vấn; người dùng vẫn kiểm tra và quyết định áp dụng giáo án.

## Người dùng và nền tảng

| Vai trò | Nền tảng | Trách nhiệm chính |
| --- | --- | --- |
| **Khách hàng** | Web và Android | Quản lý tài khoản, mua gói, đặt lịch, sử dụng giáo án, ghi nhật ký tự tập, xem kết quả với PT và theo dõi tiến độ. |
| **Huấn luyện viên** | Web và Android | Quản lý học viên được phân công, mở khung giờ, xử lý lịch hẹn, xây dựng giáo án, ghi kết quả buổi PT và nhận xét quá trình tập luyện. |
| **Quản trị viên** | Web | Quản lý tài khoản, danh mục, gói dịch vụ, phân công PT, đối soát thanh toán, nội dung tư vấn và báo cáo. |

Hệ thống có đúng ba vai trò nghiệp vụ. FitForge AI là công cụ hỗ trợ bên trong ứng dụng.

## Chức năng chính

### Tài khoản và phân quyền

- Đăng ký khách hàng, đăng nhập, đăng xuất, cập nhật hồ sơ và khôi phục mật khẩu qua email.
- Admin tạo tài khoản PT/Admin, quản lý và khóa hoặc mở khóa tài khoản.
- Kiểm tra quyền theo vai trò, chủ sở hữu tài nguyên và phân công PT còn hiệu lực.
- Phiên mobile có thời hạn **30 ngày**, hỗ trợ nhiều thiết bị; đăng xuất chỉ thu hồi phiên trên thiết bị hiện tại.

### Gói dịch vụ và thanh toán

- Quản lý gói, giá, thời hạn, số buổi PT và hạn mức chatbot theo ngày.
- Tạo đơn, mở thanh toán payOS, kiểm tra trạng thái và kích hoạt quyền lợi theo kết quả Backend đã xác minh.
- Lưu giá và quyền lợi tại thời điểm mua; hỗ trợ Admin xử lý đối soát và ghi nhận hoàn tiền thủ công.

### Lịch hẹn và huấn luyện

- Admin phân công PT; PT quản lý khung giờ và học viên đang phụ trách.
- Khách hàng đặt hoặc hủy lịch theo điều kiện thời gian; PT xác nhận, từ chối và ghi nhận hoàn thành hoặc vắng mặt.
- Kiểm soát trùng lịch, hạn xử lý và số buổi còn lại; cập nhật lịch qua realtime kết hợp tải lại dữ liệu.

### Giáo án và kết quả tập luyện

- Thư viện bài tập có ảnh, hướng dẫn và bộ lọc; Admin quản lý bài tập, nhóm cơ và giáo án mẫu.
- PT soạn và gửi giáo án để khách hàng xác nhận; khách hàng cũng có thể tự tạo và áp dụng giáo án.
- Khách hàng tự lên lịch, nhập số lần, mức tạ và thời gian nghỉ theo từng hiệp, lưu nháp rồi hoàn thành nhật ký.
- PT ghi kết quả buổi tập cùng khách hàng, lưu nháp và chốt; khách hàng xem lịch sử kết quả của mình.
- Ghi chiều cao, cân nặng, BMI và xem tiến độ. Biểu đồ tổng quan khách hàng trên web tổng hợp cả buổi tự tập và buổi PT đã hoàn thành.

**Giáo án, lịch hẹn và kết quả thực tế được quản lý riêng.** Lưu hoặc chốt kết quả buổi PT không tự hoàn thành lịch hẹn hay trừ lượt; tự tập không tiêu hao buổi PT.

### Trao đổi và tư vấn

- Chat 1–1 giữa khách hàng và PT, hỗ trợ chữ, ảnh, lịch sử và trạng thái chưa đọc.
- Thông báo trong ứng dụng theo các sự kiện nghiệp vụ và điều hướng tới tài nguyên liên quan.
- FitForge AI tư vấn theo hạn mức, hỗ trợ tạo nháp giáo án để người dùng xem, sửa và xác nhận.
- FAQ công khai và quản trị nội dung tư vấn; dữ liệu cá nhân chỉ được đưa vào ngữ cảnh AI khi người dùng bật tùy chọn tương ứng.

### Quản trị và báo cáo

- Tổng quan theo vai trò với dữ liệu nghiệp vụ thực tế.
- Báo cáo doanh thu, thanh toán, đăng ký gói, buổi PT hoàn thành và phân bố học viên.
- Bảo toàn lịch sử khi thay đổi phân công, ngừng danh mục hoặc khóa tài khoản.

## Công nghệ

| Thành phần | Công nghệ đang sử dụng |
| --- | --- |
| **Website** | Vue 3, JavaScript, Options API, Vite, Vue Router, Pinia, Axios, Bootstrap 5.3 và CSS theo theme. |
| **Mobile** | React Native 0.86, React 19, Expo SDK 57, React Navigation, SecureStore và Expo Image. |
| **Backend** | Laravel 13, PHP, Eloquent, FormRequest, Policy và Service cho nghiệp vụ. |
| **Database** | MySQL/MariaDB, migrations, transaction, khóa và ràng buộc dữ liệu. |
| **Xác thực** | Laravel Sanctum: session/cookie cho web; bearer token cho mobile. |
| **Realtime** | Laravel Reverb và client tương thích giao thức Pusher. |
| **Tích hợp** | payOS, Gemini, email; Expo Notifications cho nền tảng push mobile. |
| **Kiểm thử** | PHPUnit/Laravel, Vitest và Node Test Runner; các công cụ kiểm tra database và giao diện trong `scripts/`. |

Phiên bản dependency được quản lý trong `FE/package-lock.json`, `Mobile/package-lock.json` và `BE/composer.lock`.

## Kiến trúc và tổ chức source

Website Vue và ứng dụng React Native gọi cùng API Laravel. Backend xác thực, phân quyền và xử lý quy tắc về tiền, số lượt, lịch hẹn, giáo án và dữ liệu tập luyện. Reverb chuyển tín hiệu realtime; các thay đổi nghiệp vụ vẫn được kiểm tra tại Backend.

```text
FitForge/
├── README.md          # Giới thiệu tổng quan dự án
├── FE/                # Website Vue: Khách hàng, PT và Admin
├── BE/                # API Laravel, database và media bài tập
├── Mobile/            # Ứng dụng React Native + Expo cho Khách hàng/PT
├── md/                # Quy tắc, chức năng, hướng dẫn và biên bản kiểm chứng
├── docs/              # Sơ đồ, tài nguyên thiết kế và bằng chứng kiểm tra
├── scripts/           # Công cụ khởi chạy, dữ liệu và kiểm tra
├── exercises-dataset/ # Dataset nguồn và thông tin giấy phép
├── start.bat          # Khởi chạy web và các dịch vụ local trên Windows
└── start-mobile.bat   # Khởi chạy môi trường mobile qua mạng local
```

Không đưa API key hoặc secret vào frontend/mobile. Giá, quyền lợi và trạng thái thanh toán lấy từ Backend; nháp AI cần người dùng xác nhận trước khi áp dụng.

## Khởi chạy dự án

### Chuẩn bị môi trường

- **PHP 8.3 trở lên** tương thích `BE/composer.json`, Composer và các extension Laravel cần thiết.
- **Node.js** thỏa điều kiện `^22.18.0 || >=24.12.0` của frontend, cùng npm.
- **MySQL/MariaDB** và một database được cấu hình cho dự án.
- **Expo Go tương thích SDK 57** hoặc bản development build để kiểm tra Android; có thể dùng điện thoại hoặc LDPlayer.
- **ngrok** đã cấu hình nếu dùng `start.bat` để mở webhook payOS ra bên ngoài.

Trên máy mới, cài dependency bằng `composer install` trong `BE/`, `npm ci` trong `FE/` và `Mobile/`. Sao chép từng `.env.example` sang file cấu hình local theo hướng dẫn của module, điền cấu hình database/API và tạo `APP_KEY` một lần khi chưa có key. Chạy migrations trên database đã chuẩn bị; dữ liệu ban đầu và tài khoản được hướng dẫn tại [Seeder](md/backend/SEEDERS.md).

### Chạy trên Windows

Sau khi hoàn tất cấu hình và bật database, từ thư mục gốc:

```powershell
# Website, API, Reverb, scheduler và ngrok
.\start.bat

# Môi trường mobile qua Wi-Fi — chạy khi cần kiểm tra Android
.\start-mobile.bat
```

Nếu đã có server đang chạy, đọc hướng dẫn module trước khi mở thêm launcher để tránh trùng tiến trình hoặc cổng.

| Dịch vụ local | Địa chỉ mặc định |
| --- | --- |
| Website | [http://localhost:5173](http://localhost:5173) |
| Kiểm tra API | [http://localhost:8000/api/v1/health](http://localhost:8000/api/v1/health) |
| Reverb | Cổng `8080` |
| Expo Metro | Launcher mobile mặc định cổng `8082`; hướng dẫn LDPlayer dùng `8081`. Địa chỉ truy cập theo LAN hoặc ADB reverse. |

Hướng dẫn cấu hình và chạy riêng từng phần: [Backend](md/backend/README.md) · [Frontend](md/frontend/README.md) · [Mobile và LDPlayer](md/mobile/README.md).

## Trạng thái triển khai

**Cập nhật ngày 06/10/2026.** Dự án đã có runtime web/API/mobile và các luồng chính: tài khoản, danh mục, gói và đơn, phân công PT, lịch hẹn, giáo án, nhật ký, kết quả buổi PT, chỉ số cơ thể, chat, chatbot, thông báo và báo cáo.

Mobile đã tích hợp API cho Khách hàng/PT, ghi nhớ theme, link đặt lại mật khẩu/đơn hàng, lịch realtime và nền tảng push theo phiên thiết bị. Android là nền tảng được ưu tiên kiểm tra.

**Các phần còn cần nghiệm thu:** push trên bản cài Android với EAS/FCM, verified HTTPS App Links, phát hành APK/store, kiểm tra điện thoại thật và hoàn tất kiểm chứng các luồng nhà cung cấp thực tế. Code tích hợp hoặc kết quả trên môi trường QA không đồng nghĩa các dịch vụ này đã được nghiệm thu production.

Đọc [Quyết định dự án](md/DECISIONS.md), [Tiện ích mobile và giới hạn](md/mobile/TIEN_ICH_THIET_BI.md) và [Biên bản kiểm chứng](md/verification/README.md) để xem trạng thái chi tiết. Kết quả trong từng biên bản thuộc thời điểm được ghi tại đó.

## Tài liệu dự án

| Nội dung | Tài liệu |
| --- | --- |
| Chỉ mục tài liệu và hướng dẫn vận hành | [md/README.md](md/README.md) |
| Phạm vi và quy tắc nghiệp vụ | [SCOPE](md/SCOPE.md), [PROJECT_RULES](md/PROJECT_RULES.md), [DECISIONS](md/DECISIONS.md) |
| Hợp đồng chức năng | [features/](md/features/README.md) |
| Kiến trúc, quy ước code và API | [ARCHITECTURE](md/ARCHITECTURE.md), [CODE_STYLE](md/CODE_STYLE.md), [API_CONVENTIONS](md/API_CONVENTIONS.md) |
| Database và dữ liệu | [DATABASE_DRAFT](md/DATABASE_DRAFT.md), [DATABASE_DICTIONARY](md/DATABASE_DICTIONARY.md), [Dữ liệu bài tập](md/backend/DATA.md) |
| Kiểm thử và minh chứng | [TEST_PLAN](md/TEST_PLAN.md), [verification/](md/verification/README.md) |
| Demo đồ án | [DEMO_SCRIPT](md/DEMO_SCRIPT.md) |

## Dữ liệu và ghi nhận nguồn

Ảnh và dữ liệu bài tập được lưu kèm thông tin nguồn, bản quyền và giấy phép. Khi sử dụng hoặc phân phối, xem [NOTICE](md/backend/NOTICE.md), [LICENSE dataset](BE/database/data/LICENSE) và [Hướng dẫn dữ liệu](md/backend/DATA.md). Giữ các thông tin ghi nhận nguồn cùng dữ liệu tương ứng.
