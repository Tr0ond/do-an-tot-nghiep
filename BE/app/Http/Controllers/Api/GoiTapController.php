<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DanhSachGoiTapRequest;
use App\Http\Requests\GhiGoiTapRequest;
use App\Http\Resources\GoiTapResource;
use App\Models\GoiTap;
use App\Services\GoiTapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoiTapController extends Controller
{
    public function index(DanhSachGoiTapRequest $request): JsonResponse
    {
        $duLieu = $request->validated();
        $truyVan = GoiTap::query();
        $laAdmin = $request->is('api/v1/admin/*');
        if (! $laAdmin) {
            $truyVan->dangHienThi();
        } elseif (! empty($duLieu['trang_thai'])) {
            $truyVan->where('trang_thai', $duLieu['trang_thai']);
        }
        if (! empty($duLieu['tu_khoa'])) {
            $tuKhoa = '%'.str_replace(['=', '%', '_'], ['==', '=%', '=_'], $duLieu['tu_khoa']).'%';
            $truyVan->whereRaw("ten_goi LIKE ? ESCAPE '='", [$tuKhoa]);
        }
        if (! empty($duLieu['loai_goi'])) {
            $truyVan->where('so_buoi_pt', $duLieu['loai_goi'] === 'CHATBOT' ? '=' : '>', 0);
        }
        $phanTrang = $truyVan->orderBy('id', $laAdmin ? 'desc' : 'asc')->paginate($duLieu['per_page'] ?? 12, ['*'], 'page', $duLieu['page'] ?? 1);

        return response()->json(['status' => true, 'message' => 'Lấy danh sách gói tập thành công.', 'data' => GoiTapResource::collection($phanTrang->items())->resolve($request), 'meta' => ['current_page' => $phanTrang->currentPage(), 'per_page' => $phanTrang->perPage(), 'total' => $phanTrang->total(), 'last_page' => $phanTrang->lastPage()]]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $truyVan = GoiTap::query();
        if (! $request->is('api/v1/admin/*')) {
            $truyVan->dangHienThi();
        }

        return $this->phanHoi($request, $truyVan->findOrFail($id), 'Lấy gói tập thành công.');
    }

    public function store(GhiGoiTapRequest $request, GoiTapService $dichVu): JsonResponse
    {
        $goi = $dichVu->taoGoiTap($request->validated());

        return $this->phanHoi($request, $goi, $goi->wasRecentlyCreated ? 'Đã thêm gói tập.' : 'Yêu cầu này đã tạo gói tập thành công.', $goi->wasRecentlyCreated ? 201 : 200);
    }

    public function update(GhiGoiTapRequest $request, int $id, GoiTapService $dichVu): JsonResponse
    {
        return $this->phanHoi($request, $dichVu->suaGoiTap($id, $request->validated()), 'Đã lưu gói tập.');
    }

    public function trangThai(GhiGoiTapRequest $request, int $id, GoiTapService $dichVu): JsonResponse
    {
        return $this->phanHoi($request, $dichVu->datTrangThai($id, $request->validated()), 'Đã cập nhật trạng thái bán.');
    }

    private function phanHoi(Request $request, GoiTap $goi, string $thongBao, int $maHttp = 200): JsonResponse
    {
        return response()->json(['status' => true, 'message' => $thongBao, 'data' => (new GoiTapResource($goi))->resolve($request)], $maHttp);
    }
}
