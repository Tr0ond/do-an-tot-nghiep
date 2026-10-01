<?php

use App\Http\Controllers\Api\BaiTapController;
use App\Http\Controllers\Api\HoSoKhachHangController;
use App\Http\Controllers\Api\TaiKhoanController;
use App\Http\Controllers\Api\XacThucController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/health', function () {
    return response()->json([
        'status' => true,
        'message' => 'Backend hoạt động.',
        'data' => [
            'ung_dung' => config('app.name'),
            'thoi_gian' => now()->toIso8601String(),
        ],
    ]);
});

Route::prefix('v1')->group(function () {
    Route::get('/bai-tap/bo-loc', [BaiTapController::class, 'boLoc']);
    Route::get('/bai-tap', [BaiTapController::class, 'index']);
    Route::get('/bai-tap/{id}', [BaiTapController::class, 'show'])->whereNumber('id');
});

Route::prefix('v1')->middleware(['auth:sanctum', 'tai_khoan_hoat_dong'])->group(function () {
    Route::get('/me', [XacThucController::class, 'me']);
    Route::get('/khach-hang/ho-so/{hoSoKhachHang}', [HoSoKhachHangController::class, 'show']);
    Route::prefix('admin')->middleware('vai_tro:ADMIN')->group(function () {
        Route::get('/tai-khoan', [TaiKhoanController::class, 'index']);
        Route::post('/tai-khoan', [TaiKhoanController::class, 'store']);
    });
    Route::get('/pt/ho-so', [XacThucController::class, 'me'])->middleware('vai_tro:HUAN_LUYEN_VIEN');
});
