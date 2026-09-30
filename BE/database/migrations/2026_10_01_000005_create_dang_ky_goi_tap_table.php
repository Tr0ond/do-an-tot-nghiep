<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dang_ky_goi_tap', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('khach_hang_id');
            $table->unsignedBigInteger('goi_tap_id');
            $table->char('client_request_id', 36);
            $table->unsignedBigInteger('ma_don_payos');
            $table->string('ma_link_payos', 191)->nullable();
            $table->string('ten_goi_snapshot', 255);
            $table->unsignedBigInteger('gia_snapshot');
            $table->boolean('co_chatbot_snapshot');
            $table->unsignedInteger('so_luot_chatbot_moi_ngay_snapshot')->default(0);
            $table->unsignedInteger('so_buoi_pt_snapshot')->default(0);
            $table->unsignedInteger('thoi_han_ngay_snapshot')->default(0);
            $table->unsignedInteger('so_buoi_con_lai')->default(0);
            $table->string('trang_thai', 32);
            $table->dateTime('han_thanh_toan', 6)->nullable();
            $table->dateTime('kich_hoat_luc', 6)->nullable();
            $table->dateTime('het_han_luc', 6)->nullable();
            $table->unsignedBigInteger('khach_dang_dung_id')->nullable()->storedAs('CASE WHEN trang_thai = \'DANG_SU_DUNG\' THEN khach_hang_id ELSE NULL END');
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['ma_don_payos'], 'uq_t05_01');
            $table->unique(['ma_link_payos'], 'uq_t05_02');
            $table->unique(['khach_hang_id', 'client_request_id'], 'uq_t05_03');
            $table->unique(['khach_dang_dung_id'], 'uq_t05_04');
            $table->index(['khach_hang_id', 'trang_thai', 'het_han_luc'], 'ix_t05_05');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('khach_hang_id', 'fk_t05_01')
                ->references('id')->on('ho_so_khach_hang')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('goi_tap_id', 'fk_t05_02')
                ->references('id')->on('goi_tap')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        // MySQL thực thi CHECK để chặn dữ liệu vi phạm ngay tại database.
        DB::statement('ALTER TABLE `dang_ky_goi_tap` ADD CONSTRAINT `ck_t05_01` CHECK (so_buoi_con_lai <= so_buoi_pt_snapshot)');
    }

    public function down(): void
    {
        Schema::dropIfExists('dang_ky_goi_tap');
    }
};
