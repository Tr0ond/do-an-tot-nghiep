<?php

namespace App\Providers;

use App\Models\KhungGioHuanLuyenVien;
use App\Models\LichTap;
use App\Models\TaiKhoan;
use App\Services\LichRealtimeService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        KhungGioHuanLuyenVien::saved(function ($slot) {
            app(LichRealtimeService::class)->choPt($slot->huan_luyen_vien_id);
        });
        LichTap::saved(function ($lich) {
            app(LichRealtimeService::class)->choKhach($lich->khach_hang_id);
        });
        // Website giữ cookie/CSRF; bearer chỉ hợp lệ cho phiên mobile KH/PT được cấp đúng dấu.
        Sanctum::getAccessTokenFromRequestUsing(fn (Request $request) => $request->bearerToken());
        Sanctum::authenticateAccessTokensUsing(function ($token, bool $hopLe): bool {
            $taiKhoan = $token->tokenable;

            return $hopLe && $taiKhoan instanceof TaiKhoan
                && $taiKhoan->trang_thai === TaiKhoan::HOAT_DONG
                && in_array($taiKhoan->vai_tro, [TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN], true)
                && $token->abilities === ['mobile']
                && $token->expires_at?->isFuture()
                && is_string($token->dau_phien_dang_nhap)
                && hash_equals($taiKhoan->dauPhienDangNhap(), $token->dau_phien_dang_nhap);
        });
        RateLimiter::for('chat-gui', fn (Request $request) => Limit::perMinute(30)->by('chat:'.$request->user()?->id));
        RateLimiter::for('dang-nhap', function (Request $request) {
            $email = $request->input('email');
            $khoaEmail = is_string($email) ? Str::lower(trim($email)) : '';

            return [Limit::perMinute(20)->by($request->ip()), Limit::perMinute(5)->by(hash('sha256', $khoaEmail.'|'.$request->ip()))];
        });
        RateLimiter::for('dang-ky', fn (Request $request) => Limit::perHour(10)->by($request->ip()));
        RateLimiter::for('khoi-phuc', function (Request $request) {
            $email = $request->input('email');
            $khoaEmail = is_string($email) ? Str::lower(trim($email)) : '';

            return [Limit::perMinute(20)->by($request->ip()), Limit::perMinute(5)->by(hash('sha256', $khoaEmail.'|'.$request->ip()))];
        });
        RateLimiter::for('dat-lai', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
    }
}
