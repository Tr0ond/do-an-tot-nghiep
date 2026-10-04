<?php

namespace Tests\Feature;

use App\Models\GoiTap;
use App\Models\HoSoHuanLuyenVien;
use App\Models\KhungGioHuanLuyenVien;
use App\Models\LichHenHuanLuyen;
use App\Models\TaiKhoan;
use App\Services\LichHenService;
use App\Services\MuaGoiService;
use App\Services\PhanCongService;
use App\Services\TaiKhoanService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PDO;
use Tests\TestCase;

class LichHenTest extends TestCase
{
    private static ?string $tenDatabase = null;

    private static ?PDO $pdoMayChu = null;

    protected function setUp(): void
    {
        parent::setUp();
        Http::swap(new Factory);
        Http::preventStrayRequests();
        config(['payos.client_id' => 'test', 'payos.api_key' => 'test', 'payos.checksum_key' => 'test']);
        $cauHinh = config('database.connections.mysql');
        if (self::$tenDatabase === null) {
            $cauHinh['database'] = null;
            $cauHinh['url'] = null;
            config(['database.connections.may_chu_mua_goi' => $cauHinh]);
            self::$pdoMayChu = DB::connection('may_chu_mua_goi')->getPdo();
            $tenMoi = 'kiem_tra_lich_hen_'.bin2hex(random_bytes(8));
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

    private function nguoi(string $vaiTro = TaiKhoan::KHACH_HANG): TaiKhoan
    {
        return app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Học viên kiểm thử', 'email' => Str::uuid().'@example.test', 'password' => 'Demo123456!'], $vaiTro);
    }

    private function boDuLieu(int $buoi = 8, ?TaiKhoan $pt = null): array
    {
        $khach = $this->nguoi();
        $pt ??= $this->nguoi(TaiKhoan::HUAN_LUYEN_VIEN);
        $admin = $this->nguoi(TaiKhoan::ADMIN);
        $goi = GoiTap::create(['ten_goi' => 'Gói kiểm thử lịch', 'gia' => 99000, 'co_chatbot' => true, 'so_luot_chatbot_moi_ngay' => 10, 'so_buoi_pt' => $buoi, 'thoi_han_ngay' => 30, 'trang_thai' => 'HOAT_DONG']);
        $don = app(MuaGoiService::class)->taoDon($khach, ['goi_tap_id' => $goi->id, 'client_request_id' => (string) Str::uuid()]);
        // Fixture không gửi tiền hoặc gọi payOS; mô phỏng kết quả đã thanh toán.
        $don->update(['trang_thai' => 'DANG_SU_DUNG', 'kich_hoat_luc' => now()->subDay(), 'het_han_luc' => now()->addDays(29), 'so_buoi_con_lai' => $buoi]);
        $phanCong = app(PhanCongService::class)->phanCong($admin, ['khach_hang_id' => $khach->hoSoKhachHang->id, 'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'client_request_id' => (string) Str::uuid()]);

        return compact('khach', 'pt', 'admin', 'goi', 'don', 'phanCong');
    }

    private function slot(array $bo, int $gio = 8): KhungGioHuanLuyenVien
    {
        return app(LichHenService::class)->taoKhungGio($bo['pt'], ['bat_dau_luc' => now()->addHours($gio)->toIso8601String()]);
    }

    private function dat(array $bo, ?KhungGioHuanLuyenVien $slot = null): LichHenHuanLuyen
    {
        return app(LichHenService::class)->datLich($bo['khach'], ['khung_gio_id' => ($slot ?? $this->slot($bo))->id, 'client_request_id' => (string) Str::uuid()]);
    }

    private function dangNhap(TaiKhoan $nguoi): void
    {
        Auth::forgetGuards();
        $this->actingAs($nguoi, 'web');
    }

    public function test_dat_xac_nhan_huy_va_tu_choi_thong_bao_dung_nguoi_retry_khong_lap(): void
    {
        $b = $this->boDuLieu();
        // Kiểm tra riêng sự kiện lịch hẹn, giữ thông báo phân công của fixture ngoài các số đếm này.
        $b['khach']->notifications()->delete();
        $b['pt']->notifications()->delete();
        $s = app(LichHenService::class);
        $slot = $this->slot($b);
        $d = ['khung_gio_id' => $slot->id, 'client_request_id' => (string) Str::uuid()];
        $l = $s->datLich($b['khach'], $d);
        $s->datLich($b['khach'], $d);
        $this->assertSame(1, $b['pt']->notifications()->count());
        $this->assertSame('Có yêu cầu đặt lịch', $b['pt']->notifications()->first()->data['tieu_de']);
        $s->thaoTac($b['pt'], $l->id, 'xac-nhan');
        $s->thaoTac($b['pt'], $l->id, 'xac-nhan');
        $this->assertSame(1, $b['khach']->notifications()->count());
        $this->assertSame('Lịch hẹn đã được xác nhận', $b['khach']->notifications()->first()->data['tieu_de']);
        $s->thaoTac($b['khach'], $l->id, 'huy', 'Bận');
        $s->thaoTac($b['khach'], $l->id, 'huy', 'Bận');
        $this->assertSame(2, $b['pt']->notifications()->count());
        $l2 = $s->datLich($b['khach'], ['khung_gio_id' => $slot->id, 'client_request_id' => (string) Str::uuid()]);
        $s->thaoTac($b['pt'], $l2->id, 'tu-choi', 'Bận');
        $s->thaoTac($b['pt'], $l2->id, 'tu-choi', 'Bận');
        $this->assertSame(2, $b['khach']->notifications()->count());
        $this->assertSame(0, $b['admin']->notifications()->count());
        $this->assertSame(8, $b['don']->fresh()->so_buoi_con_lai);
    }

    public function test_het_han_lazy_va_worker_chi_mot_thong_bao(): void
    {
        $b = $this->boDuLieu();
        $s = app(LichHenService::class);
        $l = $this->dat($b);
        $this->travelTo($l->han_xac_nhan_dat_lich);
        // Mở/đóng slot cũng dọn hết hạn: phải ghi thông báo cùng transaction đó.
        $s->doiKhungGio($b['pt'], $l->khung_gio_id, 'DONG');
        $s->donQuaHan();
        $tin = $b['khach']->notifications()->get()->filter(fn ($n) => $n->data['tieu_de'] === 'Yêu cầu đặt lịch đã hết hạn');
        $this->assertCount(1, $tin);
        $this->assertSame('HET_HAN', $l->fresh()->trang_thai);
        $this->assertSame('/khach-hang/lich-hen/'.$l->id, $tin->first()->data['duong_dan']);
        $this->travelBack();
    }

    public function test_hoan_thanh_va_vang_mat_bao_kh_khong_tieu_hao_lap(): void
    {
        $b = $this->boDuLieu();
        $s = app(LichHenService::class);
        $l1 = $this->dat($b, $this->slot($b, 8));
        $l2 = $this->dat($b, $this->slot($b, 10));
        $s->thaoTac($b['pt'], $l1->id, 'xac-nhan');
        $s->thaoTac($b['pt'], $l2->id, 'xac-nhan');
        $this->travelTo($l2->ket_thuc_luc->addMinute());
        $s->thaoTac($b['pt'], $l1->id, 'hoan-thanh');
        $s->thaoTac($b['pt'], $l1->id, 'hoan-thanh');
        $s->thaoTac($b['pt'], $l2->id, 'vang-mat', 'Không đến');
        $s->thaoTac($b['pt'], $l2->id, 'vang-mat', 'Không đến');
        $this->assertCount(1, $b['khach']->notifications()->get()->filter(fn ($n) => $n->data['tieu_de'] === 'Buổi PT đã hoàn thành'));
        $this->assertCount(1, $b['khach']->notifications()->get()->filter(fn ($n) => $n->data['tieu_de'] === 'Buổi PT đã ghi nhận vắng mặt'));
        $this->assertSame(7, $b['don']->fresh()->so_buoi_con_lai);
        $this->travelBack();
    }

    public function test_dat_xac_nhan_hoan_thanh_retry_dung_mot_buoi(): void
    {
        $bo = $this->boDuLieu();
        $slot = $this->slot($bo);
        $this->dangNhap($bo['khach']);
        $payload = ['khung_gio_id' => $slot->id, 'client_request_id' => (string) Str::uuid(), 'khach_hang_id' => 999, 'trang_thai' => 'HOAN_THANH'];
        $id = $this->postJson('/api/v1/khach-hang/lich-hen', $payload)->assertOk()->assertJsonPath('data.trang_thai', 'CHO_XAC_NHAN')->json('data.id');
        $this->postJson('/api/v1/khach-hang/lich-hen', $payload)->assertOk()->assertJsonPath('data.id', $id);
        $this->assertSame(8, $bo['don']->fresh()->so_buoi_con_lai);
        $this->dangNhap($bo['pt']);
        $this->postJson('/api/v1/pt/lich-hen/'.$id.'/xac-nhan')->assertOk();
        $this->postJson('/api/v1/pt/lich-hen/'.$id.'/hoan-thanh')->assertConflict();
        $this->travelTo($slot->ket_thuc_luc->addMinute());
        $this->postJson('/api/v1/pt/lich-hen/'.$id.'/hoan-thanh')->assertOk()->assertJsonPath('data.trang_thai', 'HOAN_THANH');
        $this->postJson('/api/v1/pt/lich-hen/'.$id.'/hoan-thanh')->assertOk();
        $this->assertSame(7, $bo['don']->fresh()->so_buoi_con_lai);
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'HOAN_THANH')->count());
        $this->assertNotNull(LichHenHuanLuyen::find($id)->tieu_hao_luc);
        $this->travelBack();
    }

    public function test_khung_gio_60_phut_overlap_retry_va_ownership(): void
    {
        $bo = $this->boDuLieu();
        $this->getJson('/api/v1/pt/khung-gio?ngay=2026-10-03')->assertUnauthorized();
        $this->dangNhap($bo['pt']);
        $payload = ['bat_dau_luc' => now()->addHours(8)->toIso8601String(), 'ket_thuc_luc' => now()->addHours(10)->toIso8601String()];
        $id = $this->postJson('/api/v1/pt/khung-gio', $payload)->assertOk()->json('data.id');
        $this->postJson('/api/v1/pt/khung-gio', $payload)->assertOk()->assertJsonPath('data.id', $id);
        $slot = KhungGioHuanLuyenVien::find($id);
        $this->assertSame(3600, (int) $slot->bat_dau_luc->diffInSeconds($slot->ket_thuc_luc));
        $this->postJson('/api/v1/pt/khung-gio', ['bat_dau_luc' => $slot->bat_dau_luc->addMinutes(30)->toIso8601String()])->assertConflict();
        $this->postJson('/api/v1/pt/khung-gio', ['bat_dau_luc' => '2026-10-03 08:00:00'])->assertUnprocessable();
        $this->patchJson('/api/v1/pt/khung-gio/'.$id, ['trang_thai' => 'DONG'])->assertOk();
        $this->patchJson('/api/v1/pt/khung-gio/'.$id, ['trang_thai' => 'MO'])->assertOk();
        $this->dangNhap($this->nguoi(TaiKhoan::HUAN_LUYEN_VIEN));
        $this->patchJson('/api/v1/pt/khung-gio/'.$id, ['trang_thai' => 'DONG'])->assertNotFound();
    }

    public function test_quyen_lich_hen_khong_tu_tru_buoi_khong_thay_pt(): void
    {
        $bo = $this->boDuLieu();
        $lich = $this->dat($bo);
        $this->dangNhap($this->nguoi());
        $this->getJson('/api/v1/khach-hang/lich-hen/'.$lich->id)->assertNotFound();
        $this->postJson('/api/v1/khach-hang/lich-hen/'.$lich->id.'/huy', ['ly_do' => 'Bận'])->assertNotFound();
        $this->dangNhap($bo['khach']);
        $this->postJson('/api/v1/pt/lich-hen/'.$lich->id.'/hoan-thanh')->assertForbidden();
        $this->dangNhap($bo['admin']);
        $this->postJson('/api/v1/pt/lich-hen/'.$lich->id.'/hoan-thanh')->assertForbidden();
        $this->postJson('/api/v1/admin/lich-hen/'.$lich->id.'/dong-xu-ly', ['ly_do' => 'Kiểm tra'])->assertConflict();
        $this->dangNhap($this->nguoi(TaiKhoan::HUAN_LUYEN_VIEN));
        $this->getJson('/api/v1/pt/lich-hen/'.$lich->id)->assertNotFound();
        $this->postJson('/api/v1/pt/lich-hen/'.$lich->id.'/xac-nhan')->assertNotFound();
        $bo['khach']->trang_thai = TaiKhoan::BI_KHOA;
        $bo['khach']->save();
        $this->dangNhap($bo['khach']);
        $this->getJson('/api/v1/khach-hang/lich-hen')->assertForbidden();
    }

    public function test_deadline_4_gio_han_goi_so_buoi_va_chan_giu_slot(): void
    {
        $bo = $this->boDuLieu(1);
        $slotSom = $this->slot($bo, 3);
        $this->dangNhap($bo['khach']);
        $this->postJson('/api/v1/khach-hang/lich-hen', ['khung_gio_id' => $slotSom->id, 'client_request_id' => (string) Str::uuid()])->assertConflict();
        $slot = $this->slot($bo, 8);
        $lich = $this->dat($bo, $slot);
        $this->assertSame(1, $bo['don']->fresh()->so_buoi_con_lai);
        $slotKhac = $this->slot($bo, 10);
        $this->postJson('/api/v1/khach-hang/lich-hen', ['khung_gio_id' => $slotKhac->id, 'client_request_id' => (string) Str::uuid()])->assertConflict();
        $this->dangNhap($bo['pt']);
        $this->patchJson('/api/v1/pt/khung-gio/'.$slot->id, ['trang_thai' => 'DONG'])->assertConflict();
        $this->postJson('/api/v1/pt/lich-hen/'.$lich->id.'/tu-choi', ['ly_do' => 'Có việc đột xuất'])->assertOk();
        $bo['don']->update(['het_han_luc' => $slotKhac->bat_dau_luc]);
        $this->dangNhap($bo['khach']);
        $this->postJson('/api/v1/khach-hang/lich-hen', ['khung_gio_id' => $slotKhac->id, 'client_request_id' => (string) Str::uuid()])->assertConflict();
    }

    public function test_het_han_cho_dong_khong_phu_thuoc_worker_va_dat_lai(): void
    {
        $bo = $this->boDuLieu();
        $slot = $this->slot($bo);
        $lich = $this->dat($bo, $slot);
        $this->travelTo($lich->han_xac_nhan_dat_lich);
        $this->dangNhap($bo['pt']);
        $this->getJson('/api/v1/pt/lich-hen/'.$lich->id)->assertOk()->assertJsonPath('data.trang_thai', 'HET_HAN')->assertJsonPath('data.hanh_dong', []);
        $this->getJson('/api/v1/pt/lich-hen?trang_thai=CHO_XAC_NHAN')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson('/api/v1/pt/lich-hen/'.$lich->id.'/xac-nhan')->assertConflict();
        $moi = $this->dat($bo, $slot);
        $this->assertNotSame($lich->id, $moi->id);
        $this->assertSame('HET_HAN', $lich->fresh()->trang_thai);
        $this->assertNull($lich->fresh()->khung_gio_dang_giu_id);
        $this->travelBack();
    }

    public function test_huy_dung_han_retry_va_vang_mat_khong_tru(): void
    {
        $bo = $this->boDuLieu();
        $slot = $this->slot($bo);
        $lich = $this->dat($bo, $slot);
        app(LichHenService::class)->thaoTac($bo['pt'], $lich->id, 'xac-nhan');
        $this->travelTo($slot->bat_dau_luc->subHours(2));
        $this->dangNhap($bo['khach']);
        $this->postJson('/api/v1/khach-hang/lich-hen/'.$lich->id.'/huy', ['ly_do' => '   '])->assertUnprocessable();
        $this->postJson('/api/v1/khach-hang/lich-hen/'.$lich->id.'/huy', ['ly_do' => 'Bận công việc'])->assertOk();
        $this->postJson('/api/v1/khach-hang/lich-hen/'.$lich->id.'/huy', ['ly_do' => 'Bận công việc'])->assertOk();
        $this->assertSame($bo['khach']->id, $lich->fresh()->nguoi_huy_id);
        $this->travelBack();
        $moi = $this->dat($bo, $slot);
        app(LichHenService::class)->thaoTac($bo['pt'], $moi->id, 'xac-nhan');
        $this->travelTo($slot->bat_dau_luc->subHours(2)->addSecond());
        $this->postJson('/api/v1/khach-hang/lich-hen/'.$moi->id.'/huy', ['ly_do' => 'Bận'])->assertConflict();
        $this->travelTo($slot->ket_thuc_luc);
        $this->dangNhap($bo['pt']);
        $this->postJson('/api/v1/pt/lich-hen/'.$moi->id.'/vang-mat', ['ly_do' => 'Học viên không tới'])->assertOk()->assertJsonPath('data.trang_thai', 'VANG_MAT');
        $this->postJson('/api/v1/pt/lich-hen/'.$moi->id.'/vang-mat', ['ly_do' => 'Học viên không tới'])->assertOk();
        $this->assertSame(8, $bo['don']->fresh()->so_buoi_con_lai);
        $this->assertNull($moi->fresh()->tieu_hao_luc);
        $this->travelBack();
    }

    public function test_hoan_thanh_trong_24h_goi_vua_het_han_va_khong_muon_goi_moi(): void
    {
        $bo = $this->boDuLieu();
        $slot = $this->slot($bo);
        $bo['don']->update(['het_han_luc' => $slot->ket_thuc_luc]);
        $lich = $this->dat($bo, $slot);
        app(LichHenService::class)->thaoTac($bo['pt'], $lich->id, 'xac-nhan');
        $this->travelTo($slot->ket_thuc_luc->addHours(23));
        $bo['don']->update(['trang_thai' => 'HET_HAN']);
        $donMoi = app(MuaGoiService::class)->taoDon($bo['khach'], ['goi_tap_id' => $bo['goi']->id, 'client_request_id' => (string) Str::uuid()]);
        $donMoi->update(['trang_thai' => 'DANG_SU_DUNG', 'kich_hoat_luc' => now(), 'het_han_luc' => now()->addDays(30), 'so_buoi_con_lai' => 8]);
        $this->dangNhap($bo['pt']);
        $this->postJson('/api/v1/pt/lich-hen/'.$lich->id.'/hoan-thanh')->assertOk();
        $this->assertSame(7, $bo['don']->fresh()->so_buoi_con_lai);
        $this->assertSame(8, $donMoi->fresh()->so_buoi_con_lai);
        $this->travelBack();
    }

    public function test_qua_han_admin_dong_audit_khong_tru_va_doi_pt(): void
    {
        $bo = $this->boDuLieu();
        $slot = $this->slot($bo);
        $lich = $this->dat($bo, $slot);
        app(LichHenService::class)->thaoTac($bo['pt'], $lich->id, 'xac-nhan');
        $this->travelTo($slot->ket_thuc_luc->addHours(24));
        $this->dangNhap($bo['pt']);
        $this->postJson('/api/v1/pt/lich-hen/'.$lich->id.'/hoan-thanh')->assertConflict();
        $this->getJson('/api/v1/pt/lich-hen?trang_thai=QUA_HAN_XAC_NHAN')->assertOk()->assertJsonPath('data.0.id', $lich->id);
        $ptMoi = $this->nguoi(TaiKhoan::HUAN_LUYEN_VIEN);
        $doi = ['khach_hang_id' => $bo['khach']->hoSoKhachHang->id, 'huan_luyen_vien_id' => $ptMoi->hoSoHuanLuyenVien->id, 'phan_cong_hien_tai_id' => $bo['phanCong']->id, 'client_request_id' => (string) Str::uuid(), 'ly_do' => 'Đổi phụ trách'];
        $this->dangNhap($bo['admin']);
        $this->postJson('/api/v1/admin/phan-cong', $doi)->assertConflict();
        $this->postJson('/api/v1/admin/lich-hen/'.$lich->id.'/dong-xu-ly', ['ly_do' => 'Đã kiểm tra, thiếu xác nhận đúng hạn'])->assertOk();
        $this->postJson('/api/v1/admin/lich-hen/'.$lich->id.'/dong-xu-ly', ['ly_do' => 'Đã kiểm tra, thiếu xác nhận đúng hạn'])->assertOk();
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'DONG_BUOI_QUA_HAN')->count());
        $this->assertSame(8, $bo['don']->fresh()->so_buoi_con_lai);
        $this->postJson('/api/v1/admin/phan-cong', $doi)->assertOk();
        $this->dangNhap($bo['pt']);
        $this->getJson('/api/v1/pt/lich-hen/'.$lich->id)->assertNotFound();
        $this->travelBack();
    }

