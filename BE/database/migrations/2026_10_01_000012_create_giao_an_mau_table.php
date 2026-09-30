<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('giao_an_mau', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('ten_giao_an', 255);
            $table->string('muc_tieu', 255)->nullable();
            $table->unsignedInteger('so_ngay_tap')->default(0);
            $table->unsignedBigInteger('nguoi_tao_id');
            $table->unsignedBigInteger('nguoi_duyet_id')->nullable();
            $table->dateTime('duyet_luc', 6)->nullable();
            $table->string('trang_thai', 32);
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('nguoi_tao_id', 'fk_t12_01')
                ->references('id')->on('tai_khoan')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('nguoi_duyet_id', 'fk_t12_02')
                ->references('id')->on('tai_khoan')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giao_an_mau');
    }
};
