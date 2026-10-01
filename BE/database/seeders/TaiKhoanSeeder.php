<?php

namespace Database\Seeders;

use App\Models\TaiKhoan;
use App\Services\TaiKhoanService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TaiKhoanSeeder extends Seeder
{
    public function run(TaiKhoanService $dichVu): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Seeder tài khoản demo chỉ chạy trong môi trường local hoặc testing.');
        }

        $danhSach = [
            ['ho_ten' => 'Admin demo', 'email' => 'admin@example.test', 'vai_tro' => TaiKhoan::ADMIN],
            ['ho_ten' => 'Huấn luyện viên demo', 'email' => 'pt@example.test', 'vai_tro' => TaiKhoan::HUAN_LUYEN_VIEN],
            ['ho_ten' => 'Khách hàng demo', 'email' => 'khachhang@example.test', 'vai_tro' => TaiKhoan::KHACH_HANG],
        ];

        DB::transaction(function () use ($danhSach, $dichVu) {
            foreach ($danhSach as $duLieu) {
                $taiKhoan = TaiKhoan::where('email', $duLieu['email'])->first();
                if ($taiKhoan === null) {
                    $dichVu->taoTaiKhoan([
                        'ho_ten' => $duLieu['ho_ten'],
                        'email' => $duLieu['email'],
                        'password' => 'Demo123456!',
                    ], $duLieu['vai_tro']);

                    continue;
                }

                // Không nâng quyền hoặc đặt lại mật khẩu của tài khoản trùng email.
                if ($taiKhoan->vai_tro !== $duLieu['vai_tro']) {
                    throw new RuntimeException('Email '.$duLieu['email'].' đã tồn tại với vai trò khác. Seeder đã rollback.');
                }

                if ($taiKhoan->vai_tro === TaiKhoan::KHACH_HANG) {
                    $taiKhoan->hoSoKhachHang()->firstOrCreate([]);
                } elseif ($taiKhoan->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN) {
                    $taiKhoan->hoSoHuanLuyenVien()->firstOrCreate([]);
                }
            }
        });
    }
}
