<?php

use App\Services\LichHenService;
use App\Services\ThongBaoDayService;
use App\Services\ThongBaoService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('lich-hen:don-qua-han', function () {
    $this->info('Đã cập nhật '.app(LichHenService::class)->donQuaHan().' lịch hẹn.');
    app(ThongBaoService::class)->nhacBuoiCanGhiNhan();
})->purpose('Giải phóng yêu cầu hết hạn và đánh dấu buổi quá hạn xác nhận.');
Schedule::command('lich-hen:don-qua-han')->everyMinute()->withoutOverlapping();
Schedule::command('sanctum:prune-expired --hours=24')->daily()->withoutOverlapping();
Artisan::command('mobile:gui-push', function () {
    $this->info('Đã xử lý '.app(ThongBaoDayService::class)->xuLy().' thông báo.');
})->purpose('Gửi push và kiểm tra receipt; không gửi khi EXPO_PUSH_ENABLED=false.');
Schedule::command('mobile:gui-push')->everyMinute()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
