# Ảnh đối chiếu Figma Make → Mobile

Nguồn là bản ZIP do chủ dự án gửi, không phải các frame của bản thiết kế cũ. Kết quả và giới hạn: [FIGMA_MOBILE_IMPLEMENTATION.md](../FIGMA_MOBILE_IMPLEMENTATION.md).

Bản tham chiếu và app web chụp ở 390 × 844. Ảnh Android đối chiếu có kích thước 390 × 868 gồm 24px thanh trạng thái phía trên; vùng app là 390 × 844. Không so sánh thanh hệ điều hành hoặc nút Expo Go Tools nổi bên phải.

## Các cặp ảnh dùng để đối chiếu

| Màn | Bản xuất Make | Mobile | Lưu ý |
| --- | --- | --- | --- |
| Đăng nhập | [Nguồn](reference-login.png) | [App web](mobile-login-v2.png) | Hai input đã đo x/y/width/height: sai lệch 0; [JSON](auth-geometry.json) |
| Đăng ký | [Nguồn](reference-register.png) | [App web](mobile-register.png) | Validation mật khẩu của API thật được giữ |
| Quên mật khẩu | [Nguồn](reference-forgot.png) | [App web](mobile-forgot.png) | Không gửi email thật khi kiểm tra |
| Tổng quan KH | [Nguồn](reference-home.png) | [Android](android-home-final.png) | Dữ liệu thật khác fixture; ảnh sau cold restart |
| Giáo án | [Nguồn](reference-plans.png) | [Android](android-plans-final.png) | Trạng thái và nhãn do API thật quyết định |
| Lịch tập | [Nguồn](reference-schedule.png) | [Android](android-schedule-final.png) | Ngày hôm nay khác; không tạo chấm lịch cho ngày chưa được API trả dữ liệu |
| Tin nhắn | [Nguồn](reference-messages.png) | [Android](android-messages-final.png) | Dùng hội thoại, unread và nội dung thật |
| Hồ sơ | [Nguồn](reference-profile.png) | [Android](android-profile-final.png) | Giữ menu bổ sung hiện có ở dưới phần mẫu |
| Theme sáng | [Nguồn](reference-theme.png) | [Android](android-theme-final.png) | Ảnh trước sửa màu thanh trạng thái; chỉ dùng đối chiếu vùng app |
| Theme tối | [Nguồn](reference-theme-dark.png) | [Android](android-theme-dark.png) | Chưa lưu lựa chọn theme qua khởi động |
| Chỉ số | [Nguồn](reference-metrics.png) | [Android](android-metrics-final.png) | Scatter đo thật khác đường nội suy mẫu; cân nặng/chiều cao thật |
| Bảng nhập chỉ số | [Nguồn](reference-metric-sheet.png) | [Android](android-metric-sheet-final.png) | Kiểm tra vị trí sheet/input/nút lưu sau trừ 24px OS; chưa ghi dữ liệu |
| Kết quả buổi tập | [Nguồn](reference-self-result.png) | [Android](android-self-result-final.png) | Đã sửa padding và opacity SVG; số hiệp/tạ/thời gian/nhận xét là dữ liệu thật |
| Gói tập | [Nguồn](reference-packages.png) | [Android](android-packages-final.png) | Số lượt, giá và hạn dùng thật; không thêm “phổ biến” từ fixture |
| Đơn hàng | [Nguồn](reference-orders.png) | [Android](android-orders-final.png) | Mã đơn thật dài hơn; không cắt mã để giống mẫu |

## Các màn Android đã mở thêm

| Màn | Ảnh | Giới hạn |
| --- | --- | --- |
| Tự tập chưa có buổi sắp tới | [Ảnh](android-self-list-final.png) | Empty state và liên kết tiến độ thật |
| Danh sách buổi đã tập | [Ảnh](android-self-done-final.png) | Đọc dữ liệu thật |
| Chi tiết giáo án | [Ảnh](android-plan-detail-final.png) | Ảnh trước lần sửa opacity SVG cuối; không dùng chứng minh asset cuối |
| Soạn giáo án | [Ảnh](android-plan-editor.png) | Chỉ mở, không lưu thay đổi |
| Catalog | [Ảnh](android-catalog-final.png) | Ảnh trước lần sửa opacity SVG cuối; giữ tên/media gốc |
| Chi tiết bài tập | [Ảnh](android-exercise-detail.png) | Giữ hướng dẫn và attribution gốc |
| Hướng dẫn trong bảng | [Ảnh](android-exercise-sheet.png) | Không ghi dữ liệu |
| Chat | [Ảnh](android-chat-final.png) | Không gửi tin/ảnh mới |
| Thông báo | [Ảnh](android-notifications-final.png) | Không bấm đánh dấu tất cả đã đọc |
| Sửa hồ sơ | [Ảnh](android-edit-profile-dark.png) | Mở trong theme tối; không lưu |
| Phiên đăng nhập | [Ảnh](android-sessions.png) | Chỉ phiên hiện tại, không tạo danh sách thiết bị giả |
| Trợ lý AI | [Ảnh](android-ai-list.png) | Không tạo hội thoại/gửi yêu cầu có phí |
| Hội thoại AI | [Ảnh](android-ai-chat.png) | Đọc hội thoại hiện có |
| Thông tin gói trên màn đơn | [Ảnh](android-order-package-sheet.png) | Giữ thông tin bổ sung trong bảng |
| LDPlayer sau trả cấu hình | [Ảnh](android-restored-device.png) | Chỉ chứng minh app mở lại ở kích thước thiết bị ban đầu, không phải viewport chuẩn |

Các file không có trong bảng có thể là ảnh trung gian/debug của vòng kiểm tra trước. Đặc biệt `android-login.png` và `android-expo-error.png` không phải ảnh đăng nhập/ứng dụng sau hoàn tất sửa lỗi.

Không có ảnh đối chiếu đầy đủ cho tất cả nhánh PT, thao tác ghi và trạng thái lỗi. Các ảnh có nội dung thật khác mẫu không được coi là bằng chứng raster giống nhau hoàn toàn.

Danh sách source thay đổi: [files-modified.json](files-modified.json).
