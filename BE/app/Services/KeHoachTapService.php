<?php

namespace App\Services;

use App\Http\Requests\GhiGiaoAnMauRequest;
use App\Http\Requests\KeHoachTapRequest;
use App\Models\BaiTap;
use App\Models\GiaoAnMau;
use App\Models\HoSoKhachHang;
use App\Models\KeHoachTap;
use App\Models\NhomCo;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class KeHoachTapService
{
    public const THUOC_TINH_BAI = ['bai_tap_id', 'ngay_thu', 'thu_tu', 'so_hiep', 'so_lan_lap', 'nghi_giay', 'ghi_chu', 'muc_ta_kg'];

    public function phamVi(TaiKhoan $nguoi): Builder
    {
        $q = KeHoachTap::query();
        if ($nguoi->vai_tro === TaiKhoan::KHACH_HANG) {
            return $q->where('khach_hang_id', $nguoi->hoSoKhachHang->id)->where(fn ($q) => $q->where('nguon_tao', 'KHACH_HANG')->orWhere(fn ($q) => $q->where('trang_thai', '!=', 'NHAP')->whereNotNull('gui_luc')));
        }
        abort_unless($nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN, 403);
        $ptId = $nguoi->hoSoHuanLuyenVien->id;

        return $q->whereIn('khach_hang_id', PhanCongHuanLuyenVien::select('khach_hang_id')->where('huan_luyen_vien_id', $ptId)->whereNull('ket_thuc_luc'))
            ->where(fn ($q) => $q->where('nguon_tao', 'KHACH_HANG')->orWhereNotNull('gui_luc')->orWhere('huan_luyen_vien_id', $ptId));
    }

    public function phanCong(TaiKhoan $nguoi, int $khachId): PhanCongHuanLuyenVien
    {
        abort_unless($nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN, 403);

        return PhanCongHuanLuyenVien::where('khach_hang_id', $khachId)->where('huan_luyen_vien_id', $nguoi->hoSoHuanLuyenVien->id)->whereNull('ket_thuc_luc')->lockForUpdate()->firstOrFail();
    }

    public function doc(TaiKhoan $nguoi, int $id): KeHoachTap
    {
        return DB::transaction(function () use ($nguoi, $id) {
            $khachId = $this->phamVi($nguoi)->findOrFail($id)->khach_hang_id;
            HoSoKhachHang::lockForUpdate()->findOrFail($khachId);
            // Đọc khóa sau KH để thấy việc đổi PT vừa commit, kể cả isolation REPEATABLE READ.
            if ($nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN) {
                $this->phanCong($nguoi, $khachId);
            }
            $keHoach = $this->phamVi($nguoi)->lockForUpdate()->findOrFail($id);

            return $keHoach->load('cacBaiTap');
        }, 3);
    }

    public function tao(TaiKhoan $nguoi, int $khachId, array $duLieu): KeHoachTap
    {
        $ma = strtolower($duLieu['client_request_id']);
        $hash = hash('sha256', json_encode($this->chuanHoa($duLieu), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $xuLy = function () use ($nguoi, $khachId, $duLieu, $ma, $hash) {
            HoSoKhachHang::lockForUpdate()->findOrFail($khachId);
            $tuTao = $nguoi->vai_tro === TaiKhoan::KHACH_HANG;
            abort_unless(! $tuTao || (int) $nguoi->hoSoKhachHang->id === $khachId, 404);
            $pc = $tuTao ? null : $this->phanCong($nguoi, $khachId);
            $nguon = $tuTao ? 'KHACH_HANG' : 'PT';
            if ($cu = KeHoachTap::where('ma_yeu_cau_tao', $ma)->lockForUpdate()->first()) {
                if ($cu->nguon_tao !== $nguon || (int) $cu->khach_hang_id !== $khachId || (! $tuTao && (int) $cu->phan_cong_id !== $pc->id) || $cu->hash_yeu_cau_tao !== $hash) {
                    throw new ConflictHttpException('Mã yêu cầu đã được dùng. Hãy kiểm tra danh sách trước khi tạo mới.');
                }

                return $cu->load('cacBaiTap');
            }
            $this->kiemTraMau($duLieu['giao_an_mau_id'], $tuTao);
            $bai = $this->taoSnapshot($duLieu['bai_tap']);
            $keHoach = KeHoachTap::create([
                ...Arr::except($this->chuanHoa($duLieu), ['bai_tap']),
                'khach_hang_id' => $khachId, 'huan_luyen_vien_id' => $pc?->huan_luyen_vien_id,
                'phan_cong_id' => $pc?->id, 'nguon_tao' => $nguon,
                'thay_the_ke_hoach_id' => $this->dangApDung($khachId)?->id,
                'trang_thai' => 'NHAP', 'ma_yeu_cau_tao' => $ma, 'hash_yeu_cau_tao' => $hash,
            ]);
            $keHoach->cacBaiTap()->createMany($bai);

            return $keHoach->load('cacBaiTap');
        };
        try {
            return DB::transaction($xuLy, 3);
        } catch (UniqueConstraintViolationException $loi) {
            if (! str_contains($loi->getMessage(), 'uq_t14_03')) {
                throw $loi;
            }

            return DB::transaction($xuLy, 3);
        }
    }

    public function sua(TaiKhoan $nguoi, int $id, array $duLieu): KeHoachTap
    {
        return DB::transaction(function () use ($nguoi, $id, $duLieu) {
            $keHoach = $this->khoaThaoTac($nguoi, $id);
            $this->kiemTraPhienBan($keHoach, $duLieu['updated_at']);
            if ($keHoach->trang_thai !== 'NHAP') {
                throw new ConflictHttpException('Chỉ được sửa bản nháp. Hãy tạo giáo án mới để thay thế.');
            }
            if ($keHoach->giao_an_mau_id != $duLieu['giao_an_mau_id']) {
                $this->kiemTraMau($duLieu['giao_an_mau_id'], $keHoach->nguon_tao === 'KHACH_HANG');
            }
            $cacBai = $this->taoSnapshot($duLieu['bai_tap']);
            $keHoach->fill(Arr::except($this->chuanHoa($duLieu), ['bai_tap']));
            // Chỉ nháp được thay dòng: các bản đã gửi có thể được nhật ký tham chiếu.
            $keHoach->cacBaiTap()->delete();
            $keHoach->cacBaiTap()->createMany($cacBai);
            $this->luuPhienBan($keHoach);

            return $keHoach->refresh()->load('cacBaiTap');
        }, 3);
    }

    public function thaoTac(TaiKhoan $nguoi, int $id, string $hanhDong, string $phienBan): KeHoachTap
    {
        return DB::transaction(function () use ($nguoi, $id, $hanhDong, $phienBan) {
            $keHoach = $this->khoaThaoTac($nguoi, $id, $hanhDong);
            if ($nguoi->vai_tro === TaiKhoan::KHACH_HANG && $hanhDong === 'luu-tru') {
                return $this->ngungApDung($keHoach, $phienBan);
            }
            if ($keHoach->nguon_tao === 'KHACH_HANG') {
                return $this->thaoTacTuTao($keHoach, $hanhDong, $phienBan);
            }
            if ($nguoi->vai_tro === TaiKhoan::KHACH_HANG && $hanhDong === 'ap-dung') {
                return $this->apDungLaiPt($keHoach, $phienBan);
            }
            abort_unless(in_array($hanhDong, ['gui', 'xac-nhan', 'huy'], true), 404);
            abort_unless($nguoi->vai_tro === TaiKhoan::KHACH_HANG ? $hanhDong === 'xac-nhan' : $hanhDong !== 'xac-nhan', 404);
            if ($hanhDong === 'gui' && $keHoach->gui_luc !== null) {
                return $keHoach->load('cacBaiTap');
            }
            if ($hanhDong === 'xac-nhan' && $keHoach->duyet_luc !== null) {
                return $keHoach->load('cacBaiTap');
            }
            if ($hanhDong === 'huy' && $keHoach->trang_thai === 'DA_HUY') {
                return $keHoach->load('cacBaiTap');
            }
            $this->kiemTraPhienBan($keHoach, $phienBan);
            if ($hanhDong === 'gui') {
                if ($keHoach->trang_thai !== 'NHAP') {
                    throw new ConflictHttpException('Giáo án không còn là bản nháp.');
                }
                $this->kiemTraBanThayThe($keHoach);
                $duLieu = [...$this->chuanHoa($keHoach->toArray()), 'updated_at' => $phienBan, 'bai_tap' => $this->chuanHoaBai($keHoach->cacBaiTap->toArray())];
                $request = new KeHoachTapRequest;
                $request->setMethod('PUT');
                $v = Validator::make($duLieu, $request->rules(), $request->messages());
                $v->after(fn ($v) => GhiGiaoAnMauRequest::kiemTraThuTu($v, $duLieu))->validate();
                $ngay = array_unique(array_column($duLieu['bai_tap'], 'ngay_thu'));
                sort($ngay);
                if ($ngay !== range(1, $keHoach->so_ngay_tap)) {
                    throw ValidationException::withMessages(['bai_tap' => 'Mỗi ngày cần ít nhất một bài trước khi gửi.']);
                }
                $snapshot = $this->taoSnapshot($duLieu['bai_tap']);
                foreach ($keHoach->cacBaiTap as $viTri => $dong) {
                    $dong->update($snapshot[$viTri]);
                }
                $keHoach->trang_thai = 'CHO_DUYET';
                $keHoach->gui_luc = now();
                $keHoach->han_duyet = now()->addHours(24);
                $taiKhoanId = HoSoKhachHang::findOrFail($keHoach->khach_hang_id)->tai_khoan_id;
                $this->thongBao($keHoach, $taiKhoanId, 'gui', 'Có giáo án mới', 'PT đã gửi giáo án. Bạn có 24 giờ để xem và xác nhận.', '/khach-hang/ke-hoach/'.$id);
            } elseif ($hanhDong === 'xac-nhan') {
                $taiKhoanPtId = DB::table('ho_so_huan_luyen_vien')->where('id', $keHoach->huan_luyen_vien_id)->value('tai_khoan_id');
                if (TaiKhoan::whereKey($taiKhoanPtId)->lockForUpdate()->firstOrFail()->trang_thai !== TaiKhoan::HOAT_DONG) {
                    throw new ConflictHttpException('PT hiện không hoạt động. Hãy liên hệ Admin trước khi xác nhận giáo án.');
                }
                if ($keHoach->trang_thai !== 'CHO_DUYET' || ! $keHoach->han_duyet || $keHoach->han_duyet->lessThanOrEqualTo(now())) {
                    throw new ConflictHttpException('Giáo án đã quá hạn hoặc không còn chờ xác nhận. Hãy trao đổi với PT.');
                }
                $cu = $this->kiemTraBanThayThe($keHoach);
                if ($cu) {
                    $cu->trang_thai = 'LUU_TRU';
                    $this->luuPhienBan($cu);
                }
                $keHoach->trang_thai = 'DANG_AP_DUNG';
                $keHoach->duyet_luc = now();
                KeHoachTap::where('khach_hang_id', $keHoach->khach_hang_id)->where('nguon_tao', 'PT')->where('trang_thai', 'CHO_DUYET')->where('id', '!=', $id)->update(['trang_thai' => 'DA_HUY', 'updated_at' => now()]);
                $this->thongBao($keHoach, $taiKhoanPtId, 'xac-nhan', 'Giáo án đã được xác nhận', 'Học viên đã xác nhận giáo án và bắt đầu áp dụng.', '/pt/ke-hoach/'.$id);
            } else {
                if (! in_array($keHoach->trang_thai, ['NHAP', 'CHO_DUYET'], true)) {
                    throw new ConflictHttpException('Chỉ được hủy bản nháp hoặc bản đang chờ xác nhận.');
                }
                $keHoach->trang_thai = 'DA_HUY';
            }
            $this->luuPhienBan($keHoach);

            return $keHoach->refresh()->load('cacBaiTap');
        }, 3);
    }

    private function apDungLaiPt(KeHoachTap $k, string $phienBan): KeHoachTap
    {
        // Chỉ chọn lại snapshot đã được KH xác nhận; không bỏ qua duyệt đề xuất lần đầu.
        if ($k->gui_luc === null || $k->duyet_luc === null) {
            throw new ConflictHttpException('Chỉ áp dụng lại giáo án PT đã được bạn xác nhận trước đây.');
        }
        if ($k->trang_thai === 'DANG_AP_DUNG') {
            return $k->load('cacBaiTap');
        }
        $this->kiemTraPhienBan($k, $phienBan);
        if ($k->trang_thai !== 'LUU_TRU') {
            throw new ConflictHttpException('Chỉ áp dụng lại giáo án PT đã lưu trữ.');
        }
        $cu = $this->dangApDung($k->khach_hang_id);
        if ($cu) {
            $cu->trang_thai = 'LUU_TRU';
            $this->luuPhienBan($cu);
        }
        $k->trang_thai = 'DANG_AP_DUNG';
        $this->luuPhienBan($k);

        return $k->refresh()->load('cacBaiTap');
    }

    private function ngungApDung(KeHoachTap $k, string $phienBan): KeHoachTap
    {
        if ($k->trang_thai === 'LUU_TRU') {
            return $k->load('cacBaiTap');
        }
        $this->kiemTraPhienBan($k, $phienBan);
        if ($k->trang_thai !== 'DANG_AP_DUNG') {
            throw new ConflictHttpException('Chỉ ngừng áp dụng giáo án đang dùng.');
        }
        $k->trang_thai = 'LUU_TRU';
        $this->luuPhienBan($k);

        return $k->refresh()->load('cacBaiTap');
    }

    private function khoaThaoTac(TaiKhoan $nguoi, int $id, string $hanhDong = ''): KeHoachTap
    {
        $khachId = $this->phamVi($nguoi)->findOrFail($id)->khach_hang_id;
        HoSoKhachHang::lockForUpdate()->findOrFail($khachId);
        $keHoach = KeHoachTap::lockForUpdate()->findOrFail($id);
        if ($keHoach->nguon_tao === 'KHACH_HANG') {
            abort_unless($nguoi->vai_tro === TaiKhoan::KHACH_HANG && (int) $keHoach->khach_hang_id === (int) $nguoi->hoSoKhachHang->id, 404);

            return $keHoach;
        }
        if ($nguoi->vai_tro === TaiKhoan::KHACH_HANG && in_array($hanhDong, ['luu-tru', 'ap-dung'], true)) {
            abort_unless((int) $keHoach->khach_hang_id === (int) $nguoi->hoSoKhachHang->id && $keHoach->gui_luc !== null, 404);

            return $keHoach;
        }
        $pc = PhanCongHuanLuyenVien::where('khach_hang_id', $khachId)->whereNull('ket_thuc_luc')->lockForUpdate()->first();
        abort_unless($pc && (int) $pc->id === (int) $keHoach->phan_cong_id, 404);
        if ($nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN) {
            abort_unless((int) $pc->huan_luyen_vien_id === (int) $nguoi->hoSoHuanLuyenVien->id && (int) $keHoach->huan_luyen_vien_id === (int) $pc->huan_luyen_vien_id, 404);
        } else {
            abort_unless((int) $keHoach->khach_hang_id === (int) $nguoi->hoSoKhachHang->id && $keHoach->gui_luc !== null, 404);
        }

        return $keHoach;
    }

    private function dangApDung(int $khachId): ?KeHoachTap
    {
        return KeHoachTap::where('khach_hang_id', $khachId)->where('trang_thai', 'DANG_AP_DUNG')->lockForUpdate()->first();
    }

    private function kiemTraBanThayThe(KeHoachTap $keHoach): ?KeHoachTap
    {
        $cu = $this->dangApDung($keHoach->khach_hang_id);
        if (($cu?->id) !== ($keHoach->thay_the_ke_hoach_id ? (int) $keHoach->thay_the_ke_hoach_id : null)) {
            throw new ConflictHttpException('Giáo án đang áp dụng đã thay đổi. PT cần tạo bản mới từ trạng thái hiện tại.');
        }

        return $cu;
    }

    private function kiemTraMau(?int $id, bool $tuTao = false): void
    {
        if ($tuTao && $id !== null) {
            throw ValidationException::withMessages(['giao_an_mau_id' => 'Giáo án tự tạo dùng bài tập từ thư viện.']);
        }
        if ($id !== null && ! GiaoAnMau::where('trang_thai', 'DA_DUYET')->whereKey($id)->lockForUpdate()->exists()) {
            throw ValidationException::withMessages(['giao_an_mau_id' => 'Chỉ sử dụng giáo án mẫu đã được duyệt.']);
        }
    }

    private function thaoTacTuTao(KeHoachTap $k, string $hanhDong, string $phienBan): KeHoachTap
    {
        if (in_array($hanhDong, ['an', 'hien-lai'], true)) {
            if (! in_array($k->trang_thai, ['DA_HUY', 'LUU_TRU'], true)) {
                throw new ConflictHttpException('Chỉ ẩn hoặc hiện lại giáo án đã hủy hay lưu trữ. Hãy hủy bản nháp hoặc ngừng áp dụng trước.');
            }
            $an = $hanhDong === 'an';
            if (($k->khach_an_luc !== null) === $an) {
                return $k->load('cacBaiTap');
            }
            $this->kiemTraPhienBan($k, $phienBan);
            // Chỉ đổi lựa chọn hiển thị; không đụng snapshot, trạng thái hay quyền đọc của PT.
            $k->khach_an_luc = $an ? now() : null;
            $this->luuPhienBan($k);

            return $k->refresh()->load('cacBaiTap');
        }
        abort_unless(in_array($hanhDong, ['ap-dung', 'huy'], true), 404);
        if ($hanhDong === 'ap-dung' && $k->khach_an_luc !== null) {
            throw new ConflictHttpException('Hãy hiện lại giáo án trước khi áp dụng.');
        }
        $dich = ['ap-dung' => 'DANG_AP_DUNG', 'huy' => 'DA_HUY'][$hanhDong];
        if ($k->trang_thai === $dich) {
            return $k->load('cacBaiTap');
        }
        $this->kiemTraPhienBan($k, $phienBan);
        if ($hanhDong === 'ap-dung') {
            if (! in_array($k->trang_thai, ['NHAP', 'LUU_TRU'], true)) {
                throw new ConflictHttpException('Chỉ áp dụng bản nháp hoặc giáo án đã lưu trữ.');
            }
            if ($k->trang_thai === 'NHAP') {
                $duLieu = [...$this->chuanHoa($k->toArray()), 'updated_at' => $phienBan, 'bai_tap' => $this->chuanHoaBai($k->cacBaiTap->toArray())];
                $request = new KeHoachTapRequest;
                $request->setMethod('PUT');
                $v = Validator::make($duLieu, $request->rules(), $request->messages());
                $v->after(fn ($v) => GhiGiaoAnMauRequest::kiemTraThuTu($v, $duLieu))->validate();
                $ngay = array_unique(array_column($duLieu['bai_tap'], 'ngay_thu'));
                sort($ngay);
                if ($ngay !== range(1, $k->so_ngay_tap)) {
                    throw ValidationException::withMessages(['bai_tap' => 'Mỗi ngày cần ít nhất một bài trước khi áp dụng.']);
                }
                $snapshot = $this->taoSnapshot($duLieu['bai_tap']);
                foreach ($k->cacBaiTap as $viTri => $dong) {
                    $dong->update($snapshot[$viTri]);
                }
            }
            // Khóa KH đã giữ từ khoaThaoTac; hai nguồn dùng chung một bản đang áp dụng.
            $cu = $this->dangApDung($k->khach_hang_id);
            if ($cu) {
                $cu->trang_thai = 'LUU_TRU';
                $this->luuPhienBan($cu);
            }
            $k->duyet_luc ??= now();
        } elseif ($k->trang_thai !== 'NHAP') {
            throw new ConflictHttpException('Giáo án không còn ở trạng thái phù hợp.');
        }
        $k->trang_thai = $dich;
        $this->luuPhienBan($k);

        return $k->refresh()->load('cacBaiTap');
    }

    private function chuanHoa(array $duLieu): array
    {
        return ['ten_ke_hoach' => trim($duLieu['ten_ke_hoach']), 'muc_tieu' => $duLieu['muc_tieu'] === '' ? null : $duLieu['muc_tieu'], 'so_ngay_tap' => (int) $duLieu['so_ngay_tap'], 'giao_an_mau_id' => $duLieu['giao_an_mau_id'] ? (int) $duLieu['giao_an_mau_id'] : null, 'bai_tap' => $this->chuanHoaBai($duLieu['bai_tap'] ?? [])];
    }

    private function chuanHoaBai(array $cacBai): array
    {
        $cacBai = array_map(function ($dong) {
            $bai = array_replace(array_fill_keys(self::THUOC_TINH_BAI, null), Arr::only($dong, self::THUOC_TINH_BAI));
            foreach (array_diff(self::THUOC_TINH_BAI, ['ghi_chu', 'muc_ta_kg']) as $truong) {
                $bai[$truong] = (int) $bai[$truong];
            }
            $bai['ghi_chu'] = ($bai['ghi_chu'] ?? '') === '' ? null : $bai['ghi_chu'];
            $bai['muc_ta_kg'] = ($bai['muc_ta_kg'] ?? null) === null ? null : number_format((float) $bai['muc_ta_kg'], 2, '.', '');

            return $bai;
        }, $cacBai);
        usort($cacBai, fn ($a, $b) => [$a['ngay_thu'], $a['thu_tu']] <=> [$b['ngay_thu'], $b['thu_tu']]);

        return $cacBai;
    }

    private function taoSnapshot(array $cacBai): array
    {
        $cacBai = $this->chuanHoaBai($cacBai);
        $baiTap = BaiTap::whereIn('id', array_column($cacBai, 'bai_tap_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $nhomCo = NhomCo::whereIn('id', $baiTap->pluck('nhom_co_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        foreach ($cacBai as $viTri => &$dong) {
            $bai = $baiTap->get($dong['bai_tap_id']);
            if (! $bai || $bai->trang_thai !== 'HOAT_DONG' || $nhomCo->get($bai->nhom_co_id)?->trang_thai !== 'HOAT_DONG') {
                throw ValidationException::withMessages(["bai_tap.$viTri.bai_tap_id" => 'Bài tập hoặc nhóm cơ đã ngừng hoạt động. Hãy chọn bài khác.']);
            }
            $dong['ten_bai_tap_snapshot'] = $bai->ten_tieng_viet ?: $bai->ten_bai_tap;
            $ngonNgu = ! empty($bai->huong_dan['vi']) || ! empty($bai->cac_buoc['vi']) ? 'vi' : 'en';
            $dong['noi_dung_snapshot'] = [...Arr::only($bai->toArray(), ['anh_url', 'gif_url', 'dung_cu', 'ghi_cong_media']), 'nhom_co' => $nhomCo->get($bai->nhom_co_id)->ten_nhom_co, 'ngon_ngu_huong_dan' => $ngonNgu, 'huong_dan' => $bai->huong_dan[$ngonNgu] ?? null, 'cac_buoc' => $bai->cac_buoc[$ngonNgu] ?? []];
        }
        unset($dong);

        return $cacBai;
    }

    private function kiemTraPhienBan(KeHoachTap $keHoach, string $phienBan): void
    {
        if ($keHoach->updated_at?->format('Y-m-d H:i:s.u') !== $phienBan) {
            throw new ConflictHttpException('Giáo án đã thay đổi. Hãy tải lại trước khi tiếp tục.');
        }
    }

    private function luuPhienBan(KeHoachTap $keHoach): void
    {
        $keHoach->updated_at = $keHoach->updated_at && now()->lessThanOrEqualTo($keHoach->updated_at) ? $keHoach->updated_at->copy()->addMicrosecond() : now();
        $keHoach->save();
    }

    private function thongBao(KeHoachTap $keHoach, int $taiKhoanId, string $hanhDong, string $tieuDe, string $noiDung, string $duongDan): void
    {
        // Cùng transaction với giáo án; khóa KH và ID xác định giữ retry không sinh thông báo trùng.
        DB::table('notifications')->insert([
            'id' => (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'ke-hoach/'.$keHoach->id.'/'.$hanhDong),
            'type' => 'ke_hoach_tap', 'notifiable_type' => TaiKhoan::class, 'notifiable_id' => $taiKhoanId,
            'data' => json_encode(['tieu_de' => $tieuDe, 'noi_dung' => $noiDung, 'duong_dan' => $duongDan], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
