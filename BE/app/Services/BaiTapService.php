<?php

namespace App\Services;

use App\Models\BaiTap;
use App\Models\NhomCo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class BaiTapService
{
    public function taoBaiTap(array $duLieu): BaiTap
    {
        try {
            return DB::transaction(function () use ($duLieu) {
                $this->kiemTraNhom($duLieu['nhom_co_id']);
                $baiTap = new BaiTap(Arr::only($duLieu, ['ma_nguon', 'ten_bai_tap', 'ten_tieng_viet', 'nhom_co_id', 'dung_cu', 'trang_thai']));
                $baiTap->nguon_du_lieu = 'admin';
                $baiTap->dung_cu_nguon = $duLieu['dung_cu'];
                $this->ganNoiDungViet($baiTap, $duLieu);
                $baiTap->save();

                return $baiTap->refresh()->load('nhomCo');
            });
        } catch (UniqueConstraintViolationException $loi) {
            if (str_contains($loi->getMessage(), 'uq_t11_01')) {
                throw ValidationException::withMessages(['ma_nguon' => 'Mã bài tập này đã được sử dụng.']);
            }
            throw $loi;
        }
    }

    public function suaBaiTap(int $id, array $duLieu): BaiTap
    {
        return DB::transaction(function () use ($id, $duLieu) {
            $baiTap = BaiTap::lockForUpdate()->findOrFail($id);
            $this->kiemTraPhienBan($baiTap, $duLieu['updated_at']);
            $this->kiemTraNhom($duLieu['nhom_co_id'], $baiTap->nhom_co_id);
            if ($baiTap->nguon_du_lieu !== 'admin' && array_key_exists('ten_bai_tap', $duLieu)) {
                throw ValidationException::withMessages(['ten_bai_tap' => 'Tên gốc của bài nhập được giữ nguyên. Hãy sửa tên tiếng Việt.']);
            }
            $baiTap->fill(Arr::only($duLieu, ['ten_bai_tap', 'ten_tieng_viet', 'nhom_co_id', 'dung_cu']));
            if ($baiTap->nguon_du_lieu === 'admin') {
                $baiTap->dung_cu_nguon = $duLieu['dung_cu'];
            }
            $this->ganNoiDungViet($baiTap, $duLieu);
            $baiTap->save();

            return $baiTap->refresh()->load('nhomCo');
        });
    }

    public function datTrangThai(int $id, array $duLieu): BaiTap
    {
        return DB::transaction(function () use ($id, $duLieu) {
            $baiTap = BaiTap::lockForUpdate()->findOrFail($id);
            $this->kiemTraPhienBan($baiTap, $duLieu['updated_at']);
            $baiTap->trang_thai = $duLieu['trang_thai'];
            $baiTap->save();

            return $baiTap->refresh()->load('nhomCo');
        });
    }

    private function kiemTraNhom(int $id, ?int $nhomCu = null): void
    {
        $nhom = NhomCo::lockForUpdate()->findOrFail($id);
        if ($id !== $nhomCu && $nhom->trang_thai !== 'HOAT_DONG') {
            throw ValidationException::withMessages(['nhom_co_id' => 'Không thể chọn nhóm cơ đã ngừng sử dụng.']);
        }
    }

    private function kiemTraPhienBan(BaiTap $baiTap, string $phienBan): void
    {
        if ($baiTap->updated_at?->format('Y-m-d H:i:s.u') !== $phienBan) {
            throw new ConflictHttpException('Bài tập đã được thay đổi. Hãy tải lại bản mới trước khi lưu.');
        }
    }

    private function ganNoiDungViet(BaiTap $baiTap, array $duLieu): void
    {
        $huongDan = $baiTap->huong_dan ?? [];
        $cacBuoc = $baiTap->cac_buoc ?? [];
        unset($huongDan['vi'], $cacBuoc['vi']);
        if ($duLieu['huong_dan_vi'] !== null && $duLieu['huong_dan_vi'] !== '') {
            $huongDan['vi'] = $duLieu['huong_dan_vi'];
        }
        if ($duLieu['cac_buoc_vi'] !== []) {
            $cacBuoc['vi'] = $duLieu['cac_buoc_vi'];
        }
        $baiTap->huong_dan = $huongDan ?: null;
        $baiTap->cac_buoc = $cacBuoc ?: null;
    }
}
