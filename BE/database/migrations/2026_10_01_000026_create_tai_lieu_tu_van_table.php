<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tai_lieu_tu_van', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('tieu_de', 255);
            $table->string('loai', 32);
            $table->longText('noi_dung');
            $table->unsignedInteger('phien_ban')->default(1);
            $table->unsignedBigInteger('nguoi_cap_nhat_id');
            $table->string('trang_thai', 32);
            $table->dateTime('xuat_ban_luc', 6)->nullable();
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('nguoi_cap_nhat_id', 'fk_t26_01')
                ->references('id')->on('tai_khoan')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tai_lieu_tu_van');
    }
};
