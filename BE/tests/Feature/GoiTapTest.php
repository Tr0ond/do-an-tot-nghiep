<?php

namespace Tests\Feature;

use App\Models\GoiTap;
use App\Models\TaiKhoan;
use App\Services\GoiTapService;
use App\Services\TaiKhoanService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;
use Tests\TestCase;

class GoiTapTest extends TestCase
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
            config(['database.connections.may_chu_goi_tap' => $cauHinh]);
            self::$pdoMayChu = DB::connection('may_chu_goi_tap')->getPdo();
            $tenMoi = 'kiem_tra_goi_tap_'.bin2hex(random_bytes(8));
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
            // Chỉ xóa database ngẫu nhiên do lớp này tạo, không dùng DB ứng dụng.
            self::$pdoMayChu->exec('DROP DATABASE `'.self::$tenDatabase.'`');
            self::$tenDatabase = null;
            self::$pdoMayChu = null;
        }
        parent::tearDownAfterClass();
    }

    private function duLieu(array $ghiDe = []): array
    {
        return ['ten_goi' => 'Chatbot 30 ngày', 'gia' => 99000, 'co_chatbot' => true, 'so_luot_chatbot_moi_ngay' => 20, 'so_buoi_pt' => 0, 'thoi_han_ngay' => 30, 'trang_thai' => 'NGUNG_SU_DUNG', 'client_request_id' => (string) Str::uuid(), ...$ghiDe];
    }

    private function taoGoi(array $ghiDe = []): GoiTap
    {
        return app(GoiTapService::class)->taoGoiTap($this->duLieu($ghiDe));
    }

    private function dangNhap(string $vaiTro = TaiKhoan::ADMIN): TaiKhoan
    {
        $taiKhoan = app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Kiểm thử gói', 'email' => strtolower($vaiTro).'@example.test', 'password' => 'Demo123456!'], $vaiTro);
        $this->actingAs($taiKhoan, 'web');

        return $taiKhoan;
    }

    private function duLieuSua(GoiTap $goi, array $ghiDe = []): array
    {
        return [...array_intersect_key($goi->toArray(), array_flip(array_diff(GoiTap::THUOC_TINH, ['trang_thai']))), 'updated_at' => $goi->updated_at->format('Y-m-d H:i:s.u'), ...$ghiDe];
    }

    public function test_toan_bo_admin_endpoint_yeu_cau_session_dung_vai_tro_va_tai_khoan_hoat_dong(): void
    {
        $goi = $this->taoGoi();
        $cacYeuCau = [['GET', '', []], ['GET', '/'.$goi->id, []], ['POST', '', $this->duLieu()], ['PUT', '/'.$goi->id, $this->duLieuSua($goi)], ['PATCH', '/'.$goi->id.'/trang-thai', ['trang_thai' => 'HOAT_DONG', 'updated_at' => $goi->updated_at->format('Y-m-d H:i:s.u')]]];
        foreach ($cacYeuCau as [$phuongThuc, $url, $duLieu]) {
            $this->json($phuongThuc, '/api/v1/admin/goi-tap'.$url, $duLieu)->assertUnauthorized();
        }
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN] as $vaiTro) {
            $this->dangNhap($vaiTro);
            foreach ($cacYeuCau as [$phuongThuc, $url, $duLieu]) {
                $this->json($phuongThuc, '/api/v1/admin/goi-tap'.$url, $duLieu)->assertForbidden();
            }
        }
        $admin = $this->dangNhap();
        $admin->trang_thai = 'NGUNG_SU_DUNG';
        $admin->save();
        foreach ($cacYeuCau as [$phuongThuc, $url, $duLieu]) {
            $this->json($phuongThuc, '/api/v1/admin/goi-tap'.$url, $duLieu)->assertForbidden();
        }
    }

    public function test_admin_tao_hai_loai_goi_va_public_chi_xem_goi_dang_ban(): void
    {
        $this->dangNhap();
        $goiAn = $this->postJson('/api/v1/admin/goi-tap', $this->duLieu())->assertCreated()->assertJsonPath('data.loai_goi', 'CHATBOT')->json('data.id');
        $goiBan = $this->postJson('/api/v1/admin/goi-tap', $this->duLieu(['ten_goi' => 'PT 12 buổi', 'so_buoi_pt' => 12, 'trang_thai' => 'HOAT_DONG']))->assertCreated()->assertJsonPath('data.co_chatbot', true)->assertJsonPath('data.loai_goi', 'PT_CHATBOT')->json('data.id');
        $this->getJson('/api/v1/goi-tap')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $goiBan)->assertJsonMissingPath('data.0.updated_at')->assertJsonMissingPath('data.0.ma_yeu_cau_tao');
        $this->getJson('/api/v1/goi-tap/'.$goiBan)->assertOk()->assertJsonPath('data.gia', 99000)->assertJsonMissingPath('data.trang_thai');
        $this->getJson('/api/v1/goi-tap/'.$goiAn)->assertNotFound();
        $this->getJson('/api/v1/admin/goi-tap/'.$goiAn)->assertOk()->assertJsonPath('data.trang_thai', 'NGUNG_SU_DUNG');
        $this->assertDatabaseCount('dang_ky_goi_tap', 0);
        $this->assertDatabaseCount('thanh_toan', 0);
    }

    public function test_retry_tao_cung_uuid_khong_trung_va_khac_payload_tra_409(): void
    {
        $this->dangNhap();
        $duLieu = $this->duLieu();
        $id = $this->postJson('/api/v1/admin/goi-tap', $duLieu)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/admin/goi-tap', [...$duLieu, 'client_request_id' => strtoupper($duLieu['client_request_id'])])->assertOk()->assertJsonPath('data.id', $id);
        $this->postJson('/api/v1/admin/goi-tap', [...$duLieu, 'gia' => 199000])->assertConflict();
        $this->assertDatabaseCount('goi_tap', 1);
        $this->assertDatabaseHas('goi_tap', ['id' => $id, 'gia' => 99000]);
    }

    public function test_unique_index_chan_uuid_trung_ngay_tai_database(): void
    {
        $goi = $this->taoGoi();
        $this->expectException(UniqueConstraintViolationException::class);
        GoiTap::create(array_diff_key($goi->getAttributes(), ['id' => true]));
    }

    public function test_gia_quota_ngay_chatbot_va_payload_thua_duoc_validate(): void
    {
        $this->dangNhap();
        foreach (['gia' => [-1, 0, '1.5', 'abc', 9007199254740992], 'so_luot_chatbot_moi_ngay' => [0, -1, '1.5', 4294967296], 'thoi_han_ngay' => [0, 36501], 'so_buoi_pt' => [-1, 4294967296], 'co_chatbot' => [false, 'false'], 'ten_goi' => ['', str_repeat('a', 256)], 'client_request_id' => ['khong-phai-uuid'], 'trang_thai' => ['DANG_SU_DUNG']] as $truong => $giaTri) {
            foreach ($giaTri as $giaTriSai) {
                $this->postJson('/api/v1/admin/goi-tap', $this->duLieu([$truong => $giaTriSai]))->assertUnprocessable()->assertJsonValidationErrors($truong);
            }
        }
        $this->postJson('/api/v1/admin/goi-tap', $this->duLieu(['khach_hang_id' => 1, 'kich_hoat_luc' => now()->toIso8601String()]))->assertUnprocessable()->assertJsonValidationErrors(['khach_hang_id', 'kich_hoat_luc']);
        $this->assertDatabaseCount('goi_tap', 0);
    }

    public function test_cap_nhat_gia_quyen_loi_va_phien_ban_cu_khong_ghi_de(): void
    {
        $this->dangNhap();
        $goi = $this->taoGoi();
        $duLieu = $this->duLieuSua($goi, ['gia' => 299000, 'so_buoi_pt' => 8, 'thoi_han_ngay' => 60]);
        $phienBanMoi = $this->putJson('/api/v1/admin/goi-tap/'.$goi->id, $duLieu)->assertOk()->assertJsonPath('data.gia', 299000)->assertJsonPath('data.loai_goi', 'PT_CHATBOT')->json('data.updated_at');
        $this->assertNotSame($duLieu['updated_at'], $phienBanMoi);
        $this->putJson('/api/v1/admin/goi-tap/'.$goi->id, [...$duLieu, 'gia' => 199000])->assertConflict();
        $this->patchJson('/api/v1/admin/goi-tap/'.$goi->id.'/trang-thai', ['trang_thai' => 'HOAT_DONG', 'updated_at' => $duLieu['updated_at']])->assertConflict();
        $this->putJson('/api/v1/admin/goi-tap/'.$goi->id, [...$duLieu, 'updated_at' => $phienBanMoi, 'trang_thai' => 'HOAT_DONG'])->assertUnprocessable()->assertJsonValidationErrors('trang_thai');
        $this->assertDatabaseHas('goi_tap', ['id' => $goi->id, 'gia' => 299000, 'trang_thai' => 'NGUNG_SU_DUNG']);
    }

    public function test_ngung_ban_giu_nguyen_snapshot_dang_ky_va_khong_cap_quyen_moi(): void
    {
        $khach = app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Khách kiểm thử', 'email' => 'snapshot@example.test', 'password' => 'Demo123456!'], TaiKhoan::KHACH_HANG);
        $this->dangNhap();
        $goi = $this->taoGoi(['trang_thai' => 'HOAT_DONG']);
        $dangKyId = DB::table('dang_ky_goi_tap')->insertGetId(['khach_hang_id' => DB::table('ho_so_khach_hang')->where('tai_khoan_id', $khach->id)->value('id'), 'goi_tap_id' => $goi->id, 'client_request_id' => (string) Str::uuid(), 'ma_don_payos' => 12345678, 'ten_goi_snapshot' => $goi->ten_goi, 'gia_snapshot' => $goi->gia, 'co_chatbot_snapshot' => true, 'so_luot_chatbot_moi_ngay_snapshot' => 20, 'so_buoi_pt_snapshot' => 0, 'thoi_han_ngay_snapshot' => 30, 'so_buoi_con_lai' => 0, 'trang_thai' => 'CHO_THANH_TOAN']);
        $truoc = DB::table('dang_ky_goi_tap')->find($dangKyId);
        $phienBan = $this->putJson('/api/v1/admin/goi-tap/'.$goi->id, $this->duLieuSua($goi, ['ten_goi' => 'Tên mới', 'gia' => 500000, 'so_buoi_pt' => 10, 'so_luot_chatbot_moi_ngay' => 50]))->assertOk()->json('data.updated_at');
        $this->patchJson('/api/v1/admin/goi-tap/'.$goi->id.'/trang-thai', ['trang_thai' => 'NGUNG_SU_DUNG', 'updated_at' => $phienBan])->assertOk();
        $this->assertEquals($truoc, DB::table('dang_ky_goi_tap')->find($dangKyId));
        $this->assertDatabaseCount('goi_tap', 1);
        $this->assertDatabaseCount('dang_ky_goi_tap', 1);
        $this->assertDatabaseCount('thanh_toan', 0);
        $this->getJson('/api/v1/goi-tap/'.$goi->id)->assertNotFound();
        $this->getJson('/api/v1/admin/goi-tap/'.$goi->id)->assertOk();
    }

    public function test_goi_cu_pt_khong_chatbot_khong_public_va_khong_mo_ban_duoc(): void
    {
        $goi = GoiTap::create(array_diff_key($this->duLieu(['co_chatbot' => false, 'so_luot_chatbot_moi_ngay' => 0, 'so_buoi_pt' => 10, 'trang_thai' => 'HOAT_DONG']), ['client_request_id' => true]));
        $this->getJson('/api/v1/goi-tap/'.$goi->id)->assertNotFound();
        $this->dangNhap();
        $this->patchJson('/api/v1/admin/goi-tap/'.$goi->id.'/trang-thai', ['trang_thai' => 'HOAT_DONG', 'updated_at' => $goi->updated_at->format('Y-m-d H:i:s.u')])->assertUnprocessable()->assertJsonValidationErrors(['co_chatbot', 'so_luot_chatbot_moi_ngay']);
    }

    public function test_loc_literal_loai_trang_thai_va_phan_trang_on_dinh(): void
    {
        $mot = $this->taoGoi(['ten_goi' => 'Gói 100%_=', 'trang_thai' => 'HOAT_DONG']);
        $hai = $this->taoGoi(['ten_goi' => 'PT', 'so_buoi_pt' => 12, 'trang_thai' => 'HOAT_DONG']);
        $this->taoGoi(['ten_goi' => 'Ẩn', 'so_buoi_pt' => 8]);
        $this->getJson('/api/v1/goi-tap?tu_khoa='.urlencode('%_='))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mot->id);
        $this->getJson('/api/v1/goi-tap?loai_goi=PT_CHATBOT')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $hai->id);
        $this->getJson('/api/v1/goi-tap?per_page=1&page=2')->assertOk()->assertJsonPath('data.0.id', $hai->id)->assertJsonPath('meta.total', 2);
        $this->dangNhap();
        $this->getJson('/api/v1/admin/goi-tap?trang_thai=NGUNG_SU_DUNG')->assertOk()->assertJsonCount(1, 'data');
        foreach (['page=0', 'page=100001', 'per_page=49', 'loai_goi=GYM', 'tu_khoa='.str_repeat('x', 101)] as $query) {
            $this->getJson('/api/v1/goi-tap?'.$query)->assertUnprocessable();
        }
    }

    public function test_id_khong_ton_tai_404_va_khong_co_delete(): void
    {
        $this->getJson('/api/v1/goi-tap/999999')->assertNotFound();
        $this->dangNhap();
        $this->getJson('/api/v1/admin/goi-tap/999999')->assertNotFound();
        $goi = $this->taoGoi();
        $this->deleteJson('/api/v1/admin/goi-tap/'.$goi->id)->assertStatus(405);
        $this->assertDatabaseCount('goi_tap', 1);
    }

    public function test_csrf_bat_buoc_cho_tao_sua_va_doi_trang_thai(): void
    {
        foreach ([PreventRequestForgery::class, ValidateCsrfToken::class] as $lop) {
            $this->app->bind($lop, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
            {
                protected function runningUnitTests(): bool
                {
                    return false;
                }
            });
        }
        $this->dangNhap();
        $goi = $this->taoGoi();
        $header = ['Origin' => 'http://localhost:5173'];
        $this->withSession(['_token' => 'kiem-thu'])->postJson('/api/v1/admin/goi-tap', $this->duLieu(), $header)->assertStatus(419);
        $this->withSession(['_token' => 'kiem-thu'])->putJson('/api/v1/admin/goi-tap/'.$goi->id, $this->duLieuSua($goi), $header)->assertStatus(419);
        $this->withSession(['_token' => 'kiem-thu'])->patchJson('/api/v1/admin/goi-tap/'.$goi->id.'/trang-thai', ['trang_thai' => 'HOAT_DONG', 'updated_at' => $goi->updated_at->format('Y-m-d H:i:s.u')], $header)->assertStatus(419);
        $this->withSession(['_token' => 'kiem-thu'])->postJson('/api/v1/admin/goi-tap', $this->duLieu(), [...$header, 'X-CSRF-TOKEN' => 'kiem-thu'])->assertCreated();
    }
}
