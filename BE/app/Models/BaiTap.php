<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaiTap extends Model
{
    protected $table = 'bai_tap';

    // Giữ phần mili/micro giây của thời điểm nguồn, khớp các cột DATETIME(6).
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'nhom_co_id', 'nguon_du_lieu', 'ma_nguon', 'ten_bai_tap', 'ten_tieng_viet',
        'bo_phan_co_the', 'dung_cu', 'dung_cu_nguon', 'huong_dan', 'cac_buoc', 'co_phu',
        'co_ho_tro_nguon', 'anh_url', 'gif_url', 'duong_dan_anh_nguon', 'duong_dan_gif_nguon',
        'ma_media_nguon', 'ghi_cong_media', 'nguon_tao_luc', 'nguon_cap_nhat_luc', 'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'huong_dan' => 'array', 'cac_buoc' => 'array', 'co_phu' => 'array',
            'nguon_tao_luc' => 'datetime', 'nguon_cap_nhat_luc' => 'datetime',
        ];
    }

    public function nhomCo(): BelongsTo
    {
        return $this->belongsTo(NhomCo::class, 'nhom_co_id');
    }

    public function scopeDangHienThi(Builder $truyVan): void
    {
        $truyVan->where('trang_thai', 'HOAT_DONG')
            ->whereHas('nhomCo', fn (Builder $nhom) => $nhom->where('trang_thai', 'HOAT_DONG'));
    }
}
