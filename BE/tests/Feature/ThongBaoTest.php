<?php

namespace Tests\Feature;

use App\Models\LichHenHuanLuyen;
use App\Models\TaiKhoan;
use App\Services\GiaoAnMauService;
use App\Services\LichHenService;
use App\Services\NhatKyTapService;
use App\Services\PhanCongService;
use App\Services\TaiKhoanService;
use App\Services\ThongBaoService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;
use Tests\TestCase;

class ThongBaoTest extends TestCase
{
    private static ?string $tenDatabase = null;

    private static ?PDO $pdoMayChu = null;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        $cauHinh = config('database.connections.mysql');
        if (self::$tenDatabase === null) {
            $cauHinh['database'] = null;
            $cauHinh['url'] = null;
            config(['database.connections.may_chu_thong_bao' => $cauHinh]);
            self::$pdoMayChu = DB::connection('may_chu_thong_bao')->getPdo();
            self::$tenDatabase = 'kiem_tra_thong_bao_'.bin2hex(random_bytes(8));
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
        if (self::$tenDatabase && preg_match('/^kiem_tra_thong_bao_[a-f0-9]{16}$/D', self::$tenDatabase)) {
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

    private function tin(TaiKhoan $nguoi, array $duLieu = [], bool $daDoc = false): string
    {
        $id = (string) Str::uuid();
        $nguoi->notifications()->create(['id' => $id, 'type' => 'kiem_tra', 'data' => ['tieu_de' => 'Thông báo riêng', 'noi_dung' => 'Nội dung kiểm thử', ...$duLieu], 'read_at' => $daDoc ? now() : null]);

        return $id;
    }

    private function dangNhap(TaiKhoan $nguoi): void
    {
        Auth::forgetGuards();
        $this->actingAs($nguoi, 'web');
    }

    private function bo(): array
    {
        return (require __DIR__.'/../Support/hanh-trinh-fixture.php')();
    }

    private function cacTin(TaiKhoan $nguoi, string $tieuDe): Collection
    {
        return $nguoi->notifications()->get()->filter(fn ($tin) => $tin->data['tieu_de'] === $tieuDe)->values();
    }

    public function test_chong_trung_giu_da_doc_rollback_va_bo_tai_khoan_khoa(): void
    {
        $kh = $this->nguoi(TaiKhoan::KHACH_HANG);
        $s = app(ThongBaoService::class);
        $s->gui($kh->id, 'qa/1', 'Sự kiện', 'Nội dung', '/khach-hang/ho-so');
        $tin = $kh->notifications()->firstOrFail();
        $tin->markAsRead();
        $daDoc = $tin->fresh()->read_at;
        $this->travel(1)->minutes();
        $s->gui($kh->id, 'qa/1', 'Sự kiện khác', 'Nội dung khác', '/khach-hang/ke-hoach');
        $this->assertSame(1, $kh->notifications()->count());
        $this->assertTrue($daDoc->equalTo($tin->fresh()->read_at));
        $this->assertSame('Sự kiện', $tin->fresh()->data['tieu_de']);
        try {
            DB::transaction(function () use ($kh, $s) {
                $s->gui($kh->id, 'qa/rollback', 'Rollback', 'Không được hiện', '/khach-hang/ho-so');
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException $e) {
            $this->assertSame('rollback', $e->getMessage());
        }
        $this->assertSame(1, $kh->notifications()->count());
        $kh->forceFill(['trang_thai' => TaiKhoan::BI_KHOA])->save();
        $s->gui($kh->id, 'qa/khoa', 'Đã khóa', 'Không gửi', '/khach-hang/ho-so');
        $this->assertSame(1, $kh->notifications()->count());
        $this->travelBack();
    }

    public function test_ket_noi_khac_chi_thay_thong_bao_sau_commit(): void
    {
        $kh = $this->nguoi(TaiKhoan::KHACH_HANG);
        config(['database.connections.doc_thong_bao' => config('database.connections.mysql')]);
        $doc = DB::connection('doc_thong_bao');
        app(ThongBaoService::class)->gui($kh->id, 'qa/commit', 'Commit', 'Chỉ sau commit', '/khach-hang/ho-so');
        $this->assertSame(0, $doc->table('notifications')->where('notifiable_id', $kh->id)->count());
        DB::commit();
        $this->assertSame(1, $doc->table('notifications')->where('notifiable_id', $kh->id)->count());
        $doc->disconnect();
        // Xóa chỉ fixture vừa commit để không ảnh hưởng các ca tiếp theo trong DB QA riêng.
        $kh->notifications()->delete();
        $kh->hoSoKhachHang()->delete();
        $kh->delete();
        DB::beginTransaction();
    }

    public function test_doi_pt_gui_dung_ba_nguoi_huy_lich_retry_khong_lap_va_pt_cu_mat_quyen(): void
    {
        $b = $this->bo();
        $moi = $this->nguoi(TaiKhoan::HUAN_LUYEN_VIEN);
        $d = ['khach_hang_id' => $b['kh']->hoSoKhachHang->id, 'huan_luyen_vien_id' => $moi->hoSoHuanLuyenVien->id,
            'phan_cong_hien_tai_id' => $b['pc']->id, 'ly_do' => 'Đổi PT kiểm thử', 'client_request_id' => (string) Str::uuid()];
        $s = app(PhanCongService::class);
        $pc = $s->phanCong($b['admin'], $d);
        $so = DB::table('notifications')->count();
        $this->assertSame($pc->id, $s->phanCong($b['admin'], $d)->id);
        $this->assertSame($so, DB::table('notifications')->count());
        $this->assertCount(1, $this->cacTin($b['kh'], 'PT phụ trách đã thay đổi'));
        $this->assertCount(1, $this->cacTin($moi, 'Bạn có học viên mới'));
        $this->assertCount(1, $this->cacTin($b['pt'], 'Phân công học viên đã kết thúc'));
        $this->assertCount(2, $this->cacTin($b['kh'], 'Lịch hẹn đã hủy do đổi PT'));
        $this->assertSame('/pt/hoc-vien', $this->cacTin($b['pt'], 'Phân công học viên đã kết thúc')[0]->data['duong_dan']);
        $this->assertSame(0, $b['admin']->notifications()->count());
        $this->assertSame(0, $b['khac']->notifications()->count());
        $this->dangNhap($b['pt']);
        $this->getJson('/api/v1/pt/lich-hen/'.$b['hen'])->assertNotFound();
        app(ThongBaoService::class)->choPtHienTai($b['kh']->hoSoKhachHang->id, 'pc-cu', 'Sai PT', 'Không gửi', '/pt/hoc-vien', $b['pc']->id);
        $this->assertCount(0, $this->cacTin($moi, 'Sai PT'));
        $this->assertCount(0, $this->cacTin($b['pt'], 'Sai PT'));
    }

    public function test_loi_ghi_thong_bao_rollback_ca_phan_cong_va_huy_lich(): void
    {
        $b = $this->bo();
        $moi = $this->nguoi(TaiKhoan::HUAN_LUYEN_VIEN);
        $this->mock(ThongBaoService::class)->shouldReceive('lichHen')->once()->andThrow(new \RuntimeException('lỗi ghi thông báo'));
        try {
            app(PhanCongService::class)->phanCong($b['admin'], ['khach_hang_id' => $b['kh']->hoSoKhachHang->id,
                'huan_luyen_vien_id' => $moi->hoSoHuanLuyenVien->id, 'phan_cong_hien_tai_id' => $b['pc']->id,
                'ly_do' => 'QA', 'client_request_id' => (string) Str::uuid()]);
            $this->fail('Phải rollback.');
        } catch (\RuntimeException $e) {
            $this->assertSame('lỗi ghi thông báo', $e->getMessage());
        }
        $this->assertNull($b['pc']->fresh()->ket_thuc_luc);
        $this->assertSame('DA_XAC_NHAN', LichHenHuanLuyen::find($b['hen'])->trang_thai);
        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_nhat_ky_hoan_thanh_va_nhan_xet_retry_dung_nguoi_khong_lo_noi_dung(): void
    {
        $b = $this->bo();
        $l = $b['lich'][0];
        $p = $l->phien;
        $p->cacBaiTap()->where('thu_tu', 2)->firstOrFail()->cacHiep()->create(['thu_tu' => 1, 'so_lan_lap' => 12, 'nghi_giay' => 60]);
        $s = app(NhatKyTapService::class);
        $v = $l->updated_at->format('Y-m-d H:i:s.u');
        $s->thaoTac($b['kh'], $l->id, 'hoan-thanh', $v);
        $s->thaoTac($b['kh'], $l->id, 'hoan-thanh', $v);
        $this->assertCount(1, $this->cacTin($b['pt'], 'Có nhật ký tập mới'));
        $this->assertSame('/pt/lich-tap/'.$l->id, $this->cacTin($b['pt'], 'Có nhật ký tập mới')[0]->data['duong_dan']);
        $d = ['client_request_id' => (string) Str::uuid(), 'noi_dung' => 'Nội dung nhận xét riêng tư'];
        $s->nhanXet($b['pt'], $l->id, $d);
        $s->nhanXet($b['pt'], $l->id, $d);
        $this->assertCount(1, $this->cacTin($b['kh'], 'PT đã nhận xét buổi tập'));
        $this->assertStringNotContainsString('riêng tư', json_encode($this->cacTin($b['kh'], 'PT đã nhận xét buổi tập')[0]->data, JSON_UNESCAPED_UNICODE));
        $this->assertSame(0, $b['admin']->notifications()->count());
        $this->assertSame(0, $b['khac']->notifications()->count());
    }

    public function test_worker_nhac_dung_moc_khong_trung_va_chi_admin_nhan_qua_han(): void
    {
        $b = $this->bo();
        $l = LichHenHuanLuyen::findOrFail($b['hen']);
        $this->travelTo($l->ket_thuc_luc);
        $s = app(ThongBaoService::class);
        $s->nhacBuoiCanGhiNhan();
        $s->nhacBuoiCanGhiNhan();
        $this->assertCount(1, $this->cacTin($b['pt'], 'Buổi tập cần ghi nhận'));
        $this->travelTo($l->ket_thuc_luc->addHours(24));
        $s->nhacBuoiCanGhiNhan();
        app(LichHenService::class)->donQuaHan();
        app(LichHenService::class)->donQuaHan();
        $this->assertCount(1, $this->cacTin($b['admin'], 'Buổi PT quá hạn xác nhận'));
        $this->assertCount(1, $this->cacTin($b['pt'], 'Buổi tập cần ghi nhận'));
        $this->assertCount(0, $this->cacTin($b['kh'], 'Buổi PT quá hạn xác nhận'));
        $this->assertSame(6, $b['don']->fresh()->so_buoi_con_lai);
        $this->travelBack();
    }

    public function test_giao_an_mau_nhap_moi_va_sua_sau_duyet_bao_admin_khong_spam(): void
    {
        $b = $this->bo();
        $a2 = $this->nguoi(TaiKhoan::ADMIN);
        $aKhoa = $this->nguoi(TaiKhoan::ADMIN);
        $aKhoa->forceFill(['trang_thai' => TaiKhoan::BI_KHOA])->save();
        $d = ['ten_giao_an' => 'Mẫu QA', 'muc_tieu' => 'Sức bền', 'so_ngay_tap' => 1, 'client_request_id' => (string) Str::uuid(),
            'bai_tap' => [['bai_tap_id' => $b['keHoach']->cacBaiTap()->first()->bai_tap_id, 'ngay_thu' => 1, 'thu_tu' => 1, 'so_hiep' => 3, 'so_lan_lap' => 12, 'nghi_giay' => 60, 'ghi_chu' => null]]];
        $s = app(GiaoAnMauService::class);
        $g = $s->taoGiaoAn($d, $b['admin']->id);
        $s->taoGiaoAn($d, $b['admin']->id);
        $this->assertCount(1, $this->cacTin($a2, 'Giáo án mẫu cần xem và duyệt'));
        $g = $s->datTrangThai($g->id, ['trang_thai' => 'DA_DUYET', 'updated_at' => $g->updated_at->format('Y-m-d H:i:s.u')], $b['admin']->id);
        $d['ten_giao_an'] = 'Sửa mẫu QA';
        $d['updated_at'] = $g->updated_at->format('Y-m-d H:i:s.u');
        $g = $s->suaGiaoAn($g->id, $d);
        $d['ten_giao_an'] = 'Sửa nháp tiếp';
        $d['updated_at'] = $g->updated_at->format('Y-m-d H:i:s.u');
        $s->suaGiaoAn($g->id, $d);
        $this->assertCount(2, $this->cacTin($a2, 'Giáo án mẫu cần xem và duyệt'));
        $this->assertSame(0, $aKhoa->notifications()->count());
        $this->assertSame(0, $b['kh']->notifications()->count());
        $this->assertSame(0, $b['pt']->notifications()->count());
    }

    public function test_guest_va_tai_khoan_khoa_khong_doc_duoc(): void
    {
        $this->getJson('/api/v1/thong-bao')->assertUnauthorized();
        $this->postJson('/api/v1/thong-bao/da-doc-tat-ca')->assertUnauthorized();
        $nguoi = $this->nguoi(TaiKhoan::KHACH_HANG);
        $nguoi->forceFill(['trang_thai' => TaiKhoan::BI_KHOA])->save();
        $this->dangNhap($nguoi);
        $this->getJson('/api/v1/thong-bao')->assertForbidden();
        $this->postJson('/api/v1/thong-bao/da-doc-tat-ca')->assertForbidden();
    }

    public function test_ca_ba_vai_tro_chi_doc_va_danh_dau_thong_bao_cua_minh(): void
    {
        $nguoiKhac = $this->nguoi(TaiKhoan::KHACH_HANG);
        $idKhac = $this->tin($nguoiKhac);
        foreach ([TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN, TaiKhoan::ADMIN] as $vaiTro) {
            $nguoi = $this->nguoi($vaiTro);
            $id = $this->tin($nguoi);
            $this->dangNhap($nguoi);
            $this->getJson('/api/v1/thong-bao')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $id)->assertJsonPath('meta.so_chua_doc', 1);
            $this->postJson('/api/v1/thong-bao/'.$idKhac.'/da-doc')->assertNotFound();
            $this->postJson('/api/v1/thong-bao/'.$id.'/da-doc')->assertOk();
            $thoiDiem = DB::table('notifications')->where('id', $id)->value('read_at');
            $this->travel(1)->minutes();
            $this->postJson('/api/v1/thong-bao/'.$id.'/da-doc')->assertOk();
            $this->assertSame($thoiDiem, DB::table('notifications')->where('id', $id)->value('read_at'));
            $this->getJson('/api/v1/thong-bao')->assertJsonPath('meta.so_chua_doc', 0);
        }
        $this->assertNull(DB::table('notifications')->where('id', $idKhac)->value('read_at'));
    }

    public function test_tong_chua_doc_khong_bi_gioi_han_trang_va_doc_tat_ca_khong_anh_huong_nguoi_khac(): void
    {
        $nguoi = $this->nguoi(TaiKhoan::HUAN_LUYEN_VIEN);
        $khac = $this->nguoi(TaiKhoan::ADMIN);
        $idKhac = $this->tin($khac);
        for ($i = 0; $i < 25; $i++) {
            $this->tin($nguoi, daDoc: $i < 2);
        }
        $this->dangNhap($nguoi);
        $this->getJson('/api/v1/thong-bao')->assertJsonCount(20, 'data')->assertJsonPath('meta.so_chua_doc', 23)->assertJsonPath('meta.last_page', 2);
        $this->getJson('/api/v1/thong-bao?page=2')->assertJsonCount(5, 'data');
        $this->getJson('/api/v1/thong-bao?page=-1')->assertUnprocessable();
        $this->postJson('/api/v1/thong-bao/da-doc-tat-ca')->assertOk();
        $this->postJson('/api/v1/thong-bao/da-doc-tat-ca')->assertOk();
        $this->getJson('/api/v1/thong-bao')->assertJsonPath('meta.so_chua_doc', 0);
        $this->assertNull(DB::table('notifications')->where('id', $idKhac)->value('read_at'));
        $this->tin($nguoi);
        $this->getJson('/api/v1/thong-bao')->assertJsonPath('meta.so_chua_doc', 1);
    }

    public function test_payload_chi_co_du_lieu_hien_thi_va_chan_lien_ket_ngoai(): void
    {
        $nguoi = $this->nguoi(TaiKhoan::KHACH_HANG);
        $this->dangNhap($nguoi);
        foreach (['javascript:alert(1)', '//example.test', '/\\example.test', "/\nexample.test"] as $url) {
            $id = $this->tin($nguoi, ['duong_dan' => $url, 'bi_mat' => 'Không gửi ra API']);
            $response = $this->getJson('/api/v1/thong-bao')->assertOk();
            $tin = collect($response->json('data'))->firstWhere('id', $id);
            $this->assertNull($tin['duong_dan']);
            $this->assertArrayNotHasKey('bi_mat', $tin);
        }
        $id = $this->tin($nguoi, ['duong_dan' => '/khach-hang/lich-hen']);
        $tin = collect($this->getJson('/api/v1/thong-bao')->json('data'))->firstWhere('id', $id);
        $this->assertSame('/khach-hang/lich-hen', $tin['duong_dan']);
    }
}
