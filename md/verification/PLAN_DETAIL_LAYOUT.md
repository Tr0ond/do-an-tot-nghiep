# Bố cục chi tiết giáo án cá nhân — 2026-10-03

## Thay đổi

- `FE/src/views/KeHoachTap/ChiTiet/index.vue`: KH/PT dùng bố cục tương ứng trang chi tiết giáo án mẫu: tóm tắt, số ngày/bài/hiệp, thanh chọn ngày và thẻ bài tập gọn. Ngày chưa có bài được hiển thị rõ.
- `FE/src/components/HuongDanBaiTapGiaoAn.vue`: cửa sổ hướng dẫn từ snapshot giáo án, ảnh tĩnh mặc định và nút bật/dừng GIF; giữ thông số tập, mức tạ bằng 0 và ghi chú. Không thay hướng dẫn đã lưu bằng dữ liệu catalog hiện tại; thiếu hướng dẫn có thông báo riêng.
- `FE/src/assets/chiTietKeHoach.css`: màu theo theme, bố cục responsive, ảnh nhỏ và focus bàn phím. Nút nghiệp vụ theo quyền API và các xác nhận trong trang được giữ.
- `FE/tests/keHoachTap.spec.js`: cập nhật kiểm tra URL ảnh/GIF đúng origin Backend và hướng dẫn thiếu.

## Kiểm tra thực tế

- Frontend: 182 tests / 18 files PASS; lint, build, format:check và git diff --check PASS.
- Trình duyệt dùng tài khoản KH/PT và database fixture riêng trên MariaDB; FE localhost:5291 / BE localhost:8017. Dữ liệu thử có 2 ngày, 3 bài, 9 hiệp.
- PT desktop 1440×1000: đổi ngày 1 → 2 → 1, đúng bài và tổng hiệp; ảnh JPG và GIF tải thành công (naturalWidth > 0).
- Hướng dẫn mở bằng nút, đóng bằng Esc và trả focus về nút mở; đóng bằng nút trên điện thoại. GIF chỉ xuất hiện khi bật xem chuyển động.
- Điện thoại 390×844 và tablet 768×1024: không tràn ngang; tablet dark và desktop/mobile light được kiểm tra bằng ảnh.
- KH: nút Lên lịch tự tập/Ngừng áp dụng hiển thị; mở xác nhận ngừng áp dụng rồi chọn Để sau; cửa sổ hướng dẫn hoạt động. PT không có nút ngừng áp dụng của KH. Không thực hiện chuyển trạng thái giáo án trong lần QA này.
- Không ghi nhận console error trong luồng trên. Không thay Backend/schema, không chạy lại bộ test Backend cho thay đổi giao diện này. Fixture và các máy chủ thử được dọn sau QA.

## Bằng chứng

- [PT desktop](../../docs/verification/plan-detail-layout-desktop.png)
- [KH desktop](../../docs/verification/plan-detail-layout-customer.png)
- [Hướng dẫn và GIF](../../docs/verification/plan-detail-layout-guide.png)
- [Điện thoại](../../docs/verification/plan-detail-layout-mobile.png)
- [Tablet dark](../../docs/verification/plan-detail-layout-dark-tablet.png)

## Xem kết quả

Tải lại Frontend, mở một giáo án tại `/pt/ke-hoach/:id` hoặc `/khach-hang/ke-hoach/:id`. Không cần chạy migration cho thay đổi bố cục. Quyền và trạng thái nghiệp vụ vẫn do Backend quyết định.
