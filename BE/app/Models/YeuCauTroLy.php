<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YeuCauTroLy extends Model
{
    protected $table = 'yeu_cau_tro_ly';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['giu_luot_den' => 'immutable_datetime', 'hoan_thanh_luc' => 'immutable_datetime', 'dung_du_lieu_ca_nhan' => 'boolean'];
    }
}
