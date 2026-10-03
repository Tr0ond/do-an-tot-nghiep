<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lich_tap', function (Blueprint $table) {
            $table->uuid('ma_yeu_cau_tao')->nullable()->unique('uq_t16_02');
            $table->char('hash_yeu_cau_tao', 64)->nullable();
            $table->foreignId('nguoi_tao_id')->nullable()->constrained('tai_khoan')->restrictOnDelete();
            $table->index(['khach_hang_id', 'ngay_tap', 'id'], 'ix_t16_01');
        });
        Schema::table('ghi_chu_huan_luyen', function (Blueprint $table) {
            $table->uuid('ma_yeu_cau_tao')->nullable()->unique('uq_t20_01');
            $table->char('hash_yeu_cau_tao', 64)->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('lich_tap')->whereNotNull('ma_yeu_cau_tao')->exists()
            || DB::table('ghi_chu_huan_luyen')->whereNotNull('ma_yeu_cau_tao')->exists()) {
            throw new RuntimeException('Đã có nhật ký tự tập mới; không gỡ thông tin chống trùng.');
        }
        // InnoDB có thể thay index FK tự sinh bằng index tổng hợp mới.
        // Khôi phục index độc lập trước khi gỡ để không mất khóa ngoại.
        if (! Schema::hasIndex('lich_tap', ['khach_hang_id'])) {
            Schema::table('lich_tap', fn (Blueprint $table) => $table->index('khach_hang_id', 'ix_t16_khach_fk'));
        }
        Schema::table('ghi_chu_huan_luyen', function (Blueprint $table) {
            $table->dropUnique('uq_t20_01');
            $table->dropColumn(['ma_yeu_cau_tao', 'hash_yeu_cau_tao']);
        });
        Schema::table('lich_tap', function (Blueprint $table) {
            $table->dropConstrainedForeignId('nguoi_tao_id');
            $table->dropUnique('uq_t16_02');
            $table->dropIndex('ix_t16_01');
            $table->dropColumn(['ma_yeu_cau_tao', 'hash_yeu_cau_tao']);
        });
    }
};
