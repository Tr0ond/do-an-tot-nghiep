<?php

namespace Tests\Feature;

use App\Models\BaiTap;
use App\Models\DangKyGoiTap;
use App\Models\GoiTap;
use App\Models\KeHoachTap;
use App\Models\TaiKhoan;
use App\Services\PayosService;
use App\Services\TaiKhoanService;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PDO;
use Tests\TestCase;

// Nối các API thật qua nhiều vai trò; chỉ giả lập cổng thanh toán và Gemini.
class HanhTrinhNghiepVuTest extends TestCase
{
    private static ?string $db = null;

    private static ?PDO $pdo = null;

    private TaiKhoan $kh;

    private TaiKhoan $pt;

    private TaiKhoan $admin;

    private int $baiId;

    protected function setUp(): void
    {
        parent::setUp();
        if (self::$db === null) {
            $cfg = config('database.connections.mysql');
            $cfg['database'] = null;
            $cfg['url'] = null;
            config(['database.connections.may_chu_hanh_trinh' => $cfg]);
            self::$pdo = DB::connection('may_chu_hanh_trinh')->getPdo();
            self::$db = 'kiem_tra_hanh_trinh_'.bin2hex(random_bytes(8));
            self::$pdo->exec('CREATE DATABASE `'.self::$db.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
        config(['database.connections.mysql.database' => self::$db, 'database.connections.mysql.url' => null,
            'database.default' => 'mysql', 'payos.client_id' => 'qa-client', 'payos.api_key' => 'qa-api',
            'payos.checksum_key' => 'qa-checksum', 'payos.api_url' => 'https://api-merchant.payos.vn', 'chatbot.key' => 'qa-key']);
        DB::purge('mysql');
        $this->assertSame(self::$db, DB::selectOne('SELECT DATABASE() AS ten')->ten);
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        DB::beginTransaction();
        $this->travelTo(now()->setDate(2026, 10, 4)->setTime(1, 0, 0));
        Cache::flush();
        $this->giaLap();
        $this->kh = $this->taiKhoan(TaiKhoan::KHACH_HANG);
        $this->pt = $this->taiKhoan(TaiKhoan::HUAN_LUYEN_VIEN);
        $this->admin = $this->taiKhoan(TaiKhoan::ADMIN);
        $nhom = DB::table('nhom_co')->insertGetId(['ma_nhom_co' => 'qa-hanh-trinh', 'ten_nhom_co' => 'Toàn thân', 'ten_nguon' => 'QA', 'trang_thai' => 'HOAT_DONG']);
        $this->baiId = BaiTap::create(['nhom_co_id' => $nhom, 'ten_bai_tap' => 'Squat kiểm thử', 'trang_thai' => 'HOAT_DONG'])->id;
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        if (DB::transactionLevel()) {
            DB::rollBack(0);
        }
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$db && preg_match('/^kiem_tra_hanh_trinh_[a-f0-9]{16}$/D', self::$db)) {
            self::$pdo->exec('DROP DATABASE `'.self::$db.'`');
        }
        self::$db = null;
        self::$pdo = null;
        parent::tearDownAfterClass();
    }

    private function taiKhoan(string $vaiTro): TaiKhoan
    {
        return app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Demo hành trình', 'email' => Str::uuid().'@example.test', 'password' => 'Demo123456!'], $vaiTro);
    }

    private function doiNguoi(TaiKhoan $nguoi): void
    {
        Auth::forgetGuards();
        $this->actingAs($nguoi->fresh(), 'web');
    }

    private function giaLap(array $responses = []): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        if ($responses) {
            Http::fake($responses);
        }
    }

    private function taoDon(int $buoi = 4, int $ngay = 30): DangKyGoiTap
    {
        $goi = GoiTap::create(['ten_goi' => 'Gói hành trình', 'gia' => 99000, 'co_chatbot' => true,
            'so_luot_chatbot_moi_ngay' => 2, 'so_buoi_pt' => $buoi, 'thoi_han_ngay' => $ngay, 'trang_thai' => 'HOAT_DONG']);
        $this->doiNguoi($this->kh);
        $body = ['goi_tap_id' => $goi->id, 'client_request_id' => (string) Str::uuid()];
        $id = $this->postJson('/api/v1/khach-hang/don-hang', $body)->assertOk()->json('data.id');
        $this->postJson('/api/v1/khach-hang/don-hang', $body)->assertOk()->assertJsonPath('data.id', $id);
        $this->getJson('/api/v1/khach-hang/goi-cua-toi')->assertOk()->assertJsonPath('data.goi', null);

        return DangKyGoiTap::findOrFail($id);
    }

