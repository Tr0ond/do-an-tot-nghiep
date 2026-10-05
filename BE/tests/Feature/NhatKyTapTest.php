<?php

namespace Tests\Feature;

use App\Models\BaiTap;
use App\Models\BaiTapTrongPhien;
use App\Models\HiepTap;
use App\Models\KeHoachTap;
use App\Models\LichTap;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use App\Services\KeHoachTapService;
use App\Services\NhatKyTapService;
use App\Services\TaiKhoanService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Tests\TestCase;

class NhatKyTapTest extends TestCase
{
    private static ?string $db = null;

    private static ?PDO $pdo = null;

    private TaiKhoan $kh;

    private TaiKhoan $pt;

    private TaiKhoan $admin;

    private PhanCongHuanLuyenVien $pc;

    private KeHoachTap $k;

    private int $khId;

    private int $baiId;

    private bool $daCommit = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (self::$db === null) {
            $cfg = config('database.connections.mysql');
            $cfg['database'] = null;
            $cfg['url'] = null;
            config(['database.connections.may_chu_nhat_ky' => $cfg]);
            self::$pdo = DB::connection('may_chu_nhat_ky')->getPdo();
            self::$db = 'kiem_tra_nhat_ky_'.bin2hex(random_bytes(8));
            self::$pdo->exec('CREATE DATABASE `'.self::$db.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
        config(['database.connections.mysql.database' => self::$db, 'database.connections.mysql.url' => null, 'database.default' => 'mysql']);
        DB::purge('mysql');
        $this->assertSame(self::$db, DB::selectOne('SELECT DATABASE() AS ten')->ten);
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        DB::beginTransaction();
        $this->kh = $this->taiKhoan(TaiKhoan::KHACH_HANG);
        $this->pt = $this->taiKhoan(TaiKhoan::HUAN_LUYEN_VIEN);
        $this->admin = $this->taiKhoan(TaiKhoan::ADMIN);
        $this->khId = $this->kh->hoSoKhachHang->id;
        $this->pc = PhanCongHuanLuyenVien::create(['khach_hang_id' => $this->khId, 'huan_luyen_vien_id' => $this->pt->hoSoHuanLuyenVien->id, 'nguoi_phan_cong_id' => $this->admin->id, 'bat_dau_luc' => now()]);
        $nhom = DB::table('nhom_co')->insertGetId(['ma_nhom_co' => 'demo', 'ten_nhom_co' => 'Ngực', 'ten_nguon' => 'chest', 'trang_thai' => 'HOAT_DONG']);
        $this->baiId = BaiTap::create(['nhom_co_id' => $nhom, 'ten_bai_tap' => 'Chống đẩy', 'trang_thai' => 'HOAT_DONG'])->id;
        $s = app(KeHoachTapService::class);
        $this->k = $s->tao($this->kh, $this->khId, ['ten_ke_hoach' => 'Tự tập toàn thân', 'muc_tieu' => null, 'so_ngay_tap' => 1, 'giao_an_mau_id' => null, 'client_request_id' => (string) Str::uuid(),
            'bai_tap' => [['bai_tap_id' => $this->baiId, 'ngay_thu' => 1, 'thu_tu' => 1, 'so_hiep' => 3, 'so_lan_lap' => 12, 'nghi_giay' => 60, 'ghi_chu' => null, 'muc_ta_kg' => '10.50']]]);
        $this->k = $s->thaoTac($this->kh, $this->k->id, 'ap-dung', $this->k->updated_at->format('Y-m-d H:i:s.u'));
        $this->doiNguoi($this->kh);
    }

    protected function tearDown(): void
    {
        foreach ([BaiTapTrongPhien::class, HiepTap::class, LichTap::class] as $model) {
            Event::forget('eloquent.creating: '.$model);
            Event::forget('eloquent.updating: '.$model);
        }
        $this->travelBack();
        if (DB::transactionLevel()) {
            DB::rollBack(0);
        }
        if ($this->daCommit) {
            $this->assertSame(self::$db, DB::selectOne('SELECT DATABASE() AS ten')->ten);
            Artisan::call('migrate:fresh', ['--force' => true]);
        }
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$db !== null && preg_match('/^kiem_tra_nhat_ky_[a-f0-9]{16}$/D', self::$db)) {
            self::$pdo->exec('DROP DATABASE `'.self::$db.'`');
        }
        self::$db = null;
        self::$pdo = null;
        parent::tearDownAfterClass();
    }

