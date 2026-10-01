<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goi_tap', function (Blueprint $table) {
            // Nullable giữ tương thích dữ liệu cũ; unique chặn tạo trùng khi request retry.
            $table->uuid('ma_yeu_cau_tao')->nullable();
            $table->unique('ma_yeu_cau_tao', 'uq_t04_01');
        });
    }

    public function down(): void
    {
        Schema::table('goi_tap', function (Blueprint $table) {
            $table->dropUnique('uq_t04_01');
            $table->dropColumn('ma_yeu_cau_tao');
        });
    }
};