    private function thanhToan(DangKyGoiTap $don, int $tien = 99000): void
    {
        $payos = app(PayosService::class);
        $link = ['id' => 'qa-link-'.$don->id, 'paymentLinkId' => 'qa-link-'.$don->id, 'orderCode' => $don->ma_don_payos, 'amount' => $don->gia_snapshot,
            'status' => 'PENDING', 'checkoutUrl' => 'https://pay.payos.vn/web/qa-link-'.$don->id, 'qrCode' => 'QA'];
        $daTra = [...$link, 'status' => 'PAID', 'amountPaid' => $tien,
            'transactions' => [['amount' => $tien, 'reference' => 'QA-'.$don->id, 'transactionDateTime' => now()->toIso8601String()]]];
        $this->giaLap([
            'https://api-merchant.payos.vn/v2/payment-requests' => Http::response(['code' => '00', 'data' => $link, 'signature' => $payos->chuKy($link)]),
            'https://api-merchant.payos.vn/v2/payment-requests/*' => Http::response(['code' => '00', 'data' => $daTra, 'signature' => $payos->chuKy($daTra)]),
        ]);
        $this->doiNguoi($this->kh);
        $this->postJson('/api/v1/khach-hang/don-hang/'.$don->id.'/link-thanh-toan')->assertOk();
        $data = ['orderCode' => $don->ma_don_payos, 'paymentLinkId' => $link['id'], 'reference' => 'QA-'.$don->id,
            'amount' => $tien, 'currency' => 'VND', 'code' => '00'];
        $this->postJson('/api/v1/payos/webhook', ['data' => $data, 'signature' => str_repeat('0', 64)])->assertStatus(400);
        $this->assertNull($don->fresh()->kich_hoat_luc);
        $body = ['data' => $data, 'signature' => $payos->chuKy($data)];
        $this->postJson('/api/v1/payos/webhook', $body)->assertOk();
        $this->postJson('/api/v1/payos/webhook', $body)->assertOk();
        $this->postJson('/api/v1/khach-hang/don-hang/'.$don->id.'/dong-bo')->assertOk();
        $this->assertDatabaseCount('thanh_toan', 1);
    }

    private function phanCong(?TaiKhoan $pt = null, ?int $cu = null): int
    {
        $this->doiNguoi($this->admin);
        $body = ['khach_hang_id' => $this->kh->hoSoKhachHang->id, 'huan_luyen_vien_id' => ($pt ?? $this->pt)->hoSoHuanLuyenVien->id,
            'phan_cong_hien_tai_id' => $cu, 'client_request_id' => (string) Str::uuid(), 'ly_do' => 'Phân công kiểm thử'];
        $id = $this->postJson('/api/v1/admin/phan-cong', $body)->assertOk()->json('data.id');
        $this->postJson('/api/v1/admin/phan-cong', $body)->assertOk()->assertJsonPath('data.id', $id);

        return $id;
    }

    private function giaoAn(bool $tuTao = false): array
    {
        $this->doiNguoi($tuTao ? $this->kh : $this->pt);
        $url = $tuTao ? '/api/v1/khach-hang/ke-hoach' : '/api/v1/pt/hoc-vien/'.$this->kh->hoSoKhachHang->id.'/ke-hoach';
        $k = $this->postJson($url, ['ten_ke_hoach' => 'Giáo án hành trình', 'muc_tieu' => 'Tập đều đặn', 'so_ngay_tap' => 1,
            'giao_an_mau_id' => null, 'client_request_id' => (string) Str::uuid(), 'bai_tap' => [['bai_tap_id' => $this->baiId,
                'ngay_thu' => 1, 'thu_tu' => 1, 'so_hiep' => 3, 'so_lan_lap' => 12, 'nghi_giay' => 60, 'ghi_chu' => null, 'muc_ta_kg' => null]]])->assertCreated()->json('data');
        if (! $tuTao) {
            $k = $this->postJson('/api/v1/pt/ke-hoach/'.$k['id'].'/gui', ['updated_at' => $k['updated_at']])->assertOk()->json('data');
        }

        return $k;
    }

