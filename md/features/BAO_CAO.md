# M09 — Dashboard và báo cáo Admin

## Phạm vi triển khai 04/10/2026

Admin xem báo cáo trong **Tổng quan hệ thống**. KH/PT không có quyền API báo cáo toàn hệ thống. Giữ các thống kê danh mục hiện có; dashboard cá nhân và các sự kiện thông báo bổ sung là bước kế tiếp của M09.

GET `/api/v1/admin/bao-cao`: session hoạt động, middleware ADMIN; `tu_ngay`, `den_ngay` dạng YYYY-MM-DD, cả hai hoặc không có; tối đa366 ngày gồm hai đầu, không nhận ngày tương lai. Mặc định30 ngày đến hôm nay theo Asia/Ho_Chi_Minh. `nhom` là ngay/thang, `pt_page` phân trang20 PT. Validation422, quyền401/403. Các bộ lọc ngày sử dụng khoảng UTC nửa mở từ00:00 ngày đầu đến00:00 sau ngày cuối.

- **Tiền đã nhận**: tổng `thanh_toan.so_tien` có `xac_minh_luc`, `thanh_toan_luc` và trạng thái DA_XAC_MINH/CAN_DOI_SOAT/DA_HOAN_TIEN. Theo ngày nhận tiền, gồm tiền payOS đã xác minh nhưng chưa đủ điều kiện cấp gói; không lấy giá catalog/giá đơn làm tiền thực nhận. Mã giao dịch unique giữ chống cộng lặp.
- **Đã hoàn**: tổng `so_tien_hoan` của khoản DA_HOAN_TIEN đã xác minh, theo `hoan_tien_luc`. Hoàn trong kỳ cho khoản nhận ngoài kỳ vẫn tính; **thực thu sau hoàn** = nhận trong kỳ − hoàn trong kỳ, có thể âm. Đây là dòng tiền đã ghi nhận, không khẳng định doanh thu kế toán.
- **Chờ đối soát trong kỳ**: tiền nhận trong kỳ hiện còn CAN_DOI_SOAT. Không cộng thêm lần hai vào thực thu.
- Đơn mới theo `created_at`, đơn đã kích hoạt theo `kich_hoat_luc`; chia chuyển tiền nhiều lần vẫn chỉ một đơn kích hoạt. Gói đang sử dụng tính tại lúc đọc, cần trạng thái DANG_SU_DUNG, đã kích hoạt và chưa hết hạn; tách chỉ số hiện tại khỏi kỳ báo cáo.
- Buổi PT hoàn thành: trạng thái HOAN_THANH, có `tieu_hao_luc`, theo `ket_thuc_luc` trong kỳ; vắng mặt/quá hạn/đóng xử lý không tính. Không trộn với phiên tự tập.
- Phân bố tiền theo gói dùng ID và tên snapshot trên đơn, giữ tên/giá lịch sử khi catalog đổi. Biểu đồ ngày/tháng bù các mốc không phát sinh bằng0. Có bảng dữ liệu để đọc số chính xác.
- PT: danh sách phân trang, học viên hiện tại dựa trên phân công còn hiệu lực; buổi hoàn thành trong kỳ theo PT trên lịch lịch sử. Không đọc chat riêng, không trả hồ sơ/email KH. Admin thấy tên PT và trạng thái tài khoản.

Chỉ đọc trong transaction snapshot trên MySQL/MariaDB, không khóa để ghi và không gọi payOS/Gemini; không ghi trạng thái gói/lịch khi đọc báo cáo. FE hủy request cũ, bỏ kết quả tới muộn/unmount, không hiển thị số cũ dưới bộ lọc mới. Trạng thái loading/error/retry/rỗng; cùng theme/sidebar, responsive. Không cần migration hoặc seed database chính.

## Bố cục Superdesign và dữ liệu vận hành

Áp dụng phần nội dung thiết kế Dasboard.txt cho Admin, giữ header/sidebar hiện có. Sáu thẻ: thực thu và đơn kích hoạt trong kỳ, gói hiện tại, khách chờ PT, lịch hôm nay và yêu cầu AI hôm nay. Không hiển thị phần trăm tăng trưởng hoặc trạng thái máy chủ giả. Hai đường tiền nhận/thực thu dùng chung miền giá trị, gồm mốc âm và 0; bảng chi tiết vẫn đọc được bằng bàn phím.

API bổ sung khóa van_hanh, không đổi dữ liệu báo cáo cũ:

- can_xu_ly: giao_dich_doi_soat đếm giao dịch CAN_DOI_SOAT đã xác minh trên toàn hệ thống; khác số tiền chờ đối soát trong kỳ. khach_cho_pt đếm KH hoạt động có gói đang dùng còn lượt PT, chưa có phân công hiệu lực; không đếm khách hết hạn/hết lượt/chưa thanh toán/bị khóa. lich_qua_han đếm lịch quá hạn chưa đóng xử lý; giao_an_nhap đếm giáo án mẫu NHAP.
- lich_hom_nay: ngày Việt Nam, tổng và số lượng từng trạng thái, danh sách tối đa 8 lịch theo giờ bắt đầu rồi ID. Trả ID, giờ ISO UTC, tên KH/PT và trạng thái; không trả email/hồ sơ/chat. Lịch tính theo ngày bắt đầu ở Việt Nam, dùng khoảng UTC nửa mở. Trạng thái chờ hết hạn và đã xác nhận quá 24 giờ được suy ra khi đọc, không cần đợi worker.
- pt_co_hoc_vien: số PT có phân công hiệu lực; goi_sap_het_han: gói đang sử dụng hết hạn trong 7 ngày tới; tai_lieu_da_xuat_ban: tài liệu DA_XUAT_BAN có thời điểm xuất bản không nằm trong tương lai.
- ai: số yêu cầu/thành công/lỗi/đang xử lý, input_tokens/output_tokens hôm nay theo ngay_han_muc, biểu đồ bù 0 cho 7 ngày. Tỷ lệ thành công chỉ chia trên THANH_CONG + LOI, không có yêu cầu kết thúc thì hiển thị dấu —. Thống kê trạng thái đã ghi trong DB; không đọc nội dung hội thoại hoặc gọi provider.

Danh mục/tài khoản lấy từ API tổng quan hiện có. Gói xếp theo số đơn nhận tiền, giữ tên snapshot và cả dòng chỉ hoàn tiền (0 đơn nhận trong kỳ) trong chi tiết. PT phân trang 20 dòng; bảng cuộn ngang trong vùng riêng trên điện thoại. KH/PT vẫn dùng dashboard và quyền riêng của từng vai trò.
