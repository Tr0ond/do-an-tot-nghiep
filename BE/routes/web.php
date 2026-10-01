<?php

use App\Http\Controllers\Api\XacThucController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(config('app.frontend_url'));
});

Route::post('/dang-ky', [XacThucController::class, 'dangKy'])->middleware('throttle:dang-ky');
Route::post('/dang-nhap', [XacThucController::class, 'dangNhap'])->middleware('throttle:dang-nhap');
Route::post('/dang-xuat', [XacThucController::class, 'dangXuat'])->middleware('auth:web');
