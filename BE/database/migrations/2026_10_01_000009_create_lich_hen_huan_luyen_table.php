<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lich_hen_huan_luyen', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('khach_hang_id');
            $table->unsignedBigInteger('huan_luyen_vien_id');
            $table->unsignedBigInteger('phan_cong_id');
            $table->unsignedBigInteger('khung_gio_id');
            $table->unsignedBigInteger('dang_ky_goi_tap_id');
            $table->char('client_request_id', 36);
            $table->dateTime('bat_dau_luc', 6);
            $table->dateTime('ket_thuc_luc', 6);
            $table->string('trang_thai', 32);
            $table->dateTime('han_xac_nhan_dat_lich', 6)->nullable();
            $table->dateTime('han_xac_nhan_hoan_thanh', 6)->nullable();
            $table->dateTime('xac_nhan_luc', 6)->nullable();
            $table->dateTime('tieu_hao_luc', 6)->nullable();
            $table->unsignedBigInteger('nguoi_huy_id')->nullable();
            $table->dateTime('huy_luc', 6)->nullable();
            $table->text('ly_do_huy')->nullable();
            $table->unsignedBigInteger('nguoi_dong_xu_ly_id')->nullable();
            $table->dateTime('dong_xu_ly_luc', 6)->nullable();
            $table->text('ly_do_dong_xu_ly')->nullable();
            $table->unsignedBigInteger('khung_gio_dang_giu_id')->nullable()->storedAs('CASE WHEN trang_thai IN (\'CHO_XAC_NHAN\',\'DA_XAC_NHAN\') THEN khung_gio_id ELSE NULL END');
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['khung_gio_dang_giu_id'], 'uq_t09_01');
            $table->unique(['khach_hang_id', 'client_request_id'], 'uq_t09_02');
            $table->index(['khach_hang_id', 'bat_dau_luc', 'ket_thuc_luc'], 'ix_t09_03');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('khach_hang_id', 'fk_t09_01')
                ->references('id')->on('ho_so_khach_hang')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('huan_luyen_vien_id', 'fk_t09_02')
                ->references('id')->on('ho_so_huan_luyen_vien')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('phan_cong_id', 'fk_t09_03')
                ->references('id')->on('phan_cong_huan_luyen_vien')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('khung_gio_id', 'fk_t09_04')
                ->references('id')->on('khung_gio_huan_luyen_vien')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('dang_ky_goi_tap_id', 'fk_t09_05')
                ->references('id')->on('dang_ky_goi_tap')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('nguoi_huy_id', 'fk_t09_06')
                ->references('id')->on('tai_khoan')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('nguoi_dong_xu_ly_id', 'fk_t09_07')
                ->references('id')->on('tai_khoan')
                ->restrictOnDelete()->restrictOnUpdate();
        });

        // MySQL thực thi CHECK để chặn dữ liệu vi phạm ngay tại database.
        DB::statement('ALTER TABLE `lich_hen_huan_luyen` ADD CONSTRAINT `ck_t09_01` CHECK (TIMESTAMPDIFF(SECOND, bat_dau_luc, ket_thuc_luc) = 3600)');
    }

    public function down(): void
    {
        Schema::dropIfExists('lich_hen_huan_luyen');
    }
};
