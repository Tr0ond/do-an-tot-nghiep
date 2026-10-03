<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GhiChuHuanLuyen extends Model
{
    protected $table = 'ghi_chu_huan_luyen';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id'];

    public function pt(): BelongsTo
    {
        return $this->belongsTo(HoSoHuanLuyenVien::class, 'huan_luyen_vien_id');
    }
}
