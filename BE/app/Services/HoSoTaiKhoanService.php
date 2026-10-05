<?php

namespace App\Services;

use App\Models\TaiKhoan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HoSoTaiKhoanService
{
    public function suaHoSo(TaiKhoan $nguoiDung, array $duLieu): TaiKhoan
    {
        return DB::transaction(function () use ($nguoiDung, $duLieu) {
            $taiKhoan = TaiKhoan::query()->lockForUpdate()->findOrFail($nguoiDung->id);
            abort_unless($taiKhoan->trang_thai === TaiKhoan::HOAT_DONG, 403);
            $this->kiemTraPhienBan($taiKhoan, $duLieu['updated_at']);
            $taiKhoan->ho_ten = $duLieu['ho_ten'];
            $quanHe = match ($taiKhoan->vai_tro) {
                TaiKhoan::KHACH_HANG => 'hoSoKhachHang', TaiKhoan::HUAN_LUYEN_VIEN => 'hoSoHuanLuyenVien', default => null,
            };
            $doiHoSo = false;
            if ($quanHe) {
                $hoSo = $taiKhoan->$quanHe()->lockForUpdate()->firstOrFail();
                foreach (array_diff_key($duLieu, array_flip(['ho_ten', 'updated_at'])) as $truong => $giaTri) {
                    $hoSo->$truong = $giaTri;
                }
                $doiHoSo = $hoSo->isDirty();
                if ($doiHoSo) {
                    $hoSo->save();
                }
            }
            if ($taiKhoan->isDirty() || $doiHoSo) {
                $this->doiPhienBan($taiKhoan);
                $taiKhoan->save();
            }

            return $taiKhoan->refresh()->load(['hoSoKhachHang', 'hoSoHuanLuyenVien']);
        }, 3);
    }

    public function datTrangThai(TaiKhoan $admin, int $id, array $duLieu): TaiKhoan
    {
        return DB::transaction(function () use ($admin, $id, $duLieu) {
            // Thứ tự khóa ổn định, kiểm tra lại Admin để chặn hai người khóa chéo.
            $cacTaiKhoan = TaiKhoan::query()->whereIn('id', [$admin->id, $id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $nguoiThaoTac = $cacTaiKhoan->get($admin->id);
            abort_unless($nguoiThaoTac?->vai_tro === TaiKhoan::ADMIN && $nguoiThaoTac->trang_thai === TaiKhoan::HOAT_DONG, 403);
            $taiKhoan = $cacTaiKhoan->get($id);
            abort_unless($taiKhoan, 404);
            abort_if($id === $admin->id && $duLieu['trang_thai'] !== TaiKhoan::HOAT_DONG, 409, 'Bạn không thể tự khóa tài khoản đang đăng nhập.');
            $this->kiemTraPhienBan($taiKhoan, $duLieu['updated_at']);
            if ($taiKhoan->trang_thai !== $duLieu['trang_thai']) {
                $taiKhoan->trang_thai = $duLieu['trang_thai'];
                if ($taiKhoan->trang_thai === TaiKhoan::BI_KHOA) {
                    $this->thuHoiPhien($taiKhoan);
                    DB::table(config('auth.passwords.tai_khoan.table'))->where('email', $taiKhoan->email)->delete();
                }
                $this->doiPhienBan($taiKhoan);
                $taiKhoan->save();
            }

            return $taiKhoan->refresh();
        }, 3);
    }

    public function thuHoiPhien(TaiKhoan $taiKhoan): void
    {
        $taiKhoan->tokens()->delete();
        $taiKhoan->remember_token = Str::random(60);
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table'))->where('user_id', $taiKhoan->id)->delete();
        }
    }

    public function doiPhienBan(TaiKhoan $taiKhoan): void
    {
        $taiKhoan->updated_at = $taiKhoan->updated_at && now()->lessThanOrEqualTo($taiKhoan->updated_at)
            ? $taiKhoan->updated_at->copy()->addMicrosecond() : now();
    }

    private function kiemTraPhienBan(TaiKhoan $taiKhoan, ?string $phienBan): void
    {
        abort_if($taiKhoan->updated_at?->format('Y-m-d H:i:s.u') !== $phienBan, 409, 'Dữ liệu đã thay đổi. Hãy tải lại trước khi lưu.');
    }
}
