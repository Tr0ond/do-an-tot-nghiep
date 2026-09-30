<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ke_hoach_tap', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('khach_hang_id');
            $table->unsignedBigInteger('huan_luyen_vien_id');
            $table->unsignedBigInteger('phan_cong_id');
            $table->unsignedBigInteger('giao_an_mau_id')->nullable();
            $table->unsignedBigInteger('thay_the_ke_hoach_id')->nullable();
            $table->string('ten_ke_hoach', 255);
            $table->string('muc_tieu', 255)->nullable();
            $table->string('trang_thai', 32);
            $table->dateTime('gui_luc', 6)->nullable();
            $table->dateTime('han_duyet', 6)->nullable();
            $table->dateTime('duyet_luc', 6)->nullable();
            $table->unsignedBigInteger('khach_dang_ap_dung_id')->nullable()->storedAs('CASE WHEN trang_thai = \'DANG_AP_DUNG\' THEN khach_hang_id ELSE NULL END');
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['khach_dang_ap_dung_id'], 'uq_t14_01');
            $table->index(['khach_hang_id', 'trang_thai'], 'ix_t14_02');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('khach_hang_id', 'fk_t14_01')
                ->references('id')->on('ho_so_khach_hang')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('huan_luyen_vien_id', 'fk_t14_02')
                ->references('id')->on('ho_so_huan_luyen_vien')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('phan_cong_id', 'fk_t14_03')
                ->references('id')->on('phan_cong_huan_luyen_vien')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('giao_an_mau_id', 'fk_t14_04')
                ->references('id')->on('giao_an_mau')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('thay_the_ke_hoach_id', 'fk_t14_05')
                ->references('id')->on('ke_hoach_tap')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ke_hoach_tap');
    }
};
