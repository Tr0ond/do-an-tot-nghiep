<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ThongBaoDayRequest;
use App\Models\TaiKhoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class ThongBaoDayController extends Controller
{
    private function phien(Request $request): PersonalAccessToken
    {
        $token = $request->user()->currentAccessToken();
        abort_unless($token instanceof PersonalAccessToken, 401);

        return $token;
    }

    public function show(Request $request)
    {
        return response()->json(['status' => true, 'data' => ['da_bat' => DB::table('thiet_bi_push')->where('phien_id', $this->phien($request)->id)->where('da_bat', true)->exists()]]);
    }

    public function update(ThongBaoDayRequest $request)
    {
        $token = $this->phien($request);
        DB::transaction(function () use ($request, $token) {
            // Cùng thứ tự khóa với reset/khóa tài khoản; đăng ký không phục hồi phiên đã thu hồi.
            $nguoi = TaiKhoan::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $phien = PersonalAccessToken::whereKey($token->id)->lockForUpdate()->first();
            abort_unless($phien && $phien->expires_at?->isFuture() && $nguoi->trang_thai === TaiKhoan::HOAT_DONG && is_string($phien->dau_phien_dang_nhap) && hash_equals($nguoi->dauPhienDangNhap(), $phien->dau_phien_dang_nhap), 401);
            $expo = $request->validated('expo_token');
            DB::table('thiet_bi_push')->where('phien_id', $token->id)->where('expo_token', '<>', $expo)->delete();
            $cu = DB::table('thiet_bi_push')->where('expo_token', $expo)->lockForUpdate()->first();
            // PUT muộn của phiên cũ không giành lại token sau đăng nhập mới trên cùng máy.
            abort_if($cu && $cu->phien_id > $token->id, 409, 'Thiết bị đã đăng ký bằng phiên mới hơn. Hãy đăng nhập lại.');
            $ban = $cu && $cu->phien_id === $token->id && $cu->da_bat ? $cu->phien_ban : (string) Str::uuid();
            DB::table('thiet_bi_push')->upsert([['tai_khoan_id' => $request->user()->id, 'phien_id' => $token->id, 'expo_token' => $expo, 'phien_ban' => $ban, 'da_bat' => true, 'created_at' => now(), 'updated_at' => now()]], ['expo_token'], ['tai_khoan_id', 'phien_id', 'phien_ban', 'da_bat', 'updated_at']);
        }, 3);

        return response()->json(['status' => true, 'data' => ['da_bat' => true]]);
    }

    public function destroy(Request $request)
    {
        DB::table('thiet_bi_push')->where('phien_id', $this->phien($request)->id)->update(['da_bat' => false, 'phien_ban' => (string) Str::uuid(), 'updated_at' => now()]);

        return response()->json(['status' => true, 'data' => ['da_bat' => false]]);
    }
}
