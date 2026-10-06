<?php

namespace App\Services;

use App\Models\KeHoachTap;
use App\Models\NhomCo;
use App\Models\TaiKhoan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class GiaoAnAiService
{
    public function docYeuCau(string $noiDung): ?array
    {
        $chu = strtolower(Str::ascii($noiDung));
        $lenh = preg_match('/(?:tao|lap|soan|thiet ke).{0,60}(?:giao an|ke hoach tap)/', $chu)
            && (preg_match('/^(?:hay |ban |vui long )*(?:tao|lap|soan|thiet ke)/', $chu)
                || preg_match('/(?:cho toi|cho minh|giup toi|giup minh|toi muon|hay tao|hay lap)/', $chu));
        if (! $lenh || preg_match('/(?:khong|dung)\s+(?:tu\s+)?(?:tao|lap|soan)/', $chu)) {
            return null;
        }
        if (! preg_match('/(-?\d+)\s*buoi\s*(?:\/|moi|mot|1|tren|trong (?:mot|1))?\s*tuan/', $chu, $buoi)
            || ! preg_match_all('/(-?\d+)\s*tuan/', $chu, $tuan)
            || ! preg_match('/(?:moi buoi\s*(?:co|gom)?\s*(-?\d+)\s*bai|(-?\d+)\s*bai\s*(?:\/|moi)\s*buoi)/', $chu, $bai)) {
            return null;
        }
        $d = ['buoi_moi_tuan' => (int) $buoi[1], 'so_tuan' => (int) end($tuan[1]), 'bai_moi_buoi' => (int) (($bai[1] ?? '') !== '' ? $bai[1] : $bai[2])];
        Validator::make($d, ['buoi_moi_tuan' => 'required|integer|between:1,7', 'so_tuan' => 'required|integer|between:1,30', 'bai_moi_buoi' => 'required|integer|between:1,8'],
            ['between' => 'Giáo án AI cần 1–7 buổi/tuần, 1–8 bài/buổi và tối đa 30 buổi.'])->validate();
        if ($d['buoi_moi_tuan'] * $d['so_tuan'] > 30 || array_product($d) > 120) {
            throw ValidationException::withMessages(['noi_dung' => 'Mỗi lần tạo AI tối đa 30 buổi và 120 bài. Hãy chia thành giáo án ngắn hơn.']);
        }

        return $d;
    }

    public function ungVien(string $cauHoi): array
    {
        $taiNha = preg_match('/tai nha|khong ta|khong dung cu/', strtolower(Str::ascii($cauHoi)));
        $bai = [];
        // Lấy ứng viên từ nhiều nhóm cơ, tránh catalog theo tên dồn toàn bộ vào cơ bụng.
        foreach (NhomCo::where('trang_thai', 'HOAT_DONG')->orderBy('id')->limit(20)->pluck('id') as $nhomId) {
            $q = DB::table('bai_tap')->join('nhom_co', 'nhom_co.id', '=', 'bai_tap.nhom_co_id')
                ->where('bai_tap.nhom_co_id', $nhomId)->where('bai_tap.trang_thai', 'HOAT_DONG');
            if ($taiNha) {
                $q->where('dung_cu', 'Trọng lượng cơ thể');
            }
            foreach ($q->orderBy('bai_tap.id')->limit(4)->get(['bai_tap.id', 'ten_bai_tap', 'ten_nhom_co', 'dung_cu']) as $x) {
                $bai[] = (array) $x;
            }
        }

        return $bai;
    }

    public function kiemTra(mixed $deXuat, array $yeuCau, array $ungVien): ?array
    {
        // Model được hỏi bổ sung thay vì tạo khi thiếu thông tin tập luyện phù hợp.
        if ($deXuat === null) {
            return null;
        }
        if (! is_array($deXuat)) {
            throw new RuntimeException('GIAO_AN_KHONG_HOP_LE');
        }
        Validator::make(['giao_an' => $deXuat], [
            'giao_an' => 'required|array:ten_ke_hoach,muc_tieu,buoi_tap',
            'giao_an.ten_ke_hoach' => 'required|string|max:180', 'giao_an.muc_tieu' => 'required|string|max:180',
            'giao_an.buoi_tap' => 'required|array|size:'.($yeuCau['buoi_moi_tuan'] * $yeuCau['so_tuan']),
            'giao_an.buoi_tap.*' => 'required|array:ghi_chu,bai_tap',
            'giao_an.buoi_tap.*.ghi_chu' => 'present|nullable|string|max:500',
            'giao_an.buoi_tap.*.bai_tap' => 'required|array|size:'.$yeuCau['bai_moi_buoi'],
            'giao_an.buoi_tap.*.bai_tap.*' => 'required|array:id,hiep,lan,nghi',
            'giao_an.buoi_tap.*.bai_tap.*.id' => 'required|integer|min:1',
            'giao_an.buoi_tap.*.bai_tap.*.hiep' => 'required|integer|between:1,10',
            'giao_an.buoi_tap.*.bai_tap.*.lan' => 'required|integer|between:1,100',
            'giao_an.buoi_tap.*.bai_tap.*.nghi' => 'required|integer|between:0,600',
        ])->validate();
        $cacBai = [];
        $ids = array_column($ungVien, 'id');
        if (! array_is_list($deXuat['buoi_tap'])) {
            throw new RuntimeException('GIAO_AN_KHONG_HOP_LE');
        }
        foreach ($deXuat['buoi_tap'] as $ngay => $buoi) {
            if (! array_is_list($buoi['bai_tap']) || count(array_unique(array_column($buoi['bai_tap'], 'id'))) !== count($buoi['bai_tap'])) {
                throw new RuntimeException('BAI_TAP_TRUNG');
            }
            foreach ($buoi['bai_tap'] as $thuTu => $b) {
                if (array_filter($b, fn ($v) => ! is_int($v)) || ! in_array($b['id'], $ids, true)) {
                    throw new RuntimeException('BAI_TAP_NGOAI_NGUON');
                }
                $cacBai[] = ['bai_tap_id' => $b['id'], 'ngay_thu' => $ngay + 1, 'thu_tu' => $thuTu + 1,
                    'so_hiep' => $b['hiep'], 'so_lan_lap' => $b['lan'], 'nghi_giay' => $b['nghi'], 'muc_ta_kg' => null,
                    'ghi_chu' => 'Tuần '.(intdiv($ngay, $yeuCau['buoi_moi_tuan']) + 1).' · Buổi '.($ngay % $yeuCau['buoi_moi_tuan'] + 1).': '.($buoi['ghi_chu'] ?? '')];
            }
        }
        foreach ([$deXuat['ten_ke_hoach'], $deXuat['muc_tieu'], ...array_column($cacBai, 'ghi_chu')] as $chu) {
            if (preg_match('/<[^>]+>|https?:\/\//iu', $chu)) {
                throw new RuntimeException('NOI_DUNG_KHONG_HOP_LE');
            }
        }

        return ['ten_ke_hoach' => $deXuat['ten_ke_hoach'], 'muc_tieu' => $yeuCau['so_tuan'].' tuần · '.$yeuCau['buoi_moi_tuan'].' buổi/tuần · '.$deXuat['muc_tieu'],
            'so_ngay_tap' => $yeuCau['so_tuan'] * $yeuCau['buoi_moi_tuan'], 'giao_an_mau_id' => null, 'bai_tap' => $cacBai];
    }

    public function luu(TaiKhoan $nguoi, string $uuid, array $duLieu, array $yeuCau): array
    {
        $khachId = $nguoi->hoSoKhachHang->id;
        // Giữ namespace đã dùng để retry sau đổi thương hiệu trả cùng giáo án.
        $duLieu['client_request_id'] = (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'tr0ond/giao-an-ai/'.$khachId.'/'.$uuid);
        $k = app(KeHoachTapService::class)->tao($nguoi, $khachId, $duLieu);

        return ['id' => $k->id, 'ten_ke_hoach' => $k->ten_ke_hoach, ...$yeuCau,
            'buoi_tap' => $k->cacBaiTap->groupBy('ngay_thu')->map(function ($bai, $ngay) use ($yeuCau) {
                return ['tuan' => intdiv($ngay - 1, $yeuCau['buoi_moi_tuan']) + 1, 'buoi' => ($ngay - 1) % $yeuCau['buoi_moi_tuan'] + 1,
                    'bai_tap' => $bai->map(fn ($b) => ['id' => $b->bai_tap_id, 'ten' => $b->ten_bai_tap_snapshot,
                        'anh_url' => $b->noi_dung_snapshot['anh_url'] ?? null, 'hiep' => $b->so_hiep, 'lan' => $b->so_lan_lap,
                        'nghi' => $b->nghi_giay, 'ghi_chu' => $b->ghi_chu])->values()->all()];
            })->values()->all()];
    }

    public function hienTai(array $daTao, int $khachId): ?array
    {
        $k = KeHoachTap::where('khach_hang_id', $khachId)->where('nguon_tao', 'KHACH_HANG')->find($daTao['id']);
        if (! $k) {
            return null;
        }

        return [...$daTao, 'ten_ke_hoach' => $k->ten_ke_hoach, 'trang_thai' => $k->trang_thai, 'da_an' => $k->khach_an_luc !== null];
    }
}
