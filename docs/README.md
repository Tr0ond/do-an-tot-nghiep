# Chỉ mục tài liệu

Đã triển khai [quản trị bài tập Admin](verification/M02_ADMIN_BAI_TAP.md): thêm/sửa và ngừng/khôi phục hiển thị theo [hợp đồng bài tập](features/BAI_TAP.md).

Thư mục `docs/` chứa thiết kế và kế hoạch kiểm thử. Quy tắc, phạm vi, công nghệ và tiến độ tổng thể nằm ở thư mục gốc.

## Tài liệu thiết kế

| Tài liệu | Mục đích |
| --- | --- |
| [DECISIONS.md](DECISIONS.md) | Yêu cầu đã xác nhận, giả định và quyết định còn chờ |
| [REFERENCE_CODE_REVIEW.md](REFERENCE_CODE_REVIEW.md) | Quan sát từ mẫu code của ba dự án |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Thành phần, luồng dữ liệu, vị trí code |
| [DATABASE_DRAFT.md](DATABASE_DRAFT.md) | Thiết kế 28 bảng/52 FK, liên kết SQL, ERD và migrations; chưa chạy trên MySQL |
| [DATABASE_DICTIONARY.md](DATABASE_DICTIONARY.md) | Đầy đủ cột, kiểu, FK, UNIQUE/CHECK/index |
| [database.drawio](diagrams/database.drawio) | Bản vẽ draw MCP theo mẫu: 28 bảng/52 FK trên một canvas |
| [Dữ liệu bài tập](../BE/database/data/README.md) | Catalog 1.324 bài, media, nguồn và cách nhập |
| [API_CONVENTIONS.md](API_CONVENTIONS.md) | HTTP, response, auth và endpoint minh họa |
| [TEST_PLAN.md](TEST_PLAN.md) | Điều kiện nghiệm thu và ca kiểm thử quan trọng |
| [features/AI_CHATBOT.md](features/AI_CHATBOT.md) | Chatbot có nguồn, giới hạn và đánh giá |
| [features/REALTIME_CHAT.md](features/REALTIME_CHAT.md) | Chat văn bản, lịch sử, reconnect và thu hồi quyền |
| [features/BAI_TAP.md](features/BAI_TAP.md) | Seeder catalog, API/giao diện công khai và bộ lọc |
| [verification/M02_BAI_TAP.md](verification/M02_BAI_TAP.md) | Kiểm chứng nhập dữ liệu, API và giao diện bài tập |

## Tài liệu ở thư mục gốc

- [README.md](../README.md): tổng quan và cấu trúc hiện tại.
- [PROJECT_RULES.md](../PROJECT_RULES.md): nguồn quy tắc chính.
- [SCOPE.md](../SCOPE.md): phạm vi và chức năng từng tác nhân.
- [TECHNOLOGY.md](../TECHNOLOGY.md): công nghệ đề xuất.
- [CODE_STYLE.md](../CODE_STYLE.md): phong cách code.
- [ROADMAP.md](../ROADMAP.md): kế hoạch 24 tuần.

Các chính sách chính D01–D10 đã được chủ dự án chốt ngày 01/10/2026. Đã có runtime Laravel/Vue, [migrations MariaDB](verification/MARIADB_MIGRATIONS.md), [luồng tài khoản](features/TAI_KHOAN.md) và [bằng chứng kiểm thử](verification/M01_AUTH.md). Các module gói/lịch/chat và kiểm thử MySQL thật còn phía trước. Khi thay đổi quyết định, cập nhật [DECISIONS.md](DECISIONS.md), phần tương ứng của [PROJECT_RULES.md](../PROJECT_RULES.md) và schema/API/tests. Không duy trì nhiều bản quy tắc trái nhau.
