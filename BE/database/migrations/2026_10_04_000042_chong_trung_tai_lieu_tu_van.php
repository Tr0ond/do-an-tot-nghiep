<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tai_lieu_tu_van', function (Blueprint $t) {
            $t->char('client_request_id', 36)->nullable()->unique('uq_t26_tao');
            $t->char('payload_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('tai_lieu_tu_van')->whereNotNull('payload_hash')->exists()) {
            throw new RuntimeException('Không gỡ dữ liệu chống trùng khi đã có tài liệu tư vấn.');
        }
        Schema::table('tai_lieu_tu_van', function (Blueprint $t) {
            $t->dropUnique('uq_t26_tao');
            $t->dropColumn(['client_request_id', 'payload_hash']);
        });
    }
};
