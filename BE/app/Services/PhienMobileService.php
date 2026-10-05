<?php

namespace App\Services;

use App\Models\TaiKhoan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class PhienMobileService
{
    public function dangNhap(array $duLieu): NewAccessToken
    {
        return DB::transaction(function () use ($duLieu) {
            // Cùng khóa với khóa/reset mật khẩu: không cấp phiên từ mật khẩu hoặc trạng thái cũ.
            $id = TaiKhoan::query()->where('email', $duLieu['email'])->value('id');
            // Khóa theo khóa chính như luồng Admin; tránh đảo thứ tự khóa index email/ID.
            $taiKhoan = $id ? TaiKhoan::query()->lockForUpdate()->find($id) : null;
            if (! $taiKhoan || $taiKhoan->email !== $duLieu['email'] || ! Hash::check($duLieu['password'], $taiKhoan->password)
                || $taiKhoan->trang_thai !== TaiKhoan::HOAT_DONG
                || ! in_array($taiKhoan->vai_tro, [TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN], true)) {
                throw ValidationException::withMessages(['email' => ['Thông tin đăng nhập không đúng hoặc tài khoản không được phép dùng app.']]);
            }
            $token = $taiKhoan->createToken($duLieu['ten_thiet_bi'], ['mobile'], now()->addDays(30));
            $token->accessToken->forceFill(['dau_phien_dang_nhap' => $taiKhoan->dauPhienDangNhap()])->save();

            return $token;
        }, 3);
    }
}
