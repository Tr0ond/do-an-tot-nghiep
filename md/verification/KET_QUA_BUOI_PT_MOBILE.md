# Ghi kết quả tập cùng PT trên mobile — 06/10/2026

Đã triển khai theo yêu cầu chủ dự án, dùng cùng API và quy tắc [C42](../features/KET_QUA_BUOI_PT.md). Không thay backend, database, xác thực hoặc luồng trừ lượt trong bước mobile.

## Thay đổi

- `Mobile/src/screens/Lich/KetQuaBuoiPt.js`: PT chọn bài catalog, thêm/bỏ hiệp, số lần/tạ/nghỉ, ghi chú/nhận xét, lưu nháp, chốt có xác nhận; KH chỉ đọc bản nháp/kết quả. Tên/media lịch sử giữ snapshot từ server, dùng thành phần ảnh và theme hiện có.
- `Mobile/src/screens/Lich/ChiTietLich.js` và `navigation/DieuHuong.js`: điểm vào/stack kết quả từ lịch hiện tại hoặc lịch sử, cho cả KH/PT.
- `Mobile/src/screens/TapLuyen/Catalog.js`: cho chọn và trả về màn kết quả; luồng soạn giáo án cũ vẫn là mặc định. Catalog có tìm kiếm, bộ lọc và phân trang, không tải toàn thư viện.
- `Mobile/src/services/ketQuaBuoiPtService.js`: GET KH/PT, PUT nháp, POST chốt qua HTTP bearer chung; không gửi ID người ghi hoặc snapshot tên/media.
- `Mobile/src/utils/ketQuaBuoiPt.js`, `tests/ketQuaBuoiPt.test.mjs`: validation C42, tạ null khác 0, dấu phẩy thập phân Android, giữ nguyên chuỗi micro giây, giữ payload khi retry và chống gửi trùng.
- Cập nhật DECISIONS, hợp đồng C42 và tài liệu mobile. Không dùng skill thiết kế; giao diện dùng font Be Vietnam Pro, theme/nút/thẻ/input của app hiện tại.

## Vòng đời và lỗi

Nháp cục bộ chỉ trong bộ nhớ. Cảnh báo rời màn hình khi chưa lưu; không tự thay form bẩn khi focus/foreground tải lại. Server quyết định quyền ghi/chốt và lý do khóa. 409 giữ form, khóa ghi và yêu cầu tải lại có xác nhận. Mất phản hồi/mạng/5xx giữ đúng payload hoặc phiên bản chốt, khóa sửa/rời màn hình, có nút thử lại; không tạo ý định mới. 401/403/404 bỏ dữ liệu nhạy cảm; kết quả đọc cũ không phục hồi dữ liệu sau thu hồi. Mỗi ID lịch có state riêng, navigation/session và HTTP client đã có xử lý logout/phản hồi cũ.

## Kiểm tra thực sự đã chạy

| Kiểm tra | Kết quả/môi trường |
| --- | --- |
| Toàn bộ mobile tests | 46/46 PASS, gồm 7 test mới về C42; Node trên Windows |
| Backend `KetQuaBuoiPtTest` | 8/8 PASS, 113 assertions, MariaDB; ownership/thu hồi/trạng thái/version/retry/snapshot/rollback/hai request đồng thời |
| Expo export Android và web | PASS, Expo SDK 57.0.26; bundle không phải APK |
| Format và diff | Kiểm tra các file mobile thay đổi bằng Prettier với single quote/no semicolon và `git diff --check` |
| Native | LDPlayer 9, Android 9/API 28, Expo Go 57.0.2, ảnh portrait 720×1280, density override 230 |

QA chạy bản sao source màn chức năng ở Expo experience riêng, API/database MariaDB riêng; hai tài khoản giả `pt@kqpt.example.test`, `kh@kqpt.example.test`. Không dùng dữ liệu thật hoặc tài khoản demo đang được chủ dự án kiểm tra. Font QA dùng cùng file font (đổi đường dẫn tương đối trong bản sao để tránh URL junction Windows của Metro). Namespace phiên tách biệt với app gốc.

