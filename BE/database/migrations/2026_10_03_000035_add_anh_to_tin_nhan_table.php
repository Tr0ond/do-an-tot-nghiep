<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tin_nhan', function (Blueprint $table) {
            $table->json('anh')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('tin_nhan')->whereNotNull('anh')->exists()) {
            throw new RuntimeException('Không rollback cột ảnh khi đã có lịch sử gửi ảnh.');
        }
        Schema::table('tin_nhan', fn (Blueprint $table) => $table->dropColumn('anh'));
    }
};
