<?php

namespace App\Services;

use App\Models\ChiSoCoThe;
use App\Models\HoSoKhachHang;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ChiSoCoTheService
{
    private function khoaKhach(TaiKhoan $nguoi, int $khachId): HoSoKhachHang
    {
        abort_unless(in_array($nguoi->vai_tro, [TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN], true), 403);
        if ($nguoi->vai_tro === TaiKhoan::KHACH_HANG) {
            abort_unless((int) $nguoi->hoSoKhachHang?->id === $khachId, 404);
        }
        $khach = HoSoKhachHang::lockForUpdate()->findOrFail($khachId);
        if ($nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN) {
            // Cùng thứ tự khóa với đổi PT; không trả dữ liệu qua phân công đã hết hạn.
            $hienTai = now()->format('Y-m-d H:i:s.u');
            PhanCongHuanLuyenVien::where('khach_hang_id', $khachId)->where('huan_luyen_vien_id', $nguoi->hoSoHuanLuyenVien?->id)
                ->where('bat_dau_luc', '<=', $hienTai)->where(fn ($q) => $q->whereNull('ket_thuc_luc')->orWhere('ket_thuc_luc', '>', $hienTai))
                ->lockForUpdate()->firstOrFail();
        }

        return $khach;
    }

    public function danhSach(TaiKhoan $nguoi, int $khachId, int $soNgay, int $page, ?string $denNgay = null): array
    {
        return DB::transaction(function () use ($nguoi, $khachId, $soNgay, $page, $denNgay) {
            $khach = $this->khoaKhach($nguoi, $khachId);
            $homNay = now('Asia/Ho_Chi_Minh')->toDateString();
            $den = $denNgay ?? $homNay;
            $tu = CarbonImmutable::parse($den, 'Asia/Ho_Chi_Minh')->subDays($soNgay - 1)->toDateString();
            $q = ChiSoCoThe::where('khach_hang_id', $khachId)->whereBetween('ngay_ghi', [$tu, $den]);
            $moc = (clone $q)->orderBy('ngay_ghi')->get();
            $phanTrang = (clone $q)->orderByDesc('ngay_ghi')->paginate(20, ['*'], 'page', $page);
            $hopLe = $moc->filter(fn ($x) => $x->bmi() !== null)->values();
            $moiNhat = ChiSoCoThe::where('khach_hang_id', $khachId)->where('ngay_ghi', '<=', $homNay)->orderByDesc('ngay_ghi')->first();

            return ['data' => ['ho_ten' => $khach->taiKhoan->ho_ten, 'moi_nhat' => $moiNhat?->duLieu(),
                'tu_ngay' => $tu, 'den_ngay' => $den, 'so_lan_ghi' => $moc->count(),
                'thay_doi_can_nang_kg' => $hopLe->count() >= 2 ? round((float) $hopLe->last()->can_nang_kg - (float) $hopLe->first()->can_nang_kg, 2) : null,
                'thay_doi_bmi' => $hopLe->count() >= 2 ? round($hopLe->last()->bmi() - $hopLe->first()->bmi(), 2) : null,
                'cac_moc' => $moc->map(fn ($x) => $x->duLieu()), 'lich_su' => $phanTrang->getCollection()->map(fn ($x) => $x->duLieu())],
                'meta' => ['current_page' => $phanTrang->currentPage(), 'last_page' => $phanTrang->lastPage(), 'total' => $phanTrang->total(), 'per_page' => 20]];
        }, 3);
    }

    public function luu(TaiKhoan $nguoi, array $d, ?int $id = null): ChiSoCoThe
    {
        abort_unless($nguoi->vai_tro === TaiKhoan::KHACH_HANG, 403);

        return DB::transaction(function () use ($nguoi, $d, $id) {
            $khachId = $nguoi->hoSoKhachHang->id;
            $this->khoaKhach($nguoi, $khachId);
            $q = ChiSoCoThe::where('khach_hang_id', $khachId);
            $ban = $id ? (clone $q)->lockForUpdate()->findOrFail($id) : (clone $q)->where('ngay_ghi', $d['ngay_ghi'])->lockForUpdate()->first();
            $noiDung = ['ngay_ghi' => $d['ngay_ghi'], 'can_nang_kg' => number_format((float) $d['can_nang_kg'], 2, '.', ''),
                'chieu_cao_cm' => number_format((float) $d['chieu_cao_cm'], 2, '.', ''), 'ghi_chu' => $d['ghi_chu']];
            if ($ban) {
                $giong = $ban->only(array_keys($noiDung)) === $noiDung;
                if ($id === null) {
                    if ($giong) {
                        return $ban;
                    }
                    throw new ConflictHttpException('Ngày này đã có chỉ số. Hãy mở bản ghi trong lịch sử để sửa.');
                }
                if ($ban->updated_at?->format('Y-m-d H:i:s.u') !== $d['updated_at']) {
                    if ($giong) {
                        return $ban;
                    }
                    throw new ConflictHttpException('Bản ghi đã thay đổi. Hãy tải lại lịch sử và mở sửa.');
                }
                if ((clone $q)->where('ngay_ghi', $d['ngay_ghi'])->whereKeyNot($id)->exists()) {
                    throw new ConflictHttpException('Ngày này đã có một bản ghi khác.');
                }
                if (! $giong) {
                    $ban->fill($noiDung);
                    // Đảm bảo version tăng kể cả khi hai lần sửa dùng cùng mốc thời gian.
                    $ban->updated_at = now()->max($ban->updated_at?->copy()->addMicrosecond() ?? now());
                    $ban->save();
                }

                return $ban;
            }

            return ChiSoCoThe::create(['khach_hang_id' => $khachId, ...$noiDung]);
        }, 3);
    }

    public function nguCanhAi(int $khachId): array
    {
        $den = now('Asia/Ho_Chi_Minh')->toDateString();
        $tu = now('Asia/Ho_Chi_Minh')->subDays(89)->toDateString();
        $q = ChiSoCoThe::where('khach_hang_id', $khachId)->where('ngay_ghi', '<=', $den);
        $gon = fn ($x) => ['ngay_ghi' => $x->ngay_ghi, 'can_nang_kg' => $x->can_nang_kg !== null ? (float) $x->can_nang_kg : null,
            'chieu_cao_cm' => $x->chieu_cao_cm !== null ? (float) $x->chieu_cao_cm : null, 'bmi' => $x->bmi()];
        $moi = (clone $q)->orderByDesc('ngay_ghi')->first();
        $moc = (clone $q)->where('ngay_ghi', '>=', $tu)->orderByDesc('ngay_ghi')->limit(10)->get()->reverse()->values();
        $hopLe = (clone $q)->where('ngay_ghi', '>=', $tu)->orderBy('ngay_ghi')->get()->filter(fn ($x) => $x->bmi() !== null)->values();

        return ['moi_nhat' => $moi ? $gon($moi) : null, 'cac_moc_gan_day' => $moc->map($gon)->all(),
            'thay_doi_can_nang_90_ngay_kg' => $hopLe->count() >= 2 ? round((float) $hopLe->last()->can_nang_kg - (float) $hopLe->first()->can_nang_kg, 2) : null,
            'luu_y' => 'Chỉ số KH tự ghi; BMI chỉ tham khảo, không phân biệt cơ/mỡ, không suy ra %mỡ hoặc chẩn đoán. Không tự phân loại BMI trẻ em. Ngày thiếu số đo không có dữ liệu.'];
    }
}
