# Dữ liệu bài tập

Tài liệu này mô tả thư mục `BE/database/data/` trong source dự án. Đường dẫn source/lệnh tính từ gốc dự án hoặc thư mục được ghi ở từng bước; tài liệu đã chuyển sang `md/backend/`.

Đã chuẩn bị từ [`hasaneyldrm/exercises-dataset`](https://github.com/hasaneyldrm/exercises-dataset): **1.324 bài tập, 19 nhóm cơ chính, 28 nhãn dụng cụ, 1.324 ảnh và 1.324 GIF**. Repository nguồn được giữ riêng, không nằm trong repository dự án; catalog đã chuẩn hóa, giấy phép/ghi công và media cần dùng vẫn được lưu tại Backend. Đây là catalog đầy đủ; mục tiêu 30–50 bài demo trong SCOPE không giới hạn số bài có thể nhập.

## Tệp và tài nguyên

- `bai_tap.json`: bản ghi chuẩn hóa, giữ tên gốc, hướng dẫn và các bước của cả 10 ngôn ngữ.
- `nhom_co.json`: nhóm cơ chính với nhãn tiếng Việt và tên nguồn.
- `bai_tap.seed.sql`: nhập catalog vào database mới, sau `../design/schema.mysql.sql`.
- `media-manifest.json`: đường dẫn nguồn/đích, dung lượng và SHA-256 từng tài nguyên.
- `thong_ke.json`: số lượng, ngôn ngữ và SHA-256 tệp JSON nguồn.
- Ảnh tại `BE/public/media/bai-tap/images/`; GIF tại `BE/public/media/bai-tap/animations/`, tính từ thư mục gốc dự án.

`anh_url` và `gif_url` là đường dẫn theo origin Backend, ví dụ `/media/bai-tap/images/0001-2gPfomN.jpg`. Frontend độc lập ghép với origin Backend, không ghép vào origin của Vite. Laravel local đã phục vụ media và [giao diện danh mục](http://localhost:5173/bai-tap) đã dùng các đường dẫn này.

## Ánh xạ và chất lượng

| Trường nguồn | Trường dự án | Quy tắc |
| --- | --- | --- |
| `id` | `ma_nguon` | Chuỗi 4 ký tự, giữ `0001`; khác PK `id` nội bộ |
| `target` | `nhom_co_id` | Nhóm cơ chính; `cardiovascular system` là nhóm tim mạch, không phải cơ giải phẫu |
| `name` | `ten_bai_tap` | Giữ tên gốc; `ten_tieng_viet` để NULL khi chưa được biên tập |
| `body_part` | `bo_phan_co_the` | Giữ nhãn nguồn; `category` là bản lặp nên không tạo cột riêng |
| `equipment` | `dung_cu_nguon`, `dung_cu` | Giữ nhãn nguồn, bổ sung nhãn hiển thị tiếng Việt |
| `instructions` | `huong_dan` | JSON theo ngôn ngữ; chưa có `vi`, dùng `en` làm fallback |
| `instruction_steps` | `cac_buoc` | JSON mảng bước theo ngôn ngữ |
| `muscle_group` | `co_ho_tro_nguon` | Cơ hỗ trợ trong nguồn, không dùng làm nhóm cơ chính |
| `secondary_muscles` | `co_phu` | JSON, không mở rộng quan hệ nhiều nhóm cơ ở bản đầu |
| `image`, `gif_url` | URL public và đường dẫn nguồn riêng | Sao chép nguyên byte, không đổi kích thước |
| `created_at` | `nguon_tao_luc` | Thời điểm nguồn; khác thời điểm nhập catalog |
| Không có `updated_at` | `nguon_cap_nhat_luc` | NULL, không tự tạo ngày cập nhật nguồn |

Nguồn không có độ khó, chống chỉ định, số hiệp/lần lặp hay thời gian nghỉ. Không tự suy đoán những dữ liệu này; PT đặt chỉ tiêu trong giáo án/kế hoạch. Nội dung nguồn là dữ liệu tham khảo, chưa được xác nhận chuyên môn hoặc dịch toàn bộ sang tiếng Việt.

## Tạo lại và kiểm tra

Từ thư mục gốc, với Node.js sẵn có:

```powershell
git clone https://github.com/hasaneyldrm/exercises-dataset.git exercises-dataset
rtk proxy node scripts/chuanBiDuLieu.mjs
rtk proxy node scripts/kiemTraDuLieu.mjs
```

Lệnh chuẩn bị cần dataset nguồn tại `exercises-dataset/` như lệnh clone phía trên; lệnh kiểm tra đối chiếu SHA-256 với JSON nguồn. Lệnh tạo lại sinh JSON/SQL/từ điển dữ liệu và `docs/diagrams/database.generated.drawio` (bản chi tiết từng bảng). Bản **một canvas** theo mẫu chủ dự án được sinh riêng bằng `rtk proxy node scripts/veDatabaseTongThe.mjs` vào `docs/diagrams/database-single.generated.drawio`. Các lệnh không chạy MySQL, không thay bản vẽ `database.drawio` đã xuất từ draw MCP và không sửa dataset nguồn. Media đích đã có nhưng khác nội dung sẽ bị chặn để không ghi đè chỉnh sửa.

SQL seed dùng `INSERT`, **không UPSERT**, dành cho hai bảng catalog trống. Chạy lại sẽ bị khóa unique chặn, không ghi đè nội dung Admin đã sửa. Nhập bằng chế độ batch dừng khi có lỗi, không dùng `--force`; nếu công cụ nhập vẫn giữ kết nối sau lỗi, thực hiện `ROLLBACK`, không tiếp tục đến `COMMIT`.

Laravel dùng `rtk proxy php artisan db:seed --class=BaiTapSeeder` từ BE sau migrations, thay cho nhập SQL seed trực tiếp. Seeder ghép mã nhóm/mã nguồn, không dùng PK cố định, chạy lại chỉ thêm dữ liệu thiếu và giữ bản biên tập. Đã nhập/đối chiếu đủ catalog trên MariaDB 10.4.32; [bằng chứng](../verification/M02_BAI_TAP.md). Chưa kiểm thử MySQL thật. Từ gốc chạy `rtk proxy php scripts/kiemTraCatalogDatabase.php` để đối chiếu DB với JSON; script chỉ đọc và báo khác nguồn khi có biên tập.

## Nguồn và quyền sử dụng

Giữ nguyên [LICENSE](../../BE/database/data/LICENSE) và [NOTICE.md](NOTICE.md) của nguồn. MIT áp dụng phần dữ liệu/cấu trúc/hướng dẫn; ảnh và GIF có điều kiện sử dụng riêng của Gym visual. Chủ dự án xác nhận có quyền sử dụng ảnh/GIF ngày 01/10/2026. Không sao chép giấy phép mua hàng hoặc tài liệu riêng của chủ dự án vào repo.

Mỗi màn hình có media cần giữ ghi công **© Gym visual — https://gymvisual.com/**, dữ liệu từng bài đã lưu `ghi_cong_media`. GIF đã kiểm tra 180×180; tệp ảnh/GIF được sao chép nguyên bản và đối chiếu SHA-256. Quyền đã xác nhận không có nghĩa ảnh được cấp phép MIT.
