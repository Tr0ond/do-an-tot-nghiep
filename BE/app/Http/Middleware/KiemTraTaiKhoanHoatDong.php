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

        return $next($request);
    }
}
