<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ke_hoach_tap', function (Blueprint $table) {
            $table->unsignedInteger('so_ngay_tap')->default(1);
            $table->uuid('ma_yeu_cau_tao')->nullable();
            $table->char('hash_yeu_cau_tao', 64)->nullable();
            $table->unique('ma_yeu_cau_tao', 'uq_t14_03');
        });
        Schema::table('bai_tap_trong_ke_hoach', fn (Blueprint $table) => $table->decimal('muc_ta_kg', 6, 2)->nullable());
        // Suy ra ngày tập từ các dòng cũ để không hiển thị sai giáo án đã tồn tại.
        DB::statement('UPDATE ke_hoach_tap k SET so_ngay_tap = GREATEST(1, COALESCE((SELECT MAX(b.ngay_thu) FROM bai_tap_trong_ke_hoach b WHERE b.ke_hoach_tap_id = k.id), 1))');
    }

    public function down(): void
    {
        if (DB::table('ke_hoach_tap')->whereNotNull('ma_yeu_cau_tao')->exists() || DB::table('bai_tap_trong_ke_hoach')->whereNotNull('muc_ta_kg')->exists()) {
            throw new RuntimeException('Đã có giáo án cá nhân; không gỡ dữ liệu chống trùng và mức tạ.');
        }
        Schema::table('bai_tap_trong_ke_hoach', fn (Blueprint $table) => $table->dropColumn('muc_ta_kg'));
        Schema::table('ke_hoach_tap', function (Blueprint $table) {
            $table->dropUnique('uq_t14_03');
            $table->dropColumn(['so_ngay_tap', 'ma_yeu_cau_tao', 'hash_yeu_cau_tao']);
        });
    }
};
