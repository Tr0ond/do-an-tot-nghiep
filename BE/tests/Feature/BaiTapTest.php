<?php

namespace Tests\Feature;

use App\Models\BaiTap;
use App\Models\NhomCo;
use Database\Seeders\BaiTapSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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
