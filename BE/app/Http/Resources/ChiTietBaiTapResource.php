<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ChiTietBaiTapResource extends BaiTapResource
{
    public function toArray(Request $request): array
    {
        $ngonNgu = ! empty($this->huong_dan['vi']) || ! empty($this->cac_buoc['vi']) ? 'vi' : 'en';

        return [
            ...parent::toArray($request),
            'gif_url' => $this->gif_url, 'nguon_du_lieu' => $this->nguon_du_lieu,
            'bo_phan_co_the' => $this->bo_phan_co_the, 'co_phu' => $this->co_phu ?? [],
            'ngon_ngu_huong_dan' => $ngonNgu,
            'huong_dan' => $this->huong_dan[$ngonNgu] ?? null,
            'cac_buoc' => $this->cac_buoc[$ngonNgu] ?? [],
        ];
    }
}
