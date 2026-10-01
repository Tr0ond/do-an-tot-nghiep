<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HoSoKhachHang extends Model
{
    protected $table = 'ho_so_khach_hang';

    protected $fillable = ['tai_khoan_id'];

    protected function casts(): array
    {
        return ['thoi_gian_co_the_tap' => 'array', 'ngay_sinh' => 'date:Y-m-d'];
    }

    public function taiKhoan(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'tai_khoan_id');
    }
}
