<?php

namespace App\Services;

use App\Models\TaiKhoan;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class KhoiPhucMatKhauService
{
    public function guiLienKet(string $email): void
    {
        $mailer = config('mail.default');
        // Không ghi liên kết bí mật ra log hoặc giả vờ đã gửi qua mailer không chuyển thư.
        abort_if(in_array($mailer, ['log', 'array'], true) && ! app()->environment('testing'), 503);
        try {
            Password::broker('tai_khoan')->sendResetLink(['email' => $email, 'trang_thai' => TaiKhoan::HOAT_DONG]);
        } catch (TransportExceptionInterface) {
            // Lỗi SMTP không được phản chiếu credentials hoặc nội dung email ra response/log.
            abort(503);
        }
    }

    public function datLai(array $duLieu): void
    {
        DB::transaction(function () use ($duLieu) {
            $taiKhoan = TaiKhoan::query()->where('email', $duLieu['email'])->lockForUpdate()->first();
            $baoLoi = fn () => throw ValidationException::withMessages(['token' => ['Liên kết không hợp lệ, đã hết hạn hoặc đã được sử dụng. Hãy yêu cầu liên kết mới.']]);
            if (! $taiKhoan || $taiKhoan->trang_thai !== TaiKhoan::HOAT_DONG) {
                $baoLoi();
            }
            // Khóa token trong cùng transaction để hai request không tiêu thụ cùng token.
            DB::table(config('auth.passwords.tai_khoan.table'))->where('email', $taiKhoan->email)->lockForUpdate()->first();
            $ketQua = Password::broker('tai_khoan')->reset([...$duLieu, 'trang_thai' => TaiKhoan::HOAT_DONG], function (TaiKhoan $nguoiDung, string $matKhau) use ($taiKhoan) {
                $taiKhoan->password = $matKhau;
                $dichVu = app(HoSoTaiKhoanService::class);
                $dichVu->thuHoiPhien($taiKhoan);
                $dichVu->doiPhienBan($taiKhoan);
                $taiKhoan->save();
                event(new PasswordReset($taiKhoan));
            });
            if ($ketQua !== Password::PASSWORD_RESET) {
                $baoLoi();
            }
        }, 3);
    }
}
