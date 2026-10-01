<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HoSoKhachHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class HoSoKhachHangController extends Controller
{
    public function show(HoSoKhachHang $hoSoKhachHang): JsonResponse
    {
        Gate::authorize('view', $hoSoKhachHang);

        return response()->json([
            'status' => true,
            'message' => 'Lấy hồ sơ thành công.',
            'data' => [
                ...$hoSoKhachHang->toArray(),
                'ho_ten' => $hoSoKhachHang->taiKhoan->ho_ten,
            ],
        ]);
    }
}
