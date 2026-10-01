<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NhomCo extends Model
{
    protected $table = 'nhom_co';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = ['ma_nhom_co', 'ten_nhom_co', 'ten_nguon', 'trang_thai'];

    public function baiTap(): HasMany
    {
        return $this->hasMany(BaiTap::class, 'nhom_co_id');
    }
}
