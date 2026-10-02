<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HoSoHuanLuyenVien extends Model
{
    public function taiKhoan(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'tai_khoan_id');
    }

    protected $table = 'ho_so_huan_luyen_vien';

    protected $fillable = ['tai_khoan_id'];
}
