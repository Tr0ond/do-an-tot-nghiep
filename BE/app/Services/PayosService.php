<?php

namespace App\Services;

use App\Models\DangKyGoiTap;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class PayosService
{
    public function chuKy(array $duLieu): string
    {
        ksort($duLieu);
        $phan = [];
        foreach ($duLieu as $ten => $giaTri) {
            if (is_array($giaTri)) {
                $giaTri = array_is_list($giaTri) ? json_encode(array_map(function ($dong) {
                    if (is_array($dong)) {
                        ksort($dong);
                    }

                    return $dong;
                }, $giaTri), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '[object Object]';
            } elseif ($giaTri === null || $giaTri === 'undefined' || $giaTri === 'null') {
                $giaTri = '';
            } elseif (is_bool($giaTri)) {
                $giaTri = $giaTri ? 'true' : 'false';
            }
            $phan[] = $ten.'='.$giaTri;
        }

        return hash_hmac('sha256', implode('&', $phan), (string) config('payos.checksum_key'));
    }

    public function hopLe(array $duLieu, mixed $chuKy): bool
    {
        return filled(config('payos.checksum_key')) && is_string($chuKy) && preg_match('/^[a-f0-9]{64}$/', $chuKy) && hash_equals($this->chuKy($duLieu), $chuKy);
    }

    public function taoLink(DangKyGoiTap $don, bool $mobile = false): array
    {
        $url = rtrim(config('app.frontend_url'), '/').($mobile ? '/mo-ung-dung/don-hang/' : '/khach-hang/don-hang/').$don->id;
        $duLieu = ['amount' => $don->gia_snapshot, 'cancelUrl' => $url, 'description' => 'G'.substr((string) $don->ma_don_payos, -8), 'orderCode' => $don->ma_don_payos, 'returnUrl' => $url];
        $duLieu['signature'] = $this->chuKy($duLieu);
        $duLieu['expiredAt'] = $don->han_thanh_toan->timestamp;

        return $this->goi('POST', '/v2/payment-requests', $duLieu);
    }

    public function layLink(int $maDon): array
    {
        return $this->goi('GET', '/v2/payment-requests/'.$maDon);
    }

    private function goi(string $phuongThuc, string $duongDan, array $duLieu = []): array
    {
        if (! filled(config('payos.client_id')) || ! filled(config('payos.api_key')) || ! filled(config('payos.checksum_key'))) {
            throw new ServiceUnavailableHttpException(null, 'Chưa cấu hình cổng thanh toán.');
        }
        // Chỉ gửi khóa tới cổng đã cấu hình, không theo redirect HTTP.
        $goc = rtrim((string) config('payos.api_url'), '/');
        if ($goc !== 'https://api-merchant.payos.vn') {
            throw new ServiceUnavailableHttpException(null, 'Cấu hình địa chỉ payOS không hợp lệ.');
        }
        try {
            $ca = config('payos.ca_bundle');
            if (! is_string($ca) || ! is_readable($ca)) {
                throw new ServiceUnavailableHttpException(null, 'Chưa có chứng chỉ CA để kết nối payOS.');
            }
            $phanHoi = Http::acceptJson()->asJson()->withHeaders(['x-client-id' => config('payos.client_id'), 'x-api-key' => config('payos.api_key')])->withOptions(['allow_redirects' => false, 'verify' => $ca])->connectTimeout(3)->timeout(6)->send($phuongThuc, $goc.$duongDan, $phuongThuc === 'GET' ? [] : ['json' => $duLieu]);
        } catch (ConnectionException) {
            throw new ServiceUnavailableHttpException(null, 'Chưa kết nối được cổng thanh toán. Hãy thử lại cùng đơn.');
        }
        $noiDung = $phanHoi->json();
        if (! $phanHoi->successful() || ! is_array($noiDung) || ($noiDung['code'] ?? '') !== '00' || ! is_array($noiDung['data'] ?? null) || ! $this->hopLe($noiDung['data'], $noiDung['signature'] ?? null)) {
            // Không đưa body/headers chứa thông tin ngân hàng hay khóa vào log/response.
            throw new ServiceUnavailableHttpException(null, 'Chưa xác minh được phản hồi payOS. Hãy thử lại cùng đơn.');
        }

        return $noiDung['data'];
    }
}
