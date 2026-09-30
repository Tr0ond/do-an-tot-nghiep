<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ho_so_khach_hang', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('tai_khoan_id');
            $table->string('muc_tieu', 255)->nullable();
            $table->string('kinh_nghiem', 255)->nullable();
            $table->string('gioi_tinh', 32)->nullable();
            $table->date('ngay_sinh')->nullable();
            $table->json('thoi_gian_co_the_tap')->nullable();
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['tai_khoan_id'], 'uq_t02_01');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('tai_khoan_id', 'fk_t02_01')
                ->references('id')->on('tai_khoan')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ho_so_khach_hang');
    }
};
