<?php

use App\Services\TaiKhoanService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment(['local', 'testing'])) {
    throw new RuntimeException('Chỉ chạy fixture local/testing.');
}
$cfg = config('database.connections.mysql');
$cfg['database'] = null;
$cfg['url'] = null;
config(['database.connections.may_chu_hanh_trinh_ui' => $cfg]);
$pdo = DB::connection('may_chu_hanh_trinh_ui')->getPdo();
if (($argv[1] ?? '') === 'drop') {
    $db = $argv[2] ?? '';
    if (! preg_match('/^kiem_tra_hanh_trinh_ui_[a-f0-9]{16}$/D', $db)) {
        throw new RuntimeException('Sai database fixture.');
    }
    $pdo->exec('DROP DATABASE `'.$db.'`');
    exit;
}
$db = 'kiem_tra_hanh_trinh_ui_'.bin2hex(random_bytes(8));
$pdo->exec('CREATE DATABASE `'.$db.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null, 'broadcasting.default' => 'null']);
    DB::purge('mysql');
    if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db || Artisan::call('migrate', ['--force' => true]) !== 0) {
        throw new RuntimeException('Không khởi tạo được database QA.');
    }
    $tao = require __DIR__.'/hanh-trinh-fixture.php';
    $f = $tao();
    $rong = app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'KH mới kiểm thử', 'email' => 'rong@hanh-trinh.example.test', 'password' => 'Demo123456!'], 'KHACH_HANG');
    echo json_encode(['database' => $db, 'khach_id' => $f['kh']->hoSoKhachHang->id, 'rong_id' => $rong->id]);
} catch (Throwable $loi) {
    DB::disconnect('mysql');
    $pdo->exec('DROP DATABASE `'.$db.'`');
    fwrite(STDERR, 'Không tạo được fixture hành trình: '.get_class($loi).PHP_EOL);
    exit(1);
}
