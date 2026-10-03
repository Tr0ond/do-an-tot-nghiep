<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ThongBaoRequest;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class ThongBaoController extends Controller
{
    public function index(ThongBaoRequest $request)
    {
        // Quan hệ Notifiable tự giới hạn cả loại và ID người nhận, kể cả Admin.
        $danhSach = $request->user()->notifications()->reorder()->orderByDesc('created_at')->orderByDesc('id')->paginate(20, ['*'], 'page', $request->integer('page', 1));

        return response()->json([
            'status' => true,
            'message' => 'Đã tải thông báo.',
            'data' => array_map(fn ($tin) => $this->duLieu($tin), $danhSach->items()),
            'meta' => ['current_page' => $danhSach->currentPage(), 'last_page' => $danhSach->lastPage(), 'so_chua_doc' => $request->user()->unreadNotifications()->count()],
        ], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function daDoc(Request $request, string $id)
    {
        $tin = $request->user()->notifications()->findOrFail($id);
        // UPDATE có điều kiện giữ nguyên thời điểm đọc khi người dùng bấm lại.
        $request->user()->unreadNotifications()->where('id', $tin->id)->update(['read_at' => now()]);

        return response()->json(['status' => true, 'message' => 'Đã đánh dấu thông báo đã đọc.', 'data' => null]);
    }

    public function daDocTatCa(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['status' => true, 'message' => 'Đã đọc tất cả thông báo.', 'data' => null]);
    }

    private function duLieu(DatabaseNotification $tin): array
    {
        $duLieu = $tin->data;
        $duongDan = $duLieu['duong_dan'] ?? null;
        $hopLe = is_string($duongDan) && preg_match('~^/(?!/)[^\\\\\x00-\x20]*$~D', $duongDan);

        return [
            'id' => $tin->id,
            'tieu_de' => is_string($duLieu['tieu_de'] ?? null) ? $duLieu['tieu_de'] : 'Thông báo',
            'noi_dung' => is_string($duLieu['noi_dung'] ?? null) ? $duLieu['noi_dung'] : '',
            'duong_dan' => $hopLe ? $duongDan : null,
            'da_doc_luc' => $tin->read_at?->toIso8601String(),
            'tao_luc' => $tin->created_at?->toIso8601String(),
        ];
    }
}
