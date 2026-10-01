<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('giao_an_mau', function (Blueprint $table) {
            $table->uuid('ma_yeu_cau_tao')->nullable()->unique('uq_t12_01');
        });
    }

    public function down(): void
    {
        Schema::table('giao_an_mau', function (Blueprint $table) {
            $table->dropUnique('uq_t12_01');
            $table->dropColumn('ma_yeu_cau_tao');
        });
    }
};
