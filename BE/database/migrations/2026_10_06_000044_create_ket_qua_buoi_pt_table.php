<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ket_qua_buoi_pt', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lich_hen_id')->unique();
            $table->unsignedBigInteger('nguoi_ghi_id');
            $table->json('bai_tap');
            $table->text('ghi_chu')->nullable();
            $table->text('nhan_xet')->nullable();
            $table->dateTime('chot_luc', 6)->nullable();
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();
            $table->foreign('lich_hen_id')->references('id')->on('lich_hen_huan_luyen')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('nguoi_ghi_id')->references('id')->on('tai_khoan')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ket_qua_buoi_pt');
    }
};
