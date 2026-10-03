<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaiLieuTuVanRequest;
use App\Models\TaiLieuTuVan;
use App\Models\YeuCauTroLy;
use App\Services\TaiLieuTuVanService;

class TaiLieuTuVanController extends Controller
{
    public function index(TaiLieuTuVanRequest $r)
    {
        return $this->danhSach($r, false);
    }

    public function faq(TaiLieuTuVanRequest $r)
    {
        return $this->danhSach($r, true);
    }

    private function danhSach(TaiLieuTuVanRequest $r, bool $congKhai)
    {
        $d = $r->validated();
        $q = TaiLieuTuVan::query();
        if ($congKhai) {
            $q->where('trang_thai', 'DA_XUAT_BAN')->whereNotNull('xuat_ban_luc')->where('xuat_ban_luc', '<=', now()->format('Y-m-d H:i:s.u'));
        } elseif (! empty($d['trang_thai'])) {
            $q->where('trang_thai', $d['trang_thai']);
        }
        if (! empty($d['tu_khoa'])) {
            $tu = '%'.str_replace(['=', '%', '_'], ['==', '=%', '=_'], $d['tu_khoa']).'%';
            $q->whereRaw("tieu_de LIKE ? ESCAPE '='", [$tu]);
        }
        $p = $q->orderByDesc('id')->paginate(12, $congKhai ? ['id', 'tieu_de', 'loai', 'noi_dung', 'phien_ban', 'xuat_ban_luc'] : ['*']);

        return response()->json(['status' => true, 'message' => 'Đã tải tài liệu.', 'data' => $p->items(), 'meta' => ['page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'total' => $p->total()]]);
    }

    public function store(TaiLieuTuVanRequest $r, TaiLieuTuVanService $s)
    {
        $t = $s->tao($r->user(), $r->validated());

        return $this->phanHoi($t, $t->wasRecentlyCreated ? 201 : 200);
    }

    public function update(TaiLieuTuVanRequest $r, int $id, TaiLieuTuVanService $s)
    {
        return $this->phanHoi($s->sua($r->user(), $id, $r->validated()));
    }

    public function thaoTac(TaiLieuTuVanRequest $r, int $id, string $hanhDong, TaiLieuTuVanService $s)
    {
        return $this->phanHoi($s->sua($r->user(), $id, $r->validated(), $hanhDong));
    }

    public function thongKe(TaiLieuTuVanRequest $r)
    {
        $ngay = now('Asia/Ho_Chi_Minh')->toDateString();
        // Chỉ dữ liệu tổng hợp; không trả ID KH, câu hỏi hoặc phản hồi riêng tư cho Admin.
        $q = YeuCauTroLy::where('ngay_han_muc', $ngay);
        $d = ['ngay' => $ngay, 'yeu_cau' => (clone $q)->count(), 'thanh_cong' => (clone $q)->where('trang_thai', 'THANH_CONG')->count(), 'loi' => (clone $q)->where('trang_thai', 'LOI')->count(), 'input_tokens' => (int) (clone $q)->sum('input_tokens'), 'output_tokens' => (int) (clone $q)->sum('output_tokens')];

        return response()->json(['status' => true, 'message' => 'Đã tải thống kê AI.', 'data' => $d])->header('Cache-Control', 'private, no-store');
    }

    private function phanHoi(TaiLieuTuVan $t, int $status = 200)
    {
        return response()->json(['status' => true, 'message' => 'Đã lưu tài liệu.', 'data' => $t], $status);
    }
}