    private function apDung(array $k, bool $tuTao = false): array
    {
        $this->doiNguoi($this->kh);

        return $this->postJson('/api/v1/khach-hang/ke-hoach/'.$k['id'].'/'.($tuTao ? 'ap-dung' : 'xac-nhan'),
            ['updated_at' => $k['updated_at']])->assertOk()->assertJsonPath('data.trang_thai', 'DANG_AP_DUNG')->json('data');
    }

    private function lichTuTap(array $k): array
    {
        $this->doiNguoi($this->kh);

        return $this->postJson('/api/v1/khach-hang/lich-tap', ['ke_hoach_tap_id' => $k['id'], 'ngay_thu' => 1,
            'ngay_tap' => now('Asia/Ho_Chi_Minh')->toDateString(), 'client_request_id' => (string) Str::uuid()])->assertCreated()->json('data');
    }

    private function hoanThanhTuTap(array $l): array
    {
        $this->doiNguoi($this->kh);
        $url = '/api/v1/khach-hang/lich-tap/'.$l['id'];
        $l = $this->postJson($url.'/bat-dau', ['updated_at' => $l['updated_at']])->assertOk()->json('data');
        $body = ['updated_at' => $l['updated_at'], 'ghi_chu' => 'Ghi chú riêng tư', 'bai_tap' => array_map(fn ($b) => ['id' => $b['id'],
            'hiep_tap' => [['so_lan_lap' => 12, 'khoi_luong_kg' => null, 'nghi_giay' => 60]]], $l['bai_tap'])];
        $l = $this->putJson($url, $body)->assertOk()->json('data');
        $version = ['updated_at' => $l['updated_at']];
        $l = $this->postJson($url.'/hoan-thanh', $version)->assertOk()->json('data');
        $this->postJson($url.'/hoan-thanh', $version)->assertOk()->assertJsonPath('data.phien.id', $l['phien']['id']);
        $this->putJson($url, $body)->assertConflict();

        return $l;
    }

    private function datHen(int $sauGio = 6): array
    {
        $this->doiNguoi($this->pt);
        $batDau = now()->addHours($sauGio);
        $slot = $this->postJson('/api/v1/pt/khung-gio', ['bat_dau_luc' => $batDau->toIso8601String(), 'ket_thuc_luc' => $batDau->copy()->addHour()->toIso8601String()])->assertOk()->json('data.id');
        $this->doiNguoi($this->kh);
        $body = ['khung_gio_id' => $slot, 'client_request_id' => (string) Str::uuid()];
        $l = $this->postJson('/api/v1/khach-hang/lich-hen', $body)->assertOk()->json('data');
        $this->postJson('/api/v1/khach-hang/lich-hen', $body)->assertOk()->assertJsonPath('data.id', $l['id']);
        $this->doiNguoi($this->pt);
        $this->postJson('/api/v1/pt/lich-hen/'.$l['id'].'/xac-nhan')->assertOk();

        return $l;
    }

    private function baoCao()
    {
        $this->doiNguoi($this->admin);

        return $this->getJson('/api/v1/admin/bao-cao?tu_ngay=2026-10-04&den_ngay='.now('Asia/Ho_Chi_Minh')->toDateString())->assertOk();
    }

