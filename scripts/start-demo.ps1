param(
    [ValidateRange(1024, 65535)][int]$BackendPort = 8022,
    [ValidateRange(1024, 65535)][int]$FrontendPort = 5302
)

$ErrorActionPreference = 'Stop'
$thuMuc = Split-Path $PSScriptRoot -Parent
$backend = Join-Path $thuMuc 'BE'
$frontend = Join-Path $thuMuc 'FE'
if ($BackendPort -eq $FrontendPort) {
    throw 'Frontend và Backend demo phải dùng hai cổng khác nhau.'
}
if (Test-Path -LiteralPath (Join-Path $backend 'bootstrap/cache/config.php')) {
    throw 'Có config cache. Chạy php artisan config:clear trong BE trước khi mở demo riêng.'
}
foreach ($cong in @($BackendPort, $FrontendPort)) {
    if (Get-NetTCPConnection -State Listen -LocalPort $cong -ErrorAction SilentlyContinue) {
        throw "Cổng $cong đang được dùng. Chọn cổng khác bằng tham số; không dừng ứng dụng đang chạy."
    }
}
$php = (Get-Command php -ErrorAction Stop).Source
$node = (Get-Command node -ErrorAction Stop).Source
if (!(Test-Path -LiteralPath (Join-Path $frontend 'node_modules/vite/bin/vite.js'))) {
    throw 'Cần cài thư viện Frontend trước khi mở demo.'
}
$cu = @{}
$moi = @{
    APP_ENV = 'local'; DB_URL = ''; DB_CONNECTION = 'mysql'; APP_URL = "http://localhost:$BackendPort"
    FRONTEND_URL = "http://localhost:$FrontendPort"; SANCTUM_STATEFUL_DOMAINS = "localhost:$FrontendPort"
    SESSION_DOMAIN = ''; SESSION_COOKIE = "hanh_trinh_demo_$FrontendPort"; SESSION_SECURE_COOKIE = 'false'
    SESSION_DRIVER = 'file'; CACHE_STORE = 'array'; QUEUE_CONNECTION = 'sync'; BROADCAST_CONNECTION = 'null'
    MAIL_MAILER = 'array'; GEMINI_API_KEY = ''; PAYOS_CLIENT_ID = ''; PAYOS_API_KEY = ''; PAYOS_CHECKSUM_KEY = ''
    VITE_API_BASE_URL = "http://localhost:$BackendPort/api/v1"; VITE_REVERB_APP_KEY = ''
}
$tienTrinh = @()
$database = $null
try {
    foreach ($ten in $moi.Keys) {
        $cu[$ten] = [Environment]::GetEnvironmentVariable($ten, 'Process')
        [Environment]::SetEnvironmentVariable($ten, $moi[$ten], 'Process')
    }
    Push-Location $backend
    try {
        $raw = & $php tests/Support/hanh-trinh-demo.php
        if ($LASTEXITCODE -ne 0) { throw 'Không tạo được dữ liệu demo.' }
        $duLieu = $raw | ConvertFrom-Json
        $database = $duLieu.database
    } finally { Pop-Location }
    if ($database -notmatch '^kiem_tra_hanh_trinh_demo_[a-f0-9]{16}$') { throw 'Tên database demo không hợp lệ.' }
    $cu['DB_DATABASE'] = [Environment]::GetEnvironmentVariable('DB_DATABASE', 'Process')
    [Environment]::SetEnvironmentVariable('DB_DATABASE', $database, 'Process')
    $tienTrinh += Start-Process -FilePath $php -ArgumentList @('-S', "127.0.0.1:$BackendPort", '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php') -WorkingDirectory (Join-Path $backend 'public') -WindowStyle Hidden -PassThru
    $tienTrinh += Start-Process -FilePath $node -ArgumentList @('node_modules/vite/bin/vite.js', '--host=localhost', "--port=$FrontendPort", '--strictPort') -WorkingDirectory $frontend -WindowStyle Hidden -PassThru
    Write-Host "Demo riêng: http://localhost:$FrontendPort"
    Write-Host "Database: $database (dữ liệu giả, thanh toán giả lập)."
    Write-Host 'Tài khoản: kh / tu-tap / cho-pt / pt / pt2 / admin @hanh-trinh.example.test'
    Write-Host 'Mật khẩu demo: Demo123456!'
    Write-Host 'Không gọi payOS/Gemini/email thật. Chat dùng tải lại/polling, không chạy Reverb trong demo này.'
    Write-Host 'Kịch bản: md/DEMO_SCRIPT.md. Giữ cửa sổ này trong khi xem.'
    $null = Read-Host 'Bấm Enter khi kết thúc để dừng server demo và xóa database demo'
} finally {
    foreach ($p in $tienTrinh) {
        # Chỉ dừng hai tiến trình trực tiếp do script này mở, không đụng server chính.
        if (Get-Process -Id $p.Id -ErrorAction SilentlyContinue) {
            Stop-Process -Id $p.Id -Force -ErrorAction SilentlyContinue
        }
    }
    if ($database -match '^kiem_tra_hanh_trinh_demo_[a-f0-9]{16}$') {
        Push-Location $backend
        try {
            & $php tests/Support/hanh-trinh-demo.php drop $database
            if ($LASTEXITCODE -ne 0) { Write-Warning "Chưa dọn được database demo $database." }
        } finally { Pop-Location }
    }
    foreach ($ten in $cu.Keys) {
        if ($null -eq $cu[$ten]) {
            Remove-Item -LiteralPath "Env:$ten" -ErrorAction SilentlyContinue
        } else {
            [Environment]::SetEnvironmentVariable($ten, $cu[$ten], 'Process')
        }
    }
}
