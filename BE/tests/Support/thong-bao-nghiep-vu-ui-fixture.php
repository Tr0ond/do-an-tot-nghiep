<?php

use App\Services\GiaoAnMauService;
use App\Services\LichHenService;
use App\Services\NhatKyTapService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment(['local', 'testing'])) {
    throw new RuntimeException('Chỉ dùng fixture local/testing.');
}
$cfg = config('database.connections.mysql');
$cfg['database'] = null;
$cfg['url'] = null;
config(['database.connections.may_chu_thong_bao_ui' => $cfg]);
$pdo = DB::connection('may_chu_thong_bao_ui')->getPdo();
if (($argv[1] ?? '') === 'drop') {
    $db = $argv[2] ?? '';
    if (! preg_match('/^kiem_tra_thong_bao_ui_[a-f0-9]{16}$/D', $db)) {
        throw new RuntimeException('Sai database QA.');
    }
    $pdo->exec('DROP DATABASE `'.$db.'`');
    exit;
}
$db = 'kiem_tra_thong_bao_ui_'.bin2hex(random_bytes(8));
$pdo->exec('CREATE DATABASE `'.$db.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null, 'broadcasting.default' => 'null']);
    DB::purge('mysql');
    if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db || Artisan::call('migrate', ['--force' => true]) !== 0) {
        throw new RuntimeException('Không tạo được schema QA.');
    }
    $b = (require __DIR__.'/hanh-trinh-fixture.php')();
    $s = app(LichHenService::class);
    $slot = $s->taoKhungGio($b['pt'], ['bat_dau_luc' => now()->addHours(8)->toIso8601String()]);
    $l = $s->datLich($b['kh'], ['khung_gio_id' => $slot->id, 'client_request_id' => (string) Str::uuid()]);
    $s->thaoTac($b['pt'], $l->id, 'xac-nhan');
    $lt = $b['lich'][0];
    $lt->phien->cacBaiTap()->where('thu_tu', 2)->firstOrFail()->cacHiep()->create(['thu_tu' => 1, 'so_lan_lap' => 12, 'nghi_giay' => 60]);
    app(NhatKyTapService::class)->thaoTac($b['kh'], $lt->id, 'hoan-thanh', $lt->updated_at->format('Y-m-d H:i:s.u'));
    app(NhatKyTapService::class)->nhanXet($b['pt'], $lt->id, ['client_request_id' => (string) Str::uuid(), 'noi_dung' => 'Nhận xét QA: đã hoàn thành buổi tự tập.']);
    app(GiaoAnMauService::class)->taoGiaoAn(['ten_giao_an' => 'Giáo án kiểm thử thông báo', 'muc_tieu' => 'Kiểm chứng luồng thông báo',
        'so_ngay_tap' => 1, 'client_request_id' => (string) Str::uuid(), 'bai_tap' => [['bai_tap_id' => $b['keHoach']->cacBaiTap()->first()->bai_tap_id,
            'ngay_thu' => 1, 'thu_tu' => 1, 'so_hiep' => 3, 'so_lan_lap' => 12, 'nghi_giay' => 60, 'ghi_chu' => null]]], $b['admin']->id);
    echo json_encode(['database' => $db, 'lich_hen_id' => $l->id, 'lich_tap_id' => $lt->id]);
} catch (Throwable $e) {
    DB::disconnect('mysql');
    $pdo->exec('DROP DATABASE `'.$db.'`');
    fwrite(STDERR, 'Không tạo được QA: '.get_class($e).PHP_EOL);
    exit(1);
}
