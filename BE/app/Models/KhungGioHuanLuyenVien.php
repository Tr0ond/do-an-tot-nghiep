<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KhungGioHuanLuyenVien extends Model
{
    protected $table = 'khung_gio_huan_luyen_vien';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['bat_dau_luc' => 'immutable_datetime', 'ket_thuc_luc' => 'immutable_datetime'];
    }
}
