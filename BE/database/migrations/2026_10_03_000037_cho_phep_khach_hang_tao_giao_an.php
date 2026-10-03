<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Đổi nullable và tạo lại FK trong cùng ALTER để MariaDB/MySQL giữ quan hệ và index.
        DB::statement("ALTER TABLE ke_hoach_tap
            DROP FOREIGN KEY fk_t14_02, DROP FOREIGN KEY fk_t14_03,
            ADD COLUMN nguon_tao VARCHAR(32) NOT NULL DEFAULT 'PT',
            MODIFY huan_luyen_vien_id BIGINT UNSIGNED NULL,
            MODIFY phan_cong_id BIGINT UNSIGNED NULL,
            ADD CONSTRAINT fk_t14_02_tu_tao FOREIGN KEY (huan_luyen_vien_id) REFERENCES ho_so_huan_luyen_vien(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
            ADD CONSTRAINT fk_t14_03_tu_tao FOREIGN KEY (phan_cong_id) REFERENCES phan_cong_huan_luyen_vien(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
            DROP INDEX uq_t14_01, ADD UNIQUE KEY uq_t14_01 (nguon_tao, khach_dang_ap_dung_id)");
    }

    public function down(): void
    {
        if (DB::table('ke_hoach_tap')->where('nguon_tao', 'KHACH_HANG')->exists()) {
            throw new RuntimeException('Đã có giáo án tự tạo; không gỡ nguồn và quan hệ để tránh mất lịch sử.');
        }
        DB::statement('ALTER TABLE ke_hoach_tap
            DROP FOREIGN KEY fk_t14_02_tu_tao, DROP FOREIGN KEY fk_t14_03_tu_tao,
            MODIFY huan_luyen_vien_id BIGINT UNSIGNED NOT NULL,
            MODIFY phan_cong_id BIGINT UNSIGNED NOT NULL,
            ADD CONSTRAINT fk_t14_02 FOREIGN KEY (huan_luyen_vien_id) REFERENCES ho_so_huan_luyen_vien(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
            ADD CONSTRAINT fk_t14_03 FOREIGN KEY (phan_cong_id) REFERENCES phan_cong_huan_luyen_vien(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
            DROP INDEX uq_t14_01, ADD UNIQUE KEY uq_t14_01 (khach_dang_ap_dung_id), DROP COLUMN nguon_tao');
    }
};
