<?php

namespace App\Services;

use App\Models\HoSoKhachHang;
use App\Models\KhungGioHuanLuyenVien;
use App\Models\LichHenHuanLuyen;
use App\Models\LichTap;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class TongQuanPtService
{
    public function doc(TaiKhoan $nguoi): array
    {
        abort_unless($nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN, 403);
        $ptId = $nguoi->hoSoHuanLuyenVien->id;
        $moc = CarbonImmutable::now();
        $hom = $moc->setTimezone('Asia/Ho_Chi_Minh')->startOfDay();
        $tuan = $hom->startOfWeek();
        $cuoi = $tuan->addWeek();

        return DB::transaction(function () use ($nguoi, $ptId, $moc, $hom, $tuan, $cuoi) {
            // Cùng khóa KH với đổi phân công; đọc lại quyền sau khóa, không giữ dữ liệu PT cũ.
            $khachIds = PhanCongHuanLuyenVien::where('huan_luyen_vien_id', $ptId)->whereNull('ket_thuc_luc')->pluck('khach_hang_id');
            HoSoKhachHang::whereIn('id', $khachIds)->orderBy('id')->lockForUpdate()->get(['id']);
            $phanCong = PhanCongHuanLuyenVien::where('huan_luyen_vien_id', $ptId)->whereIn('khach_hang_id', $khachIds)
                ->whereNull('ket_thuc_luc')->where('bat_dau_luc', '<=', $moc)->lockForUpdate()->get();
            $hocVien = HoSoKhachHang::whereIn('id', $phanCong->pluck('khach_hang_id'))
                ->whereHas('taiKhoan', fn ($q) => $q->where('trang_thai', TaiKhoan::HOAT_DONG))
                ->with('taiKhoan:id,ho_ten')->orderBy('id')->get(['id', 'tai_khoan_id', 'muc_tieu']);
            $khachIds = $hocVien->pluck('id');
            $phanCong = $phanCong->whereIn('khach_hang_id', $khachIds);
            $tenKhach = $hocVien->mapWithKeys(fn ($k) => [$k->id => $k->taiKhoan->ho_ten]);
            $lichQuery = LichHenHuanLuyen::where('huan_luyen_vien_id', $ptId)->whereIn('phan_cong_id', $phanCong->pluck('id'));
            $cho = (clone $lichQuery)->where('trang_thai', 'CHO_XAC_NHAN')->where('han_xac_nhan_dat_lich', '>', $moc);
            $ketThuc = (clone $lichQuery)->where('trang_thai', 'DA_XAC_NHAN')->where('ket_thuc_luc', '<=', $moc)->where('ket_thuc_luc', '>', $moc->subHours(24));
            $homNay = (clone $lichQuery)->where('bat_dau_luc', '>=', $hom->utc())->where('bat_dau_luc', '<', $hom->addDay()->utc());
            $lichHomNay = $homNay->orderBy('bat_dau_luc')->orderBy('id')->get();
            $lichTuan = (clone $lichQuery)->where('bat_dau_luc', '>=', $tuan->utc())->where('bat_dau_luc', '<', $cuoi->utc())->get();
            $hoanThanh = $lichTuan->filter(fn ($l) => $l->trang_thai === 'HOAN_THANH' && $l->tieu_hao_luc !== null && $l->ket_thuc_luc->lessThanOrEqualTo($moc));
            $daDen = $lichTuan->filter(fn ($l) => $l->ket_thuc_luc->lessThanOrEqualTo($moc) && in_array($l->trangThaiHieuLuc(), ['DA_XAC_NHAN', 'HOAN_THANH', 'VANG_MAT', 'QUA_HAN_XAC_NHAN'], true));
            $keHoachQuery = app(KeHoachTapService::class)->phamVi($nguoi)->whereIn('khach_hang_id', $khachIds);
            $nhap = (clone $keHoachQuery)->where('trang_thai', 'NHAP')->count();
            $nhapPt = (clone $keHoachQuery)->where('trang_thai', 'NHAP')->where('nguon_tao', 'PT')->whereIn('phan_cong_id', $phanCong->pluck('id'))->where('huan_luyen_vien_id', $ptId)->count();
            $choDuyet = (clone $keHoachQuery)->where('trang_thai', 'CHO_DUYET')->where('han_duyet', '>', $moc)->whereIn('phan_cong_id', $phanCong->pluck('id'));
            $dangDung = (clone $keHoachQuery)->where('trang_thai', 'DANG_AP_DUNG');
            $cacBan = (clone $keHoachQuery)->orderByDesc('updated_at')->orderByDesc('id')->limit(3)->get();
            $cacLich = LichTap::whereIn('khach_hang_id', $khachIds)->where('ngay_tap', '>=', $tuan->toDateString())->where('ngay_tap', '<', $cuoi->toDateString())->where('trang_thai', '!=', 'DA_HUY')
                ->selectRaw('khach_hang_id, COUNT(*) AS tong')->groupBy('khach_hang_id')->pluck('tong', 'khach_hang_id');
            $tuTap = LichTap::whereIn('khach_hang_id', $khachIds)->where('trang_thai', 'HOAN_THANH')->whereHas('phien', fn ($q) => $q->where('trang_thai', 'HOAN_THANH'));
            $daTap = (clone $tuTap)->where('ngay_tap', '>=', $tuan->toDateString())->where('ngay_tap', '<=', $hom->toDateString())
                ->selectRaw('khach_hang_id, COUNT(*) AS tong')->groupBy('khach_hang_id')->pluck('tong', 'khach_hang_id');
            $ganDay = (clone $tuTap)->where('ngay_tap', '>=', $hom->subDays(6)->toDateString())->where('ngay_tap', '<=', $hom->toDateString())->pluck('khach_hang_id')->unique();
            $dangDungTheoKhach = $dangDung->get()->keyBy('khach_hang_id');
            $choTheoKhach = $choDuyet->pluck('khach_hang_id');
            $canChuY = [];
            $cacHocVien = $hocVien->map(function ($k) use ($cacLich, $daTap, $ganDay, $dangDungTheoKhach, $choTheoKhach, $phanCong, $moc, &$canChuY) {
                $lyDo = $choTheoKhach->contains($k->id) ? 'Chưa xác nhận giáo án PT' : null;
                if (! $lyDo && ! isset($cacLich[$k->id])) {
                    $lyDo = 'Chưa có lịch tự tập tuần này';
                }
                if (! $lyDo && ! $ganDay->contains($k->id) && $phanCong->firstWhere('khach_hang_id', $k->id)->bat_dau_luc->lessThanOrEqualTo($moc->subDays(7))) {
                    $lyDo = '7 ngày chưa ghi nhận buổi tự tập hoàn thành';
                }
                $dong = ['id' => $k->id, 'ho_ten' => $k->taiKhoan->ho_ten, 'muc_tieu' => $k->muc_tieu,
                    'so_lich_tuan' => (int) ($cacLich[$k->id] ?? 0), 'so_buoi_tu_tap' => (int) ($daTap[$k->id] ?? 0),
                    'giao_an' => $dangDungTheoKhach->get($k->id)?->ten_ke_hoach];
                if ($lyDo) {
                    $canChuY[] = ['id' => $k->id, 'ho_ten' => $k->taiKhoan->ho_ten, 'ly_do' => $lyDo, 'loai' => $choTheoKhach->contains($k->id) ? 'GIAO_AN' : 'LICH_TAP'];
                }

                return $dong;
            });
            // Dùng đúng cursor người nhận như ChatService; đọc tổng quan không đánh dấu đã đọc.
            $hoiQuery = DB::table('hoi_thoai as h')->join('phan_cong_huan_luyen_vien as p', 'p.id', '=', 'h.phan_cong_id')->whereIn('p.id', $phanCong->pluck('id'));
            $chuaDoc = DB::table('tin_nhan')->whereColumn('hoi_thoai_id', 'h.id')->where('nguoi_gui_id', '!=', $nguoi->id)->whereRaw('id > COALESCE(h.cursor_pt_da_doc, 0)');
            $soTin = (clone $hoiQuery)->join('tin_nhan as t', 't.hoi_thoai_id', '=', 'h.id')->where('t.nguoi_gui_id', '!=', $nguoi->id)->whereRaw('t.id > COALESCE(h.cursor_pt_da_doc, 0)')->count();
            $soHoi = (clone $hoiQuery)->whereExists(clone $chuaDoc)->count();
            $tin = (clone $hoiQuery)->select('h.id', 'p.khach_hang_id')->selectSub((clone $chuaDoc)->selectRaw('COUNT(*)'), 'so_chua_doc')
                ->selectSub(DB::table('tin_nhan')->whereColumn('hoi_thoai_id', 'h.id')->orderByDesc('id')->limit(1)->select('noi_dung'), 'tin_cuoi')
                ->selectSub(DB::table('tin_nhan')->whereColumn('hoi_thoai_id', 'h.id')->orderByDesc('id')->limit(1)->select('created_at'), 'tin_cuoi_luc')
                ->selectSub(DB::table('tin_nhan')->whereColumn('hoi_thoai_id', 'h.id')->orderByDesc('id')->limit(1)->select('id'), 'tin_cuoi_id')
                ->orderByDesc('tin_cuoi_id')->orderByDesc('h.id')->limit(3)->get();
            $slots = KhungGioHuanLuyenVien::where('huan_luyen_vien_id', $ptId)->where('trang_thai', 'MO')->where('bat_dau_luc', '>=', $moc->addHours(4))->where('bat_dau_luc', '<', $moc->addDays(7));
            // Slot chỉ còn bận với yêu cầu có hiệu lực; worker chưa dọn hết hạn không làm sai số.
            $soSlot = (clone $slots)->count();
            $daDat = (clone $slots)->whereIn('id', LichHenHuanLuyen::select('khung_gio_id')->where('huan_luyen_vien_id', $ptId)->where(fn ($q) => $q->where('trang_thai', 'DA_XAC_NHAN')->orWhere(fn ($q) => $q->where('trang_thai', 'CHO_XAC_NHAN')->where('han_xac_nhan_dat_lich', '>', $moc))))->count();
            $duLieuLich = fn ($l) => ['id' => $l->id, 'khach_hang_id' => $l->khach_hang_id, 'ho_ten' => $tenKhach[$l->khach_hang_id], 'bat_dau_luc' => $l->bat_dau_luc->toISOString(), 'ket_thuc_luc' => $l->ket_thuc_luc->toISOString(), 'trang_thai' => $l->trangThaiHieuLuc()];

            return [
                'hom_nay' => $hom->toDateString(), 'tu_ngay' => $tuan->toDateString(), 'den_ngay' => $cuoi->subDay()->toDateString(),
                'so_hoc_vien' => $hocVien->count(), 'so_buoi_hom_nay' => $lichHomNay->filter(fn ($l) => in_array($l->trangThaiHieuLuc(), ['CHO_XAC_NHAN', 'DA_XAC_NHAN', 'HOAN_THANH', 'VANG_MAT', 'QUA_HAN_XAC_NHAN'], true))->count(),
                'lich_hom_nay' => $lichHomNay->take(6)->map($duLieuLich)->values()->all(), 'tong_lich_hom_nay' => $lichHomNay->count(),
                'can_xu_ly' => ['cho_dat_lich' => (clone $cho)->count(), 'cho_ket_qua' => (clone $ketThuc)->count(), 'nhap_pt' => $nhapPt, 'hoi_thoai_chua_doc' => $soHoi,
                    'giao_an_nhap_id' => (clone $keHoachQuery)->where('trang_thai', 'NHAP')->where('nguon_tao', 'PT')->whereIn('phan_cong_id', $phanCong->pluck('id'))->where('huan_luyen_vien_id', $ptId)->orderBy('updated_at')->orderBy('id')->value('id'),
                    'lich_dat' => (clone $cho)->orderBy('han_xac_nhan_dat_lich')->limit(1)->get()->map($duLieuLich)->first(),
                    'lich_ket_qua' => (clone $ketThuc)->orderBy('ket_thuc_luc')->limit(1)->get()->map($duLieuLich)->first()],
                'hoc_vien' => $cacHocVien->take(3)->values()->all(), 'can_chu_y' => array_slice($canChuY, 0, 4), 'tong_can_chu_y' => count($canChuY),
                'giao_an' => ['nhap' => $nhap, 'cho_xac_nhan' => (clone $choDuyet)->count(), 'dang_ap_dung' => (clone $dangDung)->count(),
                    'gan_day' => $cacBan->map(fn ($k) => ['id' => $k->id, 'khach_hang_id' => $k->khach_hang_id, 'ho_ten' => $tenKhach[$k->khach_hang_id], 'ten' => $k->ten_ke_hoach, 'nguon_tao' => $k->nguon_tao,
                        'trang_thai' => $k->trang_thai === 'CHO_DUYET' && ! $k->han_duyet?->isFuture() ? 'QUA_HAN' : $k->trang_thai,
                        'co_the_sua' => $k->nguon_tao === 'PT' && $k->trang_thai === 'NHAP' && (int) $k->huan_luyen_vien_id === (int) $ptId && $phanCong->contains('id', $k->phan_cong_id)])->all()],
                'tin_nhan' => ['so_chua_doc' => $soTin, 'gan_day' => $tin->map(fn ($h) => ['id' => (int) $h->id, 'ho_ten' => $tenKhach[$h->khach_hang_id], 'so_chua_doc' => (int) $h->so_chua_doc,
                    'noi_dung' => $h->tin_cuoi === '' ? 'Đã gửi ảnh' : $h->tin_cuoi, 'luc' => $h->tin_cuoi_luc ? CarbonImmutable::parse($h->tin_cuoi_luc, 'UTC')->toISOString() : null])->all()],
                'tuan' => ['da_len_lich' => $lichTuan->count(), 'hoan_thanh' => $hoanThanh->count(), 'da_huy' => $lichTuan->whereIn('trang_thai', ['DA_HUY', 'TU_CHOI'])->count(), 'vang_mat' => $lichTuan->where('trang_thai', 'VANG_MAT')->count(),
                    'da_den' => $daDen->count(), 'ti_le_hoan_thanh' => $daDen->isEmpty() ? null : (int) round($hoanThanh->filter(fn ($l) => $l->ket_thuc_luc->lessThanOrEqualTo($moc))->count() * 100 / $daDen->count()),
                    'theo_ngay' => collect(range(0, 6))->map(fn ($i) => ['ngay' => $tuan->addDays($i)->toDateString(), 'so_buoi' => $hoanThanh->filter(fn ($l) => $l->bat_dau_luc->setTimezone('Asia/Ho_Chi_Minh')->toDateString() === $tuan->addDays($i)->toDateString())->count()])->all()],
                'khung_gio' => ['tu_luc' => $moc->addHours(4)->toISOString(), 'den_luc' => $moc->addDays(7)->toISOString(), 'tong' => $soSlot, 'da_dat' => $daDat, 'con_trong' => $soSlot - $daDat],
            ];
        }, 3);
    }
}
