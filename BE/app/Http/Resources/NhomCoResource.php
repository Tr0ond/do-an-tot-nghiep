<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NhomCoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'ma_nhom_co' => $this->ma_nhom_co,
            'ten_nhom_co' => $this->ten_nhom_co, 'ten_nguon' => $this->ten_nguon,
            'trang_thai' => $this->trang_thai,
            'so_bai_tap' => (int) $this->so_bai_tap,
            'so_bai_hoat_dong' => (int) $this->so_bai_hoat_dong,
            'so_bai_hien_thi' => $this->trang_thai === 'HOAT_DONG' ? (int) $this->so_bai_hoat_dong : 0,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s.u'),
        ];
    }
}
