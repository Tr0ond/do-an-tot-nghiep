<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DangKyGoiTap extends Model
{
    protected $table = 'dang_ky_goi_tap';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id', 'khach_dang_dung_id', 'khach_dang_cho_id'];

    protected function casts(): array
    {
        return ['gia_snapshot' => 'integer', 'co_chatbot_snapshot' => 'boolean', 'so_buoi_con_lai' => 'integer', 'han_thanh_toan' => 'immutable_datetime', 'kich_hoat_luc' => 'immutable_datetime', 'het_han_luc' => 'immutable_datetime'];
    }

    public function thanhToan()
    {
        return $this->hasMany(ThanhToan::class, 'dang_ky_goi_tap_id');
    }

    public function khachHang()
    {
        return $this->belongsTo(HoSoKhachHang::class, 'khach_hang_id');
    }
}
