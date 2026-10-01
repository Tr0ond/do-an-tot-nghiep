<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TaiKhoan extends Authenticatable
{
    use Notifiable;

    public const KHACH_HANG = 'KHACH_HANG';

    public const HUAN_LUYEN_VIEN = 'HUAN_LUYEN_VIEN';

    public const ADMIN = 'ADMIN';

    public const HOAT_DONG = 'HOAT_DONG';

    public function hoSoKhachHang(): HasOne
    {
        return $this->hasOne(HoSoKhachHang::class, 'tai_khoan_id');
    }

    public function hoSoHuanLuyenVien(): HasOne
    {
        return $this->hasOne(HoSoHuanLuyenVien::class, 'tai_khoan_id');
    }

    protected $table = 'tai_khoan';

    // Vai trò và trạng thái sẽ được đặt qua nghiệp vụ được phân quyền, không mass assign.
    protected $fillable = ['ho_ten', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
