<?php

namespace App\Services;

use App\Models\HoSoHuanLuyenVien;
use App\Models\HoSoKhachHang;
use App\Models\LichHenHuanLuyen;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PhanCongService
{
    public function phanCong(TaiKhoan $admin, array $duLieu): PhanCongHuanLuyenVien
    {
        $duLieu['phan_cong_hien_tai_id'] = isset($duLieu['phan_cong_hien_tai_id']) ? (int) $duLieu['phan_cong_hien_tai_id'] : null;

        return DB::transaction(function () use ($admin, $duLieu) {
            $khach = HoSoKhachHang::lockForUpdate()->findOrFail($duLieu['khach_hang_id']);
            $ma = strtolower($duLieu['client_request_id']);
            $daTao = PhanCongHuanLuyenVien::where('client_request_id', $ma)->first();
            if ($daTao) {
                if ($daTao->khach_hang_id !== $khach->id || $daTao->huan_luyen_vien_id !== (int) $duLieu['huan_luyen_vien_id'] || $daTao->nguoi_phan_cong_id !== $admin->id || $daTao->phan_cong_truoc_id !== ($duLieu['phan_cong_hien_tai_id'] ?? null)) {
                    throw new ConflictHttpException('Mã yêu cầu đã dùng cho phân công khác.');
                }

                if ($daTao->phan_cong_truoc_id && PhanCongHuanLuyenVien::find($daTao->phan_cong_truoc_id)?->ly_do_ket_thuc !== ($duLieu['ly_do'] ?? '')) {
                    throw new ConflictHttpException('Mã yêu cầu đã dùng với lý do đổi PT khác.');
                }

                return $daTao;
            }
            abort_unless($khach->taiKhoan()->where('trang_thai', TaiKhoan::HOAT_DONG)->exists(), 409, 'Khách hàng đang bị khóa.');
            $pt = HoSoHuanLuyenVien::lockForUpdate()->findOrFail($duLieu['huan_luyen_vien_id']);
            abort_unless($pt->taiKhoan()->where('trang_thai', TaiKhoan::HOAT_DONG)->exists(), 409, 'PT đang bị khóa.');
            $cu = PhanCongHuanLuyenVien::where('khach_hang_id', $khach->id)->whereNull('ket_thuc_luc')->lockForUpdate()->first();
            if (! $cu) {
                abort_unless(app(MuaGoiService::class)->goiHieuLuc($khach->id)->where('so_buoi_con_lai', '>', 0)->exists(), 409, 'Khách chưa có gói PT khả dụng.');
            }
            if (($cu?->id) !== ($duLieu['phan_cong_hien_tai_id'] ?? null)) {
                throw new ConflictHttpException('Phân công đã thay đổi. Hãy tải lại trước khi lưu.');
            }
            if ($cu?->huan_luyen_vien_id === $pt->id) {
                return $cu;
            }
            $moc = now();
            if ($cu) {
                app(LichHenService::class)->dongYeuCauHetHan(null, $khach->id);
                $chuaXuLy = DB::table('lich_hen_huan_luyen')->where('khach_hang_id', $khach->id)->where('bat_dau_luc', '<=', $moc)->whereIn('trang_thai', ['CHO_XAC_NHAN', 'DA_XAC_NHAN', 'QUA_HAN_XAC_NHAN'])->whereNull('dong_xu_ly_luc')->exists();
                if ($chuaXuLy) {
                    throw new ConflictHttpException('Còn buổi đã bắt đầu chưa xử lý xong. Chưa thể đổi PT.');
                }
                $lyDo = $duLieu['ly_do'] ?? '';
                if (trim($lyDo) === '') {
                    throw new ConflictHttpException('Cần ghi lý do đổi PT.');
                }
                $moc = $cu->bat_dau_luc && $moc->lessThanOrEqualTo($cu->bat_dau_luc) ? $cu->bat_dau_luc->addMicrosecond() : $moc;
                $cu->update(['ket_thuc_luc' => $moc, 'ly_do_ket_thuc' => $lyDo]);
                $lichHuy = LichHenHuanLuyen::where('phan_cong_id', $cu->id)->where('bat_dau_luc', '>', $moc)->whereIn('trang_thai', ['CHO_XAC_NHAN', 'DA_XAC_NHAN'])->get();
                DB::table('lich_hen_huan_luyen')->where('phan_cong_id', $cu->id)->where('bat_dau_luc', '>', $moc)->whereIn('trang_thai', ['CHO_XAC_NHAN', 'DA_XAC_NHAN'])->update(['trang_thai' => 'DA_HUY', 'nguoi_huy_id' => $admin->id, 'huy_luc' => $moc, 'ly_do_huy' => 'Đổi PT: '.$lyDo, 'updated_at' => $moc]);
                DB::table('ke_hoach_tap')->where('phan_cong_id', $cu->id)->whereIn('trang_thai', ['NHAP', 'CHO_DUYET', 'CHO_XAC_NHAN'])->update(['trang_thai' => 'DA_HUY', 'updated_at' => $moc]);
                foreach ($lichHuy as $lich) {
                    app(ThongBaoService::class)->lichHen($lich, 'doi-pt');
                }
            }
            $moi = PhanCongHuanLuyenVien::create(['khach_hang_id' => $khach->id, 'huan_luyen_vien_id' => $pt->id, 'nguoi_phan_cong_id' => $admin->id, 'bat_dau_luc' => $moc, 'client_request_id' => $ma, 'phan_cong_truoc_id' => $cu?->id]);
            DB::table('hoi_thoai')->insert(['phan_cong_id' => $moi->id, 'created_at' => $moc, 'updated_at' => $moc]);
            $nguoiNhan = [$khach->tai_khoan_id, $pt->tai_khoan_id];
            if ($cu) {
                $nguoiNhan[] = $cu->pt->tai_khoan_id;
            }
            app(ChatService::class)->baoCapNhat($nguoiNhan);
            $thongBao = app(ThongBaoService::class);
            $thongBao->gui($khach->tai_khoan_id, 'phan-cong/'.$moi->id.'/kh', $cu ? 'PT phụ trách đã thay đổi' : 'Bạn đã được phân công PT', 'PT mới đã được phân công. Bạn có thể xem hồ sơ và trao đổi trong tin nhắn.', '/khach-hang/ho-so');
            $thongBao->gui($pt->tai_khoan_id, 'phan-cong/'.$moi->id.'/pt', 'Bạn có học viên mới', 'Một học viên đã được phân công cho bạn. Mở hồ sơ để bắt đầu theo dõi.', '/pt/hoc-vien/'.$khach->id.'/ke-hoach');
            if ($cu) {
                $thongBao->gui($cu->pt->tai_khoan_id, 'phan-cong/'.$cu->id.'/ket-thuc', 'Phân công học viên đã kết thúc', 'Admin đã đổi PT cho một học viên. Quyền truy cập học viên và hội thoại cũ đã kết thúc.', '/pt/hoc-vien');
            }
            DB::table('nhat_ky_he_thong')->insert(['tai_khoan_id' => $admin->id, 'hanh_dong' => $cu ? 'DOI_PT' : 'PHAN_CONG_PT', 'loai_tai_nguyen' => 'phan_cong_huan_luyen_vien', 'tai_nguyen_id' => $moi->id, 'metadata_an_toan' => json_encode(['phan_cong_truoc_id' => $cu?->id]), 'created_at' => $moc, 'updated_at' => $moc]);

            return $moi;
        }, 3);
    }
}
