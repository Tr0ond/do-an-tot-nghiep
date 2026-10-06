# FitForge — tài liệu dự án

Cập nhật ngày **06/10/2026**. Tài liệu được tập trung trong `md/`; source web Vue nằm ở `FE/`, API Laravel ở `BE/`, app React Native + Expo ở `Mobile/`. Các đường dẫn source và lệnh bên dưới tính từ **thư mục gốc dự án**, không chạy bên trong `md/`.

Giới thiệu tổng quan dành cho người mới nằm tại [README.md ở thư mục gốc](../README.md). Tài liệu này là chỉ mục hướng dẫn và tài liệu chi tiết.

## Bắt đầu ở đây

| Cần làm | Tài liệu |
| --- | --- |
| Chạy/cấu hình Backend | [backend/README.md](backend/README.md) |
| Chạy/cấu hình website | [frontend/README.md](frontend/README.md) |
| Chạy app trên LDPlayer/điện thoại | [mobile/README.md](mobile/README.md) |
| Quy tắc nghiệp vụ và quyền | [PROJECT_RULES.md](PROJECT_RULES.md), [DECISIONS.md](DECISIONS.md) |
| Chức năng từng module | [features/README.md](features/README.md) |
| Tổ chức code và API | [CODE_STYLE.md](CODE_STYLE.md), [ARCHITECTURE.md](ARCHITECTURE.md), [API_CONVENTIONS.md](API_CONVENTIONS.md) |
| Database, dữ liệu và migrations | [DATABASE_DRAFT.md](DATABASE_DRAFT.md), [DATABASE_DICTIONARY.md](DATABASE_DICTIONARY.md), [backend/MIGRATIONS.md](backend/MIGRATIONS.md), [backend/DATA.md](backend/DATA.md) |
| Dữ liệu demo và kịch bản bảo vệ | [backend/SEEDERS.md](backend/SEEDERS.md), [DEMO_SCRIPT.md](DEMO_SCRIPT.md) |
| Kiểm thử và giới hạn đã biết | [TEST_PLAN.md](TEST_PLAN.md), [verification/README.md](verification/README.md) |
| Phạm vi/công nghệ/kế hoạch học thuật | [SCOPE.md](SCOPE.md), [TECHNOLOGY.md](TECHNOLOGY.md), [ROADMAP.md](ROADMAP.md) |

## Hiện trạng

- Đúng ba tác nhân: Khách hàng, Huấn luyện viên, Admin. Admin dùng web; mobile phục vụ KH/PT. AI là công cụ tư vấn, không là tác nhân nghiệp vụ thứ tư.
- Đã có tài khoản/phân quyền/hồ sơ, catalog bài tập/gói/giáo án mẫu, đơn và payOS, phân công PT, lịch hẹn, giáo án cá nhân, tự tập/nhật ký, chỉ số cơ thể, chat chữ/ảnh, chatbot/nháp AI, thông báo và báo cáo.
- C42: PT ghi kết quả bài/hiệp thực tế, lưu nháp/chốt; KH xem. Giữ thao tác hoàn thành lịch hẹn và trừ buổi riêng. Biểu đồ KH trên web có cả tự tập và PT hoàn thành.
- Mobile MB1–MB5 đã nối API thật; giao diện theo bản Figma Make xuất do chủ dự án cung cấp. Logo cạnh chữ FitForge dùng ảnh hệ thống; khung Tài khoản demo đã bỏ khỏi đăng nhập.
- C44: lưu sáng/tối/hệ thống, link reset/đơn hàng, lịch realtime và nền tảng push theo phiên thiết bị. **Push thật, APK, FCM/EAS, điện thoại thật và một số luồng nhà cung cấp thật chưa nghiệm thu.** [Cấu hình và giới hạn](mobile/TIEN_ICH_THIET_BI.md), [kiểm chứng C44](verification/MOBILE_TIEN_ICH.md).
- Trạng thái cụ thể lấy từ quyết định đã chốt, source hiện tại và biên bản tương ứng. Số test trong biên bản là kết quả tại lần kiểm tra ghi ngày, không phải chứng nhận bản hiện tại đã qua tất cả kiểm thử.

## Chạy tại máy Windows

1. Bật MySQL/MariaDB và chuẩn bị môi trường theo README Backend/Frontend/Mobile. Giữ `.env` và dữ liệu riêng, không đưa secrets vào tài liệu.
2. Từ thư mục gốc mở `start.bat` cho web/API/Reverb/scheduler/ngrok, hoặc `start-mobile.bat` cho LAN và Expo. Các script vẫn ở vị trí cũ.
3. Web local: [localhost:5173](http://localhost:5173). Khi kiểm tra thiết bị, dùng địa chỉ LAN/ADB reverse theo README Mobile.
4. Máy clone chạy migrations đúng hướng dẫn; không chạy `migrate:fresh`, rollback hoặc seed lại trên database đang sử dụng chỉ để thử.

## Cấu trúc tài liệu

```text
md/
├── README.md              # Chỉ mục và hiện trạng
├── AGENTS.md              # Hướng dẫn đầy đủ cho Codex
├── PROJECT_RULES.md       # Quy tắc chính
├── DECISIONS.md           # Quyết định đã chốt / còn chờ
├── CODE_STYLE.md          # Quy ước code
├── features/              # Hợp đồng chức năng
├── backend/               # Chạy API, dữ liệu, migrations, giấy phép
├── frontend/              # Chạy website
├── mobile/                # Chạy app, xác thực, API, push/phát hành
├── design/                # Mapping Figma Make hiện tại
├── verification/          # Biên bản kiểm thử cần giữ
└── organization.json      # Danh sách di chuyển/xóa và vị trí bản sao trước dọn
```

`AGENTS.md` tại gốc và `Mobile/AGENTS.md` chỉ là hai điểm vào ngắn để Codex tự tìm hướng dẫn. Nội dung đầy đủ đã ở `md/`; không di chuyển hai điểm vào này.

`docs/` chỉ còn tài nguyên thiết kế, sơ đồ, ảnh và JSON phục vụ kiểm tra. Không xóa cả thư mục này: công cụ kiểm tra dữ liệu/đánh giá chatbot/gallery vẫn dùng tài nguyên tại đó. `templates/` giữ các file `.example`, không có README trùng. Thư viện `node_modules`, `vendor`, dataset nguồn và cache Expo không thuộc đợt dọn tài liệu dự án.

## Nguồn và giấy phép

Giữ nguyên media bài tập tại `BE/public/media/bai-tap/` và thông tin bản quyền trong [backend/NOTICE.md](backend/NOTICE.md), [backend/DATA.md](backend/DATA.md), [LICENSE dataset](../BE/database/data/LICENSE). CA payOS và nguồn bundle ở [backend/CERTIFICATES.md](backend/CERTIFICATES.md). Các file giấy phép không bị xóa.
