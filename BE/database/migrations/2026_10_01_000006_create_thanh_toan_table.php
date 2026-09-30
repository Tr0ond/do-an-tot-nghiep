<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thanh_toan', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('dang_ky_goi_tap_id');
            $table->string('ma_giao_dich', 191);
            $table->unsignedBigInteger('so_tien');
            $table->dateTime('thanh_toan_luc', 6)->nullable();
            $table->dateTime('xac_minh_luc', 6)->nullable();
            $table->string('trang_thai', 32);
            $table->text('ly_do_doi_soat')->nullable();
            $table->unsignedBigInteger('nguoi_doi_soat_id')->nullable();
            $table->dateTime('doi_soat_luc', 6)->nullable();
            $table->unsignedBigInteger('so_tien_hoan')->nullable();
            $table->string('ma_hoan_tien', 191)->nullable();
            $table->text('ly_do_hoan_tien')->nullable();
            $table->dateTime('hoan_tien_luc', 6)->nullable();
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['ma_giao_dich'], 'uq_t06_01');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('dang_ky_goi_tap_id', 'fk_t06_01')
                ->references('id')->on('dang_ky_goi_tap')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('nguoi_doi_soat_id', 'fk_t06_02')
                ->references('id')->on('tai_khoan')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        // MySQL thực thi CHECK để chặn dữ liệu vi phạm ngay tại database.
        DB::statement('ALTER TABLE `thanh_toan` ADD CONSTRAINT `ck_t06_01` CHECK (so_tien_hoan IS NULL OR so_tien_hoan <= so_tien)');
    }

    public function down(): void
    {
        Schema::dropIfExists('thanh_toan');
    }
};
