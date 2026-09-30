<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bai_tap', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('nhom_co_id');
            $table->string('nguon_du_lieu', 64)->nullable();
            $table->char('ma_nguon', 4)->nullable();
            $table->string('ten_bai_tap', 255);
            $table->string('ten_tieng_viet', 255)->nullable();
            $table->string('bo_phan_co_the', 64)->nullable();
            $table->string('dung_cu', 255)->nullable();
            $table->string('dung_cu_nguon', 255)->nullable();
            $table->json('huong_dan')->nullable();
            $table->json('cac_buoc')->nullable();
            $table->json('co_phu')->nullable();
            $table->string('co_ho_tro_nguon', 255)->nullable();
            $table->string('anh_url', 255)->nullable();
            $table->string('gif_url', 255)->nullable();
            $table->string('duong_dan_anh_nguon', 255)->nullable();
            $table->string('duong_dan_gif_nguon', 255)->nullable();
            $table->string('ma_media_nguon', 64)->nullable();
            $table->string('ghi_cong_media', 255)->nullable();
            $table->dateTime('nguon_tao_luc', 6)->nullable();
            $table->dateTime('nguon_cap_nhat_luc', 6)->nullable();
            $table->string('trang_thai', 32);
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['nguon_du_lieu', 'ma_nguon'], 'uq_t11_01');
            $table->index(['nhom_co_id', 'trang_thai'], 'ix_t11_02');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('nhom_co_id', 'fk_t11_01')
                ->references('id')->on('nhom_co')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bai_tap');
    }
};
