<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KetQuaBuoiPtRequest;
use App\Services\KetQuaBuoiPtService;

class KetQuaBuoiPtController extends Controller
{
    public function show(KetQuaBuoiPtRequest $request, int $id, KetQuaBuoiPtService $dichVu)
    {
        return $this->tra($dichVu->duLieu($request->user(), $id), 'Đã tải kết quả buổi tập.');
    }

    public function update(KetQuaBuoiPtRequest $request, int $id, KetQuaBuoiPtService $dichVu)
    {
        $dichVu->luu($request->user(), $id, $request->validated());

        return $this->tra($dichVu->duLieu($request->user(), $id), 'Đã lưu nháp kết quả. Chưa xác nhận lịch hoặc trừ lượt PT.');
    }

    public function chot(KetQuaBuoiPtRequest $request, int $id, KetQuaBuoiPtService $dichVu)
    {
        $dichVu->chot($request->user(), $id, $request->validated('updated_at'));

        return $this->tra($dichVu->duLieu($request->user(), $id), 'Đã chốt kết quả. Xác nhận hoàn thành lịch hẹn là thao tác riêng.');
    }

    private function tra(array $data, string $message)
    {
        return response()->json(['status' => true, 'message' => $message, 'data' => $data], 200, ['Cache-Control' => 'private, no-store']);
    }
}
