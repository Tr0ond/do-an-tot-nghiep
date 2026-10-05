# Ánh xạ giao diện Figma Make vào Mobile

Nguồn chuẩn: `Redesign Tr0ond Fitness App.zip` do chủ dự án cung cấp ngày 05/10/2026, xuất từ Figma Make `GlIleXhkEGzqOjmdVnA6rP`. Bản tham chiếu React/Tailwind chạy riêng; không đưa store hoặc các nghiệp vụ mô phỏng vào app.

MCP đã gọi `get_design_context` đúng file, node `0:1`, nhưng bị giới hạn Starter. Chủ dự án đã chọn phương án dùng bản xuất. Thông số UI lấy trực tiếp từ `src/index.css`, `src/ui.tsx`, `src/App.tsx` và `src/screens/*.tsx` trong ZIP; không dùng skill định hướng thiết kế.

## Stack và thành phần

Mobile hiện tại: Expo SDK 57, React Native 0.86.3, JavaScript, React Navigation 7, Context/hooks, Be Vietnam Pro 400–800. Backend Laravel và web Vue giữ nguyên. Theme nằm tại `Mobile/src/theme/index.js`; component dùng chung tại `Mobile/src/components/`.

| Figma Make | Mobile hiện tại | Trách nhiệm giữ lại |
| --- | --- | --- |
| Btn / Card / Field / Screen / Badge / Avatar / Row | Nut / The / TruongNhap / ManHinh / Nhan / AnhDaiDien / HangMenu | Props, handlers, accessibility; đổi styling theo bản xuất |
| Sheet / Confirm | Modal của màn nghiệp vụ, HopThongBao | Callback, chống gửi trùng và thông báo thật |
| Logo, Lucide icons | Thành phần nhận diện và BieuTuong | Dùng đúng icon Lucide cùng phiên bản bản xuất |
| Bottom navigation | navigation/DieuHuong.js | Stack KH/PT, badge thật, trạng thái phiên |
| Light / Dark / System | theme/index.js, XemTruocContext.js | Giữ lựa chọn trong phiên mở app và chế độ theo hệ thống; chưa có lưu lựa chọn qua lần khởi động |

## Ánh xạ màn hình

| File / màn tham chiếu | Màn trong Mobile |
| --- | --- |
| account: Login, Register, Forgot, ForgotSent, Reset | Auth/DangNhap.js, DangKy.js, KhoiPhuc.js |
| account: Profile, EditProfile, ThemeScreen | CaNhan/HoSo.js, SuaHoSo.js; lựa chọn theme hiện có |
| account: Sessions | Chỉ trình bày dữ liệu phiên thật hiện có; không tạo danh sách thiết bị giả hoặc chức năng thu hồi thiết bị khác |
| client: Home, trainer: TrainerHome | TongQuanTaiKhoan.js; KhachHang/TongQuan.js và PT/TongQuan.js chỉ bản xem |
| client: Schedule, Book, BookingDetail | Lich/LichHen.js, DatLich.js, ChiTietLich.js |
| client: Notifications | TraoDoi/ThongBao.js |
| plans: PlanList, PlanDetail, PlanEdit | TapLuyen/GiaoAn.js, ChiTietGiaoAn.js, SoanGiaoAn.js |
| plans: Exercises, ExerciseDetail, ExercisePicker | TapLuyen/Catalog.js, ChiTietBaiTap.js; chọn bài trong soạn giáo án |
| training: SelfList, SelfEdit, SelfDetail, SelfRun, SelfResult | TapLuyen/LichTuTap.js, BuoiTuTap.js và modal tạo lịch |
| training: Metrics | TapLuyen/ChiSo.js, GhiChiSo.js |
| commerce: Packages, PackageDetail, Orders, OrderDetail | HoanThien/GoiTap.js, DonHang.js, ChiTietDon.js |
| ai: AiList, AiChat, AiPlanForm | HoanThien/TroLy.js, HoiThoaiAi.js; nháp mở ChiTietGiaoAn.js |
| trainer: Messages, Chat | TraoDoi/DanhSachHoiThoai.js, HoiThoai.js, components/AnhChat.js |
| trainer: TrainerSchedule, SlotManage, Clients, ClientDetail, Templates | Lich/LichHen.js, PT/KhungGio.js, HocVien.js, HoSoHocVien.js, TapLuyen/GiaoAnMau.js |

## Giá trị được trích từ bản xuất

- Font: Be Vietnam Pro, 400/500/600/700/800. Không thay font.
- Nút: cao 48px, chữ 15px/600, radius dạng pill; nút nhỏ 36px, chữ 13px.
- Card: radius 16px, border 1px, padding 16px.
- Input: cao 48px, radius 12px, padding ngang 16px, chữ 15px; nhãn 13px/600, cách input 6px.
- Header màn con: cao 56px, chữ 17px/700, nền surface, border dưới 1px.
- Bottom navigation: cao 72px; vùng icon 56×32px, icon 21px, nhãn 11px/600.
- Sheet: radius góc trên 24px, lề ngang 20px, nền che đen 50%, tối đa 88% chiều cao.
- Auth: hero padding ngang 24px, trên 48px, dưới 40px; tiêu đề 28px/800, line-height 1.25; khối form chồng lên hero 20px, radius góc trên 24px, padding trên 28px.
- Theme giữ các khóa tiếng Việt hiện có, cập nhật giá trị đúng CSS bản xuất; không đoán từ bản Figma cũ.

## Giữ nghiệp vụ

Không đưa `registry`, tài khoản demo hoặc thay đổi state giả trong Make vào tài khoản thật. Đăng ký không tự đăng nhập; reset theo thời hạn/validation backend; thanh toán chỉ kích hoạt qua backend; phiên chỉ logout thiết bị hiện tại; AI chỉ lưu nháp theo quyền server. Giữ toàn bộ services, UUID retry, `updated_at`, phân quyền, lifecycle, các luồng lỗi và bản xem hiện có.

## Kiểm chứng

Kế hoạch ban đầu: so sánh bản tham chiếu với app ở cùng viewport và trạng thái tương ứng, kiểm tra theme, auth, KH/PT và các luồng con; Android kiểm tra thêm safe area, bàn phím và SVG. Dữ liệu thật khác dữ liệu mô phỏng trong Make nên không thay dữ liệu ứng dụng để tạo ảnh giống mẫu.

Kết quả thực tế và giới hạn kiểm chứng được ghi tại [FIGMA_MOBILE_IMPLEMENTATION.md](../verification/FIGMA_MOBILE_IMPLEMENTATION.md). Chưa xác nhận pixel-perfect cho toàn bộ màn hình/trạng thái PT hoặc các thao tác ghi dữ liệu.
