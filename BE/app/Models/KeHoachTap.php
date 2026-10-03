<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KeHoachTap extends Model
{
    protected $table = 'ke_hoach_tap';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id', 'khach_dang_ap_dung_id'];

    protected function casts(): array
    {
        return ['gui_luc' => 'immutable_datetime', 'han_duyet' => 'immutable_datetime', 'duyet_luc' => 'immutable_datetime', 'khach_an_luc' => 'immutable_datetime'];
    }

    public function cacBaiTap(): HasMany
    {
        return $this->hasMany(BaiTapTrongKeHoach::class, 'ke_hoach_tap_id')->orderBy('ngay_thu')->orderBy('thu_tu');
    }

    public function phanCongHienTai(): HasOne
    {
        return $this->hasOne(PhanCongHuanLuyenVien::class, 'khach_hang_id', 'khach_hang_id')->whereNull('ket_thuc_luc');
    }
}
