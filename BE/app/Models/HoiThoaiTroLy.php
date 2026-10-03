<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoiThoaiTroLy extends Model
{
    protected $table = 'hoi_thoai_tro_ly';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id'];
}
