<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('khung_gio_huan_luyen_vien', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('huan_luyen_vien_id');
            $table->dateTime('bat_dau_luc', 6);
            $table->dateTime('ket_thuc_luc', 6);
            $table->string('trang_thai', 32);
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['huan_luyen_vien_id', 'bat_dau_luc'], 'uq_t08_01');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('huan_luyen_vien_id', 'fk_t08_01')
                ->references('id')->on('ho_so_huan_luyen_vien')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        // MySQL thực thi CHECK để chặn dữ liệu vi phạm ngay tại database.
        DB::statement('ALTER TABLE `khung_gio_huan_luyen_vien` ADD CONSTRAINT `ck_t08_01` CHECK (TIMESTAMPDIFF(SECOND, bat_dau_luc, ket_thuc_luc) = 3600)');
    }

    public function down(): void
    {
        Schema::dropIfExists('khung_gio_huan_luyen_vien');
    }
};
