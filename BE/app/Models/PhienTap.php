<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhienTap extends Model
{
    protected $table = 'phien_tap';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['bat_dau_luc' => 'immutable_datetime', 'hoan_thanh_luc' => 'immutable_datetime'];
    }

    public function cacBaiTap(): HasMany
    {
        return $this->hasMany(BaiTapTrongPhien::class, 'phien_tap_id')->orderBy('thu_tu');
    }

    public function nhanXet(): HasMany
    {
        return $this->hasMany(GhiChuHuanLuyen::class, 'phien_tap_id')->orderBy('id');
    }
}
