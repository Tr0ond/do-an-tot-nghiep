<?php

namespace App\Services;

use App\Models\GhiChuHuanLuyen;
use App\Models\HoSoKhachHang;
use App\Models\KeHoachTap;
use App\Models\LichTap;
use App\Models\TaiKhoan;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class NhatKyTapService
{
    public function khoaKhach(TaiKhoan $nguoi, int $id): HoSoKhachHang
    {
        abort_unless(in_array($nguoi->vai_tro, [TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN], true), 403);
        if ($nguoi->vai_tro === TaiKhoan::KHACH_HANG) {
            abort_unless((int) $nguoi->hoSoKhachHang->id === $id, 404);
        }
        $khach = HoSoKhachHang::lockForUpdate()->findOrFail($id);
        if ($nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN) {
            app(KeHoachTapService::class)->phanCong($nguoi, $id);
        }

        return $khach;
    }

    public function khoaLich(TaiKhoan $nguoi, int $id): LichTap
    {
        $khachId = LichTap::whereKey($id)->value('khach_hang_id');
        abort_unless($khachId !== null, 404);
        $this->khoaKhach($nguoi, $khachId);

        return LichTap::lockForUpdate()->findOrFail($id);
    }

    public function tao(TaiKhoan $nguoi, int $khachId, array $d): LichTap
    {
        return $this->transaction(function () use ($nguoi, $khachId, $d) {
            $this->khoaKhach($nguoi, $khachId);
            $ma = strtolower($d['client_request_id']);
            $noiDung = ['ke_hoach_tap_id' => (int) $d['ke_hoach_tap_id'], 'ngay_thu' => (int) $d['ngay_thu'], 'ngay_tap' => $d['ngay_tap']];
            $hash = hash('sha256', json_encode($noiDung, JSON_THROW_ON_ERROR));
            if ($cu = LichTap::where('ma_yeu_cau_tao', $ma)->lockForUpdate()->first()) {
                if ((int) $cu->nguoi_tao_id !== (int) $nguoi->id || (int) $cu->khach_hang_id !== $khachId || $cu->hash_yeu_cau_tao !== $hash) {
                    throw new ConflictHttpException('Mã yêu cầu đã được dùng cho lịch khác.');
                }

                return $cu;
            }
            $k = KeHoachTap::where('khach_hang_id', $khachId)->lockForUpdate()->findOrFail($d['ke_hoach_tap_id']);
            if ($k->trang_thai !== 'DANG_AP_DUNG') {
                throw new ConflictHttpException('Giáo án đã thay đổi. Chọn giáo án đang áp dụng để lên lịch mới.');
            }
            if (! $k->cacBaiTap()->where('ngay_thu', $d['ngay_thu'])->exists()) {
                throw ValidationException::withMessages(['ngay_thu' => 'Ngày này chưa có bài tập trong giáo án.']);
            }
            if (LichTap::where($noiDung)->lockForUpdate()->exists()) {
                throw new ConflictHttpException('Ngày trong giáo án này đã có lịch vào ngày đã chọn, kể cả lịch đã hủy.');
            }

            return LichTap::create([...$noiDung, 'khach_hang_id' => $khachId, 'trang_thai' => 'DA_LEN_LICH',
                'ma_yeu_cau_tao' => $ma, 'hash_yeu_cau_tao' => $hash, 'nguoi_tao_id' => $nguoi->id]);
        });
    }

    public function thaoTac(TaiKhoan $nguoi, int $id, string $hanhDong, string $phienBan): LichTap
    {
        return DB::transaction(function () use ($nguoi, $id, $hanhDong, $phienBan) {
            $lich = $this->khoaLich($nguoi, $id);
            // PT chỉ lên lịch/nhận xét; kết quả luôn do chính KH ghi.
            abort_unless($nguoi->vai_tro === TaiKhoan::KHACH_HANG, 403);
            $p = $lich->phien()->lockForUpdate()->first();
            if (($hanhDong === 'bat-dau' && $p !== null && $lich->trang_thai !== 'DA_HUY')
                || ($hanhDong === 'hoan-thanh' && $lich->trang_thai === 'HOAN_THANH')
                || ($hanhDong === 'huy' && $lich->trang_thai === 'DA_HUY')) {
                return $lich;
            }
            $this->kiemTraPhienBan($lich, $phienBan);
            if ($hanhDong === 'bat-dau') {
                if ($lich->trang_thai !== 'DA_LEN_LICH' || $lich->ngay_tap > now('Asia/Ho_Chi_Minh')->toDateString()) {
                    throw new ConflictHttpException('Chỉ bắt đầu lịch chưa hủy khi đến ngày tập.');
                }
                $cacBai = $lich->keHoach->cacBaiTap()->where('ngay_thu', $lich->ngay_thu)->get();
                if ($cacBai->isEmpty()) {
                    throw new ConflictHttpException('Lịch không có bài tập hợp lệ.');
                }
                $p = $lich->phien()->create(['khach_hang_id' => $lich->khach_hang_id, 'bat_dau_luc' => now(), 'trang_thai' => 'DANG_TAP']);
                foreach ($cacBai as $i => $b) {
                    $p->cacBaiTap()->create(['bai_tap_id' => $b->bai_tap_id, 'bai_tap_trong_ke_hoach_id' => $b->id,
                        'thu_tu' => $i + 1, 'ten_bai_tap_snapshot' => $b->ten_bai_tap_snapshot,
                        'noi_dung_snapshot' => [...($b->noi_dung_snapshot ?? []), 'du_kien' => $b->only(['so_hiep', 'so_lan_lap', 'muc_ta_kg', 'nghi_giay', 'ghi_chu'])]]);
                }
                $lich->trang_thai = 'DANG_TAP';
            } elseif ($hanhDong === 'hoan-thanh') {
                if ($lich->trang_thai !== 'DANG_TAP' || ! $p || $p->trang_thai !== 'DANG_TAP') {
                    throw new ConflictHttpException('Chỉ hoàn thành buổi đang tập.');
                }
                if ($p->cacBaiTap()->doesntHave('cacHiep')->exists()) {
                    throw ValidationException::withMessages(['bai_tap' => 'Mỗi bài cần ít nhất một hiệp thực tế trước khi hoàn thành.']);
                }
                $p->trang_thai = 'HOAN_THANH';
                $p->hoan_thanh_luc = now();
                $p->save();
                $lich->trang_thai = 'HOAN_THANH';
                app(ThongBaoService::class)->choPtHienTai($lich->khach_hang_id, 'nhat-ky/'.$p->id.'/hoan-thanh', 'Có nhật ký tập mới', 'Học viên đã hoàn thành buổi tự tập. Bạn có thể xem kết quả và nhận xét.', '/pt/lich-tap/'.$lich->id);
            } elseif ($hanhDong === 'huy') {
                if (! in_array($lich->trang_thai, ['DA_LEN_LICH', 'DANG_TAP'], true)) {
                    throw new ConflictHttpException('Buổi đã hoàn thành không thể hủy.');
                }
                $p?->update(['trang_thai' => 'DA_HUY']);
                $lich->trang_thai = 'DA_HUY';
            } else {
                abort(404);
            }
            $this->luuPhienBan($lich);

            return $lich;
        }, 3);
    }

    public function luu(TaiKhoan $nguoi, int $id, array $d): LichTap
    {
        return $this->transaction(function () use ($nguoi, $id, $d) {
            $lich = $this->khoaLich($nguoi, $id);
            abort_unless($nguoi->vai_tro === TaiKhoan::KHACH_HANG, 403);
            $p = $lich->phien()->lockForUpdate()->first();
            if ($lich->trang_thai !== 'DANG_TAP' || ! $p || $p->trang_thai !== 'DANG_TAP') {
                throw new ConflictHttpException('Chỉ sửa kết quả buổi đang tập. Kết quả hoàn thành được giữ nguyên.');
            }
            $bai = $p->cacBaiTap()->with('cacHiep')->get();
            $nhap = collect($d['bai_tap'])->keyBy('id');
            if ($nhap->count() !== $bai->count() || $nhap->keys()->diff($bai->pluck('id'))->isNotEmpty()) {
                throw ValidationException::withMessages(['bai_tap' => 'Danh sách bài phải đúng và đầy đủ các bài trong phiên này.']);
            }
            $ghiChu = isset($d['ghi_chu']) ? trim($d['ghi_chu']) : null;
            $doi = ($p->ghi_chu ?: null) !== ($ghiChu ?: null);
            $chuan = [];
            foreach ($bai as $b) {
                $chuan[$b->id] = array_map(fn ($h, $i) => ['thu_tu' => $i + 1, 'so_lan_lap' => (int) $h['so_lan_lap'],
                    'khoi_luong_kg' => $h['khoi_luong_kg'] === null ? null : number_format((float) $h['khoi_luong_kg'], 2, '.', ''),
                    'nghi_giay' => (int) $h['nghi_giay']], $nhap[$b->id]['hiep_tap'], array_keys($nhap[$b->id]['hiep_tap']));
                $cu = $b->cacHiep->map(fn ($h) => $h->only(['thu_tu', 'so_lan_lap', 'khoi_luong_kg', 'nghi_giay']))->all();
                $doi = $doi || $chuan[$b->id] !== $cu;
            }
            // Retry cùng kết quả đã lưu an toàn; dữ liệu khác phải mang đúng phiên bản.
            if (! $doi) {
                return $lich;
            }
            $this->kiemTraPhienBan($lich, $d['updated_at']);
            foreach ($bai as $b) {
                $b->cacHiep()->delete();
                $b->cacHiep()->createMany($chuan[$b->id]);
            }
            $p->ghi_chu = $ghiChu ?: null;
            $p->save();
            $this->luuPhienBan($lich);

            return $lich;
        });
    }

    public function nhanXet(TaiKhoan $nguoi, int $id, array $d): LichTap
    {
        return $this->transaction(function () use ($nguoi, $id, $d) {
            $lich = $this->khoaLich($nguoi, $id);
            abort_unless($nguoi->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN, 403);
            $pc = app(KeHoachTapService::class)->phanCong($nguoi, $lich->khach_hang_id);
            $p = $lich->phien()->lockForUpdate()->first();
            if ($lich->trang_thai !== 'HOAN_THANH' || ! $p) {
                throw new ConflictHttpException('Nhận xét sau khi học viên hoàn thành buổi tập.');
            }
            $noiDung = trim($d['noi_dung']);
            if ($noiDung === '') {
                throw ValidationException::withMessages(['noi_dung' => 'Nhập nội dung nhận xét.']);
            }
            $ma = strtolower($d['client_request_id']);
            $hash = hash('sha256', $noiDung);
            if ($cu = GhiChuHuanLuyen::where('ma_yeu_cau_tao', $ma)->lockForUpdate()->first()) {
                if ((int) $cu->phien_tap_id !== (int) $p->id || (int) $cu->phan_cong_id !== (int) $pc->id || $cu->hash_yeu_cau_tao !== $hash) {
                    throw new ConflictHttpException('Mã yêu cầu đã được dùng cho nhận xét khác.');
                }

                return $lich;
            }
            $nhanXet = $p->nhanXet()->create(['huan_luyen_vien_id' => $pc->huan_luyen_vien_id, 'phan_cong_id' => $pc->id,
                'noi_dung' => $noiDung, 'ma_yeu_cau_tao' => $ma, 'hash_yeu_cau_tao' => $hash]);
            app(ThongBaoService::class)->choKhach($lich->khach_hang_id, 'nhan-xet/'.$nhanXet->id, 'PT đã nhận xét buổi tập', 'Bạn có nhận xét mới từ PT phụ trách. Mở nhật ký để xem nội dung.', '/khach-hang/lich-tap/'.$lich->id);

            return $lich;
        });
    }

    private function transaction(callable $ham): LichTap
    {
        try {
            return DB::transaction($ham, 3);
        } catch (UniqueConstraintViolationException $e) {
            throw new ConflictHttpException('Yêu cầu bị trùng. Hãy tải lại trước khi tiếp tục.');
        }
    }

    private function kiemTraPhienBan(LichTap $lich, string $phienBan): void
    {
        if ($lich->updated_at?->format('Y-m-d H:i:s.u') !== $phienBan) {
            throw new ConflictHttpException('Buổi tập đã thay đổi. Tải lại để xem kết quả mới trước khi tiếp tục.');
        }
    }

    private function luuPhienBan(LichTap $lich): void
    {
        $lich->updated_at = $lich->updated_at && now()->lessThanOrEqualTo($lich->updated_at) ? $lich->updated_at->copy()->addMicrosecond() : now();
        $lich->save();
    }
}
