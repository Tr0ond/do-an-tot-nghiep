<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaiLieuTuVan extends Model
{
    protected $table = 'tai_lieu_tu_van';

    protected $guarded = ['id'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $hidden = ['payload_hash', 'client_request_id'];

    protected function casts(): array
    {
        return ['phien_ban' => 'integer', 'xuat_ban_luc' => 'datetime'];
    }
}
