<?php

use App\Models\TaiKhoan;
use App\Services\ChiSoCoTheService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = $argv[1] ?? '';
if (! preg_match('/^kiem_tra_chi_so_[a-f0-9]{16}$/D', $db)) {
    exit(2);
}
config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null, 'database.default' => 'mysql']);
DB::purge('mysql');
if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db) {
    exit(3);
}
$d = json_decode(file_get_contents($argv[2]), true, flags: JSON_THROW_ON_ERROR);
file_put_contents($argv[3], 'ready');
$ban = app(ChiSoCoTheService::class)->luu(TaiKhoan::findOrFail($d['nguoi_id']), $d['body']);
echo json_encode(['id' => $ban->id]);
