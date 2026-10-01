<?php

namespace App\Services;

use App\Models\NhomCo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class NhomCoService
{
    public function taoNhomCo(array $duLieu): NhomCo
    {
        try {
            return NhomCo::create([
                'ma_nhom_co' => $duLieu['ma_nhom_co'],
                'ten_nhom_co' => $duLieu['ten_nhom_co'],
                'ten_nguon' => $duLieu['ten_nhom_co'],
                'trang_thai' => 'HOAT_DONG',
            ]);
        } catch (UniqueConstraintViolationException $loi) {
            if (str_contains($loi->getMessage(), 'uq_t10_01')) {
                throw ValidationException::withMessages(['ma_nhom_co' => 'Mã nhóm cơ đã được sử dụng. Hãy kiểm tra danh sách hoặc chọn mã khác.']);
            }
            throw $loi;
        }
    }

    public function suaNhomCo(int $id, array $duLieu): NhomCo
    {
        return $this->capNhat($id, $duLieu, 'ten_nhom_co');
    }

    public function datTrangThai(int $id, array $duLieu): NhomCo
    {
        return $this->capNhat($id, $duLieu, 'trang_thai');
    }

    private function capNhat(int $id, array $duLieu, string $truong): NhomCo
    {
        return DB::transaction(function () use ($id, $duLieu, $truong) {
            $nhom = NhomCo::lockForUpdate()->findOrFail($id);
            if ($nhom->updated_at?->format('Y-m-d H:i:s.u') !== $duLieu['updated_at']) {
                throw new ConflictHttpException('Nhóm cơ đã được thay đổi. Hãy tải lại bản mới trước khi lưu.');
            }
            $nhom->$truong = $duLieu[$truong];
            if ($nhom->isDirty()) {
                $nhom->updated_at = $nhom->updated_at && now()->lessThanOrEqualTo($nhom->updated_at) ? $nhom->updated_at->copy()->addMicrosecond() : now();
                $nhom->save();
            }

            return $nhom->refresh();
        }, 3);
    }
}
