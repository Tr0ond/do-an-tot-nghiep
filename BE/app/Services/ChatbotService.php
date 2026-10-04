<?php

namespace App\Services;

use App\Models\DangKyGoiTap;
use App\Models\GoiTap;
use App\Models\HoiThoaiTroLy;
use App\Models\HoSoKhachHang;
use App\Models\KeHoachTap;
use App\Models\TaiKhoan;
use App\Models\TinNhanTroLy;
use App\Models\YeuCauTroLy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ChatbotService
{
    public function __construct(private GeminiService $gemini) {}

    public function khachId(TaiKhoan $nguoi): int
    {
        abort_unless($nguoi->vai_tro === TaiKhoan::KHACH_HANG && $nguoi->trang_thai === 'HOAT_DONG', 403);

        return $nguoi->hoSoKhachHang->id;
    }

    public function hoiThoai(int $khachId, int $id): HoiThoaiTroLy
    {
        return HoiThoaiTroLy::where('khach_hang_id', $khachId)->findOrFail($id);
    }

    public function tao(int $khachId, string $uuid): HoiThoaiTroLy
    {
        return DB::transaction(function () use ($khachId, $uuid) {
            HoSoKhachHang::whereKey($khachId)->lockForUpdate()->firstOrFail();

            return HoiThoaiTroLy::firstOrCreate(['khach_hang_id' => $khachId, 'client_request_id' => $uuid], ['tieu_de' => 'Cuộc trò chuyện mới', 'trang_thai' => 'HOAT_DONG']);
        }, 3);
    }

    private function goi(int $khachId): ?DangKyGoiTap
    {
        $moc = now()->format('Y-m-d H:i:s.u');

        return DangKyGoiTap::where('khach_hang_id', $khachId)->where('trang_thai', 'DANG_SU_DUNG')
            ->where('co_chatbot_snapshot', true)->where('kich_hoat_luc', '<=', $moc)->where('het_han_luc', '>', $moc)
            ->where('so_luot_chatbot_moi_ngay_snapshot', '>', 0)->first();
    }

    public function hanMuc(int $khachId): array
    {
        $goi = $this->goi($khachId);
        $ngay = now('Asia/Ho_Chi_Minh')->toDateString();
        $q = YeuCauTroLy::where('khach_hang_id', $khachId)->where('ngay_han_muc', $ngay);
        $daDung = (clone $q)->where('trang_thai', 'THANH_CONG')->count();
        $dangGiu = (clone $q)->where('trang_thai', 'DANG_XU_LY')->where('giu_luot_den', '>', now()->format('Y-m-d H:i:s.u'))->count();
        $toiDa = (int) ($goi?->so_luot_chatbot_moi_ngay_snapshot ?? 0);

        return ['ngay' => $ngay, 'toi_da' => $toiDa, 'da_dung' => $daDung, 'dang_giu' => $dangGiu, 'con_lai' => max(0, $toiDa - $daDung - $dangGiu),
            'co_quyen' => $goi !== null, 'san_sang' => (bool) config('chatbot.key'),
            'ten_goi' => $goi?->ten_goi_snapshot, 'het_han_luc' => $goi?->het_han_luc?->toIso8601String(),
            'cap_lai_luc' => now('Asia/Ho_Chi_Minh')->addDay()->startOfDay()->toIso8601String()];
    }

    public function gui(TaiKhoan $nguoi, int $hoiId, array $d): array
    {
        $khachId = $this->khachId($nguoi);
        $giaoAnAi = app(GiaoAnAiService::class);
        $yeuCauGiaoAn = $giaoAnAi->docYeuCau($d['noi_dung']);
        $hash = hash('sha256', json_encode([$hoiId, $d['noi_dung'], (bool) $d['dung_du_lieu_ca_nhan']], JSON_UNESCAPED_UNICODE));
        $y = DB::transaction(function () use ($khachId, $hoiId, $d, $hash) {
            HoSoKhachHang::whereKey($khachId)->lockForUpdate()->firstOrFail();
            $h = $this->hoiThoai($khachId, $hoiId);
            // Lazy expiry cũng bảo vệ lúc worker/server bị ngắt; fence ngăn lần cũ ghi kết quả muộn.
            YeuCauTroLy::where('khach_hang_id', $khachId)->where('trang_thai', 'DANG_XU_LY')->where('giu_luot_den', '<=', now()->format('Y-m-d H:i:s.u'))
                ->update(['trang_thai' => 'LOI', 'ma_loi' => 'QUA_HAN_XU_LY', 'updated_at' => now()]);
            $cu = YeuCauTroLy::where('khach_hang_id', $khachId)->where('client_request_id', $d['client_request_id'])->lockForUpdate()->first();
            if ($cu) {
                abort_unless(hash_equals((string) $cu->payload_hash, $hash), 409, 'Mã yêu cầu đã dùng cho nội dung khác.');
                if ($cu->trang_thai === 'THANH_CONG') {
                    return $cu;
                }
                abort_if($cu->trang_thai === 'DANG_XU_LY', 409, 'Yêu cầu đang được xử lý. Hãy tải lại cuộc trò chuyện.');
                abort_if($cu->so_lan_thu >= 3, 409, 'Yêu cầu đã thử tối đa 3 lần. Hãy gửi câu hỏi mới.');
            }
            abort_unless($this->goi($khachId), 403, 'Cần gói có chatbot còn hiệu lực.');
            abort_if(YeuCauTroLy::where('khach_hang_id', $khachId)->where('trang_thai', 'DANG_XU_LY')->where('giu_luot_den', '>', now()->format('Y-m-d H:i:s.u'))->exists(), 409, 'Một câu hỏi khác đang được xử lý.');
            $hm = $this->hanMuc($khachId);
            abort_if($hm['con_lai'] < 1, 429, 'Đã dùng hết lượt chatbot hôm nay.');
            abort_unless(config('chatbot.key'), 503, 'Chatbot đang chờ cấu hình. Chưa trừ lượt.');
            $goi = $this->goi($khachId);
            $giaTri = ['hoi_thoai_tro_ly_id' => $hoiId, 'dang_ky_goi_tap_id' => $goi->id, 'ngay_han_muc' => $hm['ngay'],
                'payload_hash' => $hash, 'ma_lan_xu_ly' => (string) Str::uuid(), 'trang_thai' => 'DANG_XU_LY', 'giu_luot_den' => now()->addSeconds(120),
                'ma_loi' => null, 'dung_du_lieu_ca_nhan' => $d['dung_du_lieu_ca_nhan'], 'so_lan_thu' => $cu ? $cu->so_lan_thu + 1 : 1,
                'provider' => 'gemini', 'model' => config('chatbot.model'), 'phien_ban_prompt' => config('chatbot.phien_ban_prompt')];
            $y = $cu ?? new YeuCauTroLy(['khach_hang_id' => $khachId, 'client_request_id' => $d['client_request_id']]);
            $y->fill($giaTri)->save();
            TinNhanTroLy::firstOrCreate(['yeu_cau_tro_ly_id' => $y->id, 'vai_tro' => 'USER'], ['hoi_thoai_tro_ly_id' => $hoiId, 'noi_dung' => $d['noi_dung']]);
            $h->update(['tieu_de' => Str::limit($d['noi_dung'], 80, '…')]);

            return $y;
        }, 3);
        if ($y->trang_thai === 'THANH_CONG') {
            return $this->ketQua($y);
        }
        $batDau = microtime(true);
        try {
            $nguCanh = $this->nguCanh($khachId, $d['noi_dung'], (bool) $d['dung_du_lieu_ca_nhan']);
            if ($yeuCauGiaoAn) {
                $nguCanh['bai_tap'] = $giaoAnAi->ungVien($d['noi_dung']);
                if (count($nguCanh['bai_tap']) < $yeuCauGiaoAn['bai_moi_buoi']) {
                    throw new RuntimeException('KHONG_DU_BAI_TAP');
                }
                $nguCanh['yeu_cau_tao_giao_an'] = $yeuCauGiaoAn;
            }
            $lichSu = TinNhanTroLy::where('hoi_thoai_tro_ly_id', $hoiId)->where('yeu_cau_tro_ly_id', '!=', $y->id)
                ->whereIn('yeu_cau_tro_ly_id', YeuCauTroLy::select('id')->where('trang_thai', 'THANH_CONG')
                    ->when(! $d['dung_du_lieu_ca_nhan'], fn ($q) => $q->where('dung_du_lieu_ca_nhan', false)))
                ->orderByDesc('id')->limit(8)->get(['vai_tro', 'noi_dung'])->reverse()->values()->toArray();
            // Không giữ transaction khi chờ mạng; lần hiện tại nhận một lease riêng.
            $r = $this->gemini->traLoi($nguCanh, $lichSu, $d['noi_dung']);
            $traLoi = $r['tra_loi'];
            $duLieuGiaoAn = null;
            if ($yeuCauGiaoAn) {
                if (! is_array($traLoi) || ! array_key_exists('giao_an_de_xuat', $traLoi)) {
                    throw new RuntimeException('THIEU_GIAO_AN');
                }
                $duLieuGiaoAn = $giaoAnAi->kiemTra($traLoi['giao_an_de_xuat'], $yeuCauGiaoAn, $nguCanh['bai_tap']);
                unset($traLoi['giao_an_de_xuat']);
            }
            $nguon = $this->kiemTra($traLoi, $nguCanh);
            DB::transaction(function () use ($nguoi, $khachId, $y, $r, $nguon, $batDau, $giaoAnAi, $duLieuGiaoAn, $yeuCauGiaoAn) {
                HoSoKhachHang::whereKey($khachId)->lockForUpdate()->firstOrFail();
                $cu = YeuCauTroLy::whereKey($y->id)->lockForUpdate()->firstOrFail();
                if ($cu->trang_thai !== 'DANG_XU_LY' || $cu->ma_lan_xu_ly !== $y->ma_lan_xu_ly || $cu->giu_luot_den->lessThanOrEqualTo(now())) {
                    throw new RuntimeException('QUA_HAN_XU_LY');
                }
                if (TaiKhoan::findOrFail($nguoi->id)->trang_thai !== 'HOAT_DONG') {
                    throw new RuntimeException('TAI_KHOAN_KHONG_HOP_LE');
                }
                $noiDung = $r['tra_loi']['noi_dung'];
                if ($duLieuGiaoAn) {
                    // Cùng transaction với assistant và lượt; lỗi ghi không để nháp AI mồ côi.
                    $nguon['giao_an_da_tao'] = $giaoAnAi->luu($nguoi, $y->client_request_id, $duLieuGiaoAn, $yeuCauGiaoAn);
                    $noiDung .= "\n\nĐã lưu giáo án nháp vào Giáo án của tôi. Bạn có thể xem, sửa và tự áp dụng khi phù hợp.";
                }
                TinNhanTroLy::create(['hoi_thoai_tro_ly_id' => $y->hoi_thoai_tro_ly_id, 'yeu_cau_tro_ly_id' => $y->id, 'vai_tro' => 'ASSISTANT',
                    'noi_dung' => $noiDung, 'nguon_da_kiem_tra' => $nguon]);
                $cu->update(['trang_thai' => 'THANH_CONG', 'hoan_thanh_luc' => now(), 'giu_luot_den' => null,
                    'input_tokens' => $r['input_tokens'], 'output_tokens' => $r['output_tokens'], 'do_tre_ms' => (int) ((microtime(true) - $batDau) * 1000)]);
            }, 3);
        } catch (Throwable $e) {
            // Không log raw exception HTTP: có thể chứa key, prompt hoặc response riêng tư.
            YeuCauTroLy::whereKey($y->id)->where('ma_lan_xu_ly', $y->ma_lan_xu_ly)->where('trang_thai', 'DANG_XU_LY')
                ->update(['trang_thai' => 'LOI', 'ma_loi' => 'KHONG_CO_KET_QUA_HOP_LE', 'giu_luot_den' => null, 'updated_at' => now()]);
            abort(503, 'Tr0ond AI chưa thể trả lời. Lượt của bạn được giữ nguyên; hãy thử lại hoặc xem tài liệu bên dưới.');
        }

        return $this->ketQua($y->fresh());
    }

    private function ketQua(YeuCauTroLy $y): array
    {
        $tin = TinNhanTroLy::where('yeu_cau_tro_ly_id', $y->id)->orderBy('id')->get(['id', 'vai_tro', 'noi_dung', 'nguon_da_kiem_tra', 'created_at']);
        foreach ($tin as $t) {
            if ($t->nguon_da_kiem_tra) {
                $t->nguon_da_kiem_tra = $this->nguonHienTai($t->nguon_da_kiem_tra, $y->khach_hang_id);
            }
        }

        return ['yeu_cau_id' => $y->id, 'tin_nhan' => $tin];
    }

    public function nguonHienTai(array $nguon, ?int $khachId = null): array
    {
        // Giữ snapshot trong DB, nhưng thẻ lịch sử không quảng bá giá/nguồn đã thu hồi.
        $kq = [];
        foreach (['goi_tap', 'giao_an_mau', 'bai_tap', 'tai_lieu'] as $loai) {
            $kq[$loai] = $this->docNguon($loai, array_column($nguon[$loai] ?? [], 'id'));
            if ($loai === 'tai_lieu') {
                $cu = array_column($nguon[$loai] ?? [], null, 'id');
                $kq[$loai] = array_values(array_filter($kq[$loai], fn ($x) => ($cu[$x['id']] ?? null) === $x));
            }
        }
        $kq['chinh_sach'] = $nguon['chinh_sach'] ?? null;
        if ($khachId && isset($nguon['giao_an_da_tao'])) {
            $kq['giao_an_da_tao'] = app(GiaoAnAiService::class)->hienTai($nguon['giao_an_da_tao'], $khachId);
        }

        return $kq;
    }

    private function docNguon(string $loai, array $ids): array
    {
        return match ($loai) {
            'goi_tap' => GoiTap::dangHienThi()->whereIn('id', $ids)->get(['id', ...GoiTap::THUOC_TINH])->toArray(),
            'giao_an_mau' => DB::table('giao_an_mau')->whereIn('id', $ids)->where('trang_thai', 'DA_DUYET')->get(['id', 'ten_giao_an', 'muc_tieu', 'so_ngay_tap'])->map(fn ($x) => (array) $x)->all(),
            'bai_tap' => DB::table('bai_tap')->join('nhom_co', 'nhom_co.id', '=', 'bai_tap.nhom_co_id')->whereIn('bai_tap.id', $ids)->where('bai_tap.trang_thai', 'HOAT_DONG')->where('nhom_co.trang_thai', 'HOAT_DONG')->get(['bai_tap.id', 'ten_bai_tap', 'ten_nhom_co', 'dung_cu'])->map(fn ($x) => (array) $x)->all(),
            'tai_lieu' => DB::table('tai_lieu_tu_van')->whereIn('id', $ids)->where('trang_thai', 'DA_XUAT_BAN')->whereNotNull('xuat_ban_luc')->where('xuat_ban_luc', '<=', now())->get(['id', 'tieu_de', 'noi_dung', 'phien_ban'])->map(fn ($x) => ['id' => $x->id, 'tieu_de' => $x->tieu_de, 'noi_dung' => mb_substr($x->noi_dung, 0, 2000), 'phien_ban' => $x->phien_ban])->all(),
        };
    }

    public function nguCanh(int $khachId, string $cauHoi, bool $caNhan): array
    {
        $goi = GoiTap::dangHienThi()->orderBy('id')->limit(8)->get(['id', ...GoiTap::THUOC_TINH])->toArray();
        $mau = DB::table('giao_an_mau')->where('trang_thai', 'DA_DUYET')->orderBy('id')->limit(8)->get(['id', 'ten_giao_an', 'muc_tieu', 'so_ngay_tap'])->map(fn ($x) => (array) $x)->all();
        $tu = array_slice(array_filter(preg_split('/\s+/u', preg_replace('/[^\p{L}\p{N}\s]/u', '', $cauHoi)), fn ($t) => mb_strlen($t) >= 3), 0, 6);
        $bai = DB::table('bai_tap')->join('nhom_co', 'nhom_co.id', '=', 'bai_tap.nhom_co_id')->where('bai_tap.trang_thai', 'HOAT_DONG')->where('nhom_co.trang_thai', 'HOAT_DONG');
        if ($tu) {
            $bai->where(function ($q) use ($tu) {
                foreach ($tu as $t) {
                    $q->orWhere('ten_bai_tap', 'like', '%'.$t.'%')->orWhere('ten_nhom_co', 'like', '%'.$t.'%');
                }
            });
        }
        $bai = $bai->orderBy('bai_tap.id')->limit(8)->get(['bai_tap.id', 'ten_bai_tap', 'ten_nhom_co', 'dung_cu'])->map(fn ($x) => (array) $x)->all();
        $taiLieu = DB::table('tai_lieu_tu_van')->where('trang_thai', 'DA_XUAT_BAN')->whereNotNull('xuat_ban_luc')->where('xuat_ban_luc', '<=', now())->orderByDesc('id')->limit(5)
            ->get(['id', 'tieu_de', 'noi_dung', 'phien_ban'])->map(fn ($x) => ['id' => $x->id, 'tieu_de' => $x->tieu_de, 'noi_dung' => mb_substr($x->noi_dung, 0, 2000), 'phien_ban' => $x->phien_ban])->all();
        $n = ['goi_tap' => $goi, 'giao_an_mau' => $mau, 'bai_tap' => $bai, 'tai_lieu' => $taiLieu,
            'chinh_sach' => 'KH được tự tạo và áp dụng giáo án miễn phí, không cần mua gói hoặc có PT; mỗi KH một giáo án đang dùng chung cả nguồn PT và tự tạo. '
                .'PT hiện phụ trách được đọc giáo án KH tự tạo, kể cả nháp, không sửa hoặc áp dụng thay KH. KH được ngừng giáo án PT đã nhận, không cần PT duyệt; có thể áp dụng lại bản PT đã xác nhận và đang lưu trữ. '
                .'KH được ẩn/hiện lại bản tự tạo đã hủy hoặc lưu trữ, không xóa lịch sử. KH tự lên lịch và ghi nhật ký từ giáo án đang dùng; tự tập không trừ lượt PT. '
                .'Chat riêng KH-PT theo phân công và không trừ buổi PT, kể cả hết gói. Khi đổi PT, KH giữ chat cũ, PT cũ mất quyền, PT mới không đọc chat cũ. '
                .'Chatbot AI là chức năng riêng, cần gói có quyền chatbot còn hạn và lượt ngày; hết buổi PT vẫn dùng AI nếu quyền chatbot và thời hạn còn hiệu lực. FAQ/catalog đọc miễn phí. '
                .'Lượt AI cấp lại 00:00 giờ Việt Nam; lỗi không mất lượt, gửi lại cùng UUID không tính hai lần. Hết gói không được gửi câu hỏi AI mới, vẫn đọc giáo án/lịch sử và ghi nhật ký tự tập hợp lệ. '
                .'Lịch PT dài 60 phút, đặt trước ít nhất4 giờ, KH hủy trước ít nhất2 giờ. Giáo án PT gửi mới cần KH xác nhận trong 24 giờ; không cần xác nhận lại bản đã nhận khi áp dụng lại.'];
        if ($caNhan) {
            $kh = HoSoKhachHang::findOrFail($khachId);
            $k = KeHoachTap::where('khach_hang_id', $khachId)->where('trang_thai', 'DANG_AP_DUNG')->with(['cacBaiTap' => fn ($q) => $q->limit(20)])->first();
            $n['ca_nhan'] = ['muc_tieu' => $kh->muc_tieu, 'kinh_nghiem' => $kh->kinh_nghiem,
                'chi_so_co_the' => app(ChiSoCoTheService::class)->nguCanhAi($khachId),
                'giao_an' => $k ? ['ten' => $k->ten_ke_hoach, 'muc_tieu' => $k->muc_tieu, 'so_ngay' => $k->so_ngay_tap,
                    'bai_tap' => $k->cacBaiTap->map(fn ($b) => ['ten' => $b->ten_bai_tap_snapshot, 'ngay' => $b->ngay_thu, 'hiep' => $b->so_hiep, 'lan' => $b->so_lan_lap, 'ta_kg' => $b->muc_ta_kg])->all()] : null,
                'buoi_hoan_thanh_30_ngay' => DB::table('lich_tap')->where('khach_hang_id', $khachId)->where('trang_thai', 'HOAN_THANH')->where('ngay_tap', '>=', now('Asia/Ho_Chi_Minh')->subDays(30)->toDateString())->count()];
        }

        return $n;
    }

    private function kiemTra(mixed $r, array $n): array
    {
        $truong = ['noi_dung', 'goi_tap_ids', 'giao_an_mau_ids', 'bai_tap_ids', 'nguon_tai_lieu_ids'];
        if (! is_array($r) || count(array_diff($truong, array_keys($r))) || count(array_diff(array_keys($r), $truong))
            || ! is_string($r['noi_dung']) || trim($r['noi_dung']) === '' || mb_strlen($r['noi_dung']) > 6000
            || preg_match('/<[^>]+>|https?:\/\/|\b(?:VND|USD)\b|\d[\d.,\s]*\s*(?:đồng|vnđ|₫)/iu', $r['noi_dung'])) {
            throw new RuntimeException('KET_QUA_KHONG_HOP_LE');
        }
        $ketQua = [];
        foreach (['goi_tap_ids' => 'goi_tap', 'giao_an_mau_ids' => 'giao_an_mau', 'bai_tap_ids' => 'bai_tap', 'nguon_tai_lieu_ids' => 'tai_lieu'] as $ids => $loai) {
            if (! is_array($r[$ids]) || ! array_is_list($r[$ids]) || count($r[$ids]) > 3) {
                throw new RuntimeException('ID_KHONG_HOP_LE');
            }
            foreach ($r[$ids] as $id) {
                if (! is_int($id) || ! in_array($id, array_column($n[$loai], 'id'), true)) {
                    throw new RuntimeException('ID_KHONG_HOP_LE');
                }
            }
            $hienTai = $this->docNguon($loai, $r[$ids]);
            if (array_diff($r[$ids], array_column($hienTai, 'id'))) {
                throw new RuntimeException('NGUON_DA_THAY_DOI');
            }
            if ($loai === 'tai_lieu') {
                $cu = array_column($n[$loai], null, 'id');
                foreach ($hienTai as $x) {
                    if (($cu[$x['id']] ?? null) !== $x) {
                        throw new RuntimeException('TAI_LIEU_DA_THAY_DOI');
                    }
                }
            }
            $ketQua[$loai] = array_values(array_filter($hienTai, fn ($x) => in_array($x['id'], $r[$ids], true)));
        }
        // Nguồn tài liệu hiển thị chỉ giữ nội dung đã xuất bản; không nhận URL do AI tạo.
        $ketQua['chinh_sach'] = ['tieu_de' => 'Quy tắc hệ thống Tr0ond', 'phien_ban' => 2];

        return $ketQua;
    }
}
