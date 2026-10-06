<?php

namespace App\Services;

use App\Models\HoSoHuanLuyenVien;
use App\Models\HoSoKhachHang;
use App\Models\LichHenHuanLuyen;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TaiKhoan;
use Illuminate\Support\Facades\DB;
use LogicException;
use Ramsey\Uuid\Uuid;

class ThongBaoService
{
    public function gui(int $taiKhoanId, string $suKien, string $tieuDe, string $noiDung, string $duongDan): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Thông báo phải được ghi cùng transaction nghiệp vụ.');
        }
        if (! TaiKhoan::whereKey($taiKhoanId)->where('trang_thai', TaiKhoan::HOAT_DONG)->exists()) {
            return;
        }
        // PK theo sự kiện và người nhận: retry giữ nguyên nội dung và thời điểm đã đọc.
        // Namespace cũ là định danh cố định; đổi thương hiệu không tạo lại thông báo.
        // Upsert chỉ cập nhật ID, không dùng insertOrIgnore để tránh nuốt lỗi dữ liệu khác.
        $id = (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'tr0ond/thong-bao/'.$suKien.'/'.$taiKhoanId);
        DB::table('notifications')->upsert([[
            'id' => $id, 'type' => 'nghiep_vu', 'notifiable_type' => TaiKhoan::class, 'notifiable_id' => $taiKhoanId,
            'data' => json_encode(['tieu_de' => $tieuDe, 'noi_dung' => $noiDung, 'duong_dan' => $duongDan], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'read_at' => null, 'created_at' => now(), 'updated_at' => now(),
        ]], ['id'], ['id']);
        if (preg_match('~^/(khach-hang|pt)/lich-hen/[1-9][0-9]*$~D', $duongDan)) {
            app(ThongBaoDayService::class)->xepHang($taiKhoanId, $suKien, $duongDan, 'lich');
        }
    }

    public function choAdmin(string $suKien, string $tieuDe, string $noiDung, string $duongDan): void
    {
        foreach (TaiKhoan::where('vai_tro', TaiKhoan::ADMIN)->where('trang_thai', TaiKhoan::HOAT_DONG)->orderBy('id')->pluck('id') as $id) {
            $this->gui($id, $suKien, $tieuDe, $noiDung, $duongDan);
        }
    }

    public function choKhach(int $khachId, string $suKien, string $tieuDe, string $noiDung, string $duongDan): void
    {
        $id = HoSoKhachHang::whereKey($khachId)->value('tai_khoan_id');
        if ($id) {
            $this->gui($id, $suKien, $tieuDe, $noiDung, $duongDan);
        }
    }

    public function choPtHienTai(int $khachId, string $suKien, string $tieuDe, string $noiDung, string $duongDan, ?int $phanCongId = null): void
    {
        $pc = PhanCongHuanLuyenVien::where('khach_hang_id', $khachId)->whereNull('ket_thuc_luc')->where('bat_dau_luc', '<=', now()->format('Y-m-d H:i:s.u'))
            ->when($phanCongId, fn ($q) => $q->whereKey($phanCongId))->first();
        $id = $pc ? HoSoHuanLuyenVien::whereKey($pc->huan_luyen_vien_id)->value('tai_khoan_id') : null;
        if ($id && HoSoKhachHang::whereKey($khachId)->whereHas('taiKhoan', fn ($q) => $q->where('trang_thai', TaiKhoan::HOAT_DONG))->exists()) {
            $this->gui($id, $suKien, $tieuDe, $noiDung, $duongDan);
        }
    }

    public function lichHen(LichHenHuanLuyen $lich, string $hanhDong): void
    {
        app(LichRealtimeService::class)->choPt($lich->huan_luyen_vien_id, $lich->khach_hang_id);
        $suKien = 'lich-hen/'.$lich->id.'/'.$hanhDong;
        $luc = $lich->bat_dau_luc->setTimezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');
        $duongDanKh = '/khach-hang/lich-hen/'.$lich->id;
        $duongDanPt = '/pt/lich-hen/'.$lich->id;
        if ($hanhDong === 'dat') {
            $this->choPtHienTai($lich->khach_hang_id, $suKien, 'Có yêu cầu đặt lịch', 'Học viên đặt lịch lúc '.$luc.'. Hãy xử lý trước hạn xác nhận.', $duongDanPt, $lich->phan_cong_id);
        } elseif ($hanhDong === 'huy') {
            $this->choPtHienTai($lich->khach_hang_id, $suKien, 'Học viên đã hủy lịch', 'Lịch hẹn lúc '.$luc.' đã được hủy. Khung giờ được giải phóng.', $duongDanPt, $lich->phan_cong_id);
        } elseif ($hanhDong === 'can-ghi-nhan') {
            $this->choPtHienTai($lich->khach_hang_id, $suKien, 'Buổi tập cần ghi nhận', 'Buổi lúc '.$luc.' đã kết thúc. Hãy ghi nhận kết quả trong vòng 24 giờ.', $duongDanPt, $lich->phan_cong_id);
        } elseif ($hanhDong === 'qua-han') {
            $this->choAdmin($suKien, 'Buổi PT quá hạn xác nhận', 'Lịch hẹn #'.$lich->id.' quá hạn ghi nhận; cần xem xét và đóng xử lý.', '/admin/lich-hen/'.$lich->id);
        } else {
            [$tieuDe, $noiDung] = match ($hanhDong) {
                'xac-nhan' => ['Lịch hẹn đã được xác nhận', 'PT đã xác nhận buổi lúc '.$luc.'.'],
                'tu-choi' => ['PT đã từ chối lịch hẹn', 'Yêu cầu lúc '.$luc.' đã được từ chối. Bạn có thể chọn khung giờ khác.'],
                'het-han' => ['Yêu cầu đặt lịch đã hết hạn', 'Yêu cầu lúc '.$luc.' chưa được xác nhận đúng hạn. Bạn có thể đặt lại.'],
                'doi-pt' => ['Lịch hẹn đã hủy do đổi PT', 'Buổi lúc '.$luc.' đã hủy khi đổi PT. Hãy đặt lại với PT mới.'],
                'hoan-thanh' => ['Buổi PT đã hoàn thành', 'PT đã ghi nhận buổi lúc '.$luc.'; gói đã trừ đúng một buổi.'],
                'vang-mat' => ['Buổi PT đã ghi nhận vắng mặt', 'Buổi lúc '.$luc.' được ghi nhận vắng mặt, không trừ lượt PT.'],
                default => throw new LogicException('Sự kiện lịch hẹn không hợp lệ.'),
            };
            $this->choKhach($lich->khach_hang_id, $suKien, $tieuDe, $noiDung, $duongDanKh);
        }
    }

    public function nhacBuoiCanGhiNhan(): void
    {
        LichHenHuanLuyen::where('trang_thai', 'DA_XAC_NHAN')->where('ket_thuc_luc', '<=', now())
            ->where('ket_thuc_luc', '>', now()->subHours(24))->orderBy('id')->chunkById(100, function ($ds) {
                foreach ($ds as $goc) {
                    DB::transaction(function () use ($goc) {
                        // Cùng thứ tự khóa với đổi PT/ghi nhận buổi; đọc lại trạng thái sau khóa.
                        HoSoKhachHang::lockForUpdate()->findOrFail($goc->khach_hang_id);
                        $lich = LichHenHuanLuyen::lockForUpdate()->findOrFail($goc->id);
                        if ($lich->trangThaiHieuLuc() === 'DA_XAC_NHAN' && $lich->ket_thuc_luc->lessThanOrEqualTo(now())) {
                            $this->lichHen($lich, 'can-ghi-nhan');
                        }
                    }, 3);
                }
            });
    }
}
