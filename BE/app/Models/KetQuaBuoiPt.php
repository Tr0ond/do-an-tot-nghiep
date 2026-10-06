<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KetQuaBuoiPt extends Model
{
    protected $table = 'ket_qua_buoi_pt';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['bai_tap' => 'array', 'chot_luc' => 'immutable_datetime'];
    }
}
