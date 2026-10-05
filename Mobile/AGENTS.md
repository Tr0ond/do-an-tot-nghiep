# Hướng dẫn làm việc trong Mobile

Áp dụng cùng [AGENTS.md gốc](../AGENTS.md), [PROJECT_RULES](../PROJECT_RULES.md) và [CODE_STYLE](../CODE_STYLE.md). Phần Vue Options API/Pinia chỉ dành cho `FE/`; `Mobile/` đã được chủ dự án chọn React Native + Expo/JavaScript theo C40.

## Ngữ cảnh tối thiểu

1. Đọc [README](README.md) và [chỉ mục tài liệu](docs/README.md).
2. Đọc [phạm vi](docs/PHAM_VI.md), [kiến trúc](docs/KIEN_TRUC.md) và giai đoạn đang làm trong [lộ trình](docs/LO_TRINH.md).
3. Chỉ đọc hợp đồng mobile/module liên quan. Đối chiếu route/FormRequest/Policy/Service thật của Backend trước tích hợp, không dùng endpoint đề xuất như endpoint đã tồn tại.

## Ranh giới triển khai

- Một app KH/PT, Backend Laravel dùng chung; Admin quản trị trên web. Không chuyển FE Vue sang React hoặc tạo database nghiệp vụ riêng.
- UI1 có navigation/theme/bản mẫu; MB1 có bearer auth/SecureStore/HTTP client và hồ sơ thật; MB2 có lịch/tổng quan/học viên; MB3 có giáo án/tự tập/nhật ký/số đo; MB4 có chat chữ/ảnh/Reverb/chưa đọc/thông báo trong app; MB5 có đăng ký/khôi phục, gói/đơn/thanh toán, FAQ và chatbot/nháp AI. Đối chiếu biên bản từng bước trước khi báo chức năng đã tích hợp. MB5 kiểm tra nhà cung cấp giả lập; push, bản cài và điện thoại thật còn chờ. Không dùng số liệu mẫu cho tài khoản đăng nhập thật.
- Component React dùng function/hooks; biến/hàm nghiệp vụ tiếng Việt không dấu, UI/comment tiếng Việt có dấu. Không ép Options API vào React hoặc tự chuyển toàn app sang TypeScript.
- Dùng services và HTTP client chung, state form tại màn hình. Không thêm nhiều lớp hoặc thư viện khi chưa cần.
- Quyền, giá, quota, deadline và trạng thái do Backend kiểm tra. Token/ID lưu máy không thay ownership; không đưa secret vào `EXPO_PUBLIC_*`.
- Giữ UUID khi retry cùng ý định; giữ nguyên chuỗi `updated_at`. Hủy request/listeners và bỏ response cũ khi đổi phiên; ảnh/chat riêng không tồn tại trong cache tài khoản khác.
- Các mục CHỜ CHỐT trong phạm vi chỉ chặn hành vi phụ thuộc. Chủ dự án đã chọn Android trước, phiên 30 ngày, nhiều thiết bị và logout phiên hiện tại; push và phát hành store chưa chốt. Các quyết định kỹ thuật thông thường không cần hỏi lại.
- Không tự chạy build cloud, publish, migration trên dữ liệu chính hoặc gửi push tới người dùng chỉ để kiểm tra app. Thực hiện trong phạm vi công việc được chủ dự án yêu cầu.

## Kiểm tra và bàn giao

Theo [kế hoạch kiểm thử](docs/KIEM_THU.md). Thay đổi tài liệu chỉ kiểm tra nội dung, links, UTF-8 và tính nhất quán; không chạy lại build/test ứng dụng để xác minh tài liệu. Thay code kiểm tra đúng module, kiểm chứng lifecycle/native trên thiết bị khi có ảnh hưởng. Export thành công không phải build APK/IPA hoặc nghiệm thu điện thoại.

Báo cáo file/hành vi thay đổi, kiểm tra đã thực sự chạy và môi trường, giới hạn/chờ chốt, cách mở hoặc chạy kết quả. Không tự tạo subagent khi chủ dự án chưa yêu cầu.
