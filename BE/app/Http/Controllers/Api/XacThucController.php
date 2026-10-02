<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DangKyRequest;
use App\Http\Requests\DangNhapRequest;
use App\Http\Resources\TaiKhoanResource;
use App\Models\TaiKhoan;
use App\Services\TaiKhoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class XacThucController extends Controller
{
    public function dangKy(DangKyRequest $request, TaiKhoanService $dichVu): JsonResponse
    {
        abort_if(Auth::guard('web')->check(), 409, 'Hãy đăng xuất trước khi tạo tài khoản mới.');
        $taiKhoan = $dichVu->taoTaiKhoan($request->validated(), TaiKhoan::KHACH_HANG);
        Auth::guard('web')->login($taiKhoan);
        $request->session()->regenerate();

        $request->session()->put('dau_phien_dang_nhap', $taiKhoan->dauPhienDangNhap());

        return $this->traTaiKhoan($request, $taiKhoan, 'Đăng ký thành công.', 201);
    }

    public function dangNhap(DangNhapRequest $request): JsonResponse
    {
        abort_if(Auth::guard('web')->check(), 409, 'Bạn đã đăng nhập.');
        $hopLe = Auth::guard('web')->attempt([
            ...$request->safe()->only(['email', 'password']),
            'trang_thai' => TaiKhoan::HOAT_DONG,
        ]);
        if (! $hopLe) {
            // Không tiết lộ email có tồn tại hay tài khoản đã bị khóa.
            throw ValidationException::withMessages(['email' => ['Thông tin đăng nhập không đúng hoặc tài khoản không hoạt động.']]);
        }

        $request->session()->regenerate();

        $request->session()->put('dau_phien_dang_nhap', Auth::guard('web')->user()->dauPhienDangNhap());

        return $this->traTaiKhoan($request, Auth::guard('web')->user(), 'Đăng nhập thành công.');
    }

    public function dangXuat(Request $request): JsonResponse
    {
        // Đăng xuất chỉ đóng thiết bị hiện tại; dấu phiên toàn tài khoản chỉ đổi khi khóa/reset.
        Auth::guard('web')->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['status' => true, 'message' => 'Đã đăng xuất.', 'data' => null]);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->traTaiKhoan($request, $request->user(), 'Lấy tài khoản thành công.');
    }

    private function traTaiKhoan(Request $request, TaiKhoan $taiKhoan, string $thongBao, int $maHttp = 200): JsonResponse
    {
        $taiKhoan->load(['hoSoKhachHang', 'hoSoHuanLuyenVien']);

        return response()->json(['status' => true, 'message' => $thongBao, 'data' => (new TaiKhoanResource($taiKhoan))->resolve($request)], $maHttp);
    }
}
