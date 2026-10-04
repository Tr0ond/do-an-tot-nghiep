<?php

use App\Models\BaiTap;
use App\Models\DangKyGoiTap;
use App\Models\GoiTap;
use App\Models\KeHoachTap;
use App\Models\LichTap;
use App\Models\NhomCo;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\PhienTap;
use App\Models\TaiKhoan;
use App\Services\ChiSoCoTheService;
use App\Services\TaiKhoanService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Dữ liệu giả dùng chung cho kiểm thử API và giao diện trong database riêng.
return function (): array {
    $hom = CarbonImmutable::now('Asia/Ho_Chi_Minh')->startOfDay();
    $tao = fn ($ten, $email, $vaiTro) => app(TaiKhoanService::class)->taoTaiKhoan([
        'ho_ten' => $ten, 'email' => $email.'@hanh-trinh.example.test', 'password' => 'Demo123456!',
    ], $vaiTro);
    $kh = $tao('Học viên kiểm thử', 'kh', TaiKhoan::KHACH_HANG);
    $khac = $tao('Học viên riêng tư', 'khac', TaiKhoan::KHACH_HANG);
    $pt = $tao('Minh Anh · PT kiểm thử', 'pt', TaiKhoan::HUAN_LUYEN_VIEN);
    $admin = $tao('Admin kiểm thử', 'admin', TaiKhoan::ADMIN);
    $pt->hoSoHuanLuyenVien()->update(['chuyen_mon' => 'Thể lực và tập luyện sức mạnh']);
    $pc = PhanCongHuanLuyenVien::create(['khach_hang_id' => $kh->hoSoKhachHang->id,
        'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'nguoi_phan_cong_id' => $admin->id,
        'bat_dau_luc' => now()->subDays(60)]);
    $nhom = NhomCo::create(['ma_nhom_co' => 'qa', 'ten_nhom_co' => 'Cơ toàn thân', 'ten_nguon' => 'QA', 'trang_thai' => 'HOAT_DONG']);
    $bai = BaiTap::create(['nhom_co_id' => $nhom->id, 'ten_bai_tap' => 'Squat', 'trang_thai' => 'HOAT_DONG']);
    $keHoach = KeHoachTap::create(['khach_hang_id' => $kh->hoSoKhachHang->id, 'ten_ke_hoach' => 'Hành trình tăng sức bền',
        'muc_tieu' => 'Xây dựng thói quen tập luyện đều đặn và cải thiện thể lực.', 'nguon_tao' => 'KHACH_HANG',
        'so_ngay_tap' => 3, 'trang_thai' => 'DANG_AP_DUNG', 'client_request_id' => (string) Str::uuid()]);
    for ($i = 1; $i <= 2; $i++) {
        $keHoach->cacBaiTap()->create(['bai_tap_id' => $bai->id, 'ngay_thu' => 1, 'thu_tu' => $i,
            'ten_bai_tap_snapshot' => 'Squat', 'noi_dung_snapshot' => ['ten_bai_tap' => 'Squat'], 'so_hiep' => 3, 'so_lan_lap' => 12, 'nghi_giay' => 60]);
    }
    $lich = [];
    foreach ([[-7, 'HOAN_THANH'], [-4, 'HOAN_THANH'], [-3, 'DA_HUY'], [-2, 'HOAN_THANH'], [-1, 'DA_LEN_LICH'], [0, 'DANG_TAP'], [1, 'DA_LEN_LICH'], [7, 'DA_LEN_LICH']] as [$cach, $tt]) {
        $l = LichTap::create(['khach_hang_id' => $kh->hoSoKhachHang->id, 'ke_hoach_tap_id' => $keHoach->id,
            'ngay_tap' => $hom->addDays($cach)->toDateString(), 'ngay_thu' => 1, 'trang_thai' => $tt]);
        $lich[$cach] = $l;
        if (in_array($tt, ['HOAN_THANH', 'DANG_TAP'], true)) {
            $p = PhienTap::create(['khach_hang_id' => $kh->hoSoKhachHang->id, 'lich_tap_id' => $l->id, 'trang_thai' => $tt,
                'bat_dau_luc' => $hom->addDays($cach)->addHours(8)->utc(), 'hoan_thanh_luc' => $tt === 'HOAN_THANH' ? $hom->addDays($cach)->addHours(9)->utc() : null]);
            for ($i = 1; $i <= 2; $i++) {
                $bt = $p->cacBaiTap()->create(['bai_tap_trong_ke_hoach_id' => $keHoach->cacBaiTap()->where('thu_tu', $i)->value('id'), 'bai_tap_id' => $bai->id, 'thu_tu' => $i, 'ten_bai_tap_snapshot' => 'Squat', 'noi_dung_snapshot' => ['du_kien' => ['so_hiep' => 3, 'so_lan_lap' => 12, 'nghi_giay' => 60]]]);
                if ($tt === 'HOAN_THANH' || $i === 1) {
                    $bt->cacHiep()->create(['thu_tu' => 1, 'so_lan_lap' => 12, 'khoi_luong_kg' => 10, 'nghi_giay' => 60]);
                }
            }
        }
    }
    $rieng = KeHoachTap::create(['khach_hang_id' => $khac->hoSoKhachHang->id, 'ten_ke_hoach' => 'Giáo án riêng tư',
        'nguon_tao' => 'KHACH_HANG', 'so_ngay_tap' => 1, 'trang_thai' => 'DANG_AP_DUNG', 'client_request_id' => (string) Str::uuid()]);
    LichTap::create(['khach_hang_id' => $khac->hoSoKhachHang->id, 'ke_hoach_tap_id' => $rieng->id,
        'ngay_tap' => $hom->toDateString(), 'ngay_thu' => 1, 'trang_thai' => 'DA_LEN_LICH']);
    $goi = GoiTap::create(['ten_goi' => 'Tên catalog đã đổi', 'gia' => 999999, 'co_chatbot' => true,
        'so_luot_chatbot_moi_ngay' => 10, 'so_buoi_pt' => 10, 'thoi_han_ngay' => 30, 'trang_thai' => 'NGUNG_SU_DUNG']);
    $don = DangKyGoiTap::create(['khach_hang_id' => $kh->hoSoKhachHang->id, 'goi_tap_id' => $goi->id,
        'client_request_id' => (string) Str::uuid(), 'ma_don_payos' => random_int(10000000, 999999999),
        'ten_goi_snapshot' => 'Đồng hành cùng PT', 'gia_snapshot' => 100000, 'co_chatbot_snapshot' => true,
        'so_luot_chatbot_moi_ngay_snapshot' => 10, 'so_buoi_pt_snapshot' => 10, 'thoi_han_ngay_snapshot' => 30,
        'so_buoi_con_lai' => 6, 'trang_thai' => 'DANG_SU_DUNG', 'kich_hoat_luc' => now()->subDays(20), 'het_han_luc' => now()->addDays(10)]);
    $taoHen = function ($cach, $tt, $han) use ($hom, $kh, $pt, $pc, $don) {
        $batDau = $hom->addDays($cach)->addHours(18)->utc();
        $slot = DB::table('khung_gio_huan_luyen_vien')->insertGetId(['huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id,
            'bat_dau_luc' => $batDau, 'ket_thuc_luc' => $batDau->addHour(), 'trang_thai' => 'MO']);

        return DB::table('lich_hen_huan_luyen')->insertGetId(['khach_hang_id' => $kh->hoSoKhachHang->id,
            'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'phan_cong_id' => $pc->id, 'khung_gio_id' => $slot,
            'dang_ky_goi_tap_id' => $don->id, 'client_request_id' => (string) Str::uuid(), 'bat_dau_luc' => $batDau,
            'ket_thuc_luc' => $batDau->addHour(), 'trang_thai' => $tt, 'han_xac_nhan_dat_lich' => $han]);
    };
    $hen = $taoHen(2, 'DA_XAC_NHAN', null);
    $taoHen(3, 'CHO_XAC_NHAN', now()->subMinute());
    $taoHen(4, 'DA_HUY', null);
    $taoHen(7, 'DA_XAC_NHAN', null);
    $hoiThoai = DB::table('hoi_thoai_tro_ly')->insertGetId(['khach_hang_id' => $kh->hoSoKhachHang->id, 'tieu_de' => 'Nội dung chat riêng tư', 'trang_thai' => 'HOAT_DONG']);
    foreach (['THANH_CONG', 'THANH_CONG', 'DANG_XU_LY', 'LOI'] as $tt) {
        DB::table('yeu_cau_tro_ly')->insert(['khach_hang_id' => $kh->hoSoKhachHang->id, 'hoi_thoai_tro_ly_id' => $hoiThoai,
            'dang_ky_goi_tap_id' => $don->id, 'client_request_id' => (string) Str::uuid(), 'ngay_han_muc' => $hom->toDateString(),
            'trang_thai' => $tt, 'giu_luot_den' => $tt === 'DANG_XU_LY' ? now()->addMinutes(2) : null]);
    }
    foreach ([[-6, 70], [0, 69]] as [$cach, $kg]) {
        app(ChiSoCoTheService::class)->luu($kh, ['ngay_ghi' => $hom->addDays($cach)->toDateString(), 'can_nang_kg' => $kg, 'chieu_cao_cm' => 175, 'ghi_chu' => null]);
    }

    return compact('kh', 'khac', 'pt', 'admin', 'pc', 'keHoach', 'lich', 'don', 'hen');
};
