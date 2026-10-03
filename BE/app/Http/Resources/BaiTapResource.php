<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaiTapResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'ma_nguon' => $this->ma_nguon,
            'ten_bai_tap' => $this->ten_bai_tap, 'ten_tieng_viet' => $this->ten_tieng_viet,
            'nhom_co' => ['id' => $this->nhomCo->id, 'ten_nhom_co' => $this->nhomCo->ten_nhom_co],
            'dung_cu' => $this->dung_cu, 'dung_cu_nguon' => $this->dung_cu_nguon,
            'anh_url' => $this->anh_url, 'gif_url' => $this->gif_url, 'ghi_cong_media' => $this->ghi_cong_media,
        ];
    }
}
