<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TinNhanTroLy extends Model
{
    protected $table = 'tin_nhan_tro_ly';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['nguon_da_kiem_tra' => 'array'];
    }
}
