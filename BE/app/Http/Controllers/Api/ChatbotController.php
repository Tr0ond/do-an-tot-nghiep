<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatbotRequest;
use App\Models\HoiThoaiTroLy;
use App\Models\TinNhanTroLy;
use App\Services\ChatbotService;

class ChatbotController extends Controller
{
    public function index(ChatbotRequest $r, ChatbotService $s)
    {
        $kh = $s->khachId($r->user());
        $p = HoiThoaiTroLy::where('khach_hang_id', $kh)->orderByDesc('updated_at')->orderByDesc('id')->paginate(12, ['id', 'tieu_de', 'updated_at']);

        return $this->phanHoi($p->items(), ['han_muc' => $s->hanMuc($kh), 'page' => $p->currentPage(), 'last_page' => $p->lastPage()]);
    }

    public function store(ChatbotRequest $r, ChatbotService $s)
    {
        $h = $s->tao($s->khachId($r->user()), $r->validated('client_request_id'));

        return $this->phanHoi(['id' => $h->id, 'tieu_de' => $h->tieu_de], null, $h->wasRecentlyCreated ? 201 : 200);
    }

    public function show(ChatbotRequest $r, int $id, ChatbotService $s)
    {
        $kh = $s->khachId($r->user());
        $h = $s->hoiThoai($kh, $id);
        $p = TinNhanTroLy::where('tin_nhan_tro_ly.hoi_thoai_tro_ly_id', $id)->join('yeu_cau_tro_ly', 'yeu_cau_tro_ly.id', '=', 'tin_nhan_tro_ly.yeu_cau_tro_ly_id')
            ->orderByDesc('tin_nhan_tro_ly.id')->paginate(20, ['tin_nhan_tro_ly.id', 'vai_tro', 'noi_dung', 'nguon_da_kiem_tra', 'tin_nhan_tro_ly.created_at', 'client_request_id', 'trang_thai', 'dung_du_lieu_ca_nhan', 'so_lan_thu', 'giu_luot_den']);
        $d = collect($p->items())->map(function ($t) use ($s, $kh) {
            $d = $t->toArray();
            if ($t->trang_thai === 'DANG_XU_LY' && $t->giu_luot_den && now()->greaterThanOrEqualTo($t->giu_luot_den)) {
                $d['trang_thai'] = 'LOI';
            }
            unset($d['giu_luot_den']);
            $d['co_the_thu_lai'] = $t->vai_tro === 'USER' && $d['trang_thai'] === 'LOI' && $t->so_lan_thu < 3;
            if ($t->nguon_da_kiem_tra) {
                $d['nguon_da_kiem_tra'] = $s->nguonHienTai($t->nguon_da_kiem_tra, $kh);
            }

            return $d;
        })->reverse()->values();

        return $this->phanHoi($d, ['hoi_thoai' => ['id' => $h->id, 'tieu_de' => $h->tieu_de], 'han_muc' => $s->hanMuc($kh), 'page' => $p->currentPage(), 'last_page' => $p->lastPage()]);
    }

    public function gui(ChatbotRequest $r, int $id, ChatbotService $s)
    {
        return $this->phanHoi($s->gui($r->user(), $id, $r->validated()), ['han_muc' => $s->hanMuc($s->khachId($r->user()))]);
    }

    private function phanHoi(mixed $d, ?array $meta = null, int $http = 200)
    {
        return response()->json(['status' => true, 'message' => 'Đã tải dữ liệu FitForge AI.', 'data' => $d, 'meta' => $meta], $http)->header('Cache-Control', 'private, no-store');
    }
}
