<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LichHenHuanLuyen extends Model
{
    protected $table = 'lich_hen_huan_luyen';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = ['id', 'khung_gio_dang_giu_id'];

    protected function casts(): array
    {
        return array_fill_keys(['bat_dau_luc', 'ket_thuc_luc', 'han_xac_nhan_dat_lich', 'han_xac_nhan_hoan_thanh', 'xac_nhan_luc', 'tieu_hao_luc', 'huy_luc', 'dong_xu_ly_luc', 'ghi_nhan_luc'], 'immutable_datetime');
    }

    public function trangThaiHieuLuc(): string
    {
        if ($this->trang_thai === 'CHO_XAC_NHAN' && $this->han_xac_nhan_dat_lich?->lessThanOrEqualTo(now())) {
            return 'HET_HAN';
        }
        if ($this->trang_thai === 'DA_XAC_NHAN' && $this->ket_thuc_luc->addHours(24)->lessThanOrEqualTo(now())) {
            return 'QUA_HAN_XAC_NHAN';
        }

        return $this->trang_thai;
    }

    public function khach()
    {
        return $this->belongsTo(HoSoKhachHang::class, 'khach_hang_id');
    }

    public function pt()
    {
        return $this->belongsTo(HoSoHuanLuyenVien::class, 'huan_luyen_vien_id');
    }
}
