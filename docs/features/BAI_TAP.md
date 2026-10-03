# Danh mục bài tập — hợp đồng triển khai

Phạm vi: phần bài tập của M02, gồm nhập catalog, xem danh sách/chi tiết và Admin quản lý bài tập. Không bao gồm quản lý nhóm cơ, upload media, gói dịch vụ hoặc giáo án mẫu.

| Use case | Actor/quyền | Endpoint | Dữ liệu/kết quả |
| --- | --- | --- | --- |
| Bộ lọc | Công khai, không cần gói/session | GET /api/v1/bai-tap/bo-loc | Nhóm cơ hoạt động, số bài hiển thị, dụng cụ có bài hiển thị |
| Danh sách | Công khai | GET /api/v1/bai-tap | Chỉ bài HOAT_DONG thuộc nhóm HOAT_DONG; data và meta phân trang |
| Chi tiết | Công khai | GET /api/v1/bai-tap/{id} | Cùng điều kiện hiển thị; bài/nhóm ngừng dùng hoặc ID không có trả 404 |
| Nhập catalog | Người chạy CLI Backend | php artisan db:seed --class=BaiTapSeeder | Nhóm cơ và bài mới trong một transaction; chạy lại giữ dữ liệu đã có |

GET chỉ đọc, không đổi trạng thái, không cần CSRF. Không có endpoint ghi công khai. Query danh sách: `tu_khoa` chuỗi tối đa 100 ký tự, tìm tên gốc/tên Việt/mã nguồn; `%` và `_` được tìm như ký tự thật. `nhom_co_id` phải là nhóm hoạt động, `dung_cu_nguon` là nhãn nguồn chính xác có bài hiển thị. `page` từ 1 đến 100000; `per_page` từ 1 đến 48, mặc định 12. Bộ lọc kết hợp bằng AND, thứ tự ID tăng dần; trang ngoài kết quả trả data rỗng, giữ metadata. Input sai trả 422/errors tiếng Việt; lỗi database/mạng hiển thị lỗi và cho thử lại.

Danh sách không trả JSON hướng dẫn 10 ngôn ngữ hoặc đường dẫn nguồn nội bộ; có `anh_url` và `gif_url` công khai. Chi tiết ưu tiên hướng dẫn/bước tiếng Việt nếu có, nếu chưa có dùng tiếng Anh và trả `ngon_ngu_huong_dan`. FE giữ nội dung dạng text, gắn `lang` thích hợp; không tự dịch, không dùng v-html, không tự suy đoán độ khó/chỉ tiêu tập. Trả tên gốc, tên Việt nếu có, nhóm cơ, dụng cụ, cơ phụ nguồn, ảnh/GIF và ghi công media. Media là đường dẫn `/media/bai-tap/...` theo origin Backend; FE ghép với origin API. Ở thư viện, GIF tải khi người dùng bấm xem và có nút dừng. Theo yêu cầu ngày 03/10/2026, riêng cột chọn bài của trình soạn giáo án PT/Admin tải GIF khi rê chuột hoặc focus bàn phím vào thẻ, rời thẻ trở về ảnh tĩnh; không tải GIF sẵn cho toàn danh sách. [Kiểm chứng](../verification/EXERCISE_PICKER_THUMBNAILS.md).

Seeder đọc JSON đã chuẩn hóa trong BE/database/data; kiểm tra số lượng, mã nguồn/nhóm và tồn tại ảnh/GIF trước khi ghi. Ghép nhóm bằng `ma_nhom_co`, bài bằng `(nguon_du_lieu, ma_nguon)`, không dùng ID cố định từ JSON làm PK database. Chỉ thêm dữ liệu còn thiếu, không đổi nội dung/trạng thái/nhóm của record đã có, không TRUNCATE/tắt FK. Ràng buộc UNIQUE vẫn bảo vệ database; chạy seeder tuần tự. Lỗi bất kỳ rollback lần nhập; giữ tài khoản và dữ liệu khác. Seeder catalog có thể chạy độc lập với seeder tài khoản demo.

Giao diện `/bai-tap`, `/bai-tap/:id`: bộ lọc có nhãn rõ, tìm kiếm bằng form submit, URL lưu bộ lọc/trang để refresh và quay lại danh sách đúng vị trí. Có loading, empty, lỗi/404, retry và fallback khi media hỏng; phân trang server. Liên kết từ trang chủ và khu vực cá nhân. Ghi công Gym visual tại mọi trang có media, nguồn dataset tại trang chi tiết. Giữ Vue 3 Options API/JavaScript, Bootstrap và màu sắc hiện có.

