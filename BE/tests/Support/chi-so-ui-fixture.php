<?php

use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use App\Services\ChiSoCoTheService;
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
config(['database.connections.may_chu_chi_so_ui' => $cfg]);
$pdo = DB::connection('may_chu_chi_so_ui')->getPdo();
if (($argv[1] ?? '') === 'drop') {
    $db = $argv[2] ?? '';
    if (! preg_match('/^kiem_tra_chi_so_ui_[a-f0-9]{16}$/D', $db)) {
        throw new RuntimeException('Sai database fixture.');
    }
    $pdo->exec('DROP DATABASE `'.$db.'`');
    exit;
}
$db = 'kiem_tra_chi_so_ui_'.bin2hex(random_bytes(8));
$pdo->exec('CREATE DATABASE `'.$db.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null, 'broadcasting.default' => 'null']);
    DB::purge('mysql');
    if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db || Artisan::call('migrate', ['--force' => true]) !== 0) {
        throw new RuntimeException('Không khởi tạo được database QA.');
    }
    $tao = fn ($ten, $email, $vaiTro) => app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => $ten,
        'email' => $email.'@chi-so-ui.example.test', 'password' => 'Demo123456!'], $vaiTro);
    $kh = $tao('Học viên kiểm thử', 'kh', TaiKhoan::KHACH_HANG);
    $pt = $tao('PT kiểm thử', 'pt', TaiKhoan::HUAN_LUYEN_VIEN);
    $admin = $tao('Admin kiểm thử', 'admin', TaiKhoan::ADMIN);
    PhanCongHuanLuyenVien::create(['khach_hang_id' => $kh->hoSoKhachHang->id, 'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id,
        'nguoi_phan_cong_id' => $admin->id, 'bat_dau_luc' => now()->subDays(60)]);
    foreach ([1, 3, 6, 9, 12, 16, 20, 24, 27, 32, 40, 60] as $i => $ngay) {
        app(ChiSoCoTheService::class)->luu($kh, ['ngay_ghi' => now('Asia/Ho_Chi_Minh')->subDays($ngay)->toDateString(),
            'can_nang_kg' => 70.1 + $i * .18, 'chieu_cao_cm' => 175, 'ghi_chu' => $i === 1 ? 'Đo buổi sáng trước khi tập.' : null]);
    }
    echo json_encode(['database' => $db, 'khach_id' => $kh->hoSoKhachHang->id]);
} catch (Throwable $loi) {
    DB::disconnect('mysql');
    $pdo->exec('DROP DATABASE `'.$db.'`');
    fwrite(STDERR, 'Không tạo được fixture chỉ số: '.get_class($loi).PHP_EOL);
    exit(1);
}
