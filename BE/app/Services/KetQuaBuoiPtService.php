<?php

namespace App\Services;

use App\Models\BaiTap;
use App\Models\HoSoHuanLuyenVien;
use App\Models\HoSoKhachHang;
use App\Models\KetQuaBuoiPt;
use App\Models\LichHenHuanLuyen;
use App\Models\TaiKhoan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KetQuaBuoiPtService
{
    public function lich(TaiKhoan $nguoi, int $id): LichHenHuanLuyen
    {
        abort_unless(in_array($nguoi->vai_tro, [TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN], true), 403);

        return app(LichHenService::class)->phamVi($nguoi)->findOrFail($id);
    }

    public function duLieu(TaiKhoan $nguoi, int $id): array
    {
        $lich = $this->lich($nguoi, $id);
        $k = KetQuaBuoiPt::where('lich_hen_id', $id)->first();
        $ghi = $nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN && $this->trongHan($lich) && ! $k?->chot_luc;

        return ['lich' => app(LichHenService::class)->duLieu($lich, $nguoi),
            'ket_qua' => $k ? ['id' => $k->id, 'bai_tap' => $k->bai_tap, 'ghi_chu' => $k->ghi_chu,
                'nhan_xet' => $k->nhan_xet, 'chot_luc' => $k->chot_luc?->toISOString(),
                'updated_at' => $k->updated_at?->format('Y-m-d H:i:s.u')] : null,
            'co_the_ghi' => $ghi, 'co_the_chot' => $ghi && $lich->ket_thuc_luc->lessThanOrEqualTo(now()),
            'ly_do_khoa' => $ghi ? null : ($k?->chot_luc ? 'Kết quả đã chốt, không thể chỉnh sửa.'
                : ($nguoi->vai_tro === TaiKhoan::KHACH_HANG ? 'PT ghi kết quả của buổi tập; bạn có thể xem dữ liệu đã lưu.'
                    : 'Chỉ ghi ở lịch đã xác nhận/hoàn thành, từ giờ bắt đầu đến trước kết thúc +24 giờ.'))];
    }

    private function trongHan(LichHenHuanLuyen $lich): bool
    {
        return in_array($lich->trang_thai, ['DA_XAC_NHAN', 'HOAN_THANH'], true)
            && $lich->bat_dau_luc->lessThanOrEqualTo(now()) && now()->lessThan($lich->ket_thuc_luc->addHours(24));
    }

    private function khoa(TaiKhoan $nguoi, int $id): LichHenHuanLuyen
    {
        abort_unless($nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN, 403);
        $goc = $this->lich($nguoi, $id);
        // Cùng thứ tự khóa với xác nhận lịch và đổi PT; kiểm tra lại phân công sau khi có khóa.
        HoSoKhachHang::lockForUpdate()->findOrFail($goc->khach_hang_id);
        HoSoHuanLuyenVien::lockForUpdate()->findOrFail($goc->huan_luyen_vien_id);

        return app(LichHenService::class)->phamVi($nguoi)->lockForUpdate()->findOrFail($id);
    }

    public function luu(TaiKhoan $nguoi, int $id, array $d): KetQuaBuoiPt
    {
        return DB::transaction(function () use ($nguoi, $id, $d) {
            $lich = $this->khoa($nguoi, $id);
            $k = KetQuaBuoiPt::where('lich_hen_id', $id)->lockForUpdate()->first();
            $cu = collect($k?->bai_tap ?? [])->keyBy('bai_tap_id');
            $idsMoi = collect($d['bai_tap'])->pluck('bai_tap_id')->diff($cu->keys());
            $catalog = BaiTap::dangHienThi()->whereIn('id', $idsMoi)->get()->keyBy('id');
            $bai = [];
            foreach ($d['bai_tap'] as $i => $b) {
                $idBai = (int) $b['bai_tap_id'];
                $snapshot = $cu->get($idBai);
                if (! $snapshot) {
                    if (! $catalog->has($idBai)) {
                        throw ValidationException::withMessages(['bai_tap.'.$i.'.bai_tap_id' => 'Bài tập không còn hiển thị. Chọn bài khác hoặc tải lại.']);
                    }
                    $e = $catalog[$idBai];
                    $snapshot = ['bai_tap_id' => $idBai, 'ten_bai_tap' => $e->ten_tieng_viet ?: $e->ten_bai_tap,
                        'anh_url' => $e->anh_url, 'gif_url' => $e->gif_url, 'ghi_cong_media' => $e->ghi_cong_media];
                }
                $snapshot['hiep_tap'] = array_map(fn ($h) => ['so_lan_lap' => (int) $h['so_lan_lap'],
                    'khoi_luong_kg' => $h['khoi_luong_kg'] === null ? null : number_format((float) $h['khoi_luong_kg'], 2, '.', ''),
                    'nghi_giay' => (int) $h['nghi_giay']], $b['hiep_tap']);
                $bai[] = $snapshot;
            }
            $ghiChu = trim($d['ghi_chu'] ?? '');
            $nhanXet = trim($d['nhan_xet'] ?? '');
            $noiDung = ['bai_tap' => $bai, 'ghi_chu' => $ghiChu === '' ? null : $ghiChu,
                'nhan_xet' => $nhanXet === '' ? null : $nhanXet];
            // Retry nháp đã lưu không tạo thêm phiên bản hoặc ghi đè kết quả mới.
            if ($k && $k->bai_tap === $noiDung['bai_tap'] && $k->ghi_chu === $noiDung['ghi_chu'] && $k->nhan_xet === $noiDung['nhan_xet']) {
                return $k;
            }
            abort_unless($this->trongHan($lich) && ! $k?->chot_luc, 409, 'Buổi không còn cho phép sửa kết quả.');
            $this->kiemTraPhienBan($k, $d['updated_at']);
            $k ??= new KetQuaBuoiPt(['lich_hen_id' => $id, 'nguoi_ghi_id' => $nguoi->id]);
            $k->fill($noiDung);
            $this->luuPhienBan($k);
            $this->audit($nguoi, $lich, 'LUU_KET_QUA_PT');

            return $k;
        }, 3);
    }

    public function chot(TaiKhoan $nguoi, int $id, string $phienBan): KetQuaBuoiPt
    {
        return DB::transaction(function () use ($nguoi, $id, $phienBan) {
            $lich = $this->khoa($nguoi, $id);
            $k = KetQuaBuoiPt::where('lich_hen_id', $id)->lockForUpdate()->first();
            if ($k?->chot_luc) {
                return $k;
            }
            abort_unless($this->trongHan($lich) && $lich->ket_thuc_luc->lessThanOrEqualTo(now()), 409, 'Chỉ chốt sau giờ kết thúc, trong hạn 24 giờ.');
            $this->kiemTraPhienBan($k, $phienBan);
            if (! $k || ! count($k->bai_tap) || collect($k->bai_tap)->contains(fn ($b) => ! count($b['hiep_tap']))) {
                throw ValidationException::withMessages(['bai_tap' => 'Cần ít nhất một bài và một hiệp thực tế cho mỗi bài trước khi chốt.']);
            }
            $k->chot_luc = now();
            $this->luuPhienBan($k);
            $this->audit($nguoi, $lich, 'CHOT_KET_QUA_PT');
            app(ThongBaoService::class)->choKhach($lich->khach_hang_id, 'ket-qua-pt/'.$k->id.'/chot',
                'PT đã ghi kết quả buổi tập', 'Mở lịch hẹn để xem bài tập, kết quả và nhận xét của PT.', '/khach-hang/lich-hen/'.$lich->id);

            return $k;
        }, 3);
    }

    private function kiemTraPhienBan(?KetQuaBuoiPt $k, ?string $phienBan): void
    {
        abort_unless($k?->updated_at?->format('Y-m-d H:i:s.u') === $phienBan, 409, 'Kết quả đã thay đổi. Tải lại trước khi tiếp tục.');
    }

    private function luuPhienBan(KetQuaBuoiPt $k): void
    {
        $k->updated_at = $k->updated_at && now()->lessThanOrEqualTo($k->updated_at) ? $k->updated_at->copy()->addMicrosecond() : now();
        $k->save();
    }

    private function audit(TaiKhoan $nguoi, LichHenHuanLuyen $lich, string $hanhDong): void
    {
        DB::table('nhat_ky_he_thong')->insert(['tai_khoan_id' => $nguoi->id, 'hanh_dong' => $hanhDong,
            'loai_tai_nguyen' => 'lich_hen_huan_luyen', 'tai_nguyen_id' => $lich->id,
            'metadata_an_toan' => '{}', 'created_at' => now(), 'updated_at' => now()]);
    }
}
