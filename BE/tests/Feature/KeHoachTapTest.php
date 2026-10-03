<?php

namespace Tests\Feature;

use App\Models\BaiTap;
use App\Models\BaiTapTrongKeHoach;
use App\Models\KeHoachTap;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use App\Services\KeHoachTapService;
use App\Services\TaiKhoanService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Tests\TestCase;

class KeHoachTapTest extends TestCase
{
    private static ?string $db = null;

    private static ?PDO $pdo = null;

    private TaiKhoan $kh;

    private TaiKhoan $pt;

    private TaiKhoan $admin;

    private PhanCongHuanLuyenVien $pc;

    private int $baiId;

    private int $khId;

    private bool $daCommit = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (self::$db === null) {
            $cfg = config('database.connections.mysql');
            $cfg['database'] = null;
            $cfg['url'] = null;
            config(['database.connections.may_chu_ke_hoach' => $cfg]);
            self::$pdo = DB::connection('may_chu_ke_hoach')->getPdo();
            self::$db = 'kiem_tra_ke_hoach_'.bin2hex(random_bytes(8));
            self::$pdo->exec('CREATE DATABASE `'.self::$db.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
        config(['database.connections.mysql.database' => self::$db, 'database.connections.mysql.url' => null, 'database.default' => 'mysql']);
        DB::purge('mysql');
        $this->assertSame(self::$db, DB::selectOne('SELECT DATABASE() AS ten')->ten);
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        DB::beginTransaction();
        $this->kh = $this->taiKhoan(TaiKhoan::KHACH_HANG);
        $this->pt = $this->taiKhoan(TaiKhoan::HUAN_LUYEN_VIEN);
        $this->admin = $this->taiKhoan(TaiKhoan::ADMIN);
        $this->khId = $this->kh->hoSoKhachHang->id;
        $this->pc = PhanCongHuanLuyenVien::create(['khach_hang_id' => $this->khId, 'huan_luyen_vien_id' => $this->pt->hoSoHuanLuyenVien->id, 'nguoi_phan_cong_id' => $this->admin->id, 'bat_dau_luc' => now()]);
        $nhom = DB::table('nhom_co')->insertGetId(['ma_nhom_co' => 'demo', 'ten_nhom_co' => 'Ngực', 'ten_nguon' => 'chest', 'trang_thai' => 'HOAT_DONG']);
        $this->baiId = BaiTap::create(['nhom_co_id' => $nhom, 'ten_bai_tap' => 'Chống đẩy', 'huong_dan' => ['vi' => 'Giữ lưng thẳng'], 'cac_buoc' => ['vi' => ['Hạ người chậm', 'Đẩy lên có kiểm soát']], 'trang_thai' => 'HOAT_DONG'])->id;
        $this->doiNguoi($this->pt);
    }

    protected function tearDown(): void
    {
        Event::forget('eloquent.creating: '.BaiTapTrongKeHoach::class);
        Event::forget('eloquent.updating: '.KeHoachTap::class);
        $this->travelBack();
        if (DB::transactionLevel()) {
            DB::rollBack(0);
        }
        if ($this->daCommit) {
            $this->assertSame(self::$db, DB::selectOne('SELECT DATABASE() AS ten')->ten);
            Artisan::call('migrate:fresh', ['--force' => true]);
        }
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$db !== null && preg_match('/^kiem_tra_ke_hoach_[a-f0-9]{16}$/D', self::$db)) {
            self::$pdo->exec('DROP DATABASE `'.self::$db.'`');
        }
        self::$db = null;
        self::$pdo = null;
        parent::tearDownAfterClass();
    }

    private function taiKhoan(string $vaiTro): TaiKhoan
    {
        return app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Học viên kiểm thử', 'email' => Str::uuid().'@example.test', 'password' => 'Demo123456!'], $vaiTro);
    }

    private function doiNguoi(TaiKhoan $nguoi): void
    {
        Auth::forgetGuards();
        $this->actingAs($nguoi, 'web');
    }

    private function noiDung(array $them = []): array
    {
        return ['ten_ke_hoach' => 'Toàn thân cá nhân', 'muc_tieu' => 'Tăng cơ', 'so_ngay_tap' => 1, 'giao_an_mau_id' => null, 'client_request_id' => (string) Str::uuid(), 'bai_tap' => [['bai_tap_id' => $this->baiId, 'ngay_thu' => 1, 'thu_tu' => 1, 'so_hiep' => 3, 'so_lan_lap' => 12, 'nghi_giay' => 60, 'ghi_chu' => null, 'muc_ta_kg' => '10.50']], ...$them];
    }

    private function tao(array $them = []): array
    {
        return $this->postJson('/api/v1/pt/hoc-vien/'.$this->khId.'/ke-hoach', $this->noiDung($them))->assertCreated()->json('data');
    }

    private function gui(array $k): array
    {
        return $this->postJson('/api/v1/pt/ke-hoach/'.$k['id'].'/gui', ['updated_at' => $k['updated_at']])->assertOk()->json('data');
    }

    public function test_tao_gui_xac_nhan_va_thong_bao_dung_nguoi(): void
    {
        $k = $this->tao();
        $this->getJson('/api/v1/pt/hoc-vien')->assertOk()->assertJsonPath('data.0.id', $this->khId);
        $this->doiNguoi($this->kh);
        $this->getJson('/api/v1/khach-hang/ke-hoach')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/khach-hang/ke-hoach/'.$k['id'])->assertNotFound();
        $this->doiNguoi($this->pt);
        $gui = $this->gui($k);
        $this->postJson('/api/v1/pt/ke-hoach/'.$k['id'].'/gui', ['updated_at' => $k['updated_at']])->assertOk()->assertJsonPath('data.han_duyet', $gui['han_duyet']);
        $this->assertDatabaseCount('notifications', 1);
        $this->doiNguoi($this->kh);
        $this->getJson('/api/v1/thong-bao')->assertJsonCount(1, 'data')->assertJsonPath('data.0.duong_dan', '/khach-hang/ke-hoach/'.$k['id']);
        $this->getJson('/api/v1/khach-hang/ke-hoach/'.$k['id'])->assertJsonPath('data.bai_tap.0.muc_ta_kg', '10.50');
        $url = '/api/v1/khach-hang/ke-hoach/'.$k['id'].'/xac-nhan';
        $this->postJson($url, ['updated_at' => $gui['updated_at']])->assertOk()->assertJsonPath('data.trang_thai', 'DANG_AP_DUNG');
        $this->postJson($url, ['updated_at' => $gui['updated_at']])->assertOk();
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseCount('lich_tap', 0);
        $this->doiNguoi($this->pt);
        $this->getJson('/api/v1/thong-bao')->assertJsonCount(1, 'data')->assertJsonPath('data.0.duong_dan', '/pt/ke-hoach/'.$k['id']);
        $this->doiNguoi($this->admin);
        $this->getJson('/api/v1/thong-bao')->assertJsonCount(0, 'data');
    }

