<?php

use App\Services\TaiKhoanService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
    if (($argv[1] ?? '') === 'with-pt') {
        $hom = CarbonImmutable::now('Asia/Ho_Chi_Minh')->startOfDay();
        foreach ([-4, -1] as $cach) {
            $batDau = $hom->addDays($cach)->addHours(18)->utc();
            $slot = DB::table('khung_gio_huan_luyen_vien')->insertGetId([
                'huan_luyen_vien_id' => $f['pt']->hoSoHuanLuyenVien->id,
                'bat_dau_luc' => $batDau, 'ket_thuc_luc' => $batDau->addHour(), 'trang_thai' => 'MO',
            ]);
            DB::table('lich_hen_huan_luyen')->insert([
                'khach_hang_id' => $f['kh']->hoSoKhachHang->id, 'huan_luyen_vien_id' => $f['pt']->hoSoHuanLuyenVien->id,
                'phan_cong_id' => $f['pc']->id, 'khung_gio_id' => $slot, 'dang_ky_goi_tap_id' => $f['don']->id,
                'client_request_id' => (string) Str::uuid(), 'bat_dau_luc' => $batDau,
                'ket_thuc_luc' => $batDau->addHour(), 'trang_thai' => 'HOAN_THANH', 'tieu_hao_luc' => $batDau->addHour(),
            ]);
        }
    }
    $rong = app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'KH mới kiểm thử', 'email' => 'rong@hanh-trinh.example.test', 'password' => 'Demo123456!'], 'KHACH_HANG');
    echo json_encode(['database' => $db, 'khach_id' => $f['kh']->hoSoKhachHang->id, 'rong_id' => $rong->id]);
} catch (Throwable $loi) {
    DB::disconnect('mysql');
    $pdo->exec('DROP DATABASE `'.$db.'`');
    fwrite(STDERR, 'Không tạo được fixture hành trình: '.get_class($loi).PHP_EOL);
    exit(1);
}
