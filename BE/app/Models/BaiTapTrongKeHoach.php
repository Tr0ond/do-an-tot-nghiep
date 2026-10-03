<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaiTapTrongKeHoach extends Model
{
    protected $table = 'bai_tap_trong_ke_hoach';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['noi_dung_snapshot' => 'array', 'muc_ta_kg' => 'decimal:2'];
    }
}
