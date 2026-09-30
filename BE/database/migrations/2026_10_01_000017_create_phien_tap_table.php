<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phien_tap', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('lich_tap_id');
            $table->unsignedBigInteger('khach_hang_id');
            $table->dateTime('bat_dau_luc', 6)->nullable();
            $table->dateTime('hoan_thanh_luc', 6)->nullable();
            $table->string('trang_thai', 32);
            $table->text('ghi_chu')->nullable();
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['lich_tap_id'], 'uq_t17_01');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('lich_tap_id', 'fk_t17_01')
                ->references('id')->on('lich_tap')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('khach_hang_id', 'fk_t17_02')
                ->references('id')->on('ho_so_khach_hang')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phien_tap');
    }
};
