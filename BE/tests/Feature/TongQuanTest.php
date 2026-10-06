<?php

namespace Tests\Feature;

use App\Models\BaiTap;
use App\Models\DangKyGoiTap;
use App\Models\GiaoAnMau;
use App\Models\GoiTap;
use App\Models\KeHoachTap;
use App\Models\NhomCo;
use App\Models\TaiKhoan;
use App\Models\ThanhToan;
use App\Services\TaiKhoanService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PDO;
use Tests\TestCase;

class TongQuanTest extends TestCase
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
            config(['database.connections.may_chu_tong_quan' => $cauHinh]);
            self::$pdoMayChu = DB::connection('may_chu_tong_quan')->getPdo();
            $tenMoi = 'kiem_tra_tong_quan_'.bin2hex(random_bytes(8));
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
        CarbonImmutable::setTestNow();
        Carbon::setTestNow();
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

    private function taoTaiKhoan(string $vaiTro): TaiKhoan
    {
        return app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Kiểm thử tổng quan', 'email' => bin2hex(random_bytes(8)).'@example.test', 'password' => 'Demo123456!'], $vaiTro);
    }

    public function test_dashboard_pt_rong_khong_sinh_du_lieu_mau_hoac_hanh_dong(): void
    {
        CarbonImmutable::setTestNow('2026-10-04 18:30:00 UTC');
        Carbon::setTestNow('2026-10-04 18:30:00 UTC');
        Http::preventStrayRequests();
        $this->dangNhap($this->taoTaiKhoan(TaiKhoan::HUAN_LUYEN_VIEN));
        $r = $this->getJson('/api/v1/pt/tong-quan')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.huan_luyen.hom_nay', '2026-10-05')->assertJsonPath('data.huan_luyen.tu_ngay', '2026-10-05')
            ->assertJsonPath('data.huan_luyen.den_ngay', '2026-10-11')->assertJsonPath('data.huan_luyen.so_hoc_vien', 0)
            ->assertJsonPath('data.huan_luyen.so_buoi_hom_nay', 0)->assertJsonPath('data.huan_luyen.tuan.ti_le_hoan_thanh', null)
            ->assertJsonCount(7, 'data.huan_luyen.tuan.theo_ngay')->assertJsonPath('data.huan_luyen.khung_gio.con_trong', 0);
        $this->assertArrayNotHasKey('hanh_trinh', $r->json('data'));
        $this->assertSame(0, DB::table('tin_nhan')->count());
        $this->assertSame(0, DB::table('lich_hen_huan_luyen')->count());
    }

    public function test_dashboard_pt_scope_deadline_cursor_va_tach_buoi_tu_tap(): void
    {
        CarbonImmutable::setTestNow('2026-10-07 03:00:00 UTC');
        Carbon::setTestNow('2026-10-07 03:00:00 UTC');
        $tao = require __DIR__.'/../Support/pt-dashboard-fixture.php';
        $f = $tao();
        Http::preventStrayRequests();
        $this->dangNhap($f['pt']);
        $r = $this->getJson('/api/v1/pt/tong-quan?huan_luyen_vien_id='.$f['pt2']->hoSoHuanLuyenVien->id)->assertOk()
            ->assertJsonPath('data.huan_luyen.so_hoc_vien', 2)->assertJsonPath('data.huan_luyen.so_buoi_hom_nay', 4)
            ->assertJsonPath('data.huan_luyen.can_xu_ly.cho_dat_lich', 1)->assertJsonPath('data.huan_luyen.can_xu_ly.cho_ket_qua', 1)
            ->assertJsonPath('data.huan_luyen.can_xu_ly.nhap_pt', 1)->assertJsonPath('data.huan_luyen.can_xu_ly.hoi_thoai_chua_doc', 2)
            ->assertJsonPath('data.huan_luyen.giao_an.nhap', 2)->assertJsonPath('data.huan_luyen.giao_an.cho_xac_nhan', 1)
            ->assertJsonPath('data.huan_luyen.giao_an.dang_ap_dung', 1)->assertJsonPath('data.huan_luyen.tin_nhan.so_chua_doc', 4)
            ->assertJsonPath('data.huan_luyen.tin_nhan.gan_day.0.noi_dung', 'Đã gửi ảnh')
            ->assertJsonPath('data.huan_luyen.tuan.hoan_thanh', 2)->assertJsonPath('data.huan_luyen.tuan.vang_mat', 1)
            ->assertJsonPath('data.huan_luyen.tuan.da_den', 5)->assertJsonPath('data.huan_luyen.tuan.ti_le_hoan_thanh', 40)
            ->assertJsonPath('data.huan_luyen.khung_gio.tong', 7)->assertJsonPath('data.huan_luyen.khung_gio.da_dat', 2)
            ->assertJsonPath('data.huan_luyen.khung_gio.con_trong', 5);
        $this->assertStringNotContainsString('riêng tư', $r->getContent());
        $this->assertStringNotContainsString('Nháp PT khác', $r->getContent());
        $this->assertSame(2, array_sum(array_column($r->json('data.huan_luyen.tuan.theo_ngay'), 'so_buoi')));
        $this->assertSame('QUA_HAN', $r->json('data.huan_luyen.giao_an.gan_day.1.trang_thai'));
        $this->assertFalse($r->json('data.huan_luyen.giao_an.gan_day.0.co_the_sua'));
        $this->assertSame(1, $r->json('data.huan_luyen.hoc_vien.0.so_buoi_tu_tap'));
        $this->assertNull(DB::table('hoi_thoai')->where('id', $f['cacHoi'][0])->value('cursor_pt_da_doc'));
        $this->assertSame('CHO_XAC_NHAN', $f['cacHen'][6]->fresh()->trang_thai);
        $this->assertSame(6, $f['don']->fresh()->so_buoi_con_lai);
        $this->assertSame($f['cacHen'][5]->id, $r->json('data.huan_luyen.can_xu_ly.lich_dat.id'));
        // Đã đọc tăng cursor, GET không tính tin của PT thành tin chưa đọc.
        $hoi = $f['cacHoi'][0];
        $cuoi = DB::table('tin_nhan')->where('hoi_thoai_id', $hoi)->max('id');
        DB::table('hoi_thoai')->where('id', $hoi)->update(['cursor_pt_da_doc' => $cuoi]);
        DB::table('tin_nhan')->insert(['hoi_thoai_id' => $hoi, 'nguoi_gui_id' => $f['pt']->id, 'client_message_id' => (string) Str::uuid(), 'noi_dung' => 'Tin PT gửi', 'created_at' => now()]);
        $this->getJson('/api/v1/pt/tong-quan')->assertOk()->assertJsonPath('data.huan_luyen.tin_nhan.so_chua_doc', 2)->assertJsonPath('data.huan_luyen.can_xu_ly.hoi_thoai_chua_doc', 1);
    }

    public function test_dashboard_pt_doi_phan_cong_va_khoa_khach_thu_hoi_du_lieu(): void
    {
        CarbonImmutable::setTestNow('2026-10-07 03:00:00 UTC');
        Carbon::setTestNow('2026-10-07 03:00:00 UTC');
        $tao = require __DIR__.'/../Support/pt-dashboard-fixture.php';
        $f = $tao();
        $f['pc']->update(['ket_thuc_luc' => now()]);
        DB::table('tai_khoan')->where('id', $f['kh2']->id)->update(['trang_thai' => TaiKhoan::BI_KHOA]);
        $this->dangNhap($f['pt']);
        $r = $this->getJson('/api/v1/pt/tong-quan')->assertOk()->assertJsonPath('data.huan_luyen.so_hoc_vien', 0)
            ->assertJsonCount(0, 'data.huan_luyen.lich_hom_nay')->assertJsonCount(0, 'data.huan_luyen.giao_an.gan_day')
            ->assertJsonCount(0, 'data.huan_luyen.tin_nhan.gan_day')->assertJsonPath('data.huan_luyen.tin_nhan.so_chua_doc', 0);
        $this->assertStringNotContainsString('Học viên kiểm thử', $r->getContent());
        $this->assertStringNotContainsString('Lan Phương', $r->getContent());
        // KH vẫn chỉ nhận hành trình riêng, không nhận dữ liệu dashboard PT.
        $this->dangNhap($f['kh']);
        $this->assertArrayNotHasKey('huan_luyen', $this->getJson('/api/v1/khach-hang/tong-quan')->assertOk()->json('data'));
    }

    public function test_dashboard_pt_moc_het_han_bang_hien_tai_va_khong_dem_hoan_thanh_sai(): void
    {
        CarbonImmutable::setTestNow('2026-10-07 03:00:00 UTC');
        Carbon::setTestNow('2026-10-07 03:00:00 UTC');
        $tao = require __DIR__.'/../Support/pt-dashboard-fixture.php';
        $f = $tao();
        $f['cacHen'][5]->update(['han_xac_nhan_dat_lich' => now()]);
        $f['cacHen'][3]->update(['bat_dau_luc' => now()->subHours(25), 'ket_thuc_luc' => now()->subHours(24)]);
        $f['cacBan']['Khởi đầu 3 buổi mỗi tuần']->update(['han_duyet' => now()]);
        $f['cacHen'][2]->update(['tieu_hao_luc' => null]);
        $this->dangNhap($f['pt']);
        $this->getJson('/api/v1/pt/tong-quan')->assertOk()
            ->assertJsonPath('data.huan_luyen.can_xu_ly.cho_dat_lich', 0)
            ->assertJsonPath('data.huan_luyen.can_xu_ly.cho_ket_qua', 0)
            ->assertJsonPath('data.huan_luyen.can_xu_ly.lich_dat', null)
            ->assertJsonPath('data.huan_luyen.giao_an.cho_xac_nhan', 0)
            ->assertJsonPath('data.huan_luyen.khung_gio.da_dat', 1)
            ->assertJsonPath('data.huan_luyen.khung_gio.con_trong', 6)
            ->assertJsonPath('data.huan_luyen.tuan.hoan_thanh', 1);
        $f['cacHen'][0]->update(['bat_dau_luc' => now(), 'ket_thuc_luc' => now()->addHour()]);
        $this->getJson('/api/v1/pt/tong-quan')->assertOk()->assertJsonPath('data.huan_luyen.tuan.hoan_thanh', 0);
    }

    public function test_dashboard_pt_gioi_han_the_khong_cat_so_tong(): void
    {
        $pt = $this->taoTaiKhoan(TaiKhoan::HUAN_LUYEN_VIEN);
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        for ($i = 0; $i < 8; $i++) {
            $kh = $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
            DB::table('phan_cong_huan_luyen_vien')->insert(['khach_hang_id' => $kh->hoSoKhachHang->id, 'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'nguoi_phan_cong_id' => $admin->id, 'bat_dau_luc' => now()->subDay()]);
        }
        $this->dangNhap($pt);
        $this->getJson('/api/v1/pt/tong-quan')->assertOk()->assertJsonPath('data.huan_luyen.so_hoc_vien', 8)
            ->assertJsonPath('data.huan_luyen.tong_can_chu_y', 8)->assertJsonCount(3, 'data.huan_luyen.hoc_vien')->assertJsonCount(4, 'data.huan_luyen.can_chu_y');
    }

    public function test_hanh_trinh_kh_rong_validation_va_khong_goi_ai(): void
    {
        CarbonImmutable::setTestNow('2026-10-04 18:30:00 UTC');
        Carbon::setTestNow('2026-10-04 18:30:00 UTC');
        Http::preventStrayRequests();
        $this->dangNhap($this->taoTaiKhoan(TaiKhoan::KHACH_HANG));
        $this->getJson('/api/v1/khach-hang/tong-quan')->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.hanh_trinh.hom_nay', '2026-10-05')
            ->assertJsonPath('data.hanh_trinh.buoi_thang_nay', 0)
            ->assertJsonPath('data.hanh_trinh.ti_le_hoan_thanh', null)
            ->assertJsonPath('data.hanh_trinh.giao_an', null)
            ->assertJsonPath('data.hanh_trinh.buoi_hom_nay', null)
            ->assertJsonPath('data.hanh_trinh.goi', null)
            ->assertJsonPath('data.hanh_trinh.pt', null)
            ->assertJsonPath('data.hanh_trinh.ai.con_lai', 0)
            ->assertJsonPath('data.hanh_trinh.chi_so.moi_nhat', null)
            ->assertJsonCount(30, 'data.hanh_trinh.tien_do.theo_ngay');
        foreach (['0', '8', '91', 'abc', '7.5'] as $sai) {
            $this->getJson('/api/v1/khach-hang/tong-quan?so_ngay='.$sai)->assertUnprocessable();
        }
        $this->getJson('/api/v1/khach-hang/tong-quan?so_ngay[]=7')->assertUnprocessable();
        $this->assertSame(0, DB::table('yeu_cau_tro_ly')->count());
        $this->assertSame(0, DB::table('lich_tap')->count());
    }

    public function test_hanh_trinh_dem_lich_da_den_snapshot_han_muc_va_scope_kh(): void
    {
        CarbonImmutable::setTestNow('2026-10-04 18:30:00 UTC');
        Carbon::setTestNow('2026-10-04 18:30:00 UTC');
        $tao = require __DIR__.'/../Support/hanh-trinh-fixture.php';
        $f = $tao();
        Http::preventStrayRequests();
        $this->dangNhap($f['kh']);
        $r = $this->getJson('/api/v1/khach-hang/tong-quan?so_ngay=7&khach_hang_id='.$f['khac']->hoSoKhachHang->id)->assertOk()
            ->assertJsonPath('data.hanh_trinh.buoi_thang_nay', 2)
            ->assertJsonPath('data.hanh_trinh.lich_da_den_thang_nay', 4)
            ->assertJsonPath('data.hanh_trinh.ti_le_hoan_thanh', 50)
            ->assertJsonPath('data.hanh_trinh.so_lich_sap_toi', 3)
            ->assertJsonCount(3, 'data.hanh_trinh.lich_sap_toi')
            ->assertJsonPath('data.hanh_trinh.lich_sap_toi.2.id', $f['hen'])
            ->assertJsonPath('data.hanh_trinh.buoi_hom_nay.id', $f['lich'][0]->id)
            ->assertJsonPath('data.hanh_trinh.buoi_hom_nay.so_bai', 2)
            ->assertJsonPath('data.hanh_trinh.buoi_hom_nay.so_bai_da_ghi', 1)
            ->assertJsonPath('data.hanh_trinh.giao_an.id', $f['keHoach']->id)
            ->assertJsonPath('data.hanh_trinh.giao_an.lich_tuan', 2)
            ->assertJsonPath('data.hanh_trinh.goi.ten', 'Đồng hành cùng PT')
            ->assertJsonPath('data.hanh_trinh.goi.so_buoi_con_lai', 6)
            ->assertJsonPath('data.hanh_trinh.ai.da_dung', 2)
            ->assertJsonPath('data.hanh_trinh.ai.dang_giu', 1)
            ->assertJsonPath('data.hanh_trinh.ai.con_lai', 7)
            ->assertJsonPath('data.hanh_trinh.tien_do.so_buoi', 2)
            ->assertJsonCount(7, 'data.hanh_trinh.tien_do.theo_ngay')
            ->assertJsonPath('data.hanh_trinh.chi_so.thay_doi_can_nang_kg', -1)
            ->assertJsonPath('data.hanh_trinh.chi_so.thay_doi_bmi', -0.33);
        $this->assertStringNotContainsString('Giáo án riêng tư', $r->getContent());
        $this->assertStringNotContainsString('Nội dung chat riêng tư', $r->getContent());
        $this->assertSame(['ngay_ghi', 'bmi'], array_keys($r->json('data.hanh_trinh.chi_so.cac_moc.0')));
        foreach ([30, 90] as $soNgay) {
            $this->getJson('/api/v1/khach-hang/tong-quan?so_ngay='.$soNgay)->assertOk()
                ->assertJsonCount($soNgay, 'data.hanh_trinh.tien_do.theo_ngay')
                ->assertJsonPath('data.hanh_trinh.tien_do.so_buoi', 3);
        }
        $this->assertSame('DANG_TAP', $f['lich'][0]->fresh()->trang_thai);
        $this->assertSame(4, DB::table('yeu_cau_tro_ly')->count());
        $this->dangNhap($f['khac']);
        $this->getJson('/api/v1/khach-hang/tong-quan')->assertOk()
            ->assertJsonPath('data.hanh_trinh.buoi_thang_nay', 0)
            ->assertJsonPath('data.hanh_trinh.goi', null)
            ->assertJsonPath('data.hanh_trinh.pt', null)
            ->assertJsonPath('data.hanh_trinh.chi_so.moi_nhat', null);
    }

    public function test_tien_do_gop_pt_theo_ngay_viet_nam_scope_trang_thai_va_giu_truong_cu(): void
    {
        CarbonImmutable::setTestNow('2026-10-07 03:00:00 UTC');
        Carbon::setTestNow('2026-10-07 03:00:00 UTC');
        $tao = require __DIR__.'/../Support/hanh-trinh-fixture.php';
        $f = $tao();
        $taoHen = function (string $luc, string $tt = 'HOAN_THANH', ?int $khachId = null) use ($f): int {
            $batDau = CarbonImmutable::parse($luc, 'Asia/Ho_Chi_Minh')->utc();
            $slot = DB::table('khung_gio_huan_luyen_vien')->insertGetId(['huan_luyen_vien_id' => $f['pt']->hoSoHuanLuyenVien->id,
                'bat_dau_luc' => $batDau, 'ket_thuc_luc' => $batDau->addHour(), 'trang_thai' => 'MO']);

            return DB::table('lich_hen_huan_luyen')->insertGetId(['khach_hang_id' => $khachId ?? $f['kh']->hoSoKhachHang->id,
                'huan_luyen_vien_id' => $f['pt']->hoSoHuanLuyenVien->id, 'phan_cong_id' => $f['pc']->id,
                'khung_gio_id' => $slot, 'dang_ky_goi_tap_id' => $f['don']->id, 'client_request_id' => (string) Str::uuid(),
                'bat_dau_luc' => $batDau, 'ket_thuc_luc' => $batDau->addHour(), 'trang_thai' => $tt,
                'tieu_hao_luc' => $tt === 'HOAN_THANH' ? $batDau->addHours(2) : null]);
        };
        $taoHen('2026-10-01 00:00:00');
        $taoHen('2026-10-03 23:30:00'); // Buổi qua nửa đêm vẫn tính ngày bắt đầu.
        $taoHen('2026-10-07 00:15:00');
        $taoHen('2026-09-30 23:59:59'); // Ngoài 7 ngày dù kết thúc trong ngày01/10.
        $taoHen('2026-08-01 08:00:00');
        $taoHen('2026-07-01 08:00:00'); // Ngoài 90 ngày.
        $taoHen('2026-10-07 11:00:00'); // Không cộng bản ghi tương lai, kể cả status sai.
        $taoHen('2026-10-07 07:30:00', 'HOAN_THANH', $f['khac']->hoSoKhachHang->id);
        foreach (['DA_XAC_NHAN', 'VANG_MAT', 'DA_HUY', 'QUA_HAN_XAC_NHAN', 'CHO_XAC_NHAN', 'HET_HAN'] as $i => $tt) {
            $id = $taoHen('2026-10-06 '.sprintf('%02d:00:00', $i + 1), $tt);
            if ($tt === 'DA_XAC_NHAN') {
                DB::table('ket_qua_buoi_pt')->insert(['lich_hen_id' => $id, 'nguoi_ghi_id' => $f['pt']->id,
                    'bai_tap' => '[]', 'chot_luc' => now()]); // Chốt kết quả chưa hoàn thành không cộng.
            }
        }
        $truoc = ['lich' => DB::table('lich_hen_huan_luyen')->count(), 'ket_qua' => DB::table('ket_qua_buoi_pt')->count(),
            'thong_bao' => DB::table('notifications')->count(), 'luot' => $f['don']->fresh()->so_buoi_con_lai];
        $this->dangNhap($f['kh']);
        foreach ([[7, 2, 3], [30, 3, 4], [90, 3, 5]] as [$ngay, $tuTap, $pt]) {
            $r = $this->getJson('/api/v1/khach-hang/tong-quan?so_ngay='.$ngay.'&khach_hang_id='.$f['khac']->hoSoKhachHang->id)->assertOk()
                ->assertHeader('Cache-Control', 'no-store, private')
                ->assertJsonPath('data.hanh_trinh.tien_do.so_buoi', $tuTap)
                ->assertJsonPath('data.hanh_trinh.tien_do.so_buoi_tu_tap', $tuTap)
                ->assertJsonPath('data.hanh_trinh.tien_do.so_buoi_pt', $pt)
                ->assertJsonPath('data.hanh_trinh.tien_do.tong_so_buoi', $tuTap + $pt)
                ->assertJsonPath('data.hanh_trinh.buoi_thang_nay', 2)
                ->assertJsonPath('data.hanh_trinh.ti_le_hoan_thanh', 50)
                ->assertJsonCount($ngay, 'data.hanh_trinh.tien_do.theo_ngay');
            $moc = collect($r->json('data.hanh_trinh.tien_do.theo_ngay'))->keyBy('ngay');
            $this->assertSame(1, $moc['2026-10-01']['so_buoi_pt']);
            $this->assertSame(1, $moc['2026-10-03']['so_buoi_pt']);
            $this->assertSame(1, $moc['2026-10-07']['so_buoi_pt']);
            $this->assertSame(0, $moc['2026-10-06']['so_buoi_pt']);
            $this->assertSame(2, $moc['2026-10-03']['tong_so_buoi']);
            $this->assertSame($tuTap, $moc->sum('so_buoi'));
            $this->assertSame($tuTap + $pt, $moc->sum('tong_so_buoi'));
        }
        $f['pc']->update(['ket_thuc_luc' => now()]);
        $f['don']->update(['het_han_luc' => now()]);
        $this->getJson('/api/v1/khach-hang/tong-quan?so_ngay=7')->assertOk()
            ->assertJsonPath('data.hanh_trinh.tien_do.so_buoi_pt', 3)->assertJsonPath('data.hanh_trinh.goi', null);
        $this->assertSame($truoc, ['lich' => DB::table('lich_hen_huan_luyen')->count(), 'ket_qua' => DB::table('ket_qua_buoi_pt')->count(),
            'thong_bao' => DB::table('notifications')->count(), 'luot' => $f['don']->fresh()->so_buoi_con_lai]);
        $this->dangNhap($f['khac']);
        $this->getJson('/api/v1/khach-hang/tong-quan?so_ngay=7')->assertOk()
            ->assertJsonPath('data.hanh_trinh.tien_do.so_buoi', 0)->assertJsonPath('data.hanh_trinh.tien_do.so_buoi_pt', 1)
            ->assertJsonPath('data.hanh_trinh.tien_do.tong_so_buoi', 1);
    }

    public function test_hanh_trinh_khong_giu_quyen_goi_het_han_hoac_pt_bi_khoa(): void
    {
        $tao = require __DIR__.'/../Support/hanh-trinh-fixture.php';
        $f = $tao();
        $this->dangNhap($f['kh']);
        $f['don']->update(['het_han_luc' => now()]);
        $f['pt']->trang_thai = TaiKhoan::BI_KHOA;
        $f['pt']->save();
        $this->getJson('/api/v1/khach-hang/tong-quan')->assertOk()
            ->assertJsonPath('data.hanh_trinh.goi', null)->assertJsonPath('data.hanh_trinh.ai.co_quyen', false)
            ->assertJsonPath('data.hanh_trinh.pt', null);
        $f['don']->update(['het_han_luc' => now()->addDays(10), 'kich_hoat_luc' => now()->addMinute()]);
        $this->getJson('/api/v1/khach-hang/tong-quan')->assertOk()->assertJsonPath('data.hanh_trinh.goi', null);
        $this->assertSame('DANG_SU_DUNG', $f['don']->fresh()->trang_thai);
        $this->dangNhap($f['admin']);
        $this->getJson('/api/v1/admin/tong-quan')->assertOk()->assertJsonMissingPath('data.hanh_trinh');
    }

    public function test_buoi_dang_tap_giu_snapshot_khi_kh_doi_giao_an(): void
    {
        $tao = require __DIR__.'/../Support/hanh-trinh-fixture.php';
        $f = $tao();
        $f['keHoach']->update(['trang_thai' => 'DA_LUU_TRU']);
        $moi = KeHoachTap::create(['khach_hang_id' => $f['kh']->hoSoKhachHang->id,
            'ten_ke_hoach' => 'Giáo án mới', 'nguon_tao' => 'KHACH_HANG', 'trang_thai' => 'DANG_AP_DUNG',
            'so_ngay_tap' => 1, 'client_request_id' => (string) Str::uuid()]);
        $baiCu = $f['keHoach']->cacBaiTap()->first();
        $moi->cacBaiTap()->create($baiCu->only(['bai_tap_id', 'ngay_thu', 'thu_tu', 'ten_bai_tap_snapshot', 'noi_dung_snapshot', 'so_hiep', 'so_lan_lap', 'nghi_giay']));
        // Lịch chưa bắt đầu có ID nhỏ hơn, nhưng buổi đang tập vẫn phải được ưu tiên.
        $f['lich'][-1]->update(['ngay_tap' => now('Asia/Ho_Chi_Minh')->toDateString(), 'ngay_thu' => 2]);
        $this->dangNhap($f['kh']);
        $this->getJson('/api/v1/khach-hang/tong-quan')->assertOk()
            ->assertJsonPath('data.hanh_trinh.giao_an.id', $moi->id)
            ->assertJsonPath('data.hanh_trinh.giao_an.so_bai', 1)
            ->assertJsonPath('data.hanh_trinh.buoi_hom_nay.id', $f['lich'][0]->id)
            ->assertJsonPath('data.hanh_trinh.buoi_hom_nay.ke_hoach_id', $f['keHoach']->id)
            ->assertJsonPath('data.hanh_trinh.buoi_hom_nay.so_bai', 2)
            ->assertJsonPath('data.hanh_trinh.buoi_hom_nay.so_bai_da_ghi', 1);
    }

    private function dangNhap(TaiKhoan $taiKhoan): void
    {
        Auth::forgetGuards();
        $this->actingAs($taiKhoan, 'web');
    }

    private function taoCatalog(TaiKhoan $admin): void
    {
        $nhom = NhomCo::create(['ma_nhom_co' => 'qa', 'ten_nhom_co' => 'QA', 'ten_nguon' => 'QA', 'trang_thai' => 'HOAT_DONG']);
        $ngung = NhomCo::create(['ma_nhom_co' => 'off', 'ten_nhom_co' => 'Ngừng', 'ten_nguon' => 'off', 'trang_thai' => 'NGUNG_SU_DUNG']);
        NhomCo::create(['ma_nhom_co' => 'empty', 'ten_nhom_co' => 'Rỗng', 'ten_nguon' => 'empty', 'trang_thai' => 'HOAT_DONG']);
        foreach ([[$nhom, 'HOAT_DONG'], [$nhom, 'NGUNG_SU_DUNG'], [$ngung, 'HOAT_DONG']] as [$n, $trangThai]) {
            BaiTap::create(['nhom_co_id' => $n->id, 'ten_bai_tap' => 'Bài thử', 'trang_thai' => $trangThai]);
        }
        foreach ([['HOAT_DONG', 99000], ['NGUNG_SU_DUNG', 99000], ['HOAT_DONG', 0]] as [$trangThai, $gia]) {
            GoiTap::create(['ten_goi' => 'Gói thử', 'gia' => $gia, 'co_chatbot' => true, 'so_luot_chatbot_moi_ngay' => 10, 'so_buoi_pt' => 0, 'thoi_han_ngay' => 30, 'trang_thai' => $trangThai]);
        }
        foreach (['NHAP', 'DA_DUYET', 'NGUNG_SU_DUNG'] as $trangThai) {
            GiaoAnMau::create(['ten_giao_an' => 'Giáo án thử', 'muc_tieu' => 'QA', 'so_ngay_tap' => 1, 'nguoi_tao_id' => $admin->id, 'trang_thai' => $trangThai]);
        }
    }

    public function test_dashboard_yeu_cau_dung_vai_tro_va_tai_khoan_hoat_dong(): void
    {
        $cacDuongDan = ['KHACH_HANG' => '/api/v1/khach-hang/tong-quan', 'HUAN_LUYEN_VIEN' => '/api/v1/pt/tong-quan', 'ADMIN' => '/api/v1/admin/tong-quan'];
        foreach ($cacDuongDan as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
        foreach ($cacDuongDan as $vaiTro => $urlDung) {
            $taiKhoan = $this->taoTaiKhoan($vaiTro);
            foreach ($cacDuongDan as $url) {
                $this->dangNhap($taiKhoan);
                $response = $this->getJson($url);
                $url === $urlDung ? $response->assertOk() : $response->assertForbidden();
            }
            $taiKhoan->trang_thai = TaiKhoan::BI_KHOA;
            $taiKhoan->save();
            $this->dangNhap($taiKhoan);
            $this->getJson($urlDung)->assertForbidden();
        }
    }

    public function test_admin_thong_ke_toan_database_va_cac_trang_thai(): void
    {
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
        $pt = $this->taoTaiKhoan(TaiKhoan::HUAN_LUYEN_VIEN);
        $pt->trang_thai = TaiKhoan::BI_KHOA;
        $pt->save();
        $this->taoCatalog($admin);
        $this->dangNhap($admin);
        $this->getJson('/api/v1/admin/tong-quan')
            ->assertOk()->assertJsonPath('data.quan_tri.tai_khoan', ['tong' => 3, 'hoat_dong' => 2, 'bi_khoa' => 1, 'khach_hang' => 1, 'huan_luyen_vien' => 1, 'admin' => 1])
            ->assertJsonPath('data.quan_tri.bai_tap', ['tong' => 3, 'hien_thi' => 1])
            ->assertJsonPath('data.quan_tri.nhom_co', ['tong' => 3, 'hoat_dong' => 2])
            ->assertJsonPath('data.quan_tri.goi_tap', ['tong' => 3, 'dang_ban' => 1])
            ->assertJsonPath('data.quan_tri.giao_an_mau', ['tong' => 3, 'da_duyet' => 1, 'ban_nhap' => 1, 'ngung_su_dung' => 1])
            ->assertJsonMissingPath('data.email')->assertJsonMissingPath('data.password');
    }

    public function test_khach_chi_doc_ho_so_chinh_minh_va_catalog_cong_khai(): void
    {
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $this->taoCatalog($admin);
        $khach = $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
        $khac = $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
        $khac->hoSoKhachHang()->update(['muc_tieu' => 'Không thuộc tài khoản hiện tại']);
        $this->dangNhap($khach);
        $this->getJson('/api/v1/khach-hang/tong-quan?vai_tro=ADMIN&tai_khoan_id='.$khac->id)
            ->assertOk()->assertJsonPath('data.vai_tro', TaiKhoan::KHACH_HANG)
            ->assertJsonPath('data.thu_vien', ['bai_tap' => 1, 'nhom_co' => 1, 'goi_tap' => 1])
            ->assertJsonPath('data.ho_so.hoan_thanh', 1)->assertJsonPath('data.ho_so.tong_muc', 6)
            ->assertJsonMissingPath('data.quan_tri')->assertJsonMissingPath('data.giao_an_da_duyet');
        $khach->hoSoKhachHang()->update(['muc_tieu' => 'Tăng cơ', 'kinh_nghiem' => 'Mới tập', 'ngay_sinh' => '2000-01-01', 'gioi_tinh' => 'NAM']);
        $khach->hoSoKhachHang->thoi_gian_co_the_tap = ['Thứ hai'];
        $khach->hoSoKhachHang->save();
        $this->dangNhap($khach->fresh());
        $this->getJson('/api/v1/khach-hang/tong-quan')->assertOk()->assertJsonPath('data.ho_so.hoan_thanh', 6);
    }

    public function test_pt_chi_thay_giao_an_da_duyet_va_ho_so_cua_minh(): void
    {
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $this->taoCatalog($admin);
        $pt = $this->taoTaiKhoan(TaiKhoan::HUAN_LUYEN_VIEN);
        $pt->hoSoHuanLuyenVien()->update(['chuyen_mon' => 'Sức bền']);
        $this->dangNhap($pt);
        $this->getJson('/api/v1/pt/tong-quan')
            ->assertOk()->assertJsonPath('data.giao_an_da_duyet', 1)
            ->assertJsonPath('data.ho_so.hoan_thanh', 2)->assertJsonPath('data.ho_so.tong_muc', 3)
            ->assertJsonMissingPath('data.quan_tri');
    }

    public function test_dashboard_rong_khong_sinh_du_lieu_gia(): void
    {
        $khach = $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
        $this->dangNhap($khach);
        $this->getJson('/api/v1/khach-hang/tong-quan')->assertOk()
            ->assertJsonPath('data.thu_vien', ['bai_tap' => 0, 'nhom_co' => 0, 'goi_tap' => 0])
            ->assertJsonMissingPath('data.doanh_thu')->assertJsonMissingPath('data.so_buoi_hoan_thanh');
        $this->assertSame(0, GoiTap::count());
    }

    private function donBaoCao(array $them = []): DangKyGoiTap
    {
        $kh = $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
        $goi = GoiTap::create(['ten_goi' => 'Tên catalog mới', 'gia' => 999999, 'co_chatbot' => true, 'so_luot_chatbot_moi_ngay' => 10, 'so_buoi_pt' => 10, 'thoi_han_ngay' => 30, 'trang_thai' => 'NGUNG_SU_DUNG']);

        return DangKyGoiTap::create([...[
            'khach_hang_id' => $kh->hoSoKhachHang->id, 'goi_tap_id' => $goi->id,
            'client_request_id' => (string) Str::uuid(), 'ma_don_payos' => random_int(10000000, 999999999),
            'ten_goi_snapshot' => 'Tên gói lúc mua', 'gia_snapshot' => 100000, 'co_chatbot_snapshot' => true,
            'so_buoi_pt_snapshot' => 10, 'so_buoi_con_lai' => 0, 'thoi_han_ngay_snapshot' => 30,
            'trang_thai' => 'CHO_THANH_TOAN', 'created_at' => '2026-10-01 02:00:00',
        ], ...$them]);
    }

    private function thuBaoCao(DangKyGoiTap $don, int $tien, string $luc, array $them = []): ThanhToan
    {
        return ThanhToan::create([...['dang_ky_goi_tap_id' => $don->id, 'ma_giao_dich' => (string) Str::uuid(), 'so_tien' => $tien, 'thanh_toan_luc' => $luc, 'xac_minh_luc' => now(), 'trang_thai' => 'DA_XAC_MINH'], ...$them]);
    }

    private function baoCao(string $query = 'tu_ngay=2026-10-01&den_ngay=2026-10-03&nhom=ngay')
    {
        return $this->getJson('/api/v1/admin/bao-cao?'.$query);
    }

    public function test_dashboard_van_hanh_dem_hien_tai_doc_lich_theo_ngay_vn_va_khong_lo_noi_dung_ai(): void
    {
        CarbonImmutable::setTestNow('2026-10-04 03:00:00 UTC');
        Carbon::setTestNow('2026-10-04 03:00:00 UTC');
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $pt = $this->taoTaiKhoan(TaiKhoan::HUAN_LUYEN_VIEN)->hoSoHuanLuyenVien;
        $dangDung = ['trang_thai' => 'DANG_SU_DUNG', 'kich_hoat_luc' => '2026-10-01 00:00:00', 'het_han_luc' => '2026-10-10 03:00:00', 'so_buoi_con_lai' => 2];
        $don = $this->donBaoCao($dangDung);
        $cho = $this->donBaoCao($dangDung);
        // Không đếm khách hết lượt, chưa trả tiền, hết hạn hoặc bị khóa.
        $this->donBaoCao([...$dangDung, 'so_buoi_con_lai' => 0]);
        $this->donBaoCao();
        $this->donBaoCao([...$dangDung, 'het_han_luc' => '2026-10-04 03:00:00']);
        $khoa = $this->donBaoCao($dangDung);
        DB::table('tai_khoan')->whereIn('id', DB::table('ho_so_khach_hang')->where('id', $khoa->khach_hang_id)->select('tai_khoan_id'))->update(['trang_thai' => 'BI_KHOA']);
        $pc = DB::table('phan_cong_huan_luyen_vien')->insertGetId(['khach_hang_id' => $don->khach_hang_id, 'huan_luyen_vien_id' => $pt->id, 'nguoi_phan_cong_id' => $admin->id, 'bat_dau_luc' => '2026-10-01 00:00:00', 'client_request_id' => (string) Str::uuid()]);
        $taoLich = function ($luc, $trangThai, $them = []) use ($don, $pt, $pc) {
            $batDau = CarbonImmutable::parse($luc, 'UTC');
            $slot = DB::table('khung_gio_huan_luyen_vien')->insertGetId(['huan_luyen_vien_id' => $pt->id, 'bat_dau_luc' => $batDau, 'ket_thuc_luc' => $batDau->addHour(), 'trang_thai' => 'HOAT_DONG']);

            return DB::table('lich_hen_huan_luyen')->insertGetId([...['khach_hang_id' => $don->khach_hang_id, 'huan_luyen_vien_id' => $pt->id, 'phan_cong_id' => $pc, 'khung_gio_id' => $slot, 'dang_ky_goi_tap_id' => $don->id, 'client_request_id' => (string) Str::uuid(), 'bat_dau_luc' => $batDau, 'ket_thuc_luc' => $batDau->addHour(), 'trang_thai' => $trangThai], ...$them]);
        };
        $hetHan = $taoLich('2026-10-03 17:00:00', 'CHO_XAC_NHAN', ['han_xac_nhan_dat_lich' => now()]);
        $taoLich('2026-10-03 16:59:59', 'DA_HUY');
        $taoLich('2026-10-04 17:00:00', 'DA_HUY');
        for ($i = 0; $i < 8; $i++) {
            $taoLich('2026-10-04 '.str_pad((string) ($i + 4), 2, '0', STR_PAD_LEFT).':00:00', 'DA_XAC_NHAN');
        }
        $quaHan = $taoLich('2026-10-03 02:00:00', 'DA_XAC_NHAN');
        $taoLich('2026-10-02 02:00:00', 'QUA_HAN_XAC_NHAN', ['dong_xu_ly_luc' => now()]);
        $this->thuBaoCao($cho, 1000, '2026-09-01 00:00:00', ['trang_thai' => 'CAN_DOI_SOAT']);
        $hoiThoai = DB::table('hoi_thoai_tro_ly')->insertGetId(['khach_hang_id' => $don->khach_hang_id, 'trang_thai' => 'HOAT_DONG', 'tieu_de' => 'Nội dung riêng tư không được lộ']);
        foreach ([['2026-10-04', 'THANH_CONG'], ['2026-10-04', 'LOI'], ['2026-10-04', 'DANG_XU_LY'], ['2026-09-28', 'THANH_CONG'], ['2026-09-27', 'THANH_CONG']] as [$ngay, $tt]) {
            DB::table('yeu_cau_tro_ly')->insert(['khach_hang_id' => $don->khach_hang_id, 'hoi_thoai_tro_ly_id' => $hoiThoai, 'dang_ky_goi_tap_id' => $don->id, 'client_request_id' => (string) Str::uuid(), 'ngay_han_muc' => $ngay, 'trang_thai' => $tt, 'input_tokens' => 10, 'output_tokens' => 5]);
        }
        $this->dangNhap($admin);
        $r = $this->baoCao()->assertOk()
            ->assertJsonPath('data.van_hanh.can_xu_ly.khach_cho_pt', 1)
            ->assertJsonPath('data.van_hanh.can_xu_ly.giao_dich_doi_soat', 1)
            ->assertJsonPath('data.van_hanh.can_xu_ly.lich_qua_han', 1)
            ->assertJsonPath('data.van_hanh.pt_co_hoc_vien', 1)
            ->assertJsonPath('data.van_hanh.goi_sap_het_han', 4)
            ->assertJsonPath('data.van_hanh.tai_lieu_da_xuat_ban', 0)
            ->assertJsonPath('data.trong_ky.cho_doi_soat', 0)
            ->assertJsonPath('data.van_hanh.lich_hom_nay.tong', 9)
            ->assertJsonPath('data.van_hanh.lich_hom_nay.trang_thai.HET_HAN', 1)
            ->assertJsonPath('data.van_hanh.lich_hom_nay.trang_thai.DA_XAC_NHAN', 8)
            ->assertJsonPath('data.van_hanh.lich_hom_nay.data.0.id', $hetHan)
            ->assertJsonPath('data.van_hanh.lich_hom_nay.data.0.trang_thai', 'HET_HAN')
            ->assertJsonPath('data.van_hanh.lich_hom_nay.data.0.bat_dau_luc', '2026-10-03T17:00:00+00:00')
            ->assertJsonCount(8, 'data.van_hanh.lich_hom_nay.data')
            ->assertJsonPath('data.van_hanh.ai.yeu_cau', 3)
            ->assertJsonPath('data.van_hanh.ai.thanh_cong', 1)
            ->assertJsonPath('data.van_hanh.ai.loi', 1)
            ->assertJsonPath('data.van_hanh.ai.dang_xu_ly', 1)
            ->assertJsonPath('data.van_hanh.ai.input_tokens', 30)
            ->assertJsonPath('data.van_hanh.ai.output_tokens', 15)
            ->assertJsonCount(7, 'data.van_hanh.ai.bieu_do')
            ->assertJsonPath('data.van_hanh.ai.bieu_do.0.so_luong', 1)
            ->assertJsonPath('data.van_hanh.ai.bieu_do.1.so_luong', 0)
            ->assertJsonPath('data.van_hanh.ai.bieu_do.6.so_luong', 3);
        $this->assertSame(['id', 'bat_dau_luc', 'ket_thuc_luc', 'khach_hang', 'pt', 'trang_thai'], array_keys($r->json('data.van_hanh.lich_hom_nay.data.0')));
        $this->assertStringNotContainsString('Nội dung riêng tư', $r->getContent());
        $this->assertSame('DA_XAC_NHAN', DB::table('lich_hen_huan_luyen')->where('id', $quaHan)->value('trang_thai'));
        $this->assertSame('CHO_XAC_NHAN', DB::table('lich_hen_huan_luyen')->where('id', $hetHan)->value('trang_thai'));
    }

    public function test_bao_cao_quyen_validation_va_rong(): void
    {
        $this->baoCao()->assertUnauthorized();
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN, TaiKhoan::ADMIN] as $vaiTro) {
            $tk = $this->taoTaiKhoan($vaiTro);
            $this->dangNhap($tk);
            if ($vaiTro !== TaiKhoan::ADMIN) {
                $this->baoCao('vai_tro=ADMIN')->assertForbidden();
            } else {
                $this->baoCao()->assertOk()->assertHeader('Cache-Control', 'no-store, private')
                    ->assertJsonPath('data.trong_ky.thuc_thu', 0)->assertJsonCount(3, 'data.bieu_do')
                    ->assertJsonPath('data.bieu_do.1.thuc_thu', 0)->assertJsonPath('data.theo_goi', []);
                foreach (['tu_ngay=2026-10-01', 'tu_ngay=2026-02-30&den_ngay=2026-10-03', 'tu_ngay=2026-10-03&den_ngay=2026-10-01', 'tu_ngay=2024-01-01&den_ngay=2026-10-03', 'den_ngay=2999-01-01&tu_ngay=2026-10-01', 'nhom=SQL', 'pt_page=0', 'pt_page[]=1'] as $query) {
                    $this->baoCao($query)->assertUnprocessable();
                }
                $tk->trang_thai = TaiKhoan::BI_KHOA;
                $tk->save();
                $this->dangNhap($tk->fresh());
                $this->baoCao()->assertForbidden();
            }
        }
    }

    public function test_bao_cao_tien_thuc_nhan_khong_lay_gia_don_khong_nhan_doi_join(): void
    {
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $don = $this->donBaoCao(['trang_thai' => 'DANG_SU_DUNG', 'kich_hoat_luc' => '2026-10-01 05:00:00', 'het_han_luc' => '2026-11-01 05:00:00']);
        $this->thuBaoCao($don, 40000, '2026-10-01 03:00:00');
        $this->thuBaoCao($don, 60000, '2026-10-01 04:00:00');
        $doiSoat = $this->donBaoCao(['trang_thai' => 'CAN_DOI_SOAT']);
        $this->thuBaoCao($doiSoat, 70000, '2026-10-02 01:00:00', ['trang_thai' => 'CAN_DOI_SOAT']);
        $cho = $this->donBaoCao();
        $this->thuBaoCao($cho, 99999, '2026-10-02 01:00:00', ['xac_minh_luc' => null]);
        $this->thuBaoCao($cho, 99999, '2026-10-02 01:00:00', ['trang_thai' => 'THAT_BAI']);
        $this->thuBaoCao($cho, 99999, '2026-10-02 01:00:00', ['thanh_toan_luc' => null]);
        $this->dangNhap($admin);
        $r = $this->baoCao()->assertOk()->assertJsonPath('data.trong_ky.tien_da_nhan', 170000)
            ->assertJsonPath('data.trong_ky.cho_doi_soat', 70000)->assertJsonPath('data.trong_ky.don_kich_hoat', 1)
            ->assertJsonPath('data.trong_ky.don_moi', 3)->assertJsonPath('data.theo_goi.0.ten_goi', 'Tên gói lúc mua')
            ->assertJsonPath('data.theo_goi.0.so_don_nhan_tien', 1);
        $this->assertSame(170000, array_sum(array_column($r->json('data.bieu_do'), 'tien_da_nhan')));
        $this->assertSame(170000, array_sum(array_column($r->json('data.theo_goi'), 'tien_da_nhan')));
        $this->assertSame(6, ThanhToan::count());
    }

    public function test_hoan_trong_ky_cua_khoan_thu_ngoai_ky_co_the_am_va_giu_lich_su(): void
    {
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $don = $this->donBaoCao();
        $this->thuBaoCao($don, 100000, '2026-09-15 00:00:00', ['trang_thai' => 'DA_HOAN_TIEN', 'so_tien_hoan' => 70000, 'hoan_tien_luc' => '2026-10-01 17:00:00']);
        $this->thuBaoCao($don, 20000, '2026-10-01 00:00:00', ['trang_thai' => 'DA_HOAN_TIEN', 'so_tien_hoan' => 10000, 'hoan_tien_luc' => '2026-11-01 00:00:00']);
        $this->dangNhap($admin);
        $this->baoCao()->assertOk()->assertJsonPath('data.trong_ky.tien_da_nhan', 20000)
            ->assertJsonPath('data.trong_ky.tien_da_hoan', 70000)->assertJsonPath('data.trong_ky.thuc_thu', -50000)
            ->assertJsonPath('data.bieu_do.1.tien_da_hoan', 70000)->assertJsonPath('data.bieu_do.1.thuc_thu', -70000)
            ->assertJsonPath('data.theo_goi.0.thuc_thu', -50000);
        $this->assertSame('DA_HOAN_TIEN', $don->thanhToan()->first()->trang_thai);
    }

    public function test_moc_ngay_viet_nam_nua_mo_va_nhom_thang(): void
    {
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $don = $this->donBaoCao();
        $this->thuBaoCao($don, 11, '2026-09-30 16:59:59.999999');
        $this->thuBaoCao($don, 20, '2026-09-30 17:00:00');
        $this->thuBaoCao($don, 30, '2026-10-03 16:59:59.999999');
        $this->thuBaoCao($don, 40, '2026-10-03 17:00:00');
        $this->dangNhap($admin);
        $this->baoCao()->assertOk()->assertJsonPath('data.trong_ky.thuc_thu', 50)
            ->assertJsonPath('data.bieu_do.0.thuc_thu', 20)->assertJsonPath('data.bieu_do.2.thuc_thu', 30);
        $this->baoCao('tu_ngay=2026-09-01&den_ngay=2026-10-03&nhom=thang')->assertOk()
            ->assertJsonCount(2, 'data.bieu_do')->assertJsonPath('data.bieu_do.0.ky', '2026-09')
            ->assertJsonPath('data.bieu_do.0.thuc_thu', 11)->assertJsonPath('data.bieu_do.1.thuc_thu', 50);
    }

    public function test_goi_hien_tai_khong_can_worker_het_han_va_bao_cao_khong_ghi(): void
    {
        Carbon::setTestNow('2026-10-04 05:00:00');
        CarbonImmutable::setTestNow('2026-10-04 05:00:00');
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $this->donBaoCao(['trang_thai' => 'DANG_SU_DUNG', 'kich_hoat_luc' => '2026-10-01 00:00:00', 'het_han_luc' => '2026-10-05 00:00:00']);
        $het = $this->donBaoCao(['trang_thai' => 'DANG_SU_DUNG', 'kich_hoat_luc' => '2026-10-01 00:00:00', 'het_han_luc' => '2026-10-04 05:00:00']);
        $this->donBaoCao(['trang_thai' => 'DANG_SU_DUNG', 'kich_hoat_luc' => '2026-10-05 00:00:00', 'het_han_luc' => '2026-10-06 00:00:00']);
        $this->dangNhap($admin);
        $this->baoCao('')->assertOk()->assertJsonPath('data.hien_tai.goi_dang_su_dung', 1)
            ->assertJsonPath('data.bo_loc.tu_ngay', '2026-09-05')->assertJsonCount(30, 'data.bieu_do');
        $this->assertSame('DANG_SU_DUNG', $het->fresh()->trang_thai);
        $this->assertSame(0, DB::table('nhat_ky_he_thong')->count());
    }

    public function test_pt_phan_cong_hien_tai_va_buoi_lich_su_khong_lo_ho_so_chat(): void
    {
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $pt = $this->taoTaiKhoan(TaiKhoan::HUAN_LUYEN_VIEN);
        $ptId = $pt->hoSoHuanLuyenVien->id;
        $don = $this->donBaoCao();
        $pc = DB::table('phan_cong_huan_luyen_vien')->insertGetId(['khach_hang_id' => $don->khach_hang_id, 'huan_luyen_vien_id' => $ptId, 'nguoi_phan_cong_id' => $admin->id, 'bat_dau_luc' => '2026-09-01 00:00:00', 'ket_thuc_luc' => '2026-10-01 10:00:00']);
        foreach (['HOAN_THANH', 'VANG_MAT', 'QUA_HAN_XAC_NHAN', 'HOAN_THANH'] as $i => $state) {
            $start = CarbonImmutable::parse('2026-10-01 01:00:00')->addHours($i);
            $slot = DB::table('khung_gio_huan_luyen_vien')->insertGetId(['huan_luyen_vien_id' => $ptId, 'bat_dau_luc' => $start, 'ket_thuc_luc' => $start->addHour(), 'trang_thai' => 'HOAT_DONG']);
            DB::table('lich_hen_huan_luyen')->insert(['khach_hang_id' => $don->khach_hang_id, 'huan_luyen_vien_id' => $ptId, 'phan_cong_id' => $pc, 'khung_gio_id' => $slot, 'dang_ky_goi_tap_id' => $don->id, 'client_request_id' => (string) Str::uuid(), 'bat_dau_luc' => $start, 'ket_thuc_luc' => $start->addHour(), 'trang_thai' => $state, 'tieu_hao_luc' => $i === 0 ? $start->addHour() : null]);
        }
        $moi = $this->donBaoCao();
        DB::table('phan_cong_huan_luyen_vien')->insert(['khach_hang_id' => $moi->khach_hang_id, 'huan_luyen_vien_id' => $ptId, 'nguoi_phan_cong_id' => $admin->id, 'bat_dau_luc' => '2026-10-01 00:00:00']);
        for ($i = 0; $i < 20; $i++) {
            $this->taoTaiKhoan(TaiKhoan::HUAN_LUYEN_VIEN);
        }
        $this->dangNhap($admin);
        $r = $this->baoCao()->assertOk()->assertJsonPath('data.trong_ky.buoi_pt_hoan_thanh', 1)
            ->assertJsonPath('data.hien_tai.hoc_vien_co_pt', 1)->assertJsonPath('data.pt.data.0.hoc_vien_hien_tai', 1)
            ->assertJsonPath('data.pt.data.0.buoi_hoan_thanh', 1)->assertJsonPath('data.pt.meta.total', 21)->assertJsonCount(20, 'data.pt.data');
        $this->assertSame(['id', 'ho_ten', 'trang_thai', 'hoc_vien_hien_tai', 'buoi_hoan_thanh'], array_keys($r->json('data.pt.data.0')));
        $this->baoCao('tu_ngay=2026-10-01&den_ngay=2026-10-03&pt_page=2')->assertOk()->assertJsonCount(1, 'data.pt.data')->assertJsonPath('data.pt.meta.current_page', 2);
    }
}
