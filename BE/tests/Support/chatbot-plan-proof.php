<?php

// Chỉ tạo bằng tài khoản/catalog giả trong database QA, không ghi log khóa hoặc prompt riêng tư.
use App\Models\BaiTap;
use App\Models\TaiKhoan;
use App\Services\ChatbotService;
use App\Services\GiaoAnAiService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = $argv[1] ?? '';
$cheDo = $argv[2] ?? '';
if (! app()->environment(['local', 'testing']) || ! preg_match('/^kiem_tra_chat_ui_[a-f0-9]{16}$/D', $db) || ! in_array($cheDo, ['--live', '--fake'], true)) {
    throw new RuntimeException('Cần database QA riêng và --live hoặc --fake.');
}
config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null]);
DB::purge('mysql');
if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db) {
    throw new RuntimeException('Sai database.');
}
$kh = TaiKhoan::where('email', 'kh@chat-ui.example.test')->where('vai_tro', TaiKhoan::KHACH_HANG)->sole();
$http = null;
$schemaKeys = null;
$kiemTraDeXuat = null;
Event::listen(ResponseReceived::class, function ($e) use (&$http, &$schemaKeys, &$kiemTraDeXuat) {
    $http = $e->response->status();
    $raw = $e->response->json('candidates.0.content.parts.0.text');
    $parsed = is_string($raw) ? json_decode($raw, true) : null;
    $schemaKeys = is_array($parsed) ? array_keys($parsed) : null;
    if (is_array($parsed)) {
        $kiemTraDeXuat = ['so_buoi' => count($parsed['giao_an_de_xuat']['buoi_tap'] ?? []), 'so_id_nguon' => array_map(fn ($f) => count($parsed[$f] ?? []), ['goi_tap_ids', 'giao_an_mau_ids', 'bai_tap_ids', 'nguon_tai_lieu_ids'])];
        try {
            $s = app(GiaoAnAiService::class);
            $s->kiemTra($parsed['giao_an_de_xuat'] ?? null, ['buoi_moi_tuan' => 3, 'so_tuan' => 4, 'bai_moi_buoi' => 4], $s->ungVien('tại nhà không có tạ'));
            $kiemTraDeXuat['hop_le'] = true;
        } catch (ValidationException $e) {
            $kiemTraDeXuat['loi_truong'] = array_keys($e->errors());
        } catch (Throwable $e) {
            $kiemTraDeXuat['ma_loi'] = $e instanceof RuntimeException ? $e->getMessage() : get_class($e);
        }
    }
});
if ($cheDo === '--fake') {
    config(['chatbot.key' => 'fake-key']);
    $bai = BaiTap::where('trang_thai', 'HOAT_DONG')->orderBy('id')->limit(4)->get()->map(fn ($b) => ['id' => $b->id, 'hiep' => 3, 'lan' => 12, 'nghi' => 60])->all();
    if (count($bai) !== 4) {
        throw new RuntimeException('Fixture cần 4 bài.');
    }
    $d = ['noi_dung' => 'Đề xuất giáo án cơ bản với các bài hiện có trong thư viện.', 'goi_tap_ids' => [], 'giao_an_mau_ids' => [], 'bai_tap_ids' => [], 'nguon_tai_lieu_ids' => [],
        'giao_an_de_xuat' => ['ten_ke_hoach' => 'Tập tại nhà — 4 tuần (QA giả lập)', 'muc_tieu' => 'Xây dựng nền tảng tập luyện', 'buoi_tap' => array_fill(0, 12, ['ghi_chu' => 'Tập chậm, nghỉ giữa các buổi.', 'bai_tap' => $bai])]];
    Http::preventStrayRequests();
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode($d, JSON_UNESCAPED_UNICODE)]]]]]])]);
}
$s = app(ChatbotService::class);
$h = $s->tao($kh->hoSoKhachHang->id, (string) Str::uuid());
try {
    $r = $s->gui($kh, $h->id, ['client_request_id' => (string) Str::uuid(), 'noi_dung' => 'Tạo cho tôi giáo án 3 buổi/tuần trong 4 tuần, mỗi buổi 4 bài. Tôi mới bắt đầu, tập tại nhà không có tạ, mục tiêu rèn sức bền.', 'dung_du_lieu_ca_nhan' => false]);
    $k = $r['tin_nhan'][1]['nguon_da_kiem_tra']['giao_an_da_tao'] ?? null;
    echo json_encode(['che_do' => $cheDo, 'status' => 'ok', 'http' => $http, 'schema_keys' => $schemaKeys, 'hoi_thoai_id' => $h->id, 'giao_an_id' => $k['id'] ?? null, 'so_buoi' => count($k['buoi_tap'] ?? []), 'so_bai' => array_sum(array_map(fn ($x) => count($x['bai_tap']), $k['buoi_tap'] ?? []))], JSON_UNESCAPED_UNICODE).PHP_EOL;
} catch (Throwable $e) {
    echo json_encode(['che_do' => $cheDo, 'status' => 'failed', 'http' => $http, 'schema_keys' => $schemaKeys, 'kiem_tra_de_xuat' => $kiemTraDeXuat, 'error_type' => get_class($e), 'hoi_thoai_id' => $h->id], JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit(1);
}
