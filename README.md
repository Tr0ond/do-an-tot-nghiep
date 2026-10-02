# Dự án tốt nghiệp — Quản lý huấn luyện cá nhân

**Tên đề tài đề xuất:** Xây dựng hệ thống quản lý huấn luyện cá nhân tích hợp chatbot AI tư vấn và trao đổi trực tuyến.

**Ngày khởi tạo:** 01/10/2026 · **Thời gian dự kiến:** 6 tháng · **Tác nhân:** Khách hàng, PT, Admin. Một người thực hiện khoảng 8 giờ/ngày; tên đề tài có thể đổi, ngày bảo vệ chưa được cung cấp.

## Trạng thái hiện tại

Đã khởi tạo Laravel 13 trong `BE/` và Vue 3 Options API/JavaScript trong `FE/`, cài dependencies và lưu lockfiles. Đã có Sanctum cho đăng ký KH, đăng nhập/đăng xuất, đọc hồ sơ, phân quyền KH/PT/Admin và Admin tạo PT/Admin; [hợp đồng](docs/features/TAI_KHOAN.md), [bằng chứng xác thực](docs/verification/M01_AUTH.md). Đã bổ sung sửa hồ sơ ba vai trò, quên/đặt lại mật khẩu và khóa/mở tài khoản; [kiểm chứng M01 bổ sung](docs/verification/M01_HO_SO_KHOI_PHUC.md). Đạt 98 Backend tests/4.237 assertions, 79 frontend tests và build/lint/format. Đã nhập catalog 1.324 bài tập, có API/giao diện danh sách và chi tiết, Admin thêm/sửa/ngừng/khôi phục hiển thị bài tập; [hợp đồng](docs/features/BAI_TAP.md), [kiểm chứng](docs/verification/M02_BAI_TAP.md). Đã có quản lý gói, bảng giá và chi tiết quyền lợi; [hợp đồng gói](docs/features/GOI_TAP.md), [kiểm chứng](docs/verification/M02_GOI_TAP.md). Đã có Admin quản lý nhóm cơ; [hợp đồng](docs/features/NHOM_CO.md), [kiểm chứng](docs/verification/M02_NHOM_CO.md). Đã có Admin soạn/duyệt/ngừng giáo án mẫu và PT đọc thư viện; [hợp đồng giáo án](docs/features/GIAO_AN_MAU.md), [kiểm chứng](docs/verification/M02_GIAO_AN_MAU.md). Giữ nguyên 2.648 ảnh-GIF, SQL, Draw.io và 28 migrations tạo bảng nghiệp vụ; có thêm migrations chống tạo gói/giáo án trùng, [hướng dẫn](BE/database/migrations/README.md). Đã chạy migrations trên MariaDB 10.4.32, kiểm tra migrate/rollback/migrate gốc ở database riêng; [bằng chứng](docs/verification/MARIADB_MIGRATIONS.md). Chưa kiểm thử trên MySQL thật hoặc triển khai mua/payOS, chatbot hay realtime. Các file `.example` trong `templates/` vẫn chỉ dùng để tham khảo.

Các lựa chọn chủ dự án đã yêu cầu: Vue.js, Laravel, ba tác nhân, quy mô đồ án 6 tháng, chatbot tư vấn, chat PT–khách hàng realtime và Admin tự tạo gói có quyền lợi khác nhau (chatbot riêng hoặc PT theo buổi kèm chatbot). Mỗi khách một gói khả dụng, kích hoạt khi thanh toán payOS được Backend xác nhận; gói kết hợp dùng chung thời hạn theo ngày đủ 24 giờ. Đơn giữ giá/chờ thanh toán 15 phút. Admin đặt lượt chatbot mỗi ngày; chỉ tính câu trả lời hợp lệ, lỗi không mất lượt/retry không tính lặp, cấp lại 00:00 giờ Việt Nam. Hết buổi PT còn thời hạn vẫn dùng chatbot. Catalog/FAQ miễn phí, chatbot cần gói phù hợp. Trạng thái từng quyết định nằm tại [DECISIONS.md](docs/DECISIONS.md).

## Bắt đầu đọc ở đâu?