    public function test_quyen_theo_tai_nguyen_khong_chi_vai_tro(): void
    {
        $k = $this->tao();
        $gui = $this->gui($k);
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN] as $vaiTro) {
            $this->doiNguoi($this->taiKhoan($vaiTro));
            $goc = $vaiTro === TaiKhoan::KHACH_HANG ? 'khach-hang' : 'pt';
            $this->getJson('/api/v1/'.$goc.'/ke-hoach/'.$k['id'])->assertNotFound();
            $this->postJson('/api/v1/'.$goc.'/ke-hoach/'.$k['id'].'/'.($goc === 'pt' ? 'gui' : 'xac-nhan'), ['updated_at' => $gui['updated_at']])->assertNotFound();
        }
        $this->doiNguoi($this->admin);
        $this->getJson('/api/v1/pt/hoc-vien')->assertForbidden();
        $this->getJson('/api/v1/khach-hang/ke-hoach')->assertForbidden();
        $this->pt->trang_thai = TaiKhoan::BI_KHOA;
        $this->pt->save();
        $this->doiNguoi($this->pt);
        $this->getJson('/api/v1/pt/ke-hoach/'.$k['id'])->assertForbidden();
    }

    public function test_uuid_giu_hash_tao_ban_dau_khi_da_sua_va_chong_trung(): void
    {
        $data = $this->noiDung();
        $url = '/api/v1/pt/hoc-vien/'.$this->khId.'/ke-hoach';
        $k = $this->postJson($url, $data)->assertCreated()->json('data');
        $sua = [...array_diff_key($data, ['client_request_id' => true]), 'ten_ke_hoach' => 'Đã sửa', 'updated_at' => $k['updated_at']];
        $this->putJson('/api/v1/pt/ke-hoach/'.$k['id'], $sua)->assertOk();
        $this->putJson('/api/v1/pt/ke-hoach/'.$k['id'], $sua)->assertConflict();
        $this->postJson($url, $data)->assertOk()->assertJsonPath('data.id', $k['id']);
        $this->postJson($url, [...$data, 'ten_ke_hoach' => 'Khác'])->assertConflict();
        $this->assertDatabaseCount('ke_hoach_tap', 1);
    }

    public function test_validation_va_bai_ngung_chan_gui(): void
    {
        foreach (['khach_hang_id' => 1, 'trang_thai' => 'DANG_AP_DUNG', 'so_ngay_tap' => 31, 'giao_an_mau_id' => 99999] as $truong => $giaTri) {
            $this->postJson('/api/v1/pt/hoc-vien/'.$this->khId.'/ke-hoach', $this->noiDung([$truong => $giaTri]))->assertUnprocessable();
        }
        foreach (['-1', '1001', '10.555'] as $ta) {
            $data = $this->noiDung();
            $data['bai_tap'][0]['muc_ta_kg'] = $ta;
            $this->postJson('/api/v1/pt/hoc-vien/'.$this->khId.'/ke-hoach', $data)->assertUnprocessable();
        }
        $k = $this->tao(['bai_tap' => []]);
        $this->postJson('/api/v1/pt/ke-hoach/'.$k['id'].'/gui', ['updated_at' => $k['updated_at']])->assertUnprocessable();
        $k = $this->tao();
        BaiTap::findOrFail($this->baiId)->update(['trang_thai' => 'NGUNG_SU_DUNG']);
        $this->postJson('/api/v1/pt/ke-hoach/'.$k['id'].'/gui', ['updated_at' => $k['updated_at']])->assertUnprocessable();
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_noi_dung_da_gui_bat_bien_va_luu_snapshot(): void
    {
        $k = $this->gui($this->tao());
        BaiTap::find($this->baiId)->update(['ten_bai_tap' => 'Tên catalog mới', 'huong_dan' => ['Khác'], 'trang_thai' => 'NGUNG_SU_DUNG']);
        $this->getJson('/api/v1/pt/ke-hoach/'.$k['id'])->assertJsonPath('data.bai_tap.0.ten_bai_tap', 'Chống đẩy')->assertJsonPath('data.bai_tap.0.huong_dan', 'Giữ lưng thẳng')->assertJsonPath('data.bai_tap.0.cac_buoc.0', 'Hạ người chậm');
        $this->putJson('/api/v1/pt/ke-hoach/'.$k['id'], [...array_diff_key($this->noiDung(), ['client_request_id' => true]), 'updated_at' => $k['updated_at']])->assertConflict();
    }

    public function test_moc_24_gio_va_ban_qua_han_van_xem_duoc(): void
    {
        $k = $this->gui($this->tao());
        $this->doiNguoi($this->kh);
        $this->travelTo(Carbon::parse(KeHoachTap::findOrFail($k['id'])->han_duyet));
        $this->getJson('/api/v1/khach-hang/ke-hoach/'.$k['id'])->assertJsonPath('data.trang_thai_hien_thi', 'QUA_HAN')->assertJsonPath('data.co_the_xac_nhan', false);
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$k['id'].'/xac-nhan', ['updated_at' => $k['updated_at']])->assertConflict();
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_thay_the_luu_lich_su_va_vo_hieu_ban_de_xuat_khac(): void
    {
        $a = $this->gui($this->tao());
        $b = $this->gui($this->tao());
        $nhap = $this->tao();
        $this->doiNguoi($this->kh);
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$a['id'].'/xac-nhan', ['updated_at' => $a['updated_at']])->assertOk();
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$b['id'].'/xac-nhan', ['updated_at' => $b['updated_at']])->assertConflict();
        $this->doiNguoi($this->pt);
        $this->postJson('/api/v1/pt/ke-hoach/'.$nhap['id'].'/gui', ['updated_at' => $nhap['updated_at']])->assertConflict();
        $moi = $this->gui($this->tao());
        $this->assertSame($a['id'], $moi['thay_the_ke_hoach_id']);
        $this->doiNguoi($this->kh);
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$moi['id'].'/xac-nhan', ['updated_at' => $moi['updated_at']])->assertOk();
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $a['id'], 'trang_thai' => 'LUU_TRU']);
        $this->assertSame(1, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->count());
        $this->assertDatabaseCount('bai_tap_trong_ke_hoach', 4);
    }

    public function test_doi_pt_thu_hoi_doc_va_xac_nhan_nhung_giu_lich_su_kh(): void
    {
        $k = $this->gui($this->tao());
        $this->pc->update(['ket_thuc_luc' => now()]);
        $ptMoi = $this->taiKhoan(TaiKhoan::HUAN_LUYEN_VIEN);
        PhanCongHuanLuyenVien::create(['khach_hang_id' => $this->khId, 'huan_luyen_vien_id' => $ptMoi->hoSoHuanLuyenVien->id, 'nguoi_phan_cong_id' => $this->admin->id, 'bat_dau_luc' => now()]);
        $this->getJson('/api/v1/pt/ke-hoach/'.$k['id'])->assertNotFound();
        $this->postJson('/api/v1/pt/ke-hoach/'.$k['id'].'/gui', ['updated_at' => $k['updated_at']])->assertNotFound();
        $this->doiNguoi($ptMoi);
        $this->getJson('/api/v1/pt/ke-hoach/'.$k['id'])->assertOk();
        $this->postJson('/api/v1/pt/ke-hoach/'.$k['id'].'/huy', ['updated_at' => $k['updated_at']])->assertNotFound();
        $this->doiNguoi($this->kh);
        $this->getJson('/api/v1/khach-hang/ke-hoach/'.$k['id'])->assertOk()->assertJsonPath('data.co_the_xac_nhan', false);
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$k['id'].'/xac-nhan', ['updated_at' => $k['updated_at']])->assertNotFound();
    }

    public function test_loi_ghi_dong_rollback_ca_parent_va_children(): void
    {
        Event::listen('eloquent.creating: '.BaiTapTrongKeHoach::class, fn () => throw new RuntimeException('loi-ghi'));
        try {
            app(KeHoachTapService::class)->tao($this->pt, $this->khId, $this->noiDung());
            $this->fail('Phải rollback.');
        } catch (RuntimeException $e) {
            $this->assertSame('loi-ghi', $e->getMessage());
        }
        $this->assertDatabaseCount('ke_hoach_tap', 0);
        $this->assertDatabaseCount('bai_tap_trong_ke_hoach', 0);
    }

    public function test_loi_luu_trang_thai_rollback_thong_bao_va_thoi_han(): void
    {
        $k = $this->tao();
        Event::listen('eloquent.updating: '.KeHoachTap::class, fn () => throw new RuntimeException('loi-luu-trang-thai'));
        try {
            app(KeHoachTapService::class)->thaoTac($this->pt, $k['id'], 'gui', $k['updated_at']);
            $this->fail('Phải rollback.');
        } catch (RuntimeException $e) {
            $this->assertSame('loi-luu-trang-thai', $e->getMessage());
        }
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $k['id'], 'trang_thai' => 'NHAP', 'gui_luc' => null, 'han_duyet' => null]);
    }

    public function test_pt_bi_khoa_chan_xac_nhan_moi_nhung_kh_van_doc_duoc(): void
    {
        $k = $this->gui($this->tao());
        $this->pt->trang_thai = TaiKhoan::BI_KHOA;
        $this->pt->save();
        $this->doiNguoi($this->kh);
        $this->getJson('/api/v1/khach-hang/ke-hoach/'.$k['id'])->assertOk();
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$k['id'].'/xac-nhan', ['updated_at' => $k['updated_at']])->assertConflict();
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_migration_giu_ngay_tap_cu_va_chan_rollback_du_lieu_moi(): void
    {
        $cu = KeHoachTap::create(['khach_hang_id' => $this->khId, 'huan_luyen_vien_id' => $this->pt->hoSoHuanLuyenVien->id, 'phan_cong_id' => $this->pc->id, 'ten_ke_hoach' => 'Giáo án cũ', 'trang_thai' => 'DANG_AP_DUNG']);
        $dong = $cu->cacBaiTap()->create(['bai_tap_id' => $this->baiId, 'ngay_thu' => 2, 'thu_tu' => 1, 'ten_bai_tap_snapshot' => 'Tên cũ', 'so_hiep' => 3, 'so_lan_lap' => 12, 'nghi_giay' => 60]);
        $m = require database_path('migrations/2026_10_03_000036_bo_sung_ke_hoach_ca_nhan.php');
        // ALTER tự commit trên MariaDB, nên chỉ làm trong DB kiểm thử đã xác minh và dọn ở tearDown.
        DB::commit();
        $this->daCommit = true;
        $m->down();
        $m->up();
        $this->assertSame(2, (int) $cu->fresh()->so_ngay_tap);
        $this->assertSame('Tên cũ', $dong->fresh()->ten_bai_tap_snapshot);
        $k = app(KeHoachTapService::class)->tao($this->pt, $this->khId, $this->noiDung());
        try {
            $m->down();
            $this->fail('Không được gỡ dữ liệu mới.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Đã có giáo án cá nhân', $e->getMessage());
        }
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $k->id, 'ma_yeu_cau_tao' => $k->ma_yeu_cau_tao]);
        $this->assertSame('10.50', $k->cacBaiTap->first()->muc_ta_kg);
    }

    public function test_hai_process_xac_nhan_cung_ban_khong_trung_thong_bao(): void
    {
        $k = $this->gui($this->tao());
        $ketQua = $this->haiWorker([$k, $k], 'xac-nhan', $this->kh);
        $this->assertTrue($ketQua[0]['ok']);
        $this->assertTrue($ketQua[1]['ok']);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertSame(1, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->count());
    }

    public function test_hai_process_xac_nhan_hai_ban_chi_mot_ban_ap_dung(): void
    {
        $a = $this->gui($this->tao());
        $b = $this->gui($this->tao());
        $ketQua = $this->haiWorker([$a, $b], 'xac-nhan', $this->kh);
        $this->assertSame(1, count(array_filter($ketQua, fn ($k) => $k['ok'])));
        $thatBai = array_values(array_filter($ketQua, fn ($k) => ! $k['ok']));
        $this->assertSame(409, $thatBai[0]['status']);
        $this->assertSame(1, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->count());
        $this->assertDatabaseCount('notifications', 3);
    }

    public function test_hai_process_gui_chi_mot_thong_bao_va_thoi_han(): void
    {
        $k = $this->tao();
        $ketQua = $this->haiWorker([$k, $k], 'gui', $this->pt);
        $this->assertTrue($ketQua[0]['ok']);
        $this->assertTrue($ketQua[1]['ok']);
        $this->assertDatabaseCount('notifications', 1);
    }

    private function tuTao(array $them = []): array
    {
        $this->doiNguoi($this->kh);

        return $this->postJson('/api/v1/khach-hang/ke-hoach', $this->noiDung($them))->assertCreated()->assertJsonPath('data.nguon_tao', 'KHACH_HANG')->json('data');
    }

    public function test_kh_khong_goi_khong_pt_tao_sua_ap_dung_va_luu_tru(): void
    {
        $this->pc->update(['ket_thuc_luc' => now()]);
        $body = $this->noiDung();
        $k = $this->tuTao($body);
        $this->postJson('/api/v1/khach-hang/ke-hoach', $body)->assertOk()->assertJsonPath('data.id', $k['id']);
        $this->getJson('/api/v1/khach-hang/ke-hoach')->assertJsonCount(1, 'data');
        $sua = [...$body, 'updated_at' => $k['updated_at'], 'ten_ke_hoach' => 'Tự tập'];
        unset($sua['client_request_id']);
        $k = $this->putJson('/api/v1/khach-hang/ke-hoach/'.$k['id'], $sua)->assertOk()->json('data');
        $url = '/api/v1/khach-hang/ke-hoach/'.$k['id'];
        $a = $this->postJson($url.'/ap-dung', ['updated_at' => $k['updated_at']])->assertOk()->assertJsonPath('data.trang_thai', 'DANG_AP_DUNG')->json('data');
        $this->postJson($url.'/ap-dung', ['updated_at' => $k['updated_at']])->assertOk()->assertJsonPath('data.updated_at', $a['updated_at']);
        $this->putJson($url, [...$sua, 'updated_at' => $a['updated_at']])->assertConflict();
        $luu = $this->postJson($url.'/luu-tru', ['updated_at' => $a['updated_at']])->assertOk()->assertJsonPath('data.trang_thai', 'LUU_TRU')->json('data');
        $this->postJson($url.'/ap-dung', ['updated_at' => $luu['updated_at']])->assertOk()->assertJsonPath('data.trang_thai', 'DANG_AP_DUNG');
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $k['id'], 'huan_luyen_vien_id' => null, 'phan_cong_id' => null, 'gui_luc' => null]);
        $this->assertDatabaseCount('dang_ky_goi_tap', 0);
        $this->assertDatabaseCount('lich_tap', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_pt_doc_tat_ca_ban_tu_tao_nhung_khong_sua_va_thu_hoi_khi_doi_pt(): void
    {
        $k = $this->tuTao();
        $this->doiNguoi($this->pt);
        $this->getJson('/api/v1/pt/hoc-vien/'.$this->khId.'/ke-hoach?nguon_tao=KHACH_HANG')->assertJsonCount(1, 'data')->assertJsonPath('data.0.co_the_sua', false);
        $this->getJson('/api/v1/pt/ke-hoach/'.$k['id'])->assertOk()->assertJsonPath('data.co_the_gui', false);
        $body = $this->noiDung(['updated_at' => $k['updated_at']]);
        unset($body['client_request_id']);
        $this->putJson('/api/v1/pt/ke-hoach/'.$k['id'], $body)->assertNotFound();
        $this->postJson('/api/v1/pt/ke-hoach/'.$k['id'].'/huy', ['updated_at' => $k['updated_at']])->assertNotFound();
        $this->doiNguoi($this->kh);
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$k['id'].'/ap-dung', ['updated_at' => $k['updated_at']])->assertOk();
        $this->doiNguoi($this->pt);
        $this->getJson('/api/v1/pt/ke-hoach/'.$k['id'])->assertJsonPath('data.trang_thai', 'DANG_AP_DUNG');
        $this->pc->update(['ket_thuc_luc' => now()]);
        $moi = $this->taiKhoan(TaiKhoan::HUAN_LUYEN_VIEN);
        PhanCongHuanLuyenVien::create(['khach_hang_id' => $this->khId, 'huan_luyen_vien_id' => $moi->hoSoHuanLuyenVien->id, 'nguoi_phan_cong_id' => $this->admin->id, 'bat_dau_luc' => now()]);
        $this->getJson('/api/v1/pt/ke-hoach/'.$k['id'])->assertNotFound();
        $this->doiNguoi($moi);
        $this->getJson('/api/v1/pt/ke-hoach/'.$k['id'])->assertOk();
    }

    public function test_nguoi_khac_khong_doc_sua_ap_dung_va_khong_gia_mao_nguon(): void
    {
        $k = $this->tuTao();
        $this->postJson('/api/v1/khach-hang/ke-hoach', $this->noiDung(['nguon_tao' => 'PT', 'phan_cong_id' => $this->pc->id]))->assertUnprocessable();
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$k['id'].'/xac-nhan', ['updated_at' => $k['updated_at']])->assertNotFound();
        $this->doiNguoi($this->taiKhoan(TaiKhoan::KHACH_HANG));
        $this->getJson('/api/v1/khach-hang/ke-hoach/'.$k['id'])->assertNotFound();
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$k['id'].'/ap-dung', ['updated_at' => $k['updated_at']])->assertNotFound();
        $this->doiNguoi($this->admin);
        $this->postJson('/api/v1/khach-hang/ke-hoach', $this->noiDung())->assertForbidden();
    }

    public function test_ap_dung_tu_tao_thay_pt_va_chon_lai_ban_luu_tru(): void
    {
        $pt = $this->gui($this->tao());
        $this->doiNguoi($this->kh);
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$pt['id'].'/xac-nhan', ['updated_at' => $pt['updated_at']])->assertOk();
        $a = $this->tuTao();
        $b = $this->tuTao();
        foreach ([$a, $b] as $k) {
            $this->postJson('/api/v1/khach-hang/ke-hoach/'.$k['id'].'/ap-dung', ['updated_at' => $k['updated_at']])->assertOk();
        }
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $pt['id'], 'trang_thai' => 'LUU_TRU']);
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $a['id'], 'trang_thai' => 'LUU_TRU']);
        // Retry cũ của bản đã bị thay không được tự kích hoạt lại.
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$a['id'].'/ap-dung', ['updated_at' => $a['updated_at']])->assertConflict();
        $a = $this->getJson('/api/v1/khach-hang/ke-hoach/'.$a['id'])->assertOk()->json('data');
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$a['id'].'/ap-dung', ['updated_at' => $a['updated_at']])->assertOk();
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $b['id'], 'trang_thai' => 'LUU_TRU']);
        $this->assertSame(1, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->count());
        $this->getJson('/api/v1/khach-hang/ke-hoach?nguon_tao=PT')->assertJsonCount(1, 'data');
    }

    public function test_tu_tao_validate_ngay_thieu_catalog_ngung_va_version_cu(): void
    {
        $k = $this->tuTao(['so_ngay_tap' => 2]);
        $url = '/api/v1/khach-hang/ke-hoach/'.$k['id'];
        $this->postJson($url.'/ap-dung', ['updated_at' => $k['updated_at']])->assertUnprocessable();
        $this->postJson($url.'/huy', ['updated_at' => '2000-01-01 00:00:00.000000'])->assertConflict();
        $this->postJson($url.'/huy', ['updated_at' => $k['updated_at']])->assertOk()->assertJsonPath('data.trang_thai', 'DA_HUY');
        $a = $this->tuTao();
        BaiTap::findOrFail($this->baiId)->update(['trang_thai' => 'NGUNG_SU_DUNG']);
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$a['id'].'/ap-dung', ['updated_at' => $a['updated_at']])->assertUnprocessable();
        $this->assertSame(0, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->count());
    }

    public function test_loi_ap_dung_tu_tao_rollback_ban_cu_va_snapshot(): void
    {
        $a = $this->tuTao();
        app(KeHoachTapService::class)->thaoTac($this->kh, $a['id'], 'ap-dung', $a['updated_at']);
        $b = $this->tuTao();
        BaiTap::findOrFail($this->baiId)->update(['ten_tieng_viet' => 'Tên mới']);
        Event::listen('eloquent.updating: '.KeHoachTap::class, function ($k) use ($b) {
            if ($k->id === $b['id']) {
                throw new RuntimeException('loi-ap-dung');
            }
        });
        try {
            app(KeHoachTapService::class)->thaoTac($this->kh, $b['id'], 'ap-dung', $b['updated_at']);
            $this->fail('Phải rollback.');
        } catch (RuntimeException $e) {
            $this->assertSame('loi-ap-dung', $e->getMessage());
        }
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $a['id'], 'trang_thai' => 'DANG_AP_DUNG']);
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $b['id'], 'trang_thai' => 'NHAP']);
        $this->assertDatabaseHas('bai_tap_trong_ke_hoach', ['ke_hoach_tap_id' => $b['id'], 'ten_bai_tap_snapshot' => 'Chống đẩy']);
    }

    public function test_hai_process_tu_ap_dung_chi_mot_ban_dang_dung(): void
    {
        $a = $this->tuTao();
        $b = $this->tuTao();
        $kq = $this->haiWorker([$a, $b], 'ap-dung', $this->kh);
        $this->assertTrue($kq[0]['ok']);
        $this->assertTrue($kq[1]['ok']);
        $this->assertSame(1, KeHoachTap::where('nguon_tao', 'KHACH_HANG')->where('trang_thai', 'DANG_AP_DUNG')->count());
        $this->assertSame(1, KeHoachTap::where('nguon_tao', 'KHACH_HANG')->where('trang_thai', 'LUU_TRU')->count());
    }

    public function test_migration_tu_tao_giu_ban_pt_cu_va_chan_rollback(): void
    {
        $pt = $this->tao();
        DB::commit();
        $this->daCommit = true;
        $m = require database_path('migrations/2026_10_03_000037_cho_phep_khach_hang_tao_giao_an.php');
        $m->down();
        $m->up();
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $pt['id'], 'nguon_tao' => 'PT', 'phan_cong_id' => $this->pc->id]);
        $k = $this->tuTao();
        try {
            $m->down();
            $this->fail('Không được gỡ dữ liệu tự tạo.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Đã có giáo án tự tạo', $e->getMessage());
        }
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $k['id'], 'nguon_tao' => 'KHACH_HANG']);
    }

    public function test_an_hien_lai_giu_snapshot_trang_thai_va_loc_danh_sach(): void
    {
        $this->pc->update(['ket_thuc_luc' => now()]);
        $k = $this->tuTao();
        $url = '/api/v1/khach-hang/ke-hoach/'.$k['id'];
        $huy = $this->postJson($url.'/huy', ['updated_at' => $k['updated_at']])->assertOk()->json('data');
        $snapshot = KeHoachTap::findOrFail($k['id'])->cacBaiTap->toArray();
        $an = $this->postJson($url.'/an', ['updated_at' => $huy['updated_at']])->assertOk()->assertJsonPath('data.da_an', true)->assertJsonPath('data.co_the_hien_lai', true)->assertJsonPath('data.trang_thai', 'DA_HUY')->json('data');
        $this->postJson($url.'/an', ['updated_at' => $huy['updated_at']])->assertOk()->assertJsonPath('data.updated_at', $an['updated_at'])->assertJsonPath('data.khach_an_luc', $an['khach_an_luc']);
        $this->getJson('/api/v1/khach-hang/ke-hoach')->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/khach-hang/ke-hoach?da_an=1&nguon_tao=KHACH_HANG')->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v1/khach-hang/ke-hoach?da_an=1&nguon_tao=PT')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/khach-hang/ke-hoach?da_an=sai')->assertUnprocessable();
        $this->getJson($url)->assertOk()->assertJsonPath('data.da_an', true);
        $this->postJson($url.'/hien-lai', ['updated_at' => $huy['updated_at']])->assertConflict();
        $hien = $this->postJson($url.'/hien-lai', ['updated_at' => $an['updated_at']])->assertOk()->assertJsonPath('data.da_an', false)->assertJsonPath('data.co_the_ap_dung', false)->assertJsonPath('data.trang_thai', 'DA_HUY')->json('data');
        $this->postJson($url.'/hien-lai', ['updated_at' => $an['updated_at']])->assertOk()->assertJsonPath('data.updated_at', $hien['updated_at']);
        $this->getJson('/api/v1/khach-hang/ke-hoach')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/khach-hang/ke-hoach?da_an=1')->assertJsonCount(0, 'data');
        $this->assertSame($snapshot, KeHoachTap::findOrFail($k['id'])->cacBaiTap->toArray());
        $this->assertDatabaseCount('lich_tap', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_chi_an_ban_da_huy_luu_tru_va_hien_truoc_khi_ap_dung(): void
    {
        $pt = $this->gui($this->tao());
        $k = $this->tuTao();
        $url = '/api/v1/khach-hang/ke-hoach/'.$k['id'];
        foreach (['an', 'hien-lai'] as $hanhDong) {
            $this->postJson($url.'/'.$hanhDong, ['updated_at' => $k['updated_at']])->assertConflict();
            $this->postJson('/api/v1/khach-hang/ke-hoach/'.$pt['id'].'/'.$hanhDong, ['updated_at' => $pt['updated_at']])->assertNotFound();
        }
        $a = $this->postJson($url.'/ap-dung', ['updated_at' => $k['updated_at']])->assertOk()->json('data');
        $this->postJson($url.'/an', ['updated_at' => $a['updated_at']])->assertConflict();
        $luu = $this->postJson($url.'/luu-tru', ['updated_at' => $a['updated_at']])->assertOk()->json('data');
        $this->postJson($url.'/an', ['updated_at' => $a['updated_at']])->assertConflict();
        $an = $this->postJson($url.'/an', ['updated_at' => $luu['updated_at']])->assertOk()->assertJsonPath('data.co_the_ap_dung', false)->json('data');
        $this->postJson($url.'/ap-dung', ['updated_at' => $an['updated_at']])->assertConflict();
        $hien = $this->postJson($url.'/hien-lai', ['updated_at' => $an['updated_at']])->assertOk()->assertJsonPath('data.co_the_ap_dung', true)->assertJsonPath('data.trang_thai', 'LUU_TRU')->json('data');
        $this->postJson($url.'/ap-dung', ['updated_at' => $hien['updated_at']])->assertOk()->assertJsonPath('data.trang_thai', 'DANG_AP_DUNG');
    }

    public function test_an_khong_thu_hoi_pt_nhung_chan_nguoi_khac_va_gia_mao(): void
    {
        $k = $this->tuTao();
        $url = '/api/v1/khach-hang/ke-hoach/'.$k['id'];
        $k = $this->postJson($url.'/huy', ['updated_at' => $k['updated_at']])->assertOk()->json('data');
        $k = $this->postJson($url.'/an', ['updated_at' => $k['updated_at']])->assertOk()->json('data');
        $this->postJson('/api/v1/khach-hang/ke-hoach', $this->noiDung(['khach_an_luc' => now()->toISOString()]))->assertUnprocessable();
        $this->doiNguoi($this->pt);
        $this->getJson('/api/v1/pt/hoc-vien/'.$this->khId.'/ke-hoach?da_an=0')->assertJsonCount(1, 'data')->assertJsonPath('data.0.da_an', true);
        $this->getJson('/api/v1/pt/ke-hoach/'.$k['id'])->assertOk()->assertJsonPath('data.co_the_an', false)->assertJsonPath('data.co_the_hien_lai', false);
        foreach (['an', 'hien-lai'] as $hanhDong) {
            $this->postJson('/api/v1/pt/ke-hoach/'.$k['id'].'/'.$hanhDong, ['updated_at' => $k['updated_at']])->assertNotFound();
            $this->postJson($url.'/'.$hanhDong, ['updated_at' => $k['updated_at']])->assertForbidden();
        }
        $this->pc->update(['ket_thuc_luc' => now()]);
        $this->getJson('/api/v1/pt/ke-hoach/'.$k['id'])->assertNotFound();
        $this->doiNguoi($this->taiKhoan(TaiKhoan::KHACH_HANG));
        $this->getJson($url)->assertNotFound();
        $this->postJson($url.'/hien-lai', ['updated_at' => $k['updated_at']])->assertNotFound();
        $this->doiNguoi($this->admin);
        $this->postJson($url.'/an', ['updated_at' => $k['updated_at']])->assertForbidden();
    }

    public function test_loi_an_rollback_va_migration_khong_lam_mat_lua_chon(): void
    {
        $k = $this->tuTao();
        $k = $this->postJson('/api/v1/khach-hang/ke-hoach/'.$k['id'].'/huy', ['updated_at' => $k['updated_at']])->assertOk()->json('data');
        Event::listen('eloquent.updating: '.KeHoachTap::class, function () {
            throw new RuntimeException('loi-an');
        });
        try {
            app(KeHoachTapService::class)->thaoTac($this->kh, $k['id'], 'an', $k['updated_at']);
            $this->fail('Phải rollback.');
        } catch (RuntimeException $e) {
            $this->assertSame('loi-an', $e->getMessage());
        }
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $k['id'], 'khach_an_luc' => null, 'updated_at' => $k['updated_at']]);
        Event::forget('eloquent.updating: '.KeHoachTap::class);
        app(KeHoachTapService::class)->thaoTac($this->kh, $k['id'], 'an', $k['updated_at']);
        $m = require database_path('migrations/2026_10_03_000038_them_an_giao_an_tu_tao.php');
        try {
            $m->down();
            $this->fail('Không được mất lựa chọn ẩn.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('hiện lại', $e->getMessage());
        }
        $this->assertNotNull(KeHoachTap::findOrFail($k['id'])->khach_an_luc);
    }

    public function test_hai_process_an_cung_ban_khong_tao_trung(): void
    {
        $k = $this->tuTao();
        $k = $this->postJson('/api/v1/khach-hang/ke-hoach/'.$k['id'].'/huy', ['updated_at' => $k['updated_at']])->assertOk()->json('data');
        $ketQua = $this->haiWorker([$k, $k], 'an', $this->kh);
        $this->assertTrue($ketQua[0]['ok']);
        $this->assertTrue($ketQua[1]['ok']);
        $this->assertNotNull(KeHoachTap::findOrFail($k['id'])->khach_an_luc);
        $this->assertDatabaseCount('ke_hoach_tap', 1);
        $this->assertDatabaseCount('bai_tap_trong_ke_hoach', 1);
    }

    public function test_hai_process_an_va_ap_dung_khong_de_ban_dang_dung_bi_an(): void
    {
        $k = $this->tuTao();
        $a = app(KeHoachTapService::class)->thaoTac($this->kh, $k['id'], 'ap-dung', $k['updated_at']);
        $luu = app(KeHoachTapService::class)->thaoTac($this->kh, $k['id'], 'luu-tru', $a->updated_at->format('Y-m-d H:i:s.u'));
        $k['updated_at'] = $luu->updated_at->format('Y-m-d H:i:s.u');
        $ketQua = $this->haiWorker([$k, $k], ['an', 'ap-dung'], $this->kh);
        $this->assertSame(1, count(array_filter($ketQua, fn ($kq) => $kq['ok'])));
        $loi = array_values(array_filter($ketQua, fn ($kq) => ! $kq['ok']))[0];
        $this->assertSame(409, $loi['status']);
        $ban = KeHoachTap::findOrFail($k['id']);
        $this->assertSame($ban->trang_thai === 'LUU_TRU', $ban->khach_an_luc !== null);
    }

    public function test_kh_ngung_giao_an_pt_ke_ca_khi_phan_cong_ket_thuc(): void
    {
        $k = $this->gui($this->tao());
        $this->doiNguoi($this->kh);
        $url = '/api/v1/khach-hang/ke-hoach/'.$k['id'];
        $this->postJson($url.'/luu-tru', ['updated_at' => $k['updated_at']])->assertConflict();
        $k = $this->postJson($url.'/xac-nhan', ['updated_at' => $k['updated_at']])->assertOk()->assertJsonPath('data.co_the_luu_tru', true)->json('data');
        $snapshot = KeHoachTap::findOrFail($k['id'])->cacBaiTap->toArray();
        $this->postJson($url.'/luu-tru', ['updated_at' => '2000-01-01 00:00:00.000000'])->assertConflict();
        $this->doiNguoi($this->taiKhoan(TaiKhoan::KHACH_HANG));
        $this->postJson($url.'/luu-tru', ['updated_at' => $k['updated_at']])->assertNotFound();
        $this->doiNguoi($this->pt);
        $this->postJson('/api/v1/pt/ke-hoach/'.$k['id'].'/luu-tru', ['updated_at' => $k['updated_at']])->assertNotFound();
        $this->doiNguoi($this->admin);
        $this->postJson($url.'/luu-tru', ['updated_at' => $k['updated_at']])->assertForbidden();
        $this->pc->update(['ket_thuc_luc' => now()]);
        $this->doiNguoi($this->kh);
        $luu = $this->postJson($url.'/luu-tru', ['updated_at' => $k['updated_at']])->assertOk()->assertJsonPath('data.trang_thai', 'LUU_TRU')->assertJsonPath('data.co_the_luu_tru', false)->json('data');
        $this->postJson($url.'/luu-tru', ['updated_at' => $k['updated_at']])->assertOk()->assertJsonPath('data.updated_at', $luu['updated_at']);
        $this->assertSame(0, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->count());
        $this->assertSame($snapshot, KeHoachTap::findOrFail($k['id'])->cacBaiTap->toArray());
        $this->assertDatabaseCount('lich_tap', 0);
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_xac_nhan_pt_luu_tru_ban_tu_tao_va_de_xuat_cu_khong_ghi_de(): void
    {
        $a = $this->tuTao();
        app(KeHoachTapService::class)->thaoTac($this->kh, $a['id'], 'ap-dung', $a['updated_at']);
        $this->doiNguoi($this->pt);
        $k = $this->gui($this->tao());
        $this->doiNguoi($this->kh);
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$k['id'].'/xac-nhan', ['updated_at' => $k['updated_at']])->assertOk();
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $a['id'], 'trang_thai' => 'LUU_TRU']);
        $this->assertSame(1, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->count());
        $this->doiNguoi($this->pt);
        $cu = $this->gui($this->tao());
        $b = $this->tuTao();
        app(KeHoachTapService::class)->thaoTac($this->kh, $b['id'], 'ap-dung', $b['updated_at']);
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$cu['id'].'/xac-nhan', ['updated_at' => $cu['updated_at']])->assertConflict();
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $b['id'], 'trang_thai' => 'DANG_AP_DUNG']);
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $k['id'], 'trang_thai' => 'LUU_TRU']);
    }

    public function test_hai_process_ap_dung_kh_va_xac_nhan_pt_chi_mot_ban_dang_dung(): void
    {
        $pt = $this->gui($this->tao());
        $kh = $this->tuTao();
        $ketQua = $this->haiWorker([$kh, $pt], ['ap-dung', 'xac-nhan'], $this->kh);
        $this->assertTrue($ketQua[0]['ok']);
        if (! $ketQua[1]['ok']) {
            $this->assertSame(409, $ketQua[1]['status']);
        }
        $this->assertSame(1, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->count());
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $kh['id'], 'trang_thai' => 'DANG_AP_DUNG']);
        $this->assertDatabaseCount('bai_tap_trong_ke_hoach', 2);
    }

    public function test_loi_ngung_pt_rollback_trang_thai(): void
    {
        $k = $this->gui($this->tao());
        $a = app(KeHoachTapService::class)->thaoTac($this->kh, $k['id'], 'xac-nhan', $k['updated_at']);
        Event::listen('eloquent.updating: '.KeHoachTap::class, function () {
            throw new RuntimeException('loi-ngung-pt');
        });
        try {
            app(KeHoachTapService::class)->thaoTac($this->kh, $k['id'], 'luu-tru', $a->updated_at->format('Y-m-d H:i:s.u'));
            $this->fail('Phải rollback.');
        } catch (RuntimeException $e) {
            $this->assertSame('loi-ngung-pt', $e->getMessage());
        }
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $k['id'], 'trang_thai' => 'DANG_AP_DUNG', 'updated_at' => $a->updated_at->format('Y-m-d H:i:s.u')]);
    }

    public function test_migration_gop_hai_ban_giu_ban_moi_nhat_va_unique_chung(): void
    {
        $pt = $this->tao();
        $kh = $this->tuTao();
        $snapshot = BaiTapTrongKeHoach::orderBy('id')->get()->toArray();
        DB::commit();
        $this->daCommit = true;
        $m = require database_path('migrations/2026_10_03_000039_mot_giao_an_dang_ap_dung_moi_khach.php');
        $m->down();
        DB::table('ke_hoach_tap')->where('id', $pt['id'])->update(['trang_thai' => 'DANG_AP_DUNG', 'updated_at' => '2026-10-01 00:00:00.000000']);
        DB::table('ke_hoach_tap')->where('id', $kh['id'])->update(['trang_thai' => 'DANG_AP_DUNG', 'updated_at' => '2026-10-02 00:00:00.000000']);
        $m->up();
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $pt['id'], 'trang_thai' => 'LUU_TRU']);
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $kh['id'], 'trang_thai' => 'DANG_AP_DUNG']);
        $this->assertSame($snapshot, BaiTapTrongKeHoach::orderBy('id')->get()->toArray());
        $this->assertDatabaseCount('ke_hoach_tap', 2);
        $m->down();
        $this->assertSame(1, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->count());
        DB::table('ke_hoach_tap')->whereIn('id', [$kh['id'], $pt['id']])->update(['trang_thai' => 'DANG_AP_DUNG', 'updated_at' => '2026-10-02 00:00:00.000000']);
        $m->up();
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $kh['id'], 'trang_thai' => 'DANG_AP_DUNG']);
        try {
            DB::table('ke_hoach_tap')->where('id', $pt['id'])->update(['trang_thai' => 'DANG_AP_DUNG']);
            $this->fail('Unique chung phải chặn bản thứ hai.');
        } catch (UniqueConstraintViolationException $e) {
            $this->assertStringContainsString('uq_t14_01', $e->getMessage());
        }
        $this->assertSame(1, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->count());
    }

    public function test_kh_chon_lai_pt_sau_khi_ngung_giu_snapshot_va_quyen_lich_su(): void
    {
        $pt = $this->gui($this->tao());
        $this->doiNguoi($this->kh);
        $url = '/api/v1/khach-hang/ke-hoach/'.$pt['id'];
        $pt = $this->postJson($url.'/xac-nhan', ['updated_at' => $pt['updated_at']])->assertOk()->json('data');
        $snapshot = KeHoachTap::findOrFail($pt['id'])->cacBaiTap->toArray();
        $pt = $this->postJson($url.'/luu-tru', ['updated_at' => $pt['updated_at']])->assertOk()->assertJsonPath('data.co_the_ap_dung', true)->json('data');
        $kh = $this->tuTao();
        $khUrl = '/api/v1/khach-hang/ke-hoach/'.$kh['id'];
        $kh = $this->postJson($khUrl.'/ap-dung', ['updated_at' => $kh['updated_at']])->assertOk()->json('data');
        $this->postJson($khUrl.'/luu-tru', ['updated_at' => $kh['updated_at']])->assertOk();
        $this->doiNguoi($this->pt);
        $this->getJson('/api/v1/pt/ke-hoach/'.$pt['id'])->assertOk()->assertJsonPath('data.co_the_ap_dung', false);
        $this->pc->update(['ket_thuc_luc' => now()]);
        $this->pt->update(['trang_thai' => TaiKhoan::BI_KHOA]);
        BaiTap::findOrFail($this->baiId)->update(['trang_thai' => 'NGUNG_HOAT_DONG']);
        $this->getJson('/api/v1/pt/ke-hoach/'.$pt['id'])->assertNotFound();
        $this->doiNguoi($this->kh);
        $this->getJson($url)->assertOk()->assertJsonPath('data.co_the_ap_dung', true);
        $a = $this->postJson($url.'/ap-dung', ['updated_at' => $pt['updated_at']])->assertOk()
            ->assertJsonPath('data.trang_thai', 'DANG_AP_DUNG')->assertJsonPath('data.co_the_ap_dung', false)
            ->assertJsonPath('data.duyet_luc', $pt['duyet_luc'])->assertJsonPath('data.gui_luc', $pt['gui_luc'])
            ->assertJsonPath('data.han_duyet', $pt['han_duyet'])->json('data');
        $this->postJson($url.'/ap-dung', ['updated_at' => $pt['updated_at']])->assertOk()->assertJsonPath('data.updated_at', $a['updated_at']);
        $this->assertSame($snapshot, KeHoachTap::findOrFail($pt['id'])->cacBaiTap->toArray());
        $this->assertSame(1, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->count());
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseCount('lich_tap', 0);
    }

    public function test_ap_dung_lai_pt_thay_tu_tao_va_chan_version_cu(): void
    {
        $pt = $this->gui($this->tao());
        $this->doiNguoi($this->kh);
        $url = '/api/v1/khach-hang/ke-hoach/'.$pt['id'];
        $pt = $this->postJson($url.'/xac-nhan', ['updated_at' => $pt['updated_at']])->assertOk()->json('data');
        $kh = $this->tuTao();
        $kh = $this->postJson('/api/v1/khach-hang/ke-hoach/'.$kh['id'].'/ap-dung', ['updated_at' => $kh['updated_at']])->assertOk()->json('data');
        $this->postJson($url.'/ap-dung', ['updated_at' => $pt['updated_at']])->assertConflict();
        $pt = $this->getJson($url)->assertOk()->assertJsonPath('data.co_the_ap_dung', true)->json('data');
        $this->postJson($url.'/ap-dung', ['updated_at' => $pt['updated_at']])->assertOk();
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $kh['id'], 'trang_thai' => 'LUU_TRU']);
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $pt['id'], 'trang_thai' => 'DANG_AP_DUNG']);
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$kh['id'].'/ap-dung', ['updated_at' => $kh['updated_at']])->assertConflict();
        $this->assertSame(1, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->count());
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_khong_bo_qua_duyet_pt_va_khong_ap_dung_thay_kh(): void
    {
        $nhap = $this->tao();
        $pt = $this->gui($this->tao());
        $url = '/api/v1/khach-hang/ke-hoach/'.$pt['id'];
        $this->doiNguoi($this->kh);
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$nhap['id'].'/ap-dung', ['updated_at' => $nhap['updated_at']])->assertNotFound();
        $this->getJson($url)->assertOk()->assertJsonPath('data.co_the_ap_dung', false);
        $this->postJson($url.'/ap-dung', ['updated_at' => $pt['updated_at']])->assertConflict();
        KeHoachTap::findOrFail($pt['id'])->update(['han_duyet' => now()->subSecond()]);
        $this->postJson($url.'/ap-dung', ['updated_at' => $pt['updated_at']])->assertConflict();
        foreach (['DA_HUY', 'LUU_TRU'] as $trangThai) {
            KeHoachTap::findOrFail($pt['id'])->update(['trang_thai' => $trangThai]);
            $pt = $this->getJson($url)->assertOk()->assertJsonPath('data.co_the_ap_dung', false)->json('data');
            $this->postJson($url.'/ap-dung', ['updated_at' => $pt['updated_at']])->assertConflict();
        }
        $this->doiNguoi($this->taiKhoan(TaiKhoan::KHACH_HANG));
        $this->postJson($url.'/ap-dung', ['updated_at' => $pt['updated_at']])->assertNotFound();
        $this->doiNguoi($this->pt);
        $this->postJson($url.'/ap-dung', ['updated_at' => $pt['updated_at']])->assertForbidden();
        $this->postJson('/api/v1/pt/ke-hoach/'.$pt['id'].'/ap-dung', ['updated_at' => $pt['updated_at']])->assertNotFound();
        $this->doiNguoi($this->admin);
        $this->postJson($url.'/ap-dung', ['updated_at' => $pt['updated_at']])->assertForbidden();
        $this->assertSame(0, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->count());
    }

    public function test_loi_ap_dung_lai_pt_rollback_ca_hai_ban(): void
    {
        $pt = $this->gui($this->tao());
        app(KeHoachTapService::class)->thaoTac($this->kh, $pt['id'], 'xac-nhan', $pt['updated_at']);
        $kh = $this->tuTao();
        $kh = app(KeHoachTapService::class)->thaoTac($this->kh, $kh['id'], 'ap-dung', $kh['updated_at']);
        $pt = KeHoachTap::findOrFail($pt['id']);
        $snapshot = BaiTapTrongKeHoach::orderBy('id')->get()->toArray();
        Event::listen('eloquent.updating: '.KeHoachTap::class, function ($k) use ($pt) {
            if ($k->id === $pt->id && $k->trang_thai === 'DANG_AP_DUNG') {
                throw new RuntimeException('loi-ap-dung-lai-pt');
            }
        });
        try {
            app(KeHoachTapService::class)->thaoTac($this->kh, $pt->id, 'ap-dung', $pt->updated_at->format('Y-m-d H:i:s.u'));
            $this->fail('Phải rollback cả bản cũ và bản chọn.');
        } catch (RuntimeException $e) {
            $this->assertSame('loi-ap-dung-lai-pt', $e->getMessage());
        }
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $pt->id, 'trang_thai' => 'LUU_TRU', 'updated_at' => $pt->updated_at->format('Y-m-d H:i:s.u')]);
        $this->assertDatabaseHas('ke_hoach_tap', ['id' => $kh->id, 'trang_thai' => 'DANG_AP_DUNG', 'updated_at' => $kh->updated_at->format('Y-m-d H:i:s.u')]);
        $this->assertSame($snapshot, BaiTapTrongKeHoach::orderBy('id')->get()->toArray());
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_hai_process_chon_lai_pt_va_tu_tao_chi_mot_ban_dang_dung(): void
    {
        $pt = $this->gui($this->tao());
        $a = app(KeHoachTapService::class)->thaoTac($this->kh, $pt['id'], 'xac-nhan', $pt['updated_at']);
        $luu = app(KeHoachTapService::class)->thaoTac($this->kh, $pt['id'], 'luu-tru', $a->updated_at->format('Y-m-d H:i:s.u'));
        $pt['updated_at'] = $luu->updated_at->format('Y-m-d H:i:s.u');
        $kh = $this->tuTao();
        $a = app(KeHoachTapService::class)->thaoTac($this->kh, $kh['id'], 'ap-dung', $kh['updated_at']);
        $luu = app(KeHoachTapService::class)->thaoTac($this->kh, $kh['id'], 'luu-tru', $a->updated_at->format('Y-m-d H:i:s.u'));
        $kh['updated_at'] = $luu->updated_at->format('Y-m-d H:i:s.u');
        $snapshot = BaiTapTrongKeHoach::orderBy('id')->get()->toArray();
        $ketQua = $this->haiWorker([$pt, $kh], 'ap-dung', $this->kh);
        $this->assertTrue($ketQua[0]['ok']);
        $this->assertTrue($ketQua[1]['ok']);
        $this->assertSame(1, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->count());
        $this->assertSame($snapshot, BaiTapTrongKeHoach::orderBy('id')->get()->toArray());
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseCount('lich_tap', 0);
    }

    private function haiWorker(array $cacBan, string|array $hanhDong, TaiKhoan $nguoi): array
    {
        DB::commit();
        $this->daCommit = true;
        $workers = [];
        DB::beginTransaction();
        DB::table('ho_so_khach_hang')->where('id', $this->khId)->lockForUpdate()->first();
        try {
            foreach ($cacBan as $viTri => $k) {
                $ready = tempnam(sys_get_temp_dir(), 'ke-hoach-ready-');
                unlink($ready);
                $out = tempnam(sys_get_temp_dir(), 'ke-hoach-out-');
                $err = tempnam(sys_get_temp_dir(), 'ke-hoach-err-');
                $proc = proc_open([PHP_BINARY, base_path('tests/Support/ke-hoach-worker.php'), self::$db, (string) $nguoi->id, (string) $k['id'], is_array($hanhDong) ? $hanhDong[$viTri] : $hanhDong, $k['updated_at'], $ready], [0 => ['pipe', 'r'], 1 => ['file', $out, 'w'], 2 => ['file', $err, 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
                $this->assertIsResource($proc);
                fclose($pipes[0]);
                $workers[] = compact('proc', 'ready', 'out', 'err');
            }
            $han = microtime(true) + 10;
            while ((! file_exists($workers[0]['ready']) || ! file_exists($workers[1]['ready'])) && microtime(true) < $han) {
                usleep(20000);
            }
            foreach ($workers as $w) {
                $this->assertFileExists($w['ready']);
            }
            DB::commit();
            $ketQua = [];
            foreach ($workers as &$w) {
                $this->assertSame(0, proc_close($w['proc']), file_get_contents($w['out']).file_get_contents($w['err']));
                $w['proc'] = null;
                $ketQua[] = json_decode(file_get_contents($w['out']), true, flags: JSON_THROW_ON_ERROR);
            }
            unset($w);

            return $ketQua;
        } finally {
            if (DB::transactionLevel()) {
                DB::rollBack(0);
            }
            foreach ($workers as $w) {
                if (is_resource($w['proc'])) {
                    proc_terminate($w['proc']);
                    proc_close($w['proc']);
                }
                foreach (['ready', 'out', 'err'] as $tep) {
                    if (file_exists($w[$tep])) {
                        unlink($w[$tep]);
                    }
                }
            }
        }
    }
}
