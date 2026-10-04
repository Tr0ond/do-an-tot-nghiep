<?php

use App\Models\KeHoachTap;
use App\Models\LichHenHuanLuyen;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use App\Services\TaiKhoanService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return function (): array {
    $tao = require __DIR__.'/hanh-trinh-fixture.php';
    $f = $tao();
    extract($f);
    $pt->update(['ho_ten' => 'Nguyễn Minh']);
    $hom = CarbonImmutable::now('Asia/Ho_Chi_Minh')->startOfDay();
    $kh2 = app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Lan Phương · Học viên', 'email' => 'lan@pt-dashboard.example.test', 'password' => 'Demo123456!'], TaiKhoan::KHACH_HANG);
    $pc2 = PhanCongHuanLuyenVien::create(['khach_hang_id' => $kh2->hoSoKhachHang->id, 'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'nguoi_phan_cong_id' => $admin->id, 'bat_dau_luc' => now()->subDays(20)]);
    $pt2 = app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'PT riêng tư', 'email' => 'pt2@pt-dashboard.example.test', 'password' => 'Demo123456!'], TaiKhoan::HUAN_LUYEN_VIEN);
    $pcKhac = PhanCongHuanLuyenVien::create(['khach_hang_id' => $khac->hoSoKhachHang->id, 'huan_luyen_vien_id' => $pt2->hoSoHuanLuyenVien->id, 'nguoi_phan_cong_id' => $admin->id, 'bat_dau_luc' => now()->subDays(20)]);
    $cacBan = [];
    foreach ([['NHAP', 'PT', null, 'Tăng sức mạnh · Bản nháp'], ['CHO_DUYET', 'PT', now()->addHours(20), 'Khởi đầu 3 buổi mỗi tuần'], ['CHO_DUYET', 'PT', now()->subMinute(), 'Đề xuất hết hạn'], ['NHAP', 'KHACH_HANG', null, 'Giáo án học viên tự soạn']] as [$tt, $nguon, $han, $ten]) {
        $cacBan[$ten] = KeHoachTap::create(['khach_hang_id' => $kh2->hoSoKhachHang->id, 'huan_luyen_vien_id' => $nguon === 'PT' ? $pt->hoSoHuanLuyenVien->id : null, 'phan_cong_id' => $nguon === 'PT' ? $pc2->id : null,
            'ten_ke_hoach' => $ten, 'nguon_tao' => $nguon, 'so_ngay_tap' => 3, 'trang_thai' => $tt, 'han_duyet' => $han, 'gui_luc' => $tt === 'CHO_DUYET' ? now()->subHours(4) : null, 'client_request_id' => (string) Str::uuid(), 'updated_at' => now()->addSeconds(count($cacBan))]);
    }
    KeHoachTap::create(['khach_hang_id' => $kh2->hoSoKhachHang->id, 'huan_luyen_vien_id' => $pt2->hoSoHuanLuyenVien->id, 'ten_ke_hoach' => 'Nháp PT khác không được đọc', 'nguon_tao' => 'PT', 'so_ngay_tap' => 1, 'trang_thai' => 'NHAP', 'client_request_id' => (string) Str::uuid()]);
    $cacHen = [];
    $taoHen = function ($batDau, $tt, $han = null) use ($kh, $pt, $pc, $don, &$cacHen) {
        $slot = DB::table('khung_gio_huan_luyen_vien')->insertGetId(['huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'bat_dau_luc' => $batDau, 'ket_thuc_luc' => $batDau->addHour(), 'trang_thai' => 'MO']);
        $cacHen[] = LichHenHuanLuyen::create(['khach_hang_id' => $kh->hoSoKhachHang->id, 'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'phan_cong_id' => $pc->id, 'khung_gio_id' => $slot, 'dang_ky_goi_tap_id' => $don->id,
            'client_request_id' => (string) Str::uuid(), 'bat_dau_luc' => $batDau, 'ket_thuc_luc' => $batDau->addHour(), 'trang_thai' => $tt, 'han_xac_nhan_dat_lich' => $han, 'tieu_hao_luc' => $tt === 'HOAN_THANH' ? $batDau->addHour() : null]);
    };
    $taoHen($hom->subDays(2)->addHours(8)->utc(), 'HOAN_THANH');
    $taoHen($hom->subDay()->addHours(8)->utc(), 'VANG_MAT');
    $taoHen($hom->addHours(8)->utc(), 'HOAN_THANH');
    $taoHen($hom->addHours(9)->utc(), 'DA_XAC_NHAN');
    $taoHen($hom->addHours(11)->utc(), 'DA_XAC_NHAN');
    $taoHen($hom->addHours(15)->utc(), 'CHO_XAC_NHAN', now()->addHour());
    $taoHen($hom->addHours(16)->utc(), 'CHO_XAC_NHAN', now()->subMinute());
    $taoHen($hom->addHours(19)->utc(), 'DA_HUY');
    $taoHen($hom->subDay()->addHours(6)->utc(), 'DA_XAC_NHAN');
    foreach ([12, 24 + 13] as $gio) {
        DB::table('khung_gio_huan_luyen_vien')->insert(['huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'bat_dau_luc' => $hom->addHours($gio)->utc(), 'ket_thuc_luc' => $hom->addHours($gio + 1)->utc(), 'trang_thai' => 'MO']);
    }
    $cacHoi = [];
    foreach ([[$pc, $kh], [$pc2, $kh2], [$pcKhac, $khac]] as [$phanCong, $khach]) {
        $hoi = DB::table('hoi_thoai')->insertGetId(['phan_cong_id' => $phanCong->id]);
        $cacHoi[] = $hoi;
        DB::table('tin_nhan')->insert(['hoi_thoai_id' => $hoi, 'nguoi_gui_id' => $khach->id, 'client_message_id' => (string) Str::uuid(), 'noi_dung' => $khach->id === $khac->id ? 'Tin nhắn riêng tư PT khác' : 'Em đã hoàn thành buổi tập hôm nay.', 'created_at' => now()->subMinutes(15)]);
        DB::table('tin_nhan')->insert(['hoi_thoai_id' => $hoi, 'nguoi_gui_id' => $khach->id, 'client_message_id' => (string) Str::uuid(), 'noi_dung' => '', 'created_at' => now()->subMinutes(2)]);
    }

    return [...$f, ...compact('kh2', 'pc2', 'pt2', 'pcKhac', 'cacHen', 'cacBan', 'cacHoi')];
};
