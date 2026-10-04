<?php

namespace Tests\Feature;

use App\Models\TaiKhoan;
use App\Services\BaoCaoService;
use App\Services\TaiKhoanService;
use App\Services\TongQuanKhachHangService;
use App\Services\TongQuanPtService;
use Carbon\CarbonImmutable;
use Database\Seeders\BaiTapSeeder;
use Database\Seeders\HeThong15NgaySeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class HeThong15NgaySeederTest extends TestCase
{
    private static ?string $tenDatabase = null;

    private static ?PDO $pdoMayChu = null;

    protected function setUp(): void
    {
        parent::setUp();
        $cfg = config('database.connections.mysql');
        if (self::$tenDatabase === null) {
            $cfg['database'] = null;
            $cfg['url'] = null;
            config(['database.connections.may_chu_demo15' => $cfg]);
            self::$pdoMayChu = DB::connection('may_chu_demo15')->getPdo();
            self::$tenDatabase = 'kiem_tra_demo15_'.bin2hex(random_bytes(8));
            self::$pdoMayChu->exec('CREATE DATABASE `'.self::$tenDatabase.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
        config(['database.connections.mysql.database' => self::$tenDatabase, 'database.connections.mysql.url' => null]);
        DB::purge('mysql');
        DB::setDefaultConnection('mysql');
        $this->assertSame(self::$tenDatabase, DB::selectOne('SELECT DATABASE() AS ten')->ten);
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'mysql', '--force' => true]));
        DB::beginTransaction();
        Http::preventStrayRequests();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-04 12:00:00', 'Asia/Ho_Chi_Minh'));
        (new BaiTapSeeder)->run();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
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

    public static function cacMocGio(): array
    {
        return [['2026-10-04 12:00:00'], ['2026-10-04 00:01:00'], ['2026-10-04 23:59:00']];
    }

    #[DataProvider('cacMocGio')]
    public function test_lich_su_15_ngay_co_lien_ket_va_timestamps_hop_le(string $luc): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse($luc, 'Asia/Ho_Chi_Minh'));
        (new HeThong15NgaySeeder)->run();
        $this->assertSame(35, TaiKhoan::count());
        $this->assertSame(30, DB::table('ho_so_khach_hang')->count());
        $this->assertSame(4, DB::table('ho_so_huan_luyen_vien')->count());
        $this->assertSame(29, DB::table('dang_ky_goi_tap')->count());
        $this->assertSame(27, DB::table('thanh_toan')->count());
        $this->assertSame(19, DB::table('phan_cong_huan_luyen_vien')->count());
        $this->assertSame(26, DB::table('ke_hoach_tap')->count());
        foreach (['tai_khoan', 'ho_so_khach_hang', 'goi_tap', 'dang_ky_goi_tap', 'thanh_toan', 'phan_cong_huan_luyen_vien',
            'khung_gio_huan_luyen_vien', 'lich_hen_huan_luyen', 'ke_hoach_tap', 'bai_tap_trong_ke_hoach', 'lich_tap', 'phien_tap',
            'bai_tap_trong_phien', 'hiep_tap', 'ghi_chu_huan_luyen', 'chi_so_co_the', 'hoi_thoai', 'tin_nhan',
            'hoi_thoai_tro_ly', 'yeu_cau_tro_ly', 'tin_nhan_tro_ly', 'tai_lieu_tu_van', 'notifications', 'nhat_ky_he_thong'] as $bang) {
            $this->assertSame(0, DB::table($bang)->where('created_at', '>', CarbonImmutable::now('UTC'))->count(), $bang.' created_at');
            $this->assertSame(0, DB::table($bang)->where('updated_at', '>', CarbonImmutable::now('UTC'))->count(), $bang.' updated_at');
            $this->assertSame(0, DB::table($bang)->whereColumn('updated_at', '<', 'created_at')->count(), $bang.' thứ tự thời gian');
        }
        $this->assertSame(0, DB::table('lich_hen_huan_luyen as h')->join('dang_ky_goi_tap as d', 'd.id', '=', 'h.dang_ky_goi_tap_id')
            ->where(fn ($q) => $q->whereColumn('h.khach_hang_id', '!=', 'd.khach_hang_id')->orWhereColumn('h.ket_thuc_luc', '>', 'd.het_han_luc')
                ->orWhereColumn('h.bat_dau_luc', '<', 'd.kich_hoat_luc'))->count());
        $this->assertSame(0, DB::table('lich_hen_huan_luyen')->whereRaw('TIMESTAMPDIFF(SECOND, created_at, bat_dau_luc) < 14400')->count());
        $this->assertSame(0, DB::table('lich_hen_huan_luyen')->where('trang_thai', 'DA_HUY')->whereRaw('TIMESTAMPDIFF(SECOND, huy_luc, bat_dau_luc) < 7200')->count());
        $this->assertSame(0, DB::table('lich_hen_huan_luyen')->where('trang_thai', 'HOAN_THANH')
            ->where(fn ($q) => $q->whereColumn('tieu_hao_luc', '<', 'ket_thuc_luc')->orWhereColumn('tieu_hao_luc', '>', 'han_xac_nhan_hoan_thanh'))->count());
        $this->assertSame(0, DB::table('lich_hen_huan_luyen as a')->join('lich_hen_huan_luyen as b', 'a.huan_luyen_vien_id', '=', 'b.huan_luyen_vien_id')
            ->whereColumn('a.id', '<', 'b.id')->whereColumn('a.bat_dau_luc', '<', 'b.ket_thuc_luc')->whereColumn('b.bat_dau_luc', '<', 'a.ket_thuc_luc')->count());
        foreach (DB::table('dang_ky_goi_tap')->whereNotNull('kich_hoat_luc')->get() as $don) {
            $daDung = DB::table('lich_hen_huan_luyen')->where('dang_ky_goi_tap_id', $don->id)->where('trang_thai', 'HOAN_THANH')->count();
            $this->assertSame($don->so_buoi_pt_snapshot - $daDung, $don->so_buoi_con_lai);
            $this->assertEquals($don->thoi_han_ngay_snapshot * 86400,
                CarbonImmutable::parse($don->kich_hoat_luc, 'UTC')->diffInSeconds(CarbonImmutable::parse($don->het_han_luc, 'UTC')));
        }
        $hetBuoi = DB::table('dang_ky_goi_tap')->where('so_buoi_pt_snapshot', '>', 0)->where('so_buoi_con_lai', 0)->first();
        $this->assertNotNull($hetBuoi);
        $this->assertSame('DANG_SU_DUNG', $hetBuoi->trang_thai);
        $this->assertTrue((bool) $hetBuoi->co_chatbot_snapshot);
        $this->assertGreaterThan(0, DB::table('lich_hen_huan_luyen')->where('trang_thai', 'VANG_MAT')->count());
        $this->assertSame(0, DB::table('lich_hen_huan_luyen')->where('trang_thai', '!=', 'HOAN_THANH')->whereNotNull('tieu_hao_luc')->count());
        $this->assertSame(0, DB::table('phien_tap as p')->join('lich_tap as l', 'l.id', '=', 'p.lich_tap_id')
            ->where(fn ($q) => $q->whereColumn('p.khach_hang_id', '!=', 'l.khach_hang_id')->orWhere('l.trang_thai', '!=', 'HOAN_THANH'))->count());
        $this->assertSame(0, DB::table('yeu_cau_tro_ly as y')->join('dang_ky_goi_tap as d', 'd.id', '=', 'y.dang_ky_goi_tap_id')
            ->where(fn ($q) => $q->whereColumn('y.created_at', '>=', 'd.het_han_luc')->orWhereColumn('y.created_at', '<', 'd.kich_hoat_luc'))->count());
        $this->assertSame(0, DB::table('tin_nhan_tro_ly as t')->join('yeu_cau_tro_ly as y', 'y.id', '=', 't.yeu_cau_tro_ly_id')
            ->where('t.vai_tro', 'ASSISTANT')->where('y.trang_thai', 'LOI')->count());
        $this->assertGreaterThan(0, DB::table('yeu_cau_tro_ly')->where('trang_thai', 'LOI')->count());
        $this->assertSame(0, DB::table('yeu_cau_tro_ly')->where('provider', '!=', 'demo')->count());
        $this->assertTrue(Hash::check('Demo123456!', TaiKhoan::where('email', 'admin@demo15.example.test')->first()->password));
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', HeThong15NgaySeeder::DAU_MOC)->count());
        Http::assertNothingSent();
    }

    public function test_bao_cao_tien_khop_khoan_thu_va_hoan_tien(): void
    {
        (new HeThong15NgaySeeder)->run();
        $baoCao = app(BaoCaoService::class)->tongHop(['tu_ngay' => '2026-09-20', 'den_ngay' => '2026-10-04']);
        $this->assertCount(15, $baoCao['bieu_do']);
        $this->assertSame(29, $baoCao['trong_ky']['don_moi']);
        $this->assertSame(25, $baoCao['trong_ky']['don_kich_hoat']);
        $this->assertEquals(DB::table('thanh_toan')->sum('so_tien'), $baoCao['trong_ky']['tien_da_nhan']);
        $this->assertEquals(DB::table('thanh_toan')->sum('so_tien_hoan'), $baoCao['trong_ky']['tien_da_hoan']);
        $this->assertEquals(DB::table('thanh_toan')->where('trang_thai', 'CAN_DOI_SOAT')->sum('so_tien'), $baoCao['trong_ky']['cho_doi_soat']);
        $this->assertGreaterThan(0, $baoCao['trong_ky']['buoi_pt_hoan_thanh']);
        $this->assertEquals($baoCao['trong_ky']['tien_da_nhan'] - $baoCao['trong_ky']['tien_da_hoan'], array_sum(array_column($baoCao['bieu_do'], 'thuc_thu')));
    }

    public function test_seed_lai_giu_du_lieu_da_sua_va_du_lieu_ngoai_demo(): void
    {
        $ngoai = app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Tài khoản ngoài demo', 'email' => 'ngoai@example.test', 'password' => 'Matkhau123!'], TaiKhoan::KHACH_HANG);
        (new HeThong15NgaySeeder)->run();
        $tk = TaiKhoan::where('email', 'kh01@demo15.example.test')->first();
        $tk->forceFill(['ho_ten' => 'Tên đã chỉnh sửa', 'password' => 'DoiMatKhau123!', 'trang_thai' => 'BI_KHOA'])->save();
        DB::table('notifications')->update(['read_at' => CarbonImmutable::now('UTC')]);
        $truoc = $this->snapshot();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 12:00:00', 'Asia/Ho_Chi_Minh'));
        (new HeThong15NgaySeeder)->run();
        $this->assertSame($truoc, $this->snapshot());
        $this->assertTrue(Hash::check('DoiMatKhau123!', $tk->fresh()->password));
        $this->assertSame('Tài khoản ngoài demo', $ngoai->fresh()->ho_ten);
    }

    public function test_loi_giua_luc_ghi_rollback_toan_bo_va_co_the_thu_lai(): void
    {
        $truoc = $this->snapshot();
        $thatBai = false;
        DB::connection()->beforeExecuting(function ($sql) use (&$thatBai) {
            if (! $thatBai && str_starts_with($sql, 'insert into `hiep_tap`')) {
                $thatBai = true;
                throw new RuntimeException('Lỗi ghi giả lập');
            }
        });
        try {
            (new HeThong15NgaySeeder)->run();
            $this->fail('Phải rollback khi lỗi ghi hiệp tập.');
        } catch (RuntimeException $e) {
            $this->assertSame('Lỗi ghi giả lập', $e->getMessage());
        }
        $this->assertTrue($thatBai);
        $this->assertSame($truoc, $this->snapshot());
        (new HeThong15NgaySeeder)->run();
        $this->assertSame(35, TaiKhoan::count());
    }

    public function test_chan_email_trung_thieu_catalog_va_moi_truong_production(): void
    {
        $tk = app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Email đã tồn tại', 'email' => 'kh01@demo15.example.test', 'password' => 'Rieng123456!'], TaiKhoan::KHACH_HANG);
        $truoc = $this->snapshot();
        try {
            (new HeThong15NgaySeeder)->run();
            $this->fail('Phải chặn email trùng.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Email demo15', $e->getMessage());
        }
        $this->assertSame($truoc, $this->snapshot());
        $tk->update(['email' => 'kh01@ngoai.example.test']);
        DB::table('bai_tap')->where('ma_nguon', '0413')->update(['trang_thai' => 'NGUNG_SU_DUNG']);
        $truoc = $this->snapshot();
        try {
            (new HeThong15NgaySeeder)->run();
            $this->fail('Phải chặn bài ngừng hoạt động.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('0413', $e->getMessage());
        }
        $this->assertSame($truoc, $this->snapshot());
        $this->app->instance('env', 'production');
        try {
            (new HeThong15NgaySeeder)->run();
            $this->fail('Phải chặn production.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('local/testing', $e->getMessage());
        } finally {
            $this->app->instance('env', 'testing');
        }
        $this->assertSame($truoc, $this->snapshot());
    }

    public function test_dich_vu_tong_quan_doc_duoc_du_lieu_cua_khach_va_pt(): void
    {
        (new HeThong15NgaySeeder)->run();
        $kh = TaiKhoan::where('email', 'kh01@demo15.example.test')->first();
        $pt = TaiKhoan::where('email', 'pt1@demo15.example.test')->first();
        $tongQuanKh = app(TongQuanKhachHangService::class)->doc($kh, 15);
        $tongQuanPt = app(TongQuanPtService::class)->doc($pt);
        $this->assertNotEmpty($tongQuanKh);
        $this->assertNotEmpty($tongQuanPt);
        $this->assertSame('Nguyễn Minh Quân', $tongQuanKh['pt']['ho_ten']);
        $this->assertSame(12, $tongQuanKh['giao_an']['so_bai']);
        $this->assertGreaterThan(0, $tongQuanKh['tien_do']['so_buoi']);
        $this->assertStringContainsString('Nguyễn Văn Minh', json_encode($tongQuanPt, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        Http::assertNothingSent();
    }

    private function snapshot(): array
    {
        $kq = [];
        foreach (['tai_khoan', 'ho_so_khach_hang', 'ho_so_huan_luyen_vien', 'goi_tap', 'dang_ky_goi_tap', 'thanh_toan',
            'phan_cong_huan_luyen_vien', 'khung_gio_huan_luyen_vien', 'lich_hen_huan_luyen', 'ke_hoach_tap', 'bai_tap_trong_ke_hoach',
            'lich_tap', 'phien_tap', 'bai_tap_trong_phien', 'hiep_tap', 'ghi_chu_huan_luyen', 'chi_so_co_the', 'hoi_thoai', 'tin_nhan',
            'hoi_thoai_tro_ly', 'yeu_cau_tro_ly', 'tin_nhan_tro_ly', 'tai_lieu_tu_van', 'notifications', 'nhat_ky_he_thong'] as $bang) {
            $kq[$bang] = json_encode(DB::table($bang)->orderBy('id')->get(), JSON_THROW_ON_ERROR);
        }

        return $kq;
    }
}
