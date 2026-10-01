<?php

namespace Tests\Feature;

use App\Models\BaiTap;
use App\Models\BaiTapTrongGiaoAnMau;
use App\Models\GiaoAnMau;
use App\Models\TaiKhoan;
use App\Services\GiaoAnMauService;
use App\Services\TaiKhoanService;
use Database\Seeders\BaiTapSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\GiaoAnMauSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PDO;
use RuntimeException;
use Tests\TestCase;

class GiaoAnMauTest extends TestCase
{
    private static ?string $tenDatabase = null;

    private static ?PDO $pdoMayChu = null;

    private int $nhomId;

    private array $cacIdBai;

    protected function setUp(): void
    {
        parent::setUp();
        $cauHinh = config('database.connections.mysql');
        if (self::$tenDatabase === null) {
            $cauHinh['database'] = null;
            $cauHinh['url'] = null;
            config(['database.connections.may_chu_giao_an' => $cauHinh]);
            self::$pdoMayChu = DB::connection('may_chu_giao_an')->getPdo();
            $tenMoi = 'kiem_tra_giao_an_'.bin2hex(random_bytes(8));
            self::$pdoMayChu->exec('CREATE DATABASE `'.$tenMoi.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            self::$tenDatabase = $tenMoi;
        }
        config(['database.connections.mysql.database' => self::$tenDatabase, 'database.connections.mysql.url' => null]);
        DB::purge('mysql');
        DB::setDefaultConnection('mysql');
        $this->assertSame(self::$tenDatabase, DB::selectOne('SELECT DATABASE() AS ten')->ten);
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'mysql', '--force' => true]));
        DB::beginTransaction();
        $this->nhomId = DB::table('nhom_co')->insertGetId(['ma_nhom_co' => 'kiem_thu', 'ten_nhom_co' => 'Ngực', 'ten_nguon' => 'chest', 'trang_thai' => 'HOAT_DONG']);
        $this->cacIdBai = [];
        foreach (['Chống đẩy', 'Đẩy tạ'] as $ten) {
            $this->cacIdBai[] = BaiTap::create(['nhom_co_id' => $this->nhomId, 'ten_bai_tap' => $ten, 'trang_thai' => 'HOAT_DONG'])->id;
        }
    }

    protected function tearDown(): void
    {
        Event::forget('eloquent.creating: '.BaiTapTrongGiaoAnMau::class);
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

    private function dangNhap(string $vaiTro = TaiKhoan::ADMIN): TaiKhoan
    {
        $taiKhoan = app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Kiểm thử giáo án', 'email' => strtolower($vaiTro).'@example.test', 'password' => 'Demo123456!'], $vaiTro);
        $this->doiTaiKhoan($taiKhoan);

        return $taiKhoan;
    }

    private function doiTaiKhoan(TaiKhoan $taiKhoan): void
    {
        Auth::forgetGuards();
        $this->actingAs($taiKhoan, 'web');
    }

    private function duLieu(array $ghiDe = []): array
    {
        return ['ten_giao_an' => 'Toàn thân 2 ngày', 'muc_tieu' => 'Tăng cơ', 'so_ngay_tap' => 2, 'client_request_id' => (string) Str::uuid(), 'bai_tap' => array_map(fn ($id, $index) => ['bai_tap_id' => $id, 'ngay_thu' => $index + 1, 'thu_tu' => 1, 'so_hiep' => 3, 'so_lan_lap' => 12, 'nghi_giay' => 60, 'ghi_chu' => null], $this->cacIdBai, [0, 1]), ...$ghiDe];
    }

    private function taoGiaoAn(array $ghiDe = []): GiaoAnMau
    {
        return app(GiaoAnMauService::class)->taoGiaoAn($this->duLieu($ghiDe), auth()->id());
    }

    private function duLieuSua(GiaoAnMau $giaoAn, array $ghiDe = []): array
    {
        return ['ten_giao_an' => $giaoAn->ten_giao_an, 'muc_tieu' => $giaoAn->muc_tieu, 'so_ngay_tap' => $giaoAn->so_ngay_tap, 'bai_tap' => $giaoAn->cacBaiTap->map(fn ($bai) => array_intersect_key($bai->toArray(), array_flip(BaiTapTrongGiaoAnMau::THUOC_TINH)))->all(), 'updated_at' => $giaoAn->updated_at->format('Y-m-d H:i:s.u'), ...$ghiDe];
    }

    private function doiTrangThai(GiaoAnMau $giaoAn, string $trangThai = 'DA_DUYET')
    {
        return $this->patchJson('/api/v1/admin/giao-an-mau/'.$giaoAn->id.'/trang-thai', ['trang_thai' => $trangThai, 'updated_at' => $giaoAn->fresh()->updated_at->format('Y-m-d H:i:s.u')]);
    }

    public function test_admin_endpoint_kiem_tra_session_vai_tro_va_khoa_tai_khoan(): void
    {
        $cacYeuCau = [['GET', '', []], ['GET', '/999', []], ['POST', '', $this->duLieu()], ['PUT', '/999', [...array_diff_key($this->duLieu(), ['client_request_id' => true]), 'updated_at' => '2026-10-01 00:00:00.000000']], ['PATCH', '/999/trang-thai', ['trang_thai' => 'DA_DUYET', 'updated_at' => '2026-10-01 00:00:00.000000']]];
        foreach ($cacYeuCau as [$phuongThuc, $url, $duLieu]) {
            $this->json($phuongThuc, '/api/v1/admin/giao-an-mau'.$url, $duLieu)->assertUnauthorized();
        }
        $this->getJson('/api/v1/pt/giao-an-mau')->assertUnauthorized();
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN] as $vaiTro) {
            $this->dangNhap($vaiTro);
            foreach ($cacYeuCau as [$phuongThuc, $url, $duLieu]) {
                $this->json($phuongThuc, '/api/v1/admin/giao-an-mau'.$url, $duLieu)->assertForbidden();
            }
        }
        $admin = $this->dangNhap();
        $admin->trang_thai = 'NGUNG_SU_DUNG';
        $admin->save();
        foreach ($cacYeuCau as [$phuongThuc, $url, $duLieu]) {
            $this->json($phuongThuc, '/api/v1/admin/giao-an-mau'.$url, $duLieu)->assertForbidden();
        }
    }

    public function test_tao_nhap_duyet_pt_chi_doc_da_duyet_va_ngung_an_chi_tiet(): void
    {
        $admin = $this->dangNhap();
        $id = $this->postJson('/api/v1/admin/giao-an-mau', $this->duLieu())->assertCreated()->assertJsonPath('data.trang_thai', 'NHAP')->assertJsonPath('data.nguoi_tao_id', $admin->id)->assertJsonCount(2, 'data.bai_tap')->assertJsonMissingPath('data.ma_yeu_cau_tao')->json('data.id');
        $giaoAn = GiaoAnMau::findOrFail($id);
        $this->doiTrangThai($giaoAn)->assertOk()->assertJsonPath('data.nguoi_duyet_id', $admin->id)->assertJsonPath('data.trang_thai', 'DA_DUYET');
        $this->assertNotNull($giaoAn->fresh()->duyet_luc);
        $nhap = $this->taoGiaoAn(['bai_tap' => []]);
        $pt = $this->dangNhap(TaiKhoan::HUAN_LUYEN_VIEN);
        $this->getJson('/api/v1/pt/giao-an-mau')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $id)->assertJsonMissingPath('data.0.updated_at');
        $this->getJson('/api/v1/pt/giao-an-mau/'.$id)->assertOk()->assertJsonPath('data.bai_tap.0.ten_bai_tap', 'Chống đẩy')->assertJsonMissingPath('data.nguoi_tao_id');
        $this->getJson('/api/v1/pt/giao-an-mau/'.$nhap->id)->assertNotFound();
        $this->getJson('/api/v1/pt/giao-an-mau?trang_thai=NHAP')->assertUnprocessable();
        $this->doiTaiKhoan($admin);
        $this->doiTrangThai($giaoAn, 'NGUNG_SU_DUNG')->assertOk();
        $this->doiTaiKhoan($pt);
        $this->getJson('/api/v1/pt/giao-an-mau/'.$id)->assertNotFound();
        $this->getJson('/api/v1/pt/giao-an-mau')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/pt/giao-an-mau/999999')->assertNotFound();
        $this->doiTaiKhoan($admin);
        $this->getJson('/api/v1/pt/giao-an-mau')->assertForbidden();
        $this->dangNhap(TaiKhoan::KHACH_HANG);
        $this->getJson('/api/v1/pt/giao-an-mau/'.$id)->assertForbidden();
    }

    public function test_uuid_retry_khong_trung_khac_noi_dung_409_va_unique_tai_database(): void
    {
        $this->dangNhap();
        $duLieu = $this->duLieu();
        $id = $this->postJson('/api/v1/admin/giao-an-mau', $duLieu)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/admin/giao-an-mau', [...$duLieu, 'bai_tap' => array_reverse($duLieu['bai_tap']), 'client_request_id' => strtoupper($duLieu['client_request_id'])])->assertOk()->assertJsonPath('data.id', $id);
        $this->postJson('/api/v1/admin/giao-an-mau', [...$duLieu, 'ten_giao_an' => 'Khác'])->assertConflict();
        $this->assertDatabaseCount('giao_an_mau', 1);
        $this->assertDatabaseCount('bai_tap_trong_giao_an_mau', 2);
        $giaoAn = GiaoAnMau::findOrFail($id);
        $this->expectException(UniqueConstraintViolationException::class);
        GiaoAnMau::create(array_diff_key($giaoAn->getAttributes(), ['id' => true]));
    }

    public function test_validation_chan_day_order_so_lieu_va_field_thua(): void
    {
        $this->dangNhap();
        foreach (['ten_giao_an' => ['', str_repeat('a', 256)], 'so_ngay_tap' => [0, 31, '1.5'], 'muc_tieu' => [str_repeat('a', 256)], 'client_request_id' => ['sai']] as $truong => $cacGiaTri) {
            foreach ($cacGiaTri as $giaTri) {
                $this->postJson('/api/v1/admin/giao-an-mau', $this->duLieu([$truong => $giaTri]))->assertUnprocessable()->assertJsonValidationErrors($truong);
            }
        }
        foreach (['so_hiep' => [0, 101], 'so_lan_lap' => [0, 1001, 'abc'], 'nghi_giay' => [-1, 3601, '1.5'], 'ngay_thu' => [0, 3], 'thu_tu' => [0, 2], 'ghi_chu' => [str_repeat('x', 2001)]] as $truong => $cacGiaTri) {
            foreach ($cacGiaTri as $giaTri) {
                $duLieu = $this->duLieu();
                $duLieu['bai_tap'][0][$truong] = $giaTri;
                $this->postJson('/api/v1/admin/giao-an-mau', $duLieu)->assertUnprocessable();
            }
        }
        $duLieu = $this->duLieu();
        $duLieu['bai_tap'][1]['ngay_thu'] = 1;
        $this->postJson('/api/v1/admin/giao-an-mau', $duLieu)->assertUnprocessable()->assertJsonValidationErrors('bai_tap');
        $duLieu = $this->duLieu();
        $duLieu['bai_tap'][0]['ten_bai_tap'] = 'Giả';
        $this->postJson('/api/v1/admin/giao-an-mau', $duLieu)->assertUnprocessable()->assertJsonValidationErrors('bai_tap.0');
        foreach (['trang_thai', 'nguoi_tao_id', 'nguoi_duyet_id', 'duyet_luc', 'khach_hang_id'] as $truong) {
            $this->postJson('/api/v1/admin/giao-an-mau', $this->duLieu([$truong => 1]))->assertUnprocessable()->assertJsonValidationErrors($truong);
        }
        $this->assertDatabaseCount('giao_an_mau', 0);
        $this->assertDatabaseCount('bai_tap_trong_giao_an_mau', 0);
    }

    public function test_bai_va_nhom_ngung_khong_duoc_them_moi_nhung_giu_duoc_trong_nhap(): void
    {
        $this->dangNhap();
        $giaoAn = $this->taoGiaoAn();
        BaiTap::find($this->cacIdBai[0])->update(['trang_thai' => 'NGUNG_SU_DUNG']);
        $this->postJson('/api/v1/admin/giao-an-mau', $this->duLieu())->assertUnprocessable()->assertJsonValidationErrors('bai_tap.0.bai_tap_id');
        $this->doiTrangThai($giaoAn)->assertUnprocessable();
        $this->putJson('/api/v1/admin/giao-an-mau/'.$giaoAn->id, $this->duLieuSua($giaoAn, ['muc_tieu' => 'Mục tiêu mới']))->assertOk()->assertJsonPath('data.bai_tap.0.kha_dung', false);
        $giaoAn->refresh();
        $nhanBaiNgung = $this->duLieuSua($giaoAn);
        $nhanBaiNgung['bai_tap'][] = [...$nhanBaiNgung['bai_tap'][0], 'ngay_thu' => 2, 'thu_tu' => 2];
        $this->putJson('/api/v1/admin/giao-an-mau/'.$giaoAn->id, $nhanBaiNgung)->assertUnprocessable()->assertJsonValidationErrors('bai_tap.2.bai_tap_id');
        $this->assertDatabaseCount('bai_tap_trong_giao_an_mau', 2);
        BaiTap::find($this->cacIdBai[0])->update(['trang_thai' => 'HOAT_DONG']);
        DB::table('nhom_co')->where('id', $this->nhomId)->update(['trang_thai' => 'NGUNG_SU_DUNG']);
        $this->postJson('/api/v1/admin/giao-an-mau', $this->duLieu())->assertUnprocessable();
        $this->doiTrangThai($giaoAn)->assertUnprocessable();
        DB::table('nhom_co')->where('id', $this->nhomId)->update(['trang_thai' => 'HOAT_DONG']);
        $this->doiTrangThai($giaoAn)->assertOk();
        BaiTap::find($this->cacIdBai[1])->update(['trang_thai' => 'NGUNG_SU_DUNG']);
        $this->dangNhap(TaiKhoan::HUAN_LUYEN_VIEN);
        $this->getJson('/api/v1/pt/giao-an-mau/'.$giaoAn->id)->assertOk()->assertJsonPath('data.bai_tap.1.kha_dung', false);
    }

    public function test_duyet_can_moi_ngay_co_bai_va_kiem_tra_du_lieu_cu(): void
    {
        $this->dangNhap();
        $giaoAn = $this->taoGiaoAn(['bai_tap' => []]);
        $this->doiTrangThai($giaoAn)->assertUnprocessable()->assertJsonValidationErrors('bai_tap');
        $thieu = $this->taoGiaoAn(['so_ngay_tap' => 3]);
        $this->doiTrangThai($thieu)->assertUnprocessable();
        $cu = $this->taoGiaoAn();
        DB::table('bai_tap_trong_giao_an_mau')->where('giao_an_mau_id', $cu->id)->update(['so_hiep' => 0]);
        $this->doiTrangThai($cu)->assertUnprocessable();
        $this->assertDatabaseHas('giao_an_mau', ['id' => $cu->id, 'trang_thai' => 'NHAP', 'nguoi_duyet_id' => null]);
    }

    public function test_sua_ban_da_duyet_ve_nhap_noop_giu_duyet_va_phien_ban(): void
    {
        $this->dangNhap();
        $giaoAn = $this->taoGiaoAn();
        $this->doiTrangThai($giaoAn)->assertOk();
        $giaoAn->refresh();
        $duLieu = $this->duLieuSua($giaoAn);
        $this->putJson('/api/v1/admin/giao-an-mau/'.$giaoAn->id, $duLieu)->assertOk()->assertJsonPath('data.trang_thai', 'DA_DUYET')->assertJsonPath('data.updated_at', $duLieu['updated_at']);
        $this->putJson('/api/v1/admin/giao-an-mau/'.$giaoAn->id, [...$duLieu, 'muc_tieu' => 'Khác'])->assertOk()->assertJsonPath('data.trang_thai', 'NHAP')->assertJsonPath('data.duyet_luc', null)->assertJsonPath('data.nguoi_duyet_id', null);
        $this->putJson('/api/v1/admin/giao-an-mau/'.$giaoAn->id, $duLieu)->assertConflict();
        $this->patchJson('/api/v1/admin/giao-an-mau/'.$giaoAn->id.'/trang-thai', ['trang_thai' => 'DA_DUYET', 'updated_at' => $duLieu['updated_at']])->assertConflict();
        $this->assertDatabaseHas('giao_an_mau', ['id' => $giaoAn->id, 'muc_tieu' => 'Khác', 'trang_thai' => 'NHAP']);
        $this->doiTrangThai($giaoAn)->assertOk();
        $this->doiTrangThai($giaoAn, 'NGUNG_SU_DUNG')->assertOk();
        $giaoAn->refresh();
        $this->putJson('/api/v1/admin/giao-an-mau/'.$giaoAn->id, $this->duLieuSua($giaoAn, ['ten_giao_an' => 'Đã sửa khi ngừng']))->assertOk()->assertJsonPath('data.trang_thai', 'NGUNG_SU_DUNG')->assertJsonPath('data.duyet_luc', null)->assertJsonPath('data.nguoi_duyet_id', null);
    }

    public function test_doi_thu_tu_nguyen_tu_va_failure_giua_luc_ghi_rollback(): void
    {
        $this->dangNhap();
        $giaoAn = $this->taoGiaoAn(['so_ngay_tap' => 1, 'bai_tap' => array_map(fn ($id, $index) => ['bai_tap_id' => $id, 'ngay_thu' => 1, 'thu_tu' => $index + 1, 'so_hiep' => 3, 'so_lan_lap' => 12, 'nghi_giay' => 0, 'ghi_chu' => '0'], $this->cacIdBai, [0, 1])]);
        $duLieu = $this->duLieuSua($giaoAn);
        $duLieu['bai_tap'][0]['thu_tu'] = 2;
        $duLieu['bai_tap'][1]['thu_tu'] = 1;
        $this->putJson('/api/v1/admin/giao-an-mau/'.$giaoAn->id, $duLieu)->assertOk()->assertJsonPath('data.bai_tap.0.bai_tap_id', $this->cacIdBai[1])->assertJsonPath('data.bai_tap.1.ghi_chu', '0');
        $giaoAn->refresh();
        $truoc = $giaoAn->toArray();
        $cacBaiTruoc = $giaoAn->cacBaiTap->toArray();
        $dem = 0;
        Event::listen('eloquent.creating: '.BaiTapTrongGiaoAnMau::class, function () use (&$dem) {
            if (++$dem === 2) {
                throw new RuntimeException('loi-ghi-dong-thu-hai');
            }
        });
        try {
            app(GiaoAnMauService::class)->suaGiaoAn($giaoAn->id, $this->duLieuSua($giaoAn, ['ten_giao_an' => 'Tên không được commit']));
            $this->fail('Phải phát hiện lỗi ghi dòng thứ hai.');
        } catch (RuntimeException $loi) {
            $this->assertSame('loi-ghi-dong-thu-hai', $loi->getMessage());
        }
        $this->assertSame(array_diff_key($truoc, ['cac_bai_tap' => true]), $giaoAn->fresh()->toArray());
        $this->assertSame($cacBaiTruoc, $giaoAn->fresh()->cacBaiTap->toArray());
    }

    public function test_sua_ngung_giao_an_khong_thay_doi_ke_hoach_va_snapshot(): void
    {
        $admin = $this->dangNhap();
        $giaoAn = $this->taoGiaoAn();
        $pt = $this->dangNhap(TaiKhoan::HUAN_LUYEN_VIEN);
        $khach = $this->dangNhap(TaiKhoan::KHACH_HANG);
        $khId = DB::table('ho_so_khach_hang')->where('tai_khoan_id', $khach->id)->value('id');
        $ptId = DB::table('ho_so_huan_luyen_vien')->where('tai_khoan_id', $pt->id)->value('id');
        $phanCongId = DB::table('phan_cong_huan_luyen_vien')->insertGetId(['khach_hang_id' => $khId, 'huan_luyen_vien_id' => $ptId, 'nguoi_phan_cong_id' => $admin->id, 'bat_dau_luc' => now()]);
        $keHoachId = DB::table('ke_hoach_tap')->insertGetId(['khach_hang_id' => $khId, 'huan_luyen_vien_id' => $ptId, 'phan_cong_id' => $phanCongId, 'giao_an_mau_id' => $giaoAn->id, 'ten_ke_hoach' => 'Kế hoạch cũ', 'trang_thai' => 'DANG_AP_DUNG']);
        DB::table('bai_tap_trong_ke_hoach')->insert(['ke_hoach_tap_id' => $keHoachId, 'bai_tap_id' => $this->cacIdBai[0], 'ngay_thu' => 1, 'thu_tu' => 1, 'ten_bai_tap_snapshot' => 'Tên snapshot cũ', 'noi_dung_snapshot' => json_encode(['ghi_chu' => 'Nội dung cũ']), 'so_hiep' => 2, 'so_lan_lap' => 8, 'nghi_giay' => 90]);
        $truoc = DB::table('ke_hoach_tap')->find($keHoachId);
        $baiCu = DB::table('bai_tap_trong_ke_hoach')->where('ke_hoach_tap_id', $keHoachId)->get()->toArray();
        $this->doiTaiKhoan($admin);
        $this->putJson('/api/v1/admin/giao-an-mau/'.$giaoAn->id, $this->duLieuSua($giaoAn, ['ten_giao_an' => 'Tên mới', 'bai_tap' => []]))->assertOk();
        $this->doiTrangThai($giaoAn, 'NGUNG_SU_DUNG')->assertOk();
        $this->assertEquals($truoc, DB::table('ke_hoach_tap')->find($keHoachId));
        $this->assertEquals($baiCu, DB::table('bai_tap_trong_ke_hoach')->where('ke_hoach_tap_id', $keHoachId)->get()->toArray());
        $this->deleteJson('/api/v1/admin/giao-an-mau/'.$giaoAn->id)->assertStatus(405);
        $this->assertDatabaseCount('giao_an_mau', 1);
    }

    public function test_tim_kiem_literal_va_phan_trang_va_id_sai(): void
    {
        $this->dangNhap();
        $mot = $this->taoGiaoAn(['ten_giao_an' => '100%_=']);
        $hai = $this->taoGiaoAn(['ten_giao_an' => 'Hai']);
        $this->getJson('/api/v1/admin/giao-an-mau?tu_khoa='.urlencode('%_='))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mot->id);
        $this->getJson('/api/v1/admin/giao-an-mau?per_page=1&page=1')->assertOk()->assertJsonPath('data.0.id', $hai->id)->assertJsonPath('meta.total', 2);
        foreach (['page=0', 'page=100001', 'per_page=49', 'trang_thai=SAI', 'tu_khoa='.str_repeat('a', 101)] as $query) {
            $this->getJson('/api/v1/admin/giao-an-mau?'.$query)->assertUnprocessable();
        }
        $this->getJson('/api/v1/admin/giao-an-mau/999999')->assertNotFound();
        $this->putJson('/api/v1/admin/giao-an-mau/999999', $this->duLieuSua($mot))->assertNotFound();
        $this->patchJson('/api/v1/admin/giao-an-mau/999999/trang-thai', ['trang_thai' => 'DA_DUYET', 'updated_at' => $mot->updated_at->format('Y-m-d H:i:s.u')])->assertNotFound();
    }

    public function test_csrf_bat_buoc_cho_tao_sua_va_duyet(): void
    {
        foreach ([PreventRequestForgery::class, ValidateCsrfToken::class] as $lop) {
            $this->app->bind($lop, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
            {
                protected function runningUnitTests(): bool
                {
                    return false;
                }
            });
        }
        $this->dangNhap();
        $giaoAn = $this->taoGiaoAn();
        $header = ['Origin' => 'http://localhost:5173'];
        $this->withSession(['_token' => 'kiem-thu'])->postJson('/api/v1/admin/giao-an-mau', $this->duLieu(), $header)->assertStatus(419);
        $this->withSession(['_token' => 'kiem-thu'])->putJson('/api/v1/admin/giao-an-mau/'.$giaoAn->id, $this->duLieuSua($giaoAn), $header)->assertStatus(419);
        $this->withSession(['_token' => 'kiem-thu'])->patchJson('/api/v1/admin/giao-an-mau/'.$giaoAn->id.'/trang-thai', ['trang_thai' => 'DA_DUYET', 'updated_at' => $giaoAn->updated_at->format('Y-m-d H:i:s.u')], $header)->assertStatus(419);
        $this->withSession(['_token' => 'kiem-thu'])->postJson('/api/v1/admin/giao-an-mau', $this->duLieu(), [...$header, 'X-CSRF-TOKEN' => 'kiem-thu'])->assertCreated();
    }

    public function test_database_seeder_tao_nam_giao_an_da_duyet_pt_doc_duoc_va_tra_id_thuc(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = TaiKhoan::where('email', 'admin@example.test')->firstOrFail();
        $this->assertDatabaseCount('giao_an_mau', 5);
        $this->assertDatabaseCount('bai_tap_trong_giao_an_mau', 60);
        $this->assertDatabaseCount('ke_hoach_tap', 0);
        $this->assertDatabaseCount('bai_tap_trong_ke_hoach', 0);
        foreach (GiaoAnMau::with('cacBaiTap.baiTap')->get() as $giaoAn) {
            $this->assertSame('DA_DUYET', $giaoAn->trang_thai);
            $this->assertEquals($admin->id, $giaoAn->nguoi_tao_id);
            $this->assertEquals($admin->id, $giaoAn->nguoi_duyet_id);
            $this->assertNotNull($giaoAn->duyet_luc);
            foreach ($giaoAn->cacBaiTap->groupBy('ngay_thu') as $cacBai) {
                $this->assertSame([1, 2, 3, 4], $cacBai->pluck('thu_tu')->all());
                foreach ($cacBai as $dong) {
                    $this->assertSame('exercises-dataset', $dong->baiTap->nguon_du_lieu);
                    $this->assertSame('HOAT_DONG', $dong->baiTap->trang_thai);
                }
            }
            $this->assertCount($giaoAn->so_ngay_tap, $giaoAn->cacBaiTap->groupBy('ngay_thu'));
        }

        // Hai bài kiểm thử có sẵn làm lệch ID nhập; FK phải tra mã nguồn thay vì ID JSON.
        $nguon = collect(json_decode(file_get_contents(database_path('data/bai_tap.json')), true, 512, JSON_THROW_ON_ERROR))->firstWhere('ma_nguon', '0413');
        $bai = BaiTap::where('nguon_du_lieu', 'exercises-dataset')->where('ma_nguon', '0413')->firstOrFail();
        $this->assertNotEquals($nguon['id'], $bai->id);
        $giaoAn = GiaoAnMau::where('ten_giao_an', 'Toàn thân cơ bản (demo)')->firstOrFail();
        $this->assertEquals($bai->id, $giaoAn->cacBaiTap->first()->bai_tap_id);
        $this->doiTaiKhoan(TaiKhoan::where('email', 'pt@example.test')->firstOrFail());
        $this->getJson('/api/v1/pt/giao-an-mau')->assertOk()->assertJsonCount(5, 'data');
        $this->getJson('/api/v1/pt/giao-an-mau/'.$giaoAn->id)->assertOk()->assertJsonCount(12, 'data.bai_tap')->assertJsonPath('data.bai_tap.0.bai_tap_id', $bai->id);
    }

    public function test_seed_lai_giu_nguyen_giao_an_da_sua_ngung_va_du_lieu_ngoai_seeder(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->doiTaiKhoan(TaiKhoan::where('email', 'admin@example.test')->firstOrFail());
        $this->taoGiaoAn(['ten_giao_an' => 'Giáo án tự soạn']);
        $giaoAn = GiaoAnMau::where('ten_giao_an', 'Toàn thân cơ bản (demo)')->firstOrFail();
        $duLieu = $this->duLieuSua($giaoAn, ['ten_giao_an' => 'Tên tự sửa']);
        $duLieu['bai_tap'][0]['ghi_chu'] = 'Ghi chú riêng';
        $duLieu['bai_tap'][0]['so_hiep'] = 5;
        $this->putJson('/api/v1/admin/giao-an-mau/'.$giaoAn->id, $duLieu)->assertOk();
        $this->doiTrangThai($giaoAn, 'NGUNG_SU_DUNG')->assertOk();
        BaiTap::where('nguon_du_lieu', 'exercises-dataset')->where('ma_nguon', '0413')->update(['trang_thai' => 'NGUNG_SU_DUNG']);
        $truoc = DB::table('giao_an_mau')->orderBy('id')->get()->toArray();
        $cacBaiTruoc = DB::table('bai_tap_trong_giao_an_mau')->orderBy('id')->get()->toArray();
        $this->seed(GiaoAnMauSeeder::class);
        $this->seed(GiaoAnMauSeeder::class);
        $this->assertEquals($truoc, DB::table('giao_an_mau')->orderBy('id')->get()->toArray());
        $this->assertEquals($cacBaiTruoc, DB::table('bai_tap_trong_giao_an_mau')->orderBy('id')->get()->toArray());
        $this->assertDatabaseCount('giao_an_mau', 6);
        $this->assertDatabaseCount('bai_tap_trong_giao_an_mau', 62);
    }

    public function test_thieu_bai_o_giao_an_cuoi_rollback_toan_bo_giao_an_moi(): void
    {
        $this->dangNhap();
        $cu = $this->taoGiaoAn();
        $truoc = DB::table('giao_an_mau')->find($cu->id);
        $this->seed(BaiTapSeeder::class);
        BaiTap::where('nguon_du_lieu', 'exercises-dataset')->where('ma_nguon', '1160')->delete();
        try {
            $this->seed(GiaoAnMauSeeder::class);
            $this->fail('Seeder phải từ chối bài nguồn bị thiếu.');
        } catch (RuntimeException $loi) {
            $this->assertStringContainsString('1160', $loi->getMessage());
        }
        $this->assertDatabaseCount('giao_an_mau', 1);
        $this->assertDatabaseCount('bai_tap_trong_giao_an_mau', 2);
        $this->assertEquals($truoc, DB::table('giao_an_mau')->find($cu->id));
    }

    public function test_seeder_tu_choi_bai_hoac_nhom_ngung_va_rollback(): void
    {
        $this->dangNhap();
        $this->seed(BaiTapSeeder::class);
        $bai = BaiTap::where('nguon_du_lieu', 'exercises-dataset')->where('ma_nguon', '0289')->firstOrFail();
        foreach (['bai', 'nhom'] as $truongHop) {
            if ($truongHop === 'bai') {
                $bai->update(['trang_thai' => 'NGUNG_SU_DUNG']);
            } else {
                $bai->update(['trang_thai' => 'HOAT_DONG']);
                DB::table('nhom_co')->where('id', $bai->nhom_co_id)->update(['trang_thai' => 'NGUNG_SU_DUNG']);
            }
            try {
                $this->seed(GiaoAnMauSeeder::class);
                $this->fail('Seeder phải từ chối bài hoặc nhóm ngừng.');
            } catch (ValidationException $loi) {
                $this->assertNotEmpty($loi->errors());
            }
            $this->assertDatabaseCount('giao_an_mau', 0);
            $this->assertDatabaseCount('bai_tap_trong_giao_an_mau', 0);
        }
    }

    public function test_seeder_khong_tao_nang_quyen_mo_khoa_hoac_doi_mat_khau_admin(): void
    {
        foreach (['thieu', 'sai_vai_tro', 'bi_khoa'] as $truongHop) {
            if ($truongHop === 'sai_vai_tro') {
                $taiKhoan = app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Tài khoản đã có', 'email' => 'admin@example.test', 'password' => 'MatKhauRieng123!'], TaiKhoan::KHACH_HANG);
            } elseif ($truongHop === 'bi_khoa') {
                $taiKhoan->forceFill(['vai_tro' => TaiKhoan::ADMIN, 'trang_thai' => 'NGUNG_SU_DUNG'])->save();
            }
            $truoc = DB::table('tai_khoan')->orderBy('id')->get()->toArray();
            try {
                $this->seed(GiaoAnMauSeeder::class);
                $this->fail('Seeder phải yêu cầu Admin demo đang hoạt động.');
            } catch (RuntimeException $loi) {
                $this->assertStringContainsString('Admin demo', $loi->getMessage());
            }
            $this->assertEquals($truoc, DB::table('tai_khoan')->orderBy('id')->get()->toArray());
            $this->assertDatabaseCount('giao_an_mau', 0);
            $this->assertDatabaseCount('bai_tap_trong_giao_an_mau', 0);
        }
    }

    public function test_seeder_giao_an_tu_choi_production_truoc_khi_ghi(): void
    {
        $this->app['env'] = 'production';
        try {
            $this->app->call([new GiaoAnMauSeeder, 'run']);
            $this->fail('Seeder demo không được chạy trên production.');
        } catch (RuntimeException $loi) {
            $this->assertStringContainsString('local hoặc testing', $loi->getMessage());
        }
        $this->assertDatabaseCount('tai_khoan', 0);
        $this->assertDatabaseCount('giao_an_mau', 0);
        $this->assertDatabaseCount('bai_tap_trong_giao_an_mau', 0);
        $this->assertDatabaseCount('bai_tap', 2);
    }
}
