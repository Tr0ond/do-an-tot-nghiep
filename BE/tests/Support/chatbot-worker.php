<?php

use App\Models\TaiKhoan;
use App\Services\ChatbotService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = $argv[1] ?? '';
if (! preg_match('/^kiem_tra_chatbot_[a-f0-9]{16}$/D', $db)) {
    exit(2);
}
config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null, 'chatbot.key' => 'fake-key']);
DB::purge('mysql');
if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db) {
    exit(3);
}
$d = json_decode(file_get_contents($argv[2]), true);
Http::preventStrayRequests();
Http::fake(function () {
    usleep(500000);

    return Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode(['noi_dung' => 'Bạn có thể tự tạo giáo án.', 'goi_tap_ids' => [], 'giao_an_mau_ids' => [], 'bai_tap_ids' => [], 'nguon_tai_lieu_ids' => []])]]]]]]);
});
try {
    app(ChatbotService::class)->gui(TaiKhoan::findOrFail($d['nguoi_id']), $d['hoi_id'], $d['body']);
    echo json_encode(['status' => 200]);
} catch (HttpExceptionInterface $e) {
    echo json_encode(['status' => $e->getStatusCode()]);
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e));
    exit(1);
}