    private function taiKhoan(string $vaiTro): TaiKhoan
    {
        return app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Người kiểm thử', 'email' => Str::uuid().'@example.test', 'password' => 'Demo123456!'], $vaiTro);
    }

    public function test_mobile_bearer_giao_an_nhat_ky_nhan_xet_chi_so_va_thu_hoi_phan_cong(): void
    {
        $this->travelTo(now()->addSecond()->startOfSecond());
        Auth::forgetGuards();
        $khToken = $this->postJson('/api/v1/mobile/dang-nhap', ['email' => $this->kh->email, 'password' => 'Demo123456!', 'ten_thiet_bi' => 'Android MB3'])->assertOk()->json('data.access_token');
        Auth::forgetGuards();
        $ptToken = $this->postJson('/api/v1/mobile/dang-nhap', ['email' => $this->pt->email, 'password' => 'Demo123456!', 'ten_thiet_bi' => 'Android MB3'])->assertOk()->json('data.access_token');
        $voiToken = function ($token): void {
            Auth::forgetGuards();
            $this->withHeaders(['Authorization' => 'Bearer '.$token]);
        };
        $voiToken($khToken);
        $this->getJson('/api/v1/bai-tap?per_page=12')->assertOk()->assertJsonPath('data.0.id', $this->baiId);
        $voiToken($khToken);
        $body = $this->noiDung();
        $l = $this->postJson('/api/v1/khach-hang/lich-tap', $body)->assertCreated()->json('data');
        $voiToken($khToken);
        $this->postJson('/api/v1/khach-hang/lich-tap', $body)->assertOk()->assertJsonPath('data.id', $l['id']);
        $voiToken($khToken);
        $l = $this->postJson('/api/v1/khach-hang/lich-tap/'.$l['id'].'/bat-dau', ['updated_at' => $l['updated_at']])->assertOk()->json('data');
        $this->assertSame([], $l['bai_tap'][0]['hiep_tap']);
        $voiToken($khToken);
        $l = $this->putJson('/api/v1/khach-hang/lich-tap/'.$l['id'], $this->ketQua($l))->assertOk()->json('data');
        $voiToken($khToken);
        $l = $this->postJson('/api/v1/khach-hang/lich-tap/'.$l['id'].'/hoan-thanh', ['updated_at' => $l['updated_at']])->assertOk()->json('data');
        $voiToken($ptToken);
        $nhanXet = ['noi_dung' => 'Kết quả kiểm thử mobile', 'client_request_id' => (string) Str::uuid()];
        $this->postJson('/api/v1/pt/lich-tap/'.$l['id'].'/nhan-xet', $nhanXet)->assertOk()->assertJsonCount(1, 'data.nhan_xet');
        $voiToken($ptToken);
        $this->postJson('/api/v1/pt/lich-tap/'.$l['id'].'/nhan-xet', $nhanXet)->assertOk()->assertJsonCount(1, 'data.nhan_xet');
        $voiToken($khToken);
        $c = ['ngay_ghi' => $body['ngay_tap'], 'can_nang_kg' => 70, 'chieu_cao_cm' => 175, 'ghi_chu' => null];
        $ban = $this->postJson('/api/v1/khach-hang/chi-so-co-the', $c)->assertCreated()->assertJsonPath('data.bmi', 22.86)->json('data');
        $voiToken($ptToken);
        $this->getJson('/api/v1/pt/hoc-vien/'.$this->khId.'/chi-so-co-the')->assertOk()->assertJsonPath('data.moi_nhat.id', $ban['id']);
        $voiToken($ptToken);
        $this->putJson('/api/v1/khach-hang/chi-so-co-the/'.$ban['id'], [...$c, 'updated_at' => $ban['updated_at']])->assertForbidden();
        $voiToken($ptToken);
        $d = ['ten_ke_hoach' => 'Đề xuất mobile', 'muc_tieu' => null, 'so_ngay_tap' => 1, 'giao_an_mau_id' => null, 'client_request_id' => (string) Str::uuid(), 'bai_tap' => [['bai_tap_id' => $this->baiId, 'ngay_thu' => 1, 'thu_tu' => 1, 'so_hiep' => 3, 'so_lan_lap' => 10, 'nghi_giay' => 60, 'ghi_chu' => null, 'muc_ta_kg' => null]]];
        $k = $this->postJson('/api/v1/pt/hoc-vien/'.$this->khId.'/ke-hoach', $d)->assertCreated()->json('data');
        $voiToken($ptToken);
        $k = $this->postJson('/api/v1/pt/ke-hoach/'.$k['id'].'/gui', ['updated_at' => $k['updated_at']])->assertOk()->json('data');
        $voiToken($khToken);
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$k['id'].'/xac-nhan', ['updated_at' => $k['updated_at']])->assertOk()->assertJsonPath('data.trang_thai', 'DANG_AP_DUNG');
        $this->assertSame('LUU_TRU', $this->k->fresh()->trang_thai);
        $this->assertDatabaseCount('dang_ky_goi_tap', 0);
        $this->assertDatabaseCount('lich_hen_huan_luyen', 0);
        $this->pc->update(['ket_thuc_luc' => now()]);
        $voiToken($ptToken);
        $this->getJson('/api/v1/pt/lich-tap/'.$l['id'])->assertNotFound();
        $voiToken($ptToken);
        $this->getJson('/api/v1/pt/hoc-vien/'.$this->khId.'/chi-so-co-the')->assertNotFound();
        $voiToken($khToken);
        $this->getJson('/api/v1/khach-hang/lich-tap/'.$l['id'])->assertOk()->assertJsonCount(1, 'data.nhan_xet');
    }

    private function doiNguoi(TaiKhoan $nguoi): void
    {
        Auth::forgetGuards();
        $this->actingAs($nguoi, 'web');
    }

    private function noiDung(array $them = []): array
    {
        return ['ke_hoach_tap_id' => $this->k->id, 'ngay_thu' => 1, 'ngay_tap' => now('Asia/Ho_Chi_Minh')->toDateString(), 'client_request_id' => (string) Str::uuid(), ...$them];
    }

    private function tao(array $them = []): array
    {
        return $this->postJson('/api/v1/khach-hang/lich-tap', $this->noiDung($them))->assertCreated()->json('data');
    }

    private function thaoTac(array $l, string $hanhDong): array
    {
        return $this->postJson('/api/v1/khach-hang/lich-tap/'.$l['id'].'/'.$hanhDong, ['updated_at' => $l['updated_at']])->assertOk()->json('data');
    }

    private function ketQua(array $l): array
    {
        return ['updated_at' => $l['updated_at'], 'ghi_chu' => 'Tập có kiểm soát', 'bai_tap' => array_map(fn ($b) => ['id' => $b['id'], 'hiep_tap' => [
            ['so_lan_lap' => 12, 'khoi_luong_kg' => '10.50', 'nghi_giay' => 60], ['so_lan_lap' => 8, 'khoi_luong_kg' => null, 'nghi_giay' => 0],
            ['so_lan_lap' => 10, 'khoi_luong_kg' => 0, 'nghi_giay' => 30]]], $l['bai_tap'])];
    }

    private function hoanThanh(): array
    {
        $l = $this->thaoTac($this->tao(), 'bat-dau');
        $l = $this->putJson('/api/v1/khach-hang/lich-tap/'.$l['id'], $this->ketQua($l))->assertOk()->json('data');

        return $this->thaoTac($l, 'hoan-thanh');
    }

    public function test_kh_khong_goi_khong_pt_tap_luu_hoan_thanh_retry_va_thong_ke(): void
    {
        $this->pc->update(['ket_thuc_luc' => now()]);
        $body = $this->noiDung();
        $l = $this->postJson('/api/v1/khach-hang/lich-tap', $body)->assertCreated()->json('data');
        $this->postJson('/api/v1/khach-hang/lich-tap', $body)->assertOk()->assertJsonPath('data.id', $l['id']);
        $this->postJson('/api/v1/khach-hang/lich-tap', $this->noiDung())->assertConflict();
        $this->postJson('/api/v1/khach-hang/lich-tap', [...$body, 'ngay_thu' => 2])->assertConflict();
        $p = $this->thaoTac($l, 'bat-dau');
        $this->thaoTac($l, 'bat-dau');
        $this->assertDatabaseCount('phien_tap', 1);
        $this->assertDatabaseCount('hiep_tap', 0);
        $this->postJson('/api/v1/khach-hang/lich-tap/'.$l['id'].'/hoan-thanh', ['updated_at' => $p['updated_at']])->assertUnprocessable();
        $duLieu = $this->ketQua($p);
        $r = $this->putJson('/api/v1/khach-hang/lich-tap/'.$l['id'], $duLieu)->assertOk()->json('data');
        $this->putJson('/api/v1/khach-hang/lich-tap/'.$l['id'], $duLieu)->assertOk()->assertJsonPath('data.updated_at', $r['updated_at']);
        $this->assertSame(null, $r['bai_tap'][0]['hiep_tap'][1]['khoi_luong_kg']);
        $this->assertSame('0.00', $r['bai_tap'][0]['hiep_tap'][2]['khoi_luong_kg']);
        $done = $this->thaoTac($r, 'hoan-thanh');
        $this->assertSame($done['updated_at'], $this->thaoTac($r, 'hoan-thanh')['updated_at']);
        $this->putJson('/api/v1/khach-hang/lich-tap/'.$l['id'], $duLieu)->assertConflict();
        $this->postJson('/api/v1/khach-hang/lich-tap/'.$l['id'].'/huy', ['updated_at' => $done['updated_at']])->assertConflict();
        $today = now('Asia/Ho_Chi_Minh')->toDateString();
        $this->getJson('/api/v1/khach-hang/lich-tap?tu_ngay='.$today.'&den_ngay='.$today)->assertOk()
            ->assertJsonPath('meta.thong_ke.so_buoi', 1)->assertJsonPath('meta.thong_ke.so_hiep', 3)->assertJsonPath('meta.thong_ke.so_lan', 30)
            ->assertJsonPath('meta.thong_ke.tong_khoi_luong_kg', 126)->assertJsonPath('meta.thong_ke.so_hiep_co_ta', 2)
            ->assertJsonPath('meta.thong_ke.theo_bai.0.muc_ta_cao_nhat', 10.5);
        $this->assertDatabaseCount('lich_hen_huan_luyen', 0);
        $this->assertDatabaseCount('dang_ky_goi_tap', 0);
    }

    public function test_pt_len_lich_nhung_khong_ghi_ket_qua_thay_kh(): void
    {
        $this->doiNguoi($this->pt);
        $l = $this->postJson('/api/v1/pt/hoc-vien/'.$this->khId.'/lich-tap', $this->noiDung())->assertCreated()->json('data');
        $this->assertFalse($l['co_the_bat_dau']);
        $this->postJson('/api/v1/pt/lich-tap/'.$l['id'].'/bat-dau', ['updated_at' => $l['updated_at']])->assertNotFound();
        $this->putJson('/api/v1/khach-hang/lich-tap/'.$l['id'], ['updated_at' => $l['updated_at'], 'ghi_chu' => null, 'bai_tap' => []])->assertForbidden();
        $this->doiNguoi($this->kh);
        $this->thaoTac($l, 'bat-dau');
    }

    public function test_quyen_tai_nguyen_va_thu_hoi_pt(): void
    {
        $l = $this->hoanThanh();
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN] as $role) {
            $this->doiNguoi($this->taiKhoan($role));
            $goc = $role === TaiKhoan::KHACH_HANG ? 'khach-hang' : 'pt';
            $this->getJson('/api/v1/'.$goc.'/lich-tap/'.$l['id'])->assertNotFound();
        }
        $this->doiNguoi($this->admin);
        $this->getJson('/api/v1/khach-hang/lich-tap/'.$l['id'])->assertForbidden();
        $this->doiNguoi($this->pt);
        $body = ['noi_dung' => 'Giữ nhịp thở đều.', 'client_request_id' => (string) Str::uuid()];
        $url = '/api/v1/pt/lich-tap/'.$l['id'].'/nhan-xet';
        $this->postJson($url, $body)->assertOk()->assertJsonPath('data.nhan_xet.0.noi_dung', $body['noi_dung']);
        $this->postJson($url, $body)->assertOk();
        $this->assertDatabaseCount('ghi_chu_huan_luyen', 1);
        $this->postJson($url, [...$body, 'noi_dung' => 'Đổi nội dung'])->assertConflict();
        $this->pc->update(['ket_thuc_luc' => now()]);
        $this->getJson('/api/v1/pt/lich-tap/'.$l['id'])->assertNotFound();
        $this->postJson($url, $body)->assertNotFound();
        $ptMoi = $this->taiKhoan(TaiKhoan::HUAN_LUYEN_VIEN);
        PhanCongHuanLuyenVien::create(['khach_hang_id' => $this->khId, 'huan_luyen_vien_id' => $ptMoi->hoSoHuanLuyenVien->id, 'nguoi_phan_cong_id' => $this->admin->id, 'bat_dau_luc' => now()]);
        $this->doiNguoi($ptMoi);
        $this->getJson('/api/v1/pt/lich-tap/'.$l['id'])->assertOk();
        $this->postJson($url, ['noi_dung' => 'Nhận xét PT mới', 'client_request_id' => (string) Str::uuid()])->assertOk();
        $this->doiNguoi($this->kh);
        $this->getJson('/api/v1/khach-hang/lich-tap/'.$l['id'])->assertJsonCount(2, 'data.nhan_xet');
    }

    public function test_ngay_tuong_lai_va_validation_khong_tin_payload(): void
    {
        $l = $this->tao(['ngay_tap' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString()]);
        $this->postJson('/api/v1/khach-hang/lich-tap/'.$l['id'].'/bat-dau', ['updated_at' => $l['updated_at']])->assertConflict();
        foreach ([['ngay_tap' => now('Asia/Ho_Chi_Minh')->subDay()->toDateString()], ['ngay_thu' => 2], ['khach_hang_id' => $this->khId], ['trang_thai' => 'HOAN_THANH']] as $them) {
            $this->postJson('/api/v1/khach-hang/lich-tap', $this->noiDung($them))->assertUnprocessable();
        }
        $this->getJson('/api/v1/khach-hang/lich-tap?tu_ngay=2025-01-01&den_ngay=2026-01-02')->assertUnprocessable();
        $this->assertDatabaseCount('phien_tap', 0);
    }

    public function test_lich_da_co_giu_snapshot_khi_ngung_giao_an_catalog_va_pt(): void
    {
        $l = $this->tao();
        app(KeHoachTapService::class)->thaoTac($this->kh, $this->k->id, 'luu-tru', $this->k->updated_at->format('Y-m-d H:i:s.u'));
        BaiTap::find($this->baiId)->update(['ten_bai_tap' => 'Tên mới', 'trang_thai' => 'NGUNG_SU_DUNG']);
        $this->pc->update(['ket_thuc_luc' => now()]);
        $this->postJson('/api/v1/khach-hang/lich-tap', $this->noiDung(['ngay_tap' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString()]))->assertConflict();
        $p = $this->thaoTac($l, 'bat-dau');
        $this->assertSame('Chống đẩy', $p['bai_tap'][0]['ten_bai_tap']);
        $this->assertSame('10.50', $p['bai_tap'][0]['noi_dung']['du_kien']['muc_ta_kg']);
        $this->assertSame([], $p['bai_tap'][0]['hiep_tap']);
    }

    public function test_stale_save_va_hiep_sai_phien_khong_ghi_de(): void
    {
        $l = $this->thaoTac($this->tao(), 'bat-dau');
        $body = $this->ketQua($l);
        $url = '/api/v1/khach-hang/lich-tap/'.$l['id'];
        $moi = $this->putJson($url, $body)->assertOk()->json('data');
        $this->putJson($url, [...$body, 'ghi_chu' => 'Ghi đè từ tab cũ'])->assertConflict();
        $body['updated_at'] = $moi['updated_at'];
        $body['bai_tap'][0]['id'] = 999999;
        $this->putJson($url, $body)->assertUnprocessable();
        $body = $this->ketQua($moi);
        $body['bai_tap'][0]['hiep_tap'][0]['so_lan_lap'] = 0;
        $this->putJson($url, $body)->assertUnprocessable();
        $body['bai_tap'][0]['hiep_tap'][0]['so_lan_lap'] = 10;
        $body['bai_tap'][0]['hiep_tap'][0]['khoi_luong_kg'] = -1;
        $this->putJson($url, $body)->assertUnprocessable();
        $this->assertDatabaseHas('phien_tap', ['ghi_chu' => 'Tập có kiểm soát']);
        $this->assertDatabaseCount('hiep_tap', 3);
    }

    public function test_huy_giu_hiep_va_loai_khoi_thong_ke(): void
    {
        $l = $this->thaoTac($this->tao(), 'bat-dau');
        $l = $this->putJson('/api/v1/khach-hang/lich-tap/'.$l['id'], $this->ketQua($l))->assertOk()->json('data');
        $huy = $this->thaoTac($l, 'huy');
        $this->thaoTac($l, 'huy');
        $this->assertSame('DA_HUY', $huy['trang_thai']);
        $this->assertDatabaseCount('hiep_tap', 3);
        $today = now('Asia/Ho_Chi_Minh')->toDateString();
        $this->getJson('/api/v1/khach-hang/lich-tap?tu_ngay='.$today.'&den_ngay='.$today)->assertJsonPath('meta.thong_ke.so_buoi', 0);
        $this->postJson('/api/v1/khach-hang/lich-tap/'.$l['id'].'/bat-dau', ['updated_at' => $huy['updated_at']])->assertConflict();
        $this->postJson('/api/v1/khach-hang/lich-tap', $this->noiDung())->assertConflict();
    }

    public function test_giao_an_pt_da_duyet_tap_khong_tru_goi_va_sau_het_han(): void
    {
        app(KeHoachTapService::class)->thaoTac($this->kh, $this->k->id, 'luu-tru', $this->k->updated_at->format('Y-m-d H:i:s.u'));
        $this->k = KeHoachTap::create(['khach_hang_id' => $this->khId, 'huan_luyen_vien_id' => $this->pt->hoSoHuanLuyenVien->id,
            'phan_cong_id' => $this->pc->id, 'ten_ke_hoach' => 'PT giao', 'nguon_tao' => 'PT', 'trang_thai' => 'DANG_AP_DUNG', 'gui_luc' => now(), 'duyet_luc' => now()]);
        $this->k->cacBaiTap()->create(['bai_tap_id' => $this->baiId, 'ngay_thu' => 1, 'thu_tu' => 1, 'ten_bai_tap_snapshot' => 'Chống đẩy PT', 'so_hiep' => 3, 'so_lan_lap' => 12, 'nghi_giay' => 60]);
        $goi = DB::table('goi_tap')->insertGetId(['ten_goi' => 'Gói demo', 'gia' => 1000, 'co_chatbot' => false, 'so_buoi_pt' => 5, 'thoi_han_ngay' => 30, 'trang_thai' => 'DANG_BAN']);
        $don = DB::table('dang_ky_goi_tap')->insertGetId(['khach_hang_id' => $this->khId, 'goi_tap_id' => $goi, 'client_request_id' => (string) Str::uuid(), 'ma_don_payos' => 12345,
            'ten_goi_snapshot' => 'Gói demo', 'gia_snapshot' => 1000, 'co_chatbot_snapshot' => false, 'so_buoi_pt_snapshot' => 5, 'thoi_han_ngay_snapshot' => 30, 'so_buoi_con_lai' => 4,
            'trang_thai' => 'DANG_SU_DUNG', 'kich_hoat_luc' => now()->subMonth(), 'het_han_luc' => now()->subDay()]);
        $l = $this->hoanThanh();
        $this->assertSame('PT', $l['nguon_tao']);
        $this->assertSame('Chống đẩy PT', $l['bai_tap'][0]['ten_bai_tap']);
        $this->assertDatabaseHas('dang_ky_goi_tap', ['id' => $don, 'so_buoi_con_lai' => 4]);
        $this->assertDatabaseCount('lich_hen_huan_luyen', 0);
    }

    public function test_migration_giu_du_lieu_cu_va_chan_go_chong_trung(): void
    {
        $lich = LichTap::create(['khach_hang_id' => $this->khId, 'ke_hoach_tap_id' => $this->k->id, 'ngay_thu' => 1, 'ngay_tap' => '2026-10-03', 'trang_thai' => 'DA_LEN_LICH']);
        DB::commit();
        $this->daCommit = true;
        $m = require database_path('migrations/2026_10_03_000040_bo_sung_nhat_ky_tap.php');
        $m->down();
        $m->up();
        $this->assertSame('2026-10-03', $lich->fresh()->ngay_tap);
        $this->tao(['ngay_tap' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString()]);
        try {
            $m->down();
            $this->fail('Không gỡ dữ liệu mới.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Đã có nhật ký', $e->getMessage());
        }
        $this->assertSame(1, LichTap::whereNotNull('ma_yeu_cau_tao')->count());
    }

    public function test_hai_process_tao_bat_dau_hoan_thanh_va_nhan_xet_khong_trung(): void
    {
        $a = ['nguoi_id' => $this->kh->id, 'khach_id' => $this->khId, 'hanh_dong' => 'tao', 'body' => $this->noiDung()];
        $r = $this->haiWorker([$a, $a]);
        $this->assertTrue($r[0]['ok']);
        $this->assertTrue($r[1]['ok']);
        $this->assertSame($r[0]['id'], $r[1]['id']);
        $this->assertDatabaseCount('lich_tap', 1);
        $l = $this->getJson('/api/v1/khach-hang/lich-tap/'.$r[0]['id'])->assertOk()->json('data');
        $a = ['nguoi_id' => $this->kh->id, 'id' => $l['id'], 'hanh_dong' => 'bat-dau', 'body' => ['updated_at' => $l['updated_at']]];
        $r = $this->haiWorker([$a, $a]);
        $this->assertTrue($r[0]['ok']);
        $this->assertTrue($r[1]['ok']);
        $this->assertDatabaseCount('phien_tap', 1);
        $this->assertDatabaseCount('bai_tap_trong_phien', 1);
        $l = $this->getJson('/api/v1/khach-hang/lich-tap/'.$l['id'])->assertOk()->json('data');
        $l = $this->putJson('/api/v1/khach-hang/lich-tap/'.$l['id'], $this->ketQua($l))->assertOk()->json('data');
        $a['hanh_dong'] = 'hoan-thanh';
        $a['body'] = ['updated_at' => $l['updated_at']];
        $r = $this->haiWorker([$a, $a]);
        $this->assertTrue($r[0]['ok']);
        $this->assertTrue($r[1]['ok']);
        $a['nguoi_id'] = $this->pt->id;
        $a['hanh_dong'] = 'nhan-xet';
        $a['body'] = ['noi_dung' => 'Tốt', 'client_request_id' => (string) Str::uuid()];
        $r = $this->haiWorker([$a, $a]);
        $this->assertTrue($r[0]['ok']);
        $this->assertTrue($r[1]['ok']);
        $this->assertDatabaseCount('ghi_chu_huan_luyen', 1);
    }

    public function test_hai_process_luu_khac_nhau_chi_mot_thanh_cong(): void
    {
        $l = $this->thaoTac($this->tao(), 'bat-dau');
        $a = ['nguoi_id' => $this->kh->id, 'id' => $l['id'], 'hanh_dong' => 'luu', 'body' => $this->ketQua($l)];
        $b = $a;
        $b['body']['ghi_chu'] = 'Từ tab khác';
        $r = $this->haiWorker([$a, $b]);
        $this->assertSame(1, count(array_filter($r, fn ($v) => $v['ok'])));
        $this->assertSame(409, array_values(array_filter($r, fn ($v) => ! $v['ok']))[0]['status']);
        $this->assertDatabaseCount('hiep_tap', 3);
    }

    public function test_hai_process_tao_lich_va_ngung_giao_an_khong_mat_lich(): void
    {
        $a = ['nguoi_id' => $this->kh->id, 'khach_id' => $this->khId, 'hanh_dong' => 'tao', 'body' => $this->noiDung()];
        $b = ['nguoi_id' => $this->kh->id, 'id' => $this->k->id, 'hanh_dong' => 'luu-tru', 'body' => ['updated_at' => $this->k->updated_at->format('Y-m-d H:i:s.u')]];
        $r = $this->haiWorker([$a, $b]);
        $this->assertTrue($r[1]['ok']);
        if ($r[0]['ok']) {
            $this->assertDatabaseCount('lich_tap', 1);
            $l = $this->getJson('/api/v1/khach-hang/lich-tap/'.$r[0]['id'])->assertOk()->json('data');
            $this->thaoTac($l, 'bat-dau');
        } else {
            $this->assertSame(409, $r[0]['status']);
            $this->assertDatabaseCount('lich_tap', 0);
        }
    }

    private function haiWorker(array $cacLenh): array
    {
        if (DB::transactionLevel()) {
            DB::commit();
        }
        $this->daCommit = true;
        $workers = [];
        DB::beginTransaction();
        DB::table('ho_so_khach_hang')->where('id', $this->khId)->lockForUpdate()->first();
        try {
            foreach ($cacLenh as $lenh) {
                $ready = tempnam(sys_get_temp_dir(), 'nhat-ky-ready-');
                unlink($ready);
                $input = tempnam(sys_get_temp_dir(), 'nhat-ky-input-');
                file_put_contents($input, json_encode($lenh, JSON_THROW_ON_ERROR));
                $out = tempnam(sys_get_temp_dir(), 'nhat-ky-out-');
                $err = tempnam(sys_get_temp_dir(), 'nhat-ky-err-');
                $proc = proc_open([PHP_BINARY, base_path('tests/Support/nhat-ky-worker.php'), self::$db, $input, $ready], [0 => ['pipe', 'r'], 1 => ['file', $out, 'w'], 2 => ['file', $err, 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
                $this->assertIsResource($proc);
                fclose($pipes[0]);
                $workers[] = compact('proc', 'ready', 'input', 'out', 'err');
            }
            $han = microtime(true) + 10;
            while ((! file_exists($workers[0]['ready']) || ! file_exists($workers[1]['ready'])) && microtime(true) < $han) {
                usleep(20000);
            }
            foreach ($workers as $w) {
                $this->assertFileExists($w['ready']);
            }
            DB::commit();
            $r = [];
            foreach ($workers as &$w) {
                $this->assertSame(0, proc_close($w['proc']), file_get_contents($w['out']).file_get_contents($w['err']));
                $w['proc'] = null;
                $r[] = json_decode(file_get_contents($w['out']), true, flags: JSON_THROW_ON_ERROR);
            }
            unset($w);

            return $r;
        } finally {
            if (DB::transactionLevel()) {
                DB::rollBack(0);
            }
            foreach ($workers as $w) {
                if (is_resource($w['proc'])) {
                    proc_terminate($w['proc']);
                    proc_close($w['proc']);
                }
                foreach (['ready', 'input', 'out', 'err'] as $tep) {
                    if (file_exists($w[$tep])) {
                        unlink($w[$tep]);
                    }
                }
            }
        }
    }

    public function test_rollback_bat_dau_luu_va_hoan_thanh(): void
    {
        $l = $this->tao();
        $s = app(NhatKyTapService::class);
        Event::listen('eloquent.creating: '.BaiTapTrongPhien::class, fn () => throw new RuntimeException('loi-snapshot'));
        try {
            $s->thaoTac($this->kh, $l['id'], 'bat-dau', $l['updated_at']);
            $this->fail();
        } catch (RuntimeException $e) {
            $this->assertSame('loi-snapshot', $e->getMessage());
        }
        $this->assertDatabaseCount('phien_tap', 0);
        $this->assertDatabaseHas('lich_tap', ['id' => $l['id'], 'trang_thai' => 'DA_LEN_LICH']);
        Event::forget('eloquent.creating: '.BaiTapTrongPhien::class);
        $l = $this->thaoTac($l, 'bat-dau');
        Event::listen('eloquent.creating: '.HiepTap::class, fn () => throw new RuntimeException('loi-hiep'));
        try {
            $s->luu($this->kh, $l['id'], $this->ketQua($l));
            $this->fail();
        } catch (RuntimeException $e) {
            $this->assertSame('loi-hiep', $e->getMessage());
        }
        $this->assertDatabaseCount('hiep_tap', 0);
        $this->assertDatabaseHas('phien_tap', ['ghi_chu' => null]);
        Event::forget('eloquent.creating: '.HiepTap::class);
        $l = $this->putJson('/api/v1/khach-hang/lich-tap/'.$l['id'], $this->ketQua($l))->assertOk()->json('data');
        Event::listen('eloquent.updating: '.LichTap::class, fn () => throw new RuntimeException('loi-lich'));
        try {
            $s->thaoTac($this->kh, $l['id'], 'hoan-thanh', $l['updated_at']);
            $this->fail();
        } catch (RuntimeException $e) {
            $this->assertSame('loi-lich', $e->getMessage());
        }
        $this->assertDatabaseHas('phien_tap', ['trang_thai' => 'DANG_TAP', 'hoan_thanh_luc' => null]);
    }
}
