<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GoiTap extends Model
{
    public const THUOC_TINH = ['ten_goi', 'gia', 'co_chatbot', 'so_luot_chatbot_moi_ngay', 'so_buoi_pt', 'thoi_han_ngay', 'trang_thai'];

    protected $table = 'goi_tap';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [...self::THUOC_TINH, 'ma_yeu_cau_tao'];

    protected $hidden = ['ma_yeu_cau_tao'];

    protected function casts(): array
    {
        return ['gia' => 'integer', 'co_chatbot' => 'boolean', 'so_luot_chatbot_moi_ngay' => 'integer', 'so_buoi_pt' => 'integer', 'thoi_han_ngay' => 'integer'];
    }

    public function scopeDangHienThi(Builder $truyVan): void
    {
        $truyVan->where('trang_thai', 'HOAT_DONG')->where('co_chatbot', true)->where('so_luot_chatbot_moi_ngay', '>', 0)->where('thoi_han_ngay', '>', 0)->where('gia', '>', 0);
    }
}
