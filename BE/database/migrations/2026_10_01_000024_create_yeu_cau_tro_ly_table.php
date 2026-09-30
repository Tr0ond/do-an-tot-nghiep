<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yeu_cau_tro_ly', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('khach_hang_id');
            $table->unsignedBigInteger('hoi_thoai_tro_ly_id');
            $table->unsignedBigInteger('dang_ky_goi_tap_id');
            $table->char('client_request_id', 36);
            $table->date('ngay_han_muc');
            $table->string('provider', 64)->nullable();
            $table->string('model', 128)->nullable();
            $table->string('trang_thai', 32);
            $table->dateTime('giu_luot_den', 6)->nullable();
            $table->dateTime('hoan_thanh_luc', 6)->nullable();
            $table->string('ma_loi', 64)->nullable();
            $table->unsignedInteger('do_tre_ms')->default(0);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['khach_hang_id', 'client_request_id'], 'uq_t24_01');
            $table->index(['khach_hang_id', 'ngay_han_muc', 'trang_thai', 'giu_luot_den'], 'ix_t24_02');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('khach_hang_id', 'fk_t24_01')
                ->references('id')->on('ho_so_khach_hang')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('hoi_thoai_tro_ly_id', 'fk_t24_02')
                ->references('id')->on('hoi_thoai_tro_ly')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('dang_ky_goi_tap_id', 'fk_t24_03')
                ->references('id')->on('dang_ky_goi_tap')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yeu_cau_tro_ly');
    }
};