    public function test_mua_phan_cong_giao_an_tu_tap_va_buoi_pt_chi_tru_mot_luot(): void
    {
        $don = $this->taoDon();
        GoiTap::find($don->goi_tap_id)->update(['gia' => 200000, 'so_buoi_pt' => 20]);
        $this->thanhToan($don);
        $this->getJson('/api/v1/khach-hang/goi-cua-toi')->assertOk()->assertJsonPath('data.goi.gia', 99000)->assertJsonPath('data.goi.so_buoi_pt', 4);
        $this->phanCong();
        $k = $this->apDung($this->giaoAn());
        $l = $this->hoanThanhTuTap($this->lichTuTap($k));
        $this->assertSame(4, $don->fresh()->so_buoi_con_lai);
        $this->doiNguoi($this->pt);
        $note = ['client_request_id' => (string) Str::uuid(), 'noi_dung' => 'Giữ kỹ thuật và tăng dần'];
        $this->postJson('/api/v1/pt/lich-tap/'.$l['id'].'/nhan-xet', $note)->assertOk();
        $this->postJson('/api/v1/pt/lich-tap/'.$l['id'].'/nhan-xet', $note)->assertOk();
        $hen = $this->datHen();
        $this->postJson('/api/v1/pt/lich-hen/'.$hen['id'].'/hoan-thanh')->assertConflict();
        $this->travelTo(now()->addHours(7)->addMinute());
        $this->doiNguoi($this->kh);
        $this->postJson('/api/v1/pt/lich-hen/'.$hen['id'].'/hoan-thanh')->assertForbidden();
        $this->doiNguoi($this->pt);
        $this->postJson('/api/v1/pt/lich-hen/'.$hen['id'].'/hoan-thanh')->assertOk();
        $this->postJson('/api/v1/pt/lich-hen/'.$hen['id'].'/hoan-thanh')->assertOk();
        $this->assertSame(3, $don->fresh()->so_buoi_con_lai);
        $this->baoCao()->assertJsonPath('data.trong_ky.thuc_thu', 99000)->assertJsonPath('data.trong_ky.don_kich_hoat', 1)->assertJsonPath('data.trong_ky.buoi_pt_hoan_thanh', 1);
        $this->assertSame(1, DB::table('ghi_chu_huan_luyen')->count());
        $this->doiNguoi($this->kh);
        $this->getJson('/api/v1/khach-hang/tong-quan')->assertOk()->assertJsonPath('data.hanh_trinh.buoi_thang_nay', 1);
        $this->assertCount(1, $this->kh->notifications()->get()->filter(fn ($n) => $n->data['tieu_de'] === 'Buổi PT đã hoàn thành'));
    }

