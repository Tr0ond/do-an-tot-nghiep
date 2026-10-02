<?php

namespace Tests\Feature;

use App\Models\HoSoKhachHang;
use App\Models\TaiKhoan;
use App\Notifications\KhoiPhucMatKhau;
use App\Services\HoSoTaiKhoanService;
use App\Services\KhoiPhucMatKhauService;
use App\Services\TaiKhoanService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PDO;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HoSoTaiKhoanTest extends TestCase
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
            config(['database.connections.may_chu_ho_so' => $cauHinh]);
            self::$pdoMayChu = DB::connection('may_chu_ho_so')->getPdo();
            self::$tenDatabase = 'kiem_tra_ho_so_'.bin2hex(random_bytes(8));
            self::$pdoMayChu->exec('CREATE DATABASE `'.self::$tenDatabase.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
        config(['database.connections.mysql.database' => self::$tenDatabase, 'database.connections.mysql.url' => null]);
        DB::purge('mysql');
        DB::setDefaultConnection('mysql');
        $this->assertSame(self::$tenDatabase, DB::selectOne('SELECT DATABASE() AS ten')->ten);
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'mysql', '--force' => true]));
        DB::beginTransaction();
        Notification::fake();
    }

    protected function tearDown(): void
    {
        Event::forget('eloquent.updated: '.HoSoKhachHang::class);
        Event::forget(PasswordReset::class);
        if (DB::connection()->transactionLevel() > 0) {
            DB::rollBack(0);
        }
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$tenDatabase !== null) {
            self::$pdoMayChu->exec('DROP DATABASE `'.self::$tenDatabase.'`');
            self::$tenDatabase = null;
            self::$pdoMayChu = null;
        }
        parent::tearDownAfterClass();
    }

    private function taoTaiKhoan(string $vaiTro = TaiKhoan::KHACH_HANG): TaiKhoan
    {
        return app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Tài khoản kiểm thử', 'email' => bin2hex(random_bytes(5)).'@example.test', 'password' => 'Demo123456!'], $vaiTro);
    }

    private function dangNhap(TaiKhoan $taiKhoan): void
    {
        Auth::forgetGuards();
        $this->actingAs($taiKhoan->fresh(), 'web');
    }

    private function phienBan(TaiKhoan $taiKhoan): ?string
    {
        return $taiKhoan->fresh()->updated_at?->format('Y-m-d H:i:s.u');
    }

    private function duLieuReset(TaiKhoan $taiKhoan, ?string $token = null): array
    {
        return ['email' => $taiKhoan->email, 'token' => $token ?? Password::broker('tai_khoan')->createToken($taiKhoan), 'password' => 'MatKhauMoi123!', 'password_confirmation' => 'MatKhauMoi123!'];
    }

    public function test_sua_dung_ho_so_theo_vai_tro_va_khong_ghi_de_nguoi_khac(): void
    {
        $khac = $this->taoTaiKhoan();
        $banCu = $khac->hoSoKhachHang->toArray();
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN, TaiKhoan::ADMIN] as $vaiTro) {
            $taiKhoan = $this->taoTaiKhoan($vaiTro);
            $this->dangNhap($taiKhoan);
            $duLieu = ['ho_ten' => 'Tên mới', 'updated_at' => $this->phienBan($taiKhoan)];
            if ($vaiTro === TaiKhoan::KHACH_HANG) {
                $duLieu += ['muc_tieu' => 'Tăng sức bền', 'kinh_nghiem' => 'Mới bắt đầu', 'gioi_tinh' => 'NAM', 'ngay_sinh' => '2001-10-02', 'thoi_gian_co_the_tap' => ['Thứ hai 18:00']];
            }
            if ($vaiTro === TaiKhoan::HUAN_LUYEN_VIEN) {
                $duLieu += ['chuyen_mon' => 'Sức bền', 'gioi_thieu' => '<script>Không thực thi</script>'];
            }
            $this->putJson('/api/v1/me/ho-so', $duLieu)->assertOk()->assertJsonPath('data.ho_ten', 'Tên mới')->assertJsonPath('data.email', $taiKhoan->email)->assertJsonMissingPath('data.password')->assertJsonMissingPath('data.remember_token');
            $this->assertNotSame($duLieu['updated_at'], $this->phienBan($taiKhoan));
            if ($vaiTro === TaiKhoan::KHACH_HANG) {
                $this->assertSame(['Thứ hai 18:00'], $taiKhoan->fresh()->hoSoKhachHang->thoi_gian_co_the_tap);
            }
            if ($vaiTro === TaiKhoan::HUAN_LUYEN_VIEN) {
                $this->assertSame('Sức bền', $taiKhoan->fresh()->hoSoHuanLuyenVien->chuyen_mon);
            }
        }
        $this->assertSame($banCu, $khac->hoSoKhachHang()->first()->toArray());
    }

    public function test_profile_validation_quyen_version_noop_va_null_cu(): void
    {
        $this->putJson('/api/v1/me/ho-so', [])->assertUnauthorized();
        $taiKhoan = $this->taoTaiKhoan();
        $this->dangNhap($taiKhoan);
        $duLieu = ['ho_ten' => $taiKhoan->ho_ten, 'updated_at' => $this->phienBan($taiKhoan)];
        $this->putJson('/api/v1/me/ho-so', $duLieu)->assertOk();
        $this->assertSame($duLieu['updated_at'], $this->phienBan($taiKhoan));
        foreach (['id' => 100, 'tai_khoan_id' => 100, 'email' => 'sua@example.test', 'password' => 'Demo123456!', 'vai_tro' => 'ADMIN', 'trang_thai' => 'HOAT_DONG', 'chuyen_mon' => 'Không thuộc KH'] as $truong => $giaTri) {
            $this->putJson('/api/v1/me/ho-so', [...$duLieu, $truong => $giaTri])->assertUnprocessable()->assertJsonValidationErrors($truong);
        }
        $this->putJson('/api/v1/me/ho-so', [...$duLieu, 'ngay_sinh' => now()->addDay()->format('Y-m-d'), 'gioi_tinh' => 'SAI', 'thoi_gian_co_the_tap' => array_fill(0, 15, 'Giờ tập')])->assertUnprocessable()->assertJsonValidationErrors(['ngay_sinh', 'gioi_tinh', 'thoi_gian_co_the_tap']);
        $this->putJson('/api/v1/me/ho-so', [...$duLieu, 'thoi_gian_co_the_tap' => [str_repeat('a', 121)]])->assertUnprocessable()->assertJsonValidationErrors('thoi_gian_co_the_tap.0');
        $this->putJson('/api/v1/me/ho-so', ['ho_ten' => 'Mới'])->assertUnprocessable()->assertJsonValidationErrors('updated_at');
        $this->putJson('/api/v1/me/ho-so', [...$duLieu, 'ho_ten' => 'Mới'])->assertOk();
        $this->putJson('/api/v1/me/ho-so', [...$duLieu, 'ho_ten' => 'Cũ'])->assertConflict();
        DB::table('tai_khoan')->where('id', $taiKhoan->id)->update(['updated_at' => null]);
        $this->putJson('/api/v1/me/ho-so', ['ho_ten' => 'Legacy', 'updated_at' => null])->assertOk();
        $this->putJson('/api/v1/me/ho-so', ['ho_ten' => 'Legacy2', 'updated_at' => null])->assertConflict();
        $taiKhoan->refresh();
        $taiKhoan->trang_thai = TaiKhoan::BI_KHOA;
        $taiKhoan->save();
        $this->dangNhap($taiKhoan);
        $this->putJson('/api/v1/me/ho-so', [])->assertForbidden();
    }

    public function test_ho_so_rollback_ca_ten_va_ho_so_neu_event_loi(): void
    {
        $taiKhoan = $this->taoTaiKhoan();
        $banCu = $taiKhoan->fresh()->toArray();
        $hoSoCu = $taiKhoan->hoSoKhachHang->toArray();
        Event::listen('eloquent.updated: '.HoSoKhachHang::class, fn () => throw new RuntimeException('Lỗi sau ghi hồ sơ'));
        try {
            app(HoSoTaiKhoanService::class)->suaHoSo($taiKhoan, ['ho_ten' => 'Mới', 'muc_tieu' => 'Mới', 'updated_at' => $this->phienBan($taiKhoan)]);
            $this->fail('Phải rollback.');
        } catch (RuntimeException $loi) {
            $this->assertSame('Lỗi sau ghi hồ sơ', $loi->getMessage());
        }
        $this->assertSame($banCu, $taiKhoan->fresh()->toArray());
        $this->assertSame($hoSoCu, $taiKhoan->fresh()->hoSoKhachHang->toArray());
    }

    public function test_khoa_mo_quyen_noop_xung_dot_va_bao_toan_ho_so(): void
    {
        $taiKhoan = $this->taoTaiKhoan();
        $hoSoCu = $taiKhoan->hoSoKhachHang->toArray();
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $url = '/api/v1/admin/tai-khoan/'.$taiKhoan->id.'/trang-thai';
        $duLieu = ['trang_thai' => TaiKhoan::BI_KHOA, 'updated_at' => $this->phienBan($taiKhoan)];
        $this->patchJson($url, $duLieu)->assertUnauthorized();
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN] as $vaiTro) {
            $this->dangNhap($this->taoTaiKhoan($vaiTro));
            $this->patchJson($url, $duLieu)->assertForbidden();
        }
        $this->dangNhap($admin);
        $this->patchJson($url, [...$duLieu, 'vai_tro' => 'ADMIN'])->assertUnprocessable();
        $this->patchJson($url, ['trang_thai' => 'BI_KHOA'])->assertUnprocessable();
        $this->patchJson($url, [...$duLieu, 'trang_thai' => 'SAI'])->assertUnprocessable();
        $this->patchJson('/api/v1/admin/tai-khoan/'.$admin->id.'/trang-thai', ['trang_thai' => 'BI_KHOA', 'updated_at' => $this->phienBan($admin)])->assertConflict();
        $this->patchJson('/api/v1/admin/tai-khoan/999999/trang-thai', $duLieu)->assertNotFound();
        $this->deleteJson('/api/v1/admin/tai-khoan/'.$taiKhoan->id)->assertNotFound();
        Password::broker('tai_khoan')->createToken($taiKhoan);
        $this->patchJson($url, $duLieu)->assertOk()->assertJsonPath('data.trang_thai', 'BI_KHOA');
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $taiKhoan->email]);
        $this->assertSame($hoSoCu, $taiKhoan->fresh()->hoSoKhachHang->toArray());
        $this->patchJson($url, $duLieu)->assertConflict();
        $banKhoa = $this->phienBan($taiKhoan);
        $this->patchJson($url, ['trang_thai' => 'BI_KHOA', 'updated_at' => $banKhoa])->assertOk();
        $this->assertSame($banKhoa, $this->phienBan($taiKhoan));
        $this->patchJson($url, ['trang_thai' => 'HOAT_DONG', 'updated_at' => $banKhoa])->assertOk();
    }

    public function test_khoa_roi_mo_khong_hoi_sinh_session_cu_va_chan_admin_da_khoa(): void
    {
        $this->withHeader('Origin', config('app.frontend_url'));
        $taiKhoan = $this->taoTaiKhoan();
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $this->postJson('/dang-nhap', ['email' => $taiKhoan->email, 'password' => 'Demo123456!'])->assertOk();
        $this->getJson('/api/v1/me')->assertOk();
        $phienCu = session()->all();
        $dichVu = app(HoSoTaiKhoanService::class);
        $dichVu->datTrangThai($admin, $taiKhoan->id, ['trang_thai' => 'BI_KHOA', 'updated_at' => $this->phienBan($taiKhoan)]);
        $dichVu->datTrangThai($admin, $taiKhoan->id, ['trang_thai' => 'HOAT_DONG', 'updated_at' => $this->phienBan($taiKhoan)]);
        Auth::forgetGuards();
        $this->withSession($phienCu);
        $this->getJson('/api/v1/me')->assertUnauthorized();
        Auth::forgetGuards();
        $this->postJson('/dang-nhap', ['email' => $taiKhoan->email, 'password' => 'Demo123456!'])->assertOk();
        $this->getJson('/api/v1/me')->assertOk();
        $admin->trang_thai = TaiKhoan::BI_KHOA;
        $admin->save();
        try {
            $dichVu->datTrangThai($admin, $taiKhoan->id, ['trang_thai' => 'BI_KHOA', 'updated_at' => $this->phienBan($taiKhoan)]);
            $this->fail('Admin đã khóa không có quyền.');
        } catch (HttpException $loi) {
            $this->assertSame(403, $loi->getStatusCode());
        }
    }

    public function test_gui_email_response_chung_token_hash_url_fragment_va_throttle(): void
    {
        $taiKhoan = $this->taoTaiKhoan();
        $biKhoa = $this->taoTaiKhoan();
        $biKhoa->trang_thai = 'BI_KHOA';
        $biKhoa->save();
        $phanHoi = $this->postJson('/quen-mat-khau', ['email' => ' '.strtoupper($taiKhoan->email).' '])->assertOk()->assertJsonPath('data', null)->json();
        foreach ([$biKhoa->email, 'khong-ton-tai@example.test', $taiKhoan->email] as $email) {
            $this->postJson('/quen-mat-khau', ['email' => $email])->assertOk()->assertExactJson($phanHoi);
        }
        Notification::assertSentToTimes($taiKhoan, KhoiPhucMatKhau::class, 1);
        Notification::assertNotSentTo($biKhoa, KhoiPhucMatKhau::class);
        $thu = Notification::sent($taiKhoan, KhoiPhucMatKhau::class)->first();
        $this->assertNotSame($thu->token, DB::table('password_reset_tokens')->where('email', $taiKhoan->email)->value('token'));
        $this->assertTrue(Hash::check($thu->token, DB::table('password_reset_tokens')->where('email', $taiKhoan->email)->value('token')));
        $this->assertStringStartsWith(config('app.frontend_url').'/dat-lai-mat-khau#', $thu->toMail($taiKhoan)->actionUrl);
        $this->assertNull(parse_url($thu->toMail($taiKhoan)->actionUrl, PHP_URL_QUERY));
        $this->assertArrayNotHasKey('token', $phanHoi);
        $this->postJson('/quen-mat-khau', ['email' => 'sai'])->assertUnprocessable();
        $this->postJson('/quen-mat-khau', ['email' => $taiKhoan->email, 'trang_thai' => 'HOAT_DONG'])->assertUnprocessable();
        for ($i = 0; $i < 6; $i++) {
            $cuoi = $this->postJson('/quen-mat-khau', ['email' => 'rate@example.test']);
        }
        $cuoi->assertTooManyRequests();
    }

    public function test_cors_cho_ca_hai_duong_dan_khoi_phuc_va_loi_mail_khong_lo_email(): void
    {
        foreach (['/quen-mat-khau', '/dat-lai-mat-khau'] as $url) {
            $this->options($url, [], ['Origin' => config('app.frontend_url'), 'Access-Control-Request-Method' => 'POST', 'Access-Control-Request-Headers' => 'content-type,x-xsrf-token'])
                ->assertNoContent()->assertHeader('Access-Control-Allow-Origin', config('app.frontend_url'))->assertHeader('Access-Control-Allow-Credentials', 'true');
        }
        $this->withHeader('Origin', config('app.frontend_url'))->postJson('/quen-mat-khau', ['email' => 'khong-co@example.test'])->assertOk()->assertHeader('Access-Control-Allow-Origin', config('app.frontend_url'));
        $taiKhoan = $this->taoTaiKhoan();
        $this->app->instance('env', 'local');
        config(['mail.default' => 'log']);
        $this->withoutMiddleware([PreventRequestForgery::class, ValidateCsrfToken::class]);
        foreach ([$taiKhoan->email, 'khong-co@example.test'] as $email) {
            $this->postJson('/quen-mat-khau', ['email' => $email])->assertStatus(503)->assertJsonPath('data', null)->assertJsonMissingPath('token');
        }
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->app->instance('env', 'testing');
    }

    public function test_reset_hop_le_ca_ba_vai_tro_chi_mot_lan_khong_tu_dang_nhap(): void
    {
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN, TaiKhoan::ADMIN] as $vaiTro) {
            $taiKhoan = $this->taoTaiKhoan($vaiTro);
            $duLieu = $this->duLieuReset($taiKhoan);
            $this->postJson('/dat-lai-mat-khau', $duLieu)->assertOk()->assertJsonPath('data', null);
            $this->assertGuest('web');
            $this->assertTrue(Hash::check('MatKhauMoi123!', $taiKhoan->fresh()->password));
            $this->assertFalse(Hash::check('Demo123456!', $taiKhoan->fresh()->password));
            $this->assertSame($vaiTro, $taiKhoan->fresh()->vai_tro);
            $this->assertDatabaseMissing('password_reset_tokens', ['email' => $taiKhoan->email]);
            $this->postJson('/dat-lai-mat-khau', $duLieu)->assertUnprocessable()->assertJsonValidationErrors('token');
        }
    }

    public function test_dang_xuat_khong_doi_dau_phien_cua_thiet_bi_khac(): void
    {
        $taiKhoan = $this->taoTaiKhoan();
        $taiKhoan->remember_token = str_repeat('b', 60);
        $taiKhoan->save();
        $dauPhien = $taiKhoan->dauPhienDangNhap();
        $this->postJson('/dang-nhap', ['email' => $taiKhoan->email, 'password' => 'Demo123456!'])->assertOk();
        $this->postJson('/dang-xuat')->assertOk();
        $this->assertSame($dauPhien, $taiKhoan->fresh()->dauPhienDangNhap());
        $this->assertGuest('web');
    }

    public function test_reset_sai_het_han_khoa_mismatch_qua_byte_va_csrf(): void
    {
        $taiKhoan = $this->taoTaiKhoan();
        $duLieu = $this->duLieuReset($taiKhoan);
        $matKhauCu = $taiKhoan->password;
        $this->postJson('/dat-lai-mat-khau', [...$duLieu, 'token' => str_repeat('a', 64)])->assertUnprocessable()->assertJsonValidationErrors('token');
        $this->postJson('/dat-lai-mat-khau', [...$duLieu, 'password_confirmation' => 'sai'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson('/dat-lai-mat-khau', [...$duLieu, 'password' => str_repeat('ấ', 25), 'password_confirmation' => str_repeat('ấ', 25)])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson('/dat-lai-mat-khau', [...$duLieu, 'vai_tro' => 'ADMIN'])->assertUnprocessable();
        DB::table('password_reset_tokens')->where('email', $taiKhoan->email)->update(['created_at' => now()->subMinutes(61)]);
        $this->postJson('/dat-lai-mat-khau', $duLieu)->assertUnprocessable();
        $duLieu = $this->duLieuReset($taiKhoan);
        $taiKhoan->trang_thai = 'BI_KHOA';
        $taiKhoan->save();
        $this->postJson('/dat-lai-mat-khau', $duLieu)->assertUnprocessable();
        $this->assertSame($matKhauCu, $taiKhoan->fresh()->password);
        $this->app->instance('env', 'local');
        $this->postJson('/quen-mat-khau', ['email' => 'abc@example.test'])->assertStatus(419);
        $this->postJson('/dat-lai-mat-khau', $duLieu)->assertStatus(419);
        $this->app->instance('env', 'testing');
    }

    public function test_reset_rollback_token_mat_khau_va_sessions_khi_event_loi(): void
    {
        $taiKhoan = $this->taoTaiKhoan();
        $duLieu = $this->duLieuReset($taiKhoan);
        DB::table('sessions')->insert(['id' => 'phien-kiem-thu', 'user_id' => $taiKhoan->id, 'payload' => '', 'last_activity' => time()]);
        config(['session.driver' => 'database', 'session.connection' => 'mysql']);
        $banCu = $taiKhoan->fresh()->toArray();
        $matKhauCu = $taiKhoan->password;
        $rememberCu = $taiKhoan->remember_token;
        Event::listen(PasswordReset::class, fn () => throw new RuntimeException('Lỗi sau đổi mật khẩu'));
        try {
            app(KhoiPhucMatKhauService::class)->datLai($duLieu);
            $this->fail('Phải rollback.');
        } catch (RuntimeException $loi) {
            $this->assertSame('Lỗi sau đổi mật khẩu', $loi->getMessage());
        }
        $this->assertSame($banCu, $taiKhoan->fresh()->toArray());
        $this->assertSame($matKhauCu, $taiKhoan->fresh()->password);
        $this->assertSame($rememberCu, $taiKhoan->fresh()->remember_token);
        $this->assertDatabaseHas('sessions', ['id' => 'phien-kiem-thu']);
        $this->assertTrue(Password::broker('tai_khoan')->tokenExists($taiKhoan, $duLieu['token']));
        Event::forget(PasswordReset::class);
        app(KhoiPhucMatKhauService::class)->datLai($duLieu);
        $this->assertDatabaseMissing('sessions', ['id' => 'phien-kiem-thu']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $taiKhoan->email]);
    }

    public function test_reset_thu_hoi_phien_dang_nhap_va_csrf_ho_so_trang_thai(): void
    {
        $this->withHeader('Origin', config('app.frontend_url'));
        $taiKhoan = $this->taoTaiKhoan();
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $this->postJson('/dang-nhap', ['email' => $taiKhoan->email, 'password' => 'Demo123456!'])->assertOk();
        $this->getJson('/api/v1/me')->assertOk();
        $phienCu = session()->all();
        app(KhoiPhucMatKhauService::class)->datLai($this->duLieuReset($taiKhoan));
        Auth::forgetGuards();
        $this->withSession($phienCu);
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->dangNhap($admin);
        $this->app->instance('env', 'local');
        $this->withHeader('Origin', config('app.frontend_url'));
        $this->putJson('/api/v1/me/ho-so', ['ho_ten' => 'Mới', 'updated_at' => $this->phienBan($admin)])->assertStatus(419);
        $this->patchJson('/api/v1/admin/tai-khoan/'.$taiKhoan->id.'/trang-thai', ['trang_thai' => 'BI_KHOA', 'updated_at' => $this->phienBan($taiKhoan)])->assertStatus(419);
        $this->app->instance('env', 'testing');
    }
}
