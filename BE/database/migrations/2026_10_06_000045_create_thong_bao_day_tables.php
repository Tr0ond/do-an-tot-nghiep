<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thiet_bi_push', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tai_khoan_id')->constrained('tai_khoan')->restrictOnDelete();
            $table->foreignId('phien_id')->unique()->constrained('personal_access_tokens')->cascadeOnDelete();
            $table->string('expo_token', 191)->unique();
            $table->uuid('phien_ban');
            $table->boolean('da_bat')->default(true);
            $table->timestamps();
        });
        Schema::create('hang_doi_push', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('thiet_bi_id')->constrained('thiet_bi_push')->cascadeOnDelete();
            $table->uuid('phien_ban');
            $table->string('duong_dan', 191);
            $table->string('loai', 20);
            $table->string('trang_thai', 20)->default('CHO_GUI');
            $table->unsignedTinyInteger('so_lan')->default(0);
            $table->string('ticket_id', 191)->nullable();
            $table->string('ma_loi', 60)->nullable();
            $table->timestamp('thu_lai_luc')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hang_doi_push');
        Schema::dropIfExists('thiet_bi_push');
    }
};
