<?php

namespace App\Services;

use App\Http\Requests\GhiGoiTapRequest;
use App\Models\GoiTap;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class GoiTapService
{
    public function taoGoiTap(array $duLieu): GoiTap
    {
        $maYeuCau = strtolower($duLieu['client_request_id']);
        try {
            return DB::transaction(function () use ($duLieu, $maYeuCau) {
                $goiCu = GoiTap::where('ma_yeu_cau_tao', $maYeuCau)->first();
                if ($goiCu) {
                    return $this->doiChieuYeuCau($goiCu, $duLieu);
                }

                return GoiTap::create([...Arr::only($duLieu, GoiTap::THUOC_TINH), 'ma_yeu_cau_tao' => $maYeuCau]);
            });
        } catch (UniqueConstraintViolationException $loi) {
            // Dòng đã commit bởi request cùng UUID trong lúc request này đang chạy.
            if (! str_contains($loi->getMessage(), 'uq_t04_01')) {
                throw $loi;
            }

            return $this->doiChieuYeuCau(GoiTap::where('ma_yeu_cau_tao', $maYeuCau)->firstOrFail(), $duLieu);
        }
    }

    public function suaGoiTap(int $id, array $duLieu): GoiTap
    {
        return DB::transaction(function () use ($id, $duLieu) {
            $goi = GoiTap::lockForUpdate()->findOrFail($id);
            $this->kiemTraPhienBan($goi, $duLieu['updated_at']);
            $goi->fill(Arr::only($duLieu, array_diff(GoiTap::THUOC_TINH, ['trang_thai'])));

            return $this->luuThayDoi($goi);
        });
    }

    public function datTrangThai(int $id, array $duLieu): GoiTap
    {
        return DB::transaction(function () use ($id, $duLieu) {
            $goi = GoiTap::lockForUpdate()->findOrFail($id);
            $this->kiemTraPhienBan($goi, $duLieu['updated_at']);
            if ($duLieu['trang_thai'] === 'HOAT_DONG') {
                Validator::make($goi->toArray(), GhiGoiTapRequest::quyTacQuyenLoi())->validate();
            }
            $goi->trang_thai = $duLieu['trang_thai'];

            return $this->luuThayDoi($goi);
        });
    }

    private function luuThayDoi(GoiTap $goi): GoiTap
    {
        if ($goi->isDirty()) {
            // Phiên bản phải tiến lên kể cả khi đồng hồ hoặc hai lần ghi cùng micro giây.
            $goi->updated_at = $goi->updated_at && now()->lessThanOrEqualTo($goi->updated_at) ? $goi->updated_at->copy()->addMicrosecond() : now();
            $goi->save();
        }

        return $goi->refresh();
    }

    private function kiemTraPhienBan(GoiTap $goi, string $phienBan): void
    {
        if ($goi->updated_at?->format('Y-m-d H:i:s.u') !== $phienBan) {
            throw new ConflictHttpException('Gói tập đã được thay đổi. Hãy tải lại bản mới trước khi lưu.');
        }
    }

    private function doiChieuYeuCau(GoiTap $goi, array $duLieu): GoiTap
    {
        foreach (GoiTap::THUOC_TINH as $truong) {
            if ((string) $goi->$truong !== (string) $duLieu[$truong]) {
                throw new ConflictHttpException('Mã yêu cầu đã tạo một gói khác. Hãy kiểm tra danh sách trước khi tạo yêu cầu mới.');
            }
        }

        return $goi;
    }
}
