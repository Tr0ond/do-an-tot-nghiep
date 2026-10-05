<?php

namespace Tests\Feature;

use App\Models\DangKyGoiTap;
use App\Models\GoiTap;
use App\Models\HoiThoaiTroLy;
use App\Models\KeHoachTap;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use App\Models\TinNhanTroLy;
use App\Models\YeuCauTroLy;
use App\Services\ChatbotService;
use App\Services\GiaoAnAiService;
use App\Services\PhienMobileService;
use App\Services\TaiKhoanService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PDO;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    private static ?string $db = null;

    private static ?PDO $pdo = null;

    private TaiKhoan $kh;

    private DangKyGoiTap $goi;

    private HoiThoaiTroLy $hoi;

    private bool $daCommit = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (! self::$db) {
            $cfg = config('database.connections.mysql');
            $cfg['database'] = null;
            $cfg['url'] = null;
            config(['database.connections.chatbot_test' => $cfg]);
            self::$pdo = DB::connection('chatbot_test')->getPdo();
            self::$db = 'kiem_tra_chatbot_'.bin2hex(random_bytes(8));
            self::$pdo->exec('CREATE DATABASE `'.self::$db.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
        config(['database.connections.mysql.database' => self::$db, 'database.connections.mysql.url' => null, 'database.default' => 'mysql', 'chatbot.key' => 'fake-key']);
        DB::purge('mysql');
        $this->assertSame(self::$db, DB::selectOne('SELECT DATABASE() AS ten')->ten);
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        DB::beginTransaction();
        $this->kh = $this->taiKhoan();
        $g = GoiTap::create(['ten_goi' => 'AI thử nghiệm', 'gia' => 100000, 'co_chatbot' => true, 'so_luot_chatbot_moi_ngay' => 2, 'so_buoi_pt' => 0, 'thoi_han_ngay' => 30, 'trang_thai' => 'HOAT_DONG']);
        $this->goi = DangKyGoiTap::create(['khach_hang_id' => $this->kh->hoSoKhachHang->id, 'goi_tap_id' => $g->id, 'client_request_id' => (string) Str::uuid(), 'ma_don_payos' => random_int(100000, 9999999), 'ten_goi_snapshot' => $g->ten_goi,
            'gia_snapshot' => $g->gia, 'co_chatbot_snapshot' => true, 'so_luot_chatbot_moi_ngay_snapshot' => 2, 'so_buoi_pt_snapshot' => 0, 'so_buoi_con_lai' => 0, 'thoi_han_ngay_snapshot' => 30,
            'trang_thai' => 'DANG_SU_DUNG', 'kich_hoat_luc' => now()->subDay(), 'het_han_luc' => now()->addDays(29)]);
        $this->hoi = app(ChatbotService::class)->tao($this->kh->hoSoKhachHang->id, (string) Str::uuid());
        $this->doiNguoi($this->kh);
        $this->giaLap(['generativelanguage.googleapis.com/*' => Http::response($this->provider())]);
    }

    protected function tearDown(): void
    {
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
        if (self::$db && preg_match('/^kiem_tra_chatbot_[a-f0-9]{16}$/D', self::$db)) {
            self::$pdo->exec('DROP DATABASE `'.self::$db.'`');
        }
        self::$db = null;
        self::$pdo = null;
        parent::tearDownAfterClass();
    }

    private function taiKhoan(string $vaiTro = TaiKhoan::KHACH_HANG): TaiKhoan
    {
        return app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Kiểm thử AI', 'email' => Str::uuid().'@example.test', 'password' => 'Demo123456!'], $vaiTro);
    }

    private function doiNguoi(TaiKhoan $u): void
    {
        Auth::forgetGuards();
        $this->actingAs($u, 'web');
    }

    private function body(array $them = []): array
    {
        return ['client_request_id' => (string) Str::uuid(), 'noi_dung' => 'Tôi có thể tự tạo giáo án không?', 'dung_du_lieu_ca_nhan' => false, ...$them];
    }

    private function giaLap($callback): void
    {
        // Mỗi kịch bản thay hẳn provider; Http::fake nhiều lần nối callback cũ.
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake($callback);
    }

    private function provider(array $them = []): array
    {
        return ['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode(['noi_dung' => 'Bạn có thể tự tạo giáo án trong Giáo án của tôi.', 'goi_tap_ids' => [], 'giao_an_mau_ids' => [], 'bai_tap_ids' => [], 'nguon_tai_lieu_ids' => [], ...$them], JSON_UNESCAPED_UNICODE)]]]]], 'usageMetadata' => ['promptTokenCount' => 200, 'candidatesTokenCount' => 80]];
    }

    private function gui(array $d)
    {
        return $this->postJson('/api/v1/khach-hang/chatbot/hoi-thoai/'.$this->hoi->id.'/tin-nhan', $d);
    }

    public function test_mobile_bearer_ai_retry_han_muc_quyen_va_nguon(): void
    {
        $token = app(PhienMobileService::class)->dangNhap(['email' => $this->kh->email, 'password' => 'Demo123456!', 'ten_thiet_bi' => 'MB5 QA'])->plainTextToken;
        $call = function ($method, $url, $d = []) use ($token) {
            Auth::forgetGuards();
            $this->flushHeaders();

            return $this->withHeader('Authorization', 'Bearer '.$token)->{$method.'Json'}($url, $d);
        };
        $prefix = '/api/v1/khach-hang/chatbot/hoi-thoai';
        $call('get', $prefix)->assertOk()->assertJsonPath('meta.han_muc.con_lai', 2);
        $id = $call('post', $prefix, ['client_request_id' => (string) Str::uuid()])->assertCreated()->json('data.id');
        $d = $this->body();
        $call('post', $prefix.'/'.$id.'/tin-nhan', $d)->assertOk()->assertJsonPath('meta.han_muc.con_lai', 1);
        $call('post', $prefix.'/'.$id.'/tin-nhan', $d)->assertOk()->assertJsonPath('meta.han_muc.con_lai', 1);
        Http::assertSentCount(1);
        $call('post', $prefix.'/'.$id.'/tin-nhan', [...$d, 'dung_du_lieu_ca_nhan' => true])->assertConflict();
        $call('get', $prefix.'/'.$id)->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.client_request_id', $d['client_request_id']);
        $khac = $this->taiKhoan();
        $hoi = app(ChatbotService::class)->tao($khac->hoSoKhachHang->id, (string) Str::uuid());
        $call('get', $prefix.'/'.$hoi->id)->assertNotFound();
        $this->goi->update(['het_han_luc' => now()]);
        $call('post', $prefix.'/'.$id.'/tin-nhan', $this->body())->assertForbidden();
        $call('get', $prefix.'/'.$id)->assertOk()->assertJsonPath('meta.han_muc.co_quyen', false);
        $this->assertSame(1, YeuCauTroLy::where('trang_thai', 'THANH_CONG')->count());
    }

    public function test_valid_answer_retry_history_and_package_snapshot(): void
    {
        $this->goi->update(['so_luot_chatbot_moi_ngay_snapshot' => 1]);
        GoiTap::whereKey($this->goi->goi_tap_id)->update(['so_luot_chatbot_moi_ngay' => 100]);
        $d = $this->body();
        $this->gui($d)->assertOk()->assertJsonPath('meta.han_muc.con_lai', 0);
        $this->goi->update(['het_han_luc' => now()->subSecond()]);
        $this->gui($d)->assertOk();
        Http::assertSentCount(1);
        $this->assertSame(2, TinNhanTroLy::count());
        $this->assertSame(1, YeuCauTroLy::where('trang_thai', 'THANH_CONG')->count());
        $this->getJson('/api/v1/khach-hang/chatbot/hoi-thoai/'.$this->hoi->id)->assertOk()->assertJsonCount(2, 'data');
        $this->gui([...$d, 'noi_dung' => 'Nội dung khác'])->assertConflict();
    }

    public function test_role_ownership_and_validation_are_enforced_before_provider(): void
    {
        foreach ([TaiKhoan::ADMIN, TaiKhoan::HUAN_LUYEN_VIEN] as $vai) {
            $this->doiNguoi($this->taiKhoan($vai));
            $this->gui($this->body())->assertForbidden();
        }
        $this->doiNguoi($this->taiKhoan());
        $this->gui($this->body())->assertNotFound();
        $this->getJson('/api/v1/khach-hang/chatbot/hoi-thoai/'.$this->hoi->id)->assertNotFound();
        $this->doiNguoi($this->kh);
        $this->gui($this->body(['khach_hang_id' => 999]))->assertUnprocessable();
        $this->gui($this->body(['noi_dung' => '']))->assertUnprocessable();
        $this->gui($this->body(['noi_dung' => str_repeat('a', 2001)]))->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_no_package_expired_future_or_no_ai_entitlement_cannot_send(): void
    {
        foreach ([['het_han_luc' => now()->subSecond()], ['kich_hoat_luc' => now()->addHour()], ['co_chatbot_snapshot' => false]] as $giaTri) {
            $cu = $this->goi->only(array_keys($giaTri));
            $this->goi->update($giaTri);
            $this->gui($this->body())->assertForbidden();
            $this->goi->forceFill($cu)->save();
        }
        $this->goi->update(['trang_thai' => 'HET_HAN']);
        $this->gui($this->body())->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_quota_and_missing_config_do_not_create_fake_answers(): void
    {
        config(['chatbot.key' => null]);
        $this->gui($this->body())->assertStatus(503);
        $this->assertSame(0, YeuCauTroLy::count());
        config(['chatbot.key' => 'fake-key']);
        $this->gui($this->body())->assertOk();
        $this->gui($this->body())->assertOk();
        $this->gui($this->body())->assertStatus(429);
        Http::assertSentCount(2);
    }

    public function test_provider_failure_refunds_reservation_and_retries_are_bounded(): void
    {
        $this->giaLap(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'quota'], 429)]);
        $d = $this->body();
        for ($i = 0; $i < 3; $i++) {
            $this->gui($d)->assertStatus(503);
        }
        $this->gui($d)->assertConflict();
        Http::assertSentCount(3);
        $this->assertSame(1, TinNhanTroLy::count());
        $this->assertSame(2, app(ChatbotService::class)->hanMuc($this->kh->hoSoKhachHang->id)['con_lai']);
        $this->getJson('/api/v1/khach-hang/chatbot/hoi-thoai/'.$this->hoi->id)->assertOk()->assertJsonPath('data.0.co_the_thu_lai', false);
    }

    public function test_timeout_and_invalid_json_do_not_charge(): void
    {
        $this->giaLap(fn () => throw new ConnectionException('private-detail'));
        $this->gui($this->body())->assertStatus(503)->assertDontSee('private-detail');
        $this->giaLap(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => 'not json']]]]]])]);
        $this->gui($this->body())->assertStatus(503);
        $this->assertSame(0, YeuCauTroLy::where('trang_thai', 'THANH_CONG')->count());
    }

    public function test_invalid_ids_markup_and_fabricated_currency_are_rejected(): void
    {
        foreach ([['goi_tap_ids' => [999999]], ['goi_tap_ids' => ['1']], ['noi_dung' => '<script>alert(1)</script>'], ['noi_dung' => 'Giá 500.000 đồng'], ['noi_dung' => 'https://attacker.test'], ['noi_dung' => '']] as $x) {
            $this->giaLap(['generativelanguage.googleapis.com/*' => Http::response($this->provider($x))]);
            $this->gui($this->body())->assertStatus(503);
        }
        $this->assertSame(0, TinNhanTroLy::where('vai_tro', 'ASSISTANT')->count());
    }

    public function test_current_catalog_cards_and_withdrawn_sources(): void
    {
        $this->giaLap(function () {
            GoiTap::whereKey($this->goi->goi_tap_id)->update(['gia' => 120000]);

            return Http::response($this->provider(['goi_tap_ids' => [$this->goi->goi_tap_id]]));
        });
        $this->gui($this->body())->assertOk()->assertJsonPath('data.tin_nhan.1.nguon_da_kiem_tra.goi_tap.0.gia', 120000);
        $this->giaLap(function () {
            GoiTap::whereKey($this->goi->goi_tap_id)->update(['trang_thai' => 'NGUNG_SU_DUNG']);

            return Http::response($this->provider(['goi_tap_ids' => [$this->goi->goi_tap_id]]));
        });
        $this->gui($this->body())->assertStatus(503);
    }

    public function test_personal_context_is_opt_in_and_contains_no_identifiers_or_pt_chat(): void
    {
        $this->kh->hoSoKhachHang->forceFill(['muc_tieu' => 'Tăng sức bền'])->save();
        $s = app(ChatbotService::class);
        $n = $s->nguCanh($this->kh->hoSoKhachHang->id, 'test', false);
        $this->assertArrayNotHasKey('ca_nhan', $n);
        $n = $s->nguCanh($this->kh->hoSoKhachHang->id, 'test', true);
        $this->assertSame('Tăng sức bền', $n['ca_nhan']['muc_tieu']);
        $this->assertStringNotContainsString($this->kh->email, json_encode($n));
        $this->assertArrayNotHasKey('tin_nhan_pt', $n);
        $this->gui($this->body(['dung_du_lieu_ca_nhan' => true]))->assertOk();
        $this->gui($this->body(['noi_dung' => 'Câu hỏi mới, không dùng cá nhân']))->assertOk();
        Http::assertSent(fn ($r) => str_contains($r['contents'][0]['parts'][0]['text'], 'Câu hỏi mới') && json_decode($r['contents'][0]['parts'][0]['text'], true)['lich_su'] === []);
    }

    public function test_completion_after_midnight_is_charged_to_original_day(): void
    {
        $this->travelTo(now('Asia/Ho_Chi_Minh')->setTime(23, 59, 55)->utc());
        $ngay = now('Asia/Ho_Chi_Minh')->toDateString();
        $this->giaLap(function () {
            $this->travel(10)->seconds();

            return Http::response($this->provider());
        });
        $this->gui($this->body())->assertOk()->assertJsonPath('meta.han_muc.con_lai', 2);
        $this->assertSame($ngay, YeuCauTroLy::first()->ngay_han_muc);
    }

    public function test_expired_lease_releases_and_stale_completion_is_discarded(): void
    {
        $this->giaLap(function () {
            $this->travel(121)->seconds();

            return Http::response($this->provider());
        });
        $d = $this->body();
        $this->gui($d)->assertStatus(503);
        $this->assertSame(2, app(ChatbotService::class)->hanMuc($this->kh->hoSoKhachHang->id)['con_lai']);
        $this->giaLap(['generativelanguage.googleapis.com/*' => Http::response($this->provider())]);
        $this->gui($d)->assertOk();
        $this->assertSame(1, TinNhanTroLy::where('vai_tro', 'ASSISTANT')->count());
    }

    public function test_conversation_creation_is_idempotent(): void
    {
        $d = ['client_request_id' => (string) Str::uuid()];
        $a = $this->postJson('/api/v1/khach-hang/chatbot/hoi-thoai', $d)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/khach-hang/chatbot/hoi-thoai', $d)->assertOk()->assertJsonPath('data.id', $a);
    }

    public function test_package_expiring_during_provider_keeps_original_entitlement(): void
    {
        $this->goi->update(['het_han_luc' => now()->addSeconds(2)]);
        $this->giaLap(function () {
            $this->travel(3)->seconds();

            return Http::response($this->provider());
        });
        $this->gui($this->body())->assertOk();
        $this->gui($this->body())->assertForbidden();
        $this->assertSame($this->goi->id, YeuCauTroLy::first()->dang_ky_goi_tap_id);
        Http::assertSentCount(1);
    }

    public function test_account_revocation_before_completion_discards_answer(): void
    {
        $this->giaLap(function () {
            TaiKhoan::whereKey($this->kh->id)->update(['trang_thai' => 'BI_KHOA']);

            return Http::response($this->provider());
        });
        $this->gui($this->body())->assertStatus(503);
        $this->assertSame(0, TinNhanTroLy::where('vai_tro', 'ASSISTANT')->count());
        $this->assertSame(0, YeuCauTroLy::where('trang_thai', 'THANH_CONG')->count());
    }

    public function test_assistant_insert_failure_rolls_back_charge_and_allows_retry(): void
    {
        $event = 'eloquent.creating: '.TinNhanTroLy::class;
        Event::listen($event, function ($t) {
            if ($t->vai_tro === 'ASSISTANT') {
                throw new \RuntimeException('private-db-detail');
            }
        });
        $d = $this->body();
        try {
            $this->gui($d)->assertStatus(503)->assertDontSee('private-db-detail');
            $this->assertSame(1, TinNhanTroLy::count());
            $this->assertSame(2, app(ChatbotService::class)->hanMuc($this->kh->hoSoKhachHang->id)['con_lai']);
        } finally {
            Event::forget($event);
        }
        $this->gui($d)->assertOk();
        $this->assertSame(2, TinNhanTroLy::count());
    }

    public function test_history_expired_reservation_can_retry_and_catalog_cards_are_current(): void
    {
        $this->giaLap(['generativelanguage.googleapis.com/*' => Http::response($this->provider(['goi_tap_ids' => [$this->goi->goi_tap_id]]))]);
        $this->gui($this->body())->assertOk();
        GoiTap::whereKey($this->goi->goi_tap_id)->update(['gia' => 150000]);
        $url = '/api/v1/khach-hang/chatbot/hoi-thoai/'.$this->hoi->id;
        $this->getJson($url)->assertOk()->assertJsonPath('data.1.nguon_da_kiem_tra.goi_tap.0.gia', 150000);
        // Snapshot vẫn giữ giá lúc tạo; không sửa lịch sử khi dựng thẻ mới.
        $this->assertSame(100000, TinNhanTroLy::where('vai_tro', 'ASSISTANT')->first()->nguon_da_kiem_tra['goi_tap'][0]['gia']);
        $y = YeuCauTroLy::first();
        $y->update(['trang_thai' => 'DANG_XU_LY', 'giu_luot_den' => now()->subSecond()]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.0.co_the_thu_lai', true);
        GoiTap::whereKey($this->goi->goi_tap_id)->update(['trang_thai' => 'NGUNG_SU_DUNG']);
        $this->getJson($url)->assertOk()->assertJsonCount(0, 'data.1.nguon_da_kiem_tra.goi_tap');
    }

    public function test_two_processes_cannot_spend_the_last_turn_twice(): void
    {
        $this->goi->update(['so_luot_chatbot_moi_ngay_snapshot' => 1]);
        DB::commit();
        $this->daCommit = true;
        $processes = [];
        $files = [];
        try {
            foreach ([1, 2] as $i) {
                $file = tempnam(sys_get_temp_dir(), 'chatbot-race-');
                $files[] = $file;
                file_put_contents($file, json_encode(['nguoi_id' => $this->kh->id, 'hoi_id' => $this->hoi->id, 'body' => $this->body()]));
                $p = new Process([PHP_BINARY, base_path('tests/Support/chatbot-worker.php'), self::$db, $file], base_path());
                $p->setTimeout(30);
                $p->start();
                $processes[] = $p;
            }
            $statuses = [];
            foreach ($processes as $p) {
                $p->wait();
                $this->assertTrue($p->isSuccessful(), $p->getErrorOutput());
                $statuses[] = json_decode($p->getOutput(), true)['status'];
            }
            sort($statuses);
            $this->assertContains(200, $statuses);
            $this->assertCount(1, array_filter($statuses, fn ($s) => $s === 200));
            $this->assertTrue(in_array(409, $statuses) || in_array(429, $statuses));
            $this->assertSame(1, YeuCauTroLy::where('trang_thai', 'THANH_CONG')->count());
        } finally {
            foreach ($processes as $p) {
                if ($p->isRunning()) {
                    $p->stop();
                }
            }
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    public function test_admin_documents_publish_version_replay_and_public_projection(): void
    {
        $url = '/api/v1/admin/tai-lieu-tu-van';
        $d = ['tieu_de' => 'Tự tạo giáo án', 'loai' => 'FAQ', 'noi_dung' => 'KH được tự tạo giáo án miễn phí.', 'client_request_id' => (string) Str::uuid()];
        $this->postJson($url, $d)->assertForbidden();
        $this->doiNguoi($this->taiKhoan(TaiKhoan::HUAN_LUYEN_VIEN));
        $this->postJson($url, $d)->assertForbidden();
        $admin = $this->taiKhoan(TaiKhoan::ADMIN);
        $this->doiNguoi($admin);
        $this->postJson($url, [...$d, 'nguoi_cap_nhat_id' => $this->kh->id])->assertUnprocessable();
        $this->postJson($url, [...$d, 'noi_dung' => '<script>alert(1)</script>'])->assertUnprocessable();
        $id = $this->postJson($url, $d)->assertCreated()->json('data.id');
        $this->postJson($url, $d)->assertOk()->assertJsonPath('data.id', $id);
        $this->postJson($url, [...$d, 'tieu_de' => 'Nội dung khác'])->assertConflict();
        $this->getJson('/api/v1/faq')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson($url.'/'.$id.'/xuat-ban', ['phien_ban' => 1])->assertOk()->assertJsonPath('data.phien_ban', 2);
        $this->postJson($url.'/'.$id.'/xuat-ban', ['phien_ban' => 1])->assertOk()->assertJsonPath('data.phien_ban', 2);
        $this->getJson('/api/v1/faq')->assertOk()->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.nguoi_cap_nhat_id')->assertJsonMissingPath('data.0.client_request_id');
        $sua = ['tieu_de' => $d['tieu_de'], 'loai' => 'FAQ', 'noi_dung' => 'Nội dung mới cần xuất bản lại.', 'phien_ban' => 2];
        $this->putJson($url.'/'.$id, $sua)->assertOk()->assertJsonPath('data.trang_thai', 'NHAP')->assertJsonPath('data.phien_ban', 3);
        $this->putJson($url.'/'.$id, $sua)->assertConflict();
        $this->getJson('/api/v1/faq')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/faq?trang_thai=NHAP')->assertUnprocessable();
        $this->postJson($url.'/'.$id.'/ngung', ['phien_ban' => 3])->assertConflict();
        $this->postJson($url.'/'.$id.'/xuat-ban', ['phien_ban' => 3])->assertOk();
        $this->postJson($url.'/'.$id.'/ngung', ['phien_ban' => 4])->assertOk()->assertJsonPath('data.trang_thai', 'NGUNG_SU_DUNG');
        $this->getJson('/api/v1/faq')->assertOk()->assertJsonCount(0, 'data');
        $this->doiNguoi($this->taiKhoan(TaiKhoan::ADMIN));
        $this->postJson($url, $d)->assertConflict();
    }

    public function test_document_sources_revalidate_versions_and_withdrawal(): void
    {
        $ids = [];
        foreach ([1, 2] as $i) {
            $ids[] = DB::table('tai_lieu_tu_van')->insertGetId(['tieu_de' => 'FAQ '.$i, 'loai' => 'FAQ', 'noi_dung' => 'KH tự tạo giáo án miễn phí.', 'phien_ban' => 1, 'nguoi_cap_nhat_id' => $this->kh->id, 'trang_thai' => 'DA_XUAT_BAN', 'xuat_ban_luc' => now()->subDay()]);
        }
        $this->giaLap(['generativelanguage.googleapis.com/*' => Http::response($this->provider(['nguon_tai_lieu_ids' => $ids]))]);
        $this->gui($this->body())->assertOk()->assertJsonCount(2, 'data.tin_nhan.1.nguon_da_kiem_tra.tai_lieu');
        $this->giaLap(function () use ($ids) {
            DB::table('tai_lieu_tu_van')->where('id', $ids[0])->update(['phien_ban' => 2]);

            return Http::response($this->provider(['nguon_tai_lieu_ids' => [$ids[0]]]));
        });
        $this->gui($this->body())->assertStatus(503);
        $this->getJson('/api/v1/khach-hang/chatbot/hoi-thoai/'.$this->hoi->id)->assertOk()->assertJsonCount(1, 'data.1.nguon_da_kiem_tra.tai_lieu');
        $this->giaLap(function () use ($ids) {
            DB::table('tai_lieu_tu_van')->where('id', $ids[1])->update(['trang_thai' => 'NGUNG_SU_DUNG']);

            return Http::response($this->provider(['nguon_tai_lieu_ids' => [$ids[1]]]));
        });
        $this->gui($this->body())->assertStatus(503);
        $this->assertSame(1, YeuCauTroLy::where('trang_thai', 'THANH_CONG')->count());
    }

    public function test_admin_statistics_expose_only_aggregates(): void
    {
        $this->gui($this->body())->assertOk();
        $this->giaLap(['generativelanguage.googleapis.com/*' => Http::response([], 429)]);
        $this->gui($this->body())->assertStatus(503);
        $url = '/api/v1/admin/chatbot/thong-ke';
        $this->getJson($url)->assertForbidden();
        $this->doiNguoi($this->taiKhoan(TaiKhoan::ADMIN));
        $this->getJson($url)->assertOk()->assertJsonPath('data.yeu_cau', 2)->assertJsonPath('data.thanh_cong', 1)->assertJsonPath('data.loi', 1)->assertJsonPath('data.input_tokens', 200)->assertJsonMissingPath('data.noi_dung')->assertJsonMissingPath('data.khach_hang_id');
    }

    public function test_exact_expiry_moment_does_not_grant_new_request(): void
    {
        $this->travelTo(now()->setMicrosecond(900000));
        $this->goi->update(['het_han_luc' => now()]);
        $this->gui($this->body())->assertForbidden();
        Http::assertNothingSent();
    }

    private function deXuatGiaoAn(): array
    {
        $nhom = DB::table('nhom_co')->insertGetId(['ma_nhom_co' => 'ai-test', 'ten_nhom_co' => 'Toàn thân', 'ten_nguon' => 'full body', 'trang_thai' => 'HOAT_DONG']);
        $bai = [];
        foreach (['Squat', 'Chống đẩy', 'Gập bụng', 'Chùng chân'] as $ten) {
            $id = DB::table('bai_tap')->insertGetId(['nhom_co_id' => $nhom, 'ten_bai_tap' => $ten, 'dung_cu' => 'Trọng lượng cơ thể', 'trang_thai' => 'HOAT_DONG']);
            $bai[] = ['id' => $id, 'hiep' => 3, 'lan' => 12, 'nghi' => 60];
        }

        return ['ten_ke_hoach' => 'Tập cơ bản 4 tuần', 'muc_tieu' => 'Rèn kỹ thuật và sức bền', 'buoi_tap' => array_fill(0, 12, ['ghi_chu' => 'Tập chậm, nghỉ giữa các buổi.', 'bai_tap' => $bai])];
    }

    private function yeuCauGiaoAn(): array
    {
        return $this->body(['noi_dung' => 'Tạo cho tôi giáo án 3 buổi 1 tuần tập trong 4 tuần, mỗi buổi 4 bài. Tôi mới bắt đầu, tập tại nhà không có tạ.']);
    }

    public function test_ai_creates_twelve_sessions_and_replay_keeps_one_draft(): void
    {
        $deXuat = $this->deXuatGiaoAn();
        $dangTap = KeHoachTap::create(['khach_hang_id' => $this->kh->hoSoKhachHang->id, 'nguon_tao' => 'KHACH_HANG', 'ten_ke_hoach' => 'Đang tập trước khi hỏi AI', 'trang_thai' => 'DANG_AP_DUNG']);
        $this->giaLap(['generativelanguage.googleapis.com/*' => Http::response($this->provider(['giao_an_de_xuat' => $deXuat]))]);
        $d = $this->yeuCauGiaoAn();
        $r = $this->gui($d)->assertOk()->assertJsonCount(12, 'data.tin_nhan.1.nguon_da_kiem_tra.giao_an_da_tao.buoi_tap');
        $id = $r->json('data.tin_nhan.1.nguon_da_kiem_tra.giao_an_da_tao.id');
        $k = KeHoachTap::findOrFail($id);
        $this->assertSame('NHAP', $k->trang_thai);
        $this->assertSame('KHACH_HANG', $k->nguon_tao);
        $this->assertSame($this->kh->hoSoKhachHang->id, $k->khach_hang_id);
        $this->assertSame(12, $k->so_ngay_tap);
        $this->assertSame(48, $k->cacBaiTap()->count());
        $this->assertSame($dangTap->id, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->sole()->id);
        $this->assertSame(0, DB::table('lich_tap')->count());
        $this->gui($d)->assertOk()->assertJsonPath('data.tin_nhan.1.nguon_da_kiem_tra.giao_an_da_tao.id', $id);
        Http::assertSentCount(1);
        $this->assertSame(2, KeHoachTap::count());
        Http::assertSent(fn ($request) => $request['generationConfig']['responseJsonSchema']['properties']['giao_an_de_xuat']['anyOf'][1]['properties']['buoi_tap']['minItems'] === 12
            && $request['generationConfig']['responseJsonSchema']['properties']['giao_an_de_xuat']['anyOf'][1]['properties']['buoi_tap']['items']['properties']['bai_tap']['maxItems'] === 4);
        $this->getJson('/api/v1/khach-hang/ke-hoach/'.$id)->assertOk();
        $this->getJson('/api/v1/khach-hang/chatbot/hoi-thoai/'.$this->hoi->id)->assertOk()->assertJsonPath('data.1.nguon_da_kiem_tra.giao_an_da_tao.trang_thai', 'NHAP');
        $this->gui([...$d, 'noi_dung' => $d['noi_dung'].' Mục tiêu mới.'])->assertConflict();
        $pt = $this->taiKhoan(TaiKhoan::HUAN_LUYEN_VIEN);
        $admin = $this->taiKhoan(TaiKhoan::ADMIN);
        $pc = PhanCongHuanLuyenVien::create(['khach_hang_id' => $this->kh->hoSoKhachHang->id, 'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'nguoi_phan_cong_id' => $admin->id, 'bat_dau_luc' => now()]);
        $this->doiNguoi($pt);
        $this->getJson('/api/v1/pt/ke-hoach/'.$id)->assertOk()->assertJsonPath('data.co_the_sua', false);
        $this->gui($d)->assertForbidden();
        $pc->update(['ket_thuc_luc' => now()]);
        $this->getJson('/api/v1/pt/ke-hoach/'.$id)->assertNotFound();
        $this->doiNguoi($this->taiKhoan());
        $this->getJson('/api/v1/khach-hang/ke-hoach/'.$id)->assertNotFound();
        $this->gui($this->yeuCauGiaoAn())->assertNotFound();
    }

    public function test_ai_plan_rejects_missing_sessions_duplicates_forged_ids_and_extra_fields(): void
    {
        $goc = $this->deXuatGiaoAn();
        foreach (['thieu_buoi', 'thieu_bai', 'trung_bai', 'id_gia', 'thong_so', 'chu_so_huu'] as $loi) {
            $d = $goc;
            match ($loi) {
                'thieu_buoi' => array_pop($d['buoi_tap']),
                'thieu_bai' => array_pop($d['buoi_tap'][0]['bai_tap']),
                'trung_bai' => $d['buoi_tap'][0]['bai_tap'][1]['id'] = $d['buoi_tap'][0]['bai_tap'][0]['id'],
                'id_gia' => $d['buoi_tap'][0]['bai_tap'][0]['id'] = 999999,
                'thong_so' => $d['buoi_tap'][0]['bai_tap'][0]['hiep'] = 999,
                'chu_so_huu' => $d['khach_hang_id'] = 999,
            };
            $this->giaLap(['generativelanguage.googleapis.com/*' => Http::response($this->provider(['giao_an_de_xuat' => $d]))]);
            $this->gui($this->yeuCauGiaoAn())->assertStatus(503);
        }
        $this->assertSame(0, KeHoachTap::count());
        $this->assertSame(0, YeuCauTroLy::where('trang_thai', 'THANH_CONG')->count());
        $this->assertSame(2, app(ChatbotService::class)->hanMuc($this->kh->hoSoKhachHang->id)['con_lai']);
    }

    public function test_ai_plan_and_quota_rollback_when_assistant_save_fails(): void
    {
        $d = $this->deXuatGiaoAn();
        $this->giaLap(['generativelanguage.googleapis.com/*' => Http::response($this->provider(['giao_an_de_xuat' => $d]))]);
        Event::listen('eloquent.creating: '.TinNhanTroLy::class, function ($tin) {
            if ($tin->vai_tro === 'ASSISTANT') {
                throw new \RuntimeException('Lỗi kiểm thử ghi assistant');
            }
        });
        $this->gui($this->yeuCauGiaoAn())->assertStatus(503);
        $this->assertSame(0, KeHoachTap::count());
        $this->assertSame(0, DB::table('bai_tap_trong_ke_hoach')->count());
        $this->assertSame(0, YeuCauTroLy::where('trang_thai', 'THANH_CONG')->count());
        $this->assertSame(2, app(ChatbotService::class)->hanMuc($this->kh->hoSoKhachHang->id)['con_lai']);
    }

    public function test_ai_plan_rechecks_catalog_withdrawal_before_save_and_can_ask_for_information(): void
    {
        $d = $this->deXuatGiaoAn();
        $this->giaLap(function () use ($d) {
            DB::table('bai_tap')->where('id', $d['buoi_tap'][0]['bai_tap'][0]['id'])->update(['trang_thai' => 'NGUNG_SU_DUNG']);

            return Http::response($this->provider(['giao_an_de_xuat' => $d]));
        });
        $this->gui($this->yeuCauGiaoAn())->assertStatus(503);
        $this->assertSame(0, KeHoachTap::count());
        DB::table('bai_tap')->update(['trang_thai' => 'HOAT_DONG']);
        $this->giaLap(['generativelanguage.googleapis.com/*' => Http::response($this->provider(['giao_an_de_xuat' => null, 'noi_dung' => 'Bạn có dụng cụ gì để tập?']))]);
        $this->gui($this->yeuCauGiaoAn())->assertOk();
        $this->assertSame(0, KeHoachTap::count());
    }

    public function test_generation_dimensions_and_non_commands_are_distinguished(): void
    {
        $s = app(GiaoAnAiService::class);
        $this->assertSame(['buoi_moi_tuan' => 3, 'so_tuan' => 4, 'bai_moi_buoi' => 4], $s->docYeuCau($this->yeuCauGiaoAn()['noi_dung']));
        $this->assertNull($s->docYeuCau('KH có thể tự tạo giáo án 3 buổi/tuần trong 4 tuần, mỗi buổi 4 bài không?'));
        $this->assertNull($s->docYeuCau('Không tạo giáo án, chỉ giải thích 3 buổi/tuần trong 4 tuần, mỗi buổi 4 bài.'));
        $this->assertNull($s->docYeuCau('Tạo cho tôi một giáo án.'));
        $this->gui($this->body(['noi_dung' => 'Tạo cho tôi giáo án 7 buổi/tuần trong 8 tuần, mỗi buổi 8 bài.']))->assertUnprocessable();
        Http::assertNothingSent();
    }
}
