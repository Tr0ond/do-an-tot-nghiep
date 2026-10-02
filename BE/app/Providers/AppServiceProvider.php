<?php

namespace App\Providers;

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
        // SPA chỉ dùng session cookie, không phát hành hoặc nhận personal access token.
        Sanctum::getAccessTokenFromRequestUsing(fn () => null);
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
