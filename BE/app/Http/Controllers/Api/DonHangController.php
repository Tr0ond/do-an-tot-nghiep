<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DoiSoatRequest;
use App\Http\Requests\MuaGoiRequest;
use App\Http\Resources\DonHangResource;
use App\Models\DangKyGoiTap;
use App\Models\PhanCongHuanLuyenVien;
use App\Services\MuaGoiService;
use App\Services\PayosService;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class DonHangController extends Controller
{
    public function index(Request $request)
    {
        $duLieu = $request->validate(['page' => 'sometimes|integer|min:1', 'trang_thai' => 'nullable|in:CHO_THANH_TOAN,DANG_SU_DUNG,HET_HAN,HET_HAN_THANH_TOAN,DA_HUY,CAN_DOI_SOAT']);
        $truyVan = $this->truyVan($request)->with('khachHang.taiKhoan');
        if (! empty($duLieu['trang_thai'])) {
            $trangThai = $duLieu['trang_thai'];
            if ($trangThai === 'HET_HAN') {
                $truyVan->where(fn ($q) => $q->where('trang_thai', 'HET_HAN')->orWhere(fn ($q) => $q->where('trang_thai', 'DANG_SU_DUNG')->where('het_han_luc', '<=', now())));
            } elseif ($trangThai === 'HET_HAN_THANH_TOAN') {
                $truyVan->where(fn ($q) => $q->where('trang_thai', $trangThai)->orWhere(fn ($q) => $q->where('trang_thai', 'CHO_THANH_TOAN')->where('han_thanh_toan', '<=', now())));
            } else {
                $truyVan->where('trang_thai', $trangThai);
                if ($trangThai === 'CHO_THANH_TOAN') {
                    $truyVan->where('han_thanh_toan', '>', now());
                }
                if ($trangThai === 'DANG_SU_DUNG') {
                    $truyVan->where('het_han_luc', '>', now());
                }
            }
        }
        $phanTrang = $truyVan->latest('id')->paginate(12);

        return response()->json(['status' => true, 'message' => 'Đã tải đơn hàng.', 'data' => DonHangResource::collection($phanTrang->items())->resolve($request), 'meta' => ['current_page' => $phanTrang->currentPage(), 'last_page' => $phanTrang->lastPage(), 'total' => $phanTrang->total()]], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function store(MuaGoiRequest $request, MuaGoiService $dichVu)
    {
        return $this->phanHoi($request, $dichVu->taoDon($request->user(), $request->validated()));
    }

    public function show(Request $request, int $id)
    {
        return $this->phanHoi($request, $this->truyVan($request)->findOrFail($id));
    }

    public function taoLink(Request $request, int $id, MuaGoiService $dichVu)
    {
        return $this->phanHoi($request, $dichVu->taoLink($this->truyVan($request)->findOrFail($id), $request->user()->currentAccessToken() instanceof PersonalAccessToken));
    }

    public function dongBo(Request $request, int $id, MuaGoiService $dichVu)
    {
        return $this->phanHoi($request, $dichVu->dongBo($this->truyVan($request)->findOrFail($id)));
    }

    public function goiCuaToi(Request $request, MuaGoiService $dichVu)
    {
        $khachId = $request->user()->hoSoKhachHang->id;
        $goi = $dichVu->goiHieuLuc($khachId)->first();
        $phanCong = PhanCongHuanLuyenVien::with('pt.taiKhoan')->where('khach_hang_id', $khachId)->whereNull('ket_thuc_luc')->first();

        return response()->json(['status' => true, 'message' => 'Đã tải gói của bạn.', 'data' => ['goi' => $goi ? (new DonHangResource($goi))->resolve($request) : null, 'pt' => $phanCong ? ['ho_ten' => $phanCong->pt->taiKhoan->ho_ten, 'chuyen_mon' => $phanCong->pt->chuyen_mon] : null]], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function webhook(Request $request, PayosService $payos, MuaGoiService $dichVu)
    {
        $noiDung = $request->validate(['data' => 'required|array', 'signature' => 'required|string', 'data.orderCode' => 'required|integer|min:1', 'data.paymentLinkId' => 'required|string|max:191', 'data.reference' => 'required|string|max:191', 'data.amount' => 'required|integer|min:1', 'data.currency' => 'required|in:VND', 'data.code' => 'required|string']);
        // Ký toàn bộ data gốc, kể cả các field không sử dụng trong nghiệp vụ.
        abort_unless($payos->hopLe($request->input('data'), $noiDung['signature']), 400, 'Chữ ký không hợp lệ.');
        $don = DangKyGoiTap::where('ma_don_payos', $noiDung['data']['orderCode'])->first();
        if ($don && $noiDung['data']['code'] === '00') {
            $dichVu->dongBo($don, $noiDung['data']);
        }

        return response()->json(['status' => true, 'message' => 'Đã nhận thông báo.']);
    }

    public function doiSoat(DoiSoatRequest $request, int $id, MuaGoiService $dichVu)
    {
        $ketQua = $dichVu->doiSoat($request->user(), $id, $request->validated());

        return response()->json(['status' => true, 'message' => 'Đã ghi nhận kết quả hoàn tiền thủ công.', 'data' => ['id' => $ketQua->id]]);
    }

    private function truyVan(Request $request)
    {
        $truyVan = DangKyGoiTap::query();
        if (! $request->is('api/v1/admin/*')) {
            $truyVan->where('khach_hang_id', $request->user()->hoSoKhachHang->id);
        }

        return $truyVan;
    }

    private function phanHoi(Request $request, DangKyGoiTap $don)
    {
        return response()->json(['status' => true, 'message' => 'Đã tải đơn hàng.', 'data' => (new DonHangResource($don->load(['thanhToan', 'khachHang.taiKhoan'])))->resolve($request)], 200, ['Cache-Control' => 'private, no-store']);
    }
}
