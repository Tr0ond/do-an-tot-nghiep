<?php

namespace Tests\Feature;

use App\Models\BaiTap;
use App\Models\NhomCo;
use App\Models\TaiKhoan;
use App\Services\GiaoAnMauService;
use App\Services\NhomCoService;
use App\Services\TaiKhoanService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PDO;
use RuntimeException;
use Tests\TestCase;

class NhomCoTest extends TestCase
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
            config(['database.connections.may_chu_nhom_co' => $cauHinh]);
            self::$pdoMayChu = DB::connection('may_chu_nhom_co')->getPdo();
            $tenMoi = 'kiem_tra_nhom_co_'.bin2hex(random_bytes(8));
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
        Event::forget('eloquent.updated: '.NhomCo::class);
        if (DB::connection()->transactionLevel() > 0) {
            DB::rollBack(0);
        }
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$tenDatabase !== null) {
            // Chỉ dọn database ngẫu nhiên do chính lớp kiểm thử tạo.
            self::$pdoMayChu->exec('DROP DATABASE `'.self::$tenDatabase.'`');
            self::$tenDatabase = null;
            self::$pdoMayChu = null;
        }
        parent::tearDownAfterClass();
    }

    private function dangNhap(string $vaiTro = TaiKhoan::ADMIN): TaiKhoan
    {
        $taiKhoan = app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Kiểm thử nhóm cơ', 'email' => strtolower($vaiTro).'@example.test', 'password' => 'Demo123456!'], $vaiTro);
        $this->doiTaiKhoan($taiKhoan);

        return $taiKhoan;
    }

    private function doiTaiKhoan(TaiKhoan $taiKhoan): void
    {
        Auth::forgetGuards();
        $this->actingAs($taiKhoan, 'web');
    }

    private function taoNhom(string $ma = 'chest', array $ghiDe = []): NhomCo
    {
        return NhomCo::create(['ma_nhom_co' => $ma, 'ten_nhom_co' => 'Ngực', 'ten_nguon' => $ma, 'trang_thai' => 'HOAT_DONG', ...$ghiDe]);
    }

    private function phienBan(NhomCo $nhom): ?string
    {
        return $nhom->fresh()->updated_at?->format('Y-m-d H:i:s.u');
    }

    private function doiTrangThai(NhomCo $nhom, string $trangThai)
    {
        return $this->patchJson('/api/v1/admin/nhom-co/'.$nhom->id.'/trang-thai', ['trang_thai' => $trangThai, 'updated_at' => $this->phienBan($nhom)]);
    }

    public function test_tat_ca_endpoint_yeu_cau_admin_dang_hoat_dong(): void
    {
        $nhom = $this->taoNhom();
        $cacYeuCau = [['GET', '', []], ['GET', '/'.$nhom->id, []], ['POST', '', ['ma_nhom_co' => 'abs', 'ten_nhom_co' => 'Bụng']], ['PUT', '/'.$nhom->id, ['ten_nhom_co' => 'Ngực mới', 'updated_at' => $this->phienBan($nhom)]], ['PATCH', '/'.$nhom->id.'/trang-thai', ['trang_thai' => 'NGUNG_SU_DUNG', 'updated_at' => $this->phienBan($nhom)]]];
        foreach ($cacYeuCau as [$phuongThuc, $url, $duLieu]) {
            $this->json($phuongThuc, '/api/v1/admin/nhom-co'.$url, $duLieu)->assertUnauthorized();
        }
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN] as $vaiTro) {
            $this->dangNhap($vaiTro);
            foreach ($cacYeuCau as [$phuongThuc, $url, $duLieu]) {
                $this->json($phuongThuc, '/api/v1/admin/nhom-co'.$url, $duLieu)->assertForbidden();
            }
        }
        $admin = $this->dangNhap();
        $admin->trang_thai = 'BI_KHOA';
        $admin->save();
        foreach ($cacYeuCau as [$phuongThuc, $url, $duLieu]) {
            $this->json($phuongThuc, '/api/v1/admin/nhom-co'.$url, $duLieu)->assertForbidden();
        }
        $this->assertDatabaseCount('nhom_co', 1);
    }

    public function test_tao_chuan_hoa_ma_chan_trung_va_validation(): void
    {
        $this->dangNhap();
        $duLieu = ['ma_nhom_co' => ' CORE_01 ', 'ten_nhom_co' => 'Cơ trung tâm'];
        $this->postJson('/api/v1/admin/nhom-co', $duLieu)->assertCreated()->assertJsonPath('data.ma_nhom_co', 'core_01')->assertJsonPath('data.ten_nguon', 'Cơ trung tâm')->assertJsonPath('data.trang_thai', 'HOAT_DONG')->assertJsonPath('data.so_bai_tap', 0);
        $this->postJson('/api/v1/admin/nhom-co', $duLieu)->assertUnprocessable()->assertJsonValidationErrors('ma_nhom_co');
        try {
            app(NhomCoService::class)->taoNhomCo(['ma_nhom_co' => 'core_01', 'ten_nhom_co' => 'Khác']);
            $this->fail('UNIQUE tại DB phải chặn mã trùng.');
        } catch (ValidationException $loi) {
            $this->assertArrayHasKey('ma_nhom_co', $loi->errors());
        }
        foreach (['', '123abc', 'cơ_ngực', 'a-b', str_repeat('a', 65)] as $ma) {
            $this->postJson('/api/v1/admin/nhom-co', ['ma_nhom_co' => $ma, 'ten_nhom_co' => 'Tên'])->assertUnprocessable()->assertJsonValidationErrors('ma_nhom_co');
        }
        foreach (['', str_repeat('a', 256)] as $ten) {
            $this->postJson('/api/v1/admin/nhom-co', ['ma_nhom_co' => 'ten_hop_le', 'ten_nhom_co' => $ten])->assertUnprocessable()->assertJsonValidationErrors('ten_nhom_co');
        }
        foreach (['id', 'ten_nguon', 'trang_thai', 'updated_at', 'so_bai_tap'] as $truong) {
            $this->postJson('/api/v1/admin/nhom-co', [...$duLieu, 'ma_nhom_co' => 'ma_khac', $truong => 'Giả'])->assertUnprocessable()->assertJsonValidationErrors($truong);
        }
        $this->assertDatabaseCount('nhom_co', 1);
    }

    public function test_sua_giu_ma_nguon_noop_phien_ban_va_chan_ban_cu(): void
    {
        $this->dangNhap();
        $this->freezeTime();
        $nhom = $this->taoNhom();
        $cu = $this->phienBan($nhom);
        $url = '/api/v1/admin/nhom-co/'.$nhom->id;
        $this->putJson($url, ['ten_nhom_co' => 'Ngực', 'updated_at' => $cu])->assertOk()->assertJsonPath('data.updated_at', $cu);
        $moi = $this->putJson($url, ['ten_nhom_co' => 'Cơ ngực mới', 'updated_at' => $cu])->assertOk()->assertJsonPath('data.ma_nhom_co', 'chest')->assertJsonPath('data.ten_nguon', 'chest')->json('data.updated_at');
        $this->assertNotSame($cu, $moi);
        $this->putJson($url, ['ten_nhom_co' => 'Ghi đè', 'updated_at' => $cu])->assertConflict();
        $this->patchJson($url.'/trang-thai', ['trang_thai' => 'NGUNG_SU_DUNG', 'updated_at' => $cu])->assertConflict();
        $this->patchJson($url.'/trang-thai', ['trang_thai' => 'HOAT_DONG', 'updated_at' => $moi])->assertOk()->assertJsonPath('data.updated_at', $moi);
        foreach (['ma_nhom_co', 'ten_nguon', 'trang_thai'] as $truong) {
            $this->putJson($url, ['ten_nhom_co' => 'Sai', 'updated_at' => $moi, $truong => 'sai'])->assertUnprocessable()->assertJsonValidationErrors($truong);
        }
        $this->putJson($url, ['ten_nhom_co' => 'Thiếu phiên bản'])->assertUnprocessable()->assertJsonValidationErrors('updated_at');
        $this->putJson($url, ['ten_nhom_co' => 'Sai', 'updated_at' => 'sai'])->assertUnprocessable();
        $this->patchJson($url.'/trang-thai', ['trang_thai' => 'XOA', 'updated_at' => $moi])->assertUnprocessable();
        $this->assertDatabaseHas('nhom_co', ['id' => $nhom->id, 'ten_nhom_co' => 'Cơ ngực mới', 'trang_thai' => 'HOAT_DONG']);
        $this->deleteJson($url)->assertStatus(405);
        $this->getJson('/api/v1/admin/nhom-co/999999')->assertNotFound();
        $this->putJson('/api/v1/admin/nhom-co/999999', ['ten_nhom_co' => 'Tên', 'updated_at' => $moi])->assertNotFound();
        $this->patchJson('/api/v1/admin/nhom-co/999999/trang-thai', ['trang_thai' => 'HOAT_DONG', 'updated_at' => $moi])->assertNotFound();
    }

    public function test_record_cu_phien_ban_null_chi_duoc_sua_khi_db_con_null(): void
    {
        $this->dangNhap();
        $id = DB::table('nhom_co')->insertGetId(['ma_nhom_co' => 'cu', 'ten_nhom_co' => 'Cũ', 'ten_nguon' => 'old', 'trang_thai' => 'HOAT_DONG']);
        $url = '/api/v1/admin/nhom-co/'.$id;
        $this->getJson($url)->assertOk()->assertJsonPath('data.updated_at', null);
        $moi = $this->putJson($url, ['ten_nhom_co' => 'Đã sửa', 'updated_at' => null])->assertOk()->json('data.updated_at');
        $this->assertNotNull($moi);
        $this->putJson($url, ['ten_nhom_co' => 'Ghi đè', 'updated_at' => null])->assertConflict();
    }

    public function test_tim_literal_phan_trang_filter_va_so_bai(): void
    {
        $this->dangNhap();
        $nhom = $this->taoNhom('abs', ['ten_nhom_co' => '100%_ =']);
        $this->taoNhom('chest', ['ten_nhom_co' => 'Ngực']);
        BaiTap::create(['nhom_co_id' => $nhom->id, 'ten_bai_tap' => 'Một', 'trang_thai' => 'HOAT_DONG']);
        BaiTap::create(['nhom_co_id' => $nhom->id, 'ten_bai_tap' => 'Hai', 'trang_thai' => 'NGUNG_SU_DUNG']);
        $this->getJson('/api/v1/admin/nhom-co?tu_khoa='.urlencode('%_ ='))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.so_bai_tap', 2)->assertJsonPath('data.0.so_bai_hoat_dong', 1)->assertJsonPath('data.0.so_bai_hien_thi', 1);
        $this->getJson('/api/v1/admin/nhom-co?tu_khoa=0')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/admin/nhom-co?per_page=1&page=2')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 2);
        $this->doiTrangThai($nhom, 'NGUNG_SU_DUNG')->assertOk()->assertJsonPath('data.so_bai_tap', 2)->assertJsonPath('data.so_bai_hien_thi', 0);
        $this->getJson('/api/v1/admin/nhom-co?trang_thai=NGUNG_SU_DUNG')->assertOk()->assertJsonCount(1, 'data');
        foreach (['page=0', 'page=100001', 'per_page=49', 'trang_thai=SAI', 'tu_khoa='.str_repeat('x', 101)] as $query) {
            $this->getJson('/api/v1/admin/nhom-co?'.$query)->assertUnprocessable();
        }
    }

    public function test_ngung_khoi_phuc_anh_huong_hien_thi_nhung_giu_bai_giao_an_va_snapshot(): void
    {
        $admin = $this->dangNhap();
        $nhom = $this->taoNhom();
        $bai = BaiTap::create(['nhom_co_id' => $nhom->id, 'ten_bai_tap' => 'Chống đẩy', 'trang_thai' => 'HOAT_DONG']);
        BaiTap::create(['nhom_co_id' => $nhom->id, 'ten_bai_tap' => 'Bài ngừng riêng', 'trang_thai' => 'NGUNG_SU_DUNG']);
        $duLieu = ['client_request_id' => (string) Str::uuid(), 'ten_giao_an' => 'Giáo án cũ', 'muc_tieu' => null, 'so_ngay_tap' => 1, 'bai_tap' => [['bai_tap_id' => $bai->id, 'ngay_thu' => 1, 'thu_tu' => 1, 'so_hiep' => 3, 'so_lan_lap' => 12, 'nghi_giay' => 60, 'ghi_chu' => null]]];
        $dichVu = app(GiaoAnMauService::class);
        $giaoAn = $dichVu->taoGiaoAn($duLieu, $admin->id);
        $dichVu->datTrangThai($giaoAn->id, ['trang_thai' => 'DA_DUYET', 'updated_at' => $giaoAn->updated_at->format('Y-m-d H:i:s.u')], $admin->id);
        $kh = app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'KH', 'email' => 'kh@example.test', 'password' => 'Demo123456!'], TaiKhoan::KHACH_HANG);
        $pt = app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'PT', 'email' => 'pt@example.test', 'password' => 'Demo123456!'], TaiKhoan::HUAN_LUYEN_VIEN);
        $khId = $kh->hoSoKhachHang->id;
        $ptId = $pt->hoSoHuanLuyenVien->id;
        $phanCongId = DB::table('phan_cong_huan_luyen_vien')->insertGetId(['khach_hang_id' => $khId, 'huan_luyen_vien_id' => $ptId, 'nguoi_phan_cong_id' => $admin->id, 'bat_dau_luc' => now()]);
        $keHoachId = DB::table('ke_hoach_tap')->insertGetId(['khach_hang_id' => $khId, 'huan_luyen_vien_id' => $ptId, 'phan_cong_id' => $phanCongId, 'giao_an_mau_id' => $giaoAn->id, 'ten_ke_hoach' => 'Lịch sử', 'trang_thai' => 'DANG_AP_DUNG']);
        DB::table('bai_tap_trong_ke_hoach')->insert(['ke_hoach_tap_id' => $keHoachId, 'bai_tap_id' => $bai->id, 'ten_bai_tap_snapshot' => 'Tên cũ', 'ngay_thu' => 1, 'thu_tu' => 1]);
        $cacBang = ['bai_tap', 'giao_an_mau', 'bai_tap_trong_giao_an_mau', 'ke_hoach_tap', 'bai_tap_trong_ke_hoach'];
        $truoc = [];
        foreach ($cacBang as $bang) {
            $truoc[$bang] = DB::table($bang)->orderBy('id')->get()->toArray();
        }
        $this->doiTrangThai($nhom, 'NGUNG_SU_DUNG')->assertOk();
        $this->getJson('/api/v1/bai-tap')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/bai-tap/'.$bai->id)->assertNotFound();
        $this->getJson('/api/v1/bai-tap/bo-loc')->assertOk()->assertJsonCount(0, 'data.nhom_co');
        $this->getJson('/api/v1/admin/bai-tap?nhom_co_id='.$nhom->id)->assertOk()->assertJsonCount(2, 'data');
        $this->postJson('/api/v1/admin/bai-tap', ['ma_nguon' => 'A001', 'ten_bai_tap' => 'Mới', 'ten_tieng_viet' => null, 'nhom_co_id' => $nhom->id, 'dung_cu' => null, 'huong_dan_vi' => null, 'cac_buoc_vi' => [], 'trang_thai' => 'HOAT_DONG'])->assertUnprocessable()->assertJsonValidationErrors('nhom_co_id');
        $this->postJson('/api/v1/admin/giao-an-mau', [...$duLieu, 'client_request_id' => (string) Str::uuid()])->assertUnprocessable();
        $this->patchJson('/api/v1/admin/giao-an-mau/'.$giaoAn->id.'/trang-thai', ['trang_thai' => 'DA_DUYET', 'updated_at' => $giaoAn->fresh()->updated_at->format('Y-m-d H:i:s.u')])->assertUnprocessable();
        $this->doiTaiKhoan($pt);
        $this->getJson('/api/v1/pt/giao-an-mau/'.$giaoAn->id)->assertOk()->assertJsonPath('data.bai_tap.0.kha_dung', false);
        $this->doiTaiKhoan($admin);
        $this->doiTrangThai($nhom, 'HOAT_DONG')->assertOk()->assertJsonPath('data.so_bai_hien_thi', 1);
        $this->getJson('/api/v1/bai-tap')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/bai-tap/'.$bai->id)->assertOk();
        foreach ($cacBang as $bang) {
            $this->assertEquals($truoc[$bang], DB::table($bang)->orderBy('id')->get()->toArray());
        }
    }

    public function test_ghi_yeu_cau_csrf_thuc(): void
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
        $nhom = $this->taoNhom();
        $cacYeuCau = [['POST', '', ['ma_nhom_co' => 'abs', 'ten_nhom_co' => 'Bụng']], ['PUT', '/'.$nhom->id, ['ten_nhom_co' => 'Mới', 'updated_at' => $this->phienBan($nhom)]], ['PATCH', '/'.$nhom->id.'/trang-thai', ['trang_thai' => 'NGUNG_SU_DUNG', 'updated_at' => $this->phienBan($nhom)]]];
        foreach ($cacYeuCau as [$phuongThuc, $url, $duLieu]) {
            $this->withSession(['_token' => 'kiem-thu'])->json($phuongThuc, '/api/v1/admin/nhom-co'.$url, $duLieu, ['Origin' => 'http://localhost:5173'])->assertStatus(419);
        }
        $this->withSession(['_token' => 'kiem-thu'])->postJson('/api/v1/admin/nhom-co', ['ma_nhom_co' => 'abs', 'ten_nhom_co' => 'Bụng'], ['Origin' => 'http://localhost:5173', 'X-CSRF-TOKEN' => 'kiem-thu'])->assertCreated();
        $this->assertDatabaseHas('nhom_co', ['id' => $nhom->id, 'ten_nhom_co' => 'Ngực', 'trang_thai' => 'HOAT_DONG']);
    }

    public function test_loi_sau_khi_ghi_rollback_nhom(): void
    {
        $nhom = $this->taoNhom();
        $truoc = DB::table('nhom_co')->find($nhom->id);
        Event::listen('eloquent.updated: '.NhomCo::class, function () {
            throw new RuntimeException('Lỗi ghi kiểm thử');
        });
        try {
            app(NhomCoService::class)->datTrangThai($nhom->id, ['trang_thai' => 'NGUNG_SU_DUNG', 'updated_at' => $this->phienBan($nhom)]);
            $this->fail('Lỗi sau khi ghi phải rollback.');
        } catch (RuntimeException $loi) {
            $this->assertSame('Lỗi ghi kiểm thử', $loi->getMessage());
        }
        $this->assertEquals($truoc, DB::table('nhom_co')->find($nhom->id));
    }
}
