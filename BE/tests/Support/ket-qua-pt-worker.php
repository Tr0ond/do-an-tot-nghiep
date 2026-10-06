<?php

use App\Models\TaiKhoan;
use App\Services\KetQuaBuoiPtService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = $argv[1] ?? '';
if (! preg_match('/^kiem_tra_ket_qua_pt_[a-f0-9]{16}$/D', $db)) {
    exit(2);
}
config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null, 'database.default' => 'mysql']);
DB::purge('mysql');
if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db) {
    exit(3);
}
file_put_contents($argv[5], 'ready');
try {
    $pt = TaiKhoan::findOrFail((int) $argv[3]);
    $d = json_decode($argv[6], true, flags: JSON_THROW_ON_ERROR);
    $s = app(KetQuaBuoiPtService::class);
    $r = $argv[2] === 'luu' ? $s->luu($pt, (int) $argv[4], $d) : $s->chot($pt, (int) $argv[4], $d['updated_at']);
    echo json_encode(['ok' => true, 'id' => $r->id]);
} catch (HttpException $e) {
    echo json_encode(['ok' => false, 'status' => $e->getStatusCode()]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'exception' => get_class($e)]);
    exit(1);
}
