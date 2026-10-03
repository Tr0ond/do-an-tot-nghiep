<?php

namespace Tests\Feature;

use App\Models\TaiKhoan;
use App\Services\TaiKhoanService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;
use Tests\TestCase;

class ThongBaoTest extends TestCase
{
    private static ?string $tenDatabase = null;

    private static ?PDO $pdoMayChu = null;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        $cauHinh = config('database.connections.mysql');
        if (self::$tenDatabase === null) {
            $cauHinh['database'] = null;
            $cauHinh['url'] = null;
            config(['database.connections.may_chu_thong_bao' => $cauHinh]);
            self::$pdoMayChu = DB::connection('may_chu_thong_bao')->getPdo();
            self::$tenDatabase = 'kiem_tra_thong_bao_'.bin2hex(random_bytes(8));
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
        DB::rollBack(0);
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$tenDatabase && preg_match('/^kiem_tra_thong_bao_[a-f0-9]{16}$/D', self::$tenDatabase)) {
            self::$pdoMayChu->exec('DROP DATABASE `'.self::$tenDatabase.'`');
            self::$tenDatabase = null;
            self::$pdoMayChu = null;
        }
        parent::tearDownAfterClass();
    }

    private function nguoi(string $vaiTro): TaiKhoan
    {
        return app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Demo Chat', 'email' => Str::uuid().'@example.test', 'password' => 'Demo123456!'], $vaiTro);
    }

    private function tin(TaiKhoan $nguoi, array $duLieu = [], bool $daDoc = false): string
    {
        $id = (string) Str::uuid();
        $nguoi->notifications()->create(['id' => $id, 'type' => 'kiem_tra', 'data' => ['tieu_de' => 'Thông báo riêng', 'noi_dung' => 'Nội dung kiểm thử', ...$duLieu], 'read_at' => $daDoc ? now() : null]);

        return $id;
    }

    private function dangNhap(TaiKhoan $nguoi): void
    {
        Auth::forgetGuards();
        $this->actingAs($nguoi, 'web');
    }

    public function test_guest_va_tai_khoan_khoa_khong_doc_duoc(): void
    {
        $this->getJson('/api/v1/thong-bao')->assertUnauthorized();
        $this->postJson('/api/v1/thong-bao/da-doc-tat-ca')->assertUnauthorized();
        $nguoi = $this->nguoi(TaiKhoan::KHACH_HANG);
        $nguoi->forceFill(['trang_thai' => TaiKhoan::BI_KHOA])->save();
        $this->dangNhap($nguoi);
        $this->getJson('/api/v1/thong-bao')->assertForbidden();
        $this->postJson('/api/v1/thong-bao/da-doc-tat-ca')->assertForbidden();
    }

    public function test_ca_ba_vai_tro_chi_doc_va_danh_dau_thong_bao_cua_minh(): void
    {
        $nguoiKhac = $this->nguoi(TaiKhoan::KHACH_HANG);
        $idKhac = $this->tin($nguoiKhac);
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN, TaiKhoan::ADMIN] as $vaiTro) {
            $nguoi = $this->nguoi($vaiTro);
            $id = $this->tin($nguoi);
            $this->dangNhap($nguoi);
            $this->getJson('/api/v1/thong-bao')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $id)->assertJsonPath('meta.so_chua_doc', 1);
            $this->postJson('/api/v1/thong-bao/'.$idKhac.'/da-doc')->assertNotFound();
            $this->postJson('/api/v1/thong-bao/'.$id.'/da-doc')->assertOk();
            $thoiDiem = DB::table('notifications')->where('id', $id)->value('read_at');
            $this->travel(1)->minutes();
            $this->postJson('/api/v1/thong-bao/'.$id.'/da-doc')->assertOk();
            $this->assertSame($thoiDiem, DB::table('notifications')->where('id', $id)->value('read_at'));
            $this->getJson('/api/v1/thong-bao')->assertJsonPath('meta.so_chua_doc', 0);
        }
        $this->assertNull(DB::table('notifications')->where('id', $idKhac)->value('read_at'));
    }

    public function test_tong_chua_doc_khong_bi_gioi_han_trang_va_doc_tat_ca_khong_anh_huong_nguoi_khac(): void
    {
        $nguoi = $this->nguoi(TaiKhoan::HUAN_LUYEN_VIEN);
        $khac = $this->nguoi(TaiKhoan::ADMIN);
        $idKhac = $this->tin($khac);
        for ($i = 0; $i < 25; $i++) {
            $this->tin($nguoi, daDoc: $i < 2);
        }
        $this->dangNhap($nguoi);
        $this->getJson('/api/v1/thong-bao')->assertJsonCount(20, 'data')->assertJsonPath('meta.so_chua_doc', 23)->assertJsonPath('meta.last_page', 2);
        $this->getJson('/api/v1/thong-bao?page=2')->assertJsonCount(5, 'data');
        $this->getJson('/api/v1/thong-bao?page=-1')->assertUnprocessable();
        $this->postJson('/api/v1/thong-bao/da-doc-tat-ca')->assertOk();
        $this->postJson('/api/v1/thong-bao/da-doc-tat-ca')->assertOk();
        $this->getJson('/api/v1/thong-bao')->assertJsonPath('meta.so_chua_doc', 0);
        $this->assertNull(DB::table('notifications')->where('id', $idKhac)->value('read_at'));
        $this->tin($nguoi);
        $this->getJson('/api/v1/thong-bao')->assertJsonPath('meta.so_chua_doc', 1);
    }

    public function test_payload_chi_co_du_lieu_hien_thi_va_chan_lien_ket_ngoai(): void
    {
        $nguoi = $this->nguoi(TaiKhoan::KHACH_HANG);
        $this->dangNhap($nguoi);
        foreach (['javascript:alert(1)', '//example.test', '/\\example.test', "/\nexample.test"] as $url) {
            $id = $this->tin($nguoi, ['duong_dan' => $url, 'bi_mat' => 'Không gửi ra API']);
            $response = $this->getJson('/api/v1/thong-bao')->assertOk();
            $tin = collect($response->json('data'))->firstWhere('id', $id);
            $this->assertNull($tin['duong_dan']);
            $this->assertArrayNotHasKey('bi_mat', $tin);
        }
        $id = $this->tin($nguoi, ['duong_dan' => '/khach-hang/lich-hen']);
        $tin = collect($this->getJson('/api/v1/thong-bao')->json('data'))->firstWhere('id', $id);
        $this->assertSame('/khach-hang/lich-hen', $tin['duong_dan']);
    }
}
