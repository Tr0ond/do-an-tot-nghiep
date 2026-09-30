# AGENTS.md — Hướng dẫn làm việc trong dự án tốt nghiệp

## Đọc ngữ cảnh tối thiểu

1. Đọc `README.md` và `PROJECT_RULES.md` trước khi sửa code.
2. Đọc `docs/DECISIONS.md` và `CODE_STYLE.md`.
3. Chỉ đọc tài liệu module liên quan: `SCOPE.md`, `docs/API_CONVENTIONS.md`, `docs/DATABASE_DRAFT.md` hoặc `docs/features/`.
4. Tra `docs/REFERENCE_CODE_REVIEW.md` khi cần hiểu lý do chọn phong cách code. Không mặc định đọc lại tất cả ba dự án tham khảo.

`PROJECT_RULES.md` là nguồn quy tắc chính. Không tự sửa quy tắc để hợp thức hóa cách triển khai. Mọi quyết định đánh dấu `CHỜ CHỐT` phải được chủ dự án xác nhận trước khi triển khai hành vi phụ thuộc quyết định đó; vẫn tiếp tục các phần độc lập.

## Phạm vi và trạng thái

- Đúng ba tác nhân: `KHACH_HANG`, `HUAN_LUYEN_VIEN`, `ADMIN`.
- Frontend Vue 3, JavaScript, Options API. Không tự chuyển toàn bộ sang TypeScript, Composition API, React hoặc Inertia.
- Backend Laravel; Frontend/Backend độc lập trong `FE/` và `BE/` ở thư mục gốc. Mẫu code nằm trong `templates/`; `Base/` chỉ giữ chỉ mục.
- Chatbot tư vấn và chat realtime là phạm vi chủ dự án yêu cầu. AI không phải tác nhân nghiệp vụ thứ tư.
- Đây là bộ khung tài liệu; chưa có runtime. Không báo build/test PASS khi chưa khởi tạo ứng dụng và chạy kiểm tra.
- Không mở rộng sang nhiều chi nhánh, gọi video, dinh dưỡng điều trị, AI tự áp dụng kế hoạch hoặc tự thanh toán.

## Cách triển khai

Trước một use case: xác định actor, dữ liệu, endpoint, quyền theo tài nguyên, validation, trạng thái trước/sau, failure cases, transaction, chống trùng và test.

- Component trình bày và quản lý trạng thái cục bộ; gọi qua `services/`, không lặp cấu hình Axios ở từng màn hình.
- Pinia chỉ chứa trạng thái dùng chung. Có thể dùng Options Store để giữ cách viết quen thuộc.
- Laravel Controller nhận request và trả response. FormRequest validate; Policy/Service kiểm tra quyền; Service xử lý nghiệp vụ cần transaction.
- Không thêm Repository/DDD/microservices chỉ để tăng số lớp. CRUD đơn giản có thể xử lý bằng Eloquent trực tiếp sau validation/authorization.
- Không coi router guard hoặc `localStorage` là chứng cứ phân quyền. Backend quyết định.
- Không ghi API key AI trong Vue, không đọc/in `.env` của các dự án tham khảo.
- Không copy thư mục `vendor`, `node_modules`, file upload hay dữ liệu khách hàng thật từ dự án cũ.
- Đối chiếu cấu trúc hiện có trước khi cập nhật đường dẫn. Không tạo lại thư mục chủ dự án đã bỏ chỉ để khớp tài liệu cũ; cập nhật tài liệu theo vị trí thực tế.
- Chỉ dùng subagent khi chủ dự án yêu cầu hoặc có chỉ định cụ thể cho task; không tự chia nhỏ mọi bước.

## Quy ước

- Biến/hàm nghiệp vụ: tiếng Việt không dấu camelCase, ví dụ `taiDanhSach`, `xacNhanBuoiTap`.
- Class PHP: PascalCase, ví dụ `LichHenService`.
- Bảng/cột DB và payload: snake_case, ví dụ `khach_hang_id`, `so_buoi_con_lai`.
- Tên Vue page: `views/Admin/GoiTap/index.vue`; component dùng chung: PascalCase.
- Comment tiếng Việt có dấu, giải thích lý do và quy tắc; không diễn giải lại từng dòng.
- Mã kỹ thuật Laravel/framework giữ nguyên: `store`, `update`, `authorize`, `rules`, `created`, `mounted`.
- Tài liệu/nguồn code UTF-8. Dùng `rtk` cho shell nếu môi trường làm việc cung cấp hoặc yêu cầu; không cài công cụ mới ngoài phạm vi task.

## Kiểm thử và báo cáo

Kiểm thử đúng phần thay đổi; ưu tiên phân quyền, double-submit, trùng lịch, trừ lượt, rollback, chat reconnect/revocation và chatbot không bịa dữ liệu. Tranh chấp dữ liệu cần kiểm tra trên MySQL, không chỉ SQLite/Event fake.

Khi bàn giao báo cáo:

1. Đã thay đổi file/module nào và hành vi tương ứng.
2. Kiểm thử đã thực sự chạy, kết quả và môi trường.
3. Giới hạn/chưa hoàn thành, quyết định đang chờ.
4. Cách chủ dự án xem hoặc chạy kết quả.

Thay đổi tài liệu chỉ cần kiểm tra nội dung, link, encoding và tính nhất quán. Không viết test ứng dụng để kiểm tra một bộ tài liệu.
