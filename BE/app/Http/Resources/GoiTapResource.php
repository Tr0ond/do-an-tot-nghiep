<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoiTapResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $duLieu = ['id' => $this->id, 'ten_goi' => $this->ten_goi, 'gia' => $this->gia, 'co_chatbot' => $this->co_chatbot, 'so_luot_chatbot_moi_ngay' => $this->so_luot_chatbot_moi_ngay, 'so_buoi_pt' => $this->so_buoi_pt, 'thoi_han_ngay' => $this->thoi_han_ngay, 'loai_goi' => $this->so_buoi_pt > 0 ? 'PT_CHATBOT' : 'CHATBOT'];
        if ($request->is('api/v1/admin/*')) {
            $duLieu = [...$duLieu, 'trang_thai' => $this->trang_thai, 'updated_at' => $this->updated_at?->format('Y-m-d H:i:s.u')];
        }

        return $duLieu;
    }
}
