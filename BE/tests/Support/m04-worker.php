<?php

use App\Models\TaiKhoan;
use App\Services\LichHenService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = $argv[1] ?? '';
if (! preg_match('/^kiem_tra_lich_hen_[a-f0-9]{16}$/D', $db)) {
    exit(2);
}
config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null, 'database.default' => 'mysql']);
DB::purge('mysql');
if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db) {
    exit(3);
}
file_put_contents($argv[5], 'ready');
try {
    $nguoi = TaiKhoan::findOrFail((int) $argv[3]);
    $r = $argv[2] === 'dat'
        ? app(LichHenService::class)->datLich($nguoi, ['khung_gio_id' => (int) $argv[4], 'client_request_id' => $argv[6]])
        : app(LichHenService::class)->thaoTac($nguoi, (int) $argv[4], 'hoan-thanh');
    echo json_encode(['ok' => true, 'id' => $r->id]);
} catch (HttpException $e) {
    echo json_encode(['ok' => false, 'status' => $e->getStatusCode()]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'exception' => get_class($e)]);
    exit(1);
}
