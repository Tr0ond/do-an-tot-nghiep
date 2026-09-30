<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tin_nhan_tro_ly', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('hoi_thoai_tro_ly_id');
            $table->unsignedBigInteger('yeu_cau_tro_ly_id');
            $table->enum('vai_tro', ['USER', 'ASSISTANT']);
            $table->text('noi_dung');
            $table->json('nguon_da_kiem_tra')->nullable();
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['yeu_cau_tro_ly_id', 'vai_tro'], 'uq_t25_01');
            $table->index(['hoi_thoai_tro_ly_id', 'id'], 'ix_t25_02');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('hoi_thoai_tro_ly_id', 'fk_t25_01')
                ->references('id')->on('hoi_thoai_tro_ly')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('yeu_cau_tro_ly_id', 'fk_t25_02')
                ->references('id')->on('yeu_cau_tro_ly')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tin_nhan_tro_ly');
    }
};
