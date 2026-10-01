<?php

use App\Http\Middleware\KiemTraTaiKhoanHoatDong;
use App\Http\Middleware\KiemTraVaiTro;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->redirectGuestsTo(fn () => config('app.frontend_url').'/dang-nhap');
        $middleware->alias(['tai_khoan_hoat_dong' => KiemTraTaiKhoanHoatDong::class, 'vai_tro' => KiemTraVaiTro::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*', 'dang-nhap', 'dang-ky', 'dang-xuat') || $request->expectsJson(),
        );
        $exceptions->respond(function (Response $response) {
            $maHttp = $response->getStatusCode();
            if ($maHttp < 400 || ! request()->is('api/*', 'dang-nhap', 'dang-ky', 'dang-xuat', 'sanctum/*')) {
                return $response;
            }
            $duLieu = json_decode($response->getContent(), true) ?? [];
            $thongBao = match ($maHttp) {
                401 => 'Bạn cần đăng nhập để tiếp tục.',
                403 => ($duLieu['message'] ?? '') === 'Tài khoản đã bị khóa hoặc ngừng hoạt động.'
                    ? $duLieu['message'] : 'Bạn không có quyền thực hiện thao tác này.',
                404 => 'Không tìm thấy tài nguyên.',
                409 => $duLieu['message'] ?? 'Trạng thái hiện tại không cho phép thao tác.',
                419 => 'Phiên làm việc đã hết hạn. Vui lòng thử lại.',
                422 => 'Vui lòng kiểm tra dữ liệu nhập.',
                429 => 'Bạn thao tác quá nhiều lần. Vui lòng thử lại sau.',
                default => 'Không thể xử lý yêu cầu. Vui lòng thử lại sau.',
            };
            $noiDung = ['status' => false, 'message' => $thongBao, 'code' => 'HTTP_'.$maHttp, 'data' => null, 'errors' => $duLieu['errors'] ?? (object) []];

            return $response instanceof JsonResponse ? $response->setData($noiDung) : response()->json($noiDung, $maHttp, $response->headers->all());
        });
    })->create();
