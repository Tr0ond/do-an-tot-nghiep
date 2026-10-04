<?php

namespace Tests\Feature;

use App\Models\ChiSoCoThe;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use App\Services\ChatbotService;
use App\Services\ChiSoCoTheService;
use App\Services\TaiKhoanService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Tests\TestCase;

class ChiSoCoTheTest extends TestCase
{
    private static ?string $db = null;

    private static ?PDO $pdo = null;

    private TaiKhoan $kh;

    private TaiKhoan $pt;

    private TaiKhoan $admin;

    private PhanCongHuanLuyenVien $pc;

    private bool $daCommit = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (self::$db === null) {
            $cfg = config('database.connections.mysql');
            $cfg['database'] = null;
            $cfg['url'] = null;
            config(['database.connections.may_chu_chi_so' => $cfg]);
            self::$pdo = DB::connection('may_chu_chi_so')->getPdo();
            self::$db = 'kiem_tra_chi_so_'.bin2hex(random_bytes(8));
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
        $this->pc = PhanCongHuanLuyenVien::create(['khach_hang_id' => $this->kh->hoSoKhachHang->id,
            'huan_luyen_vien_id' => $this->pt->hoSoHuanLuyenVien->id, 'nguoi_phan_cong_id' => $this->admin->id, 'bat_dau_luc' => now()]);
        $this->doiNguoi($this->kh);
    }

    protected function tearDown(): void
    {
        Event::forget('eloquent.updating: '.ChiSoCoThe::class);
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
        if (self::$db !== null && preg_match('/^kiem_tra_chi_so_[a-f0-9]{16}$/D', self::$db)) {
            self::$pdo->exec('DROP DATABASE `'.self::$db.'`');
        }
        self::$db = null;
        self::$pdo = null;
        parent::tearDownAfterClass();
    }

    private function taiKhoan(string $vaiTro): TaiKhoan
    {
        return app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Kiểm thử chỉ số', 'email' => Str::uuid().'@example.test', 'password' => 'Demo123456!'], $vaiTro);
    }

    private function doiNguoi(TaiKhoan $nguoi): void
    {
        Auth::forgetGuards();
        $this->actingAs($nguoi, 'web');
    }

    private function body(array $them = []): array
    {
        return ['ngay_ghi' => now('Asia/Ho_Chi_Minh')->toDateString(), 'can_nang_kg' => '70.00', 'chieu_cao_cm' => '175.00', 'ghi_chu' => null, ...$them];
    }

    private function tao(array $them = []): array
    {
        return $this->postJson('/api/v1/khach-hang/chi-so-co-the', $this->body($them))->assertCreated()->json('data');
    }

    public function test_kh_khong_goi_ghi_bmi_va_retry_khong_them_ban(): void
    {
        $ban = $this->tao();
        $this->assertEquals(22.86, $ban['bmi']);
        $this->postJson('/api/v1/khach-hang/chi-so-co-the', $this->body(['can_nang_kg' => 70]))->assertOk()->assertJsonPath('data.id', $ban['id']);
        $this->postJson('/api/v1/khach-hang/chi-so-co-the', $this->body(['can_nang_kg' => 71]))->assertConflict();
        $this->assertDatabaseCount('chi_so_co_the', 1);
        $this->assertDatabaseCount('dang_ky_goi_tap', 0);
        $this->getJson('/api/v1/khach-hang/chi-so-co-the')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.moi_nhat.id', $ban['id']);
    }

    public function test_validation_bac_bo_vong_eo_bmi_id_va_ngay_tuong_lai(): void
    {
        foreach ([['can_nang_kg' => 0], ['chieu_cao_cm' => 0], ['can_nang_kg' => 501], ['chieu_cao_cm' => 251],
            ['can_nang_kg' => '70.123'], ['ghi_chu' => str_repeat('a', 1001)], ['ngay_ghi' => '2026-02-30'],
            ['ngay_ghi' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString()], ['bmi' => 22], ['khach_hang_id' => 5], ['vong_eo_cm' => 80]] as $them) {
            $this->postJson('/api/v1/khach-hang/chi-so-co-the', $this->body($them))->assertUnprocessable()->assertJsonValidationErrors(array_keys($them));
        }
        $this->getJson('/api/v1/khach-hang/chi-so-co-the?so_ngay=365')->assertUnprocessable();
        $this->assertDatabaseCount('chi_so_co_the', 0);
    }

