<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DanhSachBaiTapAdminRequest;
use App\Http\Requests\GhiBaiTapRequest;
use App\Http\Requests\TrangThaiBaiTapRequest;
use App\Http\Resources\BaiTapAdminResource;
use App\Models\BaiTap;
use App\Models\NhomCo;
use App\Services\BaiTapService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BaiTapAdminController extends Controller
{
    public function index(DanhSachBaiTapAdminRequest $request): JsonResponse
    {
        $duLieu = $request->validated();
        $truyVan = BaiTap::with('nhomCo:id,ten_nhom_co,trang_thai')->select(['id', 'nhom_co_id', 'nguon_du_lieu', 'ma_nguon', 'ten_bai_tap', 'ten_tieng_viet', 'dung_cu', 'dung_cu_nguon', 'anh_url', 'ghi_cong_media', 'trang_thai', 'updated_at']);
        if (isset($duLieu['tu_khoa']) && $duLieu['tu_khoa'] !== '') {
            $tuKhoa = '%'.str_replace(['=', '%', '_'], ['==', '=%', '=_'], $duLieu['tu_khoa']).'%';
            $truyVan->where(fn (Builder $bai) => $bai->whereRaw("ten_bai_tap LIKE ? ESCAPE '='", [$tuKhoa])->orWhereRaw("ten_tieng_viet LIKE ? ESCAPE '='", [$tuKhoa])->orWhereRaw("ma_nguon LIKE ? ESCAPE '='", [$tuKhoa]));
        }
        foreach (['nhom_co_id', 'trang_thai'] as $truong) {
            if (! empty($duLieu[$truong])) {
                $truyVan->where($truong, $duLieu[$truong]);
            }
        }
        $phanTrang = $truyVan->orderByDesc('id')->paginate($duLieu['per_page'] ?? 12, ['*'], 'page', $duLieu['page'] ?? 1);

        return response()->json(['status' => true, 'message' => 'Lấy danh sách quản trị thành công.', 'data' => BaiTapAdminResource::collection($phanTrang->items())->resolve($request), 'meta' => ['current_page' => $phanTrang->currentPage(), 'per_page' => $phanTrang->perPage(), 'total' => $phanTrang->total(), 'last_page' => $phanTrang->lastPage()]]);
    }

    public function boLoc(): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Lấy bộ lọc quản trị thành công.', 'data' => [
            'nhom_co' => NhomCo::orderBy('ten_nhom_co')->get(['id', 'ten_nhom_co', 'trang_thai']),
            'dung_cu' => BaiTap::whereNotNull('dung_cu')->select('dung_cu')->distinct()->orderBy('dung_cu')->pluck('dung_cu'),
        ]]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->phanHoi($request, BaiTap::with('nhomCo')->findOrFail($id), 'Lấy bài tập thành công.');
    }

    public function store(GhiBaiTapRequest $request, BaiTapService $dichVu): JsonResponse
    {
        return $this->phanHoi($request, $dichVu->taoBaiTap($request->validated()), 'Đã thêm bài tập.', 201);
    }

    public function update(GhiBaiTapRequest $request, int $id, BaiTapService $dichVu): JsonResponse
    {
        return $this->phanHoi($request, $dichVu->suaBaiTap($id, $request->validated()), 'Đã lưu nội dung bài tập.');
    }

    public function trangThai(TrangThaiBaiTapRequest $request, int $id, BaiTapService $dichVu): JsonResponse
    {
        return $this->phanHoi($request, $dichVu->datTrangThai($id, $request->validated()), 'Đã cập nhật trạng thái bài tập.');
    }

    private function phanHoi(Request $request, BaiTap $baiTap, string $thongBao, int $maHttp = 200): JsonResponse
    {
        return response()->json(['status' => true, 'message' => $thongBao, 'data' => (new BaiTapAdminResource($baiTap))->resolve($request)], $maHttp);
    }
}
