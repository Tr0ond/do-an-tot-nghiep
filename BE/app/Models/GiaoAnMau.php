<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiaoAnMau extends Model
{
    protected $table = 'giao_an_mau';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id'];

    protected $hidden = ['ma_yeu_cau_tao'];

    protected function casts(): array
    {
        return ['so_ngay_tap' => 'integer', 'duyet_luc' => 'datetime'];
    }

    public function cacBaiTap(): HasMany
    {
        return $this->hasMany(BaiTapTrongGiaoAnMau::class, 'giao_an_mau_id')->orderBy('ngay_thu')->orderBy('thu_tu');
    }
}
