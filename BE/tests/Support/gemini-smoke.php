<?php

// Kiểm tra kết nối bằng dữ liệu giả; không đọc hồ sơ/chat/database.
use App\Services\GeminiService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\Event;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment(['local', 'testing']) || ! in_array('--live', $argv, true)) {
    fwrite(STDERR, "Chỉ chạy local/testing với --live khi được cấp key/hạn mức miễn phí.\n");
    exit(1);
}
$http = null;
Event::listen(ResponseReceived::class, function ($e) use (&$http) {
    $http = $e->response->status();
});
try {
    $r = app(GeminiService::class)->traLoi(['goi_tap' => [], 'giao_an_mau' => [], 'bai_tap' => [], 'tai_lieu' => [], 'chinh_sach' => 'KH có thể tự tạo giáo án miễn phí.'], [], 'Tôi có thể tự tạo giáo án không?');
    echo json_encode(['status' => 'ok', 'http' => $http, 'model' => config('chatbot.model'), 'schema_keys' => array_keys($r['tra_loi']), 'input_tokens' => $r['input_tokens'], 'output_tokens' => $r['output_tokens']], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    preg_match('/cURL error (\d+)/', $e->getMessage(), $m);
    echo json_encode(['status' => 'failed', 'http' => $http, 'model' => config('chatbot.model'), 'error_type' => get_class($e), 'curl_errno' => isset($m[1]) ? (int) $m[1] : null]);
    exit(1);
}
