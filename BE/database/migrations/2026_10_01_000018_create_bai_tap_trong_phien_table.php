<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bai_tap_trong_phien', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('phien_tap_id');
            $table->unsignedBigInteger('bai_tap_id');
            $table->unsignedBigInteger('bai_tap_trong_ke_hoach_id');
            $table->unsignedInteger('thu_tu')->default(1);
            $table->string('ten_bai_tap_snapshot', 255);
            $table->json('noi_dung_snapshot')->nullable();
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['phien_tap_id', 'thu_tu'], 'uq_t18_01');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('phien_tap_id', 'fk_t18_01')
                ->references('id')->on('phien_tap')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('bai_tap_id', 'fk_t18_02')
                ->references('id')->on('bai_tap')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('bai_tap_trong_ke_hoach_id', 'fk_t18_03')
                ->references('id')->on('bai_tap_trong_ke_hoach')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bai_tap_trong_phien');
    }
};
