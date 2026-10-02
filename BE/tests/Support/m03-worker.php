<?php

use App\Models\DangKyGoiTap;
use App\Models\TaiKhoan;
use App\Services\MuaGoiService;
use App\Services\PayosService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

// Chỉ worker kiểm thử: không được chạy vào DB ứng dụng.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = $argv[1] ?? '';
if (! preg_match('/^kiem_tra_mua_goi_[a-f0-9]{16}$/D', $db)) {
    exit(2);
}
config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null, 'database.default' => 'mysql', 'payos.client_id' => 'test', 'payos.api_key' => 'test', 'payos.checksum_key' => 'test']);
DB::purge('mysql');
if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db) {
    exit(3);
}
file_put_contents($argv[5], 'ready');
try {
    if ($argv[2] === 'tao') {
        $r = app(MuaGoiService::class)->taoDon(TaiKhoan::findOrFail((int) $argv[3]), ['goi_tap_id' => (int) $argv[4], 'client_request_id' => $argv[6]]);
    } else {
        $don = DangKyGoiTap::findOrFail((int) $argv[3]);
        $data = ['id' => 'link'.$don->id, 'orderCode' => $don->ma_don_payos, 'amount' => $don->gia_snapshot, 'amountPaid' => $don->gia_snapshot, 'status' => 'PAID', 'transactions' => [['amount' => $don->gia_snapshot, 'reference' => 'REF'.$don->id, 'transactionDateTime' => $don->created_at->toIso8601String()]]];
        Http::preventStrayRequests();
        Http::fake(['https://api-merchant.payos.vn/*' => Http::response(['code' => '00', 'data' => $data, 'signature' => app(PayosService::class)->chuKy($data)])]);
        $r = app(MuaGoiService::class)->dongBo($don);
    }
    echo json_encode(['ok' => true, 'id' => $r->id]);
} catch (HttpException $e) {
    echo json_encode(['ok' => false, 'status' => $e->getStatusCode()]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'exception' => get_class($e)]);
    exit(1);
}
