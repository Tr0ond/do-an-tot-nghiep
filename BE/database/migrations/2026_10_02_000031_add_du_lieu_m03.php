<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dang_ky_goi_tap', function (Blueprint $table) {
            $table->text('url_thanh_toan')->nullable();
            $table->unsignedBigInteger('khach_dang_cho_id')->nullable()->storedAs("CASE WHEN trang_thai = 'CHO_THANH_TOAN' THEN khach_hang_id ELSE NULL END");
            $table->unique('khach_dang_cho_id', 'uq_t05_06');
        });
        Schema::table('phan_cong_huan_luyen_vien', function (Blueprint $table) {
            $table->char('client_request_id', 36)->nullable();
            $table->unsignedBigInteger('phan_cong_truoc_id')->nullable();
            $table->unique('client_request_id', 'uq_t07_02');
        });
    }

    public function down(): void
    {
        Schema::table('phan_cong_huan_luyen_vien', function (Blueprint $table) {
            $table->dropUnique('uq_t07_02');
            $table->dropColumn(['client_request_id', 'phan_cong_truoc_id']);
        });
        Schema::table('dang_ky_goi_tap', function (Blueprint $table) {
            $table->dropUnique('uq_t05_06');
            $table->dropColumn(['url_thanh_toan', 'khach_dang_cho_id']);
        });
    }
};
