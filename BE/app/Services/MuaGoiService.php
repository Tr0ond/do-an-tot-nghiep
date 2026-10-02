<?php

namespace App\Services;

use App\Models\DangKyGoiTap;
use App\Models\GoiTap;
use App\Models\HoSoKhachHang;
use App\Models\TaiKhoan;
use App\Models\ThanhToan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class MuaGoiService
{
    public function __construct(private PayosService $payos) {}

    public function taoDon(TaiKhoan $taiKhoan, array $duLieu): DangKyGoiTap
    {
        return DB::transaction(function () use ($taiKhoan, $duLieu) {
            $khach = HoSoKhachHang::where('tai_khoan_id', $taiKhoan->id)->lockForUpdate()->firstOrFail();
            abort_unless($khach->taiKhoan()->where('trang_thai', TaiKhoan::HOAT_DONG)->exists(), 403);
            $ma = strtolower($duLieu['client_request_id']);
            $cu = DangKyGoiTap::where('khach_hang_id', $khach->id)->where('client_request_id', $ma)->first();
            if ($cu) {
                if ($cu->goi_tap_id !== (int) $duLieu['goi_tap_id']) {
                    throw new ConflictHttpException('Mã yêu cầu đã dùng cho gói khác.');
                }

                return $cu;
            }
            $this->dongHetHan($khach->id);
            if ($this->goiHieuLuc($khach->id)->exists()) {
                throw new ConflictHttpException('Bạn đang có gói còn hiệu lực. Hãy dùng hết thời hạn trước khi mua gói mới.');
            }
            if (DangKyGoiTap::where('khach_hang_id', $khach->id)->where('trang_thai', 'CHO_THANH_TOAN')->exists()) {
                throw new ConflictHttpException('Bạn đang có đơn chờ thanh toán. Hãy mở Đơn hàng để tiếp tục.');
            }
            $goi = GoiTap::dangHienThi()->sharedLock()->findOrFail($duLieu['goi_tap_id']);

            return DangKyGoiTap::create(['khach_hang_id' => $khach->id, 'goi_tap_id' => $goi->id, 'client_request_id' => $ma, 'ma_don_payos' => random_int(100000000000000, 899999999999999), 'ten_goi_snapshot' => $goi->ten_goi, 'gia_snapshot' => $goi->gia, 'co_chatbot_snapshot' => $goi->co_chatbot, 'so_luot_chatbot_moi_ngay_snapshot' => $goi->so_luot_chatbot_moi_ngay, 'so_buoi_pt_snapshot' => $goi->so_buoi_pt, 'thoi_han_ngay_snapshot' => $goi->thoi_han_ngay, 'so_buoi_con_lai' => 0, 'trang_thai' => 'CHO_THANH_TOAN', 'han_thanh_toan' => now()->addMinutes(15)]);
        }, 3);
    }

    public function taoLink(DangKyGoiTap $don): DangKyGoiTap
    {
        if ($don->trang_thai !== 'CHO_THANH_TOAN' || $don->han_thanh_toan->lessThanOrEqualTo(now())) {
            throw new ConflictHttpException('Đơn không còn trong thời gian thanh toán.');
        }
        if ($don->url_thanh_toan) {
            return $don;
        }
        $khoa = Cache::lock('payos-tao-link-'.$don->id, 30);
        if (! $khoa->get()) {
            throw new ConflictHttpException('Đang tạo liên kết cho đơn này. Hãy thử lại sau ít giây.');
        }
        try {
            $don->refresh();
            if ($don->trang_thai !== 'CHO_THANH_TOAN' || $don->han_thanh_toan->lessThanOrEqualTo(now())) {
                throw new ConflictHttpException('Đơn không còn trong thời gian thanh toán.');
            }
            if ($don->url_thanh_toan) {
                return $don;
            }
            // Cùng orderCode khi retry; khôi phục link nếu response tạo link trước đó bị mất.
            try {
                $duLieu = $this->payos->taoLink($don);
            } catch (ServiceUnavailableHttpException) {
                $duLieu = $this->payos->layLink($don->ma_don_payos);
                $duLieu['paymentLinkId'] = $duLieu['id'] ?? null;
                $duLieu['checkoutUrl'] = 'https://pay.payos.vn/web/'.($duLieu['id'] ?? '');
            }
            $this->doiChieuLink($don, $duLieu, 'paymentLinkId');
            $url = $duLieu['checkoutUrl'] ?? '';
            if (! is_string($url) || ! preg_match('~^https://pay\.payos\.vn/(?:web/)?[a-zA-Z0-9/_?=&%-]+$~D', $url)) {
                throw new ServiceUnavailableHttpException(null, 'Liên kết thanh toán không hợp lệ.');
            }

            return DB::transaction(function () use ($don, $duLieu, $url) {
                HoSoKhachHang::lockForUpdate()->findOrFail($don->khach_hang_id);
                $banMoi = DangKyGoiTap::lockForUpdate()->findOrFail($don->id);
                $this->doiChieuLink($banMoi, $duLieu, 'paymentLinkId');
                $banMoi->ma_link_payos = $duLieu['paymentLinkId'];
                $banMoi->url_thanh_toan = $url;
                $banMoi->save();

                return $banMoi;
            }, 3);
        } finally {
            $khoa->release();
        }
    }

    public function dongBo(DangKyGoiTap $don, ?array $thongBao = null): DangKyGoiTap
    {
        $duLieu = $this->payos->layLink($don->ma_don_payos);
        $this->doiChieuLink($don, $duLieu, 'id');
        $giaoDich = $this->docGiaoDich($duLieu);
        if ($thongBao && (($thongBao['paymentLinkId'] ?? null) !== $duLieu['id'] || ! collect($giaoDich)->contains(fn ($gd) => $gd['ma'] === $thongBao['reference'] && $gd['tien'] === $thongBao['amount']))) {
            throw new ServiceUnavailableHttpException(null, 'Thông báo chưa khớp dữ liệu payOS.');
        }

        return DB::transaction(function () use ($don, $duLieu, $giaoDich) {
            HoSoKhachHang::lockForUpdate()->findOrFail($don->khach_hang_id);
            $banMoi = DangKyGoiTap::lockForUpdate()->findOrFail($don->id);
            $this->doiChieuLink($banMoi, $duLieu, 'id');
            $this->dongHetHan($banMoi->khach_hang_id);
            $banMoi->refresh();
            $banMoi->ma_link_payos = $duLieu['id'];
            $tienDaGhi = ThanhToan::where('dang_ky_goi_tap_id', $banMoi->id)->sum('so_tien');
            // Response GET cũ không được ghi đè webhook mới đã commit.
            if ((int) $duLieu['amountPaid'] < (int) $tienDaGhi) {
                return $banMoi;
            }
            $lyDo = null;
            if ($giaoDich) {
                if (($duLieu['status'] ?? '') !== 'PAID' || (int) $duLieu['amountPaid'] !== $banMoi->gia_snapshot) {
                    $lyDo = 'Số tiền thực nhận chưa đúng giá đơn.';
                }
                foreach ($giaoDich as $gd) {
                    if ($gd['luc']->greaterThan($banMoi->han_thanh_toan) || $gd['luc']->lessThan($banMoi->created_at->copy()->startOfSecond())) {
                        $lyDo = 'Tiền được ghi nhận ngoài thời gian chờ thanh toán.';
                    }
                }
                if (! $banMoi->kich_hoat_luc && $this->goiHieuLuc($banMoi->khach_hang_id)->where('id', '!=', $banMoi->id)->exists()) {
                    $lyDo = 'Khách đã có gói khả dụng khác.';
                }
                if (ThanhToan::where('dang_ky_goi_tap_id', $banMoi->id)->where('trang_thai', 'DA_HOAN_TIEN')->exists()) {
                    $lyDo = 'Khoản thu đã được hoàn tiền thủ công.';
                }
                foreach ($giaoDich as $gd) {
                    $cu = ThanhToan::where('ma_giao_dich', $gd['ma'])->lockForUpdate()->first();
                    if ($cu && ($cu->dang_ky_goi_tap_id !== $banMoi->id || $cu->so_tien !== $gd['tien'] || ! $cu->thanh_toan_luc->equalTo($gd['luc']))) {
                        throw new ConflictHttpException('Mã giao dịch không khớp khoản thu đã ghi nhận.');
                    }
                    if (! $cu) {
                        ThanhToan::create(['dang_ky_goi_tap_id' => $banMoi->id, 'ma_giao_dich' => $gd['ma'], 'so_tien' => $gd['tien'], 'thanh_toan_luc' => $gd['luc'], 'xac_minh_luc' => now(), 'trang_thai' => $lyDo ? 'CAN_DOI_SOAT' : 'DA_XAC_MINH', 'ly_do_doi_soat' => $lyDo]);
                    } elseif (! $lyDo && $cu->trang_thai === 'CAN_DOI_SOAT') {
                        $cu->update(['trang_thai' => 'DA_XAC_MINH', 'ly_do_doi_soat' => null]);
                    }
                }
                if (! $banMoi->kich_hoat_luc) {
                    if ($lyDo) {
                        $banMoi->trang_thai = 'CAN_DOI_SOAT';
                    } else {
                        $banMoi->trang_thai = 'DANG_SU_DUNG';
                        $banMoi->kich_hoat_luc = CarbonImmutable::now();
                        $banMoi->het_han_luc = $banMoi->kich_hoat_luc->addHours((int) $banMoi->thoi_han_ngay_snapshot * 24);
                        $banMoi->so_buoi_con_lai = $banMoi->so_buoi_pt_snapshot;
                        $this->ghiAudit('KICH_HOAT_GOI', $banMoi->id);
                    }
                }
            } elseif (! $banMoi->kich_hoat_luc && in_array($banMoi->trang_thai, ['CHO_THANH_TOAN', 'HET_HAN_THANH_TOAN'], true)) {
                $banMoi->trang_thai = match ($duLieu['status']) {
                    'CANCELLED' => 'DA_HUY', 'EXPIRED' => 'HET_HAN_THANH_TOAN', default => $banMoi->han_thanh_toan->lessThanOrEqualTo(now()) ? 'HET_HAN_THANH_TOAN' : 'CHO_THANH_TOAN'
                };
            }
            $banMoi->save();

            return $banMoi;
        }, 3);
    }

    public function doiSoat(TaiKhoan $admin, int $id, array $duLieu): ThanhToan
    {
        return DB::transaction(function () use ($id, $duLieu, $admin) {
            $thamChieu = ThanhToan::findOrFail($id);
            $don = DangKyGoiTap::findOrFail($thamChieu->dang_ky_goi_tap_id);
            HoSoKhachHang::lockForUpdate()->findOrFail($don->khach_hang_id);
            $thanhToan = ThanhToan::lockForUpdate()->findOrFail($id);
            if ($thanhToan->trang_thai === 'DA_HOAN_TIEN') {
                abort_unless($thanhToan->so_tien_hoan === (int) $duLieu['so_tien_hoan'] && $thanhToan->ma_hoan_tien === $duLieu['ma_hoan_tien'] && $thanhToan->ly_do_hoan_tien === $duLieu['ly_do'], 409, 'Khoản thu đã có kết quả khác.');

                return $thanhToan;
            }
            abort_unless($thanhToan->trang_thai === 'CAN_DOI_SOAT' && (int) $duLieu['so_tien_hoan'] <= $thanhToan->so_tien, 409, 'Số tiền hoàn không hợp lệ hoặc khoản thu đã được xử lý.');
            $thanhToan->fill(['trang_thai' => 'DA_HOAN_TIEN', 'nguoi_doi_soat_id' => $admin->id, 'doi_soat_luc' => now(), 'so_tien_hoan' => $duLieu['so_tien_hoan'], 'ma_hoan_tien' => $duLieu['ma_hoan_tien'], 'ly_do_hoan_tien' => $duLieu['ly_do'], 'hoan_tien_luc' => now()])->save();
            DB::table('nhat_ky_he_thong')->insert(['tai_khoan_id' => $admin->id, 'hanh_dong' => 'GHI_NHAN_HOAN_TIEN', 'loai_tai_nguyen' => 'thanh_toan', 'tai_nguyen_id' => $id, 'created_at' => now(), 'updated_at' => now()]);

            return $thanhToan;
        }, 3);

    }

    private function doiChieuLink(DangKyGoiTap $don, array $duLieu, string $truong): void
    {
        $maLink = $duLieu[$truong] ?? null;
        if (! is_string($maLink) || ! preg_match('/^[a-zA-Z0-9_-]{1,191}$/D', $maLink) || (string) ($duLieu['orderCode'] ?? '') !== (string) $don->ma_don_payos || (string) ($duLieu['amount'] ?? '') !== (string) $don->gia_snapshot || ($don->ma_link_payos && $don->ma_link_payos !== $maLink)) {
            throw new ServiceUnavailableHttpException(null, 'Thông tin payOS không khớp đơn hàng.');
        }
    }

    private function docGiaoDich(array $duLieu): array
    {
        if (! in_array($duLieu['status'] ?? '', ['PENDING', 'PROCESSING', 'PAID', 'CANCELLED', 'EXPIRED'], true) || ! is_int($duLieu['amountPaid'] ?? null) || $duLieu['amountPaid'] < 0 || ! is_array($duLieu['transactions'] ?? null) || ! array_is_list($duLieu['transactions'])) {
            throw new ServiceUnavailableHttpException(null, 'Dữ liệu giao dịch payOS không hợp lệ.');
        }
        $ketQua = [];
        $cacMa = [];
        foreach ($duLieu['transactions'] as $gd) {
            if (! is_array($gd) || ! is_int($gd['amount'] ?? null) || $gd['amount'] <= 0 || ! is_string($gd['reference'] ?? null) || strlen($gd['reference']) > 191 || $gd['reference'] === '' || isset($cacMa[$gd['reference']]) || ! is_string($gd['transactionDateTime'] ?? null)) {
                throw new ServiceUnavailableHttpException(null, 'Giao dịch payOS chưa đủ thông tin xác minh.');
            }
            if (! preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})?$/D', $gd['transactionDateTime'])) {
                throw new ServiceUnavailableHttpException(null, 'Thời điểm thanh toán không hợp lệ.');
            }
            try {
                $luc = CarbonImmutable::parse($gd['transactionDateTime'], 'Asia/Ho_Chi_Minh')->utc();
            } catch (\Throwable) {
                throw new ServiceUnavailableHttpException(null, 'Thời điểm thanh toán không hợp lệ.');
            }
            if ($luc->greaterThan(now()->addMinute())) {
                throw new ServiceUnavailableHttpException(null, 'Thời điểm thanh toán chưa hợp lệ.');
            }
            $cacMa[$gd['reference']] = true;
            $ketQua[] = ['ma' => $gd['reference'], 'tien' => $gd['amount'], 'luc' => $luc];
        }
        if (array_sum(array_column($ketQua, 'tien')) !== $duLieu['amountPaid'] || (($duLieu['status'] ?? '') === 'PAID' && ! $ketQua)) {
            throw new ServiceUnavailableHttpException(null, 'Tổng giao dịch chưa khớp số tiền payOS.');
        }

        return $ketQua;
    }

    public function goiHieuLuc(int $khachId)
    {
        return DangKyGoiTap::where('khach_hang_id', $khachId)->where('trang_thai', 'DANG_SU_DUNG')->where('het_han_luc', '>', now());
    }

    public function dongHetHan(int $khachId): void
    {
        DangKyGoiTap::where('khach_hang_id', $khachId)->where('trang_thai', 'DANG_SU_DUNG')->where('het_han_luc', '<=', now())->update(['trang_thai' => 'HET_HAN']);
        DangKyGoiTap::where('khach_hang_id', $khachId)->where('trang_thai', 'CHO_THANH_TOAN')->where('han_thanh_toan', '<=', now())->update(['trang_thai' => 'HET_HAN_THANH_TOAN']);
    }

    private function ghiAudit(string $hanhDong, int $id): void
    {
        DB::table('nhat_ky_he_thong')->insert(['hanh_dong' => $hanhDong, 'loai_tai_nguyen' => 'dang_ky_goi_tap', 'tai_nguyen_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
    }
}
