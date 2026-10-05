<?php

namespace Tests\Feature;

use App\Models\TaiKhoan;
use App\Notifications\KhoiPhucMatKhau;
use App\Services\HoSoTaiKhoanService;
use App\Services\KhoiPhucMatKhauService;
use App\Services\PhienMobileService;
use App\Services\TaiKhoanService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;
use PDO;
use RuntimeException;
use Tests\TestCase;

class PhienMobileTest extends TestCase
{
    private static ?string $tenDatabase = null;

    private static ?PDO $pdoMayChu = null;

    protected function setUp(): void
    {
        parent::setUp();
        $cauHinh = config('database.connections.mysql');
        if (self::$tenDatabase === null) {
            $cauHinh['database'] = null;
            $cauHinh['url'] = null;
            config(['database.connections.may_chu_mobile' => $cauHinh]);
            self::$pdoMayChu = DB::connection('may_chu_mobile')->getPdo();
            self::$tenDatabase = 'kiem_tra_mobile_'.bin2hex(random_bytes(8));
            self::$pdoMayChu->exec('CREATE DATABASE `'.self::$tenDatabase.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
        config(['database.connections.mysql.database' => self::$tenDatabase, 'database.connections.mysql.url' => null]);
        DB::purge('mysql');
        DB::setDefaultConnection('mysql');
        $this->assertSame(self::$tenDatabase, DB::selectOne('SELECT DATABASE() AS ten')->ten);
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        Event::forget(PasswordReset::class);
        if (DB::transactionLevel() > 0) {
            DB::rollBack(0);
        }
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$tenDatabase !== null) {
            self::$pdoMayChu->exec('DROP DATABASE `'.self::$tenDatabase.'`');
        }
        self::$tenDatabase = null;
        self::$pdoMayChu = null;
        parent::tearDownAfterClass();
    }

    private function taoTaiKhoan(string $vaiTro = TaiKhoan::KHACH_HANG): TaiKhoan
    {
        return app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Mobile QA', 'email' => bin2hex(random_bytes(6)).'@example.test', 'password' => 'Demo123456!'], $vaiTro);
    }

    private function dangNhap(TaiKhoan $taiKhoan): string
    {
        Auth::forgetGuards();
        $this->flushHeaders();

        return $this->postJson('/api/v1/mobile/dang-nhap', ['email' => strtoupper($taiKhoan->email), 'password' => 'Demo123456!', 'ten_thiet_bi' => 'Android QA'])
            ->assertOk()->assertJsonPath('data.tai_khoan.vai_tro', $taiKhoan->vai_tro)->assertJsonMissingPath('data.tai_khoan.password')
            ->assertHeader('Cache-Control', 'no-store, private')->json('data.access_token');
    }

    private function goi(string $method, string $url, string $token, array $data = [])
    {
        Auth::forgetGuards();
        $this->flushHeaders();

        return $this->withHeader('Authorization', 'Bearer '.$token)->{$method.'Json'}($url, $data);
    }

    public function test_native_dang_ky_chi_kh_khong_cap_phien_thua_truong_va_chong_trung(): void
    {
        $d = ['ho_ten' => 'Học viên MB5', 'email' => '  MB5@example.test ', 'password' => 'Demo123456!', 'password_confirmation' => 'Demo123456!'];
        foreach (['vai_tro' => 'ADMIN', 'trang_thai' => 'HOAT_DONG', 'so_dien_thoai' => '123'] as $k => $v) {
            $this->postJson('/api/v1/mobile/dang-ky', [...$d, $k => $v])->assertUnprocessable();
        }
        $this->postJson('/api/v1/mobile/dang-ky', $d)->assertCreated()->assertJsonPath('data', null)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertDatabaseHas('tai_khoan', ['email' => 'mb5@example.test', 'vai_tro' => 'KHACH_HANG', 'trang_thai' => 'HOAT_DONG']);
        $this->assertDatabaseCount('ho_so_khach_hang', 1);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->postJson('/api/v1/mobile/dang-ky', $d)->assertUnprocessable();
        $this->assertDatabaseCount('tai_khoan', 1);
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->dangNhap(TaiKhoan::where('email', 'mb5@example.test')->firstOrFail());
    }

    public function test_native_reset_thu_hoi_nhieu_thiet_bi_token_mot_lan_va_khong_do_email(): void
    {
        Notification::fake();
        $u = $this->taoTaiKhoan();
        $a = $this->dangNhap($u);
        $b = $this->dangNhap($u);
        $this->flushHeaders();
        Auth::forgetGuards();
        $r = $this->postJson('/api/v1/mobile/quen-mat-khau', ['email' => strtoupper($u->email)])->assertOk()->assertJsonPath('data', null);
        $this->postJson('/api/v1/mobile/quen-mat-khau', ['email' => 'khongco@example.test'])->assertOk()->assertExactJson($r->json());
        $token = Notification::sent($u, KhoiPhucMatKhau::class)->first()->token;
        $this->assertStringNotContainsString($token, $r->getContent());
        $d = ['email' => $u->email, 'token' => $token, 'password' => 'Moi123456!', 'password_confirmation' => 'Moi123456!'];
        $this->postJson('/api/v1/mobile/dat-lai-mat-khau', [...$d, 'password_confirmation' => 'sai'])->assertUnprocessable();
        $this->postJson('/api/v1/mobile/dat-lai-mat-khau', [...$d, 'vai_tro' => 'ADMIN'])->assertUnprocessable();
        $this->postJson('/api/v1/mobile/dat-lai-mat-khau', $d)->assertOk()->assertJsonPath('data', null);
        $this->postJson('/api/v1/mobile/dat-lai-mat-khau', $d)->assertUnprocessable();
        $this->goi('get', '/api/v1/me', $a)->assertUnauthorized();
        $this->goi('get', '/api/v1/me', $b)->assertUnauthorized();
        $this->assertSame(0, $u->tokens()->count());
    }

    public function test_kh_pt_token_hash_30_ngay_va_dang_xuat_chi_thiet_bi_hien_tai(): void
    {
        $this->freezeSecond();
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN] as $vaiTro) {
            $nguoi = $this->taoTaiKhoan($vaiTro);
            $a = $this->dangNhap($nguoi);
            $b = $this->dangNhap($nguoi);
            $record = PersonalAccessToken::findToken($a);
            $this->assertSame(hash('sha256', explode('|', $a, 2)[1]), $record->token);
            $this->assertTrue($record->expires_at->equalTo(now()->addDays(30)));
            $this->goi('get', '/api/v1/me', $a)->assertOk()->assertJsonPath('data.id', $nguoi->id);
            $dau = $nguoi->dauPhienDangNhap();
            $this->goi('post', '/api/v1/mobile/dang-xuat', $a)->assertOk();
            $this->goi('get', '/api/v1/me', $a)->assertUnauthorized();
            $this->goi('get', '/api/v1/me', $b)->assertOk();
            $this->assertSame($dau, $nguoi->fresh()->dauPhienDangNhap());
            $this->assertSame(1, $nguoi->tokens()->count());
        }
    }

    public function test_sai_mat_khau_khoa_admin_khong_cap_token_va_rate_limit(): void
    {
        foreach ([TaiKhoan::ADMIN, TaiKhoan::KHACH_HANG] as $vaiTro) {
            $nguoi = $this->taoTaiKhoan($vaiTro);
            $nguoi->trang_thai = TaiKhoan::BI_KHOA;
            $nguoi->save();
            $this->postJson('/api/v1/mobile/dang-nhap', ['email' => $nguoi->email, 'password' => 'Demo123456!', 'ten_thiet_bi' => 'QA'])->assertUnprocessable();
        }
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $this->postJson('/api/v1/mobile/dang-nhap', ['email' => $admin->email, 'password' => 'Demo123456!', 'ten_thiet_bi' => 'QA'])->assertUnprocessable();
        $this->postJson('/api/v1/mobile/dang-nhap', ['email' => 'khongco@example.test', 'password' => 'sai', 'ten_thiet_bi' => 'QA'])->assertUnprocessable();
        for ($i = 0; $i < 6; $i++) {
            $cuoi = $this->postJson('/api/v1/mobile/dang-nhap', ['email' => 'rate@example.test', 'password' => 'sai', 'ten_thiet_bi' => 'QA']);
        }
        $cuoi->assertTooManyRequests();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_het_han_dung_moc_va_token_khong_dung_dau_abilities_bi_chan(): void
    {
        $this->freezeSecond();
        $nguoi = $this->taoTaiKhoan();
        $a = $this->dangNhap($nguoi);
        $this->travel(30)->days();
        $this->goi('get', '/api/v1/me', $a)->assertUnauthorized();
        $this->travelBack();
        foreach (['dau_phien_dang_nhap' => null, 'abilities' => ['*'], 'expires_at' => null] as $truong => $giaTri) {
            $b = $this->dangNhap($nguoi);
            PersonalAccessToken::findToken($b)->forceFill([$truong => $giaTri])->save();
            $this->goi('get', '/api/v1/me', $b)->assertUnauthorized();
        }
    }

    public function test_bearer_sua_ho_so_dung_nguoi_stale_409_va_khong_doi_quyen(): void
    {
        $nguoi = $this->taoTaiKhoan();
        $khac = $this->taoTaiKhoan();
        $a = $this->dangNhap($nguoi);
        $data = ['ho_ten' => 'Tên mới mobile', 'updated_at' => $nguoi->updated_at->format('Y-m-d H:i:s.u'), 'muc_tieu' => 'Tập đều'];
        $this->goi('put', '/api/v1/me/ho-so', $a, [...$data, 'vai_tro' => 'ADMIN', 'tai_khoan_id' => $khac->id])->assertUnprocessable();
        $this->goi('put', '/api/v1/me/ho-so', $a, $data)->assertOk()->assertJsonPath('data.ho_ten', 'Tên mới mobile');
        $this->goi('put', '/api/v1/me/ho-so', $a, $data)->assertConflict();
        $this->assertSame('Mobile QA', $khac->fresh()->ho_ten);
        $this->goi('get', '/api/v1/admin/tai-khoan', $a)->assertForbidden();
    }

    public function test_khoa_mo_khong_hoi_sinh_token_reset_thu_hoi_va_rollback(): void
    {
        $nguoi = $this->taoTaiKhoan();
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $a = $this->dangNhap($nguoi);
        $dichVu = app(HoSoTaiKhoanService::class);
        $dichVu->datTrangThai($admin, $nguoi->id, ['trang_thai' => 'BI_KHOA', 'updated_at' => $nguoi->updated_at->format('Y-m-d H:i:s.u')]);
        $this->assertSame(0, $nguoi->tokens()->count());
        $dichVu->datTrangThai($admin, $nguoi->id, ['trang_thai' => 'HOAT_DONG', 'updated_at' => $nguoi->fresh()->updated_at->format('Y-m-d H:i:s.u')]);
        $this->goi('get', '/api/v1/me', $a)->assertUnauthorized();
        $b = $this->dangNhap($nguoi);
        $data = ['email' => $nguoi->email, 'token' => Password::broker('tai_khoan')->createToken($nguoi), 'password' => 'MatKhauMoi123!', 'password_confirmation' => 'MatKhauMoi123!'];
        Event::listen(PasswordReset::class, fn () => throw new RuntimeException('Lỗi kiểm thử rollback'));
        try {
            app(KhoiPhucMatKhauService::class)->datLai($data);
            $this->fail('Phải rollback');
        } catch (RuntimeException) {
        }
        $this->goi('get', '/api/v1/me', $b)->assertOk();
        Event::forget(PasswordReset::class);
        app(KhoiPhucMatKhauService::class)->datLai($data);
        $this->goi('get', '/api/v1/me', $b)->assertUnauthorized();
        $this->assertSame(0, $nguoi->tokens()->count());
    }

    public function test_cap_token_rollback_khi_ghi_dau_loi_va_cookie_web_giu_csrf(): void
    {
        $nguoi = $this->taoTaiKhoan();
        Event::listen('eloquent.updating: '.PersonalAccessToken::class, fn () => throw new RuntimeException('Lỗi ghi dấu'));
        try {
            app(PhienMobileService::class)->dangNhap(['email' => $nguoi->email, 'password' => 'Demo123456!', 'ten_thiet_bi' => 'QA']);
            $this->fail('Phải rollback');
        } catch (RuntimeException) {
        }
        Event::forget('eloquent.updating: '.PersonalAccessToken::class);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->postJson('/dang-nhap', ['email' => $nguoi->email, 'password' => 'Demo123456!'])->assertOk();
        $this->withHeader('Origin', config('app.frontend_url'))->getJson('/api/v1/me')->assertOk();
        $this->app->instance('env', 'local');
        $this->putJson('/api/v1/me/ho-so', ['ho_ten' => 'Web', 'updated_at' => $nguoi->updated_at->format('Y-m-d H:i:s.u')])->assertStatus(419);
        $this->app->instance('env', 'testing');
    }

    public function test_login_cho_khoa_reset_tren_mysql_khong_cap_tu_trang_thai_cu(): void
    {
        DB::commit();
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        foreach (['khoa', 'reset'] as $hanhDong) {
            $nguoi = $this->taoTaiKhoan();
            $reset = ['email' => $nguoi->email, 'token' => Password::broker('tai_khoan')->createToken($nguoi), 'password' => 'MatKhauMoi123!', 'password_confirmation' => 'MatKhauMoi123!'];
            $ready = tempnam(sys_get_temp_dir(), 'mobile-ready-');
            unlink($ready);
            $out = tempnam(sys_get_temp_dir(), 'mobile-out-');
            $err = tempnam(sys_get_temp_dir(), 'mobile-err-');
            DB::beginTransaction();
            TaiKhoan::lockForUpdate()->findOrFail($nguoi->id);
            $proc = proc_open([PHP_BINARY, base_path('tests/Support/mobile-worker.php'), self::$tenDatabase, (string) $nguoi->id, $ready], [0 => ['pipe', 'r'], 1 => ['file', $out, 'w'], 2 => ['file', $err, 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
            $this->assertIsResource($proc);
            fclose($pipes[0]);
            try {
                $han = microtime(true) + 10;
                while (! file_exists($ready) && microtime(true) < $han) {
                    usleep(20000);
                }
                $this->assertFileExists($ready);
                if ($hanhDong === 'khoa') {
                    app(HoSoTaiKhoanService::class)->datTrangThai($admin, $nguoi->id, ['trang_thai' => 'BI_KHOA', 'updated_at' => $nguoi->updated_at->format('Y-m-d H:i:s.u')]);
                } else {
                    app(KhoiPhucMatKhauService::class)->datLai($reset);
                }
                DB::commit();
                $this->assertSame(0, proc_close($proc), file_get_contents($err));
                $this->assertFalse(json_decode(file_get_contents($out), true, flags: JSON_THROW_ON_ERROR)['ok']);
                $this->assertSame(0, $nguoi->tokens()->count());
            } finally {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack(0);
                }
                if (is_resource($proc)) {
                    proc_terminate($proc);
                    proc_close($proc);
                }
                foreach ([$ready, $out, $err] as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
            }
        }
    }
}
