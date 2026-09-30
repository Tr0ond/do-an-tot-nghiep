<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tin_nhan', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('hoi_thoai_id');
            $table->unsignedBigInteger('nguoi_gui_id');
            $table->char('client_message_id', 36);
            $table->text('noi_dung');
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['hoi_thoai_id', 'nguoi_gui_id', 'client_message_id'], 'uq_t22_01');
            $table->index(['hoi_thoai_id', 'id'], 'ix_t22_02');

            // Giữ lịch sử nghiệp vụ khi tài khoản hoặc danh mục ngừng sử dụng.
            $table->foreign('hoi_thoai_id', 'fk_t22_01')
                ->references('id')->on('hoi_thoai')
                ->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('nguoi_gui_id', 'fk_t22_02')
                ->references('id')->on('tai_khoan')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tin_nhan');
    }
};
