<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhanCongHuanLuyenVien extends Model
{
    protected $table = 'phan_cong_huan_luyen_vien';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id', 'khach_dang_phan_cong_id'];

    protected function casts(): array
    {
        return ['bat_dau_luc' => 'immutable_datetime', 'ket_thuc_luc' => 'immutable_datetime'];
    }

    public function pt()
    {
        return $this->belongsTo(HoSoHuanLuyenVien::class, 'huan_luyen_vien_id');
    }
}
