<?php

namespace Tests\Feature;

use App\Models\HoSoKhachHang;
use App\Models\TaiKhoan;
use App\Services\TaiKhoanService;
use Database\Seeders\TaiKhoanSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PDO;
use Tests\TestCase;

class XacThucTest extends TestCase
{
    private static ?string $tenDatabase = null;

    private static ?PDO $pdoMayChu = null;

    protected function setUp(): void
    {
        parent::setUp();
        $cauHinh = config('database.connections.mysql');
        if (self::$tenDatabase === null) {
            // Tạo database mới ngẫu nhiên; tuyệt đối không refresh database ứng dụng.
            $cauHinh['database'] = null;
            $cauHinh['url'] = null;
            $cauHinh['name'] = 'may_chu_kiem_thu';
            config(['database.connections.may_chu_kiem_thu' => $cauHinh]);
            self::$pdoMayChu = DB::connection('may_chu_kiem_thu')->getPdo();
            $tenMoi = 'kiem_tra_xac_thuc_'.bin2hex(random_bytes(8));
            self::$pdoMayChu->exec('CREATE DATABASE `'.$tenMoi.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            self::$tenDatabase = $tenMoi;
        }
        config(['database.connections.mysql.database' => self::$tenDatabase, 'database.connections.mysql.url' => null]);
        DB::purge('mysql');
        DB::setDefaultConnection('mysql');
        $this->assertSame(self::$tenDatabase, DB::selectOne('SELECT DATABASE() AS ten')->ten);
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'mysql', '--force' => true]));
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
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

    private function duLieu(string $email = 'khach@example.test'): array
    {
        return ['ho_ten' => 'Khách kiểm thử', 'email' => $email, 'password' => 'MatKhau123!', 'password_confirmation' => 'MatKhau123!'];
    }

    private function taoTaiKhoan(string $vaiTro, string $email = 'khach@example.test'): TaiKhoan
    {
        return app(TaiKhoanService::class)->taoTaiKhoan($this->duLieu($email), $vaiTro);
    }

    public function test_dang_ky_tao_khach_hang_va_ho_so_ma_hoa_mat_khau(): void
    {
        $this->postJson('/dang-ky', $this->duLieu(' KHACH@example.test '))
            ->assertCreated()->assertJsonPath('status', true)
            ->assertJsonPath('data.vai_tro', 'KHACH_HANG')
            ->assertJsonPath('data.email', 'khach@example.test')
            ->assertJsonMissingPath('data.password')->assertJsonMissingPath('data.remember_token')
            ->assertJsonPath('data.ho_so_khach_hang.tai_khoan_id', TaiKhoan::first()->id);
        $this->assertTrue(Hash::check('MatKhau123!', TaiKhoan::first()->password));
        $this->assertDatabaseCount('tai_khoan', 1);
        $this->assertDatabaseCount('ho_so_khach_hang', 1);
        $this->assertAuthenticatedAs(TaiKhoan::first(), 'web');
    }

    public function test_khong_duoc_tu_dat_vai_tro_trang_thai_hoac_id(): void
    {
        foreach (['vai_tro' => 'ADMIN', 'trang_thai' => 'HOAT_DONG', 'tai_khoan_id' => 99] as $truong => $giaTri) {
            $this->postJson('/dang-ky', [...$this->duLieu(), $truong => $giaTri])
                ->assertUnprocessable()->assertJsonValidationErrors($truong)->assertJsonPath('status', false);
        }
        $this->assertDatabaseCount('tai_khoan', 0);
    }

    public function test_validation_email_mat_khau_va_xac_nhan(): void
    {
        $this->postJson('/dang-ky', [...$this->duLieu(), 'email' => 'sai', 'password' => '123'])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->postJson('/dang-ky', [...$this->duLieu(), 'password_confirmation' => 'khong-khop'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertDatabaseCount('ho_so_khach_hang', 0);
    }

    public function test_email_trung_khong_tao_tai_khoan_hoac_ho_so_thu_hai(): void
    {
        $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
        $this->postJson('/dang-ky', $this->duLieu('KHACH@example.test'))->assertUnprocessable()->assertJsonValidationErrors('email');
        // Kiểm tra lớp transaction/UNIQUE độc lập với validation, như request tới muộn.
        try {
            app(TaiKhoanService::class)->taoTaiKhoan($this->duLieu(), TaiKhoan::KHACH_HANG);
            $this->fail('UNIQUE phải chặn email trùng.');
        } catch (ValidationException $loi) {
            $this->assertArrayHasKey('email', $loi->errors());
        }
        $this->assertDatabaseCount('tai_khoan', 1);
        $this->assertDatabaseCount('ho_so_khach_hang', 1);
    }

    public function test_rollback_tai_khoan_neu_tao_ho_so_that_bai(): void
    {
        HoSoKhachHang::creating(fn () => throw new \RuntimeException('Lỗi hồ sơ giả lập'));
        try {
            $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
            $this->fail('Phải phát hiện lỗi tạo hồ sơ.');
        } catch (\RuntimeException $loi) {
            $this->assertSame('Lỗi hồ sơ giả lập', $loi->getMessage());
        } finally {
            HoSoKhachHang::flushEventListeners();
        }
        $this->assertDatabaseCount('tai_khoan', 0);
        $this->assertDatabaseCount('ho_so_khach_hang', 0);
    }

    public function test_dang_nhap_ca_ba_vai_tro_va_dang_xuat(): void
    {
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN, TaiKhoan::ADMIN] as $vaiTro) {
            $taiKhoan = $this->taoTaiKhoan($vaiTro, strtolower($vaiTro).'@example.test');
            $this->postJson('/dang-nhap', ['email' => $taiKhoan->email, 'password' => 'MatKhau123!'])
                ->assertOk()->assertJsonPath('data.vai_tro', $vaiTro)->assertJsonMissingPath('data.password');
            $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.id', $taiKhoan->id);
            $this->postJson('/dang-xuat')->assertOk();
            // Request thật khởi tạo guard mới; không giữ cache guard giữa các HTTP tests.
            Auth::forgetGuards();
            $this->getJson('/api/v1/me')->assertUnauthorized()->assertJsonPath('status', false);
        }
    }

    public function test_sai_mat_khau_email_khong_ton_tai_va_tai_khoan_khoa(): void
    {
        $taiKhoan = $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
        $saiMatKhau = $this->postJson('/dang-nhap', ['email' => $taiKhoan->email, 'password' => 'sai'])->assertUnprocessable();
        $khongTonTai = $this->postJson('/dang-nhap', ['email' => 'khong-co@example.test', 'password' => 'sai'])->assertUnprocessable();
        $taiKhoan->trang_thai = 'KHOA';
        $taiKhoan->save();
        $biKhoa = $this->postJson('/dang-nhap', ['email' => $taiKhoan->email, 'password' => 'MatKhau123!'])->assertUnprocessable();
        $this->assertSame($saiMatKhau->json('errors.email'), $khongTonTai->json('errors.email'));
        $this->assertSame($saiMatKhau->json('errors.email'), $biKhoa->json('errors.email'));
        $this->assertGuest('web');
    }

    public function test_tai_khoan_bi_khoa_mat_quyen_tren_request_tiep_theo(): void
    {
        $taiKhoan = $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
        $this->actingAs($taiKhoan, 'web');
        $taiKhoan->trang_thai = 'KHOA';
        $taiKhoan->save();
        $this->getJson('/api/v1/me')->assertForbidden()->assertJsonPath('status', false);
        $this->assertGuest('web');
    }

    public function test_chua_dang_nhap_va_bearer_khong_duoc_truy_cap(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer token-khong-hop-le'])->assertUnauthorized();
        $this->getJson('/api/v1/admin/tai-khoan')->assertUnauthorized();
    }

    public function test_khach_chi_doc_duoc_ho_so_cua_minh(): void
    {
        $khach = $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
        $khac = $this->taoTaiKhoan(TaiKhoan::KHACH_HANG, 'khac@example.test');
        $this->actingAs($khach, 'web');
        $this->getJson('/api/v1/khach-hang/ho-so/'.$khach->hoSoKhachHang->id)->assertOk()->assertJsonPath('data.ho_ten', $khach->ho_ten);
        $this->getJson('/api/v1/khach-hang/ho-so/'.$khac->hoSoKhachHang->id)->assertForbidden();
        $this->getJson('/api/v1/khach-hang/ho-so/999999')->assertNotFound()->assertJsonPath('status', false);
    }

    public function test_pt_chi_doc_khach_duoc_phan_cong_hien_tai_va_mat_quyen_khi_dong(): void
    {
        $khach = $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
        $pt = $this->taoTaiKhoan(TaiKhoan::HUAN_LUYEN_VIEN, 'pt@example.test');
        $this->actingAs($pt, 'web');
        $duongDan = '/api/v1/khach-hang/ho-so/'.$khach->hoSoKhachHang->id;
        $this->getJson($duongDan)->assertForbidden();
        $phanCongId = DB::table('phan_cong_huan_luyen_vien')->insertGetId(['khach_hang_id' => $khach->hoSoKhachHang->id, 'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'nguoi_phan_cong_id' => $pt->id, 'bat_dau_luc' => now()->subDay()]);
        $this->getJson($duongDan)->assertOk();
        DB::table('phan_cong_huan_luyen_vien')->where('id', $phanCongId)->update(['ket_thuc_luc' => now()->subMinute()]);
        $this->getJson($duongDan)->assertForbidden();
        DB::table('phan_cong_huan_luyen_vien')->where('id', $phanCongId)->update(['bat_dau_luc' => now()->addDay(), 'ket_thuc_luc' => null]);
        $this->getJson($duongDan)->assertForbidden();
    }

    public function test_khach_va_pt_khong_duoc_truy_cap_api_admin(): void
    {
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN] as $vaiTro) {
            Auth::forgetGuards();
            $this->actingAs($this->taoTaiKhoan($vaiTro, strtolower($vaiTro).'@example.test'), 'web');
            $this->getJson('/api/v1/admin/tai-khoan')->assertForbidden();
            $this->postJson('/api/v1/admin/tai-khoan', [...$this->duLieu('moi@example.test'), 'vai_tro' => 'ADMIN'])->assertForbidden();
        }
    }

    public function test_admin_tao_pt_va_admin_khong_doi_session_cua_minh(): void
    {
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN, 'admin@example.test');
        $this->actingAs($admin, 'web');
        foreach ([TaiKhoan::HUAN_LUYEN_VIEN, TaiKhoan::ADMIN] as $vaiTro) {
            $this->postJson('/api/v1/admin/tai-khoan', [...$this->duLieu('moi-'.strtolower($vaiTro).'@example.test'), 'vai_tro' => $vaiTro])
                ->assertCreated()->assertJsonPath('data.vai_tro', $vaiTro)->assertJsonMissingPath('data.password');
        }
        $this->assertDatabaseCount('ho_so_huan_luyen_vien', 1);
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.id', $admin->id);
        $this->getJson('/api/v1/admin/tai-khoan')->assertOk()->assertJsonPath('meta.total', 3);
        $this->postJson('/api/v1/admin/tai-khoan', [...$this->duLieu('khach-moi@example.test'), 'vai_tro' => TaiKhoan::KHACH_HANG])
            ->assertUnprocessable()->assertJsonValidationErrors('vai_tro');
    }

    public function test_khach_khong_truy_cap_route_pt(): void
    {
        $this->actingAs($this->taoTaiKhoan(TaiKhoan::KHACH_HANG), 'web');
        $this->getJson('/api/v1/pt/ho-so')->assertForbidden();
    }

    public function test_gioi_han_thu_dang_nhap(): void
    {
        for ($lan = 0; $lan < 5; $lan++) {
            $this->postJson('/dang-nhap', ['email' => 'sai@example.test', 'password' => 'sai'])->assertUnprocessable();
        }
        $this->postJson('/dang-nhap', ['email' => 'sai@example.test', 'password' => 'sai'])
            ->assertTooManyRequests()->assertJsonPath('status', false)->assertHeader('Retry-After');
    }

    public function test_api_khong_co_accept_van_tra_json_401(): void
    {
        $this->get('/api/v1/me')->assertUnauthorized()->assertJsonPath('status', false);
    }

    public function test_csrf_thieu_hoac_sai_bi_chan_truoc_dang_ky(): void
    {
        $this->batCsrfTrongTest();
        $this->withSession(['_token' => 'csrf-kiem-thu'])->postJson('/dang-ky', $this->duLieu())
            ->assertStatus(419)->assertJsonPath('status', false);
        $this->withSession(['_token' => 'csrf-kiem-thu'])->postJson('/dang-ky', $this->duLieu(), ['X-CSRF-TOKEN' => 'sai'])
            ->assertStatus(419);
        $this->assertDatabaseCount('tai_khoan', 0);
        $this->withSession(['_token' => 'csrf-kiem-thu'])->postJson('/dang-ky', $this->duLieu(), ['X-CSRF-TOKEN' => 'csrf-kiem-thu'])
            ->assertCreated();
    }

    public function test_api_admin_stateful_yeu_cau_csrf(): void
    {
        $this->batCsrfTrongTest();
        $this->actingAs($this->taoTaiKhoan(TaiKhoan::ADMIN, 'admin@example.test'), 'web');
        $this->withSession(['_token' => 'csrf-kiem-thu'])->postJson('/api/v1/admin/tai-khoan',
            [...$this->duLieu('pt@example.test'), 'vai_tro' => TaiKhoan::HUAN_LUYEN_VIEN],
            ['Origin' => 'http://localhost:5173'])->assertStatus(419);
        $this->assertDatabaseCount('tai_khoan', 1);
    }

    private function batCsrfTrongTest(): void
    {
        // Laravel bỏ qua CSRF trong PHPUnit mặc định; bật kiểm tra thật cho các test này.
        foreach ([PreventRequestForgery::class, ValidateCsrfToken::class] as $lop) {
            $this->app->bind($lop, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
            {
                protected function runningUnitTests()
                {
                    return false;
                }
            });
        }
    }

    public function test_mat_khau_unicode_qua_72_byte_bi_tu_choi(): void
    {
        $matKhau = str_repeat('ắ', 30);
        $this->postJson('/dang-ky', [...$this->duLieu(), 'password' => $matKhau, 'password_confirmation' => $matKhau])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertDatabaseCount('tai_khoan', 0);
    }

    public function test_lenh_tao_admin_dau_tien_khong_tao_admin_mac_dinh(): void
    {
        $this->artisan('tai-khoan:tao-admin', ['email' => 'admin-cli@example.test'])
            ->expectsQuestion('Họ tên Admin', 'Admin kiểm thử')
            ->expectsQuestion('Mật khẩu (8–72 ký tự)', 'MatKhau123!')
            ->expectsQuestion('Nhập lại mật khẩu', 'MatKhau123!')
            ->expectsOutput('Đã tạo Admin đầu tiên. Bạn có thể đăng nhập trên Frontend.')
            ->assertSuccessful();
        $this->assertDatabaseHas('tai_khoan', ['email' => 'admin-cli@example.test', 'vai_tro' => TaiKhoan::ADMIN]);
        $this->artisan('tai-khoan:tao-admin', ['email' => 'khac@example.test'])
            ->expectsOutput('Đã có Admin. Hãy đăng nhập Admin để tạo tài khoản tiếp theo.')
            ->assertFailed();
        $this->assertDatabaseCount('tai_khoan', 1);
    }

    public function test_seeder_tao_ba_vai_tro_ho_so_va_dang_nhap_duoc(): void
    {
        $this->seed();
        $this->assertDatabaseCount('tai_khoan', 3);
        $this->assertDatabaseCount('ho_so_khach_hang', 1);
        $this->assertDatabaseCount('ho_so_huan_luyen_vien', 1);

        foreach (['admin@example.test' => TaiKhoan::ADMIN, 'pt@example.test' => TaiKhoan::HUAN_LUYEN_VIEN, 'khachhang@example.test' => TaiKhoan::KHACH_HANG] as $email => $vaiTro) {
            $taiKhoan = TaiKhoan::where('email', $email)->firstOrFail();
            $this->assertSame($vaiTro, $taiKhoan->vai_tro);
            $this->assertSame(TaiKhoan::HOAT_DONG, $taiKhoan->trang_thai);
            $this->assertTrue(Hash::check('Demo123456!', $taiKhoan->password));
            if ($vaiTro === TaiKhoan::KHACH_HANG) {
                $this->assertNotNull($taiKhoan->hoSoKhachHang);
                $this->assertNull($taiKhoan->hoSoHuanLuyenVien);
            } elseif ($vaiTro === TaiKhoan::HUAN_LUYEN_VIEN) {
                $this->assertNotNull($taiKhoan->hoSoHuanLuyenVien);
                $this->assertNull($taiKhoan->hoSoKhachHang);
            } else {
                $this->assertNull($taiKhoan->hoSoKhachHang);
                $this->assertNull($taiKhoan->hoSoHuanLuyenVien);
            }
            Auth::forgetGuards();
            $this->postJson('/dang-nhap', ['email' => $email, 'password' => 'Demo123456!'])
                ->assertOk()->assertJsonPath('data.vai_tro', $vaiTro)->assertJsonMissingPath('data.password');
            $this->postJson('/dang-xuat')->assertOk();
        }
    }

    public function test_seeder_chay_lai_khong_trung_va_giu_du_lieu_da_sua(): void
    {
        $this->seed(TaiKhoanSeeder::class);
        $taiKhoan = TaiKhoan::where('email', 'khachhang@example.test')->firstOrFail();
        $taiKhoan->ho_ten = 'Tên đã sửa';
        $taiKhoan->password = 'MatKhauMoi123!';
        $taiKhoan->trang_thai = 'KHOA';
        $taiKhoan->save();
        $hoSo = $taiKhoan->hoSoKhachHang;
        $hoSo->muc_tieu = 'Mục tiêu đã sửa';
        $hoSo->save();
        $duLieuCu = $taiKhoan->fresh()->getAttributes();
        $hoSoCu = $hoSo->fresh()->getAttributes();

        $this->seed(TaiKhoanSeeder::class);

        $this->assertDatabaseCount('tai_khoan', 3);
        $this->assertDatabaseCount('ho_so_khach_hang', 1);
        $this->assertDatabaseCount('ho_so_huan_luyen_vien', 1);
        $this->assertSame($duLieuCu, $taiKhoan->fresh()->getAttributes());
        $this->assertSame($hoSoCu, $hoSo->fresh()->getAttributes());
    }

    public function test_seeder_bo_sung_ho_so_con_thieu_cua_tai_khoan_dung_vai_tro(): void
    {
        $this->seed(TaiKhoanSeeder::class);
        // Chỉ xóa hồ sơ demo chưa có dữ liệu phụ thuộc trong database kiểm thử riêng.
        TaiKhoan::where('email', 'pt@example.test')->firstOrFail()->hoSoHuanLuyenVien()->delete();
        TaiKhoan::where('email', 'khachhang@example.test')->firstOrFail()->hoSoKhachHang()->delete();

        $this->seed(TaiKhoanSeeder::class);

        $this->assertDatabaseCount('tai_khoan', 3);
        $this->assertDatabaseCount('ho_so_khach_hang', 1);
        $this->assertDatabaseCount('ho_so_huan_luyen_vien', 1);
    }

    public function test_seeder_trung_email_khac_vai_tro_rollback_va_khong_nang_quyen(): void
    {
        $taiKhoan = $this->taoTaiKhoan(TaiKhoan::HUAN_LUYEN_VIEN, 'khachhang@example.test');
        $duLieuCu = $taiKhoan->fresh()->getAttributes();
        try {
            $this->seed(TaiKhoanSeeder::class);
            $this->fail('Seeder phải từ chối email đã có vai trò khác.');
        } catch (\RuntimeException $loi) {
            $this->assertStringContainsString('đã tồn tại với vai trò khác', $loi->getMessage());
        }

        $this->assertDatabaseCount('tai_khoan', 1);
        $this->assertDatabaseCount('ho_so_huan_luyen_vien', 1);
        $this->assertDatabaseCount('ho_so_khach_hang', 0);
        $this->assertSame($duLieuCu, $taiKhoan->fresh()->getAttributes());
    }

    public function test_seeder_tu_choi_moi_truong_production(): void
    {
        $this->app->instance('env', 'production');
        try {
            $this->app->call([new TaiKhoanSeeder, 'run']);
            $this->fail('Seeder demo phải từ chối môi trường production.');
        } catch (\RuntimeException $loi) {
            $this->assertStringContainsString('chỉ chạy trong môi trường local hoặc testing', $loi->getMessage());
        } finally {
            $this->app->instance('env', 'testing');
        }
        $this->assertDatabaseCount('tai_khoan', 0);
    }
}
