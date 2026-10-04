# Chỉ số cơ thể C38 — kiểm chứng 04/10/2026

## Kết quả và phạm vi

Đã triển khai KH ghi chiều cao/cân nặng/ngày/ghi chú, BMI tính ở Backend, số đo gần nhất, thay đổi cân nặng, biểu đồ cân nặng/BMI và bảng lịch sử. Khoảng7/30/90 ngày có ngày kết thúc tùy chọn để truy cập các lần đo cũ. KH sửa bản ghi có kiểm tra phiên bản; không xóa lịch sử. Miễn phí, không cần gói/PT. PT hiện được phân công chỉ đọc. AI nhận số đo khi KH bật dữ liệu cá nhân, không nhận ghi chú hoặc vòng eo. [Hợp đồng](../features/CHI_SO_CO_THE.md).

Không thêm migration: dùng bảng `chi_so_co_the` đã có. Cột vòng eo cũ giữ nullable để không xóa dữ liệu, không xuất hiện trong API/giao diện/ngữ cảnh AI. Không seed chỉ số giả vào database chính.

## Các file/module

- BE: model `ChiSoCoThe`, request/controller/service cùng tên, route KH/PT; `ChatbotService` thêm ngữ cảnh theo tùy chọn, `GeminiService` bổ sung giới hạn diễn giải BMI.
- FE: `views/ChiSoCoThe/index.vue`, service/utils, router, menu KH, nút chỉ số trên danh sách học viên PT; `KhungTroLy.vue` bổ sung thông tin dữ liệu cá nhân gửi AI.
- Tests: `ChiSoCoTheTest`, worker concurrency và fixture UI riêng, `chiSoCoThe.spec.js`.
- Tài liệu: C38 trong quyết định/quy tắc, hợp đồng feature/API, chỉ mục và trạng thái README/ROADMAP.

## Kiểm thử thực sự chạy

Windows, Laravel 13/PHP CLI 8.4.0, MariaDB 10.4.32; FE Vue 3/Vitest 5/Vite 8.3.1. Database kiểm thử được tạo riêng theo tên có tiền tố và mã ngẫu nhiên; concurrency dùng hai PHP process với MariaDB thật, không SQLite/Event fake.

| Kiểm tra | Kết quả |
| --- | --- |
| `php artisan test --compact --filter="ChiSoCoTheTest\|ChatbotTest"` | 35 tests,386 assertions PASS |
| Toàn FE `npm run test` | 25 files,231 tests PASS |
| `npm run lint:check` | PASS |
| `npm run format:check` | PASS |
| `npm run build` | PASS |
| Pint các file PHP liên quan | PASS |
| `git diff --check` | PASS |

Chín tests chỉ số Backend kiểm tra: KH chưa mua gói vẫn ghi; công thức BMI; giới hạn nhập/decimal/ngày Việt Nam; từ chối BMI/KH ID/vòng eo; ownership; PT readonly/phân công tương lai hoặc đã kết thúc; Admin bị từ chối; không lộ dữ liệu KH khác; cùng ngày retry không tạo thêm và dữ liệu khác409; sửa phiên bản/va chạm ngày; chiều cao lịch sử bất biến; dữ liệu cũ thiếu chiều cao trả BMI null; khoảng ngày/phân trang/delta; AI opt-in và giới hạn10 mốc/không ghi chú; rollback khi save lỗi; hai process ghi đồng thời chỉ tạo một bản ghi.

Mười một tests FE kiểm tra tính BMI preview, khoảng cách thời gian thực trên biểu đồ, payload cho phép, form KH/PT, bản sao khi sửa, gợi ý chiều cao mới sau lưu, chống gửi lặp, giữ input khi409/422, xóa dữ liệu khi quyền PT bị thu hồi và bỏ response cũ.

## Kiểm tra trình duyệt

Chạy Backend8017/Frontend5291 với database và tài khoản giả riêng, không thay môi trường8000/5173 hay dữ liệu chính. KH đăng nhập qua UI, vào menu mới, lưu70kg/175cm ngày04/10 → BMI22,86; sửa69,8kg →22,79. Chuyển BMI/cân nặng,7/30/90 ngày và ngày kết thúc cũ đã chạy. PT đăng nhập → Học viên & giáo án → Chỉ số cơ thể: đúng học viên, thấy lịch sử/biểu đồ, không form/nút sửa. Console không có lỗi/cảnh báo trong lần kiểm tra cuối.

Đã kiểm tra1440×900,768×1024 và390×844, sáng/tối. Không tràn ngang trang; bảng lịch sử cuộn riêng. Ảnh chụp dùng dữ liệu giả:

- [KH desktop sáng](chi-so-kh-desktop-light.jpg), [tối](chi-so-kh-desktop-dark.jpg).
- [KH tablet sáng](chi-so-kh-tablet-light.jpg), [tối](chi-so-kh-tablet-dark.jpg).
- [KH điện thoại sáng](chi-so-kh-mobile-light.jpg), [tối](chi-so-kh-mobile-dark.jpg).
- [PT chỉ đọc](chi-so-pt-desktop-light.jpg).

## Cách xem và giới hạn

KH đăng nhập rồi chọn **Chỉ số cơ thể** ở menu, hoặc `/khach-hang/chi-so-co-the`. PT chọn **Học viên & giáo án → Chỉ số cơ thể** của học viên. Không cần migration mới hoặc seed lại. Máy clone chưa có database vẫn chạy các migrations theo hướng dẫn chung.

BMI là thông tin tham khảo; không phân loại sức khỏe, không suy ra cơ/mỡ. Ngữ cảnh AI và hành vi bật/tắt đã kiểm thử bằng provider giả lập; chưa đánh giá lại câu trả lời Gemini thật với các số đo mới. Chưa thêm KPI chỉ số vào dashboard KH/PT trong phiên này. Không tuyên bố toàn bộ test Backend hoặc kiểm chứng concurrency trên MySQL8 đã chạy; kết quả ở đây là các tests liên quan trên MariaDB10.4.32.
