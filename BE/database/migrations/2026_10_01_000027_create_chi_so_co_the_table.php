<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chi_so_co_the', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('khach_hang_id');
            $table->date('ngay_ghi');
            $table->decimal('can_nang_kg', 6, 2)->unsigned()->nullable();
            $table->decimal('chieu_cao_cm', 5, 2)->unsigned()->nullable();
            $table->decimal('vong_eo_cm', 5, 2)->unsigned()->nullable();
            $table->text('ghi_chu')->nullable();
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['khach_hang_id', 'ngay_ghi'], 'uq_t27_01');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('khach_hang_id', 'fk_t27_01')
                ->references('id')->on('ho_so_khach_hang')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chi_so_co_the');
    }
};
