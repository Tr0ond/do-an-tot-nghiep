<?php

namespace App\Services;

use App\Models\TaiKhoan;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\PersonalAccessToken;
use Ramsey\Uuid\Uuid;

class ThongBaoDayService
{
    public function xepHang(int $taiKhoanId, string $suKien, string $duongDan, string $loai): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Push phải ghi cùng transaction nghiệp vụ.');
        }
        foreach (DB::table('thiet_bi_push')->where('tai_khoan_id', $taiKhoanId)->where('da_bat', true)->get() as $d) {
            $id = (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'fitforge/push/'.$suKien.'/'.$d->id.'/'.$d->phien_ban);
            DB::table('hang_doi_push')->upsert([['id' => $id, 'thiet_bi_id' => $d->id, 'phien_ban' => $d->phien_ban, 'duong_dan' => $duongDan, 'loai' => $loai, 'trang_thai' => 'CHO_GUI', 'so_lan' => 0, 'thu_lai_luc' => now(), 'created_at' => now(), 'updated_at' => now()]], ['id'], ['id']);
        }
    }

    private function thietBi(object $hang): ?object
    {
        $d = DB::table('thiet_bi_push')->where('id', $hang->thiet_bi_id)->where('da_bat', true)->where('phien_ban', $hang->phien_ban)->first();
        if (! $d) {
            return null;
        }
        $phien = PersonalAccessToken::find($d->phien_id);
        $nguoi = $phien?->tokenable;
        if (! $nguoi instanceof TaiKhoan || $nguoi->id !== $d->tai_khoan_id || $nguoi->trang_thai !== TaiKhoan::HOAT_DONG || $phien->abilities !== ['mobile'] || ! $phien->expires_at?->isFuture() || ! is_string($phien->dau_phien_dang_nhap) || ! hash_equals($nguoi->dauPhienDangNhap(), $phien->dau_phien_dang_nhap)) {
            return null;
        }
        if ($hang->loai === 'chat' && preg_match('~^/hoi-thoai/([1-9][0-9]*)$~D', $hang->duong_dan, $m)) {
            $hoi = DB::table('hoi_thoai as h')->join('phan_cong_huan_luyen_vien as p', 'p.id', '=', 'h.phan_cong_id')->where('h.id', $m[1])->whereNull('p.ket_thuc_luc')->first(['p.khach_hang_id', 'p.huan_luyen_vien_id']);
            if (! $hoi || ! DB::table($nguoi->vai_tro === TaiKhoan::KHACH_HANG ? 'ho_so_khach_hang' : 'ho_so_huan_luyen_vien')->where('id', $nguoi->vai_tro === TaiKhoan::KHACH_HANG ? $hoi->khach_hang_id : $hoi->huan_luyen_vien_id)->where('tai_khoan_id', $nguoi->id)->exists()) {
                return null;
            }
        } elseif ($hang->loai === 'lich' && preg_match('~^/(khach-hang|pt)/lich-hen/([1-9][0-9]*)$~D', $hang->duong_dan, $m)) {
            if (! app(LichHenService::class)->phamVi($nguoi)->whereKey($m[2])->exists()) {
                return null;
            }
        } else {
            return null;
        }

        return $d;
    }

    public function xuLy(): int
    {
        if (! config('push.enabled')) {
            return 0;
        }
        $khoa = Cache::lock('fitforge-push-worker', 600);
        if (! $khoa->get()) {
            return 0;
        }
        try {
            $so = 0;
            foreach (DB::table('hang_doi_push')->whereIn('trang_thai', ['CHO_GUI', 'CHO_RECEIPT'])->where('thu_lai_luc', '<=', now())->orderBy('thu_lai_luc')->limit(50)->get() as $hang) {
                $d = $this->thietBi($hang);
                if (! $d || now()->diffInHours($hang->created_at, true) >= 24) {
                    $this->capNhat($hang, ['trang_thai' => 'HUY']);

                    continue;
                }
                try {
                    // Không theo redirect và không ghi token/body của provider vào log.
                    $http = Http::acceptJson()->asJson()->withOptions(['allow_redirects' => false])->connectTimeout(3)->timeout(7);
                    if (filled(config('push.access_token'))) {
                        $http = $http->withToken(config('push.access_token'));
                    }
                    $receipt = $hang->trang_thai === 'CHO_RECEIPT';
                    $r = $http->post('https://exp.host/--/api/v2/push/'.($receipt ? 'getReceipts' : 'send'), $receipt ? ['ids' => [$hang->ticket_id]] : [
                        'to' => $d->expo_token, 'title' => 'FitForge', 'body' => $hang->loai === 'chat' ? 'Bạn có tin nhắn mới. Mở app để xem.' : 'Lịch hẹn của bạn có cập nhật. Mở app để xem.',
                        'sound' => 'default', 'channelId' => 'fitforge', 'ttl' => 3600,
                        'data' => ['tai_khoan_id' => $d->tai_khoan_id, 'duong_dan' => $hang->duong_dan, 'su_kien_id' => $hang->id],
                    ]);
                    if (! $r->successful()) {
                        if ($r->status() === 429 || $r->serverError()) {
                            $this->thuLai($hang, 'HTTP_RETRY');
                        } else {
                            $this->capNhat($hang, ['trang_thai' => 'LOI', 'ma_loi' => 'HTTP_CONFIG']);
                        }

                        continue;
                    }
                    $ketQua = $receipt ? $r->json('data.'.$hang->ticket_id) : $r->json('data');
                    if (($ketQua['status'] ?? '') === 'ok') {
                        if (! $receipt && ! is_string($ketQua['id'] ?? null)) {
                            $this->thuLai($hang, 'RESPONSE_INVALID');

                            continue;
                        }
                        $this->capNhat($hang, ['trang_thai' => $receipt ? 'DA_GUI' : 'CHO_RECEIPT', 'ticket_id' => $receipt ? $hang->ticket_id : $ketQua['id'], 'thu_lai_luc' => now()->addMinutes(15), 'ma_loi' => null, 'so_lan' => 0]);
                        $so++;
                    } elseif (($ketQua['details']['error'] ?? '') === 'DeviceNotRegistered') {
                        // Chỉ tắt phiên bản đã gửi, không tắt đăng ký mới cùng thiết bị.
                        DB::table('thiet_bi_push')->where('id', $d->id)->where('phien_ban', $hang->phien_ban)->update(['da_bat' => false, 'updated_at' => now()]);
                        $this->capNhat($hang, ['trang_thai' => 'LOI', 'ma_loi' => 'DeviceNotRegistered']);
                    } elseif (($ketQua['status'] ?? '') === 'error') {
                        if (($ketQua['details']['error'] ?? '') === 'MessageRateExceeded') {
                            $this->thuLai($hang, 'RATE_LIMIT');
                        } else {
                            $this->capNhat($hang, ['trang_thai' => 'LOI', 'ma_loi' => 'PROVIDER_CONFIG']);
                        }
                    } else {
                        $this->thuLai($hang, 'RESPONSE_PENDING');
                    }
                } catch (ConnectionException) {
                    $this->thuLai($hang, 'NETWORK');
                }
            }

            return $so;
        } finally {
            $khoa->release();
        }
    }

    private function thuLai(object $hang, string $loi): void
    {
        $lan = $hang->so_lan + 1;
        $this->capNhat($hang, ['so_lan' => $lan, 'trang_thai' => $lan >= 8 ? 'LOI' : $hang->trang_thai, 'ma_loi' => $loi, 'thu_lai_luc' => now()->addSeconds(min(3600, 30 * 2 ** $lan))]);
    }

    private function capNhat(object $hang, array $duLieu): void
    {
        DB::table('hang_doi_push')->where('id', $hang->id)->update([...$duLieu, 'updated_at' => now()]);
    }
}
