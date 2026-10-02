<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KhoiPhucMatKhauRequest;
use App\Services\KhoiPhucMatKhauService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class KhoiPhucMatKhauController extends Controller
{
    public function guiLienKet(KhoiPhucMatKhauRequest $request, KhoiPhucMatKhauService $dichVu): JsonResponse
    {
        abort_if(Auth::guard('web')->check(), 409, 'Hãy đăng xuất trước khi khôi phục mật khẩu.');
        $dichVu->guiLienKet($request->validated('email'));

        return response()->json(['status' => true, 'message' => 'Nếu email thuộc tài khoản đang hoạt động, liên kết khôi phục sẽ được gửi. Hãy kiểm tra cả thư rác; nếu vừa yêu cầu, hãy chờ trước khi gửi lại.', 'data' => null]);
    }

    public function datLai(KhoiPhucMatKhauRequest $request, KhoiPhucMatKhauService $dichVu): JsonResponse
    {
        abort_if(Auth::guard('web')->check(), 409, 'Hãy đăng xuất trước khi khôi phục mật khẩu.');
        $dichVu->datLai($request->validated());
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['status' => true, 'message' => 'Đã đặt lại mật khẩu. Hãy đăng nhập bằng mật khẩu mới.', 'data' => null]);
    }
}
