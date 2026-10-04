<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiSoCoThe extends Model
{
    protected $table = 'chi_so_co_the';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = ['khach_hang_id', 'ngay_ghi', 'can_nang_kg', 'chieu_cao_cm', 'ghi_chu'];

    protected function casts(): array
    {
        return ['can_nang_kg' => 'decimal:2', 'chieu_cao_cm' => 'decimal:2'];
    }

    public function bmi(): ?float
    {
        $can = (float) $this->can_nang_kg;
        $cao = (float) $this->chieu_cao_cm;

        return $can >= 10 && $can <= 500 && $cao >= 50 && $cao <= 250 ? round($can / ($cao / 100) ** 2, 2) : null;
    }

    public function duLieu(): array
    {
        return ['id' => $this->id, 'ngay_ghi' => $this->ngay_ghi,
            'can_nang_kg' => $this->can_nang_kg !== null ? (float) $this->can_nang_kg : null,
            'chieu_cao_cm' => $this->chieu_cao_cm !== null ? (float) $this->chieu_cao_cm : null,
            'bmi' => $this->bmi(), 'ghi_chu' => $this->ghi_chu,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s.u')];
    }
}
