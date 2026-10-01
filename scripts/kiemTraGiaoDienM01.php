<?php

use App\Models\TaiKhoan;
use App\Services\TaiKhoanService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

require __DIR__.'/../BE/vendor/autoload.php';
$ungDung = require __DIR__.'/../BE/bootstrap/app.php';
$ungDung->make(Kernel::class)->bootstrap();
foreach ([8001, 5174] as $cong) {
    $socket = @fsockopen('localhost', $cong, $maLoi, $thongBaoLoi, 1);
    if ($socket !== false) {
        fclose($socket);
        throw new RuntimeException('Cổng kiểm thử '.$cong.' đang được sử dụng; hãy dừng phiên thử trước.');
    }
}
$ketNoiGoc = DB::connection();
$tenDatabase = 'kiem_tra_giao_dien_'.bin2hex(random_bytes(8));
// Chỉ dùng cho kiểm thử: tạo database mới, không nhận tên database từ bên ngoài.
$ketNoiGoc->statement('CREATE DATABASE `'.$tenDatabase.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$be = null;
$fe = null;

try {
    $cauHinh = $ketNoiGoc->getConfig();
    $cauHinh['database'] = $tenDatabase;
    $cauHinh['url'] = null;
    $cauHinh['name'] = 'giao_dien_m01';
    config(['database.connections.giao_dien_m01' => $cauHinh]);
    DB::setDefaultConnection('giao_dien_m01');
    if (Artisan::call('migrate', ['--database' => 'giao_dien_m01', '--force' => true]) !== 0) {
        throw new RuntimeException(Artisan::output());
    }
    foreach (['admin' => TaiKhoan::ADMIN, 'pt' => TaiKhoan::HUAN_LUYEN_VIEN] as $ten => $vaiTro) {
        app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => $ten === 'admin' ? 'Admin kiểm thử' : 'Huấn luyện viên kiểm thử', 'email' => $ten.'-qa@example.test', 'password' => 'ThuNghiemM01!42'], $vaiTro);
    }
    $moiTruong = [
        'APP_ENV' => 'local', 'APP_DEBUG' => 'false',
        'APP_URL' => 'http://localhost:8001', 'FRONTEND_URL' => 'http://localhost:5174',
        'SANCTUM_STATEFUL_DOMAINS' => 'localhost:5174', 'DB_URL' => '',
        'DB_CONNECTION' => $cauHinh['driver'], 'DB_DATABASE' => $tenDatabase,
        'DB_HOST' => $cauHinh['host'], 'DB_PORT' => (string) $cauHinh['port'],
        'DB_USERNAME' => $cauHinh['username'], 'DB_PASSWORD' => $cauHinh['password'],
        'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'giao_dien_m01_session',
        'CACHE_STORE' => 'array',
    ];
    $be = new Process([PHP_BINARY, 'artisan', 'serve', '--host=localhost', '--port=8001', '--tries=1', '--no-reload'], base_path(), $moiTruong);
    $fe = new Process([PHP_OS_FAMILY === 'Windows' ? 'npm.cmd' : 'npm', 'run', 'dev', '--', '--port', '5174'], __DIR__.'/../FE', ['VITE_API_BASE_URL' => 'http://localhost:8001/api/v1']);
    $be->setTimeout(null)->start();
    $fe->setTimeout(null)->start();
    echo 'Database kiểm thử riêng đã tạo. Frontend: http://localhost:5174'.PHP_EOL;
    echo 'Tài khoản giả: admin-qa@example.test / pt-qa@example.test; mật khẩu thử: ThuNghiemM01!42'.PHP_EOL;
    echo 'Nhấn Enter để dừng hai server và xóa database kiểm thử vừa tạo.'.PHP_EOL;
    fgets(STDIN);
} finally {
    $fe?->stop();
    $be?->stop();
    DB::purge('giao_dien_m01');
    $ketNoiGoc->statement('DROP DATABASE `'.$tenDatabase.'`');
    echo 'Đã dọn môi trường kiểm thử giao diện.'.PHP_EOL;
}