1. [SCOPE.md](SCOPE.md): chức năng từng tác nhân, giới hạn và danh sách màn hình.
2. [TECHNOLOGY.md](TECHNOLOGY.md): công nghệ và lý do lựa chọn.
3. [PROJECT_RULES.md](PROJECT_RULES.md): tài liệu quy tắc nghiệp vụ/kỹ thuật chính.
4. [CODE_STYLE.md](CODE_STYLE.md): cách viết code gần với các dự án cũ.
5. [ROADMAP.md](ROADMAP.md): kế hoạch 24 tuần và điều kiện nghiệm thu.
6. [DECISIONS.md](docs/DECISIONS.md): những quyết định cần chốt trước khi triển khai module liên quan.

## Quy trình chính

Khách hàng xem catalog/FAQ → đăng ký → thanh toán payOS → Backend xác minh thành công → kích hoạt ngay và sử dụng quyền lợi đã mua. Gói có chatbot cho phép tư vấn theo hạn mức ngày; gói có buổi PT đi tiếp qua phân công PT → chat → nhận kế hoạch → đặt lịch → ghi kết quả → PT xác nhận buổi → theo dõi tiến độ. Gói chatbot riêng không cần phân công PT.

Khách duyệt kế hoạch trong 24 giờ, PT lập lịch tự tập sau duyệt. Buổi PT dài 60 phút, đặt trước ít nhất 4 giờ, KH hủy trước ít nhất 2 giờ; yêu cầu chờ xác nhận hết hiệu lực sau tối đa 2 giờ và không muộn hơn mốc trước buổi 2 giờ. Vắng mặt không trừ buổi/không phạt. Buổi kết thúc trong hạn gói được xác nhận trong 24 giờ sau kết thúc, kể cả gói vừa hết hạn. Chat PT theo phân công, không phụ thuộc gói; đổi PT thu hồi quyền chat của PT cũ. Giao diện dùng giờ Việt Nam, DB lưu UTC và giữ lịch sử suốt đồ án.

**Kế hoạch tập, lịch hẹn PT và nhật ký thực tế là ba khái niệm riêng.** Việc ghi nhật ký tự tập hoặc nhắn tin không tự trừ lượt PT.

Frontend Vue 3 Options API/JavaScript, Bootstrap 5.3 được bổ sung CSS/Tailwind khi cần. Chatbot dùng Gemini theo hướng CNPM, chỉ hạn mức API miễn phí; hết quota báo bận không mất lượt gói. Gói hết hạn vẫn xem kế hoạch/lịch sử và ghi nhật ký từ lịch tự tập hợp lệ đã có. Đổi PT chờ buổi đã diễn ra được xử lý xong; buổi quá 24 giờ chưa xác nhận được ghi quá hạn, Admin đóng xử lý có lý do, không trừ buổi/không xác nhận thay PT.

## Cấu trúc thư mục

```text
Dự án tốt nghiệp/
├── README.md
├── AGENTS.md
├── RULE.md                      # Chỉ mục dẫn tới nguồn quy tắc chính
├── PROJECT_RULES.md
├── SCOPE.md
├── TECHNOLOGY.md
├── CODE_STYLE.md
├── ROADMAP.md
├── .editorconfig
├── .gitignore
├── Base/
│   └── README.md                # Chỉ mục các thành phần dự án
├── FE/                          # Vue 3 Options API + Vite
│   ├── README.md
│   ├── .env.example
│   └── src/                     # Trang khởi động, router và API client
├── BE/                          # Laravel 13 API
│   ├── README.md
│   ├── .env.example
│   ├── app/                     # Các thư mục giữ chỗ cho Backend
│   ├── routes/
│   ├── database/                # Migration, factory, seeder khi triển khai
│   └── tests/
├── templates/                   # Mẫu tham khảo, chưa chạy
│   ├── README.md
│   ├── frontend/
│   └── backend/
└── docs/
    ├── README.md
    ├── DECISIONS.md
    ├── REFERENCE_CODE_REVIEW.md
    ├── ARCHITECTURE.md
    ├── DATABASE_DRAFT.md
    ├── API_CONVENTIONS.md
    ├── TEST_PLAN.md
    └── features/
        ├── README.md
        ├── AI_CHATBOT.md
        └── REALTIME_CHAT.md
```

