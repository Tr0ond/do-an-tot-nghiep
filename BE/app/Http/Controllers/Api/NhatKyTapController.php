<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NhatKyTapRequest;
use App\Models\KeHoachTap;
use App\Models\LichTap;
use App\Models\TaiKhoan;
use App\Services\NhatKyTapService;
use Illuminate\Support\Facades\DB;

class NhatKyTapController extends Controller
{
    public function index(NhatKyTapRequest $request, NhatKyTapService $dichVu, ?int $khachId = null)
    {
        return DB::transaction(function () use ($request, $dichVu, $khachId) {
            $khachId ??= $request->user()->hoSoKhachHang->id;
            $khach = $dichVu->khoaKhach($request->user(), $khachId);
            $d = $request->validated();
            $q = LichTap::where('khach_hang_id', $khachId)->whereBetween('ngay_tap', [$d['tu_ngay'], $d['den_ngay']]);
            if ($d['trang_thai'] ?? null) {
                $q->where('trang_thai', $d['trang_thai']);
            }
            $p = $q->with('keHoach', 'phien')->orderByDesc('ngay_tap')->orderByDesc('id')->paginate(12);
            $k = KeHoachTap::where('khach_hang_id', $khachId)->where('trang_thai', 'DANG_AP_DUNG')->with('cacBaiTap')->first();

            return $this->phanHoi(array_map(fn ($l) => $this->duLieu($l, $request, false), $p->items()), 'Đã tải lịch tự tập.',
                ['current_page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'total' => $p->total(),
                    'hoc_vien' => ['id' => $khachId, 'ho_ten' => TaiKhoan::findOrFail($khach->tai_khoan_id)->ho_ten],
                    'hom_nay' => now('Asia/Ho_Chi_Minh')->toDateString(),
                    'giao_an_dang_dung' => $k ? ['id' => $k->id, 'ten_ke_hoach' => $k->ten_ke_hoach, 'nguon_tao' => $k->nguon_tao,
                        'cac_ngay' => $k->cacBaiTap->groupBy('ngay_thu')->map(fn ($b, $ngay) => ['ngay_thu' => (int) $ngay, 'so_bai' => $b->count(), 'ten_bai' => $b->pluck('ten_bai_tap_snapshot')->all()])->values()] : null,
                    'thong_ke' => $this->thongKe($khachId, $d['tu_ngay'], $d['den_ngay'])]);
        }, 3);
    }

    public function show(NhatKyTapRequest $request, int $id, NhatKyTapService $dichVu)
    {
        return DB::transaction(fn () => $this->phanHoi($this->duLieu($dichVu->khoaLich($request->user(), $id), $request), 'Đã tải buổi tự tập.'), 3);
    }

    public function store(NhatKyTapRequest $request, NhatKyTapService $dichVu, ?int $khachId = null)
    {
        return DB::transaction(function () use ($request, $dichVu, $khachId) {
            $khachId ??= $request->user()->hoSoKhachHang->id;
            $l = $dichVu->tao($request->user(), $khachId, $request->validated());

            return $this->phanHoi($this->duLieu($l, $request), 'Đã lên lịch tự tập.', null, $l->wasRecentlyCreated ? 201 : 200);
        }, 3);
    }

    public function update(NhatKyTapRequest $request, int $id, NhatKyTapService $dichVu)
    {
        return DB::transaction(fn () => $this->phanHoi($this->duLieu($dichVu->luu($request->user(), $id, $request->validated()), $request), 'Đã lưu kết quả nháp.'), 3);
    }

    public function thaoTac(NhatKyTapRequest $request, int $id, string $hanhDong, NhatKyTapService $dichVu)
    {
        return DB::transaction(function () use ($request, $id, $hanhDong, $dichVu) {
            $l = $hanhDong === 'nhan-xet' ? $dichVu->nhanXet($request->user(), $id, $request->validated())
                : $dichVu->thaoTac($request->user(), $id, $hanhDong, $request->validated('updated_at'));

            return $this->phanHoi($this->duLieu($l, $request), ['bat-dau' => 'Đã bắt đầu buổi tập.', 'hoan-thanh' => 'Đã hoàn thành buổi tập.',
                'huy' => 'Đã hủy buổi tập; kết quả nháp vẫn được giữ.', 'nhan-xet' => 'Đã thêm nhận xét.'][$hanhDong]);
        }, 3);
    }

