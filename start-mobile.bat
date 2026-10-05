@echo off
setlocal DisableDelayedExpansion
chcp 65001 >nul
title Chạy Mobile qua Wi-Fi

if /i "%~1"=="--check" (
    powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\start-mobile.ps1" -Check
    exit /b
)

rem Expo cần dùng trực tiếp console để hiện QR và nhận phím Ctrl+C.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\start-mobile.ps1" %*
if errorlevel 1 (
    echo.
    echo Phiên Mobile gặp lỗi hoặc đã bị dừng. Xem thông báo phía trên.
    pause
    exit /b 1
)
exit /b 0
