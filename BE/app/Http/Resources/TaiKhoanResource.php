<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaiKhoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ho_ten' => $this->ho_ten,
            'email' => $this->email,
            'vai_tro' => $this->vai_tro,
            'trang_thai' => $this->trang_thai,
            'ho_so_khach_hang' => $this->whenLoaded('hoSoKhachHang'),
            'ho_so_huan_luyen_vien' => $this->whenLoaded('hoSoHuanLuyenVien'),
        ];
    }
}
