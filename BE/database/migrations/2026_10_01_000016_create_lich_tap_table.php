<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lich_tap', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('ke_hoach_tap_id');
            $table->unsignedBigInteger('khach_hang_id');
            $table->unsignedInteger('ngay_thu')->default(1);
            $table->date('ngay_tap');
            $table->string('trang_thai', 32);
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['ke_hoach_tap_id', 'ngay_tap', 'ngay_thu'], 'uq_t16_01');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('ke_hoach_tap_id', 'fk_t16_01')
                ->references('id')->on('ke_hoach_tap')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('khach_hang_id', 'fk_t16_02')
                ->references('id')->on('ho_so_khach_hang')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lich_tap');
    }
};
