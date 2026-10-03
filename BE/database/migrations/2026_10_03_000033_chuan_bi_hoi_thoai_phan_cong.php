<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('phan_cong_huan_luyen_vien')->orderBy('id')->chunkById(500, function ($phanCong) {
            foreach ($phanCong as $pc) {
                DB::table('hoi_thoai')->insertOrIgnore(['phan_cong_id' => $pc->id, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        // Không xóa hội thoại/tin nhắn đã phát sinh khi rollback bước chuẩn bị dữ liệu.
    }
};
