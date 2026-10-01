<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaiTapTrongGiaoAnMau extends Model
{
    public const THUOC_TINH = ['bai_tap_id', 'ngay_thu', 'thu_tu', 'so_hiep', 'so_lan_lap', 'nghi_giay', 'ghi_chu'];

    protected $table = 'bai_tap_trong_giao_an_mau';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = self::THUOC_TINH;

    public function baiTap(): BelongsTo
    {
        return $this->belongsTo(BaiTap::class, 'bai_tap_id');
    }
}