    private function duLieu(LichTap $l, NhatKyTapRequest $request, bool $chiTiet = true): array
    {
        $l->loadMissing('keHoach', 'phien');
        $kh = $request->user()->vai_tro === TaiKhoan::KHACH_HANG;
        $p = $l->phien;
        $d = ['id' => $l->id, 'khach_hang_id' => $l->khach_hang_id, 'ke_hoach_tap_id' => $l->ke_hoach_tap_id,
            'ten_ke_hoach' => $l->keHoach->ten_ke_hoach, 'nguon_tao' => $l->keHoach->nguon_tao,
            'ngay_thu' => $l->ngay_thu, 'ngay_tap' => $l->ngay_tap, 'trang_thai' => $l->trang_thai,
            'updated_at' => $l->updated_at?->format('Y-m-d H:i:s.u'),
            'co_the_bat_dau' => $kh && $l->trang_thai === 'DA_LEN_LICH' && $l->ngay_tap <= now('Asia/Ho_Chi_Minh')->toDateString(),
            'co_the_ghi' => $kh && $l->trang_thai === 'DANG_TAP',
            'co_the_huy' => $kh && in_array($l->trang_thai, ['DA_LEN_LICH', 'DANG_TAP'], true),
            'co_the_nhan_xet' => ! $kh && $l->trang_thai === 'HOAN_THANH',
            'phien' => $p ? ['id' => $p->id, 'bat_dau_luc' => $p->bat_dau_luc?->toIso8601String(),
                'hoan_thanh_luc' => $p->hoan_thanh_luc?->toIso8601String(), 'ghi_chu' => $p->ghi_chu] : null];
        if ($chiTiet) {
            $d['bai_tap'] = $p ? $p->cacBaiTap()->with('cacHiep')->get()->map(fn ($b) => ['id' => $b->id,
                'bai_tap_id' => $b->bai_tap_id, 'thu_tu' => $b->thu_tu, 'ten_bai_tap' => $b->ten_bai_tap_snapshot,
                'noi_dung' => $b->noi_dung_snapshot, 'hiep_tap' => $b->cacHiep->map(fn ($h) => $h->only(['thu_tu', 'so_lan_lap', 'khoi_luong_kg', 'nghi_giay']))])
                : $l->keHoach->cacBaiTap()->where('ngay_thu', $l->ngay_thu)->get()->map(fn ($b) => ['id' => null, 'bai_tap_id' => $b->bai_tap_id,
                    'thu_tu' => $b->thu_tu, 'ten_bai_tap' => $b->ten_bai_tap_snapshot, 'noi_dung' => [...($b->noi_dung_snapshot ?? []),
                        'du_kien' => $b->only(['so_hiep', 'so_lan_lap', 'muc_ta_kg', 'nghi_giay', 'ghi_chu'])], 'hiep_tap' => []]);
            $d['nhan_xet'] = $p ? $p->nhanXet()->with('pt.taiKhoan')->get()->map(fn ($n) => ['id' => $n->id,
                'noi_dung' => $n->noi_dung, 'ten_pt' => $n->pt->taiKhoan->ho_ten, 'created_at' => $n->created_at?->toIso8601String()]) : [];
        }

        return $d;
    }

    private function thongKe(int $khachId, string $tu, string $den): array
    {
        $lich = LichTap::where('khach_hang_id', $khachId)->where('trang_thai', 'HOAN_THANH')->whereBetween('ngay_tap', [$tu, $den]);
        $ngay = (clone $lich)->selectRaw('ngay_tap, COUNT(*) AS so_buoi')->groupBy('ngay_tap')->orderBy('ngay_tap')->get();
        $q = DB::table('hiep_tap as h')->join('bai_tap_trong_phien as b', 'b.id', '=', 'h.bai_tap_trong_phien_id')
            ->join('phien_tap as p', 'p.id', '=', 'b.phien_tap_id')->join('lich_tap as l', 'l.id', '=', 'p.lich_tap_id')
            ->where('l.khach_hang_id', $khachId)->where('l.trang_thai', 'HOAN_THANH')->where('p.trang_thai', 'HOAN_THANH')->whereBetween('l.ngay_tap', [$tu, $den]);
        $tong = (clone $q)->selectRaw('COUNT(*) AS so_hiep, COALESCE(SUM(h.so_lan_lap), 0) AS so_lan, COALESCE(SUM(h.khoi_luong_kg * h.so_lan_lap), 0) AS tong_khoi_luong, COUNT(h.khoi_luong_kg) AS so_hiep_co_ta')->first();
        $bai = (clone $q)->selectRaw('b.bai_tap_id, MAX(b.ten_bai_tap_snapshot) AS ten_bai_tap, MAX(h.khoi_luong_kg) AS muc_ta_cao_nhat')
            ->whereNotNull('h.khoi_luong_kg')->groupBy('b.bai_tap_id')->orderBy('b.bai_tap_id')->limit(10)->get();
        $tienDo = (clone $q)->whereIn('b.bai_tap_id', $bai->pluck('bai_tap_id'))->whereNotNull('h.khoi_luong_kg')
            ->selectRaw('b.bai_tap_id, l.ngay_tap, MAX(h.khoi_luong_kg) AS muc_ta_kg')->groupBy('b.bai_tap_id', 'l.ngay_tap')->orderBy('l.ngay_tap')->get();

        return ['so_buoi' => (int) $ngay->sum('so_buoi'), 'so_hiep' => (int) $tong->so_hiep, 'so_lan' => (int) $tong->so_lan,
            'tong_khoi_luong_kg' => (float) $tong->tong_khoi_luong, 'so_hiep_co_ta' => (int) $tong->so_hiep_co_ta,
            'theo_ngay' => $ngay->map(fn ($n) => ['ngay_tap' => $n->ngay_tap, 'so_buoi' => (int) $n->so_buoi]),
            'theo_bai' => $bai->map(fn ($b) => ['bai_tap_id' => $b->bai_tap_id, 'ten_bai_tap' => $b->ten_bai_tap,
                'muc_ta_cao_nhat' => (float) $b->muc_ta_cao_nhat, 'cac_moc' => $tienDo->where('bai_tap_id', $b->bai_tap_id)->values()])];
    }

    private function phanHoi($data, string $message, ?array $meta = null, int $code = 200)
    {
        return response()->json(['status' => true, 'message' => $message, 'data' => $data, ...($meta ? ['meta' => $meta] : [])], $code)
            ->header('Cache-Control', 'private, no-store');
    }
}
