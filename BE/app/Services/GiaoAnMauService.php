<?php

namespace App\Services;

use App\Http\Requests\GhiGiaoAnMauRequest;
use App\Models\BaiTap;
use App\Models\BaiTapTrongGiaoAnMau;
use App\Models\GiaoAnMau;
use App\Models\NhomCo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class GiaoAnMauService
{
    public function taoGiaoAn(array $duLieu, int $nguoiTao): GiaoAnMau
    {
        $ma = strtolower($duLieu['client_request_id']);
        try {
            return DB::transaction(function () use ($duLieu, $nguoiTao, $ma) {
                if ($cu = GiaoAnMau::where('ma_yeu_cau_tao', $ma)->lockForUpdate()->first()) {
                    return $this->doiChieuYeuCau($cu, $duLieu, $nguoiTao);
                }
                $this->kiemTraBaiTap($duLieu['bai_tap']);
                $giaoAn = GiaoAnMau::create([...Arr::only($duLieu, ['ten_giao_an', 'muc_tieu', 'so_ngay_tap']), 'nguoi_tao_id' => $nguoiTao, 'trang_thai' => 'NHAP', 'ma_yeu_cau_tao' => $ma]);
                $giaoAn->cacBaiTap()->createMany($this->chuanHoaBaiTap($duLieu['bai_tap']));

                return $giaoAn;
            }, 3);
        } catch (UniqueConstraintViolationException $loi) {
            if (! str_contains($loi->getMessage(), 'uq_t12_01')) {
                throw $loi;
            }

            return DB::transaction(fn () => $this->doiChieuYeuCau(GiaoAnMau::where('ma_yeu_cau_tao', $ma)->lockForUpdate()->firstOrFail(), $duLieu, $nguoiTao), 3);
        }
    }

    public function suaGiaoAn(int $id, array $duLieu): GiaoAnMau
    {
        return DB::transaction(function () use ($id, $duLieu) {
            $giaoAn = GiaoAnMau::lockForUpdate()->findOrFail($id);
            $this->kiemTraPhienBan($giaoAn, $duLieu['updated_at']);
            $cacBaiCu = $this->chuanHoaBaiTap($giaoAn->cacBaiTap->toArray());
            $cacBaiMoi = $this->chuanHoaBaiTap($duLieu['bai_tap']);
            $this->kiemTraBaiTap($cacBaiMoi, $cacBaiCu);
            $giaoAn->fill(Arr::only($duLieu, ['ten_giao_an', 'muc_tieu', 'so_ngay_tap']));
            if ($giaoAn->isDirty() || $cacBaiCu !== $cacBaiMoi) {
                // T13 không được kế hoạch tham chiếu; thay dòng cùng transaction tránh xung đột UNIQUE khi đổi thứ tự.
                $giaoAn->cacBaiTap()->delete();
                $giaoAn->cacBaiTap()->createMany($cacBaiMoi);
                if ($giaoAn->trang_thai === 'DA_DUYET') {
                    $giaoAn->trang_thai = 'NHAP';
                }
                $giaoAn->nguoi_duyet_id = null;
                $giaoAn->duyet_luc = null;
                $this->luuPhienBan($giaoAn);
            }

            return $giaoAn->refresh();
        }, 3);
    }

    public function datTrangThai(int $id, array $duLieu, int $nguoiDuyet): GiaoAnMau
    {
        return DB::transaction(function () use ($id, $duLieu, $nguoiDuyet) {
            $giaoAn = GiaoAnMau::lockForUpdate()->findOrFail($id);
            $this->kiemTraPhienBan($giaoAn, $duLieu['updated_at']);
            if ($duLieu['trang_thai'] === 'DA_DUYET') {
                $cacBai = $giaoAn->cacBaiTap->toArray();
                $noiDung = [...Arr::only($giaoAn->toArray(), ['ten_giao_an', 'muc_tieu', 'so_ngay_tap']), 'bai_tap' => array_map(fn ($bai) => Arr::only($bai, BaiTapTrongGiaoAnMau::THUOC_TINH), $cacBai)];
                $kiemTra = Validator::make($noiDung, GhiGiaoAnMauRequest::quyTacNoiDung(), (new GhiGiaoAnMauRequest)->messages());
                $kiemTra->after(fn ($kiemTra) => GhiGiaoAnMauRequest::kiemTraThuTu($kiemTra, $noiDung))->validate();
                $this->kiemTraBaiTap($cacBai);
                $cacNgay = array_unique(array_column($cacBai, 'ngay_thu'));
                sort($cacNgay);
                if ($cacNgay !== range(1, $giaoAn->so_ngay_tap)) {
                    throw ValidationException::withMessages(['bai_tap' => 'Mỗi ngày tập cần ít nhất một bài trước khi duyệt.']);
                }
                if ($giaoAn->trang_thai !== 'DA_DUYET') {
                    $giaoAn->nguoi_duyet_id = $nguoiDuyet;
                    $giaoAn->duyet_luc = now();
                }
            }
            $giaoAn->trang_thai = $duLieu['trang_thai'];
            if ($giaoAn->isDirty()) {
                $this->luuPhienBan($giaoAn);
            }

            return $giaoAn->refresh();
        }, 3);
    }

    private function kiemTraBaiTap(array $cacBai, array $cacBaiCu = []): void
    {
        $soDongCu = array_count_values(array_column($cacBaiCu, 'bai_tap_id'));
        $cacId = array_unique(array_column($cacBai, 'bai_tap_id'));
        $baiTap = BaiTap::whereIn('id', $cacId)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $nhomCo = NhomCo::whereIn('id', $baiTap->pluck('nhom_co_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        foreach ($cacBai as $viTri => $dong) {
            $bai = $baiTap->get($dong['bai_tap_id']);
            if (! $bai) {
                throw ValidationException::withMessages(["bai_tap.$viTri.bai_tap_id" => 'Bài tập/nhóm cơ không còn hoạt động hoặc không tồn tại. Hãy chọn bài khác.']);
            }
            if ($bai->trang_thai !== 'HOAT_DONG' || $nhomCo->get($bai->nhom_co_id)?->trang_thai !== 'HOAT_DONG') {
                // Chỉ giữ số dòng cũ: không cho nhân thêm bài đã ngừng bằng cách gửi lại ID cũ.
                if (($soDongCu[$bai->id] ?? 0) < 1) {
                    throw ValidationException::withMessages(["bai_tap.$viTri.bai_tap_id" => 'Không được thêm bài tập/nhóm cơ đã ngừng sử dụng. Hãy chọn bài khác.']);
                }
                $soDongCu[$bai->id]--;
            }
        }
    }

    private function chuanHoaBaiTap(array $cacBai): array
    {
        $ketQua = array_map(function ($dong) {
            $duLieu = array_replace(array_fill_keys(BaiTapTrongGiaoAnMau::THUOC_TINH, null), Arr::only($dong, BaiTapTrongGiaoAnMau::THUOC_TINH));
            foreach (array_diff(BaiTapTrongGiaoAnMau::THUOC_TINH, ['ghi_chu']) as $truong) {
                $duLieu[$truong] = (int) $duLieu[$truong];
            }
            $duLieu['ghi_chu'] = $duLieu['ghi_chu'] === '' ? null : $duLieu['ghi_chu'];

            return $duLieu;
        }, $cacBai);
        usort($ketQua, fn ($a, $b) => [$a['ngay_thu'], $a['thu_tu']] <=> [$b['ngay_thu'], $b['thu_tu']]);

        return $ketQua;
    }

    private function doiChieuYeuCau(GiaoAnMau $giaoAn, array $duLieu, int $nguoiTao): GiaoAnMau
    {
        $hopLe = (int) $giaoAn->nguoi_tao_id === $nguoiTao;
        foreach (['ten_giao_an', 'muc_tieu', 'so_ngay_tap'] as $truong) {
            $hopLe = $hopLe && (string) $giaoAn->$truong === (string) $duLieu[$truong];
        }
        if (! $hopLe || $this->chuanHoaBaiTap($giaoAn->cacBaiTap->toArray()) !== $this->chuanHoaBaiTap($duLieu['bai_tap'])) {
            throw new ConflictHttpException('Mã yêu cầu đã tạo giáo án khác. Hãy kiểm tra danh sách trước khi tạo yêu cầu mới.');
        }

        return $giaoAn;
    }

    private function kiemTraPhienBan(GiaoAnMau $giaoAn, string $phienBan): void
    {
        if ($giaoAn->updated_at?->format('Y-m-d H:i:s.u') !== $phienBan) {
            throw new ConflictHttpException('Giáo án đã được thay đổi. Hãy tải lại bản mới trước khi lưu.');
        }
    }

    private function luuPhienBan(GiaoAnMau $giaoAn): void
    {
        $giaoAn->updated_at = $giaoAn->updated_at && now()->lessThanOrEqualTo($giaoAn->updated_at) ? $giaoAn->updated_at->copy()->addMicrosecond() : now();
        $giaoAn->save();
    }
}
