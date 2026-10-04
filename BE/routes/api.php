<?php

use App\Http\Controllers\Api\BaiTapAdminController;
use App\Http\Controllers\Api\BaiTapController;
use App\Http\Controllers\Api\BaoCaoController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ChiSoCoTheController;
use App\Http\Controllers\Api\DonHangController;
use App\Http\Controllers\Api\GiaoAnMauController;
use App\Http\Controllers\Api\GoiTapController;
use App\Http\Controllers\Api\HoSoKhachHangController;
use App\Http\Controllers\Api\HoSoTaiKhoanController;
use App\Http\Controllers\Api\KeHoachTapController;
use App\Http\Controllers\Api\LichHenController;
use App\Http\Controllers\Api\NhatKyTapController;
use App\Http\Controllers\Api\NhomCoController;
use App\Http\Controllers\Api\PhanCongController;
use App\Http\Controllers\Api\TaiKhoanController;
use App\Http\Controllers\Api\TaiLieuTuVanController;
use App\Http\Controllers\Api\ThongBaoController;
use App\Http\Controllers\Api\TongQuanController;
use App\Http\Controllers\Api\XacThucController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

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
    Route::post('/payos/webhook', [DonHangController::class, 'webhook'])->withoutMiddleware(EnsureFrontendRequestsAreStateful::class)->middleware('throttle:120,1');
    Route::get('/goi-tap', [GoiTapController::class, 'index']);
    Route::get('/faq', [TaiLieuTuVanController::class, 'faq'])->middleware('throttle:60,1');
    Route::get('/goi-tap/{id}', [GoiTapController::class, 'show'])->whereNumber('id');
    Route::get('/bai-tap/bo-loc', [BaiTapController::class, 'boLoc']);
    Route::get('/bai-tap', [BaiTapController::class, 'index']);
    Route::get('/bai-tap/{id}', [BaiTapController::class, 'show'])->whereNumber('id');
});

