# Kế hoạch kiểm thử và nghiệm thu

**Trạng thái ngày 04/10/2026:** ứng dụng đã chạy; toàn Backend đạt 267 tests/6.641 assertions trên MariaDB 10.4.32, Frontend đạt 253 tests. Đã bổ sung 8 ca tích hợp nối hành trình KH–PT–Admin và [demo có thể chạy lại](DEMO_SCRIPT.md); [bằng chứng/giới hạn](verification/HANH_TRINH_NGHIEP_VU.md). Các mục dưới đây vẫn là tiêu chí nghiệm thu: không suy ra mọi mục PASS từ tổng số test; AI thật, thanh toán thật và môi trường triển khai cần kiểm chứng riêng.

## Những nhóm bắt buộc

| ID | Tình huống | Kết quả cần chứng minh |
| --- | --- | --- |
| QA01 | KH tự đăng ký kèm role Admin/PT | Bị từ chối/role bị server cố định theo contract |
| QA02 | KH A đọc/sửa hồ sơ/gói/lịch/phiên của KH B | Không có quyền, không lộ dữ liệu |
| QA03 | PT không được phân công đọc học viên | Không có quyền |
| QA04 | Khóa tài khoản khi session/socket đang mở | Hành động tiếp theo và events nhạy cảm bị chặn |
| QA05 | Xác nhận thanh toán lặp hoặc cùng key khác payload | Một khoản thu/cấp gói; payload khác bị conflict |
| QA06 | Sửa catalog sau khi KH đã mua | Snapshot gói đã mua giữ nguyên |
| QA07 | Hai KH tranh cùng slot PT | Tối đa một lịch giữ slot thành công |
| QA08 | Cùng KH đặt hai lịch chồng nhau đồng thời | Không tạo hai lịch chồng nhau |
| QA09 | Hủy lịch đúng/ngoài điều kiện D04 | Chuyển trạng thái và giải phóng slot đúng; giữ record |
| QA10 | Đổi PT với lịch/plan chờ và buổi đã diễn ra chưa xử lý | Chặn đổi khi buổi đang diễn ra/chưa xử lý; sau đổi hủy lịch tương lai/vô hiệu đề xuất cũ, giữ kế hoạch duyệt/lịch sử và thu hồi quyền PT cũ |
| QA11 | Hai lần hoàn thành một buổi PT | Trừ đúng một lượt, một record hoàn thành |
| QA12 | Hai buổi cùng dùng lượt cuối | Không âm lượt; tối đa một hoàn thành thành công |
| QA13 | Xác nhận thanh toán và thử dùng gói trước/sau xác nhận | Kích hoạt ngay khi thanh toán được xác nhận theo C10; retry không đặt lại mốc, gói kết hợp dùng chung thời hạn |
| QA14 | Gói hết hạn/zero quota/xác nhận muộn | Buổi kết thúc trong hạn được xác nhận trong 24 giờ sau kết thúc dù gói vừa hết hạn; không mượn gói mới, không âm lượt |
| QA15 | KH tự gọi API hoàn thành PT | Bị từ chối, lượt không đổi |
| QA16 | KH đồng thời xác nhận hai đề xuất kế hoạch | Tối đa một plan active, đề xuất stale không ghi đè |
| QA17 | Đề xuất hết hạn/PT nguồn hết phân công | Không apply theo D03/D06 |
| QA18 | Retry lưu hiệp/hoàn thành phiên | Không tạo set/phiên trùng |
| QA19 | Sửa/xóa phiên đã hoàn thành | Bị từ chối; PT note riêng không thay kết quả |
| QA20 | Gói hết hạn khi KH xem/ghi nhật ký | Vẫn xem kế hoạch/lịch sử và ghi nhật ký từ lịch hợp lệ đã có; không trừ buổi PT, không cấp chatbot/PT mới |
| QA21 | Chat trái quyền hoặc subscribe channel người khác | HTTP và WebSocket authorization bị chặn |
| QA22 | Gửi tin lặp/HTTP response và event đảo thứ tự | Một message DB và UI |
| QA23 | Offline, reconnect, nhiều tab | Tải bù đủ tin theo cursor, không duplicate |
| QA24 | Đổi PT khi socket cũ vẫn mở/job đang chờ | PT cũ không nhận nội dung mới |
| QA25 | Gửi read cursor ngoài hội thoại hoặc lùi cursor | Từ chối/không lùi, unread đúng |
| QA26 | AI trả JSON sai/ID ngoài candidate/HTML/action lạ | Validation/fallback, không áp dụng dữ liệu tùy ý |
| QA27 | AI hỏi gói ngừng bán/giá thay đổi/chính sách không có | Dùng nguồn hiện tại hoặc báo thiếu, không dựng dữ liệu |
| QA28 | AI bị yêu cầu đọc KH khác/chat PT/sửa role | Không có quyền, không ghi thay đổi nghiệp vụ |
| QA29 | Provider timeout/429/lỗi key | Lỗi/fallback rõ, retry hữu hạn, không lộ key |
| QA30 | Đăng ký chờ thanh toán trong báo cáo | Không tính vào tiền đã nhận |
| QA31 | Responsive, loading/empty/validation | UI dùng được trên điện thoại và desktop |
| QA32 | Backup rồi restore môi trường test | Quan hệ/lịch sử còn nhất quán, không mất dữ liệu |
| QA33 | Admin tạo gói chatbot riêng và gói PT theo buổi kèm chatbot | Quyền lợi riêng được lưu/hiển thị đúng; gói chatbot không cấp buổi PT/phân công PT |
| QA34 | Khách gọi chatbot khi thiếu quyền/hết hiệu lực/hết hạn mức ngày | Không gọi provider; catalog/FAQ vẫn miễn phí, không suy ra quyền từ FE/tên gói |
| QA35 | Admin đổi quyền lợi/hạn mức catalog sau khi khách đã mua | Snapshot gói đã mua giữ nguyên, tương tự giá/số buổi |
| QA36 | Hạn mức chatbot ngày: request đồng thời, provider lỗi và retry | Chỉ câu trả lời hợp lệ tính một lượt; lỗi không mất lượt/retry không tính lặp, không vượt hạn mức; lượt PT không đổi |
| QA37 | Hai thanh toán đồng thời có thể cấp hai gói khả dụng cho cùng KH | Tối đa một gói khả dụng theo C11; xử lý khoản thu/xung đột theo chính sách D01 được chốt trước implementation |
| QA38 | Webhook payOS giả/sai chữ ký/mã đơn/link/số tiền, return URL giả thành công | Không tự cấp gói; kết quả chỉ từ dữ liệu Backend xác minh, ngoại lệ ghi nhận đối soát |
| QA39 | Webhook lặp/tới muộn, đơn 15 phút hết hạn, giá catalog đổi | Một lần cấp gói; giữ giá trong hạn, tiền trong hạn dù thông báo muộn xử lý bình thường, tiền ngoài hạn đối soát |
| QA40 | Hết buổi PT còn hạn; hạn mức ngày tại 00:00 giờ Việt Nam | Chatbot còn quyền đến hạn gói; ngày mới cấp lại hạn mức, retry không tính lặp |
| QA41 | Biên đặt 4 giờ/hủy 2 giờ/deadline chờ, worker chưa dọn | Backend áp dụng mốc D04; hết hạn chờ giải phóng slot, vắng mặt không trừ buổi/không phạt |
| QA42 | Kế hoạch quá 24 giờ/đổi PT trước duyệt, lịch tự tập tạo trước duyệt | Từ chối đề xuất hết hạn/stale và lịch chưa được duyệt; giữ kế hoạch cũ/kết quả |
| QA43 | Buổi quá 24 giờ chưa xác nhận, Admin đóng xử lý rồi đổi PT | Ghi quá hạn/đóng có lý do/audit, không trừ buổi/không biến thành hoàn thành; sau đóng được đổi PT |
| QA44 | Gemini hết quota miễn phí hoặc chưa có key, API trả lỗi | Báo bận/fallback rõ không mất lượt gói, không tự chuyển sang API có phí |

