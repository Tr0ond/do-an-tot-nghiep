<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BaiTapTrongPhien extends Model
{
    protected $table = 'bai_tap_trong_phien';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['noi_dung_snapshot' => 'array'];
    }

    public function cacHiep(): HasMany
    {
        return $this->hasMany(HiepTap::class, 'bai_tap_trong_phien_id')->orderBy('thu_tu');
    }
}