Route::prefix('v1')->middleware(['auth:sanctum', 'tai_khoan_hoat_dong'])->group(function () {
    Route::prefix('khach-hang/chatbot')->middleware('vai_tro:KHACH_HANG')->group(function () {
        Route::get('/hoi-thoai', [ChatbotController::class, 'index']);
        Route::post('/hoi-thoai', [ChatbotController::class, 'store'])->middleware('throttle:20,1');
        Route::get('/hoi-thoai/{id}', [ChatbotController::class, 'show'])->whereNumber('id');
        Route::post('/hoi-thoai/{id}/tin-nhan', [ChatbotController::class, 'gui'])->whereNumber('id')->middleware('throttle:6,1');
    });
    Route::get('/thong-bao', [ThongBaoController::class, 'index']);
    Route::post('/thong-bao/da-doc-tat-ca', [ThongBaoController::class, 'daDocTatCa'])->middleware('throttle:60,1');
    Route::post('/thong-bao/{id}/da-doc', [ThongBaoController::class, 'daDoc'])->whereUuid('id')->middleware('throttle:120,1');
    Route::middleware('vai_tro:KHACH_HANG,HUAN_LUYEN_VIEN')->group(function () {
        Route::get('/hoi-thoai', [ChatController::class, 'index']);
        Route::get('/hoi-thoai/{id}/tin-nhan', [ChatController::class, 'show'])->whereNumber('id');
        Route::get('/hoi-thoai/{id}/tin-nhan/{tinId}/anh/{viTri}', [ChatController::class, 'anh'])->whereNumber(['id', 'tinId', 'viTri']);
        Route::post('/hoi-thoai/{id}/tin-nhan', [ChatController::class, 'store'])->whereNumber('id')->middleware('throttle:chat-gui');
        Route::post('/hoi-thoai/{id}/da-doc', [ChatController::class, 'daDoc'])->whereNumber('id')->name('chat.da-doc')->middleware('throttle:120,1');
    });
    Route::get('/me', [XacThucController::class, 'me']);
    Route::prefix('khach-hang')->middleware('vai_tro:KHACH_HANG')->group(function () {
        Route::get('/chi-so-co-the', [ChiSoCoTheController::class, 'index']);
        Route::post('/chi-so-co-the', [ChiSoCoTheController::class, 'store'])->middleware('throttle:30,1');
        Route::put('/chi-so-co-the/{id}', [ChiSoCoTheController::class, 'update'])->whereNumber('id')->middleware('throttle:30,1');
        Route::get('/lich-tap', [NhatKyTapController::class, 'index']);
        Route::post('/lich-tap', [NhatKyTapController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/lich-tap/{id}', [NhatKyTapController::class, 'show'])->whereNumber('id');
        Route::put('/lich-tap/{id}', [NhatKyTapController::class, 'update'])->whereNumber('id')->middleware('throttle:60,1');
        Route::post('/lich-tap/{id}/{hanhDong}', [NhatKyTapController::class, 'thaoTac'])->whereNumber('id')->where('hanhDong', 'bat-dau|hoan-thanh|huy')->middleware('throttle:60,1');
        Route::get('/ke-hoach', [KeHoachTapController::class, 'index']);
        Route::post('/ke-hoach', [KeHoachTapController::class, 'store'])->middleware('throttle:30,1');
        Route::put('/ke-hoach/{id}', [KeHoachTapController::class, 'update'])->whereNumber('id')->middleware('throttle:30,1');
        Route::get('/ke-hoach/{id}', [KeHoachTapController::class, 'show'])->whereNumber('id');
        Route::post('/ke-hoach/{id}/{hanhDong}', [KeHoachTapController::class, 'thaoTac'])->whereNumber('id')->where('hanhDong', 'xac-nhan|ap-dung|luu-tru|huy|an|hien-lai')->middleware('throttle:30,1');
        Route::get('/khung-gio', [LichHenController::class, 'khungGio']);
        Route::get('/lich-hen', [LichHenController::class, 'index']);
        Route::get('/lich-hen/{id}', [LichHenController::class, 'show'])->whereNumber('id');
        Route::post('/lich-hen', [LichHenController::class, 'store'])->middleware('throttle:20,1');
        Route::post('/lich-hen/{id}/{hanhDong}', [LichHenController::class, 'thaoTac'])->whereNumber('id')->where('hanhDong', 'huy');
        Route::get('/goi-cua-toi', [DonHangController::class, 'goiCuaToi']);
        Route::get('/don-hang', [DonHangController::class, 'index']);
        Route::post('/don-hang', [DonHangController::class, 'store'])->middleware('throttle:20,1');
        Route::get('/don-hang/{id}', [DonHangController::class, 'show'])->whereNumber('id');
        Route::post('/don-hang/{id}/link-thanh-toan', [DonHangController::class, 'taoLink'])->whereNumber('id')->middleware('throttle:10,1');
        Route::post('/don-hang/{id}/dong-bo', [DonHangController::class, 'dongBo'])->whereNumber('id')->middleware('throttle:10,1');
    });
    Route::get('/khach-hang/tong-quan', [TongQuanController::class, 'index'])->middleware('vai_tro:KHACH_HANG');
    Route::put('/me/ho-so', [HoSoTaiKhoanController::class, 'update']);
    Route::get('/khach-hang/ho-so/{hoSoKhachHang}', [HoSoKhachHangController::class, 'show']);
    Route::prefix('admin')->middleware('vai_tro:ADMIN')->group(function () {
        Route::get('/chatbot/thong-ke', [TaiLieuTuVanController::class, 'thongKe']);
        Route::get('/tai-lieu-tu-van', [TaiLieuTuVanController::class, 'index']);
        Route::post('/tai-lieu-tu-van', [TaiLieuTuVanController::class, 'store'])->middleware('throttle:20,1');
        Route::put('/tai-lieu-tu-van/{id}', [TaiLieuTuVanController::class, 'update'])->whereNumber('id')->middleware('throttle:30,1');
        Route::post('/tai-lieu-tu-van/{id}/{hanhDong}', [TaiLieuTuVanController::class, 'thaoTac'])->whereNumber('id')->where('hanhDong', 'xuat-ban|ngung')->middleware('throttle:30,1');
        Route::get('/lich-hen', [LichHenController::class, 'index']);
        Route::get('/lich-hen/{id}', [LichHenController::class, 'show'])->whereNumber('id');
        Route::post('/lich-hen/{id}/{hanhDong}', [LichHenController::class, 'thaoTac'])->whereNumber('id')->where('hanhDong', 'dong-xu-ly');
        Route::get('/don-hang', [DonHangController::class, 'index']);
        Route::get('/don-hang/{id}', [DonHangController::class, 'show'])->whereNumber('id');
        Route::patch('/thanh-toan/{id}/doi-soat', [DonHangController::class, 'doiSoat'])->whereNumber('id');
        Route::get('/phan-cong', [PhanCongController::class, 'index']);
        Route::get('/phan-cong/pt', [PhanCongController::class, 'pt']);
        Route::post('/phan-cong', [PhanCongController::class, 'store']);
        Route::get('/tong-quan', [TongQuanController::class, 'index']);
        Route::get('/bao-cao', [BaoCaoController::class, 'index']);
        Route::get('/nhom-co', [NhomCoController::class, 'index']);
        Route::post('/nhom-co', [NhomCoController::class, 'store']);
        Route::get('/nhom-co/{id}', [NhomCoController::class, 'show'])->whereNumber('id');
        Route::put('/nhom-co/{id}', [NhomCoController::class, 'update'])->whereNumber('id');
        Route::patch('/nhom-co/{id}/trang-thai', [NhomCoController::class, 'trangThai'])->whereNumber('id');
        Route::get('/giao-an-mau', [GiaoAnMauController::class, 'index']);
        Route::post('/giao-an-mau', [GiaoAnMauController::class, 'store']);
        Route::get('/giao-an-mau/{id}', [GiaoAnMauController::class, 'show'])->whereNumber('id');
        Route::put('/giao-an-mau/{id}', [GiaoAnMauController::class, 'update'])->whereNumber('id');
        Route::patch('/giao-an-mau/{id}/trang-thai', [GiaoAnMauController::class, 'trangThai'])->whereNumber('id');
        Route::get('/goi-tap', [GoiTapController::class, 'index']);
        Route::post('/goi-tap', [GoiTapController::class, 'store']);
        Route::get('/goi-tap/{id}', [GoiTapController::class, 'show'])->whereNumber('id');
        Route::put('/goi-tap/{id}', [GoiTapController::class, 'update'])->whereNumber('id');
        Route::patch('/goi-tap/{id}/trang-thai', [GoiTapController::class, 'trangThai'])->whereNumber('id');
        Route::get('/bai-tap/bo-loc', [BaiTapAdminController::class, 'boLoc']);
        Route::get('/bai-tap', [BaiTapAdminController::class, 'index']);
        Route::post('/bai-tap', [BaiTapAdminController::class, 'store']);
        Route::get('/bai-tap/{id}', [BaiTapAdminController::class, 'show'])->whereNumber('id');
        Route::put('/bai-tap/{id}', [BaiTapAdminController::class, 'update'])->whereNumber('id');
        Route::patch('/bai-tap/{id}/trang-thai', [BaiTapAdminController::class, 'trangThai'])->whereNumber('id');
        Route::get('/tai-khoan', [TaiKhoanController::class, 'index']);
        Route::post('/tai-khoan', [TaiKhoanController::class, 'store']);
        Route::patch('/tai-khoan/{id}/trang-thai', [TaiKhoanController::class, 'trangThai'])->whereNumber('id');
    });
    Route::get('/pt/ho-so', [XacThucController::class, 'me'])->middleware('vai_tro:HUAN_LUYEN_VIEN');
    Route::prefix('pt')->middleware('vai_tro:HUAN_LUYEN_VIEN')->group(function () {
        Route::get('/hoc-vien/{khachId}/chi-so-co-the', [ChiSoCoTheController::class, 'index'])->whereNumber('khachId');
        Route::get('/hoc-vien/{khachId}/lich-tap', [NhatKyTapController::class, 'index'])->whereNumber('khachId');
        Route::post('/hoc-vien/{khachId}/lich-tap', [NhatKyTapController::class, 'store'])->whereNumber('khachId')->middleware('throttle:30,1');
        Route::get('/lich-tap/{id}', [NhatKyTapController::class, 'show'])->whereNumber('id');
        Route::post('/lich-tap/{id}/{hanhDong}', [NhatKyTapController::class, 'thaoTac'])->whereNumber('id')->where('hanhDong', 'nhan-xet')->middleware('throttle:30,1');
        Route::get('/hoc-vien', [KeHoachTapController::class, 'hocVien']);
        Route::get('/hoc-vien/{khachId}/ke-hoach', [KeHoachTapController::class, 'index'])->whereNumber('khachId');
        Route::post('/hoc-vien/{khachId}/ke-hoach', [KeHoachTapController::class, 'store'])->whereNumber('khachId')->middleware('throttle:30,1');
        Route::get('/ke-hoach/{id}', [KeHoachTapController::class, 'show'])->whereNumber('id');
        Route::put('/ke-hoach/{id}', [KeHoachTapController::class, 'update'])->whereNumber('id')->middleware('throttle:30,1');
        Route::post('/ke-hoach/{id}/{hanhDong}', [KeHoachTapController::class, 'thaoTac'])->whereNumber('id')->where('hanhDong', 'gui|huy')->middleware('throttle:30,1');
        Route::get('/khung-gio', [LichHenController::class, 'khungGio']);
        Route::post('/khung-gio', [LichHenController::class, 'taoKhung']);
        Route::patch('/khung-gio/{id}', [LichHenController::class, 'doiKhung'])->whereNumber('id');
        Route::get('/lich-hen', [LichHenController::class, 'index']);
        Route::get('/lich-hen/{id}', [LichHenController::class, 'show'])->whereNumber('id');
        Route::post('/lich-hen/{id}/{hanhDong}', [LichHenController::class, 'thaoTac'])->whereNumber('id')->where('hanhDong', 'xac-nhan|tu-choi|hoan-thanh|vang-mat');
        Route::get('/tong-quan', [TongQuanController::class, 'index']);
        Route::get('/giao-an-mau', [GiaoAnMauController::class, 'index']);
        Route::get('/giao-an-mau/{id}', [GiaoAnMauController::class, 'show'])->whereNumber('id');
    });
});