Kiểm thử cần kiểm tra dữ liệu đủ và JSON nguyên vẹn, nhập lại/bảo toàn bản biên tập, ánh xạ khi ID nhóm thay đổi, rollback, API công khai không lộ record ngừng dùng, bộ lọc/tìm kiếm/phân trang/validation, ảnh/GIF, quay lại bằng URL và desktop/tablet/mobile. Database kiểm thử riêng trên MariaDB hiện tại; không tuyên bố đã kiểm thử tranh chấp trên MySQL thật.

## Admin quản lý bài tập

Chỉ session ADMIN đang hoạt động được sử dụng các endpoint dưới đây. KH/PT trả 403, chưa đăng nhập 401; endpoint ghi dùng CSRF của Sanctum. Quyền do Backend quyết định, không dựa vào router.

| Use case | Endpoint | Hành vi |
| --- | --- | --- |
| Danh sách | GET /api/v1/admin/bai-tap | Tìm tên/mã, lọc nhóm/trạng thái; cả bài ngừng dùng; 12 bài/trang, tối đa 48 |
| Bộ lọc | GET /api/v1/admin/bai-tap/bo-loc | Tất cả nhóm kèm trạng thái; các nhãn dụng cụ hiện có |
| Biên tập | GET /api/v1/admin/bai-tap/{id} | Nội dung biên tập và updated_at đủ micro giây |
| Thêm | POST /api/v1/admin/bai-tap | Nguồn admin; mã 4 chữ/số, chuẩn hóa uppercase, unique theo nguồn; 201 |
| Sửa | PUT /api/v1/admin/bai-tap/{id} | Sửa nhóm, dụng cụ, tên Việt, hướng dẫn/bước tiếng Việt; 200 |
| Hiển thị | PATCH /api/v1/admin/bai-tap/{id}/trang-thai | Đặt HOAT_DONG hoặc NGUNG_SU_DUNG; 200 |

Payload thêm: ma_nguon, ten_bai_tap, ten_tieng_viet nullable, nhom_co_id, dung_cu nullable, huong_dan_vi nullable tối đa 10000 ký tự, cac_buoc_vi tối đa 30 chuỗi, mỗi chuỗi không rỗng/tối đa 2000 ký tự, trang_thai. Tên/dụng cụ tối đa 255 ký tự. Nhóm mới phải hoạt động. Bài do Admin tạo không có ảnh/GIF, giao diện hiển thị fallback; không gán ảnh hay ghi công của dataset cho bài mới.

Payload sửa: ten_tieng_viet, nhom_co_id, dung_cu, huong_dan_vi, cac_buoc_vi, updated_at; ten_bai_tap chỉ cho bài nguồn admin. Payload trạng thái: trang_thai, updated_at. Không nhận mã/nguồn/media/JSON ngôn ngữ khác hoặc trường ngoài hợp đồng. Giữ nguyên tên gốc, metadata nguồn, media và hướng dẫn ngôn ngữ khác của bài nhập. Xóa nội dung vi chỉ bỏ khóa vi để trang công khai quay về tiếng Anh. Nhóm ngừng dùng có thể giữ nguyên khi biên tập bài đang thuộc nhóm đó, nhưng không được chuyển bài sang nhóm ngừng dùng khác.

Sửa/trạng thái khóa hàng trong transaction, so updated_at gửi lên với phiên bản hiện tại; lỗi bản cũ trả 409 và không ghi đè. Trạng thái đặt cùng giá trị là no-op khi phiên bản vẫn đúng. Tạo trùng mã trả 422, DB UNIQUE bảo vệ cả request trùng; frontend khóa nút khi gửi và giữ mã khi gặp lỗi. Không có DELETE, không đổi FK/tắt ràng buộc hay xóa lịch sử; ngừng hiển thị chỉ loại bài khỏi API công khai. Nội dung kế hoạch đã áp dụng phải dùng snapshot trong module kế hoạch, không dùng biên tập catalog để ghi đè lịch sử.

Giao diện: /admin/bai-tap, /admin/bai-tap/them, /admin/bai-tap/:id/sua; bộ lọc/trang ở URL, có retry/loading/empty, lỗi cạnh trường, giữ form khi lưu lỗi. Khi 409 cho tải lại bản mới trước khi sửa tiếp; không retry ghi tự động. Kiểm thử trên database riêng: quyền/session/CSRF, validation và metadata, trùng mã, xung đột bản cũ, nội dung tiếng Việt, ngừng/khôi phục hiển thị và bản ghi tham chiếu còn tồn tại.