    public function test_quota_khong_am_va_rollback_khi_audit_loi(): void
    {
        $bo = $this->boDuLieu(1);
        $slot = $this->slot($bo);
        $lich = $this->dat($bo, $slot);
        app(LichHenService::class)->thaoTac($bo['pt'], $lich->id, 'xac-nhan');
        $this->travelTo($slot->ket_thuc_luc);
        $bo['don']->update(['so_buoi_con_lai' => 0]);
        $this->dangNhap($bo['pt']);
        $this->postJson('/api/v1/pt/lich-hen/'.$lich->id.'/hoan-thanh')->assertConflict();
        $bo['don']->update(['so_buoi_con_lai' => 1]);
        DB::connection()->beforeExecuting(function ($sql) {
            if (str_contains($sql, 'insert into `nhat_ky_he_thong`')) {
                throw new \RuntimeException('QA rollback lịch');
            }
        });
        try {
            app(LichHenService::class)->thaoTac($bo['pt'], $lich->id, 'hoan-thanh');
            $this->fail('Phải rollback');
        } catch (\RuntimeException $e) {
            $this->assertSame('QA rollback lịch', $e->getMessage());
        }
        $this->assertSame(1, $bo['don']->fresh()->so_buoi_con_lai);
        $this->assertSame('DA_XAC_NHAN', $lich->fresh()->trang_thai);
        $this->assertNull($lich->fresh()->tieu_hao_luc);
        $this->travelBack();
    }

