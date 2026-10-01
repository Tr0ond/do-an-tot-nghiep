<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DanhSachNhomCoRequest;
use App\Http\Requests\GhiNhomCoRequest;
use App\Http\Resources\NhomCoResource;
use App\Models\NhomCo;
use App\Services\NhomCoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NhomCoController extends Controller
{
    public function index(DanhSachNhomCoRequest $request): JsonResponse
    {
        $duLieu = $request->validated();
        $truyVan = NhomCo::query()->withCount($this->demBai());
        if (isset($duLieu['tu_khoa']) && $duLieu['tu_khoa'] !== '') {
            $tuKhoa = '%'.str_replace(['=', '%', '_'], ['==', '=%', '=_'], $duLieu['tu_khoa']).'%';
            $truyVan->where(fn (Builder $nhom) => $nhom->whereRaw("ten_nhom_co LIKE ? ESCAPE '='", [$tuKhoa])->orWhereRaw("ma_nhom_co LIKE ? ESCAPE '='", [$tuKhoa])->orWhereRaw("ten_nguon LIKE ? ESCAPE '='", [$tuKhoa]));
        }
        if (! empty($duLieu['trang_thai'])) {
            $truyVan->where('trang_thai', $duLieu['trang_thai']);
        }
        $phanTrang = $truyVan->orderBy('ten_nhom_co')->orderBy('id')->paginate($duLieu['per_page'] ?? 12, ['*'], 'page', $duLieu['page'] ?? 1);

        return response()->json(['status' => true, 'message' => 'Lấy danh sách nhóm cơ thành công.', 'data' => NhomCoResource::collection($phanTrang->items())->resolve($request), 'meta' => ['current_page' => $phanTrang->currentPage(), 'per_page' => $phanTrang->perPage(), 'total' => $phanTrang->total(), 'last_page' => $phanTrang->lastPage()]]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->phanHoi($request, NhomCo::findOrFail($id), 'Lấy nhóm cơ thành công.');
    }

    public function store(GhiNhomCoRequest $request, NhomCoService $dichVu): JsonResponse
    {
        return $this->phanHoi($request, $dichVu->taoNhomCo($request->validated()), 'Đã thêm nhóm cơ.', 201);
    }

    public function update(GhiNhomCoRequest $request, int $id, NhomCoService $dichVu): JsonResponse
    {
        return $this->phanHoi($request, $dichVu->suaNhomCo($id, $request->validated()), 'Đã lưu nhóm cơ.');
    }

    public function trangThai(GhiNhomCoRequest $request, int $id, NhomCoService $dichVu): JsonResponse
    {
        return $this->phanHoi($request, $dichVu->datTrangThai($id, $request->validated()), 'Đã cập nhật trạng thái nhóm cơ.');
    }

    private function demBai(): array
    {
        return ['baiTap as so_bai_tap', 'baiTap as so_bai_hoat_dong' => fn (Builder $bai) => $bai->where('trang_thai', 'HOAT_DONG')];
    }

    private function phanHoi(Request $request, NhomCo $nhom, string $thongBao, int $maHttp = 200): JsonResponse
    {
        return response()->json(['status' => true, 'message' => $thongBao, 'data' => (new NhomCoResource($nhom->loadCount($this->demBai())))->resolve($request)], $maHttp);
    }
}
