<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phan_cong_huan_luyen_vien', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('khach_hang_id');
            $table->unsignedBigInteger('huan_luyen_vien_id');
            $table->unsignedBigInteger('nguoi_phan_cong_id');
            $table->dateTime('bat_dau_luc', 6)->nullable();
            $table->dateTime('ket_thuc_luc', 6)->nullable();
            $table->text('ly_do_ket_thuc')->nullable();
            $table->unsignedBigInteger('khach_dang_phan_cong_id')->nullable()->storedAs('CASE WHEN ket_thuc_luc IS NULL THEN khach_hang_id ELSE NULL END');
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['khach_dang_phan_cong_id'], 'uq_t07_01');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('khach_hang_id', 'fk_t07_01')
                ->references('id')->on('ho_so_khach_hang')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('huan_luyen_vien_id', 'fk_t07_02')
                ->references('id')->on('ho_so_huan_luyen_vien')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('nguoi_phan_cong_id', 'fk_t07_03')
                ->references('id')->on('tai_khoan')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        // MySQL thực thi CHECK để chặn dữ liệu vi phạm ngay tại database.
        DB::statement('ALTER TABLE `phan_cong_huan_luyen_vien` ADD CONSTRAINT `ck_t07_01` CHECK (ket_thuc_luc IS NULL OR ket_thuc_luc > bat_dau_luc)');
    }

    public function down(): void
    {
        Schema::dropIfExists('phan_cong_huan_luyen_vien');
    }
};
