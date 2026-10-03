<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ke_hoach_tap', function (Blueprint $table) {
            $table->dateTime('khach_an_luc', 6)->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('ke_hoach_tap')->whereNotNull('khach_an_luc')->exists()) {
            throw new RuntimeException('Cần hiện lại các giáo án đã ẩn trước khi rollback để giữ lựa chọn của khách hàng.');
        }
        Schema::table('ke_hoach_tap', function (Blueprint $table) {
            $table->dropColumn('khach_an_luc');
        });
    }
};
