<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class BaiTapAdminResource extends BaiTapResource
{
    public function toArray(Request $request): array
    {
        $duLieu = [
            ...parent::toArray($request),
            'nguon_du_lieu' => $this->nguon_du_lieu, 'trang_thai' => $this->trang_thai,
            'nhom_co_id' => $this->nhom_co_id, 'nhom_co_trang_thai' => $this->nhomCo->trang_thai,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s.u'),
        ];
        if (array_key_exists('huong_dan', $this->resource->getAttributes())) {
            $duLieu = [...$duLieu, 'huong_dan_vi' => $this->huong_dan['vi'] ?? null, 'cac_buoc_vi' => $this->cac_buoc['vi'] ?? [], 'gif_url' => $this->gif_url];
        }

        return $duLieu;
    }
}
