<?php

use App\Models\TaiKhoan;
use App\Services\ChatService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = $argv[1] ?? '';
if (! preg_match('/^kiem_tra_chat_[a-f0-9]{16}$/D', $db)) {
    exit(2);
}
config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null, 'database.default' => 'mysql', 'broadcasting.default' => 'null']);
DB::purge('mysql');
if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db) {
    exit(3);
}
file_put_contents($argv[4], 'ready');
try {
    $duLieu = ['client_message_id' => $argv[5], 'noi_dung' => 'Hai process cùng gửi'];
    if (isset($argv[6])) {
        if (realpath($argv[7]) !== realpath(storage_path('framework/testing/disks/local'))) {
            exit(4);
        }
        config(['filesystems.disks.local.root' => $argv[7]]);
        $duLieu['anh'] = [new UploadedFile($argv[6], 'anh.png', test: true)];
    }
    $tin = app(ChatService::class)->gui(TaiKhoan::findOrFail((int) $argv[2]), (int) $argv[3], $duLieu);
    echo json_encode(['ok' => true, 'id' => $tin['id']]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'exception' => get_class($e)]);
    exit(1);
}
