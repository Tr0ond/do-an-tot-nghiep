<?php

namespace App\Services;

use App\Events\LichCanDongBo;
use App\Models\HoSoHuanLuyenVien;
use App\Models\HoSoKhachHang;
use App\Models\PhanCongHuanLuyenVien;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LichRealtimeService
{
    public function choKhach(int $khachId): void
    {
        $pt = PhanCongHuanLuyenVien::where('khach_hang_id', $khachId)->whereNull('ket_thuc_luc')->where('bat_dau_luc', '<=', now())->value('huan_luyen_vien_id');
        if ($pt) {
            $this->choPt($pt, $khachId);

            return;
        }
        $id = HoSoKhachHang::whereKey($khachId)->value('tai_khoan_id');
        if ($id) {
            $this->phat([$id]);
        }
    }

    public function choPt(int $ptId, ?int $khachId = null): void
    {
        $khachIds = PhanCongHuanLuyenVien::where('huan_luyen_vien_id', $ptId)->whereNull('ket_thuc_luc')->where('bat_dau_luc', '<=', now())->pluck('khach_hang_id')->all();
        if ($khachId) {
            $khachIds[] = $khachId;
        }
        $ids = HoSoKhachHang::whereIn('id', $khachIds)->pluck('tai_khoan_id')->all();
        $pt = HoSoHuanLuyenVien::whereKey($ptId)->value('tai_khoan_id');
        if ($pt) {
            $ids[] = $pt;
        }
        $this->phat($ids);
    }

    private function phat(array $ids): void
    {
        DB::afterCommit(function () use ($ids) {
            try {
                event(new LichCanDongBo($ids));
            } catch (\Throwable) {
                Log::warning('Lịch: chưa phát được tín hiệu đồng bộ realtime.');
            }
        });
    }
}
