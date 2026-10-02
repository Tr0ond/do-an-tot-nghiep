<?php

namespace Tests\Unit;

use App\Services\PayosService;
use Tests\TestCase;

class PayosChuKyTest extends TestCase
{
    public function test_vector_chu_ky_cong_khai_tu_payos_va_tamper(): void
    {
        // Vector độc lập từ tài liệu payOS, không dùng khóa của chủ dự án.
        config(['payos.checksum_key' => '1a54716c8f0efb2744fb28b6e38b25da7f67a925d98bc1c18bd8faaecadd7675']);
        $data = ['orderCode' => 123, 'amount' => 3000, 'description' => 'VQRIO123', 'accountNumber' => '12345678', 'reference' => 'TF230204212323', 'transactionDateTime' => '2023-02-04 18:25:00', 'currency' => 'VND', 'paymentLinkId' => '124c33293c43417ab7879e14c8d9eb18', 'code' => '00', 'desc' => 'Thành công', 'counterAccountBankId' => '', 'counterAccountBankName' => '', 'counterAccountName' => '', 'counterAccountNumber' => '', 'virtualAccountName' => '', 'virtualAccountNumber' => ''];
        $chuKy = '412e915d2871504ed31be63c8f62a149a4410d34c4c42affc9006ef9917eaa03';
        $payos = app(PayosService::class);
        $this->assertTrue($payos->hopLe($data, $chuKy));
        $this->assertFalse($payos->hopLe([...$data, 'amount' => 3001], $chuKy));
        config(['payos.checksum_key' => '']);
        $this->assertFalse($payos->hopLe($data, $chuKy));
    }
}
