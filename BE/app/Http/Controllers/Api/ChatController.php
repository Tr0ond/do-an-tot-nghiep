<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatRequest;
use App\Services\ChatService;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function __construct(private ChatService $chat) {}

    public function index(ChatRequest $request)
    {
        $danhSach = $this->chat->danhSach($request->user(), $request->validated());

        return response()->json(['status' => true, 'message' => 'Đã tải hội thoại.', 'data' => $danhSach->items(), 'meta' => ['current_page' => $danhSach->currentPage(), 'last_page' => $danhSach->lastPage(), 'total' => $danhSach->total(), 'so_chua_doc' => $this->chat->soChuaDoc($request->user())]], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function show(ChatRequest $request, int $id)
    {
        return response()->json(['status' => true, 'message' => 'Đã tải tin nhắn.', 'data' => $this->chat->tinNhan($request->user(), $id, $request->validated())], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function store(ChatRequest $request, int $id)
    {
        return response()->json(['status' => true, 'message' => 'Tin nhắn đã lưu.', 'data' => $this->chat->gui($request->user(), $id, $request->validated())]);
    }

    public function daDoc(ChatRequest $request, int $id)
    {
        return response()->json(['status' => true, 'message' => 'Đã cập nhật trạng thái đọc.', 'data' => $this->chat->daDoc($request->user(), $id, $request->validated('tin_nhan_id'))]);
    }

    public function anh(ChatRequest $request, int $id, int $tinId, int $viTri)
    {
        $anh = $this->chat->anh($request->user(), $id, $tinId, $viTri);
        abort_unless(Storage::disk('local')->exists($anh['duong_dan']), 404);

        return Storage::disk('local')->response($anh['duong_dan'], 'anh-chat', [
            'Content-Type' => $anh['mime'],
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
