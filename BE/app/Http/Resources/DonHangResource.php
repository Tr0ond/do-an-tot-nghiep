<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonHangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $trangThai = $this->trang_thai;
        if ($trangThai === 'DANG_SU_DUNG' && $this->het_han_luc?->lessThanOrEqualTo(now())) {
            $trangThai = 'HET_HAN';
        }
        if ($trangThai === 'CHO_THANH_TOAN' && $this->han_thanh_toan?->lessThanOrEqualTo(now())) {
            $trangThai = 'HET_HAN_THANH_TOAN';
        }

        return ['id' => $this->id, 'ma_don_payos' => $this->ma_don_payos, 'ten_goi' => $this->ten_goi_snapshot, 'gia' => $this->gia_snapshot, 'co_chatbot' => $this->co_chatbot_snapshot, 'so_luot_chatbot_moi_ngay' => $this->so_luot_chatbot_moi_ngay_snapshot, 'so_buoi_pt' => $this->so_buoi_pt_snapshot, 'thoi_han_ngay' => $this->thoi_han_ngay_snapshot, 'so_buoi_con_lai' => $this->so_buoi_con_lai, 'trang_thai' => $trangThai, 'han_thanh_toan' => $this->han_thanh_toan?->toIso8601String(), 'kich_hoat_luc' => $this->kich_hoat_luc?->toIso8601String(), 'het_han_luc' => $this->het_han_luc?->toIso8601String(), 'created_at' => $this->created_at?->toIso8601String(), 'url_thanh_toan' => $trangThai === 'CHO_THANH_TOAN' ? $this->url_thanh_toan : null, 'khach_hang' => $this->when($request->is('api/v1/admin/*'), fn () => ['id' => $this->khachHang->id, 'ho_ten' => $this->khachHang->taiKhoan->ho_ten]), 'thanh_toan' => $this->whenLoaded('thanhToan', fn () => $this->thanhToan->map(fn ($t) => ['id' => $t->id, 'ma_giao_dich' => $t->ma_giao_dich, 'so_tien' => $t->so_tien, 'trang_thai' => $t->trang_thai, 'thanh_toan_luc' => $t->thanh_toan_luc?->toIso8601String(), 'ly_do_doi_soat' => $t->ly_do_doi_soat, 'so_tien_hoan' => $t->so_tien_hoan, 'ma_hoan_tien' => $t->ma_hoan_tien, 'ly_do_hoan_tien' => $t->ly_do_hoan_tien]))];
    }
}