    public function test_sua_co_version_giu_chieu_cao_cu_va_khong_trung_ngay(): void
    {
        $this->travelTo(now()->startOfSecond());
        $cu = $this->tao(['ngay_ghi' => now('Asia/Ho_Chi_Minh')->subDay()->toDateString()]);
        $ban = $this->tao();
        $body = $this->body(['chieu_cao_cm' => 180, 'updated_at' => $ban['updated_at']]);
        $moi = $this->putJson('/api/v1/khach-hang/chi-so-co-the/'.$ban['id'], $body)->assertOk()->json('data');
        $this->assertNotSame($ban['updated_at'], $moi['updated_at']);
        $this->assertEquals(21.6, $moi['bmi']);
        $this->assertEquals(22.86, ChiSoCoThe::find($cu['id'])->bmi());
        $this->putJson('/api/v1/khach-hang/chi-so-co-the/'.$ban['id'], $body)->assertOk();
        $this->putJson('/api/v1/khach-hang/chi-so-co-the/'.$ban['id'], [...$body, 'can_nang_kg' => 80])->assertConflict();
        $this->putJson('/api/v1/khach-hang/chi-so-co-the/'.$ban['id'], [...$body, 'updated_at' => $moi['updated_at'], 'ngay_ghi' => $cu['ngay_ghi']])->assertConflict();
        $this->assertDatabaseCount('chi_so_co_the', 2);
    }

    public function test_kh_khac_admin_pt_khong_ghi_thay_va_pt_bi_thu_hoi(): void
    {
        $ban = $this->tao();
        $hocVien = '/api/v1/pt/hoc-vien/'.$this->kh->hoSoKhachHang->id.'/chi-so-co-the';
        $this->doiNguoi($this->taiKhoan(TaiKhoan::KHACH_HANG));
        $this->putJson('/api/v1/khach-hang/chi-so-co-the/'.$ban['id'], $this->body(['updated_at' => $ban['updated_at']]))->assertNotFound();
        $this->getJson($hocVien)->assertForbidden();
        $this->doiNguoi($this->admin);
        $this->getJson('/api/v1/khach-hang/chi-so-co-the')->assertForbidden();
        $this->getJson($hocVien)->assertForbidden();
        $this->doiNguoi($this->pt);
        $this->getJson($hocVien)->assertOk()->assertJsonPath('data.moi_nhat.id', $ban['id']);
        $this->postJson('/api/v1/khach-hang/chi-so-co-the', $this->body())->assertForbidden();
        $this->getJson('/api/v1/pt/hoc-vien/'.$this->taiKhoan(TaiKhoan::KHACH_HANG)->hoSoKhachHang->id.'/chi-so-co-the')->assertNotFound();
        $this->pc->update(['ket_thuc_luc' => now()]);
        $this->getJson($hocVien)->assertNotFound();
        $this->pc->update(['ket_thuc_luc' => null, 'bat_dau_luc' => now()->addHour()]);
        $this->getJson($hocVien)->assertNotFound();
    }

    public function test_khoang_ngay_viet_nam_phan_trang_va_ban_cu_thieu_so_do(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-03T18:00:00Z'));
        for ($i = 0; $i < 31; $i++) {
            app(ChiSoCoTheService::class)->luu($this->kh, $this->body(['ngay_ghi' => now('Asia/Ho_Chi_Minh')->subDays($i)->toDateString(), 'can_nang_kg' => 70 + $i / 10]));
        }
        $k = $this->getJson('/api/v1/khach-hang/chi-so-co-the?so_ngay=30')->assertOk()->assertJsonPath('data.den_ngay', '2026-10-04')->assertJsonPath('data.tu_ngay', '2026-09-05')->assertJsonPath('meta.total', 30)->json('data');
        $this->assertCount(30, $k['cac_moc']);
        $this->assertCount(20, $k['lich_su']);
        $this->assertEquals(-2.9, $k['thay_doi_can_nang_kg']);
        $this->getJson('/api/v1/khach-hang/chi-so-co-the?so_ngay=30&page=2')->assertOk()->assertJsonCount(10, 'data.lich_su');
        DB::table('chi_so_co_the')->where('khach_hang_id', $this->kh->hoSoKhachHang->id)->update(['chieu_cao_cm' => null, 'vong_eo_cm' => 80]);
        $k = $this->getJson('/api/v1/khach-hang/chi-so-co-the?so_ngay=7')->assertOk()->json('data');
        $this->assertNull($k['moi_nhat']['bmi']);
        $this->assertNull($k['thay_doi_bmi']);
        $this->assertArrayNotHasKey('vong_eo_cm', $k['moi_nhat']);
    }

    public function test_khong_so_do_la_null_khong_phai_0_va_moi_nhat_ngoai_khoang(): void
    {
        $this->getJson('/api/v1/khach-hang/chi-so-co-the')->assertOk()->assertJsonPath('data.moi_nhat', null)->assertJsonPath('data.thay_doi_can_nang_kg', null);
        $ban = $this->tao(['ngay_ghi' => now('Asia/Ho_Chi_Minh')->subDays(40)->toDateString()]);
        $this->getJson('/api/v1/khach-hang/chi-so-co-the')->assertOk()->assertJsonPath('data.moi_nhat.id', $ban['id'])->assertJsonCount(0, 'data.cac_moc')->assertJsonPath('data.thay_doi_can_nang_kg', null);
        $this->getJson('/api/v1/khach-hang/chi-so-co-the?den_ngay='.$ban['ngay_ghi'])->assertOk()->assertJsonCount(1, 'data.lich_su');
        $this->getJson('/api/v1/khach-hang/chi-so-co-the?den_ngay='.now('Asia/Ho_Chi_Minh')->addDay()->toDateString())->assertUnprocessable();
    }

