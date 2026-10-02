<?php

namespace App\Http\Middleware;

use App\Models\TaiKhoan;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class KiemTraTaiKhoanHoatDong
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->trang_thai !== TaiKhoan::HOAT_DONG) {
            Auth::guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            abort(403, 'Tài khoản đã bị khóa hoặc ngừng hoạt động.');
        }

        // Session thật phải giữ dấu lúc đăng nhập; khóa/mở hoặc đổi mật khẩu không hồi sinh phiên cũ.
        if ($request->hasSession() && $request->session()->has(Auth::guard('web')->getName())) {
            $dauPhien = $request->session()->get('dau_phien_dang_nhap');
            if (! is_string($dauPhien) || ! hash_equals($request->user()->dauPhienDangNhap(), $dauPhien)) {
                Auth::guard('web')->logoutCurrentDevice();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                abort(401);
            }
        }

        return $next($request);
    }
}
