<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PhanCongRequest;
use App\Models\DangKyGoiTap;
use App\Models\HoSoHuanLuyenVien;
use App\Models\HoSoKhachHang;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use App\Services\PhanCongService;
use Illuminate\Http\Request;

class PhanCongController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['page' => 'sometimes|integer|min:1']);
        $khach = HoSoKhachHang::with('taiKhoan')->where(function ($q) {
            $q->whereIn('id', DangKyGoiTap::where('trang_thai', 'DANG_SU_DUNG')->where('het_han_luc', '>', now())->where('so_buoi_con_lai', '>', 0)->select('khach_hang_id'))
                ->orWhereIn('id', PhanCongHuanLuyenVien::whereNull('ket_thuc_luc')->select('khach_hang_id'));
        })->whereHas('taiKhoan', fn ($q) => $q->where('trang_thai', TaiKhoan::HOAT_DONG))->orderBy('id')->paginate(12);
        $phanCong = PhanCongHuanLuyenVien::with('pt.taiKhoan')->whereIn('khach_hang_id', collect($khach->items())->pluck('id'))->whereNull('ket_thuc_luc')->get()->keyBy('khach_hang_id');

        return response()->json(['status' => true, 'message' => 'Đã tải khách có gói PT.', 'data' => collect($khach->items())->map(function ($k) use ($phanCong) {
            $p = $phanCong->get($k->id);

            return ['khach_hang_id' => $k->id, 'ho_ten' => $k->taiKhoan->ho_ten, 'phan_cong_hien_tai_id' => $p?->id, 'pt' => $p ? ['id' => $p->huan_luyen_vien_id, 'ho_ten' => $p->pt->taiKhoan->ho_ten] : null];
        }), 'meta' => ['current_page' => $khach->currentPage(), 'last_page' => $khach->lastPage(), 'total' => $khach->total()]], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function pt(Request $request)
    {
        $request->validate(['page' => 'sometimes|integer|min:1']);
        $ds = HoSoHuanLuyenVien::with('taiKhoan')->whereHas('taiKhoan', fn ($q) => $q->where('trang_thai', TaiKhoan::HOAT_DONG))->orderBy('id')->paginate(30);

        return response()->json(['status' => true, 'message' => 'Đã tải PT.', 'data' => collect($ds->items())->map(fn ($p) => ['id' => $p->id, 'ho_ten' => $p->taiKhoan->ho_ten, 'chuyen_mon' => $p->chuyen_mon]), 'meta' => ['current_page' => $ds->currentPage(), 'last_page' => $ds->lastPage()]]);
    }

    public function store(PhanCongRequest $request, PhanCongService $dichVu)
    {
        $phanCong = $dichVu->phanCong($request->user(), $request->validated());

        return response()->json(['status' => true, 'message' => 'Đã lưu phân công PT.', 'data' => ['id' => $phanCong->id]]);
    }
}
