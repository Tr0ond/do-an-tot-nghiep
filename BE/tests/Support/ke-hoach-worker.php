<?php

use App\Models\TaiKhoan;
use App\Services\KeHoachTapService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = $argv[1] ?? '';
if (! preg_match('/^kiem_tra_ke_hoach_[a-f0-9]{16}$/D', $db)) {
    exit(2);
}
config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null, 'database.default' => 'mysql']);
DB::purge('mysql');
if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db) {
    exit(3);
}
file_put_contents($argv[6], 'ready');
try {
    $k = app(KeHoachTapService::class)->thaoTac(TaiKhoan::findOrFail((int) $argv[2]), (int) $argv[3], $argv[4], $argv[5]);
    echo json_encode(['ok' => true, 'id' => $k->id, 'trang_thai' => $k->trang_thai]);
} catch (HttpExceptionInterface $e) {
    echo json_encode(['ok' => false, 'status' => $e->getStatusCode()]);
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e));
    exit(1);
}
