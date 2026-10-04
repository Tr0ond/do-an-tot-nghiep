<?php

namespace Database\Seeders;

use App\Models\BaiTap;
use App\Models\TaiKhoan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class HeThong15NgaySeeder extends Seeder
{
    public const DAU_MOC = 'SEED_HE_THONG_15_NGAY_V1';

    private CarbonImmutable $bayGio;

    private CarbonImmutable $ngayDau;

    private string $matKhau;

    private array $cacBai;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Dữ liệu mô phỏng chỉ được tạo trong local/testing.');
        }
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Seeder cần MySQL/MariaDB để giữ các ràng buộc nghiệp vụ.');
        }
        $tenKhoa = 'demo15:'.substr(hash('sha256', DB::connection()->getDatabaseName()), 0, 40);
        if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS khoa', [$tenKhoa])->khoa !== 1) {
            throw new RuntimeException('Seeder 15 ngày đang chạy ở tiến trình khác.');
        }
        try {
            $daTao = DB::transaction(function () {
                if (DB::table('nhat_ky_he_thong')->where('hanh_dong', self::DAU_MOC)->exists()) {
                    return false;
                }
                $this->bayGio = CarbonImmutable::now('UTC');
                $this->ngayDau = $this->bayGio->setTimezone('Asia/Ho_Chi_Minh')->startOfDay()->subDays(14);
                $this->matKhau = Hash::make('Demo123456!');
                $this->kiemTraPhuThuoc();
                $this->taoDuLieu();

                return true;
            });
            $this->command?->info($daTao
                ? 'Đã tạo mô phỏng 15 ngày: 1 Admin, 4 PT, 30 khách hàng. Mọi thanh toán và câu trả lời AI đều là dữ liệu giả lập.'
                : 'Bộ mô phỏng 15 ngày đã tồn tại; giữ nguyên toàn bộ dữ liệu và mật khẩu.');
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS khoa', [$tenKhoa]);
        }
    }

    private function kiemTraPhuThuoc(): void
    {
        if (DB::table('tai_khoan')->where('email', 'like', '%@demo15.example.test')->exists()) {
            throw new RuntimeException('Email demo15 đã tồn tại nhưng thiếu dấu hoàn tất. Không ghi đè tài khoản; hãy kiểm tra dữ liệu.');
        }
        // Dùng mã nguồn thay vì ID cố định; chia ngày toàn thân giống giáo án mẫu đã có.
        $maNgay = [['0413', '0289', '0293', '0274'], ['1460', '0405', '0861', '0872'], ['0413', '0662', '0293', '1373']];
        $bai = BaiTap::with('nhomCo')->where('nguon_du_lieu', 'exercises-dataset')
            ->whereIn('ma_nguon', array_unique(array_merge(...$maNgay)))->get()->keyBy('ma_nguon');
        $this->cacBai = [];
        foreach ($maNgay as $ngay => $cacMa) {
            foreach ($cacMa as $ma) {
                if (! isset($bai[$ma]) || $bai[$ma]->trang_thai !== 'HOAT_DONG' || $bai[$ma]->nhomCo?->trang_thai !== 'HOAT_DONG') {
                    throw new RuntimeException('Thiếu bài/nhóm hoạt động cho mã '.$ma.'. Chuẩn bị BaiTapSeeder trước khi chạy.');
                }
                $this->cacBai[$ngay + 1][] = $bai[$ma];
            }
        }
    }

    private function taoDuLieu(): void
    {
        $moCua = $this->ngayDau->addHours(6)->utc();
        $admin = $this->taiKhoan('Lê Hoàng Nam', 'admin', TaiKhoan::ADMIN, $moCua);
        foreach ([
            ['Đặt lịch cùng PT', 'Buổi PT dài 60 phút. Đặt trước ít nhất 4 giờ; khách hủy trước ít nhất 2 giờ. Vắng mặt không trừ lượt PT.'],
            ['Quyền chatbot theo gói', 'Chatbot cần gói có quyền AI còn hiệu lực. Hạn mức được cấp lại lúc 00:00 giờ Việt Nam; yêu cầu lỗi không mất lượt. Hết buổi PT vẫn dùng chatbot đến hết hạn gói.'],
            ['Nhật ký và giáo án cá nhân', 'Khách có thể tự tạo giáo án, lên lịch từ giáo án đang áp dụng và ghi kết quả của mình. Nhật ký tự tập không tiêu hao buổi PT.'],
        ] as $i => [$tieuDe, $noiDung]) {
            $this->ghi('tai_lieu_tu_van', ['tieu_de' => $tieuDe.' (demo 15 ngày)', 'loai' => 'FAQ', 'noi_dung' => $noiDung,
                'phien_ban' => 1, 'nguoi_cap_nhat_id' => $admin, 'trang_thai' => 'DA_XUAT_BAN', 'xuat_ban_luc' => $moCua,
                'client_request_id' => $this->uuid('faq/'.$i)], $moCua);
        }
        $cacPt = [];
        foreach (['Nguyễn Minh Quân', 'Trần Ngọc Anh', 'Lê Đức Huy', 'Phạm Thu Hà'] as $i => $ten) {
            $tk = $this->taiKhoan($ten, 'pt'.($i + 1), TaiKhoan::HUAN_LUYEN_VIEN, $moCua);
            $pt = $this->ghi('ho_so_huan_luyen_vien', ['tai_khoan_id' => $tk,
                'chuyen_mon' => ['Sức mạnh và thể lực', 'Tập luyện cho người mới', 'Tăng cơ và kỹ thuật tạ', 'Thể lực và vận động cơ bản'][$i],
                'gioi_thieu' => 'PT đồng hành theo mục tiêu cá nhân, theo dõi kỹ thuật và điều chỉnh mức tập. Hồ sơ mô phỏng.'], $moCua);
            $cacPt[] = ['id' => $pt, 'tai_khoan_id' => $tk];
        }
        $goi = [];
        foreach ([['AI cơ bản', 149000, 0, 10, 30], ['PT khởi động', 1290000, 8, 15, 30],
            ['PT đồng hành', 1890000, 12, 20, 30], ['PT trải nghiệm', 699000, 4, 10, 30], ['AI trải nghiệm 7 ngày', 49000, 0, 5, 7]] as $i => [$ten, $gia, $buoi, $luot, $han]) {
            $id = $this->ghi('goi_tap', ['ten_goi' => $ten.' (demo 15 ngày)', 'gia' => $gia, 'co_chatbot' => true,
                'so_luot_chatbot_moi_ngay' => $luot, 'so_buoi_pt' => $buoi, 'thoi_han_ngay' => $han,
                'trang_thai' => 'HOAT_DONG', 'ma_yeu_cau_tao' => $this->uuid('goi/'.$i)], $moCua);
            $goi[] = (array) DB::table('goi_tap')->find($id);
        }
        $tenKhach = ['Nguyễn Văn Minh', 'Trần Thảo Linh', 'Lê Quốc Bảo', 'Phạm Mai Anh', 'Hoàng Gia Huy',
            'Vũ Ngọc Hân', 'Đặng Tuấn Kiệt', 'Bùi Thanh Vy', 'Đỗ Đức Anh', 'Hồ Khánh Chi',
            'Ngô Hoàng Phúc', 'Dương Bảo Ngọc', 'Lý Minh Khang', 'Phan Thu Trang', 'Võ Thành Đạt',
            'Đinh Hải Yến', 'Trịnh Anh Dũng', 'Cao Ngọc Mai', 'Tạ Quang Vinh', 'Hà Minh Châu',
            'Nguyễn Nhật Nam', 'Trần Mỹ Duyên', 'Lê Hải Đăng', 'Phạm Bích Ngọc', 'Hoàng Đức Long',
            'Vũ Quỳnh Như', 'Đặng Minh Trí', 'Bùi Phương Thảo', 'Đỗ Trung Hiếu', 'Hồ Ngọc Ánh'];
        foreach ($tenKhach as $i => $ten) {
            $ngay = $i === 18 ? 0 : ($i < 24 ? intdiv($i, 2) : [0, 12, 12, 5, 13, 14][$i - 24]);
            $dangKy = $i === 29 ? $this->bayGio->subMinutes(10) : $this->ngayDau->addDays($ngay)->addHours(8)->addMinutes($i % 2 * 40)->utc();
            $tk = $this->taiKhoan($ten, 'kh'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT), TaiKhoan::KHACH_HANG, $dangKy);
            $mucTieu = ['Tăng sức mạnh và xây dựng thói quen', 'Cải thiện thể lực', 'Tăng cơ với mức tập phù hợp'][$i % 3];
            $kh = $this->ghi('ho_so_khach_hang', ['tai_khoan_id' => $tk, 'muc_tieu' => $mucTieu,
                'kinh_nghiem' => $i % 3 === 0 ? 'Mới bắt đầu' : 'Đã tập từ 3 đến 6 tháng', 'gioi_tinh' => $i % 2 ? 'NU' : 'NAM',
                'ngay_sinh' => (1993 + $i % 10).'-'.str_pad((string) ($i % 12 + 1), 2, '0', STR_PAD_LEFT).'-15',
                'thoi_gian_co_the_tap' => $this->json(['Buổi tối các ngày trong tuần', 'Sáng cuối tuần'])], $dangKy);
            $chon = $i < 20 ? ($i === 18 ? 3 : ($i % 2 ? 1 : 2)) : ($i === 24 ? 4 : 0);
            $don = $i === 25 ? null : $this->donHang($kh, $goi[$chon], $i, $dangKy, $admin);
            $pc = null;
            $pt = null;
            if ($i < 19) {
                $pt = $cacPt[$i % 4];
                $pc = $this->ghi('phan_cong_huan_luyen_vien', ['khach_hang_id' => $kh, 'huan_luyen_vien_id' => $pt['id'],
                    'nguoi_phan_cong_id' => $admin, 'bat_dau_luc' => $dangKy->addHours(2),
                    'client_request_id' => $this->uuid('phan-cong/'.$i)], $dangKy->addHours(2));
                $this->audit($admin, 'PHAN_CONG_PT', 'phan_cong_huan_luyen_vien', $pc, $dangKy->addHours(2));
                $this->thongBao($tk, 'phan-cong/'.$i, 'Bạn đã có PT phụ trách', $ten.' bắt đầu hành trình cùng PT.', '/khach-hang/tong-quan', $dangKy->addHours(2));
                $this->lichHen($kh, $tk, $pt, $pc, $don, $i, $ngay, $dangKy);
                $this->chat($tk, $pt, $pc, $i, $ngay, $dangKy);
            } elseif ($i === 19) {
                $this->thongBao($admin, 'cho-pt', 'Khách hàng cần phân công PT', 'Khách đã thanh toán gói PT và đang chờ phân công.', '/admin/phan-cong', $dangKy->addMinutes(20));
            }
            if ($i < 26) {
                $this->tapLuyen($kh, $tk, $pt, $pc, $i, $ngay, $dangKy, $mucTieu);
                $this->chiSo($kh, $i, $ngay, $dangKy);
            }
            if ($i < 25) {
                $this->chatbot($kh, $don, $i, $ngay);
                $this->thongBao($tk, 'kich-hoat/'.$i, 'Thanh toán thành công', 'Gói mô phỏng đã được kích hoạt. Xem thời hạn và quyền lợi trong đơn hàng.', '/khach-hang/don-hang/'.$don['id'], $dangKy->addMinutes(20));
            }
        }
        foreach ($cacPt as $pt) {
            foreach ([15, 16, 17] as $d) {
                foreach ([9, 10, 11, 14] as $gio) {
                    $batDau = $this->ngayDau->addDays($d)->addHours($gio)->utc();
                    $this->ghi('khung_gio_huan_luyen_vien', ['huan_luyen_vien_id' => $pt['id'], 'bat_dau_luc' => $batDau,
                        'ket_thuc_luc' => $batDau->addHour(), 'trang_thai' => 'MO'], $this->bayGio->subMinutes(30));
                }
            }
        }
        $this->ghi('nhat_ky_he_thong', ['tai_khoan_id' => $admin, 'hanh_dong' => self::DAU_MOC,
            'loai_tai_nguyen' => 'DU_LIEU_MO_PHONG', 'metadata_an_toan' => $this->json([
                'tu_ngay' => $this->ngayDau->toDateString(), 'den_ngay' => $this->bayGio->setTimezone('Asia/Ho_Chi_Minh')->toDateString(),
                'thanh_toan' => 'GIA_LAP', 'ai' => 'GIA_LAP', 'so_khach_hang' => 30,
            ])], $this->bayGio);
    }

    private function donHang(int $kh, array $goi, int $i, CarbonImmutable $dangKy, int $admin): array
    {
        $taoLuc = $i === 29 ? $this->bayGio->subMinutes(5) : $dangKy->addMinutes(15);
        $kichHoat = $taoLuc->addMinutes(5);
        $ngoaiLe = $i >= 26;
        $trangThai = match ($i) {
            24 => 'HET_HAN', 26 => 'HET_HAN_THANH_TOAN', 27, 28 => 'CAN_DOI_SOAT', 29 => 'CHO_THANH_TOAN', default => 'DANG_SU_DUNG',
        };
        $id = $this->ghi('dang_ky_goi_tap', ['khach_hang_id' => $kh, 'goi_tap_id' => $goi['id'],
            'client_request_id' => $this->uuid('don/'.$i), 'ma_don_payos' => 800000000000 + $kh,
            'ten_goi_snapshot' => $goi['ten_goi'], 'gia_snapshot' => $goi['gia'], 'co_chatbot_snapshot' => true,
            'so_luot_chatbot_moi_ngay_snapshot' => $goi['so_luot_chatbot_moi_ngay'], 'so_buoi_pt_snapshot' => $goi['so_buoi_pt'],
            'thoi_han_ngay_snapshot' => $goi['thoi_han_ngay'], 'so_buoi_con_lai' => $ngoaiLe ? 0 : $goi['so_buoi_pt'],
            'trang_thai' => $trangThai, 'han_thanh_toan' => $taoLuc->addMinutes(15),
            'kich_hoat_luc' => $ngoaiLe ? null : $kichHoat, 'het_han_luc' => $ngoaiLe ? null : $kichHoat->addDays($goi['thoi_han_ngay'])], $taoLuc,
            $i === 24 ? $kichHoat->addDays(7) : ($i === 26 ? $taoLuc->addMinutes(15) : ($ngoaiLe ? $taoLuc : $kichHoat)));
        if (! in_array($i, [26, 29], true)) {
            $muon = in_array($i, [27, 28], true);
            $tienLuc = $taoLuc->addMinutes($muon ? 25 : 4);
            $hoanLuc = $i === 27 ? $tienLuc->addDay() : null;
            $this->ghi('thanh_toan', ['dang_ky_goi_tap_id' => $id, 'ma_giao_dich' => 'DEMO15-THU-'.$kh,
                'so_tien' => $goi['gia'], 'thanh_toan_luc' => $tienLuc, 'xac_minh_luc' => $tienLuc->addMinute(),
                'trang_thai' => $i === 27 ? 'DA_HOAN_TIEN' : ($muon ? 'CAN_DOI_SOAT' : 'DA_XAC_MINH'),
                'ly_do_doi_soat' => $muon ? 'Mô phỏng tiền đến sau hạn chờ 15 phút.' : null,
                'nguoi_doi_soat_id' => $i === 27 ? $admin : null, 'doi_soat_luc' => $hoanLuc,
                'so_tien_hoan' => $i === 27 ? $goi['gia'] : null, 'ma_hoan_tien' => $i === 27 ? 'DEMO15-HOAN-'.$kh : null,
                'ly_do_hoan_tien' => $i === 27 ? 'Mô phỏng hoàn toàn bộ khoản tiền đến muộn.' : null, 'hoan_tien_luc' => $hoanLuc], $tienLuc->addMinute(), $hoanLuc ?? $tienLuc->addMinute());
            if ($muon) {
                $this->thongBao($admin, 'doi-soat/'.$i, 'Khoản thu cần đối soát', 'Khoản thu mô phỏng đến sau hạn chờ thanh toán.', '/admin/don-hang/'.$id, $tienLuc->addMinute());
            }
            $this->audit(null, $muon ? 'CAN_DOI_SOAT' : 'KICH_HOAT_GOI', 'dang_ky_goi_tap', $id, $tienLuc->addMinute());
        }

        return (array) DB::table('dang_ky_goi_tap')->find($id);
    }

    private function lichHen(int $kh, int $tk, array $pt, int $pc, array $don, int $i, int $ngay, CarbonImmutable $dangKy): void
    {
        $hoanThanh = 0;
        $soHen = 0;
        for ($d = $ngay + 1; $d <= 17; $d += 3) {
            if ($hoanThanh >= $don['so_buoi_pt_snapshot']) {
                break;
            }
            $batDau = $this->ngayDau->addDays($d)->addHours(16 + intdiv($i, 4))->utc();
            $ketThuc = $batDau->addHour();
            $taoLuc = $batDau->subDay()->max($dangKy->addHours(3))->min($this->bayGio->subMinutes(30));
            $daQua = $ketThuc->addMinutes(15)->lessThanOrEqualTo($this->bayGio);
            $ptTuChoi = $daQua && $i !== 18 && ($i + $soHen) % 13 === 9;
            $tt = $daQua ? match ($i === 18 ? 0 : ($i + $soHen) % 13) {
                3, 9 => 'DA_HUY', 7 => 'VANG_MAT', 11 => 'HET_HAN', default => 'HOAN_THANH',
            } : ($d === 17 && $i % 3 === 0 ? 'CHO_XAC_NHAN' : 'DA_XAC_NHAN');
            if ($tt === 'CHO_XAC_NHAN') {
                $taoLuc = $this->bayGio->subMinutes(10);
            }
            $slot = $this->ghi('khung_gio_huan_luyen_vien', ['huan_luyen_vien_id' => $pt['id'],
                'bat_dau_luc' => $batDau, 'ket_thuc_luc' => $ketThuc, 'trang_thai' => 'MO'], $dangKy->addHours(2));
            $tieuHao = $tt === 'HOAN_THANH' ? $ketThuc->addMinutes(10) : null;
            $ghiNhan = in_array($tt, ['HOAN_THANH', 'VANG_MAT'], true) ? $ketThuc->addMinutes(10) : null;
            $capNhat = match ($tt) {
                'HOAN_THANH', 'VANG_MAT' => $ghiNhan, 'DA_HUY' => $ptTuChoi ? $taoLuc->addMinutes(20) : $batDau->subHours(3),
                'HET_HAN' => $taoLuc->addHours(2), 'CHO_XAC_NHAN' => $taoLuc, default => $taoLuc->addMinutes(20),
            };
            $id = $this->ghi('lich_hen_huan_luyen', ['khach_hang_id' => $kh, 'huan_luyen_vien_id' => $pt['id'],
                'phan_cong_id' => $pc, 'khung_gio_id' => $slot, 'dang_ky_goi_tap_id' => $don['id'],
                'client_request_id' => $this->uuid('hen/'.$i.'/'.$d), 'bat_dau_luc' => $batDau, 'ket_thuc_luc' => $ketThuc,
                'trang_thai' => $tt, 'han_xac_nhan_dat_lich' => $taoLuc->addHours(2), 'han_xac_nhan_hoan_thanh' => $ketThuc->addDay(),
                'xac_nhan_luc' => ! $ptTuChoi && in_array($tt, ['DA_XAC_NHAN', 'HOAN_THANH', 'VANG_MAT', 'DA_HUY'], true) ? $taoLuc->addMinutes(20) : null,
                'tieu_hao_luc' => $tieuHao, 'nguoi_ghi_nhan_id' => $ghiNhan ? $pt['tai_khoan_id'] : null,
                'ghi_nhan_luc' => $ghiNhan, 'ly_do_ghi_nhan' => $tt === 'VANG_MAT' ? 'Học viên bận việc đột xuất, không trừ buổi.' : null,
                'nguoi_huy_id' => $tt === 'DA_HUY' ? ($ptTuChoi ? $pt['tai_khoan_id'] : $tk) : null, 'huy_luc' => in_array($tt, ['DA_HUY', 'HET_HAN'], true) ? $capNhat : null,
                'ly_do_huy' => match ($tt) {
                    'DA_HUY' => $ptTuChoi ? 'PT bận đột xuất, đề nghị học viên chọn khung giờ khác.' : 'Có việc gia đình, xin đặt lại buổi khác.', 'HET_HAN' => 'PT không xác nhận trong thời hạn.', default => null
                }], $taoLuc, $capNhat);
            $hoanThanh += $tt === 'HOAN_THANH' ? 1 : 0;
            $soHen++;
            if ($ghiNhan) {
                $this->audit($pt['tai_khoan_id'], $tt, 'lich_hen_huan_luyen', $id, $ghiNhan);
            }
            $this->thongBao($tt === 'CHO_XAC_NHAN' ? $pt['tai_khoan_id'] : $tk, 'hen/'.$id,
                $tt === 'HOAN_THANH' ? 'Buổi PT đã hoàn thành' : 'Cập nhật lịch hẹn',
                'Lịch lúc '.$batDau->setTimezone('Asia/Ho_Chi_Minh')->format('H:i d/m').' có trạng thái '.$tt.'.',
                ($tt === 'CHO_XAC_NHAN' ? '/pt' : '/khach-hang').'/lich-hen/'.$id, $capNhat);
        }
        DB::table('dang_ky_goi_tap')->where('id', $don['id'])->update(['so_buoi_con_lai' => $don['so_buoi_pt_snapshot'] - $hoanThanh,
            'updated_at' => DB::table('lich_hen_huan_luyen')->where('dang_ky_goi_tap_id', $don['id'])->max('tieu_hao_luc') ?? $don['updated_at']]);
    }

    private function tapLuyen(int $kh, int $tk, ?array $pt, ?int $pc, int $i, int $ngay, CarbonImmutable $dangKy, string $mucTieu): void
    {
        $gui = $dangKy->addHours(3);
        $duyet = $gui->addHour();
        $keHoach = $this->ghi('ke_hoach_tap', ['khach_hang_id' => $kh, 'huan_luyen_vien_id' => $pt['id'] ?? null, 'phan_cong_id' => $pc,
            'nguon_tao' => $pt ? 'PT' : 'KHACH_HANG', 'ten_ke_hoach' => 'Toàn thân 3 buổi — '.$mucTieu,
            'muc_tieu' => $mucTieu, 'so_ngay_tap' => 3, 'trang_thai' => 'DANG_AP_DUNG',
            'gui_luc' => $pt ? $gui : null, 'han_duyet' => $pt ? $gui->addDay() : null, 'duyet_luc' => $duyet,
            'ma_yeu_cau_tao' => $this->uuid('ke-hoach/'.$i)], $gui, $duyet);
        $dongBai = [];
        foreach ($this->cacBai as $ngayThu => $cacBai) {
            foreach ($cacBai as $thuTu => $bai) {
                $ngonNgu = ! empty($bai->huong_dan['vi']) || ! empty($bai->cac_buoc['vi']) ? 'vi' : 'en';
                $snapshot = ['anh_url' => $bai->anh_url, 'gif_url' => $bai->gif_url, 'dung_cu' => $bai->dung_cu,
                    'ghi_cong_media' => $bai->ghi_cong_media, 'nhom_co' => $bai->nhomCo->ten_nhom_co,
                    'ngon_ngu_huong_dan' => $ngonNgu, 'huong_dan' => $bai->huong_dan[$ngonNgu] ?? null, 'cac_buoc' => $bai->cac_buoc[$ngonNgu] ?? []];
                $dong = ['ke_hoach_tap_id' => $keHoach, 'bai_tap_id' => $bai->id, 'ngay_thu' => $ngayThu, 'thu_tu' => $thuTu + 1,
                    'ten_bai_tap_snapshot' => $bai->ten_tieng_viet ?: $bai->ten_bai_tap, 'noi_dung_snapshot' => $this->json($snapshot),
                    'so_hiep' => 3, 'so_lan_lap' => $i % 3 === 2 ? 10 : 12, 'nghi_giay' => 60, 'muc_ta_kg' => null,
                    'ghi_chu' => 'Khởi động trước buổi tập, chọn mức tập phù hợp; thông số mô phỏng.'];
                $dong['id'] = $this->ghi('bai_tap_trong_ke_hoach', $dong, $gui);
                $dongBai[$ngayThu][] = $dong;
            }
        }
        $lan = 0;
        for ($d = $ngay + 1; $d <= 16; $d += 2) {
            $ngayTap = $this->ngayDau->addDays($d);
            $batDau = $ngayTap->addHours(7)->utc();
            $ketThuc = $batDau->addMinutes(45 + $i % 10);
            $daQua = $ketThuc->lessThanOrEqualTo($this->bayGio);
            $tt = $daQua ? (($i + $lan) % 9 === 4 ? 'DA_HUY' : 'HOAN_THANH') : 'DA_LEN_LICH';
            $ngayThu = $lan % 3 + 1;
            $lich = $this->ghi('lich_tap', ['khach_hang_id' => $kh, 'ke_hoach_tap_id' => $keHoach, 'ngay_tap' => $ngayTap->toDateString(),
                'ngay_thu' => $ngayThu, 'trang_thai' => $tt, 'nguoi_tao_id' => $pt['tai_khoan_id'] ?? $tk,
                'ma_yeu_cau_tao' => $this->uuid('lich-tap/'.$i.'/'.$d)], $duyet, $daQua ? $ketThuc : $duyet);
            if ($tt === 'HOAN_THANH') {
                $phien = $this->ghi('phien_tap', ['khach_hang_id' => $kh, 'lich_tap_id' => $lich,
                    'trang_thai' => 'HOAN_THANH', 'bat_dau_luc' => $batDau, 'hoan_thanh_luc' => $ketThuc], $batDau, $ketThuc);
                foreach ($dongBai[$ngayThu] as $dong) {
                    $snapshot = json_decode($dong['noi_dung_snapshot'], true, 512, JSON_THROW_ON_ERROR);
                    $snapshot['du_kien'] = ['so_hiep' => 3, 'so_lan_lap' => $dong['so_lan_lap'], 'nghi_giay' => 60, 'muc_ta_kg' => null, 'ghi_chu' => $dong['ghi_chu']];
                    $baiPhien = $this->ghi('bai_tap_trong_phien', ['phien_tap_id' => $phien, 'bai_tap_id' => $dong['bai_tap_id'],
                        'bai_tap_trong_ke_hoach_id' => $dong['id'], 'thu_tu' => $dong['thu_tu'],
                        'ten_bai_tap_snapshot' => $dong['ten_bai_tap_snapshot'], 'noi_dung_snapshot' => $this->json($snapshot)], $batDau);
                    for ($hiep = 1; $hiep <= 3; $hiep++) {
                        $this->ghi('hiep_tap', ['bai_tap_trong_phien_id' => $baiPhien, 'thu_tu' => $hiep,
                            'so_lan_lap' => $dong['so_lan_lap'] - ($hiep === 3 && $lan < 2 ? 2 : 0),
                            'khoi_luong_kg' => null, 'nghi_giay' => 60 + ($hiep === 3 ? 15 : 0)], $ketThuc);
                    }
                }
                if ($pt && $ketThuc->addHour()->lessThanOrEqualTo($this->bayGio)) {
                    $this->ghi('ghi_chu_huan_luyen', ['phien_tap_id' => $phien, 'huan_luyen_vien_id' => $pt['id'], 'phan_cong_id' => $pc,
                        'noi_dung' => ['Nhịp tập ổn định, giữ kỹ thuật và nghỉ đủ giữa các hiệp.', 'Đã hoàn thành tốt. Buổi sau thử tăng số lần lặp khi thấy thoải mái.',
                            'Hiệp cuối còn mệt; giữ mức hiện tại và tập trung vào động tác.'][$lan % 3],
                        'ma_yeu_cau_tao' => $this->uuid('nhan-xet/'.$i.'/'.$d)], $ketThuc->addHour());
                }
            }
            $lan++;
        }
    }

    private function chiSo(int $kh, int $i, int $ngay, CarbonImmutable $dangKy): void
    {
        for ($d = $ngay; $d <= 14; $d += 4) {
            $luc = $d === $ngay ? $dangKy->addMinutes(5) : $this->ngayDau->addDays($d)->addHours(7)->utc();
            if ($luc->greaterThan($this->bayGio)) {
                continue;
            }
            $this->ghi('chi_so_co_the', ['khach_hang_id' => $kh, 'ngay_ghi' => $luc->setTimezone('Asia/Ho_Chi_Minh')->toDateString(),
                'can_nang_kg' => 58 + $i % 12 * 2 + ($i % 3 === 2 ? 1 : -1) * round(($d - $ngay) * 0.025, 2),
                'chieu_cao_cm' => 158 + $i % 10 * 2, 'ghi_chu' => 'Đo cùng thời điểm buổi sáng; dữ liệu mô phỏng.'], $luc);
        }
    }

    private function chat(int $tk, array $pt, int $pc, int $i, int $ngay, CarbonImmutable $dangKy): void
    {
        $hoi = $this->ghi('hoi_thoai', ['phan_cong_id' => $pc], $dangKy->addHours(2));
        $cuoi = null;
        $tinKhachCuoi = null;
        for ($d = $ngay; $d <= 14; $d += 3) {
            $luc = $this->ngayDau->addDays($d)->addHours(13)->utc();
            if ($luc->addMinutes(4)->greaterThan($this->bayGio)) {
                continue;
            }
            $capTin = [
                ['Em đã xem giáo án. Buổi tới mình tập trung vào kỹ thuật nhé?', 'Được nhé, bạn khởi động 5–10 phút rồi tập theo giáo án. Có gì chưa rõ hãy nhắn mình.'],
                ['Em đã ghi nhật ký buổi tập rồi, PT xem giúp em nhé.', 'Mình sẽ xem kết quả. Bạn giữ nhịp tập và nghỉ đủ giữa các hiệp nhé.'],
                ['Buổi sau em có thể giữ mức tập như hiện tại không?', 'Được, ưu tiên đúng kỹ thuật. Khi thấy thoải mái mình sẽ điều chỉnh từng bước.'],
            ][intdiv($d - $ngay, 3) % 3];
            foreach ($capTin as $n => $noiDung) {
                $cuoi = $this->ghi('tin_nhan', ['hoi_thoai_id' => $hoi, 'nguoi_gui_id' => $n === 0 ? $tk : $pt['tai_khoan_id'],
                    'client_message_id' => $this->uuid('tin/'.$i.'/'.$d.'/'.$n), 'noi_dung' => $noiDung], $luc->addMinutes($n * 4));
                if ($n === 0) {
                    $tinKhachCuoi = $cuoi;
                }
            }
        }
        if ($cuoi) {
            DB::table('hoi_thoai')->where('id', $hoi)->update(['cursor_khach_da_doc' => $i % 3 === 0 ? $tinKhachCuoi : $cuoi,
                'cursor_pt_da_doc' => $cuoi, 'updated_at' => DB::table('tin_nhan')->where('id', $cuoi)->value('created_at')]);
        }
    }

    private function chatbot(int $kh, array $don, int $i, int $ngay): void
    {
        $hoi = null;
        for ($d = $ngay; $d <= 14; $d += 3) {
            $luc = $this->ngayDau->addDays($d)->addHours(14)->utc();
            if ($luc->addSeconds(3)->greaterThan($this->bayGio) || $luc->greaterThanOrEqualTo(CarbonImmutable::parse($don['het_han_luc'], 'UTC'))) {
                continue;
            }
            $hoi ??= $this->ghi('hoi_thoai_tro_ly', ['khach_hang_id' => $kh, 'tieu_de' => 'Làm quen với tập luyện (mô phỏng)',
                'trang_thai' => 'HOAT_DONG', 'client_request_id' => $this->uuid('hoi-ai/'.$i)], $luc);
            $loi = ($i + $d) % 11 === 0;
            $chuDe = intdiv($d - $ngay, 3) % 3;
            $cauHoi = ['Tôi nên khởi động thế nào trước buổi tập?', 'Làm sao duy trì thói quen tập đều?', 'Tôi nên nghỉ giữa các hiệp bao lâu?'][$chuDe];
            $yeuCau = $this->ghi('yeu_cau_tro_ly', ['khach_hang_id' => $kh, 'hoi_thoai_tro_ly_id' => $hoi, 'dang_ky_goi_tap_id' => $don['id'],
                'client_request_id' => $this->uuid('yeu-cau-ai/'.$i.'/'.$d), 'ngay_han_muc' => $luc->setTimezone('Asia/Ho_Chi_Minh')->toDateString(),
                'provider' => 'demo', 'model' => 'du-lieu-mo-phong', 'trang_thai' => $loi ? 'LOI' : 'THANH_CONG',
                'hoan_thanh_luc' => $luc->addSeconds(3), 'ma_loi' => $loi ? 'DEMO_PROVIDER_BUSY' : null,
                'do_tre_ms' => 0, 'input_tokens' => 0, 'output_tokens' => 0, 'payload_hash' => hash('sha256', $cauHoi), 'dung_du_lieu_ca_nhan' => false], $luc, $luc->addSeconds(3));
            $this->ghi('tin_nhan_tro_ly', ['hoi_thoai_tro_ly_id' => $hoi, 'yeu_cau_tro_ly_id' => $yeuCau, 'vai_tro' => 'USER', 'noi_dung' => $cauHoi], $luc);
            if (! $loi) {
                $this->ghi('tin_nhan_tro_ly', ['hoi_thoai_tro_ly_id' => $hoi, 'yeu_cau_tro_ly_id' => $yeuCau, 'vai_tro' => 'ASSISTANT',
                    'noi_dung' => '[Dữ liệu mô phỏng, không gọi AI thật] '.[
                        'Bạn có thể bắt đầu bằng vận động nhẹ rồi làm quen với động tác của buổi tập ở mức thoải mái. Trao đổi với PT để điều chỉnh phù hợp.',
                        'Chọn khung giờ phù hợp, lên lịch từ giáo án đang dùng và ghi nhật ký sau buổi tập. Đặt mục tiêu nhỏ để dễ duy trì.',
                        'Tham khảo thời gian nghỉ trong giáo án của bạn. Nếu chưa hồi phục, nghỉ thêm và trao đổi với PT trước khi tăng mức tập.',
                    ][$chuDe]], $luc->addSeconds(3));
            }
        }
    }

    private function thongBao(int $tk, string $suKien, string $tieuDe, string $noiDung, string $duongDan, CarbonImmutable $luc): void
    {
        // Chỉ gửi trong nhóm demo, không phát sự kiện hay thông báo cho tài khoản đang có.
        DB::table('notifications')->insert(['id' => $this->uuid('thong-bao/'.$suKien.'/'.$tk), 'type' => 'nghiep_vu',
            'notifiable_type' => TaiKhoan::class, 'notifiable_id' => $tk,
            'data' => $this->json(['tieu_de' => $tieuDe, 'noi_dung' => $noiDung, 'duong_dan' => $duongDan]),
            'read_at' => $luc->lessThan($this->bayGio->subDays(2)) ? $luc->addHour() : null, 'created_at' => $luc, 'updated_at' => $luc]);
    }

    private function taiKhoan(string $ten, string $ma, string $vaiTro, CarbonImmutable $luc): int
    {
        return $this->ghi('tai_khoan', ['ho_ten' => $ten, 'email' => $ma.'@demo15.example.test',
            'password' => $this->matKhau, 'vai_tro' => $vaiTro, 'trang_thai' => 'HOAT_DONG'], $luc);
    }

    private function audit(?int $tk, string $hanhDong, string $loai, int $id, CarbonImmutable $luc): void
    {
        $this->ghi('nhat_ky_he_thong', ['tai_khoan_id' => $tk, 'hanh_dong' => $hanhDong, 'loai_tai_nguyen' => $loai,
            'tai_nguyen_id' => $id, 'metadata_an_toan' => $this->json(['du_lieu_mo_phong' => true])], $luc);
    }

    private function ghi(string $bang, array $duLieu, CarbonImmutable $luc, ?CarbonImmutable $capNhat = null): int
    {
        return DB::table($bang)->insertGetId([...$duLieu, 'created_at' => $luc, 'updated_at' => $capNhat ?? $luc]);
    }

    private function uuid(string $ma): string
    {
        return (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'tr0ond/demo15/v1/'.$ma);
    }

    private function json(array $duLieu): string
    {
        return json_encode($duLieu, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
