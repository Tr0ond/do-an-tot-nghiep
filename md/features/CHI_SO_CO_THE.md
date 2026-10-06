# Chỉ số cơ thể — C38 / M06

Chủ dự án xác nhận ngày04/10/2026: KH ghi chiều cao/cân nặng/ngày/ghi chú, BMI do Backend tính; bỏ vòng eo khỏi chức năng. Không cần gói hoặc PT. Giữ lịch sử theo ngày, cho KH sửa số liệu nhập sai. PT hiện phụ trách chỉ đọc; AI chỉ nhận khi KH bật dữ liệu cá nhân.

## Dữ liệu và quyền

- Dùng bảng `chi_so_co_the` hiện có, unique KH/ngày. Không cần migration mới. Cột vòng eo cũ giữ nullable để tránh xóa dữ liệu, không nhận/trả/dùng trong tính năng.
- Mỗi lần ghi lưu chiều cao và cân nặng tại ngày đo. Chiều cao form mới gợi ý từ bản gần nhất; không sửa chiều cao các ngày cũ.
- BMI = kg / (cm/100)², làm tròn2 chữ số, không lưu cột BMI. Bản cũ thiếu/không hợp lệ trả BMI null, không coi là0.
- Chọn ngày1900-01-01 đến hôm nay giờ Việt Nam; cân nặng10–500kg, chiều cao50–250cm, tối đa2 số thập phân; giới hạn nhập liệu, không phải phân loại sức khỏe. Ghi chú tối đa1000 ký tự.
- KH chỉ đọc/ghi của mình. PT chỉ đọc học viên có phân công bắt đầu <= hiện tại và kết thúc null/> hiện tại; kiểm tra trong transaction khóa KH/phân công để đồng bộ đổi PT. Admin không có endpoint chỉ số cá nhân.
- Không xóa bản ghi. Sửa yêu cầu `updated_at` microsecond (nullable cho dữ liệu cũ). Sai phiên bản409. Một KH/ngày: POST cùng dữ liệu trả bản cũ200, dữ liệu khác409 để KH mở sửa; không ghi đè âm thầm. Khóa KH trước bản ghi và unique DB chống double-submit.

## API dưới /api/v1

- GET `/khach-hang/chi-so-co-the`; POST cùng URL; PUT `/{id}`.
- GET `/pt/hoc-vien/{khachId}/chi-so-co-the` chỉ đọc.
- GET nhận `so_ngay=7|30|90` (mặc định30), `page>=1`, `den_ngay=YYYY-MM-DD` (mặc định hôm nay, không nhận ngày tương lai). Lịch sử theo khoảng chọn, tính cả ngày kết thúc,20 hàng/trang, biểu đồ tối đa90 mốc thật. Chọn ngày kết thúc cũ để xem/sửa dữ liệu trước90 ngày. `moi_nhat` là lần đo mới nhất toàn lịch sử đến hôm nay, không phụ thuộc khoảng chọn; thay đổi cân nặng/BMI so đầu-cuối khoảng chọn, null nếu chưa đủ2 mốc hợp lệ. Không nội suy số đo thiếu; không gọi thay đổi là giảm mỡ.
- POST/PUT nhận `ngay_ghi`, `can_nang_kg`, `chieu_cao_cm`, `ghi_chu` nullable; PUT thêm `updated_at`. Không nhận KH ID, BMI, vòng eo hoặc trường lạ.400/401/403/404/409/422 theo hợp đồng chung; phản hồi private,no-store.

## Giao diện và AI

- Menu KH **Chỉ số cơ thể**: ghi mới/sửa, số đo gần nhất, biểu đồ cân nặng hoặc BMI 7/30/90 ngày với lựa chọn **Xem đến ngày**, bảng lịch sử và phân trang.
- PT: **Học viên & giáo án → Chỉ số cơ thể**, cùng màn hình chỉ đọc, không có form/nút sửa.
- Ngữ cảnh Gemini khi bật dữ liệu cá nhân: lần đo gần nhất, tối đa10 mốc trong90 ngày, thay đổi từ mốc đầu-cuối hợp lệ. Không gửi ghi chú hoặc vòng eo. Khi tắt không gửi nhóm dữ liệu này; giữ lọc lịch sử cá nhân hiện có.
- BMI chỉ tham khảo, không phân biệt cơ/mỡ; không chẩn đoán hoặc suy ra %mỡ, không phân loại BMI người chưa trưởng thành. Kết hợp mục tiêu/kinh nghiệm/nhật ký, AI tạo nháp theo C37, không tự áp dụng.

Nguồn công thức và giới hạn: [WHO](https://www.who.int/news-room/fact-sheets/detail/obesity-and-overweight), [CDC](https://www.cdc.gov/bmi/faq/).
