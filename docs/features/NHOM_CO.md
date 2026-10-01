# Nhóm cơ — M02

Admin đang hoạt động quản lý T10 `nhom_co`; KH/PT không có quyền quản trị. Không xóa nhóm hoặc di chuyển/xóa bài tập khi đổi trạng thái. API dưới `/api/v1/admin/nhom-co`, yêu cầu session Sanctum; ghi yêu cầu CSRF.

| Endpoint | Dữ liệu và hành vi |
| --- | --- |
| GET `/` | `tu_khoa`, `trang_thai`, `page`, `per_page`; tìm literal theo tên/mã/tên nguồn, phân trang mặc định 12, tối đa 48 |
| GET `/{id}` | Nhóm cùng tổng bài, số bài hoạt động, số bài đang hiển thị và phiên bản |
| POST `/` | `ma_nhom_co`, `ten_nhom_co`; mã được trim/chuyển thường, 1–64 ký tự theo `[a-z][a-z0-9_]*`, unique; tên 1–255 ký tự. Tạo `HOAT_DONG`, tên nguồn ban đầu bằng tên nhập |
| PUT `/{id}` | Chỉ `ten_nhom_co`, `updated_at`; giữ mã và tên nguồn để seeder tiếp tục ánh xạ chính xác |
| PATCH `/{id}/trang-thai` | `trang_thai` = `HOAT_DONG` hoặc `NGUNG_SU_DUNG`, `updated_at` |

Không nhận ID, timestamps, số bài hoặc metadata nguồn do client gửi ngoài hợp đồng. Không có DELETE. POST trùng mã trả 422; UNIQUE `uq_t10_01` chặn cả khi bỏ qua FormRequest hoặc request cạnh tranh. FE khóa submit trong lúc gửi; lỗi mạng giữ form để kiểm tra danh sách trước khi thử lại, không tự coi mã trùng là tạo thành công.

Sửa/trạng thái khóa hàng trong transaction và kiểm tra phiên bản `updated_at` định dạng `Y-m-d H:i:s.u`. Record cũ có timestamp NULL được gửi `updated_at: null`, chỉ hợp lệ khi database cũng NULL. Bản cũ trả 409; không ghi đè, FE giữ form và yêu cầu tải lại. Noop giữ phiên bản; thay đổi thật tăng phiên bản ngay cả khi đồng hồ bị đóng băng trong test. Lỗi ghi rollback, không thay đổi các bảng liên quan.

`so_bai_tap` tính tất cả bài; `so_bai_hoat_dong` tính trạng thái riêng của bài; `so_bai_hien_thi` bằng số bài hoạt động nếu nhóm hoạt động, bằng 0 nếu nhóm ngừng. Ngừng nhóm không thay trạng thái từng bài. API công khai/list/detail/bộ lọc chỉ hiển thị bài và nhóm hoạt động; khôi phục không bật lại bài đã ngừng riêng. Admin vẫn đọc được nhóm/bài ngừng.

Service bài tập không cho thêm/chuyển bài sang nhóm ngừng (được giữ nhóm cũ khi sửa bài). Service giáo án không cho thêm bài thuộc nhóm ngừng hoặc duyệt giáo án chứa bài đó; bản đã duyệt vẫn được giữ và chi tiết PT báo `kha_dung: false`. Không tự thu hồi duyệt hoặc sửa snapshot kế hoạch/phiên tập.

FE: `/admin/nhom-co` danh sách/filter/phân trang, xác nhận ngừng/khôi phục cùng số bài ảnh hưởng, liên kết danh sách bài theo nhóm; `/admin/nhom-co/them` và `/:id/sua` dùng chung form. Form copy dữ liệu, lỗi cạnh trường, loading/empty/error, xử lý 401/403/419, 409, 422 và phản hồi muộn. Giữ Vue Options API và service Axios chung. Không thêm migration hoặc seed lại catalog cho module này.
