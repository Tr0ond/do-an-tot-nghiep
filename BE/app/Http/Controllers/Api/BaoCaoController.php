<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaoCaoRequest;
use App\Services\BaoCaoService;
use Illuminate\Http\JsonResponse;

class BaoCaoController extends Controller
{
    public function index(BaoCaoRequest $request, BaoCaoService $service): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Đã tải báo cáo.', 'data' => $service->tongHop($request->validated())])->header('Cache-Control', 'private, no-store');
    }
}
