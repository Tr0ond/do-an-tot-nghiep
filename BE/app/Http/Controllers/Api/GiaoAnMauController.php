<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DanhSachGiaoAnMauRequest;
use App\Http\Requests\GhiGiaoAnMauRequest;
use App\Http\Resources\GiaoAnMauResource;
use App\Models\GiaoAnMau;
use App\Services\GiaoAnMauService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GiaoAnMauController extends Controller
{
    public function index(DanhSachGiaoAnMauRequest $request): JsonResponse
    {
        $duLieu = $request->validated();
        $truyVan = GiaoAnMau::query()->withCount('cacBaiTap');
        if (! $request->is('api/v1/admin/*')) {
            $truyVan->where('trang_thai', 'DA_DUYET');
        } elseif (! empty($duLieu['trang_thai'])) {
            $truyVan->where('trang_thai', $duLieu['trang_thai']);
        }
        if (! empty($duLieu['tu_khoa'])) {
            $tuKhoa = '%'.str_replace(['=', '%', '_'], ['==', '=%', '=_'], $duLieu['tu_khoa']).'%';
            $truyVan->where(fn ($q) => $q->whereRaw("ten_giao_an LIKE ? ESCAPE '='", [$tuKhoa])->orWhereRaw("muc_tieu LIKE ? ESCAPE '='", [$tuKhoa]));
        }
        $phanTrang = $truyVan->orderByDesc('id')->paginate($duLieu['per_page'] ?? 12, ['*'], 'page', $duLieu['page'] ?? 1);

        return response()->json(['status' => true, 'message' => 'Đã tải giáo án mẫu.', 'data' => GiaoAnMauResource::collection($phanTrang->items())->resolve($request), 'meta' => ['current_page' => $phanTrang->currentPage(), 'last_page' => $phanTrang->lastPage(), 'per_page' => $phanTrang->perPage(), 'total' => $phanTrang->total()]]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $truyVan = GiaoAnMau::query();
        if (! $request->is('api/v1/admin/*')) {
            $truyVan->where('trang_thai', 'DA_DUYET');
        }

        // Giữ parent ổn định đến khi đọc xong các dòng, tránh PT đọc nội dung nháp của một lần sửa đồng thời.
        return DB::transaction(fn () => $this->phanHoi($request, $truyVan->sharedLock()->findOrFail($id), 'Đã tải giáo án.'));
    }

    public function store(GhiGiaoAnMauRequest $request, GiaoAnMauService $dichVu): JsonResponse
    {
        $giaoAn = $dichVu->taoGiaoAn($request->validated(), $request->user()->id);

        return $this->phanHoi($request, $giaoAn, 'Đã lưu giáo án nháp.', $giaoAn->wasRecentlyCreated ? 201 : 200);
    }

    public function update(GhiGiaoAnMauRequest $request, int $id, GiaoAnMauService $dichVu): JsonResponse
    {
        return $this->phanHoi($request, $dichVu->suaGiaoAn($id, $request->validated()), 'Đã lưu nội dung giáo án.');
    }

    public function trangThai(GhiGiaoAnMauRequest $request, int $id, GiaoAnMauService $dichVu): JsonResponse
    {
        return $this->phanHoi($request, $dichVu->datTrangThai($id, $request->validated(), $request->user()->id), 'Đã cập nhật trạng thái giáo án.');
    }

    private function phanHoi(Request $request, GiaoAnMau $giaoAn, string $thongBao, int $maHttp = 200): JsonResponse
    {
        $giaoAn->load('cacBaiTap.baiTap.nhomCo');

        return response()->json(['status' => true, 'message' => $thongBao, 'data' => (new GiaoAnMauResource($giaoAn))->resolve($request)], $maHttp);
    }
}
