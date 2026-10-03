<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DanhSachBaiTapRequest;
use App\Http\Resources\BaiTapResource;
use App\Http\Resources\ChiTietBaiTapResource;
use App\Models\BaiTap;
use App\Models\NhomCo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BaiTapController extends Controller
{
    public function index(DanhSachBaiTapRequest $request): JsonResponse
    {
        $duLieu = $request->validated();
        $truyVan = BaiTap::dangHienThi()->with('nhomCo:id,ten_nhom_co')
            ->select(['id', 'nhom_co_id', 'ma_nguon', 'ten_bai_tap', 'ten_tieng_viet', 'dung_cu', 'dung_cu_nguon', 'anh_url', 'gif_url', 'ghi_cong_media']);
        if (isset($duLieu['tu_khoa']) && $duLieu['tu_khoa'] !== '') {
            // Escape ký tự LIKE để từ khóa không vô tình thành wildcard.
            $tuKhoa = '%'.str_replace(['=', '%', '_'], ['==', '=%', '=_'], $duLieu['tu_khoa']).'%';
            $truyVan->where(fn (Builder $bai) => $bai->whereRaw("ten_bai_tap LIKE ? ESCAPE '='", [$tuKhoa])
                ->orWhereRaw("ten_tieng_viet LIKE ? ESCAPE '='", [$tuKhoa])->orWhereRaw("ma_nguon LIKE ? ESCAPE '='", [$tuKhoa]));
        }
        if (! empty($duLieu['nhom_co_id'])) {
            $truyVan->where('nhom_co_id', $duLieu['nhom_co_id']);
        }
        if (! empty($duLieu['dung_cu_nguon'])) {
            $truyVan->where('dung_cu_nguon', $duLieu['dung_cu_nguon']);
        }
        $phanTrang = $truyVan->orderBy('id')->paginate($duLieu['per_page'] ?? 12, ['*'], 'page', $duLieu['page'] ?? 1);

        return response()->json([
            'status' => true, 'message' => 'Lấy danh sách bài tập thành công.',
            'data' => BaiTapResource::collection($phanTrang->items())->resolve($request),
            'meta' => ['current_page' => $phanTrang->currentPage(), 'per_page' => $phanTrang->perPage(), 'total' => $phanTrang->total(), 'last_page' => $phanTrang->lastPage()],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $baiTap = BaiTap::dangHienThi()->with('nhomCo:id,ten_nhom_co')->findOrFail($id);

        return response()->json(['status' => true, 'message' => 'Lấy bài tập thành công.', 'data' => (new ChiTietBaiTapResource($baiTap))->resolve($request)]);
    }

    public function boLoc(): JsonResponse
    {
        $nhomCo = NhomCo::where('trang_thai', 'HOAT_DONG')->select('id', 'ten_nhom_co')
            ->withCount(['baiTap as so_bai_tap' => fn (Builder $bai) => $bai->where('trang_thai', 'HOAT_DONG')])
            ->orderBy('ten_nhom_co')->get(['id', 'ten_nhom_co']);
        $dungCu = BaiTap::dangHienThi()->whereNotNull('dung_cu_nguon')->whereNotNull('dung_cu')
            ->select('dung_cu_nguon', 'dung_cu')->distinct()->orderBy('dung_cu')->get();

        return response()->json(['status' => true, 'message' => 'Lấy bộ lọc thành công.', 'data' => ['nhom_co' => $nhomCo, 'dung_cu' => $dungCu]]);
    }
}
