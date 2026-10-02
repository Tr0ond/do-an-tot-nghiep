<?php

namespace Tests\Feature;

use App\Models\BaiTap;
use App\Models\GiaoAnMau;
use App\Models\GoiTap;
use App\Models\NhomCo;
use App\Models\TaiKhoan;
use App\Services\TaiKhoanService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

class TongQuanTest extends TestCase
{
    private static ?string $tenDatabase = null;

    private static ?PDO $pdoMayChu = null;

    protected function setUp(): void
    {
        parent::setUp();
        $cauHinh = config('database.connections.mysql');
        if (self::$tenDatabase === null) {
            $cauHinh['database'] = null;
            $cauHinh['url'] = null;
            config(['database.connections.may_chu_tong_quan' => $cauHinh]);
            self::$pdoMayChu = DB::connection('may_chu_tong_quan')->getPdo();
            $tenMoi = 'kiem_tra_tong_quan_'.bin2hex(random_bytes(8));
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

    private function taoTaiKhoan(string $vaiTro): TaiKhoan
    {
        return app(TaiKhoanService::class)->taoTaiKhoan(['ho_ten' => 'Kiểm thử tổng quan', 'email' => bin2hex(random_bytes(8)).'@example.test', 'password' => 'Demo123456!'], $vaiTro);
    }

    private function dangNhap(TaiKhoan $taiKhoan): void
    {
        Auth::forgetGuards();
        $this->actingAs($taiKhoan, 'web');
    }

    private function taoCatalog(TaiKhoan $admin): void
    {
        $nhom = NhomCo::create(['ma_nhom_co' => 'qa', 'ten_nhom_co' => 'QA', 'ten_nguon' => 'QA', 'trang_thai' => 'HOAT_DONG']);
        $ngung = NhomCo::create(['ma_nhom_co' => 'off', 'ten_nhom_co' => 'Ngừng', 'ten_nguon' => 'off', 'trang_thai' => 'NGUNG_SU_DUNG']);
        NhomCo::create(['ma_nhom_co' => 'empty', 'ten_nhom_co' => 'Rỗng', 'ten_nguon' => 'empty', 'trang_thai' => 'HOAT_DONG']);
        foreach ([[$nhom, 'HOAT_DONG'], [$nhom, 'NGUNG_SU_DUNG'], [$ngung, 'HOAT_DONG']] as [$n, $trangThai]) {
            BaiTap::create(['nhom_co_id' => $n->id, 'ten_bai_tap' => 'Bài thử', 'trang_thai' => $trangThai]);
        }
        foreach ([['HOAT_DONG', 99000], ['NGUNG_SU_DUNG', 99000], ['HOAT_DONG', 0]] as [$trangThai, $gia]) {
            GoiTap::create(['ten_goi' => 'Gói thử', 'gia' => $gia, 'co_chatbot' => true, 'so_luot_chatbot_moi_ngay' => 10, 'so_buoi_pt' => 0, 'thoi_han_ngay' => 30, 'trang_thai' => $trangThai]);
        }
        foreach (['NHAP', 'DA_DUYET', 'NGUNG_SU_DUNG'] as $trangThai) {
            GiaoAnMau::create(['ten_giao_an' => 'Giáo án thử', 'muc_tieu' => 'QA', 'so_ngay_tap' => 1, 'nguoi_tao_id' => $admin->id, 'trang_thai' => $trangThai]);
        }
    }

    public function test_dashboard_yeu_cau_dung_vai_tro_va_tai_khoan_hoat_dong(): void
    {
        $cacDuongDan = ['KHACH_HANG' => '/api/v1/khach-hang/tong-quan', 'HUAN_LUYEN_VIEN' => '/api/v1/pt/tong-quan', 'ADMIN' => '/api/v1/admin/tong-quan'];
        foreach ($cacDuongDan as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
        foreach ($cacDuongDan as $vaiTro => $urlDung) {
            $taiKhoan = $this->taoTaiKhoan($vaiTro);
            foreach ($cacDuongDan as $url) {
                $this->dangNhap($taiKhoan);
                $response = $this->getJson($url);
                $url === $urlDung ? $response->assertOk() : $response->assertForbidden();
            }
            $taiKhoan->trang_thai = TaiKhoan::BI_KHOA;
            $taiKhoan->save();
            $this->dangNhap($taiKhoan);
            $this->getJson($urlDung)->assertForbidden();
        }
    }

    public function test_admin_thong_ke_toan_database_va_cac_trang_thai(): void
    {
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
        $pt = $this->taoTaiKhoan(TaiKhoan::HUAN_LUYEN_VIEN);
        $pt->trang_thai = TaiKhoan::BI_KHOA;
        $pt->save();
        $this->taoCatalog($admin);
        $this->dangNhap($admin);
        $this->getJson('/api/v1/admin/tong-quan')
            ->assertOk()->assertJsonPath('data.quan_tri.tai_khoan', ['tong' => 3, 'hoat_dong' => 2, 'bi_khoa' => 1, 'khach_hang' => 1, 'huan_luyen_vien' => 1, 'admin' => 1])
            ->assertJsonPath('data.quan_tri.bai_tap', ['tong' => 3, 'hien_thi' => 1])
            ->assertJsonPath('data.quan_tri.nhom_co', ['tong' => 3, 'hoat_dong' => 2])
            ->assertJsonPath('data.quan_tri.goi_tap', ['tong' => 3, 'dang_ban' => 1])
            ->assertJsonPath('data.quan_tri.giao_an_mau', ['tong' => 3, 'da_duyet' => 1, 'ban_nhap' => 1, 'ngung_su_dung' => 1])
            ->assertJsonMissingPath('data.email')->assertJsonMissingPath('data.password');
    }

    public function test_khach_chi_doc_ho_so_chinh_minh_va_catalog_cong_khai(): void
    {
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $this->taoCatalog($admin);
        $khach = $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
        $khac = $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
        $khac->hoSoKhachHang()->update(['muc_tieu' => 'Không thuộc tài khoản hiện tại']);
        $this->dangNhap($khach);
        $this->getJson('/api/v1/khach-hang/tong-quan?vai_tro=ADMIN&tai_khoan_id='.$khac->id)
            ->assertOk()->assertJsonPath('data.vai_tro', TaiKhoan::KHACH_HANG)
            ->assertJsonPath('data.thu_vien', ['bai_tap' => 1, 'nhom_co' => 1, 'goi_tap' => 1])
            ->assertJsonPath('data.ho_so.hoan_thanh', 1)->assertJsonPath('data.ho_so.tong_muc', 6)
            ->assertJsonMissingPath('data.quan_tri')->assertJsonMissingPath('data.giao_an_da_duyet');
        $khach->hoSoKhachHang()->update(['muc_tieu' => 'Tăng cơ', 'kinh_nghiem' => 'Mới tập', 'ngay_sinh' => '2000-01-01', 'gioi_tinh' => 'NAM']);
        $khach->hoSoKhachHang->thoi_gian_co_the_tap = ['Thứ hai'];
        $khach->hoSoKhachHang->save();
        $this->dangNhap($khach->fresh());
        $this->getJson('/api/v1/khach-hang/tong-quan')->assertOk()->assertJsonPath('data.ho_so.hoan_thanh', 6);
    }

    public function test_pt_chi_thay_giao_an_da_duyet_va_ho_so_cua_minh(): void
    {
        $admin = $this->taoTaiKhoan(TaiKhoan::ADMIN);
        $this->taoCatalog($admin);
        $pt = $this->taoTaiKhoan(TaiKhoan::HUAN_LUYEN_VIEN);
        $pt->hoSoHuanLuyenVien()->update(['chuyen_mon' => 'Sức bền']);
        $this->dangNhap($pt);
        $this->getJson('/api/v1/pt/tong-quan')
            ->assertOk()->assertJsonPath('data.giao_an_da_duyet', 1)
            ->assertJsonPath('data.ho_so.hoan_thanh', 2)->assertJsonPath('data.ho_so.tong_muc', 3)
            ->assertJsonMissingPath('data.quan_tri');
    }

    public function test_dashboard_rong_khong_sinh_du_lieu_gia(): void
    {
        $khach = $this->taoTaiKhoan(TaiKhoan::KHACH_HANG);
        $this->dangNhap($khach);
        $this->getJson('/api/v1/khach-hang/tong-quan')->assertOk()
            ->assertJsonPath('data.thu_vien', ['bai_tap' => 0, 'nhom_co' => 0, 'goi_tap' => 0])
            ->assertJsonMissingPath('data.doanh_thu')->assertJsonMissingPath('data.so_buoi_hoan_thanh');
        $this->assertSame(0, GoiTap::count());
    }
}