    public function test_ai_chi_lay_du_lieu_khi_bat_ca_nhan_khong_gui_note_vong_eo_nguoi_khac(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->tao(['ngay_ghi' => now('Asia/Ho_Chi_Minh')->subDays($i)->toDateString(), 'ghi_chu' => 'ghi-chu-rieng-tu']);
        }
        $khac = $this->taiKhoan(TaiKhoan::KHACH_HANG);
        ChiSoCoThe::create(['khach_hang_id' => $khac->hoSoKhachHang->id, ...$this->body(['can_nang_kg' => 99])]);
        $s = app(ChatbotService::class);
        $khId = $this->kh->hoSoKhachHang->id;
        $tat = $s->nguCanh($khId, 'Tạo giáo án', false);
        $this->assertArrayNotHasKey('ca_nhan', $tat);
        $bat = $s->nguCanh($khId, 'Tạo giáo án', true)['ca_nhan']['chi_so_co_the'];
        $this->assertCount(10, $bat['cac_moc_gan_day']);
        $this->assertEquals(70, $bat['moi_nhat']['can_nang_kg']);
        $this->assertArrayNotHasKey('ghi_chu', $bat['moi_nhat']);
        $this->assertArrayNotHasKey('vong_eo_cm', $bat['moi_nhat']);
        $this->assertStringNotContainsString('ghi-chu-rieng-tu', json_encode($bat));
    }

    public function test_sua_that_bai_rollback(): void
    {
        $ban = $this->tao();
        Event::listen('eloquent.updating: '.ChiSoCoThe::class, fn () => throw new RuntimeException('loi-ghi'));
        try {
            app(ChiSoCoTheService::class)->luu($this->kh, $this->body(['can_nang_kg' => 80, 'updated_at' => $ban['updated_at']]), $ban['id']);
            $this->fail();
        } catch (RuntimeException $e) {
            $this->assertSame('loi-ghi', $e->getMessage());
        }
        $this->assertDatabaseHas('chi_so_co_the', ['id' => $ban['id'], 'can_nang_kg' => 70]);
    }

    public function test_hai_process_cung_ngay_chi_tao_mot_ban(): void
    {
        DB::commit();
        $this->daCommit = true;
        DB::beginTransaction();
        DB::table('ho_so_khach_hang')->where('id', $this->kh->hoSoKhachHang->id)->lockForUpdate()->first();
        $workers = [];
        try {
            for ($i = 0; $i < 2; $i++) {
                $input = tempnam(sys_get_temp_dir(), 'chi-so-input-');
                file_put_contents($input, json_encode(['nguoi_id' => $this->kh->id, 'body' => $this->body()], JSON_THROW_ON_ERROR));
                $ready = tempnam(sys_get_temp_dir(), 'chi-so-ready-');
                unlink($ready);
                $out = tempnam(sys_get_temp_dir(), 'chi-so-out-');
                $err = tempnam(sys_get_temp_dir(), 'chi-so-err-');
                $proc = proc_open([PHP_BINARY, base_path('tests/Support/chi-so-worker.php'), self::$db, $input, $ready], [0 => ['pipe', 'r'], 1 => ['file', $out, 'w'], 2 => ['file', $err, 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
                $this->assertIsResource($proc);
                fclose($pipes[0]);
                $workers[] = compact('proc', 'input', 'ready', 'out', 'err');
            }
            $han = microtime(true) + 10;
            while ((! file_exists($workers[0]['ready']) || ! file_exists($workers[1]['ready'])) && microtime(true) < $han) {
                usleep(20000);
            }
            foreach ($workers as $w) {
                $this->assertFileExists($w['ready']);
            }
            DB::commit();
            $r = [];
            foreach ($workers as &$w) {
                $this->assertSame(0, proc_close($w['proc']), file_get_contents($w['err']));
                $w['proc'] = null;
                $r[] = json_decode(file_get_contents($w['out']), true, flags: JSON_THROW_ON_ERROR);
            }
            unset($w);
            $this->assertSame($r[0]['id'], $r[1]['id']);
            $this->assertDatabaseCount('chi_so_co_the', 1);
        } finally {
            if (DB::transactionLevel()) {
                DB::rollBack(0);
            }
            foreach ($workers as $w) {
                if (is_resource($w['proc'])) {
                    proc_terminate($w['proc']);
                    proc_close($w['proc']);
                }
                foreach (['ready', 'input', 'out', 'err'] as $tep) {
                    if (file_exists($w[$tep])) {
                        unlink($w[$tep]);
                    }
                }
            }
        }
    }
}