    public function test_don_qua_han_va_bo_loc_ngay_viet_nam(): void
    {
        $bo = $this->boDuLieu();
        $slot = $this->slot($bo);
        $lich = $this->dat($bo, $slot);
        $this->dangNhap($bo['khach']);
        $ngay = $slot->bat_dau_luc->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d');
        $this->getJson('/api/v1/khach-hang/khung-gio?ngay='.$ngay)->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/khach-hang/lich-hen?ngay='.$ngay)->assertOk()->assertJsonPath('data.0.id', $lich->id);
        $this->travelTo($lich->han_xac_nhan_dat_lich);
        $this->getJson('/api/v1/khach-hang/khung-gio?ngay='.$ngay)->assertOk()->assertJsonPath('data.0.id', $slot->id);
        $this->assertSame(1, app(LichHenService::class)->donQuaHan());
        $this->assertSame(0, app(LichHenService::class)->donQuaHan());
        $this->assertSame('HET_HAN', $lich->fresh()->trang_thai);
        $this->travelBack();
    }

    public function test_dat_dung_moc_4_gio_va_yeu_cau_het_han_khong_chan_doi_pt(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-03T00:00:00Z'));
        $bo = $this->boDuLieu();
        $slot = $this->slot($bo, 4);
        $lich = $this->dat($bo, $slot);
        $this->assertTrue($lich->han_xac_nhan_dat_lich->equalTo($slot->bat_dau_luc->subHours(2)));
        $this->travelTo($slot->bat_dau_luc->addMinute());
        $ptMoi = $this->nguoi(TaiKhoan::HUAN_LUYEN_VIEN);
        $this->dangNhap($bo['admin']);
        $this->postJson('/api/v1/admin/phan-cong', ['khach_hang_id' => $bo['khach']->hoSoKhachHang->id, 'huan_luyen_vien_id' => $ptMoi->hoSoHuanLuyenVien->id, 'phan_cong_hien_tai_id' => $bo['phanCong']->id, 'client_request_id' => (string) Str::uuid(), 'ly_do' => 'Đổi phụ trách'])->assertOk();
        $this->assertSame('HET_HAN', $lich->fresh()->trang_thai);
        $this->travelBack();
    }

