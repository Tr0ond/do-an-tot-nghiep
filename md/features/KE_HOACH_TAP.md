# Giáo án cá nhân — M05

Theo R12/C19: PT tạo cho KH đang phụ trách, từ catalog hoặc sao chép giáo án mẫu đã duyệt. KH xem/xác nhận giáo án PT trong 24 giờ từ lúc gửi. C31 bổ sung KH tự tạo miễn phí từ catalog và tự áp dụng không cần PT duyệt, kể cả chưa mua gói/chưa có PT. C33 quy định mỗi KH tối đa một giáo án đang áp dụng, chung cho hai nguồn; chọn bản mới lưu trữ bản cũ, giữ kết quả. KH được ngừng áp dụng bản PT của mình. M05 chưa tạo lịch tự tập, ghi nhật ký hoặc trừ buổi PT (M06 tiếp theo).

## Use case và quyền

- PT đọc danh sách học viên theo phân công hiện tại; đọc kế hoạch đã gửi của KH đang phụ trách, kể cả bản đã áp dụng từ PT trước. Nháp chỉ tác giả xem. PT cũ mất toàn bộ quyền khi phân công kết thúc.
- PT tạo/lưu nháp, gửi và hủy đề xuất chưa áp dụng của chính mình trong phân công hiện tại. KH không thấy nháp; KH giữ lịch sử đã gửi khi đổi PT/hết gói.
- KH xác nhận đề xuất chưa hết hạn, tác giả/phân công còn hiệu lực và tài khoản PT hoạt động. Admin không soạn hoặc xác nhận thay KH.
- Gửi chỉ khi mỗi ngày có bài, bài/nhóm còn hoạt động; lưu nháp cũng kiểm tra catalog. Snapshot tên/hướng dẫn/media lúc gửi giữ nội dung KH đã xem. Không sửa nội dung đã gửi/áp dụng.
- Snapshot kế hoạch gốc tại lúc tạo nháp: nếu bản đang áp dụng đã thay đổi, gửi/xác nhận trả 409. Xác nhận thay thế lưu trữ bản gốc trước khi áp dụng bản mới trong một transaction; những đề xuất khác còn chờ trở thành DA_HUY.

## API / trạng thái / lỗi

API dưới `/api/v1`, session Sanctum và tài khoản hoạt động:

| Endpoint | Quyền |
| --- | --- |
| GET `/pt/hoc-vien` | PT, tìm `tu_khoa`, phân trang 20 |
| GET/POST `/pt/hoc-vien/{khachId}/ke-hoach` | PT đang phụ trách, danh sách/tạo nháp |
| GET `/khach-hang/ke-hoach` | KH, giáo án PT đã gửi và bản tự tạo chưa ẩn; `da_an=1` xem mục Đã ẩn |
| GET `/pt/ke-hoach/{id}` / `/khach-hang/ke-hoach/{id}` | Scope như trên |
| PUT `/pt/ke-hoach/{id}` | Chỉ nháp của PT hiện tại |
| POST `/pt/ke-hoach/{id}/gui` / `/huy` | Nháp/gửi; hủy nháp hoặc đề xuất chưa áp dụng |
| POST `/khach-hang/ke-hoach/{id}/xac-nhan` | Chính KH, chưa quá hạn 24 giờ |

Tạo có UUID `client_request_id`; retry so hash nội dung ban đầu và actor/KH/phân công, trả cùng ID kể cả nháp đã sửa. Sửa/gửi/hủy/xác nhận dùng `updated_at` dạng `Y-m-d H:i:s.u`; mutation stale trả 409. Gửi/xác nhận/hủy lặp trả trạng thái hiện tại khi hành động đó đã thành công và quyền vẫn hợp lệ, không đặt lại hạn/nhân thông báo. Không có DELETE. Các field hệ thống bị chặn.

Trạng thái lưu: NHAP → CHO_DUYET → DANG_AP_DUNG → LUU_TRU; nháp/chờ có thể DA_HUY. CHO_DUYET có `han_duyet <= now` hiển thị QUA_HAN, server từ chối xác nhận kể cả chưa có scheduler; không đổi TTL khi đọc/retry. Danh sách phân trang 20, tối đa 500 bài/30 ngày, tên/mục tiêu 255 ký tự, hiệp 1–100, lần lặp 1–1000, nghỉ 0–3600 giây, ghi chú 2000 ký tự, mức tạ có thể bỏ trống hoặc 0–1000 kg, tối đa hai chữ số thập phân.

