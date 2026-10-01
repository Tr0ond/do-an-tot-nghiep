<?php

namespace App\Services;

use App\Models\TaiKhoan;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaiKhoanService
{
    public function taoTaiKhoan(array $duLieu, string $vaiTro): TaiKhoan
    {
        try {
            return DB::transaction(function () use ($duLieu, $vaiTro) {
                $taiKhoan = new TaiKhoan([
                    'ho_ten' => $duLieu['ho_ten'],
                    'email' => $duLieu['email'],
                    'password' => $duLieu['password'],
                ]);
                $taiKhoan->vai_tro = $vaiTro;
                $taiKhoan->trang_thai = TaiKhoan::HOAT_DONG;
                $taiKhoan->save();

                if ($vaiTro === TaiKhoan::KHACH_HANG) {
                    $taiKhoan->hoSoKhachHang()->create([]);
                } elseif ($vaiTro === TaiKhoan::HUAN_LUYEN_VIEN) {
                    $taiKhoan->hoSoHuanLuyenVien()->create([]);
                }

                return $taiKhoan;
            });
        } catch (UniqueConstraintViolationException $loi) {
            // UNIQUE vẫn chặn hai request đồng thời vượt qua validation email.
            if (str_contains($loi->getMessage(), 'uq_t01_01')) {
                throw ValidationException::withMessages(['email' => ['Email đã được sử dụng.']]);
            }

            throw $loi;
        }
    }
}
