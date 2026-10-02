@echo off
setlocal DisableDelayedExpansion
chcp 65001 >nul
title Khởi động dự án huấn luyện cá nhân

rem Dùng vị trí file để chạy được từ mọi thư mục, kể cả đường dẫn có dấu.
where php >nul 2>&1
if errorlevel 1 (
    echo [LỖI] Không tìm thấy PHP trong PATH. Hãy cài PHP và thêm vào PATH.
    goto :loi
)
where node >nul 2>&1
if errorlevel 1 (
    echo [LỖI] Không tìm thấy Node.js trong PATH. Hãy cài Node.js phù hợp FE/package.json.
    goto :loi
)
where npm.cmd >nul 2>&1
if errorlevel 1 (
    echo [LỖI] Không tìm thấy npm.cmd trong PATH. Hãy kiểm tra cài đặt Node.js.
    goto :loi
)
where ngrok >nul 2>&1
if errorlevel 1 (
    echo [LỖI] Không tìm thấy ngrok trong PATH. Hãy cài ngrok và thêm vào PATH.
    echo Sau khi cài, cấu hình authtoken ngrok trên máy trước khi chạy lại.
    goto :loi
)
if not exist "%~dp0BE\artisan" (
    echo [LỖI] Không tìm thấy BE\artisan. Đặt start.bat ở thư mục gốc dự án.
    goto :loi
)
if not exist "%~dp0BE\vendor\autoload.php" (
    echo [LỖI] Backend chưa có thư viện. Vào thư mục BE và chạy: composer install
    goto :loi
)
if not exist "%~dp0BE\.env" (
    echo [LỖI] Chưa có BE\.env. Sao chép BE\.env.example thành BE\.env và cấu hình database.
    echo Sau đó vào BE và chạy: php artisan key:generate
    goto :loi
)
if not exist "%~dp0FE\package.json" (
    echo [LỖI] Không tìm thấy FE\package.json. Đặt start.bat ở thư mục gốc dự án.
    goto :loi
)
if not exist "%~dp0FE\node_modules\vite\bin\vite.js" (
    echo [LỖI] Frontend chưa có thư viện. Vào thư mục FE và chạy: npm ci
    goto :loi
)
if not exist "%~dp0FE\.env" (
    echo [LỖI] Chưa có FE\.env. Sao chép FE\.env.example thành FE\.env.
    goto :loi
)

if /i "%~1"=="--check" (
    echo [OK] Đã tìm thấy PHP, Node.js, npm, ngrok, thư viện và file cấu hình BE/FE.
    exit /b 0
)

echo Hãy bật MySQL và bảo đảm đã chạy migrations trước khi sử dụng.
echo Nếu dự án đang chạy, hãy dừng các cửa sổ cũ trước khi chạy lại file này.
echo.

rem Cửa sổ riêng giúp xem lỗi và dừng từng tiến trình bằng Ctrl+C.
rem Giữ cùng hostname localhost để phiên đăng nhập Sanctum hoạt động.
start "Backend Laravel - 8000" /D "%~dp0BE" "%ComSpec%" /d /k "php artisan serve --host=localhost --port=8000 --tries=1"
start "Frontend Vue - 5173" /D "%~dp0FE" "%ComSpec%" /d /k "call npm.cmd run dev"
start "Laravel Scheduler" /D "%~dp0BE" "%ComSpec%" /d /k "php artisan schedule:work"
start "ngrok - Webhook payOS" /D "%~dp0" "%ComSpec%" /d /k "ngrok http http://localhost:8000"

echo Đã mở 4 cửa sổ khởi động. Xem kết quả và lỗi tại từng cửa sổ.
echo Khi Vue báo sẵn sàng, mở: http://localhost:5173
echo Kiểm tra Backend: http://localhost:8000/api/v1/health
echo Trong cửa sổ ngrok, lấy URL HTTPS tại Forwarding và thêm /api/v1/payos/webhook.
echo Nếu URL thay đổi, cập nhật Webhook URL trong kênh thanh toán payOS.
echo Để dừng dự án, nhấn Ctrl+C trong cả 4 cửa sổ rồi đóng cửa sổ.
echo.
pause
exit /b 0

:loi
echo.
if /i not "%~1"=="--check" pause
exit /b 1
