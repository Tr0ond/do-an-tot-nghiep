<?php

namespace Tests\Feature;

use App\Models\DangKyGoiTap;
use App\Models\GoiTap;
use App\Models\HoSoKhachHang;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use App\Models\ThanhToan;
use App\Services\MuaGoiService;
use App\Services\PayosService;
use App\Services\PhanCongService;
use App\Services\TaiKhoanService;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PDO;
use Tests\TestCase;

class MuaGoiTest extends TestCase
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
            $tenMoi = 'kiem_tra_mua_goi_'.bin2hex(random_bytes(8));
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

    private function khach(string $vaiTro = TaiKhoan::KHACH_HANG): TaiKhoan
    {
        return app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Demo M03', 'email' => Str::uuid().'@example.test', 'password' => 'Demo123456!'], $vaiTro);
    }

    private function goi(int $buoi = 8): GoiTap
    {
        return GoiTap::create(['ten_goi' => 'Gói M03', 'gia' => 99000, 'co_chatbot' => true, 'so_luot_chatbot_moi_ngay' => 10, 'so_buoi_pt' => $buoi, 'thoi_han_ngay' => 30, 'trang_thai' => 'HOAT_DONG']);
    }

    private function don(?TaiKhoan $khach = null): DangKyGoiTap
    {
        return app(MuaGoiService::class)->taoDon($khach ?? $this->khach(), ['goi_tap_id' => $this->goi()->id, 'client_request_id' => (string) Str::uuid()]);
    }

    private function giaLap(DangKyGoiTap $don, int $tien = 99000, ?string $luc = null, string $status = 'PAID'): array
    {
        Http::swap(new Factory);
        config(['payos.client_id' => 'test-client', 'payos.api_key' => 'test-api', 'payos.checksum_key' => 'test-checksum']);
        Http::preventStrayRequests();
        $gd = $tien ? [['amount' => $tien, 'reference' => 'REF'.$don->id, 'transactionDateTime' => $luc ?? now()->toIso8601String()]] : [];
        $data = ['id' => 'link'.$don->id, 'orderCode' => $don->ma_don_payos, 'amount' => $don->gia_snapshot, 'amountPaid' => $tien, 'status' => $status, 'transactions' => $gd];
        $payos = app(PayosService::class);
        Http::fake(['https://api-merchant.payos.vn/v2/payment-requests/*' => Http::response(['code' => '00', 'data' => $data, 'signature' => $payos->chuKy($data)])]);

        return $data;
    }

    public function test_dat_mua_retry_snapshot_va_chan_don_goi_trung(): void
    {
        $khach = $this->khach();
        $goi = $this->goi();
        Auth::forgetGuards();
        $this->actingAs($khach, 'web');
        $payload = ['goi_tap_id' => $goi->id, 'client_request_id' => (string) Str::uuid(), 'gia' => 1, 'khach_hang_id' => 999];
        $id = $this->postJson('/api/v1/khach-hang/don-hang', $payload)->assertOk()->assertJsonPath('data.gia', 99000)->json('data.id');
        $this->postJson('/api/v1/khach-hang/don-hang', $payload)->assertOk()->assertJsonPath('data.id', $id);
        $goi->update(['gia' => 200000, 'so_buoi_pt' => 20]);
        $this->getJson('/api/v1/khach-hang/don-hang/'.$id)->assertOk()->assertJsonPath('data.gia', 99000)->assertJsonPath('data.so_buoi_pt', 8);
        $this->postJson('/api/v1/khach-hang/don-hang', [...$payload, 'client_request_id' => (string) Str::uuid()])->assertConflict();
        $this->postJson('/api/v1/khach-hang/don-hang', [...$payload, 'goi_tap_id' => $this->goi()->id])->assertConflict();
        $this->assertSame(1, DangKyGoiTap::count());
        $don = DangKyGoiTap::find($id);
        $this->giaLap($don);
        app(MuaGoiService::class)->dongBo($don);
        $this->postJson('/api/v1/khach-hang/don-hang', [...$payload, 'client_request_id' => (string) Str::uuid()])->assertConflict();
    }

    public function test_quyen_ownership_validation_va_return_url_khong_cap_goi(): void
    {
        $khach = $this->khach();
        $don = $this->don($khach);
        $this->getJson('/api/v1/khach-hang/don-hang')->assertUnauthorized();
        $this->actingAs($this->khach(), 'web');
        foreach (['', '/link-thanh-toan', '/dong-bo'] as $duoi) {
            $duoi ? $this->postJson('/api/v1/khach-hang/don-hang/'.$don->id.$duoi)->assertNotFound() : $this->getJson('/api/v1/khach-hang/don-hang/'.$don->id)->assertNotFound();
        }
        $this->getJson('/api/v1/admin/don-hang')->assertForbidden();
        $this->postJson('/api/v1/admin/phan-cong', [])->assertForbidden();
        Auth::forgetGuards();
        $this->actingAs($khach, 'web');
        $this->postJson('/api/v1/khach-hang/don-hang', ['client_request_id' => 'bad'])->assertUnprocessable();
        $this->getJson('/api/v1/khach-hang/don-hang/'.$don->id.'?status=PAID&code=00')->assertOk()->assertJsonPath('data.trang_thai', 'CHO_THANH_TOAN');
        $this->assertNull($don->fresh()->kich_hoat_luc);
        $khach->trang_thai = TaiKhoan::BI_KHOA;
        $khach->save();
        Auth::forgetGuards();
        $this->actingAs($khach, 'web');
        $this->getJson('/api/v1/khach-hang/don-hang')->assertForbidden();
    }

    public function test_tao_link_ky_dung_khong_gui_du_lieu_ca_nhan_va_retry(): void
    {
        $don = $this->don();
        config(['payos.client_id' => 'test-client', 'payos.api_key' => 'test-api', 'payos.checksum_key' => 'test-key', 'cache.default' => 'array']);
        Http::preventStrayRequests();
        $data = ['orderCode' => $don->ma_don_payos, 'amount' => 99000, 'paymentLinkId' => 'link'.$don->id, 'checkoutUrl' => 'https://pay.payos.vn/web/link'.$don->id];
        Http::fake(['https://api-merchant.payos.vn/v2/payment-requests' => Http::response(['code' => '00', 'data' => $data, 'signature' => app(PayosService::class)->chuKy($data)])]);
        $moi = app(MuaGoiService::class)->taoLink($don);
        $this->assertSame($data['checkoutUrl'], $moi->url_thanh_toan);
        app(MuaGoiService::class)->taoLink($moi);
        Http::assertSentCount(1);
        Http::assertSent(function ($r) use ($don) {
            $signed = array_intersect_key($r->data(), array_flip(['amount', 'cancelUrl', 'description', 'orderCode', 'returnUrl']));

            return $r['amount'] === 99000 && $r['expiredAt'] === $don->han_thanh_toan->timestamp && ! isset($r['buyerEmail']) && ! isset($r['buyerName']) && $r['signature'] === app(PayosService::class)->chuKy($signed);
        });
    }

    public function test_webhook_ky_sai_khong_ghi_du_lieu_va_retry_khong_cap_lai(): void
    {
        $don = $this->don();
        $data = $this->giaLap($don);
        $event = ['orderCode' => $don->ma_don_payos, 'paymentLinkId' => $data['id'], 'reference' => 'REF'.$don->id, 'amount' => 99000, 'currency' => 'VND', 'code' => '00'];
        $this->postJson('/api/v1/payos/webhook', ['data' => $event, 'signature' => str_repeat('a', 64)])->assertBadRequest();
        $this->assertSame(0, ThanhToan::count());
        $signed = ['data' => $event, 'signature' => app(PayosService::class)->chuKy($event)];
        $this->postJson('/api/v1/payos/webhook', $signed)->assertOk();
        $moi = $don->fresh();
        $this->assertSame('DANG_SU_DUNG', $moi->trang_thai);
        $this->assertSame(8, $moi->so_buoi_con_lai);
        $this->assertSame(30 * 24 * 3600, $moi->het_han_luc->timestamp - $moi->kich_hoat_luc->timestamp);
        $this->travel(5)->minutes();
        $this->postJson('/api/v1/payos/webhook', $signed)->assertOk();
        $this->assertTrue($moi->kich_hoat_luc->equalTo($don->fresh()->kich_hoat_luc));
        $this->assertSame(1, ThanhToan::count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'KICH_HOAT_GOI')->count());
        $this->travelBack();
    }

    public function test_thieu_thua_qua_han_va_tien_dung_nhung_thong_bao_muon(): void
    {
        foreach ([90000, 100000] as $tien) {
            $don = $this->don();
            $this->giaLap($don, $tien);
            $moi = app(MuaGoiService::class)->dongBo($don);
            $this->assertSame('CAN_DOI_SOAT', $moi->trang_thai);
            $this->assertNull($moi->kich_hoat_luc);
        }
        $don = $this->don();
        $this->travel(16)->minutes();
        $this->giaLap($don);
        $this->assertSame('CAN_DOI_SOAT', app(MuaGoiService::class)->dongBo($don)->trang_thai);
        $this->travelBack();
        $don = $this->don();
        $luc = now()->addMinutes(10)->toIso8601String();
        $this->travel(20)->minutes();
        $this->giaLap($don, 99000, $luc);
        $moi = app(MuaGoiService::class)->dongBo($don);
        $this->assertSame('DANG_SU_DUNG', $moi->trang_thai);
        $this->assertTrue($moi->kich_hoat_luc->equalTo(now()));
        $this->travelBack();
    }

    public function test_tien_thieu_sau_do_du_trong_han_chi_kich_hoat_mot_lan(): void
    {
        $don = $this->don();
        $data = $this->giaLap($don, 90000);
        $this->assertSame('CAN_DOI_SOAT', app(MuaGoiService::class)->dongBo($don)->trang_thai);
        $data['status'] = 'PAID';
        $data['amountPaid'] = 99000;
        $data['transactions'][] = ['amount' => 9000, 'reference' => 'BO-SUNG'.$don->id, 'transactionDateTime' => now()->toIso8601String()];
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['https://api-merchant.payos.vn/*' => Http::response(['code' => '00', 'data' => $data, 'signature' => app(PayosService::class)->chuKy($data)])]);
        $moi = app(MuaGoiService::class)->dongBo($don);
        $this->assertSame('DANG_SU_DUNG', $moi->trang_thai);
        $this->assertSame(2, ThanhToan::where('trang_thai', 'DA_XAC_MINH')->count());
        $this->assertSame(0, ThanhToan::where('trang_thai', 'CAN_DOI_SOAT')->count());
        app(MuaGoiService::class)->dongBo($moi);
        $this->assertSame(2, ThanhToan::count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'KICH_HOAT_GOI')->count());
    }

    public function test_link_sai_chu_ky_sai_va_tong_giao_dich_sai_khong_cap_goi(): void
    {
        $don = $this->don();
        $data = $this->giaLap($don);
        foreach ([[...$data, 'orderCode' => 123], [...$data, 'amountPaid' => 99001], [...$data, 'transactions' => []]] as $sai) {
            Http::swap(new Factory);
            Http::fake(['https://api-merchant.payos.vn/*' => Http::response(['code' => '00', 'data' => $sai, 'signature' => app(PayosService::class)->chuKy($sai)])]);
            $this->actingAs($don->khachHang->taiKhoan, 'web');
            $this->postJson('/api/v1/khach-hang/don-hang/'.$don->id.'/dong-bo')->assertStatus(503);
            $this->assertNull($don->fresh()->kich_hoat_luc);
            $this->assertSame(0, ThanhToan::count());
        }
    }

    public function test_phan_cong_retry_doi_pt_va_chan_phien_ban_cu(): void
    {
        $admin = $this->khach(TaiKhoan::ADMIN);
        $don = $this->don();
        $this->giaLap($don);
        app(MuaGoiService::class)->dongBo($don);
        $pt = $this->khach(TaiKhoan::HUAN_LUYEN_VIEN);
        $pt2 = $this->khach(TaiKhoan::HUAN_LUYEN_VIEN);
        $payload = ['khach_hang_id' => $don->khach_hang_id, 'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'phan_cong_hien_tai_id' => null, 'client_request_id' => (string) Str::uuid()];
        $this->actingAs($admin, 'web');
        $id = $this->postJson('/api/v1/admin/phan-cong', $payload)->assertOk()->json('data.id');
        $this->postJson('/api/v1/admin/phan-cong', $payload)->assertOk()->assertJsonPath('data.id', $id);
        $this->postJson('/api/v1/admin/phan-cong', [...$payload, 'client_request_id' => (string) Str::uuid(), 'huan_luyen_vien_id' => $pt2->hoSoHuanLuyenVien->id])->assertConflict();
        $moi = [...$payload, 'client_request_id' => (string) Str::uuid(), 'huan_luyen_vien_id' => $pt2->hoSoHuanLuyenVien->id, 'phan_cong_hien_tai_id' => (string) $id, 'ly_do' => 'Đổi lịch theo nhu cầu'];
        $this->postJson('/api/v1/admin/phan-cong', $moi)->assertOk();
        $this->postJson('/api/v1/admin/phan-cong', $moi)->assertOk();
        $this->postJson('/api/v1/admin/phan-cong', [...$moi, 'ly_do' => 'Lý do đã đổi'])->assertConflict();
        $this->assertNotNull(PhanCongHuanLuyenVien::find($id)->ket_thuc_luc);
        $this->assertSame(1, PhanCongHuanLuyenVien::whereNull('ket_thuc_luc')->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'DOI_PT')->count());
    }

    public function test_phan_cong_huy_lich_tuong_lai_giu_ke_hoach_duyet_va_chan_buoi_chua_xu_ly(): void
    {
        $admin = $this->khach(TaiKhoan::ADMIN);
        $don = $this->don();
        $this->giaLap($don);
        app(MuaGoiService::class)->dongBo($don);
        $pt = $this->khach(TaiKhoan::HUAN_LUYEN_VIEN);
        $pt2 = $this->khach(TaiKhoan::HUAN_LUYEN_VIEN);
        $payload = ['khach_hang_id' => $don->khach_hang_id, 'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'phan_cong_hien_tai_id' => null, 'client_request_id' => (string) Str::uuid()];
        $pc = app(PhanCongService::class)->phanCong($admin, $payload);
        $gio = now()->addDay()->startOfSecond();
        $slot = DB::table('khung_gio_huan_luyen_vien')->insertGetId(['huan_luyen_vien_id' => $pc->huan_luyen_vien_id, 'bat_dau_luc' => $gio, 'ket_thuc_luc' => $gio->copy()->addHour(), 'trang_thai' => 'HOAT_DONG']);
        $lich = DB::table('lich_hen_huan_luyen')->insertGetId(['khach_hang_id' => $don->khach_hang_id, 'huan_luyen_vien_id' => $pc->huan_luyen_vien_id, 'phan_cong_id' => $pc->id, 'khung_gio_id' => $slot, 'dang_ky_goi_tap_id' => $don->id, 'client_request_id' => (string) Str::uuid(), 'bat_dau_luc' => $gio, 'ket_thuc_luc' => $gio->copy()->addHour(), 'trang_thai' => 'DA_XAC_NHAN']);
        $mau = ['khach_hang_id' => $don->khach_hang_id, 'huan_luyen_vien_id' => $pc->huan_luyen_vien_id, 'phan_cong_id' => $pc->id, 'ten_ke_hoach' => 'QA'];
        $cho = DB::table('ke_hoach_tap')->insertGetId([...$mau, 'trang_thai' => 'CHO_DUYET']);
        $duyet = DB::table('ke_hoach_tap')->insertGetId([...$mau, 'trang_thai' => 'DANG_AP_DUNG']);
        $doi = [...$payload, 'huan_luyen_vien_id' => $pt2->hoSoHuanLuyenVien->id, 'phan_cong_hien_tai_id' => $pc->id, 'client_request_id' => (string) Str::uuid(), 'ly_do' => 'Khách yêu cầu đổi PT'];
        $this->travelTo($gio->copy()->addMinute());
        $this->actingAs($admin, 'web');
        $this->postJson('/api/v1/admin/phan-cong', $doi)->assertConflict();
        $this->assertNull($pc->fresh()->ket_thuc_luc);
        $this->assertSame('CHO_DUYET', DB::table('ke_hoach_tap')->find($cho)->trang_thai);
        $this->travelBack();
        $this->postJson('/api/v1/admin/phan-cong', $doi)->assertOk();
        $this->assertSame('DA_HUY', DB::table('lich_hen_huan_luyen')->find($lich)->trang_thai);
        $this->assertNull(DB::table('lich_hen_huan_luyen')->find($lich)->khung_gio_dang_giu_id);
        $this->assertSame('DA_HUY', DB::table('ke_hoach_tap')->find($cho)->trang_thai);
        $this->assertSame('DANG_AP_DUNG', DB::table('ke_hoach_tap')->find($duyet)->trang_thai);
    }

    public function test_cap_goi_rollback_khi_audit_loi(): void
    {
        $don = $this->don();
        $this->giaLap($don);
        DB::connection()->beforeExecuting(function ($sql) {
            if (str_contains($sql, 'insert into `nhat_ky_he_thong`')) {
                throw new \RuntimeException('QA rollback');
            }
        });
        try {
            app(MuaGoiService::class)->dongBo($don);
            $this->fail('Phải rollback');
        } catch (\RuntimeException $e) {
            $this->assertSame('QA rollback', $e->getMessage());
        }
        $this->assertSame(0, ThanhToan::count());
        $this->assertNull($don->fresh()->kich_hoat_luc);
        // Callback chỉ trên connection này; test khác dùng connection được purge.
    }

    public function test_hai_process_tao_don_va_cap_goi_khong_trung(): void
    {
        $khach = $this->khach();
        $goi = $this->goi();
        $ma = (string) Str::uuid();
        DB::commit();
        try {
            $ketQua = $this->chayHaiWorker('tao', $khach->id, $goi->id, $ma, $khach->hoSoKhachHang->id);
            $this->assertTrue($ketQua[0]['ok']);
            $this->assertTrue($ketQua[1]['ok']);
            $this->assertSame($ketQua[0]['id'], $ketQua[1]['id']);
            $don = DangKyGoiTap::find($ketQua[0]['id']);
            $ketQua = $this->chayHaiWorker('dong-bo', $don->id, 0, 'unused', $don->khach_hang_id);
            $this->assertTrue($ketQua[0]['ok']);
            $this->assertTrue($ketQua[1]['ok']);
            $this->assertSame(1, ThanhToan::count());
            $this->assertSame(1, DangKyGoiTap::where('trang_thai', 'DANG_SU_DUNG')->count());
            $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'KICH_HOAT_GOI')->count());
        } finally {
            $cacDon = DangKyGoiTap::where('khach_hang_id', $khach->hoSoKhachHang->id)->pluck('id');
            DB::table('nhat_ky_he_thong')->where('loai_tai_nguyen', 'dang_ky_goi_tap')->whereIn('tai_nguyen_id', $cacDon)->delete();
            ThanhToan::whereIn('dang_ky_goi_tap_id', $cacDon)->delete();
            DangKyGoiTap::whereIn('id', $cacDon)->delete();
            $goi->delete();
            $khach->hoSoKhachHang->delete();
            $khach->delete();
        }
    }

    private function chayHaiWorker(string $hanhDong, int $id, int $goiId, string $ma, int $khachId): array
    {
        $workers = [];
        $files = [];
        DB::beginTransaction();
        HoSoKhachHang::lockForUpdate()->findOrFail($khachId);
        try {
            for ($i = 0; $i < 2; $i++) {
                $ready = tempnam(sys_get_temp_dir(), 'm03-ready-');
                unlink($ready);
                $out = tempnam(sys_get_temp_dir(), 'm03-out-');
                $err = tempnam(sys_get_temp_dir(), 'm03-err-');
                $files = [...$files, $ready, $out, $err];
                $proc = proc_open([PHP_BINARY, base_path('tests/Support/m03-worker.php'), self::$tenDatabase, $hanhDong, (string) $id, (string) $goiId, $ready, $ma], [0 => ['pipe', 'r'], 1 => ['file', $out, 'w'], 2 => ['file', $err, 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
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

    public function test_hoan_tien_thu_cong_retry_khong_kich_hoat_goi(): void
    {
        $don = $this->don();
        $this->giaLap($don, 100000);
        app(MuaGoiService::class)->dongBo($don);
        $id = ThanhToan::first()->id;
        $payload = ['so_tien_hoan' => 100000, 'ma_hoan_tien' => 'REFUND-QA', 'ly_do' => 'Đã hoàn đủ tiền theo ngân hàng'];
        $this->actingAs($this->khach(TaiKhoan::ADMIN), 'web');
        $this->patchJson('/api/v1/admin/thanh-toan/'.$id.'/doi-soat', $payload)->assertOk();
        $this->patchJson('/api/v1/admin/thanh-toan/'.$id.'/doi-soat', $payload)->assertOk();
        app(MuaGoiService::class)->dongBo($don);
        $this->assertNull($don->fresh()->kich_hoat_luc);
        $this->assertSame('DA_HOAN_TIEN', ThanhToan::find($id)->trang_thai);
    }
}
