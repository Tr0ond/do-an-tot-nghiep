param(
    [switch]$Check,
    [string]$Ip,
    [ValidateRange(1024, 65535)][int]$ExpoPort = 8082
)

$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$thuMuc = Split-Path $PSScriptRoot -Parent
$mobile = Join-Path $thuMuc 'Mobile'
$backend = Join-Path $thuMuc 'BE'
$tienTrinh = @()
$maThoat = 0
$tenBien = 'REACT_NATIVE_PACKAGER_HOSTNAME'
$hostCu = [Environment]::GetEnvironmentVariable($tenBien, 'Process')

function layCongDangNghe([int]$cong) {
    @(Get-NetTCPConnection -State Listen -LocalPort $cong -ErrorAction SilentlyContinue)
}

function ngheTrenLan($danhSach) {
    @($danhSach | Where-Object { $_.LocalAddress -in @($Ip, '0.0.0.0', '::') }).Count -gt 0
}

function dungTienTrinh($tienTrinhCha) {
    # Chỉ dừng cây tiến trình do lần chạy này mở; không dừng server có sẵn.
    if ($tienTrinhCha.HasExited) { return }
    $con = @(Get-CimInstance Win32_Process -Filter "ParentProcessId=$($tienTrinhCha.Id)")
    foreach ($p in $con) {
        $dangChay = Get-Process -Id $p.ProcessId -ErrorAction SilentlyContinue
        if ($dangChay) { dungTienTrinh $dangChay }
    }
    Stop-Process -Id $tienTrinhCha.Id -Force -ErrorAction SilentlyContinue
}

function kiemTraBackend {
    try {
        $phanHoi = Invoke-RestMethod -Uri "http://${Ip}:8000/api/v1/health" -TimeoutSec 2
        return $phanHoi.status -eq $true -and $null -ne $phanHoi.data.ung_dung
    } catch { return $false }
}

function capNhatBien([string]$noiDung, [string]$ten, [string]$giaTri) {
    $mau = '(?m)^\s*' + [regex]::Escape($ten) + '=.*$'
    $dong = "$ten=$giaTri"
    if ([regex]::IsMatch($noiDung, $mau)) {
        return [regex]::Replace($noiDung, $mau, $dong)
    }
    return $noiDung.TrimEnd() + "`n$dong`n"
}

