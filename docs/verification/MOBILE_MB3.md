# MB3 — Tập luyện và số đo trên mobile

Ngày kiểm tra: **05/10/2026**. Phạm vi theo C31–C38/C41; nối app Expo SDK 57 vào API Laravel hiện có. Không thêm migration/endpoint, không đổi nghiệp vụ web, quyền phân công hoặc cách trừ lượt. Chưa tạo APK/IPA và chưa nghiệm thu điện thoại thật.

## Phần đã triển khai

| Module/file | Hành vi |
| --- | --- |
| [tapLuyenService.js](../../Mobile/src/services/tapLuyenService.js) | API chung cho catalog, mẫu, giáo án, lịch tự tập/nhật ký/nhận xét và chỉ số; dùng HTTP client/phiên MB1 |
| [screens/TapLuyen](../../Mobile/src/screens/TapLuyen) | Danh sách/tìm/lọc bài, chi tiết hướng dẫn; giáo án KH/PT, soạn nháp và sao chép mẫu đã duyệt; lịch tự tập, hiệp thực tế, nhận xét; ghi/sửa/lịch sử số đo và tiến độ |
| [components/TapLuyen.js](../../Mobile/src/components/TapLuyen.js) | Lỗi API, lựa chọn có nhãn, ảnh/GIF công khai đúng origin/path, khung ảnh dự phòng và ghi công nguồn |
| [useBanNhap.js](../../Mobile/src/hooks/useBanNhap.js), [utils/tapLuyen.js](../../Mobile/src/utils/tapLuyen.js) | Giữ form khi chọn bài/quay lại, hỏi trước bỏ thay đổi; kiểm tra số, whitelist payload, thứ tự mỗi ngày, UUID/nội dung retry |
| [DieuHuong.js](../../Mobile/src/navigation/DieuHuong.js) và màn Tổng quan/Cá nhân/Lịch/Hồ sơ học viên | Tab Giáo án KH và các lối mở chức năng thật theo role; bản xem UI1 vẫn tách biệt |
| [utils/http.js](../../Mobile/src/utils/http.js) | Chuẩn hóa lỗi kết nối Android thành tiếng Việt, giữ status/errors của response Backend |
| [NhatKyTapTest.php](../../BE/tests/Feature/NhatKyTapTest.php) | Thêm hành trình bearer KH/PT qua giáo án, tự tập, nhận xét, số đo và thu hồi phân công |

## Actor, dữ liệu và trạng thái

- **Catalog:** KH/PT tra `/bai-tap`, `/bo-loc`, `/{id}`; PT đọc `/pt/giao-an-mau`. Tìm/lọc/phân trang do BE. App không thêm video, RPE, đánh giá y tế hoặc trường giả từ thiết kế. Ảnh công khai không gắn bearer vào URL; GIF chỉ chạy sau thao tác, dừng khi rời màn và tắt khi thiết bị yêu cầu giảm chuyển động.
- **Giáo án KH:** tự tạo không cần gói qua `/khach-hang/ke-hoach`; bản nháp sửa được, chỉ áp dụng khi hợp lệ. Khách xác nhận đề xuất PT trong 24 giờ; mỗi khách có tối đa một bản đang áp dụng. Chuyển bản giữ giáo án/lịch sử cũ. Các nút sửa/xác nhận/áp dụng/lưu trữ/hủy/ẩn/hiện lấy từ `co_the_*` của BE.
- **Giáo án PT:** `/pt/hoc-vien/{khachId}/ke-hoach`, `/pt/ke-hoach/{id}` và `/gui`, `/huy`; phải đang phụ trách. Sao chép mẫu đã duyệt chỉ thay nội dung nháp, không tự gửi/áp dụng. PT không sửa giáo án KH và không xác nhận thay KH.
- **Tự tập:** KH/PT tạo lịch từ giáo án đang dùng qua endpoint `lich-tap` đúng role. KH bắt đầu → nhập hiệp thực tế → lưu nháp → hoàn thành hoặc hủy. Mỗi bài cần ít nhất một hiệp để hoàn thành; buổi hoàn thành chỉ đọc. Snapshot hướng dẫn và chỉ tiêu được giữ; không tự điền chỉ tiêu thành kết quả và không trừ buổi PT.
- **Nhận xét:** PT hiện phụ trách chỉ nhận xét phiên hoàn thành, nội dung/UUID giữ nguyên khi retry. KH đọc nhận xét; PT không ghi hiệp thay KH.
- **Số đo:** KH ghi/sửa một bản mỗi ngày qua `/khach-hang/chi-so-co-the`, PT chỉ đọc `/pt/hoc-vien/{khachId}/chi-so-co-the`. Cân nặng 10–500 kg, chiều cao 50–250 cm, ngày 1900 đến hôm nay; BE tính BMI từ chiều cao của bản ghi. Không thêm vòng eo/phần trăm mỡ. Biểu đồ chỉ đặt chấm tại ngày có dữ liệu, không nội suy ngày trống; lịch sử hiển thị giá trị/ngày cụ thể.