    public function test_tu_tap_mien_phi_bmi_va_mot_giao_an_chung_hai_nguon(): void
    {
        $tuTao = $this->apDung($this->giaoAn(true), true);
        $lich = $this->lichTuTap($tuTao);
        $this->doiNguoi($this->kh);
        $this->postJson('/api/v1/khach-hang/chi-so-co-the', ['ngay_ghi' => '2026-10-04', 'can_nang_kg' => 70, 'chieu_cao_cm' => 175, 'ghi_chu' => null])->assertCreated();
        $this->hoanThanhTuTap($lich);
        $this->assertDatabaseCount('dang_ky_goi_tap', 0);
        $this->assertDatabaseCount('lich_hen_huan_luyen', 0);
        $this->getJson('/api/v1/khach-hang/chi-so-co-the')->assertOk()->assertJsonPath('data.moi_nhat.bmi', 22.86);
        $don = $this->taoDon();
        $this->thanhToan($don);
        $this->phanCong();
        $ptPlan = $this->apDung($this->giaoAn());
        $this->assertSame('LUU_TRU', KeHoachTap::find($tuTao['id'])->trang_thai);
        $this->assertSame(1, KeHoachTap::where('trang_thai', 'DANG_AP_DUNG')->count());
        $stop = $this->postJson('/api/v1/khach-hang/ke-hoach/'.$ptPlan['id'].'/luu-tru', ['updated_at' => $ptPlan['updated_at']])->assertOk()->json('data');
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$ptPlan['id'].'/ap-dung', ['updated_at' => $stop['updated_at']])->assertOk();
        $this->getJson('/api/v1/khach-hang/lich-tap/'.$lich['id'])->assertOk()->assertJsonPath('data.trang_thai', 'HOAN_THANH');
        $this->doiNguoi($this->pt);
        $this->getJson('/api/v1/pt/hoc-vien/'.$this->kh->hoSoKhachHang->id.'/chi-so-co-the')->assertOk()->assertJsonPath('data.moi_nhat.bmi', 22.86);
        $this->getJson('/api/v1/pt/ke-hoach/'.$tuTao['id'])->assertOk();
        $this->assertSame(4, $don->fresh()->so_buoi_con_lai);
    }

    public function test_doi_pt_huy_lich_tuong_lai_thu_hoi_quyen_nhung_giu_lich_su(): void
    {
        $don = $this->taoDon();
        $this->thanhToan($don);
        $pc = $this->phanCong();
        $k = $this->apDung($this->giaoAn(true), true);
        $l = $this->hoanThanhTuTap($this->lichTuTap($k));
        $deXuat = $this->giaoAn();
        $hen = $this->datHen();
        $this->doiNguoi($this->kh);
        $hoi = $this->getJson('/api/v1/hoi-thoai')->assertOk()->json('data.0.id');
        $msg = ['client_message_id' => (string) Str::uuid(), 'noi_dung' => 'Tin riêng tư của PT cũ', 'anh' => []];
        $this->postJson('/api/v1/hoi-thoai/'.$hoi.'/tin-nhan', $msg)->assertOk();
        $this->postJson('/api/v1/hoi-thoai/'.$hoi.'/tin-nhan', $msg)->assertOk();
        $moi = $this->taiKhoan(TaiKhoan::HUAN_LUYEN_VIEN);
        $this->phanCong($moi, $pc);
        // Đổi ngay cùng mốc tạo được chuẩn hóa +1 microsecond để giữ khoảng lịch sử hợp lệ.
        $this->travelTo(now()->addSecond());
        $this->doiNguoi($this->pt);
        foreach (['/pt/ke-hoach/'.$k['id'], '/pt/lich-tap/'.$l['id'], '/pt/hoc-vien/'.$this->kh->hoSoKhachHang->id.'/chi-so-co-the', '/hoi-thoai/'.$hoi.'/tin-nhan'] as $path) {
            $this->assertSame(404, $this->getJson('/api/v1'.$path)->status(), $path);
        }
        $this->postJson('/api/v1/hoi-thoai/'.$hoi.'/tin-nhan', [...$msg, 'client_message_id' => (string) Str::uuid()])->assertNotFound();
        $this->doiNguoi($moi);
        $this->getJson('/api/v1/pt/ke-hoach/'.$k['id'])->assertOk();
        $this->getJson('/api/v1/pt/lich-tap/'.$l['id'])->assertOk();
        $this->getJson('/api/v1/hoi-thoai/'.$hoi.'/tin-nhan')->assertNotFound();
        $this->doiNguoi($this->kh);
        $this->getJson('/api/v1/hoi-thoai/'.$hoi.'/tin-nhan')->assertOk()->assertJsonPath('data.tin_nhan.0.noi_dung', $msg['noi_dung']);
        $this->getJson('/api/v1/khach-hang/lich-hen/'.$hen['id'])->assertOk()->assertJsonPath('data.trang_thai', 'DA_HUY');
        $this->postJson('/api/v1/khach-hang/ke-hoach/'.$deXuat['id'].'/xac-nhan', ['updated_at' => $deXuat['updated_at']])->assertNotFound();
        $this->assertSame('DANG_AP_DUNG', KeHoachTap::find($k['id'])->trang_thai);
        $this->assertSame(4, $don->fresh()->so_buoi_con_lai);
        $this->doiNguoi($this->admin);
        $this->getJson('/api/v1/hoi-thoai/'.$hoi.'/tin-nhan')->assertForbidden();
        $this->getJson('/api/v1/pt/lich-tap/'.$l['id'])->assertForbidden();
    }

    public function test_goi_het_han_van_ghi_tu_tap_va_xac_nhan_buoi_pt_trong_24_gio(): void
    {
        $don = $this->taoDon(1, 1);
        $this->thanhToan($don);
        $this->phanCong();
        $k = $this->apDung($this->giaoAn());
        $l = $this->lichTuTap($k);
        $hen = $this->datHen(20);
        $this->travelTo(now()->addHours(24)->addMinute());
        $this->doiNguoi($this->kh);
        $this->getJson('/api/v1/khach-hang/goi-cua-toi')->assertOk()->assertJsonPath('data.goi', null);
        $this->hoanThanhTuTap($l);
        $this->doiNguoi($this->pt);
        $this->postJson('/api/v1/pt/lich-hen/'.$hen['id'].'/hoan-thanh')->assertOk();
        $this->postJson('/api/v1/pt/lich-hen/'.$hen['id'].'/hoan-thanh')->assertOk();
        $this->assertSame(0, $don->fresh()->so_buoi_con_lai);
        $this->doiNguoi($this->kh);
        $hoi = $this->postJson('/api/v1/khach-hang/chatbot/hoi-thoai', ['client_request_id' => (string) Str::uuid()])->assertCreated()->json('data.id');
        $this->giaLap();
        $this->postJson('/api/v1/khach-hang/chatbot/hoi-thoai/'.$hoi.'/tin-nhan', ['client_request_id' => (string) Str::uuid(), 'noi_dung' => 'Hướng dẫn squat', 'dung_du_lieu_ca_nhan' => false])->assertForbidden();
        Http::assertNothingSent();
        $this->assertDatabaseCount('yeu_cau_tro_ly', 0);
        $this->baoCao()->assertJsonPath('data.trong_ky.buoi_pt_hoan_thanh', 1);
    }

    public function test_tien_ngoai_le_doi_soat_hoan_thu_cong_khong_cap_goi_va_bao_cao_dung(): void
    {
        $don = $this->taoDon();
        $this->baoCao()->assertJsonPath('data.trong_ky.thuc_thu', 0);
        $this->thanhToan($don, 100000);
        $this->assertNull($don->fresh()->kich_hoat_luc);
        $this->baoCao()->assertJsonPath('data.trong_ky.thuc_thu', 100000)->assertJsonPath('data.trong_ky.cho_doi_soat', 100000)->assertJsonPath('data.trong_ky.don_kich_hoat', 0);
        $id = DB::table('thanh_toan')->value('id');
        $body = ['so_tien_hoan' => 100000, 'ma_hoan_tien' => 'QA-REFUND', 'ly_do' => 'Chỉ ghi nhận ngân hàng giả lập'];
        $this->patchJson('/api/v1/admin/thanh-toan/'.$id.'/doi-soat', $body)->assertOk();
        $this->patchJson('/api/v1/admin/thanh-toan/'.$id.'/doi-soat', $body)->assertOk();
        $this->baoCao()->assertJsonPath('data.trong_ky.thuc_thu', 0)->assertJsonPath('data.trong_ky.tien_da_hoan', 100000)->assertJsonPath('data.trong_ky.cho_doi_soat', 0);
        $this->assertNull($don->fresh()->kich_hoat_luc);
        $this->assertDatabaseCount('phan_cong_huan_luyen_vien', 0);
    }

    public function test_goi_ai_rieng_loi_khong_mat_luot_tao_nhap_retry_va_kh_tu_ap_dung(): void
    {
        $don = $this->taoDon(0);
        $this->thanhToan($don);
        $this->assertDatabaseCount('phan_cong_huan_luyen_vien', 0);
        $this->doiNguoi($this->kh);
        $dangDung = $this->apDung($this->giaoAn(true), true);
        $hoi = $this->postJson('/api/v1/khach-hang/chatbot/hoi-thoai', ['client_request_id' => (string) Str::uuid()])->assertCreated()->json('data.id');
        $url = '/api/v1/khach-hang/chatbot/hoi-thoai/'.$hoi.'/tin-nhan';
        $d = ['client_request_id' => (string) Str::uuid(), 'noi_dung' => 'Tạo cho tôi 1 giáo án 3 buổi mỗi tuần trong 4 tuần, mỗi buổi 1 bài', 'dung_du_lieu_ca_nhan' => false];
        $this->giaLap(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'quota'], 429)]);
        $this->postJson($url, $d)->assertStatus(503);
        $this->getJson('/api/v1/khach-hang/tong-quan')->assertOk()->assertJsonPath('data.hanh_trinh.ai.con_lai', 2);
        $noiDung = ['noi_dung' => 'Đã tạo giáo án nháp để bạn xem và tự áp dụng.', 'goi_tap_ids' => [],
            'giao_an_mau_ids' => [], 'bai_tap_ids' => [], 'nguon_tai_lieu_ids' => [], 'giao_an_de_xuat' => ['ten_ke_hoach' => 'Nháp AI kiểm thử',
                'muc_tieu' => 'Tập đều đặn', 'buoi_tap' => array_fill(0, 12, ['ghi_chu' => null, 'bai_tap' => [['id' => $this->baiId, 'hiep' => 3, 'lan' => 12, 'nghi' => 60]]])]];
        $provider = ['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode($noiDung, JSON_UNESCAPED_UNICODE)]]]]]];
        $this->giaLap(['generativelanguage.googleapis.com/*' => Http::response($provider)]);
        $this->postJson($url, $d)->assertOk()->assertJsonPath('meta.han_muc.con_lai', 1);
        $this->postJson($url, $d)->assertOk()->assertJsonPath('meta.han_muc.con_lai', 1);
        Http::assertSentCount(1);
        $this->assertSame(1, DB::table('yeu_cau_tro_ly')->where('trang_thai', 'THANH_CONG')->count());
        $nhap = KeHoachTap::where('ten_ke_hoach', 'Nháp AI kiểm thử')->firstOrFail();
        $this->assertSame('NHAP', $nhap->trang_thai);
        $this->assertSame(12, $nhap->cacBaiTap()->count());
        $this->assertSame('DANG_AP_DUNG', KeHoachTap::find($dangDung['id'])->trang_thai);
        $this->assertDatabaseCount('lich_tap', 0);
        $k = $this->getJson('/api/v1/khach-hang/ke-hoach/'.$nhap->id)->assertOk()->json('data');
        $this->apDung($k, true);
        $this->assertSame('LUU_TRU', KeHoachTap::find($dangDung['id'])->trang_thai);
        $this->assertSame(0, $don->fresh()->so_buoi_con_lai);
    }

    public function test_limiter_tach_chuc_nang_van_chan_gui_qua_muc_tren_nhieu_hoi_thoai(): void
    {
        $this->doiNguoi($this->kh);
        $url = '/api/v1/khach-hang/chatbot/hoi-thoai';
        $hoi = [];
        for ($i = 0; $i < 7; $i++) {
            $hoi[] = $this->postJson($url, ['client_request_id' => (string) Str::uuid()])->assertCreated()->json('data.id');
        }
        // Không có gói: 6 yêu cầu bị từ chối quyền, yêu cầu thứ 7 bị giới hạn kỹ thuật.
        foreach ($hoi as $i => $id) {
            $r = $this->postJson($url.'/'.$id.'/tin-nhan', ['client_request_id' => (string) Str::uuid(), 'noi_dung' => 'Xin chào', 'dung_du_lieu_ca_nhan' => false]);
            $this->assertSame($i === 6 ? 429 : 403, $r->status());
        }
        $this->postJson('/api/v1/thong-bao/da-doc-tat-ca')->assertOk();
        $this->postJson($url, ['client_request_id' => (string) Str::uuid()])->assertCreated();
        Http::assertNothingSent();
    }

    public function test_khach_khac_va_tai_khoan_da_khoa_khong_truy_cap_du_lieu(): void
    {
        $k = $this->apDung($this->giaoAn(true), true);
        $l = $this->hoanThanhTuTap($this->lichTuTap($k));
        $khac = $this->taiKhoan(TaiKhoan::KHACH_HANG);
        $this->doiNguoi($khac);
        $this->getJson('/api/v1/khach-hang/ke-hoach/'.$k['id'])->assertNotFound();
        $this->getJson('/api/v1/khach-hang/lich-tap/'.$l['id'])->assertNotFound();
        $this->getJson('/api/v1/khach-hang/tong-quan')->assertOk()->assertJsonPath('data.hanh_trinh.buoi_thang_nay', 0);
        $this->doiNguoi($this->admin);
        $this->patchJson('/api/v1/admin/tai-khoan/'.$this->kh->id.'/trang-thai', ['trang_thai' => TaiKhoan::BI_KHOA,
            'updated_at' => $this->kh->fresh()->updated_at?->format('Y-m-d H:i:s.u')])->assertOk();
        $this->doiNguoi($this->kh);
        $this->getJson('/api/v1/khach-hang/lich-tap/'.$l['id'])->assertForbidden();
        $this->getJson('/api/v1/thong-bao')->assertForbidden();
        $this->assertDatabaseCount('phien_tap', 1);
    }
}
