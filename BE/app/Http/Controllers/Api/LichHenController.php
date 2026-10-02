<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KhungGioRequest;
use App\Http\Requests\LichHenRequest;
use App\Http\Requests\ThaoTacLichHenRequest;
use App\Models\KhungGioHuanLuyenVien;
use App\Models\LichHenHuanLuyen;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use App\Services\LichHenService;
use App\Services\MuaGoiService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class LichHenController extends Controller
{
    private function tra($data, string $message = 'Đã tải lịch huấn luyện.', array $meta = [])
    {
        return response()->json(['status' => true, 'message' => $message, 'data' => $data, 'meta' => $meta], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function index(Request $request, LichHenService $dichVu)
    {
        $request->validate(['page' => 'sometimes|integer|min:1', 'trang_thai' => 'sometimes|in:CHO_XAC_NHAN,DA_XAC_NHAN,HOAN_THANH,VANG_MAT,DA_HUY,HET_HAN,QUA_HAN_XAC_NHAN', 'ngay' => 'sometimes|date_format:Y-m-d']);
        $q = $dichVu->phamVi($request->user())->with(['khach.taiKhoan', 'pt.taiKhoan']);
        if ($request->filled('ngay')) {
            $ngay = CarbonImmutable::parse($request->ngay, 'Asia/Ho_Chi_Minh')->startOfDay()->utc();
            $q->where('bat_dau_luc', '>=', $ngay)->where('bat_dau_luc', '<', $ngay->addDay());
        }
        // Trạng thái đọc luôn tính deadline, không phụ thuộc scheduler đã chạy.
        if ($request->filled('trang_thai')) {
            $s = $request->trang_thai;
            if ($s === 'HET_HAN') {
                $q->where(fn ($p) => $p->where('trang_thai', 'HET_HAN')->orWhere(fn ($p) => $p->where('trang_thai', 'CHO_XAC_NHAN')->where('han_xac_nhan_dat_lich', '<=', now()->format('Y-m-d H:i:s.u'))));
            } elseif ($s === 'QUA_HAN_XAC_NHAN') {
                $q->where(fn ($p) => $p->where('trang_thai', $s)->orWhere(fn ($p) => $p->where('trang_thai', 'DA_XAC_NHAN')->where('ket_thuc_luc', '<=', now()->subHours(24)->format('Y-m-d H:i:s.u'))));
            } else {
                $q->where('trang_thai', $s);
                if ($s === 'CHO_XAC_NHAN') {
                    $q->where(fn ($p) => $p->whereNull('han_xac_nhan_dat_lich')->orWhere('han_xac_nhan_dat_lich', '>', now()->format('Y-m-d H:i:s.u')));
                }
                if ($s === 'DA_XAC_NHAN') {
                    $q->where('ket_thuc_luc', '>', now()->subHours(24)->format('Y-m-d H:i:s.u'));
                }
            }
        }
        $ds = $q->orderByDesc('bat_dau_luc')->orderByDesc('id')->paginate(12);

        return $this->tra(collect($ds->items())->map(fn ($l) => $dichVu->duLieu($l, $request->user())), meta: ['current_page' => $ds->currentPage(), 'last_page' => $ds->lastPage(), 'total' => $ds->total()]);
    }

    public function show(Request $request, int $id, LichHenService $dichVu)
    {
        return $this->tra($dichVu->duLieu($dichVu->phamVi($request->user())->findOrFail($id), $request->user()));
    }

    public function store(LichHenRequest $request, LichHenService $dichVu)
    {
        return $this->tra($dichVu->duLieu($dichVu->datLich($request->user(), $request->validated()), $request->user()), 'Đã gửi yêu cầu đặt lịch. Chờ PT xác nhận.');
    }

    public function thaoTac(ThaoTacLichHenRequest $request, int $id, string $hanhDong, LichHenService $dichVu)
    {
        return $this->tra($dichVu->duLieu($dichVu->thaoTac($request->user(), $id, $hanhDong, $request->validated('ly_do') ?? ''), $request->user()), 'Đã cập nhật lịch hẹn.');
    }

    public function khungGio(Request $request, MuaGoiService $muaGoi)
    {
        $request->validate(['ngay' => 'required|date_format:Y-m-d', 'page' => 'sometimes|integer|min:1']);
        $ngay = CarbonImmutable::parse($request->ngay, 'Asia/Ho_Chi_Minh')->startOfDay()->utc();
        $q = KhungGioHuanLuyenVien::where('bat_dau_luc', '>=', $ngay)->where('bat_dau_luc', '<', $ngay->addDay());
        $nguoi = $request->user();
        $pt = null;
        $goi = null;
        $lyDo = '';
        if ($nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN) {
            $q->whereIn('huan_luyen_vien_id', $nguoi->hoSoHuanLuyenVien()->select('id'));
        } else {
            $khach = $nguoi->hoSoKhachHang()->firstOrFail();
            $p = PhanCongHuanLuyenVien::with('pt.taiKhoan')->where('khach_hang_id', $khach->id)->whereNull('ket_thuc_luc')->first();
            $goi = $muaGoi->goiHieuLuc($khach->id)->first();
            if (! $p) {
                $lyDo = 'Bạn chưa được phân công PT. Vui lòng liên hệ quản trị viên.';
            } elseif ($p->pt->taiKhoan->trang_thai !== TaiKhoan::HOAT_DONG) {
                $lyDo = 'PT phụ trách hiện không hoạt động. Vui lòng liên hệ quản trị viên.';
            } elseif (! $goi || $goi->so_buoi_con_lai < 1) {
                $lyDo = 'Bạn cần gói PT còn buổi và còn hiệu lực để đặt lịch.';
            }
            if ($lyDo) {
                return $this->tra([], meta: ['ly_do' => $lyDo, 'current_page' => 1, 'last_page' => 1]);
            }
            $pt = ['ho_ten' => $p->pt->taiKhoan->ho_ten, 'chuyen_mon' => $p->pt->chuyen_mon];
            $q->where('huan_luyen_vien_id', $p->huan_luyen_vien_id)->where('trang_thai', 'MO')->where('bat_dau_luc', '>=', now()->addHours(4)->format('Y-m-d H:i:s.u'))->where('ket_thuc_luc', '<=', $goi->het_han_luc->format('Y-m-d H:i:s.u'));
            $q->whereNotExists(function ($l) use ($khach) {
                $l->selectRaw('1')->from('lich_hen_huan_luyen')->where(fn ($l) => $l->where('khach_hang_id', $khach->id)->orWhereColumn('huan_luyen_vien_id', 'khung_gio_huan_luyen_vien.huan_luyen_vien_id'))
                    ->where(fn ($l) => $l->where('trang_thai', 'DA_XAC_NHAN')->orWhere(fn ($l) => $l->where('trang_thai', 'CHO_XAC_NHAN')->where(fn ($l) => $l->whereNull('han_xac_nhan_dat_lich')->orWhere('han_xac_nhan_dat_lich', '>', now()->format('Y-m-d H:i:s.u')))))
                    ->whereColumn('bat_dau_luc', '<', 'khung_gio_huan_luyen_vien.ket_thuc_luc')->whereColumn('ket_thuc_luc', '>', 'khung_gio_huan_luyen_vien.bat_dau_luc');
            });
        }
        $ds = $q->orderBy('bat_dau_luc')->paginate(24);
        $ids = collect($ds->items())->pluck('id');
        $giu = LichHenHuanLuyen::whereIn('khung_gio_id', $ids)->where(fn ($q) => $q->where('trang_thai', 'DA_XAC_NHAN')->orWhere(fn ($q) => $q->where('trang_thai', 'CHO_XAC_NHAN')->where(fn ($q) => $q->whereNull('han_xac_nhan_dat_lich')->orWhere('han_xac_nhan_dat_lich', '>', now()->format('Y-m-d H:i:s.u')))))->pluck('khung_gio_id');

        return $this->tra(collect($ds->items())->map(fn ($s) => ['id' => $s->id, 'bat_dau_luc' => $s->bat_dau_luc->toIso8601String(), 'ket_thuc_luc' => $s->ket_thuc_luc->toIso8601String(), 'trang_thai' => $s->trang_thai, 'dang_giu' => $giu->contains($s->id), 'co_the_doi' => $s->bat_dau_luc->greaterThan(now()) && ! $giu->contains($s->id)]), meta: ['pt' => $pt, 'so_buoi_con_lai' => $goi?->so_buoi_con_lai, 'ly_do' => $lyDo, 'current_page' => $ds->currentPage(), 'last_page' => $ds->lastPage(), 'total' => $ds->total()]);
    }

    public function taoKhung(KhungGioRequest $request, LichHenService $dichVu)
    {
        return $this->tra(['id' => $dichVu->taoKhungGio($request->user(), $request->validated())->id], 'Đã mở khung giờ 60 phút.');
    }

    public function doiKhung(Request $request, int $id, LichHenService $dichVu)
    {
        $d = $request->validate(['trang_thai' => 'required|in:MO,DONG']);

        return $this->tra(['id' => $dichVu->doiKhungGio($request->user(), $id, $d['trang_thai'])->id], 'Đã cập nhật khung giờ.');
    }
}