Transaction, khóa/unique, ownership, phân công và trạng thái được BE hiện có kiểm tra; app không cập nhật trực tiếp counter/DB. Contract nguồn: [Giáo án](../features/KE_HOACH_TAP.md), [Nhật ký](../features/NHAT_KY_TAP.md), [Chỉ số](../features/CHI_SO_CO_THE.md), [Bài tập](../features/BAI_TAP.md), [Mẫu](../features/GIAO_AN_MAU.md).

## Validation và failure cases

- Input số không chấp nhận trống bắt buộc, âm, vô hạn, ký pháp số mũ hoặc số hiệp phân số. Hỗ trợ dấu phẩy thập phân; tạ trống gửi `null`, tạ `0` giữ đúng `0`. Không tự điền thời gian nghỉ.
- POST tạo giáo án/lịch/nhận xét giữ UUID và nội dung khi mất phản hồi. Form khóa sửa trong lúc gửi/chưa rõ kết quả và cho thử lại cùng nội dung. PUT giữ payload và chuỗi `updated_at` đầy đủ; version cũ báo 409, giữ form rồi tải lại theo xác nhận của người dùng.
- 422 giữ form, hiện lỗi và cho sửa; 403/404 xử lý theo tài nguyên, không tự logout. 401 xử lý phiên chung. Loading/empty/error/retry/phân trang có trên các danh sách. Focus/foreground tải lại qua hook MB2; request/response cũ bị bỏ khi đổi phiên.
- Chưa có realtime cho tập luyện; quay lại màn/kéo làm mới hoặc nút tải lại để đọc thay đổi. Form chưa lưu hỏi trước rời màn; không có autosave qua việc đóng process.

## Kiểm tra thực sự đã chạy

Môi trường: Windows, Node 22.20.0; Laravel/PHP của dự án, **MariaDB 10.4.32**; LDPlayer 9 Android 9, Expo Go 57.0.2. Source đang ở working tree, chưa tạo commit. Fixture native dùng database ngẫu nhiên riêng, tài khoản/dữ liệu tổng hợp; không seed/reset dữ liệu chính, không gọi thanh toán hoặc AI. Fixture đã được đối chiếu và dọn sau kiểm tra.

| Kiểm tra | Kết quả |
| --- | --- |
| `rtk proxy npm test` trong Mobile | **19/19 PASS**: phiên/vòng đời MB1–MB2 và payload snapshot, null/0, validation số, thứ tự/whitelist và retry MB3 |
| `rtk proxy php artisan test --filter="KeHoachTapTest\|NhatKyTapTest\|ChiSoCoTheTest\|BaiTapTest"` trong BE | **79 PASS, 3.901 assertions** trên MariaDB; gồm quyền, version, snapshot, tranh chấp/rollback và bearer KH/PT mới |
| Hành trình bearer mới chạy riêng | **1 PASS, 33 assertions**: tạo/bắt đầu/lưu/hoàn thành/retry, nhận xét retry, BMI, PT chỉ đọc, gửi/xác nhận giáo án, thu hồi phân công mà KH giữ lịch sử |
| Expo dependencies/doctor | Dependencies đúng phiên bản, **21/21 kiểm tra PASS** |
| Expo export Android/iOS/web | Đã bundle thành công; export không phải build/bằng chứng chạy iOS |
| Pint file test, format JS, Markdown/link/UTF-8 | Đã kiểm tra theo phần thay đổi |

