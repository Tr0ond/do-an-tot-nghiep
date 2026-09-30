<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goi_tap', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('ten_goi', 255);
            $table->unsignedBigInteger('gia');
            $table->boolean('co_chatbot')->default(false);
            $table->unsignedInteger('so_luot_chatbot_moi_ngay')->default(0);
            $table->unsignedInteger('so_buoi_pt')->default(0);
            $table->unsignedInteger('thoi_han_ngay')->default(0);
            $table->string('trang_thai', 32);
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();
        });

        // MySQL thực thi CHECK để chặn dữ liệu vi phạm ngay tại database.
        DB::statement('ALTER TABLE `goi_tap` ADD CONSTRAINT `ck_t04_01` CHECK (thoi_han_ngay > 0)');
        DB::statement('ALTER TABLE `goi_tap` ADD CONSTRAINT `ck_t04_02` CHECK ((co_chatbot = 1 AND so_luot_chatbot_moi_ngay > 0) OR (co_chatbot = 0 AND so_luot_chatbot_moi_ngay = 0))');
        DB::statement('ALTER TABLE `goi_tap` ADD CONSTRAINT `ck_t04_03` CHECK (co_chatbot = 1 OR so_buoi_pt > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('goi_tap');
    }
};
