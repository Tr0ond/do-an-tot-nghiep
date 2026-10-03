<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeHoachTapRequest;
use App\Models\HoSoKhachHang;
use App\Models\KeHoachTap;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use App\Services\KeHoachTapService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class KeHoachTapController extends Controller
{
    public function hocVien(KeHoachTapRequest $request)
    {
        $q = HoSoKhachHang::query()->join('tai_khoan', 'tai_khoan.id', '=', 'ho_so_khach_hang.tai_khoan_id')
            ->whereIn('ho_so_khach_hang.id', PhanCongHuanLuyenVien::select('khach_hang_id')->where('huan_luyen_vien_id', $request->user()->hoSoHuanLuyenVien->id)->whereNull('ket_thuc_luc'));
        if ($tuKhoa = $request->validated('tu_khoa')) {
            $q->whereRaw("tai_khoan.ho_ten LIKE ? ESCAPE '='", ['%'.str_replace(['=', '%', '_'], ['==', '=%', '=_'], $tuKhoa).'%']);
        }
        $p = $q->select('ho_so_khach_hang.id', 'tai_khoan.ho_ten')->orderBy('ho_so_khach_hang.id')->paginate(20);

        return $this->phanHoi($p->items(), 'Đã tải học viên.', ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'total' => $p->total()]);
    }

    public function index(KeHoachTapRequest $request, KeHoachTapService $dichVu, ?int $khachId = null)
    {
        return DB::transaction(function () use ($request, $dichVu, $khachId) {
            $hocVien = null;
            if ($khachId !== null) {
                $khach = HoSoKhachHang::lockForUpdate()->findOrFail($khachId);
                $dichVu->phanCong($request->user(), $khachId);
                $hocVien = ['id' => $khachId, 'ho_ten' => TaiKhoan::findOrFail($khach->tai_khoan_id)->ho_ten];
            }
            $q = $dichVu->phamVi($request->user());
            if ($request->user()->vai_tro === TaiKhoan::KHACH_HANG) {
                $request->boolean('da_an') ? $q->whereNotNull('khach_an_luc') : $q->whereNull('khach_an_luc');
            }
            if ($nguon = $request->validated('nguon_tao')) {
                $q->where('nguon_tao', $nguon);
            }
            if ($khachId !== null) {
                $q->where('khach_hang_id', $khachId);
            }
            $p = $q->with('phanCongHienTai.pt.taiKhoan')->withCount('cacBaiTap')->orderByDesc('id')->paginate(20);

            return $this->phanHoi(array_map(fn ($k) => $this->duLieu($k, $request), $p->items()), 'Đã tải giáo án.', ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'total' => $p->total(), 'hoc_vien' => $hocVien]);
        }, 3);
    }

    public function show(KeHoachTapRequest $request, int $id, KeHoachTapService $dichVu)
    {
        return $this->phanHoi($this->duLieu($dichVu->doc($request->user(), $id), $request), 'Đã tải giáo án.');
    }

    public function store(KeHoachTapRequest $request, KeHoachTapService $dichVu, ?int $khachId = null)
    {
        $khachId ??= $request->user()->hoSoKhachHang->id;
        $k = $dichVu->tao($request->user(), $khachId, $request->validated());

        return $this->phanHoi($this->duLieu($k, $request), 'Đã lưu bản nháp.', null, $k->wasRecentlyCreated ? 201 : 200);
    }

    public function update(KeHoachTapRequest $request, int $id, KeHoachTapService $dichVu)
    {
        return $this->phanHoi($this->duLieu($dichVu->sua($request->user(), $id, $request->validated()), $request), 'Đã lưu giáo án.');
    }

    public function thaoTac(KeHoachTapRequest $request, int $id, string $hanhDong, KeHoachTapService $dichVu)
    {
        return $this->phanHoi($this->duLieu($dichVu->thaoTac($request->user(), $id, $hanhDong, $request->validated('updated_at')), $request), 'Đã cập nhật giáo án.');
    }

    private function duLieu(KeHoachTap $k, KeHoachTapRequest $request): array
    {
        $k->loadMissing('phanCongHienTai.pt.taiKhoan');
        $pc = $k->phanCongHienTai;
        $conQuyen = $pc && (int) $pc->id === (int) $k->phan_cong_id;
        $laPt = $request->user()->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN;
        $tuTao = $k->nguon_tao === 'KHACH_HANG';
        $laKhSoHuu = ! $laPt && (int) $request->user()->hoSoKhachHang->id === (int) $k->khach_hang_id;
        $chuSoHuu = $laKhSoHuu && $tuTao;
        $conQuyen = $conQuyen && (! $laPt || (int) $pc->huan_luyen_vien_id === (int) $request->user()->hoSoHuanLuyenVien->id);
        $choDuyet = $k->trang_thai === 'CHO_DUYET' && $k->han_duyet?->isFuture();
        $xacNhanDuoc = ! $laPt && $conQuyen && $choDuyet && $pc->pt->taiKhoan->trang_thai === TaiKhoan::HOAT_DONG;
        $duLieu = [
            'id' => $k->id, 'khach_hang_id' => $k->khach_hang_id, 'nguon_tao' => $k->nguon_tao,
            'ten_ke_hoach' => $k->ten_ke_hoach, 'muc_tieu' => $k->muc_tieu,
            'so_ngay_tap' => $k->so_ngay_tap, 'so_bai_tap' => $k->cac_bai_tap_count ?? $k->cacBaiTap->count(),
            'giao_an_mau_id' => $k->giao_an_mau_id, 'thay_the_ke_hoach_id' => $k->thay_the_ke_hoach_id,
            'trang_thai' => $k->trang_thai,
            'da_an' => $k->khach_an_luc !== null, 'khach_an_luc' => $k->khach_an_luc?->toISOString(),
            'trang_thai_hien_thi' => $k->trang_thai === 'CHO_DUYET' && ! $choDuyet ? 'QUA_HAN' : $k->trang_thai,
            'updated_at' => $k->updated_at?->format('Y-m-d H:i:s.u'),
            'gui_luc' => $k->gui_luc?->toISOString(), 'han_duyet' => $k->han_duyet?->toISOString(), 'duyet_luc' => $k->duyet_luc?->toISOString(),
            'co_the_sua' => (($laPt && $conQuyen && ! $tuTao) || $chuSoHuu) && $k->trang_thai === 'NHAP',
            'co_the_gui' => $laPt && $conQuyen && ! $tuTao && $k->trang_thai === 'NHAP',
            'co_the_huy' => ($laPt && $conQuyen && ! $tuTao && in_array($k->trang_thai, ['NHAP', 'CHO_DUYET'])) || ($chuSoHuu && $k->trang_thai === 'NHAP'),
            'co_the_xac_nhan' => $xacNhanDuoc,
            'co_the_ap_dung' => ($chuSoHuu && $k->khach_an_luc === null && in_array($k->trang_thai, ['NHAP', 'LUU_TRU']))
                || ($laKhSoHuu && ! $tuTao && $k->gui_luc !== null && $k->duyet_luc !== null && $k->trang_thai === 'LUU_TRU'),
            'co_the_luu_tru' => $laKhSoHuu && $k->trang_thai === 'DANG_AP_DUNG',
            'co_the_an' => $chuSoHuu && $k->khach_an_luc === null && in_array($k->trang_thai, ['DA_HUY', 'LUU_TRU']),
            'co_the_hien_lai' => $chuSoHuu && $k->khach_an_luc !== null,
        ];
        if ($k->relationLoaded('cacBaiTap')) {
            $duLieu['bai_tap'] = $k->cacBaiTap->map(fn ($b) => [...Arr::only($b->toArray(), KeHoachTapService::THUOC_TINH_BAI), 'id' => $b->id, 'ten_bai_tap' => $b->ten_bai_tap_snapshot, ...($b->noi_dung_snapshot ?? [])])->all();
        }

        return $duLieu;
    }

    private function phanHoi($data, string $message, ?array $meta = null, int $status = 200)
    {
        return response()->json(['status' => true, 'message' => $message, 'data' => $data, ...($meta === null ? [] : ['meta' => $meta])], $status)->header('Cache-Control', 'private, no-store');
    }
}
