# Bộ mẫu Stitch đã duyệt

Lưu ngày 04/10/2026 từ [dự án Stitch 10304506651660798975](https://stitch.withgoogle.com/projects/10304506651660798975), theo yêu cầu lưu và áp dụng của chủ dự án.

- [Xem 32 mẫu gốc](index.html).
- [Thông tin màn hình và nguồn xuất](manifest.json).
- [64 file HTML/PNG cùng dung lượng và SHA-256](files.json).
- [Đối chiếu phần triển khai](APPLIED.md).
- [Ảnh ứng dụng Vue thực tế](../fitness-motion-app/index.html).

Các file HTML và PNG được xuất nguyên bản, không thay màu hoặc sửa nội dung mẫu. Script `scripts/save-stitch-approved.cjs` kiểm tra định dạng tải về, không ghi đè file đã có và ghi SHA-256 để kiểm tra bản lưu. Không cần Stitch đang mở để xem các PNG đã lưu. HTML gốc có thể cần mạng cho font, Tailwind CDN và ảnh ngoài.

Đây là bản thiết kế thị giác, không phải dữ liệu khách hàng hay đặc tả nghiệp vụ. Số liệu, tên, nhận xét, thông tin bảo mật và tính năng minh họa trong Stitch không được coi là nội dung đã được hệ thống hỗ trợ. Ứng dụng vẫn dùng Vue Options API, service và quyền Backend hiện có.

Một số mẫu mở rộng chỉ có bản sáng; ứng dụng triển khai cả sáng/tối bằng token dùng chung. Bộ mẫu tổng quan có riêng sáu bản desktop và hai bản điện thoại cho sáng/tối.
