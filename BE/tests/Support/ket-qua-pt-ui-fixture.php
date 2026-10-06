<?php

use App\Models\BaiTap;
use App\Models\GoiTap;
use App\Services\LichHenService;
use App\Services\MuaGoiService;
use App\Services\PhanCongService;
use App\Services\TaiKhoanService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment(['local', 'testing'])) {
    throw new RuntimeException('Only local/testing fixture.');
}
$cfg = config('database.connections.mysql');
$cfg['database'] = null;
$cfg['url'] = null;
config(['database.connections.may_chu_kqpt_ui' => $cfg]);
$pdo = DB::connection('may_chu_kqpt_ui')->getPdo();
if (($argv[1] ?? '') === 'drop') {
    $db = $argv[2] ?? '';
    if (! preg_match('/^kiem_tra_kqpt_ui_[a-f0-9]{16}$/D', $db)) {
        throw new RuntimeException('Invalid fixture database.');
    }
    $pdo->exec('DROP DATABASE `'.$db.'`');
    exit;
}
if (($argv[1] ?? '') === 'inspect') {
    $db = $argv[2] ?? '';
    if (! preg_match('/^kiem_tra_kqpt_ui_[a-f0-9]{16}$/D', $db)) {
        throw new RuntimeException('Invalid fixture database.');
    }
    config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null]);
    DB::purge('mysql');
    if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db) {
        throw new RuntimeException('Wrong QA database.');
    }
    echo json_encode(['db_version' => DB::selectOne('SELECT VERSION() AS v')->v,
        'results' => DB::table('ket_qua_buoi_pt')->count(),
        'finalized' => DB::table('ket_qua_buoi_pt')->whereNotNull('chot_luc')->count(),
        'appointment' => DB::table('lich_hen_huan_luyen')->where('id', 1)->value('trang_thai'),
        'remaining_credits' => DB::table('dang_ky_goi_tap')->where('id', 1)->value('so_buoi_con_lai')]);
    exit;
}
$db = 'kiem_tra_kqpt_ui_'.bin2hex(random_bytes(8));
$pdo->exec('CREATE DATABASE `'.$db.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null, 'broadcasting.default' => 'null']);
    DB::purge('mysql');
    if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db || Artisan::call('migrate', ['--force' => true]) !== 0) {
        throw new RuntimeException('Cannot initialize QA database.');
    }
    $tao = fn ($vaiTro, $hoTen, $email) => app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => $hoTen, 'email' => $email.'@kqpt.example.test', 'password' => 'Demo123456!'], $vaiTro);
    $kh = $tao('KHACH_HANG', 'Khách hàng QA kết quả PT', 'kh');
    $pt = $tao('HUAN_LUYEN_VIEN', 'Huấn luyện viên QA', 'pt');
    $admin = $tao('ADMIN', 'Admin QA', 'admin');
    $goi = GoiTap::create(['ten_goi' => 'Gói QA kết quả PT', 'gia' => 99000, 'co_chatbot' => true, 'so_luot_chatbot_moi_ngay' => 10, 'so_buoi_pt' => 8, 'thoi_han_ngay' => 30, 'trang_thai' => 'HOAT_DONG']);
    $don = app(MuaGoiService::class)->taoDon($kh, ['goi_tap_id' => $goi->id, 'client_request_id' => (string) Str::uuid()]);
    // Dữ liệu QA riêng: không gửi tiền hoặc gọi cổng thanh toán.
    $don->update(['trang_thai' => 'DANG_SU_DUNG', 'kich_hoat_luc' => now()->subDay(), 'het_han_luc' => now()->addDays(29), 'so_buoi_con_lai' => 8]);
    app(PhanCongService::class)->phanCong($admin, ['khach_hang_id' => $kh->hoSoKhachHang->id, 'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'client_request_id' => (string) Str::uuid()]);
    $slot = app(LichHenService::class)->taoKhungGio($pt, ['bat_dau_luc' => now()->addHours(8)->toIso8601String()]);
    $lich = app(LichHenService::class)->datLich($kh, ['khung_gio_id' => $slot->id, 'client_request_id' => (string) Str::uuid()]);
    app(LichHenService::class)->thaoTac($pt, $lich->id, 'xac-nhan');
    $thoiGian = ['bat_dau_luc' => now()->subHours(2), 'ket_thuc_luc' => now()->subHour()];
    $slot->update($thoiGian);
    $lich->update($thoiGian);
    $nhom = DB::table('nhom_co')->insertGetId(['ma_nhom_co' => 'nguc', 'ten_nhom_co' => 'Ngực', 'ten_nguon' => 'chest', 'trang_thai' => 'HOAT_DONG']);
    foreach (['Chống đẩy', 'Đẩy ngực với tạ đơn', 'Squat'] as $ten) {
        BaiTap::create(['nhom_co_id' => $nhom, 'ten_bai_tap' => $ten, 'trang_thai' => 'HOAT_DONG']);
    }
    echo json_encode(['database' => $db, 'lich_id' => $lich->id, 'don_id' => $don->id], JSON_UNESCAPED_UNICODE);
} catch (Throwable $loi) {
    DB::disconnect('mysql');
    $pdo->exec('DROP DATABASE `'.$db.'`');
    fwrite(STDERR, 'Cannot create fixture: '.get_class($loi).' '.$loi->getMessage().PHP_EOL);
    exit(1);
}
