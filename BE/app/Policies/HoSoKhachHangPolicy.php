<?php

namespace App\Policies;

use App\Models\HoSoKhachHang;
use App\Models\TaiKhoan;
use Illuminate\Support\Facades\DB;

class HoSoKhachHangPolicy
{
    public function view(TaiKhoan $taiKhoan, HoSoKhachHang $hoSo): bool
    {
        if ($taiKhoan->vai_tro === TaiKhoan::ADMIN) {
            return true;
        }

        if ($taiKhoan->vai_tro === TaiKhoan::KHACH_HANG) {
            return $taiKhoan->id === $hoSo->tai_khoan_id;
        }

        if ($taiKhoan->vai_tro !== TaiKhoan::HUAN_LUYEN_VIEN) {
            return false;
        }

        $ptId = $taiKhoan->hoSoHuanLuyenVien?->id;

        return $ptId !== null && DB::table('phan_cong_huan_luyen_vien')
            ->where('khach_hang_id', $hoSo->id)
            ->where('huan_luyen_vien_id', $ptId)
            ->where('bat_dau_luc', '<=', now())
            ->where(function ($truyVan) {
                $truyVan->whereNull('ket_thuc_luc')->orWhere('ket_thuc_luc', '>', now());
            })->exists();
    }
}
