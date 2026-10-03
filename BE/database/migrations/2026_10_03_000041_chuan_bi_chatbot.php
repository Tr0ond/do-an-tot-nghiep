<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hoi_thoai_tro_ly', function (Blueprint $table) {
            $table->char('client_request_id', 36)->nullable();
            $table->unique(['khach_hang_id', 'client_request_id'], 'uq_t23_tao');
        });
        Schema::table('yeu_cau_tro_ly', function (Blueprint $table) {
            $table->char('payload_hash', 64)->nullable();
            $table->char('ma_lan_xu_ly', 36)->nullable();
            $table->unsignedTinyInteger('so_lan_thu')->default(1);
            $table->boolean('dung_du_lieu_ca_nhan')->default(false);
            $table->string('phien_ban_prompt', 64)->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('yeu_cau_tro_ly')->whereNotNull('payload_hash')->exists()) {
            throw new RuntimeException('Không gỡ dữ liệu chống trùng khi đã có lịch sử chatbot.');
        }
        Schema::table('yeu_cau_tro_ly', fn (Blueprint $table) => $table->dropColumn(['payload_hash', 'ma_lan_xu_ly', 'so_lan_thu', 'dung_du_lieu_ca_nhan', 'phien_ban_prompt']));
        Schema::table('hoi_thoai_tro_ly', function (Blueprint $table) {
            $table->dropUnique('uq_t23_tao');
            $table->dropColumn('client_request_id');
        });
    }
};
