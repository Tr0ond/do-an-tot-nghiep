<?php

namespace App\Http\Resources;

use App\Models\BaiTapTrongGiaoAnMau;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiaoAnMauResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $duLieu = ['id' => $this->id, 'ten_giao_an' => $this->ten_giao_an, 'muc_tieu' => $this->muc_tieu, 'so_ngay_tap' => $this->so_ngay_tap, 'so_bai_tap' => $this->cac_bai_tap_count ?? $this->cacBaiTap->count(), 'trang_thai' => $this->trang_thai, 'duyet_luc' => $this->duyet_luc?->toIso8601String()];
        if ($request->is('api/v1/admin/*')) {
            $duLieu += ['nguoi_tao_id' => $this->nguoi_tao_id, 'nguoi_duyet_id' => $this->nguoi_duyet_id, 'updated_at' => $this->updated_at?->format('Y-m-d H:i:s.u')];
        }
        if ($this->relationLoaded('cacBaiTap')) {
            $duLieu['bai_tap'] = $this->cacBaiTap->map(function ($dong) {
                $bai = $dong->baiTap;

                return [
                    ...array_intersect_key($dong->toArray(), array_flip(BaiTapTrongGiaoAnMau::THUOC_TINH)),
                    'ten_bai_tap' => $bai->ten_tieng_viet ?: $bai->ten_bai_tap,
                    'nhom_co' => $bai->nhomCo->ten_nhom_co,
                    'kha_dung' => $bai->trang_thai === 'HOAT_DONG' && $bai->nhomCo->trang_thai === 'HOAT_DONG',
                    'anh_url' => $bai->anh_url,
                    'dung_cu' => $bai->dung_cu,
                ];
            })->all();
        }

        return $duLieu;
    }
}