Khóa hàng KH cùng thứ tự với PhanCongService, rồi đọc lại phân công/plan bằng locking read; chống snapshot MariaDB cũ, tranh chấp đổi PT/duyệt. C33/migration000039 dùng unique `(khach_dang_ap_dung_id)` cho tối đa một giáo án hoạt động/KH bất kể nguồn. Mọi thay đổi parent/child/thông báo trong transaction; rollback không để thông báo mồ côi. KH nhận chuông khi PT gửi; PT nhận chuông khi KH xác nhận. Bản ghi chỉ nhìn thấy sau commit, ID UUID xác định theo plan/hành động; không gửi email/WebSocket chứa giáo án. Chuông polling có sẵn tải thông báo sau commit.

Migration 000036 thêm ngày tập/UUID/hash và mức tạ, không reset dữ liệu. Số ngày của giáo án cũ lấy từ ngày lớn nhất trong các bài, tối thiểu một. Rollback từ chối khi có UUID hoặc mức tạ mới để tránh mất dữ liệu. Bản cũ không có hash không dùng làm retry mới. Xác nhận/gửi/hủy kiểm tra trạng thái, deadline và quyền tài nguyên trước thao tác. 401 phiên thiếu, 403 role/khóa, 404 ngoài scope, 409 trạng thái/stale/TTL, 422 validation.

## Giao diện

PT có `/pt/hoc-vien` → `/pt/hoc-vien/:khachId/ke-hoach`, tạo tại `/pt/hoc-vien/:khachId/ke-hoach/them`, sửa nháp tại `/pt/ke-hoach/:id/sua` và chi tiết tại `/pt/ke-hoach/:id`. KH có `/khach-hang/ke-hoach` và `/khach-hang/ke-hoach/:id`. Các trang giữ menu/theme của `CaNhanLayout`; Admin không có màn hình mới trong M05.

Trình soạn giữ dữ liệu khi lỗi, chặn double-submit, giữ UUID/payload khi chưa rõ kết quả tạo và yêu cầu tải lại khi 409. Gửi/hủy/áp dụng có bước xác nhận trong trang. Hướng dẫn bài chọn tiếng Việt khi có, nếu không dùng tiếng Anh; GIF tải khi mở phần hướng dẫn. [Kết quả kiểm chứng](../verification/M05_KE_HOACH_TAP.md).

## KH tự tạo — C31

- KH đọc mọi bản tự tạo của mình, kể cả nháp; PT đang phụ trách đọc tất cả bản tự tạo của KH và biết bản nào `DANG_AP_DUNG`. PT chỉ đọc, không sửa/gửi/hủy/áp dụng thay; kết thúc phân công thu hồi quyền ngay. Giáo án PT vẫn giữ quy tắc nháp chỉ tác giả đọc và KH duyệt trong 24 giờ.
- POST `/khach-hang/ke-hoach` tạo nháp từ catalog, không cần gói/PT; PUT `/{id}` sửa nháp tự tạo; POST `/{id}/ap-dung`, `/luu-tru`, `/huy` tự áp dụng, ngừng áp dụng hoặc hủy nháp. UUID/phiên bản/giới hạn thông số như luồng PT. Nguồn, chủ sở hữu, liên kết PT và trạng thái chỉ Backend gán; payload giả mạo bị 422. C33 mở `/luu-tru` cho giáo án PT đã nhận; C34 mở `/ap-dung` cho bản PT đã xác nhận trước đây và lưu trữ. `/huy` của KH vẫn chỉ dùng cho nháp tự tạo.
- NHAP → DANG_AP_DUNG → LUU_TRU; bản lưu trữ được chọn áp dụng lại với version mới. DA_HUY không áp dụng lại. Nội dung từng bản đã áp dụng bất biến; cần tạo bản mới nếu đổi bài/thông số. Khi áp dụng nháp, mỗi ngày cần bài, catalog còn hoạt động và snapshot được chốt lại. Áp dụng lại bản lưu trữ giữ snapshot cũ.
- Khóa KH trong transaction trước đổi trạng thái, lưu trữ bản đang dùng bất kể nguồn theo C33 rồi áp dụng bản được chọn. Hai yêu cầu tự chọn khác bản đồng thời được tuần tự hóa; lựa chọn commit sau là bản đang dùng, cả hai bản được giữ. Xác nhận PT còn kiểm tra bản gốc của đề xuất; bản gốc thay đổi thì 409. Retry cũ khi bản tự tạo đã bị thay trả 409; retry khi đã ở trạng thái đích trả hiện trạng không tăng version. Rollback giữ cả trạng thái cũ và snapshot.
- Migration000037 thêm `nguon_tao` mặc định PT cho dữ liệu cũ, nullable PT/phân công cho bản KH, unique `(nguon_tao, khach_dang_ap_dung_id)`; giữ FK, không reset. Down từ chối khi có bản tự tạo để giữ lịch sử. Không gửi thông báo PT cho mỗi lần tự tạo hoặc tự áp dụng trong phần này; PT xem trong danh sách học viên.
- Danh sách có lọc `nguon_tao=PT|KHACH_HANG`, nhãn nguồn và trạng thái. KH có nút **Tự tạo giáo án**, trang `/khach-hang/ke-hoach/them`, sửa `/khach-hang/ke-hoach/:id/sua`. Dùng trình soạn Options API chung với PT; KH chọn từ catalog, không mở thư viện giáo án mẫu dành cho PT.

