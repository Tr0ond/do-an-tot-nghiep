<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaoTaiKhoanRequest;
use App\Http\Requests\TrangThaiTaiKhoanRequest;
use App\Http\Resources\TaiKhoanResource;
use App\Models\TaiKhoan;
use App\Services\HoSoTaiKhoanService;
use App\Services\TaiKhoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaiKhoanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $phanTrang = TaiKhoan::query()->latest('id')->paginate(20);

        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách tài khoản thành công.',
            'data' => TaiKhoanResource::collection($phanTrang->items())->resolve($request),
            'meta' => ['current_page' => $phanTrang->currentPage(), 'per_page' => $phanTrang->perPage(), 'total' => $phanTrang->total(), 'last_page' => $phanTrang->lastPage()],
        ]);
    }

    public function store(TaoTaiKhoanRequest $request, TaiKhoanService $dichVu): JsonResponse
    {
        $taiKhoan = $dichVu->taoTaiKhoan($request->validated(), $request->validated('vai_tro'));

        return response()->json(['status' => true, 'message' => 'Tạo tài khoản thành công.', 'data' => (new TaiKhoanResource($taiKhoan))->resolve($request)], 201);
    }

    public function trangThai(TrangThaiTaiKhoanRequest $request, int $id, HoSoTaiKhoanService $dichVu): JsonResponse
    {
        $taiKhoan = $dichVu->datTrangThai($request->user(), $id, $request->validated());

        return response()->json(['status' => true, 'message' => $taiKhoan->trang_thai === TaiKhoan::BI_KHOA ? 'Đã khóa tài khoản và thu hồi phiên đăng nhập.' : 'Đã mở khóa tài khoản. Người dùng có thể đăng nhập lại.', 'data' => (new TaiKhoanResource($taiKhoan))->resolve($request)]);
    }
}
