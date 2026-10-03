<?php

use App\Models\BaiTap;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use App\Services\ChatService;
use App\Services\GiaoAnMauService;
use App\Services\KeHoachTapService;
use App\Services\TaiKhoanService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment(['local', 'testing'])) {
    throw new RuntimeException('Chỉ chạy fixture local/testing.');
}
$cfg = config('database.connections.mysql');
$cfg['database'] = null;
$cfg['url'] = null;
config(['database.connections.may_chu_chat_ui' => $cfg]);
$pdo = DB::connection('may_chu_chat_ui')->getPdo();
if (($argv[1] ?? '') === 'list') {
    echo json_encode($pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE 'kiem_tra_chat_ui_%'")->fetchAll(PDO::FETCH_COLUMN));
    exit;
}
if (($argv[1] ?? '') === 'drop') {
    $db = $argv[2] ?? '';
    if (! preg_match('/^kiem_tra_chat_ui_[a-f0-9]{16}$/D', $db)) {
        throw new RuntimeException('Sai database fixture.');
    }
    $pdo->exec('DROP DATABASE `'.$db.'`');
    exit;
}
$db = 'kiem_tra_chat_ui_'.bin2hex(random_bytes(8));
$pdo->exec('CREATE DATABASE `'.$db.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null, 'broadcasting.default' => 'null']);
    DB::purge('mysql');
    if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db) {
        throw new RuntimeException('Sai database.');
    }
    if (Artisan::call('migrate', ['--force' => true]) !== 0) {
        throw new RuntimeException('Migration fixture thất bại.');
    }
    $service = app(TaiKhoanService::class);
    $kh = $service->taoTaiKhoan(['ho_ten' => 'Học viên demo', 'email' => 'kh@chat-ui.example.test', 'password' => 'Demo123456!'], TaiKhoan::KHACH_HANG);
    $pt = $service->taoTaiKhoan(['ho_ten' => 'Huấn luyện viên demo', 'email' => 'pt@chat-ui.example.test', 'password' => 'Demo123456!'], TaiKhoan::HUAN_LUYEN_VIEN);
    $admin = $service->taoTaiKhoan(['ho_ten' => 'Admin demo', 'email' => 'admin@chat-ui.example.test', 'password' => 'Demo123456!'], TaiKhoan::ADMIN);
    if (($argv[1] ?? '') === 'thong-bao') {
        // Chỉ seed vào database QA riêng; không bật sự kiện thông báo nghiệp vụ.
        foreach ([[$kh, '/khach-hang/lich-hen'], [$pt, '/pt/lich-hen'], [$admin, '/admin/lich-hen']] as [$nguoi, $duongDan]) {
            for ($i = 1; $i <= 2; $i++) {
                $nguoi->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'kiem_tra_giao_dien', 'data' => ['tieu_de' => 'Thông báo mẫu — lịch hẹn', 'noi_dung' => 'Đây là dữ liệu kiểm thử giao diện thông báo.', 'duong_dan' => $duongDan], 'read_at' => $i === 1 ? now() : null]);
            }
        }
    }
    $pc = PhanCongHuanLuyenVien::create(['khach_hang_id' => $kh->hoSoKhachHang->id, 'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'nguoi_phan_cong_id' => $admin->id, 'bat_dau_luc' => now(), 'client_request_id' => (string) Str::uuid()]);
    if (in_array($argv[1] ?? '', ['ke-hoach', 'ke-hoach-chung'], true)) {
        $nhom = DB::table('nhom_co')->insertGetId(['ma_nhom_co' => 'demo', 'ten_nhom_co' => 'Toàn thân', 'ten_nguon' => 'full body', 'trang_thai' => 'HOAT_DONG']);
        $cacBai = [];
        foreach (['Chống đẩy', 'Squat', 'Plank'] as $i => $ten) {
            $b = BaiTap::create(['nhom_co_id' => $nhom, 'ten_bai_tap' => $ten, 'huong_dan' => ['vi' => 'Giữ lưng thẳng và kiểm soát nhịp thở.'], 'cac_buoc' => ['vi' => ['Giữ đúng tư thế bắt đầu.', 'Thực hiện chậm theo số lần đã chỉ định.']], 'trang_thai' => 'HOAT_DONG']);
            $cacBai[] = ['bai_tap_id' => $b->id, 'ngay_thu' => $i < 2 ? 1 : 2, 'thu_tu' => $i < 2 ? $i + 1 : 1, 'so_hiep' => 3, 'so_lan_lap' => 12, 'nghi_giay' => 60, 'ghi_chu' => 'Ưu tiên kỹ thuật trước khi tăng mức tạ.'];
        }
        $dichVuMau = app(GiaoAnMauService::class);
        $m = $dichVuMau->taoGiaoAn(['ten_giao_an' => 'Toàn thân — 2 ngày', 'muc_tieu' => 'Xây dựng nền tảng và tăng sức bền', 'so_ngay_tap' => 2, 'bai_tap' => $cacBai, 'client_request_id' => (string) Str::uuid()], $admin->id);
        $dichVuMau->datTrangThai($m->id, ['trang_thai' => 'DA_DUYET', 'updated_at' => $m->updated_at->format('Y-m-d H:i:s.u')], $admin->id);
        if (($argv[1] ?? '') === 'ke-hoach-chung') {
            $dichVu = app(KeHoachTapService::class);
            $body = ['ten_ke_hoach' => 'Tự tập toàn thân', 'muc_tieu' => 'Giáo án tự tạo', 'so_ngay_tap' => 1, 'giao_an_mau_id' => null, 'client_request_id' => (string) Str::uuid(), 'bai_tap' => [[...$cacBai[0], 'muc_ta_kg' => null]]];
            $tuTao = $dichVu->tao($kh, $kh->hoSoKhachHang->id, $body);
            $dichVu->thaoTac($kh, $tuTao->id, 'ap-dung', $tuTao->updated_at->format('Y-m-d H:i:s.u'));
            $ptGiao = $dichVu->tao($pt, $kh->hoSoKhachHang->id, [...$body, 'ten_ke_hoach' => 'Giáo án PT — nền tảng', 'muc_tieu' => 'Đề xuất từ PT', 'client_request_id' => (string) Str::uuid()]);
            $ptGiao = $dichVu->thaoTac($pt, $ptGiao->id, 'gui', $ptGiao->updated_at->format('Y-m-d H:i:s.u'));
            $dichVu->thaoTac($kh, $ptGiao->id, 'xac-nhan', $ptGiao->updated_at->format('Y-m-d H:i:s.u'));
        }
    }
    $hoi = DB::table('hoi_thoai')->insertGetId(['phan_cong_id' => $pc->id, 'created_at' => now(), 'updated_at' => now()]);
    for ($i = 1; $i <= 52; $i++) {
        DB::table('tin_nhan')->insert(['hoi_thoai_id' => $hoi, 'nguoi_gui_id' => $i % 2 ? $kh->id : $pt->id, 'client_message_id' => (string) Str::uuid(), 'noi_dung' => 'Tin lịch sử demo '.$i, 'created_at' => now()->subDays(2)->addMinutes($i), 'updated_at' => now()]);
    }
    foreach ([[$kh, 'Chào anh, mình trao đổi lịch tập tuần này ở đây nhé.'], [$pt, 'Chào bạn! Bạn muốn trao đổi về buổi tập nào?'], [$kh, "Mình muốn xem lại hướng dẫn bài tập.\nCảm ơn anh đã hỗ trợ."], [$pt, "Bạn có thể mở Thư viện bài tập trên header để xem hướng dẫn.\nNếu cần giải thích thêm, cứ nhắn tại đây nhé."]] as [$nguoi, $body]) {
        app(ChatService::class)->gui($nguoi, $hoi, ['noi_dung' => $body, 'client_message_id' => (string) Str::uuid()]);
    }
    echo $db;
} catch (Throwable $loi) {
    DB::disconnect('mysql');
    $pdo->exec('DROP DATABASE `'.$db.'`');
    fwrite(STDERR, 'Không tạo được fixture chat: '.get_class($loi).PHP_EOL);
    exit(1);
}
