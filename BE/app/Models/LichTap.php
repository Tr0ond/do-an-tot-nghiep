<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LichTap extends Model
{
    protected $table = 'lich_tap';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id'];

    public function keHoach(): BelongsTo
    {
        return $this->belongsTo(KeHoachTap::class, 'ke_hoach_tap_id');
    }

    public function phien(): HasOne
    {
        return $this->hasOne(PhienTap::class, 'lich_tap_id');
    }
}
