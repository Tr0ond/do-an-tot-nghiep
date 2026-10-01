# Danh mục bài tập — hợp đồng triển khai

Phạm vi: phần bài tập của M02, gồm nhập catalog, xem danh sách và chi tiết. Không bao gồm CRUD Admin, gói dịch vụ hoặc giáo án mẫu.

| Use case | Actor/quyền | Endpoint | Dữ liệu/kết quả |
| --- | --- | --- | --- |
| Bộ lọc | Công khai, không cần gói/session | GET /api/v1/bai-tap/bo-loc | Nhóm cơ hoạt động, số bài hiển thị, dụng cụ có bài hiển thị |
| Danh sách | Công khai | GET /api/v1/bai-tap | Chỉ bài HOAT_DONG thuộc nhóm HOAT_DONG; data và meta phân trang |
| Chi tiết | Công khai | GET /api/v1/bai-tap/{id} | Cùng điều kiện hiển thị; bài/nhóm ngừng dùng hoặc ID không có trả 404 |
| Nhập catalog | Người chạy CLI Backend | php artisan db:seed --class=BaiTapSeeder | Nhóm cơ và bài mới trong một transaction; chạy lại giữ dữ liệu đã có |

GET chỉ đọc, không đổi trạng thái, không cần CSRF. Không có endpoint ghi công khai. Query danh sách: `tu_khoa` chuỗi tối đa 100 ký tự, tìm tên gốc/tên Việt/mã nguồn; `%` và `_` được tìm như ký tự thật. `nhom_co_id` phải là nhóm hoạt động, `dung_cu_nguon` là nhãn nguồn chính xác có bài hiển thị. `page` từ 1 đến 100000; `per_page` từ 1 đến 48, mặc định 12. Bộ lọc kết hợp bằng AND, thứ tự ID tăng dần; trang ngoài kết quả trả data rỗng, giữ metadata. Input sai trả 422/errors tiếng Việt; lỗi database/mạng hiển thị lỗi và cho thử lại.

Danh sách không trả JSON hướng dẫn 10 ngôn ngữ hoặc đường dẫn nguồn nội bộ. Chi tiết ưu tiên hướng dẫn/bước tiếng Việt nếu có, nếu chưa có dùng tiếng Anh và trả `ngon_ngu_huong_dan`. FE giữ nội dung dạng text, gắn `lang` thích hợp; không tự dịch, không dùng v-html, không tự suy đoán độ khó/chỉ tiêu tập. Trả tên gốc, tên Việt nếu có, nhóm cơ, dụng cụ, cơ phụ nguồn, ảnh/GIF và ghi công media. Media là đường dẫn `/media/bai-tap/...` theo origin Backend; FE ghép với origin API. GIF chỉ tải khi người dùng bấm xem, có nút dừng để quay về ảnh tĩnh.

Seeder đọc JSON đã chuẩn hóa trong BE/database/data; kiểm tra số lượng, mã nguồn/nhóm và tồn tại ảnh/GIF trước khi ghi. Ghép nhóm bằng `ma_nhom_co`, bài bằng `(nguon_du_lieu, ma_nguon)`, không dùng ID cố định từ JSON làm PK database. Chỉ thêm dữ liệu còn thiếu, không đổi nội dung/trạng thái/nhóm của record đã có, không TRUNCATE/tắt FK. Ràng buộc UNIQUE vẫn bảo vệ database; chạy seeder tuần tự. Lỗi bất kỳ rollback lần nhập; giữ tài khoản và dữ liệu khác. Seeder catalog có thể chạy độc lập với seeder tài khoản demo.

Giao diện `/bai-tap`, `/bai-tap/:id`: bộ lọc có nhãn rõ, tìm kiếm bằng form submit, URL lưu bộ lọc/trang để refresh và quay lại danh sách đúng vị trí. Có loading, empty, lỗi/404, retry và fallback khi media hỏng; phân trang server. Liên kết từ trang chủ và khu vực cá nhân. Ghi công Gym visual tại mọi trang có media, nguồn dataset tại trang chi tiết. Giữ Vue 3 Options API/JavaScript, Bootstrap và màu sắc hiện có.

Kiểm thử cần kiểm tra dữ liệu đủ và JSON nguyên vẹn, nhập lại/bảo toàn bản biên tập, ánh xạ khi ID nhóm thay đổi, rollback, API công khai không lộ record ngừng dùng, bộ lọc/tìm kiếm/phân trang/validation, ảnh/GIF, quay lại bằng URL và desktop/tablet/mobile. Database kiểm thử riêng trên MariaDB hiện tại; không tuyên bố đã kiểm thử tranh chấp trên MySQL thật.