Native đã thao tác và chụp ảnh:

1. PT mở lịch, vào kết quả trống, chọn Chống đẩy từ catalog, thêm hiệp 12 lần, tạ bỏ trống, nghỉ 60 giây, nhập nhận xét. Không điền kết quả thực tế mặc định.
2. Rời màn hình hiện cảnh báo, quay lại giữ form.
3. Dừng riêng API QA trước khi lưu: app hiện lỗi mạng, khóa sửa và nút **Thử lại lưu nháp**. Khởi động lại API, retry lưu đúng một record; lịch vẫn DA_XAC_NHAN, còn 8 lượt.
4. Sửa ghi chú trên mobile; một client bearer QA cập nhật nhận xét trên server. Mobile lưu bằng version cũ nhận 409, giữ ghi chú chưa lưu và khóa ghi. Tải lại có xác nhận bỏ thay đổi, đọc được nhận xét mới.
5. Chốt native có xác nhận, khóa sửa. DB: một record đã chốt, lịch vẫn DA_XAC_NHAN, còn 8 lượt.
6. Hoàn thành lịch qua nút hiện có ở chi tiết lịch: DB chuyển HOAN_THANH, còn 7 lượt. Kết quả vẫn một record và một lần chốt.
7. Nạp bản mã cuối, PT xem lại kết quả đã chốt/lịch hoàn thành. Đăng xuất riêng tài khoản QA, đăng nhập KH, mở Lịch tập → Lịch sử → lịch → kết quả: cùng bài/hiệp/nhận xét, không có nhập/sửa/lưu/chốt.

[Số liệu DB đã kiểm tra](../../docs/verification/ket-qua-pt-mobile/09-database.json). Sau QA đã dừng riêng API/Metro thử nghiệm, xóa đúng database QA vừa tạo, trả LDPlayer về app gốc `exp://192.168.1.15:8086` và xác nhận tài khoản/8 lượt đang mở ban đầu vẫn nguyên. Không thay cấu hình môi trường hoặc đăng xuất tài khoản gốc.

Ảnh: [PT nhập](../../docs/verification/ket-qua-pt-mobile/01-pt-nhap.png), [cảnh báo rời](../../docs/verification/ket-qua-pt-mobile/02-canh-bao-roi.png), [mất mạng](../../docs/verification/ket-qua-pt-mobile/03-mat-ket-noi.png), [409](../../docs/verification/ket-qua-pt-mobile/04-xung-dot-phien-ban.png), [xác nhận chốt](../../docs/verification/ket-qua-pt-mobile/05-xac-nhan-chot.png), [đã chốt](../../docs/verification/ket-qua-pt-mobile/06-pt-da-chot.png), [PT bản mã cuối](../../docs/verification/ket-qua-pt-mobile/07-pt-lich-hoan-thanh.png), [KH chỉ đọc](../../docs/verification/ket-qua-pt-mobile/08-kh-xem-ket-qua.png).

## Cách xem và giới hạn

Mở lại app đang chạy trong Expo Go để nhận mã mới. Tài khoản thật → Lịch tập → lịch (hoặc Lịch sử) → Kết quả tập cùng PT. PT ghi trong hạn server; KH xem kết quả do PT lưu. Dữ liệu demo web tạo trước đó cũng dùng được trên mobile khi cùng backend; các lịch đã chốt chỉ xem, lịch hết hạn không sửa.

Chưa nghiệm thu điện thoại vật lý, TalkBack hoặc phát hành APK/store. Không bổ sung offline sync, push hay biểu đồ PT trên tổng quan mobile trong task này. Không kết luận pixel-perfect với frame Figma mới vì màn kết quả PT không có frame mới được cung cấp. Kiểm tra mất mạng native ở bước trên xảy ra trước khi server ghi; mất phản hồi sau ghi được kiểm tra qua helper retry và backend, không giả định native đã thử mọi dạng mất phản hồi.
