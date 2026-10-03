<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HiepTap extends Model
{
    protected $table = 'hiep_tap';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['khoi_luong_kg' => 'decimal:2'];
    }
}
