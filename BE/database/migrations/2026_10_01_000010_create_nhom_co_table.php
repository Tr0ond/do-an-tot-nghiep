<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nhom_co', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('ma_nhom_co', 64);
            $table->string('ten_nhom_co', 255);
            $table->string('ten_nguon', 255);
            $table->string('trang_thai', 32);
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['ma_nhom_co'], 'uq_t10_01');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nhom_co');
    }
};