    public function test_hai_process_giu_cung_slot_va_hoan_thanh_khong_trung(): void
    {
        $bo = $this->boDuLieu(1);
        $slot = $this->slot($bo);
        $boKhac = $this->boDuLieu(1, $bo['pt']);
        DB::commit();
        $r = $this->chayHaiWorker('dat', $bo['khach']->id, $slot->id, (string) Str::uuid(), $bo['pt']->hoSoHuanLuyenVien->id);
        $this->assertTrue($r[0]['ok']);
        $this->assertTrue($r[1]['ok']);
        $this->assertSame($r[0]['id'], $r[1]['id']);
        $lich = LichHenHuanLuyen::findOrFail($r[0]['id']);
        app(LichHenService::class)->thaoTac($bo['pt'], $lich->id, 'xac-nhan');
        // Hai worker dùng thời gian thật; đưa fixture đã diễn ra trong hạn gói.
        $slot->update(['bat_dau_luc' => now()->subHours(2), 'ket_thuc_luc' => now()->subHour()]);
        $lich->update(['bat_dau_luc' => $slot->bat_dau_luc, 'ket_thuc_luc' => $slot->ket_thuc_luc]);
        $r = $this->chayHaiWorker('hoan-thanh', $bo['pt']->id, $lich->id, 'unused', $bo['pt']->hoSoHuanLuyenVien->id);
        $this->assertTrue($r[0]['ok']);
        $this->assertTrue($r[1]['ok']);
        $this->assertSame(0, $bo['don']->fresh()->so_buoi_con_lai);
        $this->assertSame(1, LichHenHuanLuyen::where('trang_thai', 'HOAN_THANH')->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'HOAN_THANH')->count());
        $slotMoi = $this->slot($bo, 12);
        $bo['don']->update(['so_buoi_con_lai' => 1]);
        $r = $this->chayHaiWorker('dat', $bo['khach']->id, $slotMoi->id, (string) Str::uuid(), $bo['pt']->hoSoHuanLuyenVien->id, $boKhac['khach']->id);
        $this->assertSame(1, count(array_filter($r, fn ($k) => $k['ok'])));
        $this->assertSame(409, collect($r)->firstWhere('ok', false)['status']);
        $this->assertSame(1, LichHenHuanLuyen::where('khung_gio_id', $slotMoi->id)->count());
        // Dữ liệu commit chỉ nằm trong DB riêng; lớp xóa DB này khi kết thúc.
    }

    private function chayHaiWorker(string $hanhDong, int $id, int $goiId, string $ma, int $khachId, ?int $nguoiKhac = null): array
    {
        $workers = [];
        $files = [];
        DB::beginTransaction();
        HoSoHuanLuyenVien::lockForUpdate()->findOrFail($khachId);
        try {
            for ($i = 0; $i < 2; $i++) {
                $ready = tempnam(sys_get_temp_dir(), 'm04-ready-');
                unlink($ready);
                $out = tempnam(sys_get_temp_dir(), 'm04-out-');
                $err = tempnam(sys_get_temp_dir(), 'm04-err-');
                $files = [...$files, $ready, $out, $err];
                $proc = proc_open([PHP_BINARY, base_path('tests/Support/m04-worker.php'), self::$tenDatabase, $hanhDong, (string) ($i === 1 && $nguoiKhac ? $nguoiKhac : $id), (string) $goiId, $ready, $ma], [0 => ['pipe', 'r'], 1 => ['file', $out, 'w'], 2 => ['file', $err, 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
                $this->assertIsResource($proc);
                fclose($pipes[0]);
                $workers[] = ['proc' => $proc, 'out' => $out, 'err' => $err, 'ready' => $ready];
            }
            $han = microtime(true) + 10;
            while ((! file_exists($workers[0]['ready']) || ! file_exists($workers[1]['ready'])) && microtime(true) < $han) {
                usleep(20000);
            }
            $this->assertFileExists($workers[0]['ready']);
            $this->assertFileExists($workers[1]['ready']);
            DB::commit();
            $ketQua = [];
            foreach ($workers as $w) {
                $this->assertSame(0, proc_close($w['proc']), file_get_contents($w['err']));
                $ketQua[] = json_decode(file_get_contents($w['out']), true, flags: JSON_THROW_ON_ERROR);
            }

            return $ketQua;
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($workers as $w) {
                if (is_resource($w['proc'])) {
                    proc_terminate($w['proc']);
                    proc_close($w['proc']);
                }
            }
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}
