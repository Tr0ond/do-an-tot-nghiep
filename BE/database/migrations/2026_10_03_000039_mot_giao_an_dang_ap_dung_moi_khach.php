<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Chạy trong maintenance: giữ bản được áp dụng gần nhất và không xóa lịch sử.
        DB::transaction(function () {
            $cacBan = DB::table('ke_hoach_tap')->where('trang_thai', 'DANG_AP_DUNG')
                ->orderBy('khach_hang_id')->orderByRaw('COALESCE(updated_at, duyet_luc, created_at) DESC')->orderByDesc('id')
                ->lockForUpdate()->get();
            foreach ($cacBan->groupBy('khach_hang_id') as $nhom) {
                foreach ($nhom->skip(1) as $ban) {
                    DB::table('ke_hoach_tap')->where('id', $ban->id)->update([
                        'trang_thai' => 'LUU_TRU',
                        'updated_at' => DB::raw("GREATEST(COALESCE(updated_at, '1000-01-01') + INTERVAL 1 MICROSECOND, UTC_TIMESTAMP(6))"),
                    ]);
                }
            }
        });
        DB::statement('ALTER TABLE ke_hoach_tap DROP INDEX uq_t14_01, ADD UNIQUE KEY uq_t14_01 (khach_dang_ap_dung_id)');
    }

    public function down(): void
    {
        // Trở về index cũ không tự kích hoạt lại bản đã được lưu trữ.
        DB::statement('ALTER TABLE ke_hoach_tap DROP INDEX uq_t14_01, ADD UNIQUE KEY uq_t14_01 (nguon_tao, khach_dang_ap_dung_id)');
    }
};