Các cases có D chưa chốt phải chốt expected behavior trước khi viết assertion. Không đoán luật để làm test pass.

## Loại test

- PHPUnit feature/API: auth, validation, ownership, state transition và database effects.
- Unit: phép tính deadline/snapshot và validator/context AI có ý nghĩa.
- MySQL integration: transactions, constraints, khóa và concurrent requests. SQLite không thay thế bằng chứng này.
- Vitest/Vue Test Utils: xử lý lỗi/optimistic merge/component phức tạp; không viết test chỉ lặp lại implementation.
- Playwright: luồng mua/phân công/đặt/tập/chat; Reverb thật với ít nhất hai user sessions.
- AI fake tests: deterministic schema/ID/fallback và quyền.
- AI live eval: bộ câu hỏi/rubric được chuẩn bị, provider/model/prompt/dataset được ghi lại. Không gọi API trả phí tự động trong suite thường.

## Definition of Done từng module

1. Decision ảnh hưởng đã chốt; scope/request/response/quyền rõ.
2. Happy path và failure cases implement đầy đủ, không chỉ giao diện.
3. Validation/resource authorization và transaction/idempotency phù hợp.
4. Các test liên quan thực sự chạy; có log/môi trường/commit khi có Git.
5. Tài liệu schema/API/runtime cập nhật; không còn secret trong code.
6. Demo có dữ liệu giả và thao tác có thể lặp lại.

Chỉ thêm broaden/re-run khi có thay đổi hoặc failure/unresolved concern. Với sửa tài liệu thuần túy, kiểm tra link/encoding/nhất quán là đủ.

## Bằng chứng

Khi có kết quả thực tế, bổ sung bảng tổng hợp vào tài liệu này và liên kết log/báo cáo tại vị trí chủ dự án chọn. Mỗi lần chạy ghi module, ngày, môi trường, cách chạy, kết quả và giới hạn. Hiện chưa có log kiểm thử ứng dụng; không tạo lại thư mục hồ sơ đã bỏ chỉ để khớp tài liệu cũ.

Không ghi đầy đủ token/cookie/keys. Với AI ghi chất lượng theo rubric thay vì số PASS API; với realtime ghi môi trường Reverb/browser và thao tác reconnect/revoke.

## Đo tải sau khi có ứng dụng

Nếu cần, thử tăng dần client/requests theo môi trường thực; báo p50/p95, lỗi, CPU/RAM và dataset. Số lượng seed trong SCOPE không phải thông lượng hay cam kết production.
