<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nhat_ky_he_thong', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('tai_khoan_id')->nullable();
            $table->string('hanh_dong', 128);
            $table->string('loai_tai_nguyen', 128);
            $table->unsignedBigInteger('tai_nguyen_id')->nullable();
            $table->json('metadata_an_toan')->nullable();
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->index(['loai_tai_nguyen', 'tai_nguyen_id', 'created_at'], 'ix_t28_01');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('tai_khoan_id', 'fk_t28_01')
                ->references('id')->on('tai_khoan')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nhat_ky_he_thong');
    }
};
