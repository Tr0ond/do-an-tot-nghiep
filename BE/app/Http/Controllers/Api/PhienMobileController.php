<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DangKyMobileRequest;
use App\Http\Requests\DangNhapMobileRequest;
use App\Http\Requests\KhoiPhucMatKhauRequest;
use App\Http\Resources\TaiKhoanResource;
use App\Models\TaiKhoan;
use App\Services\KhoiPhucMatKhauService;
use App\Services\PhienMobileService;
use App\Services\TaiKhoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class PhienMobileController extends Controller
{
    public function dangKy(DangKyMobileRequest $request, TaiKhoanService $dichVu): JsonResponse
    {
        $dichVu->taoTaiKhoan($request->validated(), TaiKhoan::KHACH_HANG);

        // Tạo tài khoản không cấp phiên; người dùng đăng nhập riêng để lưu token an toàn.
        return response()->json(['status' => true, 'message' => 'Đã tạo tài khoản học viên. Hãy đăng nhập để tiếp tục.', 'data' => null], 201)->header('Cache-Control', 'no-store');
    }

    public function guiLienKet(KhoiPhucMatKhauRequest $request, KhoiPhucMatKhauService $dichVu): JsonResponse
    {
        $dichVu->guiLienKet($request->validated('email'), true);

        return response()->json(['status' => true, 'message' => 'Nếu email thuộc tài khoản đang hoạt động, liên kết khôi phục sẽ được gửi. Hãy kiểm tra cả thư rác; nếu vừa yêu cầu, hãy chờ trước khi gửi lại.', 'data' => null])->header('Cache-Control', 'no-store');
    }

    public function datLai(KhoiPhucMatKhauRequest $request, KhoiPhucMatKhauService $dichVu): JsonResponse
    {
        $dichVu->datLai($request->validated());

        return response()->json(['status' => true, 'message' => 'Đã đặt lại mật khẩu. Hãy đăng nhập bằng mật khẩu mới.', 'data' => null])->header('Cache-Control', 'no-store');
    }

    public function dangNhap(DangNhapMobileRequest $request, PhienMobileService $dichVu): JsonResponse
    {
        $token = $dichVu->dangNhap($request->validated());
        $taiKhoan = $token->accessToken->tokenable->load(['hoSoKhachHang', 'hoSoHuanLuyenVien']);

        return response()->json(['status' => true, 'message' => 'Đăng nhập thành công.', 'data' => [
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at->toIso8601String(),
            'tai_khoan' => (new TaiKhoanResource($taiKhoan))->resolve($request),
        ]])->header('Cache-Control', 'no-store');
    }

    public function dangXuat(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        abort_unless($token instanceof PersonalAccessToken, 401);
        // Không đổi dấu phiên hoặc xóa token của các thiết bị khác.
        $token->delete();

        return response()->json(['status' => true, 'message' => 'Đã đăng xuất thiết bị này.', 'data' => null]);
    }
}
