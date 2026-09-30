<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../BE/vendor/autoload.php';
$ungDung = require __DIR__.'/../BE/bootstrap/app.php';
$ungDung->make(Kernel::class)->bootstrap();

function kiemTra(bool $dieuKien, string $thongBao): void
{
    if (! $dieuKien) {
        throw new RuntimeException($thongBao);
    }
}

function phaiBiChan(callable $thaoTac, int $maLoi, string $tenRangBuoc): void
{
    try {
        $thaoTac();
    } catch (QueryException $loi) {
        kiemTra((int) $loi->errorInfo[1] === $maLoi, 'Sai mã lỗi: '.$tenRangBuoc);
        kiemTra(str_contains($loi->getMessage(), $tenRangBuoc), 'Sai ràng buộc: '.$tenRangBuoc);

        return;
    }

    throw new RuntimeException('Database không chặn vi phạm: '.$tenRangBuoc);
}

function chayMigration(string $lenh): void
{
    $ketQua = Artisan::call($lenh, ['--database' => 'kiem_tra_migrations', '--force' => true]);
    kiemTra($ketQua === 0, Artisan::output());
}

$ketNoiGoc = DB::connection();
kiemTra(in_array($ketNoiGoc->getDriverName(), ['mysql', 'mariadb'], true), 'Cần kết nối MySQL/MariaDB thật.');
$phienBan = $ketNoiGoc->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
$laMariaDb = stripos($phienBan, 'MariaDB') !== false;
$maLoiCheck = $laMariaDb ? 4025 : 3819;
$tenDatabase = 'kiem_tra_migrations_'.bin2hex(random_bytes(8));
// Không nhận tên database từ tham số, không IF NOT EXISTS; chỉ dọn database vừa tự tạo.
$ketNoiGoc->statement('CREATE DATABASE `'.$tenDatabase.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$maThoat = 0;

try {
    $cauHinh = $ketNoiGoc->getConfig();
    $cauHinh['database'] = $tenDatabase;
    $cauHinh['url'] = null;
    $cauHinh['name'] = 'kiem_tra_migrations';
    unset($cauHinh['read'], $cauHinh['write']);
    config(['database.connections.kiem_tra_migrations' => $cauHinh]);
    DB::setDefaultConnection('kiem_tra_migrations');
    Schema::clearResolvedInstance('db.schema');
    $ketNoi = DB::connection();
    kiemTra($ketNoi->getName() === 'kiem_tra_migrations'
        && $ketNoi->selectOne('SELECT DATABASE() AS ten')->ten === $tenDatabase
        && Schema::getConnection()->getDatabaseName() === $tenDatabase, 'Kết nối kiểm thử phải cách ly database gốc.');

    if ($laMariaDb) {
        $ketNoi->statement('SET SESSION check_constraint_checks = OFF');
        $biChan = false;
        try {
            $migration = require __DIR__.'/../BE/database/migrations/2026_10_01_000001_create_tai_khoan_table.php';
            $migration->up();
        } catch (RuntimeException $loi) {
            $biChan = str_contains($loi->getMessage(), 'check_constraint_checks');
        } finally {
            $ketNoi->statement('SET SESSION check_constraint_checks = ON');
        }
        kiemTra($biChan && ! Schema::hasTable('tai_khoan'), 'Phải chặn CHECK bị tắt trước khi tạo bảng.');
    }

    chayMigration('migrate');
    $schema = json_decode(file_get_contents(__DIR__.'/../BE/database/design/schema.json'), true, flags: JSON_THROW_ON_ERROR);
    foreach ($schema as $bang) {
        kiemTra(Schema::hasTable($bang['ten']), 'Thiếu bảng: '.$bang['ten']);
        kiemTra(Schema::getColumnListing($bang['ten']) === array_column($bang['cot'], 0), 'Sai cột: '.$bang['ten']);
    }
    kiemTra($ketNoi->table('migrations')->count() === 31, 'Cần đủ 31 migrations nghiệp vụ/kỹ thuật.');
    $dem = fn (string $sql) => (int) $ketNoi->selectOne($sql)->so_luong;
    kiemTra($dem("SELECT COUNT(*) AS so_luong FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND UPDATE_RULE = 'RESTRICT' AND DELETE_RULE = 'RESTRICT'") === 52, 'Thiếu 52 FK RESTRICT.');
    kiemTra($dem("SELECT COUNT(*) AS so_luong FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = 'CHECK' AND CONSTRAINT_NAME LIKE 'ck\\_t%'") === 9, 'Thiếu 9 CHECK nghiệp vụ.');
    kiemTra($dem("SELECT COUNT(*) AS so_luong FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = 'UNIQUE' AND CONSTRAINT_NAME LIKE 'uq\\_t%'") === 26, 'Thiếu 26 UNIQUE nghiệp vụ.');
    kiemTra($dem("SELECT COUNT(*) AS so_luong FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND EXTRA LIKE '%STORED GENERATED%'") === 4, 'Thiếu 4 generated STORED.');

    // Dữ liệu hoàn toàn giả, chỉ ghi trong database riêng; kiểm tra lỗi SQL thực tế.
    $ketNoi->beginTransaction();
    $taiKhoan = $ketNoi->table('tai_khoan')->insertGetId(['email' => 'migration@example.test', 'password' => 'du-lieu-kiem-thu', 'vai_tro' => 'KHACH_HANG', 'trang_thai' => 'HOAT_DONG']);
    $khachHang = $ketNoi->table('ho_so_khach_hang')->insertGetId(['tai_khoan_id' => $taiKhoan]);
    $taiKhoanPt = $ketNoi->table('tai_khoan')->insertGetId(['email' => 'pt@example.test', 'password' => 'du-lieu-kiem-thu', 'vai_tro' => 'HUAN_LUYEN_VIEN', 'trang_thai' => 'HOAT_DONG']);
    $pt = $ketNoi->table('ho_so_huan_luyen_vien')->insertGetId(['tai_khoan_id' => $taiKhoanPt]);
    $goi = ['ten_goi' => 'Gói kiểm thử', 'gia' => 100000, 'co_chatbot' => 1, 'so_luot_chatbot_moi_ngay' => 10, 'so_buoi_pt' => 10, 'thoi_han_ngay' => 30, 'trang_thai' => 'DANG_BAN'];
    $goiId = $ketNoi->table('goi_tap')->insertGetId($goi);
    foreach ([['thoi_han_ngay' => 0], ['so_luot_chatbot_moi_ngay' => 0], ['co_chatbot' => 0, 'so_luot_chatbot_moi_ngay' => 0, 'so_buoi_pt' => 0]] as $viTri => $sai) {
        phaiBiChan(fn () => $ketNoi->table('goi_tap')->insert(array_replace($goi, $sai)), $maLoiCheck, 'ck_t04_0'.($viTri + 1));
    }
    phaiBiChan(fn () => $ketNoi->table('ho_so_khach_hang')->insert(['tai_khoan_id' => 999999]), 1452, 'fk_t02_01');
    phaiBiChan(fn () => $ketNoi->table('tai_khoan')->where('id', $taiKhoan)->delete(), 1451, 'fk_t02_01');

    $dangKy = ['khach_hang_id' => $khachHang, 'goi_tap_id' => $goiId, 'client_request_id' => 'don-1', 'ma_don_payos' => 1, 'ten_goi_snapshot' => 'Gói kiểm thử', 'gia_snapshot' => 100000, 'co_chatbot_snapshot' => 1, 'so_buoi_pt_snapshot' => 10, 'so_buoi_con_lai' => 10, 'trang_thai' => 'DANG_SU_DUNG'];
    $dangKyId = $ketNoi->table('dang_ky_goi_tap')->insertGetId($dangKy);
    phaiBiChan(fn () => $ketNoi->table('dang_ky_goi_tap')->where('id', $dangKyId)->update(['so_buoi_con_lai' => 11]), $maLoiCheck, 'ck_t05_01');
    phaiBiChan(fn () => $ketNoi->table('thanh_toan')->insert(['dang_ky_goi_tap_id' => $dangKyId, 'ma_giao_dich' => 'gia', 'so_tien' => 100000, 'so_tien_hoan' => 100001, 'trang_thai' => 'DA_THANH_TOAN']), $maLoiCheck, 'ck_t06_01');

    $phanCong = ['khach_hang_id' => $khachHang, 'huan_luyen_vien_id' => $pt, 'nguoi_phan_cong_id' => $taiKhoanPt, 'bat_dau_luc' => '2026-10-01 08:00:00'];
    $phanCongId = $ketNoi->table('phan_cong_huan_luyen_vien')->insertGetId($phanCong);
    phaiBiChan(fn () => $ketNoi->table('phan_cong_huan_luyen_vien')->where('id', $phanCongId)->update(['ket_thuc_luc' => '2026-10-01 07:00:00']), $maLoiCheck, 'ck_t07_01');
    $khungGio = ['huan_luyen_vien_id' => $pt, 'bat_dau_luc' => '2026-10-02 08:00:00', 'ket_thuc_luc' => '2026-10-02 09:00:00', 'trang_thai' => 'MO'];
    $khungGioId = $ketNoi->table('khung_gio_huan_luyen_vien')->insertGetId($khungGio);
    phaiBiChan(fn () => $ketNoi->table('khung_gio_huan_luyen_vien')->where('id', $khungGioId)->update(['ket_thuc_luc' => '2026-10-02 08:30:00']), $maLoiCheck, 'ck_t08_01');
    $lichHen = ['khach_hang_id' => $khachHang, 'huan_luyen_vien_id' => $pt, 'phan_cong_id' => $phanCongId, 'khung_gio_id' => $khungGioId, 'dang_ky_goi_tap_id' => $dangKyId, 'client_request_id' => 'hen-1', 'bat_dau_luc' => '2026-10-02 08:00:00', 'ket_thuc_luc' => '2026-10-02 09:00:00', 'trang_thai' => 'CHO_XAC_NHAN'];
    $lichHenId = $ketNoi->table('lich_hen_huan_luyen')->insertGetId($lichHen);
    phaiBiChan(fn () => $ketNoi->table('lich_hen_huan_luyen')->where('id', $lichHenId)->update(['ket_thuc_luc' => '2026-10-02 08:30:00']), $maLoiCheck, 'ck_t09_01');
    $keHoach = ['khach_hang_id' => $khachHang, 'huan_luyen_vien_id' => $pt, 'phan_cong_id' => $phanCongId, 'ten_ke_hoach' => 'Kiểm thử', 'trang_thai' => 'DANG_AP_DUNG'];
    $keHoachId = $ketNoi->table('ke_hoach_tap')->insertGetId($keHoach);

    // Cả 4 khóa phải chặn record đang mở trùng nhau, rồi giải phóng khi đóng trạng thái.
    foreach ([
        ['dang_ky_goi_tap', $dangKyId, array_replace($dangKy, ['client_request_id' => 'don-2', 'ma_don_payos' => 2]), 'khach_dang_dung_id', $khachHang, 'uq_t05_04', ['trang_thai' => 'HET_HAN']],
        ['phan_cong_huan_luyen_vien', $phanCongId, $phanCong, 'khach_dang_phan_cong_id', $khachHang, 'uq_t07_01', ['ket_thuc_luc' => '2026-10-03 08:00:00']],
        ['lich_hen_huan_luyen', $lichHenId, array_replace($lichHen, ['client_request_id' => 'hen-2', 'trang_thai' => 'DA_XAC_NHAN']), 'khung_gio_dang_giu_id', $khungGioId, 'uq_t09_01', ['trang_thai' => 'DA_HUY']],
        ['ke_hoach_tap', $keHoachId, $keHoach, 'khach_dang_ap_dung_id', $khachHang, 'uq_t14_01', ['trang_thai' => 'LUU_TRU']],
    ] as [$bang, $id, $banMoi, $cot, $giaTri, $khoa, $dong]) {
        kiemTra((int) $ketNoi->table($bang)->where('id', $id)->value($cot) === $giaTri, 'Generated sai: '.$cot);
        phaiBiChan(fn () => $ketNoi->table($bang)->insert($banMoi), 1062, $khoa);
        $ketNoi->table($bang)->where('id', $id)->update($dong);
        kiemTra($ketNoi->table($bang)->where('id', $id)->value($cot) === null, 'Chưa giải phóng: '.$cot);
        $ketNoi->table($bang)->insert($banMoi);
        kiemTra($ketNoi->table($bang)->where('id', $id)->exists(), 'Mất lịch sử: '.$bang);
    }
    $ketNoi->rollBack();

    chayMigration('migrate:rollback');
    kiemTra($ketNoi->table('migrations')->count() === 0, 'Rollback phải hoàn tất toàn bộ batch kiểm thử.');
    foreach ($schema as $bang) {
        kiemTra(! Schema::hasTable($bang['ten']), 'Rollback còn bảng: '.$bang['ten']);
    }
    chayMigration('migrate');
    kiemTra($ketNoi->table('migrations')->count() === 31, 'Migrate lần hai chưa đủ.');
    echo 'PASS '.$phienBan.': migrate → rollback → migrate; 28 bảng/303 cột/52 FK/26 UNIQUE/9 CHECK/4 generated; 8 CHECK chặn dữ liệu sai; FK giữ lịch sử; 4 UNIQUE generated chặn trùng và giải phóng.'.PHP_EOL;
} catch (Throwable $loi) {
    fwrite(STDERR, $loi->getMessage().PHP_EOL);
    $maThoat = 1;
} finally {
    if (isset($ketNoi) && $ketNoi->transactionLevel() > 0) {
        $ketNoi->rollBack(0);
    }
    DB::purge('kiem_tra_migrations');
    $ketNoiGoc->statement('DROP DATABASE `'.$tenDatabase.'`');
}

exit($maThoat);
