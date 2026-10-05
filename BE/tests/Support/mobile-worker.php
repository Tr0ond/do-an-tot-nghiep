<?php

use App\Models\TaiKhoan;
use App\Services\PhienMobileService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = $argv[1] ?? '';
if (! preg_match('/^kiem_tra_mobile_[a-f0-9]{16}$/D', $db)) {
    exit(2);
}
config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null, 'database.default' => 'mysql']);
DB::purge('mysql');
if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db) {
    exit(3);
}
$nguoi = TaiKhoan::findOrFail((int) $argv[2]);
file_put_contents($argv[3], 'ready');
try {
    app(PhienMobileService::class)->dangNhap(['email' => $nguoi->email, 'password' => 'Demo123456!', 'ten_thiet_bi' => 'Worker QA']);
    echo json_encode(['ok' => true]);
} catch (ValidationException) {
    echo json_encode(['ok' => false]);
}
