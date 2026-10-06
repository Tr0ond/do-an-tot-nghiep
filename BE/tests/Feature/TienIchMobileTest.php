<?php

namespace Tests\Feature;

use App\Events\ChatCanDongBo;
use App\Events\LichCanDongBo;
use App\Models\DangKyGoiTap;
use App\Models\GoiTap;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use App\Services\ChatService;
use App\Services\LichHenService;
use App\Services\MuaGoiService;
use App\Services\PayosService;
use App\Services\PhienMobileService;
use App\Services\TaiKhoanService;
use App\Services\ThongBaoDayService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use PDO;
use Tests\TestCase;

class TienIchMobileTest extends TestCase
{
    private static ?string $tenDatabase = null;

    private static ?PDO $pdoMayChu = null;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'push.enabled' => true]);
        Http::preventStrayRequests();
        Event::fake([ChatCanDongBo::class, LichCanDongBo::class]);
        $c = config('database.connections.mysql');
        if (! self::$tenDatabase) {
            $c['database'] = null;
            $c['url'] = null;
            config(['database.connections.may_chu_tien_ich' => $c]);
            self::$pdoMayChu = DB::connection('may_chu_tien_ich')->getPdo();
            self::$tenDatabase = 'kiem_tra_tien_ich_'.bin2hex(random_bytes(8));
            self::$pdoMayChu->exec('CREATE DATABASE `'.self::$tenDatabase.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
        config(['database.connections.mysql.database' => self::$tenDatabase, 'database.connections.mysql.url' => null]);
        DB::purge('mysql');
        DB::setDefaultConnection('mysql');
        $this->assertSame(self::$tenDatabase, DB::selectOne('SELECT DATABASE() AS ten')->ten);
        // Chỉ reset database QA có tên ngẫu nhiên vừa được kiểm tra.
        $this->assertSame(0, Artisan::call('migrate:fresh', ['--force' => true]));
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$tenDatabase && preg_match('/^kiem_tra_tien_ich_[a-f0-9]{16}$/D', self::$tenDatabase)) {
            self::$pdoMayChu->exec('DROP DATABASE `'.self::$tenDatabase.'`');
        }
        self::$tenDatabase = null;
        self::$pdoMayChu = null;
        parent::tearDownAfterClass();
    }

    private function nguoi(string $vaiTro = TaiKhoan::KHACH_HANG): TaiKhoan
    {
        return app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'QA tiện ích', 'email' => Str::uuid().'@example.test', 'password' => 'Demo123456!'], $vaiTro);
    }

    private function phien(TaiKhoan $u): string
    {
        return app(PhienMobileService::class)->dangNhap(['email' => $u->email, 'password' => 'Demo123456!', 'ten_thiet_bi' => 'QA Android'])->plainTextToken;
    }

    private function goi(string $method, string $token, array $d = [], string $duong = '/api/v1/mobile/thong-bao-day')
    {
        Auth::forgetGuards();
        $this->flushHeaders();

        return $this->withHeader('Authorization', 'Bearer '.$token)->{$method.'Json'}($duong, $d);
    }

    private function bo(): array
    {
        $kh = $this->nguoi();
        $pt = $this->nguoi(TaiKhoan::HUAN_LUYEN_VIEN);
        $admin = $this->nguoi(TaiKhoan::ADMIN);
        $pc = PhanCongHuanLuyenVien::create(['khach_hang_id' => $kh->hoSoKhachHang->id, 'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'nguoi_phan_cong_id' => $admin->id, 'bat_dau_luc' => now()->subHour(), 'client_request_id' => (string) Str::uuid()]);
        $hoi = DB::table('hoi_thoai')->insertGetId(['phan_cong_id' => $pc->id, 'created_at' => now(), 'updated_at' => now()]);
        $a = $this->phien($kh);
        $b = $this->phien($pt);
        $this->goi('put', $a, ['expo_token' => 'ExpoPushToken[khach-qa]'])->assertOk();
        $this->goi('put', $b, ['expo_token' => 'ExpoPushToken[pt-qa]'])->assertOk();

        return compact('kh', 'pt', 'admin', 'pc', 'hoi', 'a', 'b');
    }

    private function gui(array $bo): void
    {
        app(ChatService::class)->gui($bo['kh'], $bo['hoi'], ['noi_dung' => 'Nội dung riêng không gửi push', 'client_message_id' => (string) Str::uuid()]);
    }

    public function test_cai_dat_chi_bearer_dung_phien_validation_va_logout_mot_thiet_bi(): void
    {
        $u = $this->nguoi();
        $a = $this->phien($u);
        $b = $this->phien($u);
        $this->getJson('/api/v1/mobile/thong-bao-day')->assertUnauthorized();
        $this->goi('put', $a, ['expo_token' => 'https://evil.test'])->assertUnprocessable();
        $this->goi('put', $a, ['expo_token' => 'ExpoPushToken[a]'])->assertOk();
        $this->goi('put', $b, ['expo_token' => 'ExpoPushToken[b]'])->assertOk();
        $this->assertDatabaseCount('thiet_bi_push', 2);
        $this->goi('delete', $a)->assertOk();
        $this->goi('get', $a)->assertJsonPath('data.da_bat', false)->assertJsonMissingPath('data.expo_token');
        $this->goi('get', $b)->assertJsonPath('data.da_bat', true);
        $this->goi('post', $a, [], '/api/v1/mobile/dang-xuat')->assertOk();
        $this->assertDatabaseCount('thiet_bi_push', 1);
        $this->goi('get', $b)->assertOk();
        Auth::forgetGuards();
        $this->flushHeaders();
        $this->actingAs($u, 'web')->getJson('/api/v1/mobile/thong-bao-day')->assertUnauthorized();
    }

    public function test_chat_chi_xep_nguoi_nhan_retry_khong_lap_rollback_khong_push(): void
    {
        $bo = $this->bo();
        $d = ['noi_dung' => 'Tin QA', 'client_message_id' => (string) Str::uuid()];
        app(ChatService::class)->gui($bo['kh'], $bo['hoi'], $d);
        app(ChatService::class)->gui($bo['kh'], $bo['hoi'], $d);
        $this->assertDatabaseCount('hang_doi_push', 1);
        $hang = DB::table('hang_doi_push')->first();
        $this->assertSame($bo['pt']->id, DB::table('thiet_bi_push')->find($hang->thiet_bi_id)->tai_khoan_id);
        DB::beginTransaction();
        $this->gui($bo);
        $this->assertDatabaseCount('hang_doi_push', 2);
        DB::rollBack();
        $this->assertDatabaseCount('hang_doi_push', 1);
    }

    public function test_gui_ticket_receipt_noi_dung_an_toan_va_khong_gui_lap(): void
    {
        $bo = $this->bo();
        $this->gui($bo);
        Http::fake(['*/send' => Http::response(['data' => ['status' => 'ok', 'id' => 'ticket-qa']]), '*/getReceipts' => Http::response(['data' => ['ticket-qa' => ['status' => 'ok']]])]);
        $s = app(ThongBaoDayService::class);
        $this->assertSame(1, $s->xuLy());
        $this->assertSame(0, $s->xuLy());
        Http::assertSent(fn ($r) => $r['to'] === 'ExpoPushToken[pt-qa]' && $r['data']['tai_khoan_id'] === $bo['pt']->id && $r['data']['duong_dan'] === '/hoi-thoai/'.$bo['hoi'] && ! str_contains($r->body(), 'Nội dung riêng') && $r['channelId'] === 'fitforge');
        $this->travel(16)->minutes();
        $this->assertSame(1, $s->xuLy());
        $this->assertDatabaseHas('hang_doi_push', ['trang_thai' => 'DA_GUI']);
        Http::assertSentCount(2);
        $this->travelBack();
    }

    public function test_doi_tai_khoan_tren_may_khong_gui_hang_cu_va_refresh_token_khong_doi_phien_ban(): void
    {
        $bo = $this->bo();
        $this->gui($bo);
        $cu = DB::table('thiet_bi_push')->where('expo_token', 'ExpoPushToken[pt-qa]')->first();
        $this->goi('put', $bo['b'], ['expo_token' => 'ExpoPushToken[pt-qa]'])->assertOk();
        $this->assertSame($cu->phien_ban, DB::table('thiet_bi_push')->find($cu->id)->phien_ban);
        $khac = $this->nguoi();
        $this->goi('put', $this->phien($khac), ['expo_token' => 'ExpoPushToken[pt-qa]'])->assertOk();
        $this->goi('put', $bo['b'], ['expo_token' => 'ExpoPushToken[pt-qa]'])->assertConflict();
        Http::fake();
        $this->assertSame(0, app(ThongBaoDayService::class)->xuLy());
        Http::assertNothingSent();
        $this->assertDatabaseHas('hang_doi_push', ['trang_thai' => 'HUY']);
    }

    public function test_het_han_khoa_tai_khoan_va_doi_pt_khong_gui_push_cu(): void
    {
        foreach (['het-han', 'khoa', 'doi-pt', 'doi-mat-khau'] as $loai) {
            $bo = $this->bo();
            $this->gui($bo);
            if ($loai === 'het-han') {
                PersonalAccessToken::findToken($bo['b'])->update(['expires_at' => now()->subSecond()]);
            } elseif ($loai === 'khoa') {
                $bo['pt']->forceFill(['trang_thai' => TaiKhoan::BI_KHOA])->save();
            } elseif ($loai === 'doi-pt') {
                $bo['pc']->update(['ket_thuc_luc' => now()]);
            } else {
                $bo['pt']->update(['password' => 'MatKhauMoi123!']);
            }
            Http::fake();
            $this->assertSame(0, app(ThongBaoDayService::class)->xuLy());
            Http::assertNothingSent();
        }
    }

    public function test_device_not_registered_tat_token_va_http_429_retry_co_gioi_han(): void
    {
        $bo = $this->bo();
        $this->gui($bo);
        Http::fake(['*/send' => Http::sequence()->push([], 429)->push(['data' => ['status' => 'error', 'details' => ['error' => 'DeviceNotRegistered']]])]);
        app(ThongBaoDayService::class)->xuLy();
        $this->assertDatabaseHas('hang_doi_push', ['trang_thai' => 'CHO_GUI', 'so_lan' => 1]);
        $this->assertSame(0, app(ThongBaoDayService::class)->xuLy());
        Http::assertSentCount(1);
        $this->travel(3)->minutes();
        app(ThongBaoDayService::class)->xuLy();
        $this->assertDatabaseHas('thiet_bi_push', ['expo_token' => 'ExpoPushToken[pt-qa]', 'da_bat' => false]);
        $this->assertDatabaseHas('hang_doi_push', ['trang_thai' => 'LOI', 'ma_loi' => 'DeviceNotRegistered']);
        $this->travelBack();
    }

    public function test_realtime_slot_lich_chi_sau_commit_rollback_khong_phat_va_push_dat_xac_nhan(): void
    {
        $bo = $this->bo();
        $goi = GoiTap::create(['ten_goi' => 'QA', 'gia' => 99000, 'co_chatbot' => true, 'so_luot_chatbot_moi_ngay' => 10, 'so_buoi_pt' => 8, 'thoi_han_ngay' => 30, 'trang_thai' => 'HOAT_DONG']);
        $don = app(MuaGoiService::class)->taoDon($bo['kh'], ['goi_tap_id' => $goi->id, 'client_request_id' => (string) Str::uuid()]);
        $don->update(['trang_thai' => 'DANG_SU_DUNG', 'kich_hoat_luc' => now()->subHour(), 'het_han_luc' => now()->addDays(29), 'so_buoi_con_lai' => 8]);
        DB::beginTransaction();
        $slot = app(LichHenService::class)->taoKhungGio($bo['pt'], ['bat_dau_luc' => now()->addHours(8)->toIso8601String()]);
        Event::assertNotDispatched(LichCanDongBo::class);
        DB::commit();
        Event::assertDispatched(LichCanDongBo::class, function ($e) use ($bo) {
            $names = array_map(fn ($c) => $c->name, $e->broadcastOn());

            return in_array('private-chat.tai-khoan.'.$bo['kh']->id, $names) && in_array('private-chat.tai-khoan.'.$bo['pt']->id, $names) && $e->broadcastWith() === ['can_dong_bo' => true];
        });
        Event::fake([LichCanDongBo::class]);
        DB::beginTransaction();
        app(LichHenService::class)->doiKhungGio($bo['pt'], $slot->id, 'DONG');
        DB::rollBack();
        Event::assertNotDispatched(LichCanDongBo::class);
        $lich = app(LichHenService::class)->datLich($bo['kh'], ['khung_gio_id' => $slot->id, 'client_request_id' => (string) Str::uuid()]);
        app(LichHenService::class)->thaoTac($bo['pt'], $lich->id, 'xac-nhan');
        app(LichHenService::class)->thaoTac($bo['pt'], $lich->id, 'xac-nhan');
        $this->assertDatabaseCount('hang_doi_push', 2);
        Event::assertDispatched(LichCanDongBo::class);
    }

    public function test_payos_mobile_ve_trang_cong_khai_web_giu_url_cu(): void
    {
        config(['app.frontend_url' => 'https://fitforge.example', 'payos.client_id' => 'qa', 'payos.api_key' => 'qa', 'payos.checksum_key' => 'qa', 'payos.ca_bundle' => __FILE__]);
        $s = app(PayosService::class);
        Http::fake(['https://api-merchant.payos.vn/*' => Http::response(['code' => '00', 'data' => ['qa' => true], 'signature' => $s->chuKy(['qa' => true])])]);
        $don = new DangKyGoiTap(['ma_don_payos' => 12345, 'gia_snapshot' => 99000, 'han_thanh_toan' => now()->addMinutes(15)]);
        $don->id = 42;
        $s->taoLink($don, true);
        $s->taoLink($don);
        Http::assertSent(fn ($r) => $r['returnUrl'] === 'https://fitforge.example/mo-ung-dung/don-hang/42' && $r['cancelUrl'] === $r['returnUrl']);
        Http::assertSent(fn ($r) => $r['returnUrl'] === 'https://fitforge.example/khach-hang/don-hang/42');
    }
}