try {
    $php = (Get-Command php -ErrorAction Stop).Source
    $node = (Get-Command node -ErrorAction Stop).Source
    foreach ($file in @('BE/artisan', 'BE/vendor/autoload.php', 'BE/.env', 'Mobile/package.json', 'Mobile/node_modules/expo/bin/cli', 'Mobile/.env.example')) {
        if (!(Test-Path -LiteralPath (Join-Path $thuMuc $file))) {
            throw "Thiếu $file. Cài thư viện/cấu hình theo README trước khi chạy."
        }
    }
    if ($ExpoPort -in @(8000, 8080)) { throw 'Cổng Expo phải khác 8000 (Backend) và 8080 (chat).' }

    $diaChi = @(Get-NetIPConfiguration | Where-Object {
        $_.IPv4DefaultGateway -and $_.IPv4Address.IPAddress -and
        $_.IPv4Address.IPAddress -notmatch '^(127\.|169\.254\.)'
    } | Sort-Object @{ Expression = { if ($_.InterfaceAlias -match 'Wi-?Fi|Wireless|WLAN') { 0 } else { 1 } } }, InterfaceIndex)
    if (!$Ip) {
        if (!$diaChi.Count) { throw 'Không tìm thấy mạng LAN. Kết nối Wi-Fi rồi chạy lại, hoặc truyền -Ip <IPv4 của máy tính>.' }
        $Ip = @($diaChi[0].IPv4Address)[0].IPAddress
    }
    $ipHopLe = $null
    if (![System.Net.IPAddress]::TryParse($Ip, [ref]$ipHopLe) -or
        $ipHopLe.AddressFamily -ne [System.Net.Sockets.AddressFamily]::InterNetwork -or
        $Ip -match '^(127\.|169\.254\.)' -or $Ip -eq '0.0.0.0') {
        throw 'Cần IPv4 LAN thực tế của máy tính, không dùng localhost.'
    }
    if (!(Get-NetIPAddress -AddressFamily IPv4 -IPAddress $Ip -ErrorAction SilentlyContinue)) {
        throw "IP $Ip không thuộc máy tính này. Kiểm tra kết nối Wi-Fi."
    }

    Write-Host "IP máy tính: $Ip | Expo: $ExpoPort | Backend: 8000 | Chat: 8080"
    $congExpo = @(layCongDangNghe $ExpoPort)
    $congBackend = @(layCongDangNghe 8000)
    $congChat = @(layCongDangNghe 8080)
    if ($congExpo.Count) {
        $chuSoHuu = ($congExpo.OwningProcess | Select-Object -Unique) -join ', '
        throw "Cổng Expo $ExpoPort đang được dùng (PID $chuSoHuu). Dừng phiên Expo cũ bằng Ctrl+C hoặc chạy start-mobile.bat -ExpoPort 8083."
    }
    $backendSanSang = ngheTrenLan $congBackend
    if ($backendSanSang -and !(kiemTraBackend)) {
        throw 'Cổng 8000 trên LAN đang được dùng nhưng API health không trả đúng. Xem lại server đang chạy.'
    }
    if ($congChat.Count -and !(ngheTrenLan $congChat)) {
        throw 'Chat đang chạy chỉ ở localhost. Dừng cửa sổ Reverb cũ bằng Ctrl+C rồi chạy lại file này để dùng chat trên điện thoại.'
    }
    $coReverb = Test-Path -LiteralPath (Join-Path $backend 'vendor/laravel/reverb/src/ReverbServiceProvider.php')
    if (!$coReverb) { throw 'Thiếu thư viện Reverb. Chạy composer install trong BE.' }

    if ($Check) {
        Write-Host '[OK] Công cụ, thư viện, IP và cổng phù hợp. Chưa mở server hoặc sửa cấu hình.'
        Write-Host 'Chưa kiểm tra MySQL, tài khoản Expo hoặc kết nối từ iPhone.'
        exit 0
    }

    $envLocal = Join-Path $mobile '.env.local'
    $nguon = if (Test-Path -LiteralPath $envLocal) { $envLocal } else { Join-Path $mobile '.env.example' }
    $noiDung = [System.IO.File]::ReadAllText($nguon)
    $noiDung = capNhatBien $noiDung 'EXPO_PUBLIC_API_URL' "http://${Ip}:8000/api/v1"
    $noiDung = capNhatBien $noiDung 'EXPO_PUBLIC_REVERB_URL' "ws://${Ip}:8080"
    # Giữ public key, origin và các biến khác đã cấu hình, không đọc secrets Backend.
    [System.IO.File]::WriteAllText($envLocal, $noiDung.Replace("`r`n", "`n"), [System.Text.UTF8Encoding]::new($false))

    $log = Join-Path $mobile '.expo/launcher'
    New-Item -ItemType Directory -Path $log -Force | Out-Null
    if (!$backendSanSang) {
        $tienTrinh += Start-Process -FilePath $php -ArgumentList @('artisan', 'serve:local', "--host=$Ip", '--port=8000', '--tries=1', '--no-reload') -WorkingDirectory $backend -WindowStyle Hidden -RedirectStandardOutput (Join-Path $log 'backend.log') -RedirectStandardError (Join-Path $log 'backend-error.log') -PassThru
        $sanSang = $false
        for ($lan = 0; $lan -lt 15; $lan++) {
            if (kiemTraBackend) { $sanSang = $true; break }
            if ($tienTrinh[-1].HasExited) { break }
            Start-Sleep -Milliseconds 500
        }
        if (!$sanSang) { throw 'Backend chưa sẵn sàng. Xem Mobile/.expo/launcher/backend-error.log và backend.log.' }
    } else { Write-Host 'Dùng Backend LAN đang chạy.' }

    if (!$congChat.Count) {
        # Cùng một Reverb nhận kết nối LAN và loopback để Backend phát sự kiện đúng server.
        $tienTrinh += Start-Process -FilePath $php -ArgumentList @('artisan', 'reverb:start', '--host=0.0.0.0', '--port=8080') -WorkingDirectory $backend -WindowStyle Hidden -RedirectStandardOutput (Join-Path $log 'reverb.log') -RedirectStandardError (Join-Path $log 'reverb-error.log') -PassThru
        $sanSang = $false
        for ($lan = 0; $lan -lt 15; $lan++) {
            if (ngheTrenLan (layCongDangNghe 8080)) { $sanSang = $true; break }
            if ($tienTrinh[-1].HasExited) { break }
            Start-Sleep -Milliseconds 500
        }
        if (!$sanSang) { throw 'Reverb chưa sẵn sàng. Xem Mobile/.expo/launcher/reverb-error.log và reverb.log.' }
    } else { Write-Host 'Dùng Reverb LAN đang chạy.' }

    Write-Host 'Bật MySQL. Điện thoại và máy tính cần cùng Wi-Fi.'
    Write-Host "Kiểm tra BE trên Safari: http://${Ip}:8000/api/v1/health"
    Write-Host "Kiểm tra Expo trên Safari: http://${Ip}:$ExpoPort/status"
    Write-Host 'iPhone: Expo Go và CLI phải đăng nhập cùng tài khoản Expo.'
    Write-Host 'Quét QR phía dưới. Giữ cửa sổ này; Ctrl+C để dừng phiên Mobile.'
    Write-Host 'Scheduler/ngrok cho lịch quá hạn và webhook thanh toán chạy riêng theo start.bat.'
    [Environment]::SetEnvironmentVariable($tenBien, $Ip, 'Process')
    Push-Location $mobile
    try {
        # TTY của CLI được giữ để QR hiện trực tiếp; không cho tự đổi cổng khi bị chiếm.
        $cli = 'node_modules/expo/bin/cli'
        & $node $cli start --go --lan --port $ExpoPort
        if ($LASTEXITCODE -notin @(0, 130, -1073741510)) { $maThoat = 1 }
    } finally { Pop-Location }
} catch {
    Write-Host "[LỖI] $($_.Exception.Message)" -ForegroundColor Red
    $maThoat = 1
} finally {
    foreach ($p in $tienTrinh) { dungTienTrinh $p }
    [Environment]::SetEnvironmentVariable($tenBien, $hostCu, 'Process')
}
exit $maThoat
