<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThanhToan extends Model
{
    protected $table = 'thanh_toan';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['so_tien' => 'integer', 'so_tien_hoan' => 'integer', 'thanh_toan_luc' => 'immutable_datetime', 'xac_minh_luc' => 'immutable_datetime', 'doi_soat_luc' => 'immutable_datetime', 'hoan_tien_luc' => 'immutable_datetime'];
    }
}
