<?php

// Live eval dùng duy nhất database fixture riêng; không chạy trong PHPUnit mặc định.
use App\Models\DangKyGoiTap;
use App\Models\TaiKhoan;
use App\Models\YeuCauTroLy;
use App\Services\ChatbotService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = $argv[1] ?? '';
if (! app()->environment(['local', 'testing']) || ! preg_match('/^kiem_tra_chat_ui_[a-f0-9]{16}$/D', $db) || ! in_array('--live', $argv, true)) {
    throw new RuntimeException('Cần fixture QA và --live với key/hạn mức miễn phí được cấp.');
}
config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null]);
DB::purge('mysql');
if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db) {
    throw new RuntimeException('Sai fixture.');
}
$kh = TaiKhoan::where('email', 'kh@chat-ui.example.test')->firstOrFail();
DangKyGoiTap::where('khach_hang_id', $kh->hoSoKhachHang->id)->update(['so_luot_chatbot_moi_ngay_snapshot' => 60]);
$cases = json_decode(file_get_contents(base_path('../docs/verification/m08-cau-hoi.json')), true, 16, JSON_THROW_ON_ERROR);
$cases = array_slice($cases, 0, 40);
$report = ['ngay' => now()->toIso8601String(), 'model' => config('chatbot.model'), 'prompt_version' => config('chatbot.phien_ban_prompt'), 'du_lieu' => 'Chỉ tài khoản/catalog/FAQ giả trong fixture QA; không dùng dữ liệu KH thật.', 'cham_noi_dung' => 'Cần chấm theo rubric riêng; thành công API/schema không chứng minh nội dung đúng.', 'ket_qua' => []];
$http = null;
Event::listen(ResponseReceived::class, function ($e) use (&$http) {
    $http = $e->response->status();
});
$s = app(ChatbotService::class);
foreach ($cases as $i => $c) {
    $http = null;
    $uuid = (string) Str::uuid();
    $hoi = $s->tao($kh->hoSoKhachHang->id, (string) Str::uuid());
    $start = microtime(true);
    try {
        $r = $s->gui($kh, $hoi->id, ['client_request_id' => $uuid, 'noi_dung' => $c['cau_hoi'], 'dung_du_lieu_ca_nhan' => false]);
        $t = $r['tin_nhan']->firstWhere('vai_tro', 'ASSISTANT');
        $kq = ['status' => 'THANH_CONG', 'tra_loi' => $t->noi_dung, 'nguon' => $t->nguon_da_kiem_tra];
    } catch (Throwable $e) {
        $kq = ['status' => 'LOI', 'http' => $http, 'da_tru_luot' => YeuCauTroLy::where('client_request_id', $uuid)->where('trang_thai', 'THANH_CONG')->exists()];
    }
    $report['ket_qua'][] = [...$c, ...$kq, 'do_tre_ms' => (int) ((microtime(true) - $start) * 1000)];
    file_put_contents(base_path('../docs/verification/m08-live-eval.json'), json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo ($i + 1).'/40 '.$kq['status'].PHP_EOL;
    // Không đổi sang model/dự án trả phí và không retry khi provider hết quota.
    if ($http === 429 || $http === 401 || $http === 403) {
        break;
    }
}
