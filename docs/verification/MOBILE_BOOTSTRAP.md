# Kiểm chứng khởi tạo Mobile — 05/10/2026

## Phạm vi

Theo C40, tạo `Mobile/` bằng mẫu Expo `blank` JavaScript chính thức, cài dependencies và lưu npm lockfile. Màn hình khởi động ghi ứng dụng huấn luyện cá nhân dành cho KH/PT. Thêm React Native Web để lệnh xem trước web hoạt động. Không thay mã nghiệp vụ FE/BE, không migration, không kết nối API hoặc tạo tài khoản Expo/EAS.

## Môi trường và kết quả thực tế

- Windows, Node `22.20.0`, npm `10.9.3`.
- Expo `57.0.26`, React Native `0.86.3`, React `19.2.3`.
- Cài đặt dependencies: thành công, có `Mobile/package-lock.json`.
- `npx expo install --check`: Dependencies are up to date.
- `npx expo-doctor@latest`: 21/21 checks passed, chạy lại sau khi thêm hỗ trợ web vẫn đạt.
- `npx expo export --platform all`: exit 0; bundle thành công cho Android (580 modules), iOS (582 modules), web (183 modules), xuất vào `Mobile/dist/` được Git bỏ qua.
- Không tạo Git repository lồng trong `Mobile/`.

## Giới hạn

- Đây là kiểm tra cấu hình và đóng gói JavaScript/assets; chưa build APK/IPA, chưa chạy trên điện thoại hoặc simulator, chưa kiểm thử UI trực quan.
- `npm audit --omit=dev` báo 23 cảnh báo phụ thuộc (7 moderate, 16 high), gồm chuỗi phụ thuộc qua `braces`, `node-forge`, `uuid` của công cụ Expo/Metro. Không diễn giải số package bị ảnh hưởng thành 23 lỗi độc lập trong mã ứng dụng. Chưa xác minh khả năng khai thác trong app.
- npm đề xuất `audit fix --force` hạ Expo về `44.0.6`; không áp dụng vì phá bộ phiên bản tương thích SDK 57. Các cảnh báo còn tồn tại, cần theo dõi bản sửa upstream trước phát hành.
- Chưa có đăng nhập mobile, API client, điều hướng KH/PT, chat, chatbot, thanh toán hoặc push. Web tiếp tục dùng xác thực session hiện có; xác thực mobile cần được triển khai riêng trước tích hợp nghiệp vụ.

## Cách xem

Mở terminal trong `Mobile/`, chạy `npm start` và dùng Expo Go tương thích để quét QR cùng mạng Wi-Fi, hoặc `npm run web` để xem trên trình duyệt. Máy clone chạy `npm ci` trước. Chi tiết tại [Mobile README](../../Mobile/README.md).
