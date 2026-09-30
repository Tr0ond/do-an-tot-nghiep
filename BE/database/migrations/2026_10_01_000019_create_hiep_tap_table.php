<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hiep_tap', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('bai_tap_trong_phien_id');
            $table->unsignedInteger('thu_tu')->default(1);
            $table->unsignedInteger('so_lan_lap')->default(0);
            $table->decimal('khoi_luong_kg', 7, 2)->unsigned()->nullable();
            $table->unsignedInteger('nghi_giay')->default(0);
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['bai_tap_trong_phien_id', 'thu_tu'], 'uq_t19_01');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('bai_tap_trong_phien_id', 'fk_t19_01')
                ->references('id')->on('bai_tap_trong_phien')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        // MySQL thực thi CHECK để chặn dữ liệu vi phạm ngay tại database.
        DB::statement('ALTER TABLE `hiep_tap` ADD CONSTRAINT `ck_t19_01` CHECK (thu_tu > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('hiep_tap');
    }
};
