<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BaoCaoService
{
    public function tongHop(array $boLoc): array
    {
        $tu = CarbonImmutable::parse($boLoc['tu_ngay'], 'Asia/Ho_Chi_Minh')->startOfDay();
        $den = CarbonImmutable::parse($boLoc['den_ngay'], 'Asia/Ho_Chi_Minh')->addDay()->startOfDay();
        $nhom = $boLoc['nhom'] ?? 'ngay';
        $bayGio = CarbonImmutable::now('UTC');

        // Một snapshot đọc nhất quán giữa tổng tiền, biểu đồ và các bảng chi tiết.
        return DB::transaction(function () use ($tu, $den, $nhom, $bayGio, $boLoc) {
            $dongTien = $this->dongTien($tu->utc(), $den->utc());
            $tong = DB::query()->fromSub(clone $dongTien, 'dong_tien')
                ->selectRaw('COALESCE(SUM(tien_nhan),0) AS tien_da_nhan, COALESCE(SUM(tien_hoan),0) AS tien_da_hoan, COALESCE(SUM(doi_soat),0) AS cho_doi_soat')->first();
            $tien = $this->soTien($tong);
            $dinhDang = $nhom === 'thang' ? '%Y-%m' : '%Y-%m-%d';
            $cacMoc = DB::query()->fromSub(clone $dongTien, 'dong_tien')
                ->selectRaw('DATE_FORMAT(DATE_ADD(moc, INTERVAL 7 HOUR), ?) AS ky, SUM(tien_nhan) AS tien_da_nhan, SUM(tien_hoan) AS tien_da_hoan, SUM(doi_soat) AS cho_doi_soat', [$dinhDang])
                ->groupBy('ky')->get()->keyBy('ky');
            $bieuDo = [];
            for ($moc = $nhom === 'thang' ? $tu->startOfMonth() : $tu; $moc->lessThan($den); $moc = $nhom === 'thang' ? $moc->addMonth() : $moc->addDay()) {
                $ky = $moc->format($nhom === 'thang' ? 'Y-m' : 'Y-m-d');
                $bieuDo[] = ['ky' => $ky, ...$this->soTien($cacMoc->get($ky))];
            }
            $theoGoi = DB::query()->fromSub(clone $dongTien, 'dong_tien')
                ->join('dang_ky_goi_tap as don', 'don.id', '=', 'dong_tien.don_id')
                ->selectRaw('don.goi_tap_id, don.ten_goi_snapshot, SUM(tien_nhan) AS tien_da_nhan, SUM(tien_hoan) AS tien_da_hoan, SUM(doi_soat) AS cho_doi_soat, COUNT(DISTINCT CASE WHEN tien_nhan > 0 THEN don.id END) AS so_don_nhan_tien')
                ->groupBy('don.goi_tap_id', 'don.ten_goi_snapshot')->orderByDesc('tien_da_nhan')->orderBy('don.goi_tap_id')->orderBy('don.ten_goi_snapshot')
                ->get()->map(fn ($r) => ['goi_tap_id' => $r->goi_tap_id, 'ten_goi' => $r->ten_goi_snapshot, 'so_don_nhan_tien' => (int) $r->so_don_nhan_tien, ...$this->soTien($r)])->all();
            $buoi = DB::table('lich_hen_huan_luyen')->where('trang_thai', 'HOAN_THANH')->whereNotNull('tieu_hao_luc');
            $this->trongKy($buoi, 'ket_thuc_luc', $tu->utc(), $den->utc());
            $phanCong = DB::table('phan_cong_huan_luyen_vien')->whereNotNull('bat_dau_luc')->where('bat_dau_luc', '<=', $bayGio)->whereNull('ket_thuc_luc');
            $pt = DB::table('ho_so_huan_luyen_vien as pt')->join('tai_khoan as tk', 'tk.id', '=', 'pt.tai_khoan_id')
                ->select('pt.id', 'tk.ho_ten', 'tk.trang_thai')
                ->selectSub((clone $phanCong)->whereColumn('huan_luyen_vien_id', 'pt.id')->selectRaw('COUNT(DISTINCT khach_hang_id)'), 'hoc_vien_hien_tai')
                ->selectSub((clone $buoi)->whereColumn('huan_luyen_vien_id', 'pt.id')->selectRaw('COUNT(*)'), 'buoi_hoan_thanh')
                ->orderByDesc('hoc_vien_hien_tai')->orderBy('pt.id')->paginate(20, ['*'], 'pt_page', $boLoc['pt_page'] ?? 1);
            $goiHienTai = DB::table('dang_ky_goi_tap')->where('trang_thai', 'DANG_SU_DUNG')->whereNotNull('kich_hoat_luc')->where('kich_hoat_luc', '<=', $bayGio)->where('het_han_luc', '>', $bayGio);

            return [
                'bo_loc' => ['tu_ngay' => $tu->toDateString(), 'den_ngay' => $den->subDay()->toDateString(), 'nhom' => $nhom, 'mui_gio' => 'Asia/Ho_Chi_Minh'],
                'cap_nhat_luc' => $bayGio->toIso8601String(),
                'trong_ky' => [
                    ...$tien,
                    'don_moi' => $this->trongKy(DB::table('dang_ky_goi_tap'), 'created_at', $tu->utc(), $den->utc())->count(),
                    'don_kich_hoat' => $this->trongKy(DB::table('dang_ky_goi_tap'), 'kich_hoat_luc', $tu->utc(), $den->utc())->count(),
                    'buoi_pt_hoan_thanh' => (clone $buoi)->count(),
                ],
                'hien_tai' => ['goi_dang_su_dung' => $goiHienTai->count(), 'hoc_vien_co_pt' => (clone $phanCong)->distinct()->count('khach_hang_id')],
                'van_hanh' => $this->vanHanh($bayGio, $goiHienTai, $phanCong),
                'bieu_do' => $bieuDo,
                'theo_goi' => $theoGoi,
                'pt' => [
                    'data' => collect($pt->items())->map(fn ($r) => ['id' => $r->id, 'ho_ten' => $r->ho_ten, 'trang_thai' => $r->trang_thai, 'hoc_vien_hien_tai' => (int) $r->hoc_vien_hien_tai, 'buoi_hoan_thanh' => (int) $r->buoi_hoan_thanh])->all(),
                    'meta' => ['current_page' => $pt->currentPage(), 'last_page' => $pt->lastPage(), 'total' => $pt->total()],
                ],
            ];
        });
    }

    private function vanHanh(CarbonImmutable $bayGio, Builder $goiHienTai, Builder $phanCong): array
    {
        $homNay = $bayGio->setTimezone('Asia/Ho_Chi_Minh')->startOfDay();
        $khachChoPt = DB::table('ho_so_khach_hang as kh')
            ->join('tai_khoan as tk', 'tk.id', '=', 'kh.tai_khoan_id')->where('tk.trang_thai', 'HOAT_DONG')
            ->whereIn('kh.id', (clone $goiHienTai)->where('so_buoi_con_lai', '>', 0)->select('khach_hang_id'))
            ->whereNotIn('kh.id', (clone $phanCong)->select('khach_hang_id'))->count();
        // Trạng thái hiệu lực không phụ thuộc việc worker đã chuyển trạng thái lưu trong DB.
        $trangThai = "CASE WHEN lh.trang_thai = 'CHO_XAC_NHAN' AND lh.han_xac_nhan_dat_lich <= ? THEN 'HET_HAN'
            WHEN lh.trang_thai = 'DA_XAC_NHAN' AND lh.ket_thuc_luc <= ? THEN 'QUA_HAN_XAC_NHAN' ELSE lh.trang_thai END";
        $lich = DB::query()->fromSub(DB::table('lich_hen_huan_luyen as lh')->select('lh.*')
            ->selectRaw($trangThai.' AS trang_thai_hieu_luc', [$bayGio, $bayGio->subHours(24)]), 'lich');
        $lichHomNay = $this->trongKy(clone $lich, 'bat_dau_luc', $homNay->utc(), $homNay->addDay()->utc());
        $trangThaiHomNay = (clone $lichHomNay)->selectRaw('trang_thai_hieu_luc, COUNT(*) AS so_luong')
            ->groupBy('trang_thai_hieu_luc')->pluck('so_luong', 'trang_thai_hieu_luc')->map(fn ($n) => (int) $n)->all();
        $cacLich = (clone $lichHomNay)
            ->join('ho_so_khach_hang as kh', 'kh.id', '=', 'lich.khach_hang_id')
            ->join('tai_khoan as tk_kh', 'tk_kh.id', '=', 'kh.tai_khoan_id')
            ->join('ho_so_huan_luyen_vien as pt', 'pt.id', '=', 'lich.huan_luyen_vien_id')
            ->join('tai_khoan as tk_pt', 'tk_pt.id', '=', 'pt.tai_khoan_id')
            ->orderBy('lich.bat_dau_luc')->orderBy('lich.id')->limit(8)
            ->get(['lich.id', 'lich.bat_dau_luc', 'lich.ket_thuc_luc', 'lich.trang_thai_hieu_luc', 'tk_kh.ho_ten as khach_hang', 'tk_pt.ho_ten as pt'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'bat_dau_luc' => CarbonImmutable::parse($r->bat_dau_luc, 'UTC')->toIso8601String(),
                'ket_thuc_luc' => CarbonImmutable::parse($r->ket_thuc_luc, 'UTC')->toIso8601String(),
                'khach_hang' => $r->khach_hang, 'pt' => $r->pt, 'trang_thai' => $r->trang_thai_hieu_luc,
            ])->all();
        $ai = DB::table('yeu_cau_tro_ly')->where('ngay_han_muc', $homNay->toDateString());
        $cacNgayAi = DB::table('yeu_cau_tro_ly')->whereBetween('ngay_han_muc', [$homNay->subDays(6)->toDateString(), $homNay->toDateString()])
            ->selectRaw('ngay_han_muc, COUNT(*) AS so_luong')->groupBy('ngay_han_muc')->pluck('so_luong', 'ngay_han_muc');
        $bieuDoAi = [];
        for ($i = 6; $i >= 0; $i--) {
            $ngay = $homNay->subDays($i)->toDateString();
            $bieuDoAi[] = ['ngay' => $ngay, 'so_luong' => (int) ($cacNgayAi[$ngay] ?? 0)];
        }

        return [
            'can_xu_ly' => [
                'giao_dich_doi_soat' => DB::table('thanh_toan')->where('trang_thai', 'CAN_DOI_SOAT')->whereNotNull('xac_minh_luc')->count(),
                'khach_cho_pt' => $khachChoPt,
                'lich_qua_han' => (clone $lich)->where('trang_thai_hieu_luc', 'QUA_HAN_XAC_NHAN')->whereNull('dong_xu_ly_luc')->count(),
                'giao_an_nhap' => DB::table('giao_an_mau')->where('trang_thai', 'NHAP')->count(),
            ],
            'lich_hom_nay' => ['ngay' => $homNay->toDateString(), 'tong' => array_sum($trangThaiHomNay), 'trang_thai' => $trangThaiHomNay, 'data' => $cacLich],
            'pt_co_hoc_vien' => (clone $phanCong)->distinct()->count('huan_luyen_vien_id'),
            'goi_sap_het_han' => (clone $goiHienTai)->where('het_han_luc', '<=', $bayGio->addDays(7))->count(),
            'tai_lieu_da_xuat_ban' => DB::table('tai_lieu_tu_van')->where('trang_thai', 'DA_XUAT_BAN')->where('xuat_ban_luc', '<=', $bayGio)->count(),
            'ai' => [
                'ngay' => $homNay->toDateString(), 'yeu_cau' => (clone $ai)->count(),
                'thanh_cong' => (clone $ai)->where('trang_thai', 'THANH_CONG')->count(),
                'loi' => (clone $ai)->where('trang_thai', 'LOI')->count(),
                'dang_xu_ly' => (clone $ai)->where('trang_thai', 'DANG_XU_LY')->count(),
                'input_tokens' => (int) (clone $ai)->sum('input_tokens'), 'output_tokens' => (int) (clone $ai)->sum('output_tokens'),
                'bieu_do' => $bieuDoAi,
            ],
        ];
    }

    private function dongTien(CarbonImmutable $tu, CarbonImmutable $den): Builder
    {
        $xacMinh = DB::table('thanh_toan')->whereNotNull('xac_minh_luc')->whereIn('trang_thai', ['DA_XAC_MINH', 'CAN_DOI_SOAT', 'DA_HOAN_TIEN']);
        $nhan = $this->trongKy(clone $xacMinh, 'thanh_toan_luc', $tu, $den)
            ->selectRaw("dang_ky_goi_tap_id AS don_id, thanh_toan_luc AS moc, so_tien AS tien_nhan, 0 AS tien_hoan, CASE WHEN trang_thai = 'CAN_DOI_SOAT' THEN so_tien ELSE 0 END AS doi_soat");
        $hoan = $this->trongKy(clone $xacMinh, 'hoan_tien_luc', $tu, $den)->where('trang_thai', 'DA_HOAN_TIEN')
            ->selectRaw('dang_ky_goi_tap_id AS don_id, hoan_tien_luc AS moc, 0 AS tien_nhan, COALESCE(so_tien_hoan,0) AS tien_hoan, 0 AS doi_soat');

        return $nhan->unionAll($hoan);
    }

    private function trongKy(Builder $query, string $cot, CarbonImmutable $tu, CarbonImmutable $den): Builder
    {
        return $query->where($cot, '>=', $tu)->where($cot, '<', $den);
    }

    private function soTien(?object $r): array
    {
        $nhan = (int) ($r->tien_da_nhan ?? 0);
        $hoan = (int) ($r->tien_da_hoan ?? 0);

        return ['tien_da_nhan' => $nhan, 'tien_da_hoan' => $hoan, 'thuc_thu' => $nhan - $hoan, 'cho_doi_soat' => (int) ($r->cho_doi_soat ?? 0)];
    }
}
