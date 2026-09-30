<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghi_chu_huan_luyen', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('phien_tap_id');
            $table->unsignedBigInteger('huan_luyen_vien_id');
            $table->unsignedBigInteger('phan_cong_id');
            $table->text('noi_dung');
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('phien_tap_id', 'fk_t20_01')
                ->references('id')->on('phien_tap')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('huan_luyen_vien_id', 'fk_t20_02')
                ->references('id')->on('ho_so_huan_luyen_vien')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('phan_cong_id', 'fk_t20_03')
                ->references('id')->on('phan_cong_huan_luyen_vien')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghi_chu_huan_luyen');
    }
};
