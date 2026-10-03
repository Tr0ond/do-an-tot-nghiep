<?php

use App\Models\TaiKhoan;
use App\Services\KeHoachTapService;
use App\Services\NhatKyTapService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = $argv[1] ?? '';
if (! preg_match('/^kiem_tra_nhat_ky_[a-f0-9]{16}$/D', $db)) {
    exit(2);
}
config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null, 'database.default' => 'mysql']);
DB::purge('mysql');
if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db) {
    exit(3);
}
$d = json_decode(file_get_contents($argv[2]), true, flags: JSON_THROW_ON_ERROR);
file_put_contents($argv[3], 'ready');
try {
    $nguoi = TaiKhoan::findOrFail($d['nguoi_id']);
    $s = app(NhatKyTapService::class);
    $l = match ($d['hanh_dong']) {
        'tao' => $s->tao($nguoi, $d['khach_id'], $d['body']),
        'luu' => $s->luu($nguoi, $d['id'], $d['body']),
        'nhan-xet' => $s->nhanXet($nguoi, $d['id'], $d['body']),
        'luu-tru' => app(KeHoachTapService::class)->thaoTac($nguoi, $d['id'], 'luu-tru', $d['body']['updated_at']),
        default => $s->thaoTac($nguoi, $d['id'], $d['hanh_dong'], $d['body']['updated_at']),
    };
    echo json_encode(['ok' => true, 'id' => $l->id, 'trang_thai' => $l->trang_thai]);
} catch (HttpExceptionInterface $e) {
    echo json_encode(['ok' => false, 'status' => $e->getStatusCode()]);
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e));
    exit(1);
}