Hành trình trực tiếp trên LDPlayer:

1. KH tạo nháp, chọn bài từ catalog (không mất tên đã nhập), điền chỉ tiêu rồi lưu/áp dụng.
2. KH lên lịch ngày hiện tại, bắt đầu với hiệp thực tế trống, ghi một hiệp 12 lần/60 giây/tạ chưa biết, lưu rồi hoàn thành. Android back khi form có thay đổi hỏi xác nhận; chọn quay lại giữ form.
3. KH ghi 70 kg/175 cm: BE trả BMI 22,86; sửa ghi chú, lịch sử và biểu đồ cập nhật.
4. PT xem nhật ký chỉ đọc, ghi nhận xét thành công; xem số đo KH nhưng không có thao tác ghi/sửa.
5. PT sao chép mẫu đã duyệt, lưu nháp và gửi đề xuất. KH đăng nhập lại, xem bản chờ và xác nhận: bản PT chuyển `DANG_AP_DUNG`, bản KH cũ `LUU_TRU`; lịch hoàn thành/nhận xét vẫn còn, quota PT vẫn **8/8**.
6. Kiểm tra thư viện/bộ lọc và số đo ở chiều rộng khoảng 375 dp, cỡ chữ 130%, sáng/tối: nội dung cuộn được, nút/bộ lọc xuống dòng và biểu đồ có khung ổn định. App hiện khóa hướng dọc theo cấu hình Expo; chưa nghiệm thu chế độ ngang hoặc TalkBack.
7. Ngắt server QA lúc sửa số đo: app báo lỗi, giữ nguyên form và khóa sửa nội dung đang chờ; bật lại server và bấm lưu lại thành công, lịch sử vẫn có bốn bản ghi. Đây là kiểm tra mất kết nối local, chưa thay mạng di động yếu/mất phản hồi sau khi server đã commit.

Ảnh dùng dữ liệu QA tổng hợp:

- [Nhật ký hoàn thành](screenshots/mobile-mb3-buoi-hoan-thanh.png)
- [Số đo và tiến độ](screenshots/mobile-mb3-chi-so.png)
- [PT nhận xét](screenshots/mobile-mb3-pt-nhan-xet.png)
- [PT gửi đề xuất](screenshots/mobile-mb3-pt-gui.png)
- [KH xác nhận và áp dụng](screenshots/mobile-mb3-kh-xac-nhan.png)
- [Bộ lọc ở 375 dp, chữ 130%](screenshots/mobile-mb3-375-chu-lon.png)
- [Số đo ở 375 dp, chế độ tối](screenshots/mobile-mb3-375-toi.png)
- [Lỗi mạng có hướng dẫn thử lại](screenshots/mobile-mb3-loi-mang.png)

## Cách xem và phần còn lại

Theo [hướng dẫn chạy Mobile](../../Mobile/README.md); dùng tài khoản KH/PT của dữ liệu chính, không dùng tài khoản fixture đã dọn. KH: **Giáo án**, **Lịch tập → Lịch tự tập**, **Cá nhân → Chỉ số cơ thể**. PT: **Học viên → Hồ sơ → Giáo án/Tự tập/Số đo**. Chat/thông báo native là bước MB4 tiếp theo.

Chưa nghiệm thu điện thoại thật/iOS/bản cài, mạng di động yếu và đóng process giữa lần ghi; chưa kiểm chứng đủ TalkBack/reduce-motion/GIF thật, danh sách giáo án rất lớn và mọi tổ hợp UI/permission trên native. Những phần BE có test không tự thay nghiệm thu UI. Không có push/cloud build/store hoặc thay đổi chính sách đã chốt.
