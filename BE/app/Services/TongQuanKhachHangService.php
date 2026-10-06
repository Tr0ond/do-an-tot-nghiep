<?php

namespace App\Services;

use App\Models\DangKyGoiTap;
use App\Models\KeHoachTap;
use App\Models\LichHenHuanLuyen;
use App\Models\LichTap;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class TongQuanKhachHangService
{
    public function doc(TaiKhoan $nguoi, int $soNgay): array
    {
        abort_unless($nguoi->vai_tro === TaiKhoan::KHACH_HANG, 403);

        return DB::transaction(function () use ($nguoi, $soNgay) {
            $khachId = $nguoi->hoSoKhachHang->id;
            // Đồng bộ với đổi PT/giáo án; tất cả dữ liệu đều thuộc KH từ session.
            app(NhatKyTapService::class)->khoaKhach($nguoi, $khachId);
            $hom = CarbonImmutable::now('Asia/Ho_Chi_Minh');
            $ngay = $hom->toDateString();
            $moc = now()->format('Y-m-d H:i:s.u');
            $tu = $hom->subDays($soNgay - 1)->toDateString();
            $denTuan = $hom->addDays(6)->toDateString();
            $q = LichTap::where('khach_hang_id', $khachId);
            $hoanThanh = (clone $q)->where('trang_thai', 'HOAN_THANH')->whereHas('phien', fn ($p) => $p->where('trang_thai', 'HOAN_THANH'));
            $thang = [(string) $hom->startOfMonth()->toDateString(), $ngay];
            $soBuoi = (clone $hoanThanh)->whereBetween('ngay_tap', $thang)->count();
            $daDen = (clone $q)->whereBetween('ngay_tap', $thang)->whereIn('trang_thai', ['DA_LEN_LICH', 'DANG_TAP', 'HOAN_THANH'])->count();
            $lichHomNay = (clone $q)->where('ngay_tap', $ngay)->whereIn('trang_thai', ['DA_LEN_LICH', 'DANG_TAP'])
                ->with('keHoach', 'phien.cacBaiTap.cacHiep')->orderByRaw("CASE WHEN trang_thai = 'DANG_TAP' THEN 0 ELSE 1 END")->orderBy('id')->first();
            $keHoach = KeHoachTap::where('khach_hang_id', $khachId)->where('trang_thai', 'DANG_AP_DUNG')->withCount('cacBaiTap')->first();
            $tuan = (clone $q)->whereBetween('ngay_tap', [$hom->startOfWeek()->toDateString(), $hom->endOfWeek()->toDateString()])
                ->whereIn('trang_thai', ['DA_LEN_LICH', 'DANG_TAP', 'HOAN_THANH']);
            if ($keHoach) {
                $tuan->where('ke_hoach_tap_id', $keHoach->id);
            }
            $tuTap = (clone $q)->whereBetween('ngay_tap', [$ngay, $denTuan])->whereIn('trang_thai', ['DA_LEN_LICH', 'DANG_TAP']);
            $hen = LichHenHuanLuyen::where('khach_hang_id', $khachId)->where('bat_dau_luc', '>=', $moc)
                ->where('bat_dau_luc', '<', $hom->addDays(7)->startOfDay()->utc()->format('Y-m-d H:i:s.u'))
                ->where(fn ($r) => $r->where('trang_thai', 'DA_XAC_NHAN')->orWhere(fn ($s) => $s->where('trang_thai', 'CHO_XAC_NHAN')->where('han_xac_nhan_dat_lich', '>', $moc)));
            $sapToi = (clone $tuTap)->with('keHoach')->orderBy('ngay_tap')->orderBy('id')->limit(3)->get()->map(fn ($l) => [
                'loai' => 'TU_TAP', 'id' => $l->id, 'ten' => $l->keHoach->ten_ke_hoach.' · Ngày '.$l->ngay_thu,
                'ngay' => $l->ngay_tap, 'bat_dau_luc' => null, 'trang_thai' => $l->trang_thai,
                'sap_xep' => $l->ngay_tap.'T00:00:00',
            ])->concat((clone $hen)->with('pt.taiKhoan')->orderBy('bat_dau_luc')->limit(3)->get()->map(fn ($h) => [
                'loai' => 'PT', 'id' => $h->id, 'ten' => 'Buổi PT với '.$h->pt->taiKhoan->ho_ten,
                'ngay' => $h->bat_dau_luc->setTimezone('Asia/Ho_Chi_Minh')->toDateString(),
                'bat_dau_luc' => $h->bat_dau_luc->toIso8601String(), 'trang_thai' => $h->trangThaiHieuLuc(),
                'sap_xep' => $h->bat_dau_luc->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i:s'),
            ]))->sortBy('sap_xep')->take(3)->map(fn ($l) => collect($l)->except('sap_xep')->all())->values();
            $goi = DangKyGoiTap::where('khach_hang_id', $khachId)->where('trang_thai', 'DANG_SU_DUNG')
                ->where('kich_hoat_luc', '<=', $moc)->where('het_han_luc', '>', $moc)->first();
            $phanCong = PhanCongHuanLuyenVien::where('khach_hang_id', $khachId)->where('bat_dau_luc', '<=', $moc)
                ->where(fn ($r) => $r->whereNull('ket_thuc_luc')->orWhere('ket_thuc_luc', '>', $moc))
                ->whereHas('pt.taiKhoan', fn ($r) => $r->where('trang_thai', 'HOAT_DONG'))->with('pt.taiKhoan')->first();
            $mocTap = (clone $hoanThanh)->whereBetween('ngay_tap', [$tu, $ngay])->selectRaw('ngay_tap, COUNT(*) AS so_buoi')
                ->groupBy('ngay_tap')->pluck('so_buoi', 'ngay_tap');
            // DB lưu UTC, Việt Nam UTC+7; không phụ thuộc bảng timezone của MySQL.
            // Đếm lịch hoàn thành trực tiếp, tránh nhân số buổi khi có nhiều bài/hiệp kết quả.
            $mocPt = LichHenHuanLuyen::where('khach_hang_id', $khachId)->where('trang_thai', 'HOAN_THANH')
                ->where('bat_dau_luc', '>=', $hom->subDays($soNgay - 1)->startOfDay()->utc()->format('Y-m-d H:i:s.u'))
                ->where('bat_dau_luc', '<', $hom->addDay()->startOfDay()->utc()->format('Y-m-d H:i:s.u'))
                ->where('ket_thuc_luc', '<=', $moc)
                ->selectRaw('DATE(DATE_ADD(bat_dau_luc, INTERVAL 7 HOUR)) AS ngay, COUNT(*) AS so_buoi')
                ->groupBy('ngay')->pluck('so_buoi', 'ngay');
            $theoNgay = [];
            for ($i = $soNgay - 1; $i >= 0; $i--) {
                $n = $hom->subDays($i)->toDateString();
                $soTuTapNgay = (int) ($mocTap[$n] ?? 0);
                $soPtNgay = (int) ($mocPt[$n] ?? 0);
                $theoNgay[] = ['ngay' => $n, 'so_buoi' => $soTuTapNgay, 'so_buoi_tu_tap' => $soTuTapNgay,
                    'so_buoi_pt' => $soPtNgay, 'tong_so_buoi' => $soTuTapNgay + $soPtNgay];
            }
            $chiSo = app(ChiSoCoTheService::class)->danhSach($nguoi, $khachId, 30, 1)['data'];

            return ['hom_nay' => $ngay, 'buoi_thang_nay' => $soBuoi, 'lich_da_den_thang_nay' => $daDen,
                'ti_le_hoan_thanh' => $daDen ? round($soBuoi / $daDen * 100) : null,
                'so_lich_sap_toi' => (clone $tuTap)->count() + (clone $hen)->count(), 'lich_sap_toi' => $sapToi,
                'buoi_hom_nay' => $lichHomNay ? $this->buoi($lichHomNay) : null,
                'giao_an' => $keHoach ? ['id' => $keHoach->id, 'ten' => $keHoach->ten_ke_hoach, 'muc_tieu' => $keHoach->muc_tieu,
                    'nguon_tao' => $keHoach->nguon_tao, 'so_ngay_tap' => (int) $keHoach->so_ngay_tap, 'so_bai' => $keHoach->cac_bai_tap_count,
                    'lich_tuan' => (clone $tuan)->count(), 'hoan_thanh_tuan' => (clone $tuan)->whereHas('phien', fn ($p) => $p->where('trang_thai', 'HOAN_THANH'))
                        ->where('trang_thai', 'HOAN_THANH')->count()] : null,
                'goi' => $goi ? ['ten' => $goi->ten_goi_snapshot, 'het_han_luc' => $goi->het_han_luc->toIso8601String(),
                    'so_buoi_con_lai' => $goi->so_buoi_con_lai, 'so_buoi_pt' => (int) $goi->so_buoi_pt_snapshot,
                    'co_chatbot' => $goi->co_chatbot_snapshot] : null,
                'ai' => app(ChatbotService::class)->hanMuc($khachId),
                'pt' => $phanCong ? ['ho_ten' => $phanCong->pt->taiKhoan->ho_ten, 'chuyen_mon' => $phanCong->pt->chuyen_mon] : null,
                'tien_do' => ['so_ngay' => $soNgay, 'tu_ngay' => $tu, 'den_ngay' => $ngay,
                    'so_buoi' => array_sum(array_column($theoNgay, 'so_buoi')),
                    'so_buoi_tu_tap' => array_sum(array_column($theoNgay, 'so_buoi_tu_tap')),
                    'so_buoi_pt' => array_sum(array_column($theoNgay, 'so_buoi_pt')),
                    'tong_so_buoi' => array_sum(array_column($theoNgay, 'tong_so_buoi')), 'theo_ngay' => $theoNgay],
                'chi_so' => ['moi_nhat' => $chiSo['moi_nhat'], 'thay_doi_bmi' => $chiSo['thay_doi_bmi'],
                    'thay_doi_can_nang_kg' => $chiSo['thay_doi_can_nang_kg'],
                    'cac_moc' => $chiSo['cac_moc']->map(fn ($x) => collect($x)->only(['ngay_ghi', 'bmi'])->all())->values()],
            ];
        }, 3);
    }

    private function buoi(LichTap $lich): array
    {
        $bai = $lich->phien?->cacBaiTap;
        $soBai = $bai?->count() ?? $lich->keHoach->cacBaiTap()->where('ngay_thu', $lich->ngay_thu)->count();

        return ['id' => $lich->id, 'ten' => $lich->keHoach->ten_ke_hoach, 'ke_hoach_id' => $lich->ke_hoach_tap_id,
            'ngay_thu' => (int) $lich->ngay_thu, 'trang_thai' => $lich->trang_thai, 'so_bai' => $soBai,
            'so_bai_da_ghi' => $bai?->filter(fn ($b) => $b->cacHiep->count() > 0)->count() ?? 0];
    }
}
