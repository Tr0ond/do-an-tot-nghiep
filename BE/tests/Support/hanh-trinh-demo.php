<?php

use App\Models\BaiTap;
use App\Models\GoiTap;
use App\Services\ChiSoCoTheService;
use App\Services\KeHoachTapService;
use App\Services\MuaGoiService;
use App\Services\NhatKyTapService;
use App\Services\PayosService;
use App\Services\PhanCongService;
use App\Services\TaiKhoanService;
use Database\Seeders\BaiTapSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment(['local', 'testing'])) {
    throw new RuntimeException('Chỉ dùng demo local/testing.');
}
$cfg = config('database.connections.mysql');
$cfg['database'] = null;
$cfg['url'] = null;
config(['database.connections.may_chu_demo' => $cfg]);
$pdo = DB::connection('may_chu_demo')->getPdo();
if (($argv[1] ?? '') === 'drop') {
    $db = $argv[2] ?? '';
    if (! preg_match('/^kiem_tra_hanh_trinh_demo_[a-f0-9]{16}$/D', $db)) {
        throw new RuntimeException('Chỉ xóa đúng database demo.');
    }
    $pdo->exec('DROP DATABASE `'.$db.'`');
    exit;
}
$db = 'kiem_tra_hanh_trinh_demo_'.bin2hex(random_bytes(8));
$pdo->exec('CREATE DATABASE `'.$db.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null,
        'broadcasting.default' => 'null', 'payos.client_id' => 'qa', 'payos.api_key' => 'qa', 'payos.checksum_key' => 'qa',
        'payos.api_url' => 'https://api-merchant.payos.vn']);
    DB::purge('mysql');
    if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db || Artisan::call('migrate', ['--force' => true]) !== 0) {
        throw new RuntimeException('Không tạo được schema demo.');
    }
    Http::swap(new Factory);
    Http::preventStrayRequests();
    (new BaiTapSeeder)->run();
    $tao = fn ($email, $vai) => app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Demo '.$email,
        'email' => $email.'@hanh-trinh.example.test', 'password' => 'Demo123456!'], $vai);
    $admin = $tao('admin', 'ADMIN');
    $pt = $tao('pt', 'HUAN_LUYEN_VIEN');
    $tao('pt2', 'HUAN_LUYEN_VIEN');
    $kh = $tao('kh', 'KHACH_HANG');
    $tao('tu-tap', 'KHACH_HANG');
    $cho = $tao('cho-pt', 'KHACH_HANG');
    $goi = GoiTap::create(['ten_goi' => 'Gói demo PT & AI', 'gia' => 99000, 'co_chatbot' => true,
        'so_luot_chatbot_moi_ngay' => 10, 'so_buoi_pt' => 4, 'thoi_han_ngay' => 30, 'trang_thai' => 'HOAT_DONG']);
    // Mọi khoản tiền ở đây là giả lập; đi qua xác minh/transaction thật của dịch vụ.
    foreach ([$kh, $cho] as $nguoi) {
        $don = app(MuaGoiService::class)->taoDon($nguoi, ['goi_tap_id' => $goi->id, 'client_request_id' => (string) Str::uuid()]);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        $data = ['id' => 'demo-'.$don->id, 'orderCode' => $don->ma_don_payos, 'amount' => 99000, 'amountPaid' => 99000,
            'status' => 'PAID', 'transactions' => [['amount' => 99000, 'reference' => 'DEMO-'.$don->id, 'transactionDateTime' => now()->toIso8601String()]]];
        Http::fake(['https://api-merchant.payos.vn/v2/payment-requests/*' => Http::response([
            'code' => '00', 'data' => $data, 'signature' => app(PayosService::class)->chuKy($data)])]);
        app(MuaGoiService::class)->dongBo($don);
    }
    app(PhanCongService::class)->phanCong($admin, ['khach_hang_id' => $kh->hoSoKhachHang->id,
        'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'phan_cong_hien_tai_id' => null, 'client_request_id' => (string) Str::uuid()]);
    $bai = BaiTap::where('trang_thai', 'HOAT_DONG')->where('dung_cu', 'Trọng lượng cơ thể')->orderBy('id')->limit(4)->get();
    $nd = ['ten_ke_hoach' => 'Giáo án demo toàn thân', 'muc_tieu' => 'Kiểm thử tập luyện và ghi nhật ký', 'so_ngay_tap' => 1,
        'giao_an_mau_id' => null, 'client_request_id' => (string) Str::uuid(), 'bai_tap' => $bai->map(fn ($b, $i) => ['bai_tap_id' => $b->id,
            'ngay_thu' => 1, 'thu_tu' => $i + 1, 'so_hiep' => 3, 'so_lan_lap' => 12, 'nghi_giay' => 60, 'ghi_chu' => null, 'muc_ta_kg' => null])->all()];
    $s = app(KeHoachTapService::class);
    $k = $s->tao($pt, $kh->hoSoKhachHang->id, $nd);
    $k = $s->thaoTac($pt, $k->id, 'gui', $k->updated_at->format('Y-m-d H:i:s.u'));
    $k = $s->thaoTac($kh, $k->id, 'xac-nhan', $k->updated_at->format('Y-m-d H:i:s.u'));
    $l = app(NhatKyTapService::class)->tao($kh, $kh->hoSoKhachHang->id, ['ke_hoach_tap_id' => $k->id, 'ngay_thu' => 1,
        'ngay_tap' => now('Asia/Ho_Chi_Minh')->toDateString(), 'client_request_id' => (string) Str::uuid()]);
    foreach ([[-7, 70], [0, 69]] as [$cach, $kg]) {
        app(ChiSoCoTheService::class)->luu($kh, ['ngay_ghi' => now('Asia/Ho_Chi_Minh')->addDays($cach)->toDateString(),
            'can_nang_kg' => $kg, 'chieu_cao_cm' => 175, 'ghi_chu' => null]);
    }
    echo json_encode(['database' => $db, 'khach_id' => $kh->hoSoKhachHang->id, 'lich_tap_id' => $l->id,
        'giao_an_id' => $k->id, 'thanh_toan' => 'GIA_LAP', 'catalog' => BaiTap::count()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    DB::disconnect('mysql');
    $pdo->exec('DROP DATABASE `'.$db.'`');
    fwrite(STDERR, 'Không tạo được demo: '.get_class($e).PHP_EOL);
    exit(1);
}
