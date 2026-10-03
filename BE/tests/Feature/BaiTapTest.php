<?php

namespace Tests\Feature;

use App\Models\BaiTap;
use App\Models\NhomCo;
use App\Models\TaiKhoan;
use App\Services\BaiTapService;
use App\Services\TaiKhoanService;
use Database\Seeders\BaiTapSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PDO;
use Tests\TestCase;

class BaiTapTest extends TestCase
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
            $cauHinh['name'] = 'may_chu_catalog';
            config(['database.connections.may_chu_catalog' => $cauHinh]);
            self::$pdoMayChu = DB::connection('may_chu_catalog')->getPdo();
            $tenMoi = 'kiem_tra_bai_tap_'.bin2hex(random_bytes(8));
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
            // Chỉ dọn database ngẫu nhiên do chính lớp kiểm thử tạo.
            self::$pdoMayChu->exec('DROP DATABASE `'.self::$tenDatabase.'`');
            self::$tenDatabase = null;
            self::$pdoMayChu = null;
        }
        parent::tearDownAfterClass();
    }

    private function taoNhom(string $ma, string $trangThai = 'HOAT_DONG'): NhomCo
    {
        return NhomCo::create(['ma_nhom_co' => $ma, 'ten_nhom_co' => 'Cơ '.$ma, 'ten_nguon' => $ma, 'trang_thai' => $trangThai]);
    }

    private function taoBai(NhomCo $nhom, array $duLieu = []): BaiTap
    {
        return BaiTap::create([
            'nhom_co_id' => $nhom->id, 'ten_bai_tap' => 'Sit-up', 'dung_cu' => 'Trọng lượng cơ thể',
            'dung_cu_nguon' => 'body weight', 'trang_thai' => 'HOAT_DONG',
            'anh_url' => '/media/bai-tap/images/0001-2gPfomN.jpg',
            'gif_url' => '/media/bai-tap/animations/0001-2gPfomN.gif',
            'huong_dan' => ['en' => 'English instructions'], 'cac_buoc' => ['en' => ['Step one', 'Step two']],
            'ghi_cong_media' => '© Gym visual — https://gymvisual.com/', ...$duLieu,
        ]);
    }

    private function dangNhapAdmin(string $vaiTro = TaiKhoan::ADMIN): TaiKhoan
    {
        $taiKhoan = app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Quản trị kiểm thử', 'email' => strtolower($vaiTro).'@example.test', 'password' => 'Demo123456!'], $vaiTro);
        $this->actingAs($taiKhoan, 'web');

        return $taiKhoan;
    }

    private function duLieuAdmin(NhomCo $nhom): array
    {
        return ['ma_nguon' => 'A001', 'ten_bai_tap' => 'Bài do Admin tạo', 'ten_tieng_viet' => null, 'nhom_co_id' => $nhom->id, 'dung_cu' => 'Thảm tập', 'huong_dan_vi' => 'Hướng dẫn tiếng Việt', 'cac_buoc_vi' => ['Bước một', 'Bước hai'], 'trang_thai' => 'HOAT_DONG'];
    }

    private function duLieuSua(BaiTap $baiTap): array
    {
        return ['nhom_co_id' => $baiTap->nhom_co_id, 'ten_tieng_viet' => 'Gập bụng', 'dung_cu' => 'Thảm', 'huong_dan_vi' => 'Hướng dẫn Việt', 'cac_buoc_vi' => ['Bước Việt'], 'updated_at' => $baiTap->updated_at->format('Y-m-d H:i:s.u')];
    }

    public function test_admin_bai_tap_yeu_cau_session_va_dung_vai_tro(): void
    {
        $nhom = $this->taoNhom('abs');
        $bai = $this->taoBai($nhom);
        $cacYeuCau = [['GET', '/bai-tap', []], ['GET', '/bai-tap/bo-loc', []], ['GET', '/bai-tap/'.$bai->id, []], ['POST', '/bai-tap', $this->duLieuAdmin($nhom)], ['PUT', '/bai-tap/'.$bai->id, $this->duLieuSua($bai)], ['PATCH', '/bai-tap/'.$bai->id.'/trang-thai', ['trang_thai' => 'NGUNG_SU_DUNG', 'updated_at' => $bai->updated_at->format('Y-m-d H:i:s.u')]]];
        foreach ($cacYeuCau as [$phuongThuc, $url, $duLieu]) {
            $this->json($phuongThuc, '/api/v1/admin'.$url, $duLieu)->assertUnauthorized();
        }
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN] as $vaiTro) {
            $this->dangNhapAdmin($vaiTro);
            foreach ($cacYeuCau as [$phuongThuc, $url, $duLieu]) {
                $this->json($phuongThuc, '/api/v1/admin'.$url, $duLieu)->assertForbidden();
            }
        }
        $taiKhoan = $this->dangNhapAdmin();
        $taiKhoan->trang_thai = 'BI_KHOA';
        $taiKhoan->save();
        $this->getJson('/api/v1/admin/bai-tap')->assertForbidden();
        $this->assertDatabaseCount('bai_tap', 1);
    }

    public function test_admin_tao_bai_va_chan_ma_trung_ke_ca_o_database(): void
    {
        $this->dangNhapAdmin();
        $duLieu = $this->duLieuAdmin($this->taoNhom('abs'));
        $this->postJson('/api/v1/admin/bai-tap', [...$duLieu, 'ma_nguon' => ' a001 '])->assertCreated()
            ->assertJsonPath('data.nguon_du_lieu', 'admin')->assertJsonPath('data.ma_nguon', 'A001')
            ->assertJsonPath('data.anh_url', null)->assertJsonPath('data.ghi_cong_media', null)->assertJsonPath('data.cac_buoc_vi.0', 'Bước một');
        $this->postJson('/api/v1/admin/bai-tap', $duLieu)->assertUnprocessable()->assertJsonValidationErrors('ma_nguon');
        try {
            app(BaiTapService::class)->taoBaiTap($duLieu);
            $this->fail('UNIQUE phải chặn mã trùng khi không qua FormRequest.');
        } catch (ValidationException $loi) {
            $this->assertArrayHasKey('ma_nguon', $loi->errors());
        }
        $this->assertDatabaseCount('bai_tap', 1);
        $bai = BaiTap::first();
        $this->getJson('/api/v1/bai-tap/'.$bai->id)->assertOk()->assertJsonPath('data.ngon_ngu_huong_dan', 'vi');
    }

    public function test_admin_bien_tap_giu_media_nguon_va_cac_ngon_ngu_khac(): void
    {
        $this->dangNhapAdmin();
        $bai = $this->taoBai($this->taoNhom('abs'), ['nguon_du_lieu' => 'exercises-dataset', 'ma_nguon' => '0001', 'huong_dan' => ['en' => 'English', 'fr' => 'French'], 'cac_buoc' => ['en' => ['Step'], 'fr' => ['Étape']], 'ma_media_nguon' => 'abc', 'co_phu' => ['quadriceps']]);
        $cu = $bai->getAttributes();
        $this->putJson('/api/v1/admin/bai-tap/'.$bai->id, $this->duLieuSua($bai))->assertOk()->assertJsonPath('data.ten_tieng_viet', 'Gập bụng');
        $moi = $bai->fresh();
        foreach (['ten_bai_tap', 'ma_nguon', 'nguon_du_lieu', 'anh_url', 'gif_url', 'ma_media_nguon', 'ghi_cong_media', 'co_phu'] as $truong) {
            $this->assertSame($cu[$truong], $moi->getAttributes()[$truong]);
        }
        $this->assertSame(['en' => 'English', 'fr' => 'French', 'vi' => 'Hướng dẫn Việt'], $moi->huong_dan);
        $this->assertSame(['en' => ['Step'], 'fr' => ['Étape'], 'vi' => ['Bước Việt']], $moi->cac_buoc);
        $this->putJson('/api/v1/admin/bai-tap/'.$bai->id, [...$this->duLieuSua($moi), 'huong_dan_vi' => null, 'cac_buoc_vi' => []])->assertOk();
        $this->getJson('/api/v1/bai-tap/'.$bai->id)->assertOk()->assertJsonPath('data.ngon_ngu_huong_dan', 'en')->assertJsonPath('data.huong_dan', 'English');
    }

    public function test_admin_validation_va_khong_duoc_sua_truong_nguon(): void
    {
        $this->dangNhapAdmin();
        $duLieu = $this->duLieuAdmin($this->taoNhom('abs'));
        foreach (['ma_nguon' => 'ABCDE', 'nhom_co_id' => 99999, 'ten_bai_tap' => '', 'ten_tieng_viet' => ['sai'], 'huong_dan_vi' => str_repeat('a', 10001), 'cac_buoc_vi' => [''], 'trang_thai' => 'BI_KHOA', 'nguon_du_lieu' => 'exercises-dataset', 'anh_url' => '/x', 'huong_dan' => ['en' => 'hacked']] as $truong => $giaTri) {
            $this->postJson('/api/v1/admin/bai-tap', [...$duLieu, $truong => $giaTri])->assertUnprocessable()->assertJsonValidationErrors($truong === 'cac_buoc_vi' ? 'cac_buoc_vi.0' : $truong);
        }
        $this->assertDatabaseCount('bai_tap', 0);
        $bai = $this->taoBai($this->taoNhom('biceps'), ['nguon_du_lieu' => 'exercises-dataset']);
        foreach (['ten_bai_tap' => 'Đổi tên gốc', 'ma_nguon' => '9999', 'trang_thai' => 'NGUNG_SU_DUNG', 'gif_url' => '/x'] as $truong => $giaTri) {
            $this->putJson('/api/v1/admin/bai-tap/'.$bai->id, [...$this->duLieuSua($bai), $truong => $giaTri])->assertUnprocessable()->assertJsonValidationErrors($truong);
        }
        $this->assertSame('Sit-up', $bai->fresh()->ten_bai_tap);
    }

    public function test_admin_phan_trang_tim_kiem_literal_va_doc_bai_ngung_dung(): void
    {
        $this->dangNhapAdmin();
        $nhom = $this->taoNhom('abs', 'NGUNG_SU_DUNG');
        $an = $this->taoBai($nhom, ['trang_thai' => 'NGUNG_SU_DUNG', 'ten_tieng_viet' => 'Gập bụng 100%_']);
        $this->taoBai($this->taoNhom('biceps'));
        $this->getJson('/api/v1/admin/bai-tap?per_page=1')->assertOk()->assertJsonPath('meta.total', 2)->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.huong_dan_vi');
        foreach (['Gập bụng', '%', '_'] as $tuKhoa) {
            $this->getJson('/api/v1/admin/bai-tap?'.http_build_query(['tu_khoa' => $tuKhoa, 'nhom_co_id' => $nhom->id, 'trang_thai' => 'NGUNG_SU_DUNG']))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $an->id);
        }
        $this->getJson('/api/v1/admin/bai-tap/'.$an->id)->assertOk();
        $this->getJson('/api/v1/admin/bai-tap/bo-loc')->assertOk()->assertJsonCount(2, 'data.nhom_co');
        $this->getJson('/api/v1/admin/bai-tap/999999')->assertNotFound();
        foreach ([['page' => 0], ['per_page' => 49], ['tu_khoa' => ['x']], ['trang_thai' => ['x']], ['nhom_co_id' => 99999]] as $query) {
            $this->getJson('/api/v1/admin/bai-tap?'.http_build_query($query))->assertUnprocessable()->assertJsonValidationErrors(array_key_first($query));
        }
    }

    public function test_admin_phien_ban_cu_tra_409_khong_ghi_de(): void
    {
        $this->dangNhapAdmin();
        $bai = $this->taoBai($this->taoNhom('abs'));
        $cu = $this->duLieuSua($bai);
        $this->putJson('/api/v1/admin/bai-tap/'.$bai->id, $cu)->assertOk();
        $this->putJson('/api/v1/admin/bai-tap/'.$bai->id, [...$cu, 'ten_tieng_viet' => 'Bản cũ'])->assertConflict();
        $this->patchJson('/api/v1/admin/bai-tap/'.$bai->id.'/trang-thai', ['updated_at' => $cu['updated_at'], 'trang_thai' => 'NGUNG_SU_DUNG'])->assertConflict();
        $this->assertSame('Gập bụng', $bai->fresh()->ten_tieng_viet);
        $this->assertSame('HOAT_DONG', $bai->fresh()->trang_thai);
    }

    public function test_admin_ngung_khoi_phuc_hien_thi_giu_tham_chieu_va_khong_co_delete(): void
    {
        $admin = $this->dangNhapAdmin();
        $bai = $this->taoBai($this->taoNhom('abs'));
        $giaoAn = DB::table('giao_an_mau')->insertGetId(['ten_giao_an' => 'Mẫu giữ lịch sử', 'nguoi_tao_id' => $admin->id, 'trang_thai' => 'HOAT_DONG']);
        $thamChieu = DB::table('bai_tap_trong_giao_an_mau')->insertGetId(['giao_an_mau_id' => $giaoAn, 'bai_tap_id' => $bai->id]);
        $url = '/api/v1/admin/bai-tap/'.$bai->id.'/trang-thai';
        $an = $this->patchJson($url, ['trang_thai' => 'NGUNG_SU_DUNG', 'updated_at' => $bai->updated_at->format('Y-m-d H:i:s.u')])->assertOk()->json('data');
        $this->getJson('/api/v1/bai-tap/'.$bai->id)->assertNotFound();
        $this->assertDatabaseHas('bai_tap_trong_giao_an_mau', ['id' => $thamChieu, 'bai_tap_id' => $bai->id]);
        $this->patchJson($url, ['trang_thai' => 'NGUNG_SU_DUNG', 'updated_at' => $an['updated_at']])->assertOk()->assertJsonPath('data.updated_at', $an['updated_at']);
        $this->patchJson($url, ['trang_thai' => 'HOAT_DONG', 'updated_at' => $an['updated_at']])->assertOk();
        $this->getJson('/api/v1/bai-tap/'.$bai->id)->assertOk();
        $this->deleteJson('/api/v1/admin/bai-tap/'.$bai->id)->assertStatus(405);
        $this->assertDatabaseCount('bai_tap', 1);
    }

    public function test_admin_nhom_ngung_dung_va_sua_bai_tao_thu_cong(): void
    {
        $this->dangNhapAdmin();
        $an = $this->taoNhom('inactive', 'NGUNG_SU_DUNG');
        $this->postJson('/api/v1/admin/bai-tap', $this->duLieuAdmin($an))->assertUnprocessable()->assertJsonValidationErrors('nhom_co_id');
        $baiAn = $this->taoBai($an);
        $this->putJson('/api/v1/admin/bai-tap/'.$baiAn->id, $this->duLieuSua($baiAn))->assertOk();
        $hien = $this->taoNhom('abs');
        $bai = app(BaiTapService::class)->taoBaiTap($this->duLieuAdmin($hien));
        $this->putJson('/api/v1/admin/bai-tap/'.$bai->id, [...$this->duLieuSua($bai), 'nhom_co_id' => $an->id])->assertUnprocessable()->assertJsonValidationErrors('nhom_co_id');
        $this->putJson('/api/v1/admin/bai-tap/'.$bai->id, [...$this->duLieuSua($bai), 'ten_bai_tap' => 'Tên đã sửa', 'huong_dan_vi' => '0'])->assertOk()->assertJsonPath('data.huong_dan_vi', '0');
        $this->assertSame('Thảm', $bai->fresh()->dung_cu_nguon);
    }

    public function test_admin_bai_tap_stateful_chan_csrf_thieu_va_sai(): void
    {
        foreach ([PreventRequestForgery::class, ValidateCsrfToken::class] as $lop) {
            $this->app->bind($lop, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
            {
                protected function runningUnitTests()
                {
                    return false;
                }
            });
        }
        $this->dangNhapAdmin();
        $nhom = $this->taoNhom('abs');
        $bai = $this->taoBai($nhom);
        $header = ['Origin' => 'http://localhost:5173'];
        $this->withSession(['_token' => 'kiem-thu'])->postJson('/api/v1/admin/bai-tap', $this->duLieuAdmin($nhom), $header)->assertStatus(419);
        $this->withSession(['_token' => 'kiem-thu'])->putJson('/api/v1/admin/bai-tap/'.$bai->id, $this->duLieuSua($bai), [...$header, 'X-CSRF-TOKEN' => 'sai'])->assertStatus(419);
        $this->withSession(['_token' => 'kiem-thu'])->patchJson('/api/v1/admin/bai-tap/'.$bai->id.'/trang-thai', ['trang_thai' => 'NGUNG_SU_DUNG', 'updated_at' => $bai->updated_at->format('Y-m-d H:i:s.u')], $header)->assertStatus(419);
        $this->withSession(['_token' => 'kiem-thu'])->postJson('/api/v1/admin/bai-tap', $this->duLieuAdmin($nhom), [...$header, 'X-CSRF-TOKEN' => 'kiem-thu'])->assertCreated();
    }

    public function test_seed_du_catalog_giu_json_va_anh_xa_theo_ma_khong_phu_thuoc_id(): void
    {
        DB::table('nhom_co')->insert(['id' => 500, 'ma_nhom_co' => 'abductors', 'ten_nhom_co' => 'Tên đã biên tập', 'ten_nguon' => 'abductors', 'trang_thai' => 'NGUNG_SU_DUNG']);
        $this->seed(BaiTapSeeder::class);
        $this->assertDatabaseCount('nhom_co', 19);
        $this->assertDatabaseCount('bai_tap', 1324);
        $nguon = json_decode(file_get_contents(database_path('data/bai_tap.json')), true, 512, JSON_THROW_ON_ERROR);
        $baiDau = BaiTap::where('ma_nguon', '0001')->firstOrFail();
        $this->assertSame($nguon[0]['huong_dan'], $baiDau->huong_dan);
        $this->assertSame($nguon[0]['cac_buoc'], $baiDau->cac_buoc);
        $this->assertSame($nguon[0]['co_phu'], $baiDau->co_phu);
        $this->assertSame($nguon[0]['nguon_tao_luc'], $baiDau->nguon_tao_luc->format('Y-m-d H:i:s.v'));
        $this->assertSame('abs', $baiDau->nhomCo->ma_nhom_co);
        $this->assertNotSame($nguon[0]['nhom_co_id'], $baiDau->nhom_co_id);
        $this->assertGreaterThan(0, BaiTap::where('nhom_co_id', 500)->count());
        $this->assertDatabaseHas('nhom_co', ['id' => 500, 'ten_nhom_co' => 'Tên đã biên tập', 'trang_thai' => 'NGUNG_SU_DUNG']);
        foreach ($nguon as $bai) {
            $this->assertFileExists(public_path(ltrim($bai['anh_url'], '/')));
            $this->assertFileExists(public_path(ltrim($bai['gif_url'], '/')));
        }
        $baiDau->ten_tieng_viet = 'Tên Việt đã biên tập';
        $baiDau->trang_thai = 'NGUNG_SU_DUNG';
        $baiDau->huong_dan = ['vi' => 'Hướng dẫn đã sửa'];
        $baiDau->save();
        $duLieuCu = $baiDau->fresh()->getAttributes();
        unset($nguon);

        $this->seed(BaiTapSeeder::class);

        $this->assertDatabaseCount('nhom_co', 19);
        $this->assertDatabaseCount('bai_tap', 1324);
        $this->assertSame($duLieuCu, $baiDau->fresh()->getAttributes());
        $this->assertDatabaseCount('tai_khoan', 0);
    }

    public function test_seed_rollback_ca_nhom_va_bai_neu_co_loi_giua_chung(): void
    {
        $soBai = 0;
        BaiTap::creating(function () use (&$soBai) {
            if (++$soBai === 3) {
                throw new \RuntimeException('Lỗi nhập catalog giả lập');
            }
        });
        try {
            $this->seed(BaiTapSeeder::class);
            $this->fail('Phải rollback khi nhập bài tập bị lỗi.');
        } catch (\RuntimeException $loi) {
            $this->assertSame('Lỗi nhập catalog giả lập', $loi->getMessage());
        } finally {
            BaiTap::flushEventListeners();
        }
        $this->assertDatabaseCount('nhom_co', 0);
        $this->assertDatabaseCount('bai_tap', 0);
    }

    public function test_danh_sach_cong_khai_phan_trang_va_khong_lo_json_noi_bo(): void
    {
        $nhom = $this->taoNhom('abs');
        $bai = $this->taoBai($nhom);
        $this->taoBai($nhom, ['ten_bai_tap' => 'Bài thứ hai']);
        $this->getJson('/api/v1/bai-tap?per_page=1')->assertOk()->assertJsonPath('status', true)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $bai->id)
            ->assertJsonPath('data.0.gif_url', $bai->gif_url)
            ->assertJsonPath('meta.total', 2)->assertJsonPath('meta.last_page', 2)
            ->assertJsonMissingPath('data.0.huong_dan')->assertJsonMissingPath('data.0.duong_dan_anh_nguon');
        $this->getJson('/api/v1/bai-tap?per_page=1&page=2')->assertOk()->assertJsonPath('data.0.ten_bai_tap', 'Bài thứ hai');
        $this->getJson('/api/v1/bai-tap?page=99')->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 2);
        $this->get('/api/v1/bai-tap')->assertOk()->assertJsonPath('status', true);
        $this->assertGuest('web');
    }

    public function test_bai_va_nhom_ngung_dung_bi_an_trong_danh_sach_chi_tiet_va_bo_loc(): void
    {
        $hienThi = $this->taoBai($this->taoNhom('abs'));
        $anBai = $this->taoBai($hienThi->nhomCo, ['trang_thai' => 'NGUNG_SU_DUNG', 'dung_cu_nguon' => 'hidden', 'dung_cu' => 'Ẩn']);
        $anNhom = $this->taoBai($this->taoNhom('inactive', 'NGUNG_SU_DUNG'), ['dung_cu_nguon' => 'inactive', 'dung_cu' => 'Ẩn']);
        $this->getJson('/api/v1/bai-tap')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $hienThi->id);
        foreach ([$anBai->id, $anNhom->id, 999999] as $id) {
            $this->getJson('/api/v1/bai-tap/'.$id)->assertNotFound()->assertJsonPath('status', false);
        }
        $this->getJson('/api/v1/bai-tap/bo-loc')->assertOk()->assertJsonCount(1, 'data.nhom_co')
            ->assertJsonPath('data.nhom_co.0.so_bai_tap', 1)->assertJsonCount(1, 'data.dung_cu');
        $this->getJson('/api/v1/bai-tap?nhom_co_id='.$anNhom->nhom_co_id)->assertUnprocessable()->assertJsonValidationErrors('nhom_co_id');
        $this->getJson('/api/v1/bai-tap?dung_cu_nguon=hidden')->assertUnprocessable()->assertJsonValidationErrors('dung_cu_nguon');
    }

    public function test_tim_kiem_ten_viet_ma_nguon_va_ket_hop_bo_loc(): void
    {
        $nhom = $this->taoNhom('abs');
        $bai = $this->taoBai($nhom, ['ten_tieng_viet' => 'Gập bụng', 'ma_nguon' => '0123']);
        $this->taoBai($nhom, ['dung_cu_nguon' => 'dumbbell', 'dung_cu' => 'Tạ đơn']);
        $this->taoBai($this->taoNhom('biceps'));
        foreach (['gập bụng', 'SIT-UP', '0123', '0'] as $tuKhoa) {
            $ketQua = $this->getJson('/api/v1/bai-tap?'.http_build_query(['tu_khoa' => $tuKhoa, 'nhom_co_id' => $nhom->id, 'dung_cu_nguon' => 'body weight']))
                ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $bai->id);
        }
        $this->getJson('/api/v1/bai-tap?tu_khoa=khong-co')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/bai-tap?nhom_co_id='.$nhom->id.'&dung_cu_nguon=dumbbell')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_wildcard_like_va_payload_sql_duoc_tim_nhu_van_ban(): void
    {
        $nhom = $this->taoNhom('abs');
        $this->taoBai($nhom);
        $literal = $this->taoBai($nhom, ['ten_bai_tap' => 'Bài 100%_ =']);
        foreach (['%', '_', '='] as $tuKhoa) {
            $this->getJson('/api/v1/bai-tap?'.http_build_query(['tu_khoa' => $tuKhoa]))
                ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $literal->id);
        }
        $this->getJson('/api/v1/bai-tap?'.http_build_query(['tu_khoa' => "' OR 1=1 --"]))->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_query_sai_tra_422_khong_loi_500_va_gioi_han_phan_trang(): void
    {
        foreach ([['page' => 0], ['page' => 100001], ['per_page' => 49], ['per_page' => -1], ['tu_khoa' => str_repeat('a', 101)], ['nhom_co_id' => 999], ['dung_cu_nguon' => 'sai'], ['dung_cu_nguon' => ['sai']], ['nhom_co_id' => ['sai']], ['tu_khoa' => ['sai']]] as $query) {
            $this->getJson('/api/v1/bai-tap?'.http_build_query($query))->assertUnprocessable()
                ->assertJsonValidationErrors(array_key_first($query))->assertJsonPath('status', false);
        }
    }

    public function test_chi_tiet_fallback_tieng_anh_va_uu_tien_tieng_viet(): void
    {
        $bai = $this->taoBai($this->taoNhom('abs'));
        $this->getJson('/api/v1/bai-tap/'.$bai->id)->assertOk()->assertJsonPath('data.ngon_ngu_huong_dan', 'en')
            ->assertJsonPath('data.huong_dan', 'English instructions')->assertJsonPath('data.cac_buoc.0', 'Step one')
            ->assertJsonPath('data.gif_url', $bai->gif_url)->assertJsonMissingPath('data.duong_dan_gif_nguon');
        $bai->huong_dan = ['vi' => '<script>không chạy</script>', 'en' => 'English'];
        $bai->cac_buoc = ['vi' => ['Bước đã biên tập'], 'en' => ['English']];
        $bai->save();
        $this->getJson('/api/v1/bai-tap/'.$bai->id)->assertOk()->assertJsonPath('data.ngon_ngu_huong_dan', 'vi')
            ->assertJsonPath('data.huong_dan', '<script>không chạy</script>')->assertJsonPath('data.cac_buoc.0', 'Bước đã biên tập');
        $this->getJson('/api/v1/bai-tap/khong-phai-id')->assertNotFound();
    }
}
