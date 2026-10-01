<?php

namespace App\Console\Commands;

use App\Http\Requests\DangKyRequest;
use App\Models\TaiKhoan;
use App\Services\TaiKhoanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TaoAdminDauTien extends Command
{
    protected $signature = 'tai-khoan:tao-admin {email}';

    protected $description = 'Tạo Admin đầu tiên; nhập mật khẩu ẩn, không dùng tài khoản mặc định';

    public function handle(TaiKhoanService $dichVu): int
    {
        if (TaiKhoan::where('vai_tro', TaiKhoan::ADMIN)->exists()) {
            $this->error('Đã có Admin. Hãy đăng nhập Admin để tạo tài khoản tiếp theo.');

            return self::FAILURE;
        }

        $duLieu = [
            'ho_ten' => $this->ask('Họ tên Admin'),
            'email' => Str::lower(trim($this->argument('email'))),
            'password' => $this->secret('Mật khẩu (8–72 ký tự)'),
            'password_confirmation' => $this->secret('Nhập lại mật khẩu'),
        ];
        $quyTac = new DangKyRequest;
        $kiemTra = Validator::make($duLieu, $quyTac->rules(), $quyTac->messages(), $quyTac->attributes());
        if ($kiemTra->fails()) {
            $this->error('Thông tin chưa hợp lệ. Kiểm tra họ tên/email và mật khẩu nhập lại.');

            return self::FAILURE;
        }

        $dichVu->taoTaiKhoan($kiemTra->validated(), TaiKhoan::ADMIN);
        $this->info('Đã tạo Admin đầu tiên. Bạn có thể đăng nhập trên Frontend.');

        return self::SUCCESS;
    }
}