[Kiểm chứng giáo án tự tạo](../verification/M05_TU_TAO_GIAO_AN.md).

## Ẩn / hiện lại giáo án tự tạo — C32

- Actor KH sở hữu bản nguồn `KHACH_HANG`; chỉ `DA_HUY` hoặc `LUU_TRU` được ẩn. Nháp cần hủy trước, bản đang dùng cần ngừng áp dụng trước. Giáo án PT giao và Admin/PT không có quyền ẩn thay KH.
- POST `/khach-hang/ke-hoach/{id}/an` và `/hien-lai` nhận duy nhất `updated_at`. API trả `da_an`, `khach_an_luc`, `co_the_an`, `co_the_hien_lai`. Chặn ghi trực tiếp thời điểm ẩn qua payload tạo/sửa.
- GET danh sách KH mặc định `da_an=0`; `da_an=1` chỉ lấy bản đã ẩn, kết hợp lọc nguồn và phân trang 20. GET chi tiết vẫn đọc được. PT hiện tại luôn xem toàn bộ giáo án trong scope, kể cả KH đã ẩn; kết thúc phân công thu hồi quyền như trước.
- `khach_an_luc` DATETIME(6) nullable là lựa chọn hiển thị, không phải trạng thái nghiệp vụ. Ẩn/hiện lại giữ nguyên snapshot, dòng bài tập, trạng thái và lịch sử; không sinh thông báo hoặc trừ buổi. Hiện lại không tự áp dụng; bản đã ẩn phải hiện lại trước khi áp dụng. Bản đã hủy vẫn không thể áp dụng lại.
- Khóa KH rồi giáo án trong transaction; ghi yêu cầu version hiện tại, stale/trạng thái không phù hợp trả 409. Retry đã ở đích trả hiện trạng, không tăng version hoặc đặt lại thời điểm ẩn. Tranh chấp ẩn/áp dụng chỉ một thao tác thành công, không có giáo án đang dùng bị ẩn.
- Migration000038 chỉ thêm cột nullable, giữ dữ liệu cũ chưa ẩn. Down từ chối khi còn bản đã ẩn; cần hiện lại trước để không mất lựa chọn. Không có DELETE.
- KH mở chi tiết → **Ẩn giáo án**, xác nhận trong trang; chọn **Hiển thị → Đã ẩn** để xem và **Hiện lại giáo án**. PT thấy nhãn **KH đã ẩn**, chỉ đọc. Giữ CaNhanLayout/theme, chống double-submit và bỏ response sau chuyển tài nguyên.

[Kiểm chứng ẩn giáo án](../verification/M05_AN_GIAO_AN.md).

## Một giáo án đang áp dụng / ngừng giáo án PT — C33

