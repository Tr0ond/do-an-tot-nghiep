<?php

namespace Database\Seeders;

use App\Models\BaiTap;
use App\Models\GoiTap;
use App\Models\KetQuaBuoiPt;
use App\Models\LichHenHuanLuyen;
use App\Models\TaiKhoan;
use App\Services\KetQuaBuoiPtService;
use App\Services\LichHenService;
use App\Services\MuaGoiService;
use App\Services\PhanCongService;
use App\Services\TaiKhoanService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class KetQuaBuoiPtDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('Chỉ tạo dữ liệu demo kết quả PT trên môi trường local.');
        }
        $khoa = DB::selectOne("SELECT GET_LOCK('fitforge_demo_ket_qua_pt', 5) AS da_khoa");
        if ((int) $khoa->da_khoa !== 1) {
            throw new RuntimeException('Đang tạo dữ liệu demo. Hãy thử lại sau.');
        }
        $kenhCu = config('broadcasting.default');
        // Demo không phát sự kiện tới các phiên thật đang mở.
        config(['broadcasting.default' => 'null']);
        try {
            DB::transaction(function () {
                $kh = TaiKhoan::where('email', 'kh.ketqua@demo.test')->first();
                $pt = TaiKhoan::where('email', 'pt.ketqua@demo.test')->first();
                if ($kh || $pt) {
                    // Không đặt lại mật khẩu, thời gian hoặc nháp người dùng đã thử.
                    if (! $kh || ! $pt || $kh->vai_tro !== TaiKhoan::KHACH_HANG
                        || $pt->vai_tro !== TaiKhoan::HUAN_LUYEN_VIEN
                        || $kh->ho_ten !== '[DEMO] Khách hàng kết quả PT'
                        || $pt->ho_ten !== '[DEMO] PT ghi kết quả') {
                        throw new RuntimeException('Email demo đã tồn tại nhưng không khớp bộ dữ liệu. Không ghi đè.');
                    }
                    $this->inKetQua($kh, $pt, false);

                    return;
                }
                $admin = TaiKhoan::where('vai_tro', TaiKhoan::ADMIN)->where('trang_thai', TaiKhoan::HOAT_DONG)->orderBy('id')->firstOrFail();
                $goi = GoiTap::dangHienThi()->where('so_buoi_pt', '>=', 8)->orderBy('id')->firstOrFail();
                $bai = BaiTap::dangHienThi()->where('ten_bai_tap', 'like', '%squat%')->orderBy('id')->limit(3)->get();
                if ($bai->count() < 3) {
                    $bai = BaiTap::dangHienThi()->orderBy('id')->limit(3)->get();
                }
                if ($bai->count() < 3) {
                    throw new RuntimeException('Cần ít nhất 3 bài catalog đang hiển thị.');
                }
                $taiKhoan = app(TaiKhoanService::class);
                $kh = $taiKhoan->taoTaiKhoan(['ho_ten' => '[DEMO] Khách hàng kết quả PT', 'email' => 'kh.ketqua@demo.test', 'password' => 'Demo123456!'], TaiKhoan::KHACH_HANG);
                $pt = $taiKhoan->taoTaiKhoan(['ho_ten' => '[DEMO] PT ghi kết quả', 'email' => 'pt.ketqua@demo.test', 'password' => 'Demo123456!'], TaiKhoan::HUAN_LUYEN_VIEN);
                $moc = now()->startOfSecond();
                $don = app(MuaGoiService::class)->taoDon($kh, ['goi_tap_id' => $goi->id, 'client_request_id' => (string) Str::uuid()]);
                // Gói giả để thử UI; không tạo khoản thu, link payOS hoặc thanh toán thật.
                $don->update(['ten_goi_snapshot' => '[DEMO] '.$goi->ten_goi,
                    'trang_thai' => 'DANG_SU_DUNG', 'kich_hoat_luc' => $moc->copy()->subDays(3),
                    'het_han_luc' => $moc->copy()->addDays(27), 'so_buoi_con_lai' => 8]);
                $pc = app(PhanCongService::class)->phanCong($admin, ['khach_hang_id' => $kh->hoSoKhachHang->id,
                    'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'client_request_id' => (string) Str::uuid()]);
                $pc->update(['bat_dau_luc' => $moc->copy()->subDays(3)]);
                $lichService = app(LichHenService::class);
                $ketQuaService = app(KetQuaBuoiPtService::class);
                foreach ([-90, -240, -360, 480] as $i => $phut) {
                    $slot = $lichService->taoKhungGio($pt, ['bat_dau_luc' => $moc->copy()->addHours(8 + $i * 2)->toIso8601String()]);
                    $lich = $lichService->datLich($kh, ['khung_gio_id' => $slot->id, 'client_request_id' => (string) Str::uuid()]);
                    $lichService->thaoTac($pt, $lich->id, 'xac-nhan');
                    // Chỉ dịch giờ các record demo vừa tạo để có buổi đã diễn ra thử ngay.
                    $batDau = $moc->copy()->addMinutes($phut);
                    $ketThuc = $batDau->copy()->addHour();
                    $slot->update(['bat_dau_luc' => $batDau, 'ket_thuc_luc' => $ketThuc, 'created_at' => $batDau->copy()->subDay()]);
                    $lich->update(['bat_dau_luc' => $batDau, 'ket_thuc_luc' => $ketThuc,
                        'created_at' => $batDau->copy()->subHours(8), 'xac_nhan_luc' => $batDau->copy()->subHours(4),
                        'han_xac_nhan_dat_lich' => $batDau->copy()->subHours(6),
                        'han_xac_nhan_hoan_thanh' => $ketThuc->copy()->addHours(24)]);
                    if (! in_array($i, [1, 2], true)) {
                        continue;
                    }
                    $noiDung = ['updated_at' => null, 'ghi_chu' => '[DEMO] '.($i === 1 ? 'Bản nháp để bạn sửa, thêm hiệp và chốt.' : 'Kết quả mẫu đã chốt; chỉ xem, không sửa.'),
                        'nhan_xet' => 'Giữ lưng thẳng và kiểm soát nhịp thở. Dữ liệu giả dùng để thử giao diện.',
                        'bai_tap' => $bai->take($i === 1 ? 2 : 3)->values()->map(fn ($b, $j) => [
                            'bai_tap_id' => $b->id, 'hiep_tap' => [
                                ['so_lan_lap' => 12, 'khoi_luong_kg' => $j === 0 ? null : '10.00', 'nghi_giay' => 60],
                                ['so_lan_lap' => 10, 'khoi_luong_kg' => $j === 0 ? '0.00' : '12.50', 'nghi_giay' => 90],
                            ],
                        ])->all()];
                    $kq = $ketQuaService->luu($pt, $lich->id, $noiDung);
                    if ($i === 2) {
                        $ketQuaService->chot($pt, $lich->id, $kq->updated_at->format('Y-m-d H:i:s.u'));
                        $lichService->thaoTac($pt, $lich->id, 'hoan-thanh');
                    }
                }
                $this->inKetQua($kh, $pt, true);
            });
        } finally {
            config(['broadcasting.default' => $kenhCu]);
            DB::selectOne("SELECT RELEASE_LOCK('fitforge_demo_ket_qua_pt') AS da_mo");
        }
    }

    private function inKetQua(TaiKhoan $kh, TaiKhoan $pt, bool $moi): void
    {
        $lich = LichHenHuanLuyen::where('khach_hang_id', $kh->hoSoKhachHang->id)
            ->where('huan_luyen_vien_id', $pt->hoSoHuanLuyenVien->id)->orderBy('id')->get();
        $this->command?->line(json_encode(['tao_moi' => $moi, 'kh_email' => $kh->email, 'pt_email' => $pt->email,
            'so_buoi_con_lai' => app(MuaGoiService::class)->goiHieuLuc($kh->hoSoKhachHang->id)->value('so_buoi_con_lai'),
            'lich' => $lich->map(function ($l) use ($pt) {
                $kq = KetQuaBuoiPt::where('lich_hen_id', $l->id)->first();

                return ['id' => $l->id, 'trang_thai' => $l->trang_thai,
                    'bat_dau_vn' => $l->bat_dau_luc->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                    'het_han_ghi_vn' => $l->ket_thuc_luc->addHours(24)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                    'ket_qua' => $kq ? ($kq->chot_luc ? 'DA_CHOT' : 'NHAP') : 'CHUA_GHI',
                    'co_the_ghi' => app(KetQuaBuoiPtService::class)->duLieu($pt, $l->id)['co_the_ghi']];
            })->all()], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