`FE/`, `BE/` và `templates/` nằm trực tiếp ở thư mục gốc. `Base/` chỉ giữ chỉ mục tài liệu. Thiết kế nằm ở [DATABASE_DRAFT.md](docs/DATABASE_DRAFT.md), [từ điển dữ liệu](docs/DATABASE_DICTIONARY.md) và [sơ đồ draw.io](docs/diagrams/database.drawio). SQL tại `BE/database/design/`, dữ liệu tại `BE/database/data/`, media tại `BE/public/media/bai-tap/`; generator/kiểm tra tại `scripts/`.

Repository nguồn [`hasaneyldrm/exercises-dataset`](https://github.com/hasaneyldrm/exercises-dataset) được giữ riêng và không đưa vào repository dự án này. Catalog đã chuẩn hóa, giấy phép/ghi công và media được chuẩn bị cho Backend vẫn có trong `BE/`. Muốn chạy lại generator, clone dataset nguồn vào `exercises-dataset/` ở thư mục gốc; xem [hướng dẫn dữ liệu](BE/database/data/README.md).

## Quy mô mục tiêu

- Một phòng gym, huấn luyện 1–1, website responsive.
- 9 module, **28 màn hình đề xuất**, **28 bảng nghiệp vụ dự kiến**; bảng nội bộ framework được tính riêng.
- Dữ liệu demo mục tiêu: 50–100 khách hàng, 5–10 PT, 30–50 bài tập, 3–5 gói, 5–10 giáo án mẫu. Catalog đã chuẩn bị đủ 1.324 bài/19 nhóm cơ/28 nhãn dụng cụ từ dataset của chủ dự án; có thể chọn 30–50 bài cho kịch bản demo. Đã có [seeder 3 tài khoản demo Admin/PT/KH và 5 giáo án đã duyệt](BE/database/seeders/README.md) cho local/testing; chưa seed gói. Giáo án có 60 dòng bài, chưa gán cho khách hàng. Đây là dữ liệu để trình diễn, không phải kết quả đo tải.
- Một Backend Laravel, một Frontend Vue, MySQL, Reverb và một nhà cung cấp AI tại một thời điểm.

## Nguồn tham khảo cách code

- `E:/CNPM`: Laravel/Vue, giao diện theo vai trò, chatbot Gemini dựa trên dữ liệu tour.
- `E:/Cinema_project`: Laravel/Vue, Options API, CRUD quản trị và naming tiếng Việt không dấu.
- `E:/CS 445/Travel_Master`: nội dung thực tế là EduManage, có API client tập trung, Pinia, Reverb, services và feature tests.

Chi tiết mẫu đã đọc và nhận xét có giới hạn nằm ở [REFERENCE_CODE_REVIEW.md](docs/REFERENCE_CODE_REVIEW.md). Không sao chép secrets, dependencies hay mã nguồn của ba dự án vào bộ khung này.

## Việc tiếp theo

Các chính sách chính D01–D10 đã chốt trong [DECISIONS.md](docs/DECISIONS.md). Đã có phần xác thực/phân quyền của M01; tạo Admin đầu tiên và xem hướng dẫn chạy tại [TAI_KHOAN.md](docs/features/TAI_KHOAN.md). Đã có catalog bài tập, gói và giáo án mẫu. Đã có nhóm cơ M02 và sửa hồ sơ, quên/đặt lại mật khẩu, khóa/mở khóa tài khoản qua giao diện M01. Phần tiếp theo là đặt mua/payOS/phân công PT/lịch theo [ROADMAP.md](ROADMAP.md). Chưa có tài khoản/key hoặc kiểm thử payOS/Gemini.

Đã tách trang giới thiệu cho người chưa đăng nhập và dashboard KH/PT/Admin sau đăng nhập. Bấm Trang chủ/logo hoặc tải lại / sẽ vào đúng tổng quan theo session server. Thống kê hiện lấy dữ liệu M01/M02 thật; [hợp đồng tổng quan](docs/features/TONG_QUAN.md), [kiểm chứng giao diện](docs/verification/DASHBOARD.md).
