<?php

namespace Tests\Feature;

use App\Models\BaiTap;
use App\Models\GoiTap;
use App\Models\HoSoKhachHang;
use App\Models\KetQuaBuoiPt;
use App\Models\KhungGioHuanLuyenVien;
use App\Models\LichHenHuanLuyen;
use App\Models\TaiKhoan;
use App\Services\KetQuaBuoiPtService;
use App\Services\LichHenService;
use App\Services\MuaGoiService;
use App\Services\PhanCongService;
use App\Services\TaiKhoanService;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PDO;
use Tests\TestCase;

class KetQuaBuoiPtTest extends TestCase
{
    private static ?string $tenDatabase = null;

    private static ?PDO $pdoMayChu = null;

    private bool $daCommit = false;

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
            $tenMoi = 'kiem_tra_ket_qua_pt_'.bin2hex(random_bytes(8));
            self::$pdoMayChu->exec('CREATE DATABASE `'.$tenMoi.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            self::$tenDatabase = $tenMoi;
        }
        config(['database.connections.mysql.database' => self::$tenDatabase, 'database.connections.mysql.url' => null]);
        DB::purge('mysql');
        DB::setDefaultConnection('mysql');
        $this->assertSame(self::$tenDatabase, DB::selectOne('SELECT DATABASE() AS ten')->ten);
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'mysql', '--force' => true]));
        DB::beginTransaction();
        $this->travelTo(now()->startOfSecond());
    }

    protected function tearDown(): void
    {
        if (DB::connection()->transactionLevel() > 0) {
            DB::rollBack(0);
        }
        Event::forget('eloquent.saved: '.KetQuaBuoiPt::class);
        $this->travelBack();
        if ($this->daCommit) {
            $this->assertSame(self::$tenDatabase, DB::selectOne('SELECT DATABASE() AS ten')->ten);
            Artisan::call('migrate:fresh', ['--force' => true]);
        }
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$tenDatabase !== null && preg_match('/^kiem_tra_ket_qua_pt_[a-f0-9]{16}$/D', self::$tenDatabase)) {
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

    private function buoi(): array
    {
        $bo = $this->boDuLieu();
        $lich = $this->dat($bo);
        app(LichHenService::class)->thaoTac($bo['pt'], $lich->id, 'xac-nhan');
        $this->travelTo($lich->ket_thuc_luc->addMinute());
        $nhom = DB::table('nhom_co')->insertGetId(['ma_nhom_co' => 'nguc', 'ten_nhom_co' => 'Ngực', 'ten_nguon' => 'chest', 'trang_thai' => 'HOAT_DONG']);
        $bai = BaiTap::create(['nhom_co_id' => $nhom, 'ten_bai_tap' => 'Chống đẩy', 'trang_thai' => 'HOAT_DONG']);
        $this->dangNhap($bo['pt']);

        return [...$bo, 'lich' => $lich, 'bai' => $bai];
    }

    private function payload(array $bo, ?string $version = null): array
    {
        return ['updated_at' => $version, 'ghi_chu' => 'Tập thực tế', 'nhan_xet' => 'Giữ kỹ thuật',
            'bai_tap' => [['bai_tap_id' => $bo['bai']->id, 'hiep_tap' => [
                ['so_lan_lap' => 10, 'khoi_luong_kg' => null, 'nghi_giay' => 60],
                ['so_lan_lap' => 8, 'khoi_luong_kg' => '0', 'nghi_giay' => 0],
            ]]]];
    }

    private function url(array $bo): string
    {
        return '/api/v1/pt/lich-hen/'.$bo['lich']->id.'/ket-qua';
    }

    public function test_get_khong_tao_du_lieu_va_scope_kh_pt_admin(): void
    {
        $bo = $this->buoi();
        $url = $this->url($bo);
        $this->getJson($url)->assertOk()->assertJsonPath('data.ket_qua', null)->assertJsonPath('data.co_the_ghi', true)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertDatabaseCount('ket_qua_buoi_pt', 0);
        $this->dangNhap($bo['khach']);
        $this->getJson(str_replace('/pt/', '/khach-hang/', $url))->assertOk()->assertJsonPath('data.co_the_ghi', false);
        $this->putJson($url, $this->payload($bo))->assertForbidden();
        $this->dangNhap($this->nguoi());
        $this->getJson(str_replace('/pt/', '/khach-hang/', $url))->assertNotFound();
        $this->dangNhap($this->nguoi(TaiKhoan::HUAN_LUYEN_VIEN));
        $this->getJson($url)->assertNotFound();
        $this->putJson($url, $this->payload($bo))->assertNotFound();
        $this->dangNhap($bo['admin']);
        $this->getJson($url)->assertForbidden();
    }

    public function test_luu_retry_phien_ban_snapshot_khong_tru_buoi(): void
    {
        $bo = $this->buoi();
        $url = $this->url($bo);
        $p = $this->payload($bo);
        $r = $this->putJson($url, $p)->assertOk()->assertJsonPath('data.ket_qua.bai_tap.0.ten_bai_tap', 'Chống đẩy');
        $version = $r->json('data.ket_qua.updated_at');
        $this->putJson($url, $p)->assertOk()->assertJsonPath('data.ket_qua.updated_at', $version);
        $this->assertDatabaseCount('ket_qua_buoi_pt', 1);
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'LUU_KET_QUA_PT')->count());
        $p['ghi_chu'] = 'Nháp mới';
        $this->putJson($url, $p)->assertConflict();
        $bo['bai']->update(['ten_bai_tap' => 'Tên catalog mới', 'trang_thai' => 'NGUNG_HIEN_THI']);
        $p['updated_at'] = $version;
        $r = $this->putJson($url, $p)->assertOk()->assertJsonPath('data.ket_qua.bai_tap.0.ten_bai_tap', 'Chống đẩy');
        $this->assertNotSame($version, $r->json('data.ket_qua.updated_at'));
        $this->assertSame(8, $bo['don']->fresh()->so_buoi_con_lai);
        $this->assertSame('DA_XAC_NHAN', $bo['lich']->fresh()->trang_thai);
        $this->dangNhap($bo['khach']);
        $this->getJson(str_replace('/pt/', '/khach-hang/', $url))->assertOk()->assertJsonPath('data.ket_qua.ghi_chu', 'Nháp mới');
    }

    public function test_chot_bat_bien_retry_va_xac_nhan_lich_chi_tru_mot_luot(): void
    {
        $bo = $this->buoi();
        $url = $this->url($bo);
        $p = $this->payload($bo);
        $version = $this->putJson($url, $p)->assertOk()->json('data.ket_qua.updated_at');
        $this->postJson($url.'/chot', ['updated_at' => $version])->assertOk()->assertJsonPath('data.co_the_ghi', false);
        $this->postJson($url.'/chot', ['updated_at' => $version])->assertOk();
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'CHOT_KET_QUA_PT')->count());
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $bo['khach']->id)->where('data->tieu_de', 'PT đã ghi kết quả buổi tập')->count());
        $this->assertSame(8, $bo['don']->fresh()->so_buoi_con_lai);
        $this->putJson($url, $p)->assertOk();
        $p['nhan_xet'] = 'Không được sửa';
        $this->putJson($url, $p)->assertConflict();
        $s = app(LichHenService::class);
        $s->thaoTac($bo['pt'], $bo['lich']->id, 'hoan-thanh');
        $s->thaoTac($bo['pt'], $bo['lich']->id, 'hoan-thanh');
        $this->assertSame(7, $bo['don']->fresh()->so_buoi_con_lai);
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'HOAN_THANH')->count());
    }

    public function test_chua_den_gio_qua_han_vang_mat_va_chot_thieu_hiep(): void
    {
        $bo = $this->buoi();
        $url = $this->url($bo);
        $p = $this->payload($bo);
        $this->travelTo($bo['lich']->bat_dau_luc->subSecond());
        $this->putJson($url, $p)->assertConflict();
        $this->travelTo($bo['lich']->bat_dau_luc);
        $p['bai_tap'][0]['hiep_tap'] = [];
        $version = $this->putJson($url, $p)->assertOk()->json('data.ket_qua.updated_at');
        $this->postJson($url.'/chot', ['updated_at' => $version])->assertConflict();
        $this->travelTo($bo['lich']->ket_thuc_luc);
        $this->postJson($url.'/chot', ['updated_at' => $version])->assertUnprocessable()->assertJsonValidationErrors('bai_tap');
        $this->travelTo($bo['lich']->ket_thuc_luc->addHours(24));
        $this->getJson($url)->assertOk()->assertJsonPath('data.co_the_ghi', false);
        $this->putJson($url, $this->payload($bo, $version))->assertConflict();
        $this->travelTo($bo['lich']->ket_thuc_luc->addMinute());
        app(LichHenService::class)->thaoTac($bo['pt'], $bo['lich']->id, 'vang-mat', 'Không đến tập');
        $this->putJson($url, $this->payload($bo, $version))->assertConflict();
        $this->assertSame(8, $bo['don']->fresh()->so_buoi_con_lai);
    }

    public function test_validation_cam_truong_gia_bai_ngung_hiep_sai_va_trung_bai(): void
    {
        $bo = $this->buoi();
        $url = $this->url($bo);
        $p = $this->payload($bo);
        $this->putJson($url, [...$p, 'khach_hang_id' => 1])->assertUnprocessable();
        $sai = $p;
        $sai['bai_tap'][0]['ten_bai_tap'] = 'Tên tự gửi';
        $this->putJson($url, $sai)->assertUnprocessable();
        foreach ([['so_lan_lap', 0], ['so_lan_lap', 1.5], ['nghi_giay', -1], ['khoi_luong_kg', '1.123'], ['khoi_luong_kg', 1001]] as [$k, $v]) {
            $sai = $p;
            $sai['bai_tap'][0]['hiep_tap'][0][$k] = $v;
            $this->putJson($url, $sai)->assertUnprocessable();
        }
        $sai = $p;
        $sai['bai_tap'][] = $p['bai_tap'][0];
        $this->putJson($url, $sai)->assertUnprocessable();
        $bo['bai']->update(['trang_thai' => 'NGUNG_HIEN_THI']);
        $this->putJson($url, $p)->assertUnprocessable();
        $this->assertDatabaseCount('ket_qua_buoi_pt', 0);
    }

    public function test_thu_hoi_pt_khong_lo_du_lieu_kh_giu_lich_su(): void
    {
        $bo = $this->buoi();
        $url = $this->url($bo);
        $this->putJson($url, $this->payload($bo))->assertOk();
        $bo['phanCong']->update(['ket_thuc_luc' => now()]);
        $this->getJson($url)->assertNotFound();
        $this->putJson($url, $this->payload($bo))->assertNotFound();
        $this->dangNhap($bo['khach']);
        $bo['don']->update(['trang_thai' => 'HET_HAN', 'het_han_luc' => now()->subSecond()]);
        $this->getJson(str_replace('/pt/', '/khach-hang/', $url))->assertOk()->assertJsonPath('data.ket_qua.nhan_xet', 'Giữ kỹ thuật');
    }

    public function test_loi_sau_khi_luu_rollback_ket_qua_audit_va_luot(): void
    {
        $bo = $this->buoi();
        KetQuaBuoiPt::saved(fn () => throw new \RuntimeException('rollback QA'));
        try {
            app(KetQuaBuoiPtService::class)->luu($bo['pt'], $bo['lich']->id, $this->payload($bo));
            $this->fail('Phải rollback.');
        } catch (\RuntimeException $e) {
            $this->assertSame('rollback QA', $e->getMessage());
        }
        $this->assertDatabaseCount('ket_qua_buoi_pt', 0);
        $this->assertSame(0, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'LUU_KET_QUA_PT')->count());
        $this->assertSame(8, $bo['don']->fresh()->so_buoi_con_lai);
    }

    public function test_hai_process_luu_cung_nhap_va_chot_khong_trung(): void
    {
        $bo = $this->buoi();
        $this->travelBack();
        $bo['lich']->update(['bat_dau_luc' => now()->subHours(2), 'ket_thuc_luc' => now()->subHour()]);
        DB::commit();
        $this->daCommit = true;
        $p = $this->payload($bo);
        $r = $this->haiWorker($bo, 'luu', [$p, $p]);
        $this->assertTrue($r[0]['ok']);
        $this->assertTrue($r[1]['ok']);
        $this->assertSame($r[0]['id'], $r[1]['id']);
        $this->assertDatabaseCount('ket_qua_buoi_pt', 1);
        $version = KetQuaBuoiPt::firstOrFail()->updated_at->format('Y-m-d H:i:s.u');
        $p['updated_at'] = $version;
        $p['ghi_chu'] = 'Cửa sổ A';
        $p2 = $p;
        $p2['ghi_chu'] = 'Cửa sổ B';
        $r = $this->haiWorker($bo, 'luu', [$p, $p2]);
        $this->assertSame(1, count(array_filter($r, fn ($x) => $x['ok'])));
        $this->assertSame(409, collect($r)->firstWhere('ok', false)['status']);
        $version = KetQuaBuoiPt::firstOrFail()->updated_at->format('Y-m-d H:i:s.u');
        $r = $this->haiWorker($bo, 'chot', [['updated_at' => $version], ['updated_at' => $version]]);
        $this->assertTrue($r[0]['ok']);
        $this->assertTrue($r[1]['ok']);
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'CHOT_KET_QUA_PT')->count());
        $this->assertSame(8, $bo['don']->fresh()->so_buoi_con_lai);
    }

    private function haiWorker(array $bo, string $hd, array $payloads): array
    {
        $workers = [];
        $files = [];
        DB::beginTransaction();
        HoSoKhachHang::lockForUpdate()->findOrFail($bo['khach']->hoSoKhachHang->id);
        try {
            foreach ($payloads as $p) {
                $ready = tempnam(sys_get_temp_dir(), 'kqpt-ready-');
                unlink($ready);
                $out = tempnam(sys_get_temp_dir(), 'kqpt-out-');
                $err = tempnam(sys_get_temp_dir(), 'kqpt-err-');
                $files = [...$files, $ready, $out, $err];
                $proc = proc_open([PHP_BINARY, base_path('tests/Support/ket-qua-pt-worker.php'), self::$tenDatabase, $hd, (string) $bo['pt']->id, (string) $bo['lich']->id, $ready, json_encode($p, JSON_THROW_ON_ERROR)],
                    [0 => ['pipe', 'r'], 1 => ['file', $out, 'w'], 2 => ['file', $err, 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
                $this->assertIsResource($proc);
                fclose($pipes[0]);
                $workers[] = compact('proc', 'out', 'err', 'ready');
            }
            $han = microtime(true) + 10;
            while ((! file_exists($workers[0]['ready']) || ! file_exists($workers[1]['ready'])) && microtime(true) < $han) {
                usleep(20000);
            }
            $this->assertFileExists($workers[0]['ready']);
            $this->assertFileExists($workers[1]['ready']);
            DB::commit();
            $r = [];
            foreach ($workers as $w) {
                $this->assertSame(0, proc_close($w['proc']), file_get_contents($w['err']));
                $r[] = json_decode(file_get_contents($w['out']), true, flags: JSON_THROW_ON_ERROR);
            }

            return $r;
        } finally {
            if (DB::transactionLevel()) {
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
