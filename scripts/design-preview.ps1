param([switch]$Stop, [int]$FrontendPort = 5310, [int]$BackendPort = 8030)

$ErrorActionPreference = 'Stop'
$workspace = Split-Path $PSScriptRoot -Parent
$statePath = Join-Path $env:TEMP 'fitforge-motion-preview.json'
$backend = Join-Path $workspace 'BE'
$frontend = Join-Path $workspace 'FE'
$php = (Get-Command php).Source
$node = (Get-Command node).Source

if (Test-Path -LiteralPath $statePath) {
    $state = Get-Content -LiteralPath $statePath -Raw | ConvertFrom-Json
    if ($state.workspace -ne $workspace) { throw 'Bản xem trước thuộc workspace khác.' }
    if (!$Stop) { $state | ConvertTo-Json -Depth 5; exit }
    foreach ($item in $state.processes) {
        $process = Get-Process -Id $item.id -ErrorAction SilentlyContinue
        if ($process -and $process.StartTime.ToUniversalTime().Ticks -eq ([DateTime]$item.started).ToUniversalTime().Ticks) {
            Stop-Process -Id $item.id -Force
        }
    }
    if ($state.database -notmatch '^kiem_tra_hanh_trinh_demo_[a-f0-9]{16}$') { throw 'Database không phải demo.' }
    Push-Location $backend
    try { & $php tests/Support/hanh-trinh-demo.php drop $state.database }
    finally { Pop-Location }
    if ($LASTEXITCODE -ne 0) { throw 'Chưa dọn được database demo.' }
    Remove-Item -LiteralPath $statePath
    exit
}
if ($Stop) { Write-Output 'Không có bản xem trước đang chạy.'; exit }
if ($FrontendPort -eq $BackendPort) { throw 'Hai cổng phải khác nhau.' }
if (Test-Path -LiteralPath (Join-Path $backend 'bootstrap/cache/config.php')) { throw 'Cần xóa config cache trước khi mở demo.' }
foreach ($port in @($FrontendPort, $BackendPort)) {
    if (Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue) { throw "Cổng $port đang được dùng." }
}
$environment = @{
    APP_ENV = 'local'; DB_URL = ''; DB_CONNECTION = 'mysql'; APP_URL = "http://localhost:$BackendPort"
    FRONTEND_URL = "http://localhost:$FrontendPort"; SANCTUM_STATEFUL_DOMAINS = "localhost:$FrontendPort"
    SESSION_DOMAIN = ''; SESSION_COOKIE = "motion_preview_$FrontendPort"; SESSION_SECURE_COOKIE = 'false'
    SESSION_DRIVER = 'file'; CACHE_STORE = 'array'; QUEUE_CONNECTION = 'sync'; BROADCAST_CONNECTION = 'null'
    MAIL_MAILER = 'array'; GEMINI_API_KEY = ''; PAYOS_CLIENT_ID = ''; PAYOS_API_KEY = ''; PAYOS_CHECKSUM_KEY = ''
    VITE_API_BASE_URL = "http://localhost:$BackendPort/api/v1"; VITE_REVERB_APP_KEY = ''
}
$previous = @{}
$processes = @()
$database = $null
try {
    foreach ($key in $environment.Keys) {
        $previous[$key] = [Environment]::GetEnvironmentVariable($key, 'Process')
        [Environment]::SetEnvironmentVariable($key, $environment[$key], 'Process')
    }
    Push-Location $backend
    try {
        $fixture = & $php tests/Support/hanh-trinh-demo.php | ConvertFrom-Json
        if ($LASTEXITCODE -ne 0) { throw 'Không tạo được dữ liệu demo.' }
    } finally { Pop-Location }
    $database = $fixture.database
    if ($database -notmatch '^kiem_tra_hanh_trinh_demo_[a-f0-9]{16}$') { throw 'Database không hợp lệ.' }
    $previous['DB_DATABASE'] = [Environment]::GetEnvironmentVariable('DB_DATABASE', 'Process')
    [Environment]::SetEnvironmentVariable('DB_DATABASE', $database, 'Process')
    $processes += Start-Process -FilePath $php -ArgumentList @('-S', "127.0.0.1:$BackendPort", '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php') -WorkingDirectory (Join-Path $backend 'public') -WindowStyle Hidden -PassThru
    $processes += Start-Process -FilePath $node -ArgumentList @('node_modules/vite/bin/vite.js', '--host=localhost', "--port=$FrontendPort", '--strictPort') -WorkingDirectory $frontend -WindowStyle Hidden -PassThru
    $state = @{
        workspace = $workspace; database = $database; frontend = "http://localhost:$FrontendPort"; backend = "http://localhost:$BackendPort"
        fixture = $fixture
        processes = @($processes | ForEach-Object { @{ id = $_.Id; started = $_.StartTime.ToUniversalTime().ToString('o') } })
    }
    $state | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath $statePath -Encoding UTF8
    $state | ConvertTo-Json -Depth 5
} catch {
    foreach ($process in $processes) { Stop-Process -Id $process.Id -Force -ErrorAction SilentlyContinue }
    if ($database -match '^kiem_tra_hanh_trinh_demo_[a-f0-9]{16}$') {
        Push-Location $backend
        try { & $php tests/Support/hanh-trinh-demo.php drop $database }
        finally { Pop-Location }
    }
    throw
} finally {
    foreach ($key in $previous.Keys) { [Environment]::SetEnvironmentVariable($key, $previous[$key], 'Process') }
}
