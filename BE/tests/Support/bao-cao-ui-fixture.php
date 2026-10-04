<?php

use App\Models\DangKyGoiTap;
use App\Models\GoiTap;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use App\Models\ThanhToan;
use App\Services\TaiKhoanService;
use Carbon\CarbonImmutable;
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
config(['database.connections.may_chu_bao_cao_ui' => $cfg]);
$pdo = DB::connection('may_chu_bao_cao_ui')->getPdo();
if (($argv[1] ?? '') === 'drop') {
    $db = $argv[2] ?? '';
    if (! preg_match('/^kiem_tra_bao_cao_ui_[a-f0-9]{16}$/D', $db)) {
        throw new RuntimeException('Sai database fixture.');
    }
    $pdo->exec('DROP DATABASE `'.$db.'`');
    exit;
}
$db = 'kiem_tra_bao_cao_ui_'.bin2hex(random_bytes(8));
$pdo->exec('CREATE DATABASE `'.$db.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    config(['database.connections.mysql.database' => $db, 'database.connections.mysql.url' => null, 'broadcasting.default' => 'null']);
    DB::purge('mysql');
    if (DB::selectOne('SELECT DATABASE() AS ten')->ten !== $db || Artisan::call('migrate', ['--force' => true]) !== 0) {
        throw new RuntimeException('Không khởi tạo được database QA.');
    }
    $taiKhoan = app(TaiKhoanService::class);
    $tao = fn ($ten, $email, $vaiTro) => $taiKhoan->taoTaiKhoan(['ho_ten' => $ten, 'email' => $email.'@bao-cao-ui.example.test', 'password' => 'Demo123456!'], $vaiTro);
    $admin = $tao('Admin kiểm thử', 'admin', TaiKhoan::ADMIN);
    $pts = [];
    for ($i = 1; $i <= 21; $i++) {
        $pts[] = $tao('Huấn luyện viên '.str_pad($i, 2, '0', STR_PAD_LEFT), 'pt'.$i, TaiKhoan::HUAN_LUYEN_VIEN);
    }
    $goi = GoiTap::create(['ten_goi' => 'Gói mới trong catalog', 'gia' => 990000, 'co_chatbot' => true, 'so_luot_chatbot_moi_ngay' => 10, 'so_buoi_pt' => 10, 'thoi_han_ngay' => 30, 'trang_thai' => 'HOAT_DONG']);
    $homNay = CarbonImmutable::today('Asia/Ho_Chi_Minh');
    $taoDon = function ($i, $ten, $gia, $state = 'CHO_THANH_TOAN') use ($tao, $goi, $homNay) {
        $kh = $tao('Học viên kiểm thử '.$i, 'kh'.$i, TaiKhoan::KHACH_HANG);

        return DangKyGoiTap::create(['khach_hang_id' => $kh->hoSoKhachHang->id, 'goi_tap_id' => $goi->id, 'client_request_id' => (string) Str::uuid(), 'ma_don_payos' => 9000000 + $i, 'ten_goi_snapshot' => $ten, 'gia_snapshot' => $gia, 'co_chatbot_snapshot' => true, 'so_luot_chatbot_moi_ngay_snapshot' => 10, 'so_buoi_pt_snapshot' => 10, 'so_buoi_con_lai' => 9, 'thoi_han_ngay_snapshot' => 30, 'trang_thai' => $state, 'kich_hoat_luc' => $state === 'DANG_SU_DUNG' ? $homNay->subDays(10)->utc() : null, 'het_han_luc' => $state === 'DANG_SU_DUNG' ? $homNay->addDays(20)->utc() : null, 'created_at' => $homNay->subDays(12)->utc()]);
    };
    $nhan = fn ($don, $tien, $ngay, $them = []) => ThanhToan::create([...['dang_ky_goi_tap_id' => $don->id, 'ma_giao_dich' => 'QA-'.Str::uuid(), 'so_tien' => $tien, 'thanh_toan_luc' => $homNay->subDays($ngay)->addHours(9)->utc(), 'xac_minh_luc' => now(), 'trang_thai' => 'DA_XAC_MINH'], ...$them]);
    $don1 = $taoDon(1, 'Đồng hành cùng PT', 500000, 'DANG_SU_DUNG');
    $nhan($don1, 200000, 12);
    $nhan($don1, 300000, 10);
    $don2 = $taoDon(2, 'Tr0ond AI & PT', 300000, 'DANG_SU_DUNG');
    $nhan($don2, 300000, 8);
    $nhan($taoDon(3, 'Đồng hành cùng PT', 500000, 'CAN_DOI_SOAT'), 50000, 3, ['trang_thai' => 'CAN_DOI_SOAT']);
    $nhan($taoDon(4, 'Gói cũ cần hoàn', 120000, 'DA_HUY'), 120000, 40, ['trang_thai' => 'DA_HOAN_TIEN', 'so_tien_hoan' => 120000, 'hoan_tien_luc' => $homNay->subDays(1)->addHours(9)->utc()]);
    $taoDon(5, 'Đơn chưa thanh toán', 900000);
    $choPt = $taoDon(6, 'Tr0ond AI & PT', 300000, 'DANG_SU_DUNG');
    $nhan($choPt, 300000, 0);
    foreach ([$don1, $don2] as $i => $don) {
        $pt = $pts[$i]->hoSoHuanLuyenVien->id;
        $pc = PhanCongHuanLuyenVien::create(['khach_hang_id' => $don->khach_hang_id, 'huan_luyen_vien_id' => $pt, 'nguoi_phan_cong_id' => $admin->id, 'bat_dau_luc' => $homNay->subDays(9)->utc(), 'client_request_id' => (string) Str::uuid()]);
        $start = $homNay->subDays(5)->addHours(14)->utc();
        $slot = DB::table('khung_gio_huan_luyen_vien')->insertGetId(['huan_luyen_vien_id' => $pt, 'bat_dau_luc' => $start, 'ket_thuc_luc' => $start->addHour(), 'trang_thai' => 'HOAT_DONG']);
        DB::table('lich_hen_huan_luyen')->insert(['khach_hang_id' => $don->khach_hang_id, 'huan_luyen_vien_id' => $pt, 'phan_cong_id' => $pc->id, 'khung_gio_id' => $slot, 'dang_ky_goi_tap_id' => $don->id, 'client_request_id' => (string) Str::uuid(), 'bat_dau_luc' => $start, 'ket_thuc_luc' => $start->addHour(), 'trang_thai' => 'HOAN_THANH', 'tieu_hao_luc' => $start->addHour()]);
        $start = $homNay->addHours(10 + $i * 3)->utc();
        $slot = DB::table('khung_gio_huan_luyen_vien')->insertGetId(['huan_luyen_vien_id' => $pt, 'bat_dau_luc' => $start, 'ket_thuc_luc' => $start->addHour(), 'trang_thai' => 'HOAT_DONG']);
        DB::table('lich_hen_huan_luyen')->insert(['khach_hang_id' => $don->khach_hang_id, 'huan_luyen_vien_id' => $pt, 'phan_cong_id' => $pc->id, 'khung_gio_id' => $slot, 'dang_ky_goi_tap_id' => $don->id, 'client_request_id' => (string) Str::uuid(), 'bat_dau_luc' => $start, 'ket_thuc_luc' => $start->addHour(), 'trang_thai' => $i ? 'CHO_XAC_NHAN' : 'DA_XAC_NHAN', 'han_xac_nhan_dat_lich' => $start->subHours(2)]);
    }
    $hoiThoai = DB::table('hoi_thoai_tro_ly')->insertGetId(['khach_hang_id' => $don1->khach_hang_id, 'trang_thai' => 'HOAT_DONG', 'tieu_de' => 'QA riêng tư']);
    foreach ([3, 6, 2, 9, 4, 7, 10] as $i => $soLuong) {
        for ($j = 0; $j < $soLuong; $j++) {
            DB::table('yeu_cau_tro_ly')->insert(['khach_hang_id' => $don1->khach_hang_id, 'hoi_thoai_tro_ly_id' => $hoiThoai, 'dang_ky_goi_tap_id' => $don1->id, 'client_request_id' => (string) Str::uuid(), 'ngay_han_muc' => $homNay->subDays(6 - $i)->toDateString(), 'trang_thai' => $j === 0 ? 'LOI' : 'THANH_CONG', 'input_tokens' => 120, 'output_tokens' => 250]);
        }
    }
    echo $db;
} catch (Throwable $loi) {
    DB::disconnect('mysql');
    $pdo->exec('DROP DATABASE `'.$db.'`');
    fwrite(STDERR, 'Không tạo được fixture báo cáo: '.get_class($loi).PHP_EOL);
    exit(1);
}
