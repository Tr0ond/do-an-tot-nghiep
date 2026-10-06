# CA bundle cho kết nối payOS

Tài liệu này mô tả thư mục `BE/resources/certs/` trong source dự án. Đường dẫn source/lệnh tính từ gốc dự án hoặc thư mục được ghi ở từng bước; tài liệu đã chuyển sang `md/backend/`.

`cacert.pem` là tập CA công khai do curl chuyển đổi từ Mozilla, tải qua HTTPS từ https://curl.se/ca/cacert.pem ngày 02/10/2026. Đây không phải khóa bí mật. Nguồn và giấy phép MPL 2.0: https://curl.se/docs/caextract.html.

Laravel HTTP Client dùng bundle này để xác minh TLS trên máy Windows chưa cấu hình CA cho PHP. Không dùng verify=false. Có thể đặt `PAYOS_CA_BUNDLE` bằng đường dẫn tuyệt đối tới bundle CA được quản lý khi triển khai. Cập nhật từ nguồn chính thức định kỳ và chạy kiểm tra kết nối sau cập nhật.
