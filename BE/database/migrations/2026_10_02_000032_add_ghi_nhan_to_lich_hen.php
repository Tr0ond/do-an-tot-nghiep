<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lich_hen_huan_luyen', function (Blueprint $table) {
            $table->unsignedBigInteger('nguoi_ghi_nhan_id')->nullable();
            $table->dateTime('ghi_nhan_luc', 6)->nullable();
            $table->text('ly_do_ghi_nhan')->nullable();
            $table->foreign('nguoi_ghi_nhan_id', 'fk_t09_ghi_nhan')->references('id')->on('tai_khoan')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lich_hen_huan_luyen', function (Blueprint $table) {
            $table->dropForeign('fk_t09_ghi_nhan');
            $table->dropColumn(['nguoi_ghi_nhan_id', 'ghi_nhan_luc', 'ly_do_ghi_nhan']);
        });
    }
};
