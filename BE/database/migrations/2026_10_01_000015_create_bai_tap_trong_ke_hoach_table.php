<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bai_tap_trong_ke_hoach', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('ke_hoach_tap_id');
            $table->unsignedBigInteger('bai_tap_id');
            $table->unsignedInteger('ngay_thu')->default(1);
            $table->unsignedInteger('thu_tu')->default(1);
            $table->string('ten_bai_tap_snapshot', 255);
            $table->json('noi_dung_snapshot')->nullable();
            $table->unsignedInteger('so_hiep')->default(1);
            $table->unsignedInteger('so_lan_lap')->default(1);
            $table->unsignedInteger('nghi_giay')->default(0);
            $table->text('ghi_chu')->nullable();
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['ke_hoach_tap_id', 'ngay_thu', 'thu_tu'], 'uq_t15_01');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('ke_hoach_tap_id', 'fk_t15_01')
                ->references('id')->on('ke_hoach_tap')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('bai_tap_id', 'fk_t15_02')
                ->references('id')->on('bai_tap')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bai_tap_trong_ke_hoach');
    }
};
