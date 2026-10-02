<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuaHoSoRequest;
use App\Http\Resources\TaiKhoanResource;
use App\Services\HoSoTaiKhoanService;
use Illuminate\Http\JsonResponse;

class HoSoTaiKhoanController extends Controller
{
    public function update(SuaHoSoRequest $request, HoSoTaiKhoanService $dichVu): JsonResponse
    {
        $taiKhoan = $dichVu->suaHoSo($request->user(), $request->validated());

        return response()->json(['status' => true, 'message' => 'Đã cập nhật hồ sơ.', 'data' => (new TaiKhoanResource($taiKhoan))->resolve($request)]);
    }
}
