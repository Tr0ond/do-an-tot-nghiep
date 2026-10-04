<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChiSoCoTheRequest;
use App\Services\ChiSoCoTheService;

class ChiSoCoTheController extends Controller
{
    public function index(ChiSoCoTheRequest $request, ChiSoCoTheService $dichVu, ?int $khachId = null)
    {
        $k = $dichVu->danhSach($request->user(), $khachId ?? $request->user()->hoSoKhachHang->id,
            (int) $request->validated('so_ngay', 30), (int) $request->validated('page', 1), $request->validated('den_ngay'));

        return $this->phanHoi($k['data'], 'Đã tải chỉ số cơ thể.', 200, $k['meta']);
    }

    public function store(ChiSoCoTheRequest $request, ChiSoCoTheService $dichVu)
    {
        $ban = $dichVu->luu($request->user(), $request->validated());

        return $this->phanHoi($ban->duLieu(), 'Đã ghi nhận chỉ số cơ thể.', $ban->wasRecentlyCreated ? 201 : 200);
    }

    public function update(ChiSoCoTheRequest $request, int $id, ChiSoCoTheService $dichVu)
    {
        return $this->phanHoi($dichVu->luu($request->user(), $request->validated(), $id)->duLieu(), 'Đã cập nhật chỉ số cơ thể.');
    }

    private function phanHoi(array $data, string $message, int $status = 200, ?array $meta = null)
    {
        return response()->json(['status' => true, 'message' => $message, 'data' => $data, ...($meta ? ['meta' => $meta] : [])], $status)
            ->header('Cache-Control', 'private, no-store');
    }
}
