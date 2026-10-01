<?php

// Chạy sau kiemTraGiaoDienM01.php; chỉ dùng server/database thử ở cổng 8001.
$gocBackend = 'http://localhost:8001';
$cookies = [];
$client = curl_init();

function guiRequest(string $phuongThuc, string $duongDan, ?array $duLieu = null, bool $guiCsrf = false): array
{
    global $client, $cookies, $gocBackend;
    $headers = ['Accept: application/json', 'Origin: http://localhost:5174', 'Content-Type: application/json'];
    if ($guiCsrf && isset($cookies['XSRF-TOKEN'])) {
        $headers[] = 'X-XSRF-TOKEN: '.urldecode($cookies['XSRF-TOKEN']);
    }
    $cookieHeader = implode('; ', array_map(fn ($ten, $giaTri) => $ten.'='.$giaTri, array_keys($cookies), $cookies));
    curl_setopt_array($client, [
        CURLOPT_URL => $gocBackend.$duongDan,
        CURLOPT_CUSTOMREQUEST => $phuongThuc,
        CURLOPT_POSTFIELDS => $duLieu === null ? null : json_encode($duLieu),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_COOKIE => $cookieHeader,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$cookies) {
            if (preg_match('/^Set-Cookie: ([^=]+)=([^;]*)/i', $header, $ketQua)) {
                $cookies[$ketQua[1]] = $ketQua[2];
            }

            return strlen($header);
        },
    ]);
    $noiDung = curl_exec($client);
    if ($noiDung === false) {
        throw new RuntimeException('Không kết nối được môi trường thử ở cổng 8001.');
    }

    return ['ma' => curl_getinfo($client, CURLINFO_HTTP_CODE), 'data' => json_decode($noiDung, true)];
}

function phaiCoMa(array $phanHoi, int $ma): void
{
    if ($phanHoi['ma'] !== $ma) {
        throw new RuntimeException('Mã HTTP thực tế '.$phanHoi['ma'].' khác '.$ma);
    }
}

try {
    phaiCoMa(guiRequest('GET', '/api/v1/me'), 401);
    phaiCoMa(guiRequest('POST', '/dang-nhap', ['email' => 'admin-qa@example.test', 'password' => 'ThuNghiemM01!42']), 419);
    phaiCoMa(guiRequest('GET', '/sanctum/csrf-cookie'), 204);
    $cookieTruoc = $cookies['giao_dien_m01_session'];
    phaiCoMa(guiRequest('POST', '/dang-nhap', ['email' => 'admin-qa@example.test', 'password' => 'ThuNghiemM01!42'], true), 200);
    $cookieDangNhap = $cookies['giao_dien_m01_session'];
    if ($cookieDangNhap === $cookieTruoc) {
        throw new RuntimeException('Đăng nhập phải đổi session cookie.');
    }
    phaiCoMa(guiRequest('GET', '/api/v1/me'), 200);
    $cookiesSauDangNhap = $cookies;
    $cookies['giao_dien_m01_session'] = $cookieTruoc;
    phaiCoMa(guiRequest('GET', '/api/v1/me'), 401);
    $cookies = $cookiesSauDangNhap;
    phaiCoMa(guiRequest('GET', '/api/v1/me'), 200);
    $duLieu = ['ho_ten' => 'PT HTTP kiểm thử', 'email' => 'pt-http-'.bin2hex(random_bytes(4)).'@example.test', 'password' => 'ThuNghiemM01!42', 'password_confirmation' => 'ThuNghiemM01!42', 'vai_tro' => 'HUAN_LUYEN_VIEN'];
    phaiCoMa(guiRequest('POST', '/api/v1/admin/tai-khoan', $duLieu), 419);
    phaiCoMa(guiRequest('POST', '/api/v1/admin/tai-khoan', $duLieu, true), 201);
    phaiCoMa(guiRequest('POST', '/dang-xuat', null, true), 200);
    phaiCoMa(guiRequest('GET', '/api/v1/me'), 401);
    $cookies['giao_dien_m01_session'] = $cookieDangNhap;
    phaiCoMa(guiRequest('GET', '/api/v1/me'), 401);
    echo 'PASS HTTP thật: CSRF session/API, đăng nhập, đổi cookie, đăng xuất và phát lại cookie cũ bị từ chối.'.PHP_EOL;
} finally {
    curl_close($client);
}
