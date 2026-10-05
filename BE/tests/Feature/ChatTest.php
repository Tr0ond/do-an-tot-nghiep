<?php

namespace Tests\Feature;

use App\Events\ChatCanDongBo;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use App\Services\ChatService;
use App\Services\PhanCongService;
use App\Services\PhienMobileService;
use App\Services\TaiKhoanService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;
use Tests\TestCase;

class ChatTest extends TestCase
{
    private static ?string $tenDatabase = null;

    private static ?PDO $pdoMayChu = null;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'chat-test-key', 'broadcasting.connections.reverb.secret' => 'chat-test-secret', 'broadcasting.connections.reverb.app_id' => 'chat-test']);
        Broadcast::purge('reverb');
        require base_path('routes/channels.php');
        Event::fake([ChatCanDongBo::class]);
        Storage::fake('local');
        $cauHinh = config('database.connections.mysql');
        if (self::$tenDatabase === null) {
            $cauHinh['database'] = null;
            $cauHinh['url'] = null;
            config(['database.connections.may_chu_chat' => $cauHinh]);
            self::$pdoMayChu = DB::connection('may_chu_chat')->getPdo();
            self::$tenDatabase = 'kiem_tra_chat_'.bin2hex(random_bytes(8));
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
        if (self::$tenDatabase && preg_match('/^kiem_tra_chat_[a-f0-9]{16}$/D', self::$tenDatabase)) {
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

    private function bo(): array
    {
        $khach = $this->nguoi(TaiKhoan::KHACH_HANG);
        $pt = $this->nguoi(TaiKhoan::HUAN_LUYEN_VIEN);
        $admin = $this->nguoi(TaiKhoan::ADMIN);
        $phanCong = PhanCongHuanLuyenVien::create(['khach_hang_id' => $khach->hoSoKhachHang->id, 'huan_luyen_vien_id' => $pt->hoSoHuanLuyenVien->id, 'nguoi_phan_cong_id' => $admin->id, 'bat_dau_luc' => now()->subHour(), 'client_request_id' => (string) Str::uuid()]);
        $id = DB::table('hoi_thoai')->insertGetId(['phan_cong_id' => $phanCong->id, 'created_at' => now(), 'updated_at' => now()]);

        return compact('khach', 'pt', 'admin', 'phanCong', 'id');
    }

    private function dangNhap(TaiKhoan $nguoi): void
    {
        Auth::forgetGuards();
        $this->actingAs($nguoi, 'web');
    }

    private function payload(string $noiDung = 'Xin chào PT'): array
    {
        return ['client_message_id' => (string) Str::uuid(), 'noi_dung' => $noiDung];
    }

    public function test_bearer_mobile_chat_anh_auth_thong_bao_va_thu_hoi_phan_cong(): void
    {
        $b = $this->bo();
        $token = fn (TaiKhoan $nguoi) => app(PhienMobileService::class)->dangNhap(['email' => $nguoi->email, 'password' => 'Demo123456!', 'ten_thiet_bi' => 'Android MB4'])->plainTextToken;
        $kh = $token($b['khach']);
        $pt = $token($b['pt']);
        $ngoai = $token($this->nguoi(TaiKhoan::KHACH_HANG));
        $phien = function (string $t) {
            Auth::forgetGuards();
            $this->flushHeaders();
            $this->withHeader('Authorization', 'Bearer '.$t);
        };
        $url = '/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan';
        $p = [...$this->payload('Ảnh qua bearer'), 'anh' => [UploadedFile::fake()->image('mobile.png')]];
        $phien($kh);
        $tin = $this->post($url, $p, ['Accept' => 'application/json'])->assertOk()->json('data');
        $phien($kh);
        $this->post($url, $p, ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.id', $tin['id']);
        $phien($pt);
        $this->getJson('/api/v1/hoi-thoai')->assertJsonPath('meta.so_chua_doc', 1);
        $phien($pt);
        $this->getJson($url)->assertJsonPath('data.tin_nhan.0.id', $tin['id']);
        $anhUrl = $url.'/'.$tin['id'].'/anh/0';
        $phien($pt);
        $this->get($anhUrl, ['Accept' => 'application/json'])->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $phien($pt);
        $this->postJson('/api/v1/hoi-thoai/'.$b['id'].'/da-doc', ['tin_nhan_id' => $tin['id']])->assertOk();
        $phien($pt);
        $this->getJson('/api/v1/hoi-thoai')->assertJsonPath('meta.so_chua_doc', 0);
        $phien($pt);
        $this->postJson('/api/v1/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-chat.tai-khoan.'.$b['pt']->id])->assertOk()->assertJsonStructure(['auth'])->assertJsonMissingPath('status');
        $phien($pt);
        $this->postJson('/api/v1/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-chat.tai-khoan.'.$b['khach']->id])->assertForbidden();
        $thongBaoId = (string) Str::uuid();
        $b['khach']->notifications()->create(['id' => $thongBaoId, 'type' => 'mb4', 'data' => ['tieu_de' => 'Thông báo thử', 'noi_dung' => 'Nội dung thử', 'duong_dan' => '/khach-hang/ho-so']]);
        $phien($kh);
        $this->getJson('/api/v1/thong-bao')->assertJsonPath('meta.so_chua_doc', 1);
        $phien($ngoai);
        $this->postJson('/api/v1/thong-bao/'.$thongBaoId.'/da-doc')->assertNotFound();
        $phien($kh);
        $this->postJson('/api/v1/thong-bao/'.$thongBaoId.'/da-doc')->assertOk();
        $b['phanCong']->forceFill(['ket_thuc_luc' => now()])->save();
        $phien($pt);
        $this->getJson($url.'?after_id='.$tin['id'])->assertNotFound();
        $phien($pt);
        $this->get($anhUrl, ['Accept' => 'application/json'])->assertNotFound();
        $phien($kh);
        $this->getJson($url)->assertJsonPath('data.hoi_thoai.co_the_gui', false);
        $phien($kh);
        $this->postJson($url, $this->payload())->assertConflict();
        $phien($kh);
        $this->postJson('/api/v1/mobile/dang-xuat')->assertOk();
        $phien($kh);
        $this->postJson('/api/v1/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-chat.tai-khoan.'.$b['khach']->id])->assertUnauthorized();
        $phien($kh);
        $this->get($anhUrl, ['Accept' => 'application/json'])->assertUnauthorized();
    }

    public function test_scope_khach_pt_admin_va_nguoi_ngoai(): void
    {
        $b = $this->bo();
        $url = '/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan';
        $this->getJson('/api/v1/hoi-thoai')->assertUnauthorized();
        $this->getJson($url)->assertUnauthorized();
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN] as $vaiTro) {
            $this->dangNhap($this->nguoi($vaiTro));
            $this->getJson('/api/v1/hoi-thoai')->assertOk()->assertJsonCount(0, 'data');
            $this->getJson($url)->assertNotFound();
            $this->postJson($url, $this->payload())->assertNotFound();
            $this->postJson('/api/v1/hoi-thoai/'.$b['id'].'/da-doc', ['tin_nhan_id' => 1])->assertNotFound();
        }
        $this->dangNhap($b['admin']);
        $this->getJson('/api/v1/hoi-thoai')->assertForbidden();
        $this->getJson($url)->assertForbidden();
        foreach (['khach', 'pt'] as $nguoi) {
            $this->dangNhap($b[$nguoi]);
            $this->getJson('/api/v1/hoi-thoai')->assertOk()->assertJsonPath('data.0.id', $b['id']);
        }
    }

    public function test_gui_retry_va_key_khac_noi_dung_khong_tru_luot_goi(): void
    {
        $b = $this->bo();
        $this->dangNhap($b['khach']);
        $p = $this->payload('<script>alert("xss")</script> Xin chào');
        $url = '/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan';
        $id = $this->postJson($url, $p)->assertOk()->assertJsonPath('data.nguoi_gui_id', $b['khach']->id)->json('data.id');
        $this->postJson($url, $p)->assertOk()->assertJsonPath('data.id', $id);
        $this->postJson($url, [...$p, 'noi_dung' => 'Khác'])->assertConflict();
        $this->assertSame(1, DB::table('tin_nhan')->where('hoi_thoai_id', $b['id'])->count());
        $this->assertSame(0, DB::table('dang_ky_goi_tap')->count());
        $this->dangNhap($b['pt']);
        $this->getJson($url)->assertOk()->assertJsonPath('data.tin_nhan.0.noi_dung', $p['noi_dung']);
        $this->getJson('/api/v1/hoi-thoai')->assertOk()->assertJsonPath('data.0.so_chua_doc', 1)->assertJsonPath('meta.so_chua_doc', 1);
    }

    public function test_gui_chu_voi_mang_anh_rong_va_retry_khong_tao_tin_trung(): void
    {
        $b = $this->bo();
        $url = '/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan';
        foreach (['khach', 'pt'] as $nguoi) {
            $this->dangNhap($b[$nguoi]);
            $p = $this->payload('Tin chỉ có chữ');
            $id = $this->postJson($url, [...$p, 'anh' => []])->assertOk()->assertJsonPath('data.noi_dung', $p['noi_dung'])->assertJsonCount(0, 'data.anh')->json('data.id');
            $this->postJson($url, $p)->assertOk()->assertJsonPath('data.id', $id);
            $this->postJson($url, [...$p, 'anh' => []])->assertOk()->assertJsonPath('data.id', $id);
            $this->postJson($url, [...$this->payload('   '), 'anh' => []])->assertUnprocessable()->assertJsonValidationErrors('noi_dung');
            $this->postJson($url, [...$this->payload(), 'anh' => 'khong-phai-mang'])->assertUnprocessable()->assertJsonValidationErrors('anh');
        }
        $this->assertSame(2, DB::table('tin_nhan')->where('hoi_thoai_id', $b['id'])->count());
    }

    public function test_cursor_tang_don_dieu_va_khong_nhan_id_hoi_thoai_khac(): void
    {
        $b = $this->bo();
        $chat = app(ChatService::class);
        $mot = $chat->gui($b['khach'], $b['id'], $this->payload('Một'));
        $hai = $chat->gui($b['khach'], $b['id'], $this->payload('Hai'));
        $khac = $this->bo();
        $tinKhac = $chat->gui($khac['khach'], $khac['id'], $this->payload('Riêng'));
        $this->dangNhap($b['pt']);
        $url = '/api/v1/hoi-thoai/'.$b['id'].'/da-doc';
        $this->postJson($url, ['tin_nhan_id' => $hai['id']])->assertOk()->assertJsonPath('data.cursor_da_doc', $hai['id']);
        $this->postJson($url, ['tin_nhan_id' => $mot['id']])->assertOk()->assertJsonPath('data.cursor_da_doc', $hai['id']);
        $this->postJson($url, ['tin_nhan_id' => $tinKhac['id']])->assertUnprocessable();
        $this->getJson('/api/v1/hoi-thoai')->assertOk()->assertJsonPath('meta.so_chua_doc', 0);
        $this->dangNhap($b['khach']);
        $this->getJson('/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan')->assertJsonPath('data.hoi_thoai.cursor_doi_phuong', $hai['id']);
    }

    public function test_phan_trang_tin_va_tai_bu_sau_cursor(): void
    {
        $b = $this->bo();
        $ids = [];
        for ($i = 0; $i < 123; $i++) {
            $ids[] = DB::table('tin_nhan')->insertGetId(['hoi_thoai_id' => $b['id'], 'nguoi_gui_id' => $b['khach']->id, 'client_message_id' => (string) Str::uuid(), 'noi_dung' => 'Tin '.$i, 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->dangNhap($b['pt']);
        $url = '/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan';
        $this->getJson($url)->assertOk()->assertJsonCount(50, 'data.tin_nhan')->assertJsonPath('data.tin_nhan.0.id', $ids[73])->assertJsonPath('data.con_tin', true);
        $this->getJson($url.'?before_id='.$ids[73])->assertOk()->assertJsonPath('data.tin_nhan.0.id', $ids[23]);
        $this->getJson($url.'?after_id=0')->assertOk()->assertJsonPath('data.tin_nhan.0.id', $ids[0])->assertJsonPath('data.tin_nhan.49.id', $ids[49])->assertJsonPath('data.con_tin', true);
        $this->getJson($url.'?after_id='.$ids[99])->assertOk()->assertJsonCount(23, 'data.tin_nhan')->assertJsonPath('data.con_tin', false);
        $this->getJson($url.'?before_id=1&after_id=1')->assertUnprocessable();
    }

    public function test_doi_pt_thu_hoi_quyen_cu_khach_con_lich_su_pt_moi_khong_doc_cu(): void
    {
        $b = $this->bo();
        app(ChatService::class)->gui($b['khach'], $b['id'], $this->payload('Lịch sử riêng'));
        $ptMoi = $this->nguoi(TaiKhoan::HUAN_LUYEN_VIEN);
        $moi = app(PhanCongService::class)->phanCong($b['admin'], ['khach_hang_id' => $b['khach']->hoSoKhachHang->id, 'huan_luyen_vien_id' => $ptMoi->hoSoHuanLuyenVien->id, 'phan_cong_hien_tai_id' => $b['phanCong']->id, 'client_request_id' => (string) Str::uuid(), 'ly_do' => 'Đổi phụ trách']);
        $idMoi = DB::table('hoi_thoai')->where('phan_cong_id', $moi->id)->value('id');
        $this->dangNhap($b['pt']);
        $this->getJson('/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan')->assertNotFound();
        $this->postJson('/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan', $this->payload())->assertNotFound();
        $this->getJson('/api/v1/hoi-thoai')->assertJsonCount(0, 'data');
        $this->dangNhap($ptMoi);
        $this->getJson('/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan')->assertNotFound();
        $this->getJson('/api/v1/hoi-thoai/'.$idMoi.'/tin-nhan')->assertOk()->assertJsonCount(0, 'data.tin_nhan');
        $this->dangNhap($b['khach']);
        $this->getJson('/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan')->assertOk()->assertJsonPath('data.hoi_thoai.co_the_gui', false)->assertJsonPath('data.tin_nhan.0.noi_dung', 'Lịch sử riêng');
        $this->postJson('/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan', $this->payload())->assertConflict();
        $this->postJson('/api/v1/hoi-thoai/'.$idMoi.'/tin-nhan', $this->payload('Gửi PT mới'))->assertOk();
    }

    public function test_authorize_kenh_ca_nhan_va_khoa_tai_khoan(): void
    {
        $b = $this->bo();
        $payload = ['socket_id' => '1.2', 'channel_name' => 'private-chat.tai-khoan.'.$b['khach']->id];
        $this->postJson('/api/v1/broadcasting/auth', $payload)->assertUnauthorized();
        $this->dangNhap($b['khach']);
        $this->postJson('/api/v1/broadcasting/auth', $payload)->assertOk()->assertJsonStructure(['auth']);
        $this->postJson('/api/v1/broadcasting/auth', [...$payload, 'channel_name' => 'private-chat.tai-khoan.'.$b['pt']->id])->assertForbidden();
        $b['khach']->forceFill(['trang_thai' => TaiKhoan::BI_KHOA])->save();
        $this->dangNhap($b['khach']->fresh());
        $this->postJson('/api/v1/broadcasting/auth', $payload)->assertForbidden();
        $this->dangNhap($b['admin']);
        $this->postJson('/api/v1/broadcasting/auth', [...$payload, 'channel_name' => 'private-chat.tai-khoan.'.$b['admin']->id])->assertForbidden();
    }

    public function test_validation_rate_limit_va_doi_phuong_bi_khoa(): void
    {
        $b = $this->bo();
        $this->dangNhap($b['khach']);
        $url = '/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan';
        foreach (['', str_repeat('a', 4001)] as $body) {
            $this->postJson($url, $this->payload($body))->assertUnprocessable();
        }
        $this->postJson($url, ['noi_dung' => 'Hi', 'client_message_id' => 'sai'])->assertUnprocessable();
        $b['pt']->forceFill(['trang_thai' => TaiKhoan::BI_KHOA])->save();
        $this->getJson($url)->assertOk()->assertJsonPath('data.hoi_thoai.co_the_gui', false);
        $this->postJson($url, $this->payload())->assertConflict();
        $b['pt']->forceFill(['trang_thai' => TaiKhoan::HOAT_DONG])->save();
        $p = $this->payload();
        for ($i = 0; $i < 26; $i++) {
            $this->postJson($url, $p)->assertOk();
        }
        $this->postJson($url, $p)->assertTooManyRequests();
    }

    public function test_event_chi_sau_commit_khong_chua_noi_dung_va_rollback_khong_phat(): void
    {
        $b = $this->bo();
        $chat = app(ChatService::class);
        $chat->gui($b['khach'], $b['id'], $this->payload('Nội dung riêng'));
        Event::assertNotDispatched(ChatCanDongBo::class);
        DB::commit();
        Event::assertDispatched(ChatCanDongBo::class, function ($event) use ($b) {
            $this->assertSame(['can_dong_bo' => true], $event->broadcastWith());
            $this->assertEquals(['private-chat.tai-khoan.'.$b['khach']->id, 'private-chat.tai-khoan.'.$b['pt']->id], array_map(fn ($c) => $c->name, $event->broadcastOn()));

            return true;
        });
        Event::fake([ChatCanDongBo::class]);
        DB::beginTransaction();
        $chat->gui($b['khach'], $b['id'], $this->payload('Rollback'));
        DB::rollBack();
        Event::assertNotDispatched(ChatCanDongBo::class);
        $this->assertSame(1, DB::table('tin_nhan')->where('hoi_thoai_id', $b['id'])->count());
    }

    public function test_hai_process_gui_cung_uuid_chi_luu_mot_tin(): void
    {
        $b = $this->bo();
        $ma = (string) Str::uuid();
        DB::commit();
        $kq = $this->haiWorker($b, $ma);
        $this->assertTrue($kq[0]['ok']);
        $this->assertTrue($kq[1]['ok']);
        $this->assertSame($kq[0]['id'], $kq[1]['id']);
        $this->assertSame(1, DB::table('tin_nhan')->where('hoi_thoai_id', $b['id'])->count());
    }

    private function haiWorker(array $b, string $ma, ?string $tepAnh = null): array
    {
        $workers = [];
        DB::beginTransaction();
        DB::table('ho_so_khach_hang')->where('id', $b['phanCong']->khach_hang_id)->lockForUpdate()->first();
        try {
            for ($i = 0; $i < 2; $i++) {
                $ready = tempnam(sys_get_temp_dir(), 'chat-ready-');
                unlink($ready);
                $out = tempnam(sys_get_temp_dir(), 'chat-out-');
                $err = tempnam(sys_get_temp_dir(), 'chat-err-');
                $lenh = [PHP_BINARY, base_path('tests/Support/chat-worker.php'), self::$tenDatabase, (string) $b['khach']->id, (string) $b['id'], $ready, $ma];
                if ($tepAnh) {
                    array_push($lenh, $tepAnh, Storage::disk('local')->path(''));
                }
                $proc = proc_open($lenh, [0 => ['pipe', 'r'], 1 => ['file', $out, 'w'], 2 => ['file', $err, 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
                $this->assertIsResource($proc);
                fclose($pipes[0]);
                $workers[] = compact('proc', 'ready', 'out', 'err');
            }
            $han = microtime(true) + 10;
            while ((! file_exists($workers[0]['ready']) || ! file_exists($workers[1]['ready'])) && microtime(true) < $han) {
                usleep(20000);
            }
            foreach ($workers as $worker) {
                $this->assertFileExists($worker['ready']);
            }
            DB::commit();
            $kq = [];
            foreach ($workers as $worker) {
                $this->assertSame(0, proc_close($worker['proc']), file_get_contents($worker['out']).file_get_contents($worker['err']));
                $kq[] = json_decode(file_get_contents($worker['out']), true, flags: JSON_THROW_ON_ERROR);
            }

            return $kq;
        } finally {
            if (DB::transactionLevel()) {
                DB::rollBack();
            }
            foreach ($workers as $worker) {
                if (is_resource($worker['proc'])) {
                    proc_terminate($worker['proc']);
                    proc_close($worker['proc']);
                }
                foreach (['ready', 'out', 'err'] as $key) {
                    if (file_exists($worker[$key])) {
                        unlink($worker[$key]);
                    }
                }
            }
        }
    }

    public function test_reverb_thuc_hai_client_va_socket_pt_cu_khong_nhan_noi_dung(): void
    {
        $b = $this->bo();
        DB::commit();
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) substr(strrchr(stream_socket_get_name($server, false), ':'), 1);
        fclose($server);
        $ipc = tempnam(sys_get_temp_dir(), 'chat-live-');
        $log = $ipc.'.log';
        $env = array_merge(getenv(), ['REVERB_APP_ID' => 'chat-test', 'REVERB_APP_KEY' => 'chat-test-key', 'REVERB_APP_SECRET' => 'chat-test-secret', 'REVERB_ALLOWED_ORIGINS' => 'localhost']);
        $reverb = proc_open([PHP_BINARY, base_path('artisan'), 'reverb:start', '--host=127.0.0.1', '--port='.$port], [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, base_path(), $env, ['bypass_shell' => true]);
        fclose($pipes[0]);
        $node = null;
        try {
            $this->doiDieuKien(fn () => (bool) @fsockopen('127.0.0.1', $port, timeout: .1));
            config(['broadcasting.connections.reverb.options.port' => $port]);
            Broadcast::purge('reverb');
            require base_path('routes/channels.php');
            Event::fakeExcept([ChatCanDongBo::class]);
            $node = proc_open(['node', base_path('../FE/tests/support/reverb-client.mjs'), (string) $port, $ipc], [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, base_path(), null, ['bypass_shell' => true]);
            fclose($pipes[0]);
            $this->doiDieuKien(fn () => file_exists($ipc.'.ids'));
            $ids = json_decode(file_get_contents($ipc.'.ids'), true);
            $auth = [];
            foreach (['khach', 'pt'] as $i => $key) {
                $this->dangNhap($b[$key]);
                $channel = 'private-chat.tai-khoan.'.$b[$key]->id;
                $token = $this->postJson('/api/v1/broadcasting/auth', ['socket_id' => $ids[$i], 'channel_name' => $channel])->assertOk()->json('auth');
                $auth[] = ['channel' => $channel, 'auth' => $token];
            }
            file_put_contents($ipc.'.auth', json_encode($auth));
            $this->doiDieuKien(fn () => file_exists($ipc.'.ready'));
            $chat = app(ChatService::class);
            $tin = $chat->gui($b['khach'], $b['id'], [...$this->payload('Nội dung chỉ qua HTTP'), 'anh' => [UploadedFile::fake()->image('anh-realtime.png')]]);
            $docEvents = fn () => file_exists($ipc.'.events') ? array_map(fn ($line) => json_decode($line, true), file($ipc.'.events', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : [];
            $this->doiDieuKien(fn () => count($docEvents()) >= 2);
            $this->assertEqualsCanonicalizing([0, 1], array_column($docEvents(), 'client'));
            $this->dangNhap($b['pt']);
            $this->getJson('/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan')->assertJsonCount(1, 'data.tin_nhan.0.anh');
            $this->get('/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan/'.$tin['id'].'/anh/0')->assertOk();
            $ptMoi = $this->nguoi(TaiKhoan::HUAN_LUYEN_VIEN);
            $pcMoi = app(PhanCongService::class)->phanCong($b['admin'], ['khach_hang_id' => $b['phanCong']->khach_hang_id, 'huan_luyen_vien_id' => $ptMoi->hoSoHuanLuyenVien->id, 'phan_cong_hien_tai_id' => $b['phanCong']->id, 'client_request_id' => (string) Str::uuid(), 'ly_do' => 'Kiểm thử thu hồi']);
            $this->doiDieuKien(fn () => count($docEvents()) >= 4);
            $cu = count(array_filter($docEvents(), fn ($e) => $e['client'] === 1));
            $idMoi = DB::table('hoi_thoai')->where('phan_cong_id', $pcMoi->id)->value('id');
            $chat->gui($b['khach'], $idMoi, $this->payload('Riêng với PT mới'));
            $this->doiDieuKien(fn () => count($docEvents()) >= 5);
            $this->assertSame($cu, count(array_filter($docEvents(), fn ($e) => $e['client'] === 1)));
            foreach ($docEvents() as $event) {
                $this->assertSame(['can_dong_bo' => true], json_decode($event['data'], true));
            }
            $this->dangNhap($b['pt']);
            $this->getJson('/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan?after_id='.$tin['id'])->assertNotFound();
            file_put_contents($ipc.'.stop', 'ok');
            $this->assertSame(0, proc_close($node));
            $node = null;
        } finally {
            foreach ([$node, $reverb] as $proc) {
                if (is_resource($proc)) {
                    proc_terminate($proc);
                    proc_close($proc);
                }
            }
            foreach (['', '.log', '.ids', '.auth', '.ready', '.events', '.stop'] as $suffix) {
                if (file_exists($ipc.$suffix)) {
                    unlink($ipc.$suffix);
                }
            }
        }
    }

    private function doiDieuKien(callable $kiemTra): void
    {
        $han = microtime(true) + 8;
        do {
            if ($kiemTra()) {
                return;
            }
            usleep(25000);
        } while (microtime(true) < $han);
        $this->fail('Kết nối Reverb kiểm thử không sẵn sàng trong 8 giây.');
    }

    public function test_gui_anh_kem_chu_thich_retry_khong_nhan_doi_tep_va_noi_dung(): void
    {
        $b = $this->bo();
        $this->dangNhap($b['khach']);
        $url = '/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan';
        $p = [...$this->payload('Ảnh buổi tập'), 'anh' => [UploadedFile::fake()->image('buoi-tap.png')]];
        $tin = $this->post($url, $p, ['Accept' => 'application/json'])->assertOk()->assertJsonCount(1, 'data.anh')->json('data');
        $this->assertArrayNotHasKey('duong_dan', $tin['anh'][0]);
        $this->post($url, $p, ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.id', $tin['id']);
        $this->assertCount(1, Storage::disk('local')->allFiles('chat'));
        $this->post($url, [...$p, 'anh' => [UploadedFile::fake()->image('khac.png', 20, 20)]], ['Accept' => 'application/json'])->assertConflict();
        $this->post($url, [...$p, 'noi_dung' => 'Đổi chú thích'], ['Accept' => 'application/json'])->assertConflict();
        $this->postJson($url, array_diff_key($p, ['anh' => true]))->assertConflict();
        $this->assertSame(1, DB::table('tin_nhan')->where('hoi_thoai_id', $b['id'])->count());
        $this->dangNhap($b['pt']);
        $this->getJson($url)->assertOk()->assertJsonPath('data.tin_nhan.0.anh.0.ten', 'buoi-tap.png');
        $anhUrl = $url.'/'.$tin['id'].'/anh/0';
        $this->get($anhUrl)->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('Cache-Control', 'no-store, private')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get($url.'/'.$tin['id'].'/anh/1')->assertNotFound();
    }

    public function test_anh_rieng_tu_nguoi_ngoai_admin_va_pt_cu_khong_doc_duoc(): void
    {
        $b = $this->bo();
        $this->dangNhap($b['khach']);
        $url = '/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan';
        $tin = $this->post($url, [...$this->payload(''), 'anh' => [UploadedFile::fake()->image('anh.png')]], ['Accept' => 'application/json'])->assertOk()->json('data');
        $anhUrl = $url.'/'.$tin['id'].'/anh/0';
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN] as $vaiTro) {
            $this->dangNhap($this->nguoi($vaiTro));
            $this->get($anhUrl)->assertNotFound();
        }
        $this->dangNhap($b['admin']);
        $this->get($anhUrl)->assertForbidden();
        $b['phanCong']->forceFill(['ket_thuc_luc' => now()])->save();
        $this->dangNhap($b['pt']);
        $this->get($anhUrl)->assertNotFound();
        $this->dangNhap($b['khach']);
        $this->get($anhUrl)->assertOk();
        $this->post($url, [...$this->payload(), 'anh' => [UploadedFile::fake()->image('moi.png')]], ['Accept' => 'application/json'])->assertConflict();
        $this->assertCount(1, Storage::disk('local')->allFiles('chat'));
        $b['khach']->forceFill(['trang_thai' => TaiKhoan::BI_KHOA])->save();
        $this->dangNhap($b['khach']->fresh());
        $this->get($anhUrl)->assertForbidden();
    }

    public function test_validate_anh_5mb_4_tep_mime_that_va_tin_khong_chu_thich(): void
    {
        $b = $this->bo();
        $this->dangNhap($b['khach']);
        $url = '/api/v1/hoi-thoai/'.$b['id'].'/tin-nhan';
        foreach ([
            [UploadedFile::fake()->image('lon.png')->size(5121)],
            array_map(fn () => UploadedFile::fake()->image('anh.png'), range(1, 5)),
            [UploadedFile::fake()->createWithContent('gia.png', '<?php echo "bad";')],
            [UploadedFile::fake()->createWithContent('anh.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>')],
            [UploadedFile::fake()->image('rong.png', 8001, 1)],
        ] as $anh) {
            $this->post($url, [...$this->payload(), 'anh' => $anh], ['Accept' => 'application/json'])->assertUnprocessable();
        }
        $p = [...$this->payload(''), 'anh' => array_map(fn () => UploadedFile::fake()->image('anh.png')->size(5120), range(1, 4))];
        $this->post($url, $p, ['Accept' => 'application/json'])->assertOk()->assertJsonCount(4, 'data.anh')->assertJsonPath('data.noi_dung', '');
        $this->getJson('/api/v1/hoi-thoai')->assertJsonPath('data.0.tin_cuoi', 'Đã gửi ảnh');
        $this->assertCount(4, Storage::disk('local')->allFiles('chat'));
    }

    public function test_loi_database_rollback_tin_va_don_tep_da_luu(): void
    {
        $b = $this->bo();
        DB::listen(function ($query) {
            if (str_starts_with($query->sql, 'insert into `tin_nhan`')) {
                throw new \RuntimeException('Lỗi DB kiểm thử');
            }
        });
        try {
            app(ChatService::class)->gui($b['khach'], $b['id'], [...$this->payload(), 'anh' => [UploadedFile::fake()->image('anh.png')]]);
            $this->fail('Phải báo lỗi DB.');
        } catch (\RuntimeException $loi) {
            $this->assertSame('Lỗi DB kiểm thử', $loi->getMessage());
        }
        $this->assertSame(0, DB::table('tin_nhan')->where('hoi_thoai_id', $b['id'])->count());
        $this->assertCount(0, Storage::disk('local')->allFiles('chat'));
        Event::assertNotDispatched(ChatCanDongBo::class);
    }

    public function test_hai_process_gui_cung_anh_va_uuid_chi_luu_mot_tin_mot_tep(): void
    {
        $b = $this->bo();
        $anh = UploadedFile::fake()->image('anh.png');
        DB::commit();
        $kq = $this->haiWorker($b, (string) Str::uuid(), $anh->getRealPath());
        $this->assertTrue($kq[0]['ok']);
        $this->assertTrue($kq[1]['ok']);
        $this->assertSame($kq[0]['id'], $kq[1]['id']);
        $this->assertCount(1, Storage::disk('local')->allFiles('chat'));
        $this->assertSame(1, DB::table('tin_nhan')->where('hoi_thoai_id', $b['id'])->count());
    }
}
