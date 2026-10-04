<?php

namespace App\Services;

use App\Models\DangKyGoiTap;
use App\Models\HoSoHuanLuyenVien;
use App\Models\HoSoKhachHang;
use App\Models\KhungGioHuanLuyenVien;
use App\Models\LichHenHuanLuyen;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class LichHenService
{
    public function taoKhungGio(TaiKhoan $nguoi, array $duLieu): KhungGioHuanLuyenVien
    {
        abort_unless($nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN, 403);
        $batDau = CarbonImmutable::parse($duLieu['bat_dau_luc'])->utc();

        return DB::transaction(function () use ($nguoi, $batDau) {
            $pt = $this->khoaPT($nguoi->hoSoHuanLuyenVien()->firstOrFail()->id);
            abort_unless($batDau->greaterThan(now()), 409, 'Giờ bắt đầu phải ở tương lai.');
            $cu = KhungGioHuanLuyenVien::where('huan_luyen_vien_id', $pt->id)->where('bat_dau_luc', $batDau)->first();
            if ($cu) {
                abort_unless($cu->trang_thai === 'MO', 409, 'Khung giờ này đã đóng. Hãy mở lại từ danh sách.');

                return $cu;
            }
            abort_if($this->chongKhung($pt->id, $batDau, $batDau->addHour())->exists(), 409, 'Khung giờ chồng với giờ đã mở.');

            return KhungGioHuanLuyenVien::create(['huan_luyen_vien_id' => $pt->id, 'bat_dau_luc' => $batDau, 'ket_thuc_luc' => $batDau->addHour(), 'trang_thai' => 'MO']);
        }, 3);
    }

    public function doiKhungGio(TaiKhoan $nguoi, int $id, string $trangThai): KhungGioHuanLuyenVien
    {
        abort_unless($nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN, 403);

        return DB::transaction(function () use ($nguoi, $id, $trangThai) {
            $pt = $this->khoaPT($nguoi->hoSoHuanLuyenVien()->firstOrFail()->id);
            $slot = KhungGioHuanLuyenVien::where('huan_luyen_vien_id', $pt->id)->lockForUpdate()->findOrFail($id);
            if ($slot->trang_thai === $trangThai) {
                return $slot;
            }
            abort_unless($slot->bat_dau_luc->greaterThan(now()), 409, 'Không thay đổi khung giờ đã bắt đầu.');
            $this->dongYeuCauHetHan($pt->id);
            abort_if(LichHenHuanLuyen::where('khung_gio_id', $id)->whereIn('trang_thai', ['CHO_XAC_NHAN', 'DA_XAC_NHAN'])->exists(), 409, 'Khung giờ đang có lịch hẹn.');
            if ($trangThai === 'MO') {
                abort_if($this->chongKhung($pt->id, $slot->bat_dau_luc, $slot->ket_thuc_luc)->where('id', '!=', $id)->exists(), 409, 'Khung giờ chồng với giờ đã mở.');
            }
            $slot->update(['trang_thai' => $trangThai]);

            return $slot;
        }, 3);
    }

    private function chongKhung(int $pt, $batDau, $ketThuc)
    {
        return KhungGioHuanLuyenVien::where('huan_luyen_vien_id', $pt)->where('trang_thai', 'MO')->where('bat_dau_luc', '<', $ketThuc)->where('ket_thuc_luc', '>', $batDau);
    }

    private function khoaPT(int $id): HoSoHuanLuyenVien
    {
        $pt = HoSoHuanLuyenVien::lockForUpdate()->findOrFail($id);
        abort_unless($pt->taiKhoan()->where('trang_thai', TaiKhoan::HOAT_DONG)->exists(), 409, 'PT đang bị khóa.');

        return $pt;
    }

    // Chỉ đổi trạng thái, không lấy thêm khóa hồ sơ KH khi đang giữ khóa PT.
    // Mọi ghi lịch hẹn khác đều khóa KH trước PT; tránh đảo thứ tự gây deadlock.
    public function dongYeuCauHetHan(?int $pt = null, ?int $khach = null): void
    {
        $cacLich = LichHenHuanLuyen::when($pt, fn ($q) => $q->where('huan_luyen_vien_id', $pt))->when($khach, fn ($q) => $q->where('khach_hang_id', $khach))
            ->where('trang_thai', 'CHO_XAC_NHAN')->where('han_xac_nhan_dat_lich', '<=', now()->format('Y-m-d H:i:s.u'))
            ->orderBy('id')->get();
        foreach ($cacLich as $lich) {
            $doi = LichHenHuanLuyen::whereKey($lich->id)->where('trang_thai', 'CHO_XAC_NHAN')
                ->where('han_xac_nhan_dat_lich', '<=', now()->format('Y-m-d H:i:s.u'))
                ->update(['trang_thai' => 'HET_HAN', 'huy_luc' => now(), 'ly_do_huy' => 'PT không xác nhận trong thời hạn.', 'updated_at' => now()]);
            if ($doi) {
                app(ThongBaoService::class)->lichHen($lich, 'het-han');
            }
        }
    }

    public function datLich(TaiKhoan $nguoi, array $duLieu): LichHenHuanLuyen
    {
        abort_unless($nguoi->vai_tro === TaiKhoan::KHACH_HANG, 403);

        return DB::transaction(function () use ($nguoi, $duLieu) {
            $khach = HoSoKhachHang::where('tai_khoan_id', $nguoi->id)->lockForUpdate()->firstOrFail();
            abort_unless($khach->taiKhoan()->where('trang_thai', TaiKhoan::HOAT_DONG)->exists(), 403);
            $ma = strtolower($duLieu['client_request_id']);
            $cu = LichHenHuanLuyen::where('khach_hang_id', $khach->id)->where('client_request_id', $ma)->first();
            if ($cu) {
                abort_unless($cu->khung_gio_id === (int) $duLieu['khung_gio_id'], 409, 'Mã yêu cầu đã dùng cho khung giờ khác.');

                return $cu;
            }
            $phanCong = PhanCongHuanLuyenVien::where('khach_hang_id', $khach->id)->whereNull('ket_thuc_luc')->first();
            abort_unless($phanCong, 409, 'Bạn chưa được phân công PT.');
            $pt = $this->khoaPT($phanCong->huan_luyen_vien_id);
            $this->dongYeuCauHetHan($pt->id);
            $this->dongYeuCauHetHan(null, $khach->id);
            $slot = KhungGioHuanLuyenVien::where('huan_luyen_vien_id', $pt->id)->lockForUpdate()->findOrFail($duLieu['khung_gio_id']);
            abort_unless($slot->trang_thai === 'MO' && $slot->bat_dau_luc->greaterThanOrEqualTo(now()->addHours(4)), 409, 'Phải đặt khung giờ đang mở trước ít nhất 4 giờ.');
            $goi = app(MuaGoiService::class)->goiHieuLuc($khach->id)->lockForUpdate()->first();
            abort_unless($goi && $goi->so_buoi_con_lai > 0 && $slot->ket_thuc_luc->lessThanOrEqualTo($goi->het_han_luc), 409, 'Cần gói PT còn buổi và còn hạn đến hết buổi tập.');
            $giu = LichHenHuanLuyen::whereIn('trang_thai', ['CHO_XAC_NHAN', 'DA_XAC_NHAN']);
            // Buổi chờ/đã xác nhận giữ quyền đặt, nhưng counter chỉ giảm lúc hoàn thành.
            $dangGiu = (clone $giu)->where('dang_ky_goi_tap_id', $goi->id)->where(function ($q) {
                $q->where('trang_thai', 'CHO_XAC_NHAN')->orWhere('ket_thuc_luc', '>', now()->subHours(24)->format('Y-m-d H:i:s.u'));
            })->count();
            abort_if($dangGiu >= $goi->so_buoi_con_lai, 409, 'Các buổi còn lại đã được giữ cho lịch hẹn. Hãy hủy hoặc xử lý lịch cũ.');
            abort_if((clone $giu)->where(fn ($q) => $q->where('khach_hang_id', $khach->id)->orWhere('huan_luyen_vien_id', $pt->id))->where('bat_dau_luc', '<', $slot->ket_thuc_luc)->where('ket_thuc_luc', '>', $slot->bat_dau_luc)->exists(), 409, 'KH hoặc PT đã có lịch hẹn chồng giờ.');
            $han = CarbonImmutable::now()->addHours(2)->min($slot->bat_dau_luc->subHours(2));

            $lich = LichHenHuanLuyen::create(['khach_hang_id' => $khach->id, 'huan_luyen_vien_id' => $pt->id, 'phan_cong_id' => $phanCong->id, 'khung_gio_id' => $slot->id, 'dang_ky_goi_tap_id' => $goi->id, 'client_request_id' => $ma, 'bat_dau_luc' => $slot->bat_dau_luc, 'ket_thuc_luc' => $slot->ket_thuc_luc, 'trang_thai' => 'CHO_XAC_NHAN', 'han_xac_nhan_dat_lich' => $han, 'han_xac_nhan_hoan_thanh' => $slot->ket_thuc_luc->addHours(24)]);
            app(ThongBaoService::class)->lichHen($lich, 'dat');

            return $lich;
        }, 3);
    }

    public function phamVi(TaiKhoan $nguoi)
    {
        $q = LichHenHuanLuyen::query();
        if ($nguoi->vai_tro === TaiKhoan::KHACH_HANG) {
            $q->whereIn('khach_hang_id', $nguoi->hoSoKhachHang()->select('id'));
        } elseif ($nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN) {
            $q->whereIn('huan_luyen_vien_id', $nguoi->hoSoHuanLuyenVien()->select('id'))
                ->whereIn('phan_cong_id', PhanCongHuanLuyenVien::whereNull('ket_thuc_luc')->select('id'));
        } else {
            abort_unless($nguoi->vai_tro === TaiKhoan::ADMIN, 403);
        }

        return $q;
    }

    public function thaoTac(TaiKhoan $nguoi, int $id, string $hanhDong, string $lyDo = ''): LichHenHuanLuyen
    {
        if (in_array($hanhDong, ['huy', 'tu-choi', 'vang-mat', 'dong-xu-ly'])) {
            abort_unless(trim($lyDo) !== '' && mb_strlen($lyDo) <= 1000, 422, 'Cần ghi lý do hợp lệ.');
        }
        $goc = $this->phamVi($nguoi)->findOrFail($id);

        return DB::transaction(function () use ($nguoi, $goc, $hanhDong, $lyDo) {
            // Cùng thứ tự với đặt lịch/phân công. Không tin quyền từ lần đọc ngoài transaction.
            HoSoKhachHang::lockForUpdate()->findOrFail($goc->khach_hang_id);
            HoSoHuanLuyenVien::lockForUpdate()->findOrFail($goc->huan_luyen_vien_id);
            $lich = $this->phamVi($nguoi)->lockForUpdate()->findOrFail($goc->id);
            $trangThai = $lich->trangThaiHieuLuc();
            if ($hanhDong === 'huy' || $hanhDong === 'tu-choi') {
                $laHuy = $hanhDong === 'huy';
                abort_unless($nguoi->vai_tro === ($laHuy ? TaiKhoan::KHACH_HANG : TaiKhoan::HUAN_LUYEN_VIEN), 403);
                if ($lich->trang_thai === 'DA_HUY' && $lich->nguoi_huy_id === $nguoi->id && $lich->ly_do_huy === $lyDo) {
                    return $lich;
                }
                abort_unless(in_array($trangThai, $laHuy ? ['CHO_XAC_NHAN', 'DA_XAC_NHAN'] : ['CHO_XAC_NHAN']), 409, 'Lịch hẹn không còn cho phép hủy/từ chối.');
                if ($laHuy) {
                    abort_unless($lich->bat_dau_luc->greaterThanOrEqualTo(now()->addHours(2)), 409, 'Chỉ được hủy trước ít nhất 2 giờ.');
                }
                $lich->update(['trang_thai' => 'DA_HUY', 'nguoi_huy_id' => $nguoi->id, 'huy_luc' => now(), 'ly_do_huy' => $lyDo]);
                app(ThongBaoService::class)->lichHen($lich, $hanhDong);
            } elseif ($hanhDong === 'xac-nhan') {
                abort_unless($nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN, 403);
                if ($trangThai === 'DA_XAC_NHAN') {
                    return $lich;
                }
                abort_unless($trangThai === 'CHO_XAC_NHAN', 409, 'Yêu cầu đã hết hạn hoặc được xử lý.');
                $lich->update(['trang_thai' => 'DA_XAC_NHAN', 'xac_nhan_luc' => now()]);
                app(ThongBaoService::class)->lichHen($lich, 'xac-nhan');
            } elseif (in_array($hanhDong, ['hoan-thanh', 'vang-mat'])) {
                abort_unless($nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN, 403);
                $dich = $hanhDong === 'hoan-thanh' ? 'HOAN_THANH' : 'VANG_MAT';
                if ($lich->trang_thai === $dich) {
                    abort_unless($lich->nguoi_ghi_nhan_id === $nguoi->id && ($dich !== 'VANG_MAT' || $lich->ly_do_ghi_nhan === $lyDo), 409, 'Buổi này đã được ghi nhận với thông tin khác.');

                    return $lich;
                }
                abort_unless($trangThai === 'DA_XAC_NHAN' && $lich->ket_thuc_luc->lessThanOrEqualTo(now()), 409, 'Chỉ xử lý sau giờ kết thúc, trong vòng 24 giờ.');
                if ($dich === 'HOAN_THANH') {
                    $goi = DangKyGoiTap::lockForUpdate()->findOrFail($lich->dang_ky_goi_tap_id);
                    abort_unless($goi->khach_hang_id === $lich->khach_hang_id && $goi->kich_hoat_luc && $lich->bat_dau_luc->greaterThanOrEqualTo($goi->kich_hoat_luc) && $lich->ket_thuc_luc->lessThanOrEqualTo($goi->het_han_luc) && in_array($goi->trang_thai, ['DANG_SU_DUNG', 'HET_HAN']) && $goi->so_buoi_con_lai > 0 && ! $lich->tieu_hao_luc, 409, 'Gói gắn với buổi tập không còn buổi hợp lệ.');
                    $goi->decrement('so_buoi_con_lai');
                    $lich->tieu_hao_luc = now();
                }
                $lich->trang_thai = $dich;
                $lich->nguoi_ghi_nhan_id = $nguoi->id;
                $lich->ghi_nhan_luc = now();
                $lich->ly_do_ghi_nhan = $dich === 'VANG_MAT' ? $lyDo : null;
                $lich->save();
                $this->ghiAudit($nguoi, $lich, $dich, $lyDo);
                app(ThongBaoService::class)->lichHen($lich, $hanhDong);
            } elseif ($hanhDong === 'dong-xu-ly') {
                abort_unless($nguoi->vai_tro === TaiKhoan::ADMIN, 403);
                if ($lich->dong_xu_ly_luc) {
                    abort_unless($lich->nguoi_dong_xu_ly_id === $nguoi->id && $lich->ly_do_dong_xu_ly === $lyDo, 409, 'Buổi này đã được đóng xử lý.');

                    return $lich;
                }
                abort_unless($trangThai === 'QUA_HAN_XAC_NHAN', 409, 'Chỉ đóng xử lý buổi quá hạn xác nhận.');
                $lich->update(['trang_thai' => 'QUA_HAN_XAC_NHAN', 'nguoi_dong_xu_ly_id' => $nguoi->id, 'dong_xu_ly_luc' => now(), 'ly_do_dong_xu_ly' => $lyDo]);
                $this->ghiAudit($nguoi, $lich, 'DONG_BUOI_QUA_HAN', $lyDo);
            } else {
                abort(404);
            }

            return $lich;
        }, 3);
    }

    private function ghiAudit(TaiKhoan $nguoi, LichHenHuanLuyen $lich, string $hanhDong, string $lyDo): void
    {
        DB::table('nhat_ky_he_thong')->insert(['tai_khoan_id' => $nguoi->id, 'hanh_dong' => $hanhDong, 'loai_tai_nguyen' => 'lich_hen_huan_luyen', 'tai_nguyen_id' => $lich->id, 'metadata_an_toan' => json_encode(['ly_do' => $lyDo], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function duLieu(LichHenHuanLuyen $lich, TaiKhoan $nguoi): array
    {
        $lich->loadMissing(['khach.taiKhoan', 'pt.taiKhoan']);
        $s = $lich->trangThaiHieuLuc();
        $hanhDong = [];
        $laPT = $nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN;
        if ($nguoi->vai_tro === TaiKhoan::KHACH_HANG && in_array($s, ['CHO_XAC_NHAN', 'DA_XAC_NHAN']) && $lich->bat_dau_luc->greaterThanOrEqualTo(now()->addHours(2))) {
            $hanhDong[] = 'huy';
        }
        if ($laPT && $s === 'CHO_XAC_NHAN') {
            $hanhDong = ['xac-nhan', 'tu-choi'];
        }
        if ($laPT && $s === 'DA_XAC_NHAN' && $lich->ket_thuc_luc->lessThanOrEqualTo(now())) {
            $hanhDong = ['hoan-thanh', 'vang-mat'];
        }
        if ($nguoi->vai_tro === TaiKhoan::ADMIN && $s === 'QUA_HAN_XAC_NHAN' && ! $lich->dong_xu_ly_luc) {
            $hanhDong[] = 'dong-xu-ly';
        }

        return ['id' => $lich->id, 'khung_gio_id' => $lich->khung_gio_id, 'khach_hang' => $lich->khach->taiKhoan->ho_ten, 'pt' => $lich->pt->taiKhoan->ho_ten, 'dang_ky_goi_tap_id' => $lich->dang_ky_goi_tap_id, 'bat_dau_luc' => $lich->bat_dau_luc->toIso8601String(), 'ket_thuc_luc' => $lich->ket_thuc_luc->toIso8601String(), 'trang_thai' => $s, 'han_xac_nhan_dat_lich' => $lich->han_xac_nhan_dat_lich?->toIso8601String(), 'han_xac_nhan_hoan_thanh' => $lich->ket_thuc_luc->addHours(24)->toIso8601String(), 'tieu_hao_luc' => $lich->tieu_hao_luc?->toIso8601String(), 'huy_luc' => $lich->huy_luc?->toIso8601String(), 'ly_do_huy' => $lich->ly_do_huy, 'dong_xu_ly_luc' => $lich->dong_xu_ly_luc?->toIso8601String(), 'ly_do_dong_xu_ly' => $lich->ly_do_dong_xu_ly, 'ghi_nhan_luc' => $lich->ghi_nhan_luc?->toIso8601String(), 'ly_do_ghi_nhan' => $lich->ly_do_ghi_nhan, 'hanh_dong' => $hanhDong];
    }

    public function donQuaHan(): int
    {
        $so = 0;
        LichHenHuanLuyen::where(fn ($q) => $q->where(fn ($p) => $p->where('trang_thai', 'CHO_XAC_NHAN')->where('han_xac_nhan_dat_lich', '<=', now()->format('Y-m-d H:i:s.u')))->orWhere(fn ($p) => $p->where('trang_thai', 'DA_XAC_NHAN')->where('ket_thuc_luc', '<=', now()->subHours(24)->format('Y-m-d H:i:s.u'))))->select(['id', 'khach_hang_id', 'huan_luyen_vien_id'])->chunkById(100, function ($ds) use (&$so) {
            foreach ($ds as $goc) {
                $so += DB::transaction(function () use ($goc) {
                    HoSoKhachHang::lockForUpdate()->findOrFail($goc->khach_hang_id);
                    HoSoHuanLuyenVien::lockForUpdate()->findOrFail($goc->huan_luyen_vien_id);
                    $lich = LichHenHuanLuyen::lockForUpdate()->findOrFail($goc->id);
                    $s = $lich->trangThaiHieuLuc();
                    if ($s === $lich->trang_thai) {
                        return 0;
                    }
                    $lich->trang_thai = $s;
                    if ($s === 'HET_HAN') {
                        $lich->huy_luc = now();
                        $lich->ly_do_huy = 'PT không xác nhận trong thời hạn.';
                    }
                    $lich->save();
                    app(ThongBaoService::class)->lichHen($lich, $s === 'HET_HAN' ? 'het-han' : 'qua-han');

                    return 1;
                }, 3);
            }
        });

        return $so;
    }
}