- POST KH `/{id}/luu-tru` dùng cho cả hai nguồn, bản đang áp dụng của chính KH. Không cần gói, PT duyệt hoặc phân công còn hiệu lực khi ngừng bản PT đã nhận. Nháp/chờ xác nhận không được ngừng áp dụng; KH khác/Admin/PT không được ngừng thay. API `co_the_luu_tru` phản ánh quyền này.
- Ngừng chuyển `DANG_AP_DUNG → LUU_TRU`, không tự chọn bản khác, không sửa bài/snapshot, không tạo lịch/trừ buổi. Retry bản đã lưu trữ trả hiện trạng; version cũ khi còn đang dùng trả409; lỗi lưu rollback hoàn toàn. PT hiện tại xem trạng thái trong danh sách/chi tiết. Không bổ sung thông báo riêng cho ngừng ở phần này.
- Tự áp dụng KH hoặc xác nhận PT đều lưu trữ bản đang dùng trước đó, chung hai nguồn. Gửi/xác nhận PT kiểm tra `thay_the_ke_hoach_id` so với bản đang áp dụng chung; đề xuất cũ không ghi đè lựa chọn mới của KH. Giới hạn không áp dụng lại giáo án PT lưu trữ đã được C34 thay thế cho bản đã xác nhận trước đây.
- Migration000039 gộp dữ liệu cũ: giữ bản đang áp dụng có `COALESCE(updated_at,duyet_luc,created_at)` lớn nhất (ID lớn hơn khi hòa), lưu trữ bản còn lại và tăng version; không xóa bài/snapshot. Chạy trong maintenance trước đổi unique. Down chỉ phục hồi index theo nguồn, không tự kích hoạt bản đã lưu trữ.
- KH thấy nút **Ngừng áp dụng** trên chi tiết giáo án PT, xác nhận trong trang; giao diện giải thích chỉ một bản đang dùng và việc chọn bản mới lưu trữ bản cũ. C32 vẫn chỉ cho ẩn bản tự tạo.

[Kiểm chứng C33](../verification/M05_MOT_GIAO_AN.md).

## Áp dụng lại giáo án PT đã xác nhận — C34

- Actor KH, đúng chủ sở hữu, bản nguồn PT đã gửi và đã xác nhận (`gui_luc` và `duyet_luc` không null). POST `/khach-hang/ke-hoach/{id}/ap-dung` nhận `updated_at`; `LUU_TRU → DANG_AP_DUNG`. Không cần gói, PT duyệt lại, PT cũ hoạt động hoặc phân công cũ còn hiệu lực; không khôi phục quyền PT cũ.
- Nháp ngoài scope trả404. Đề xuất chưa xác nhận, quá hạn, đã hủy hoặc bản lưu trữ không có dấu xác nhận trả409; không cho dùng `/ap-dung` thay cho `/xac-nhan` lần đầu. Stale version khi còn lưu trữ trả409. Retry bản đã áp dụng trả hiện trạng, không tăng version hay thông báo.
- Khóa KH rồi giáo án; lưu trữ bản đang dùng bất kể nguồn và áp dụng bản chọn trong một transaction. Ràng buộc unique chung của000039 vẫn áp dụng; hai lựa chọn khác bản đồng thời tuần tự hóa, bản commit cuối là bản đang dùng. Lỗi ghi rollback cả hai trạng thái/phiên bản.
- Giữ snapshot/dòng bài/thông số và `duyet_luc`, `gui_luc`, `han_duyet` ban đầu, kể cả catalog đã ngừng dùng. Không tạo lịch, trừ lượt hoặc gửi lại thông báo xác nhận. PT hiện tại xem trạng thái theo scope đọc hiện có.
- API `co_the_ap_dung` trảtrue cho KH sở hữu bản PT đã xác nhận đang lưu trữ, false cho PT và các trạng thái khác. KH thấy **Áp dụng lại giáo án**, xác nhận thay bản hiện tại trong trang và nhận thông báo thành công. Quyền sửa, hủy, ẩn/hiện không mở rộng sang giáo án PT.
- Không đổi schema, không cần migration mới. [Kiểm chứng C34](../verification/M05_AP_DUNG_LAI_PT.md).
