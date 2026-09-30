<?php

namespace Tests\Feature;

use Tests\TestCase;

class KiemTraHeThongTest extends TestCase
{
    public function test_endpoint_suc_khoe_tra_json_khong_can_database(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'Backend hoạt động.')
            ->assertJsonStructure(['data' => ['ung_dung', 'thoi_gian']]);
    }

    public function test_frontend_duoc_cap_cors_credentials(): void
    {
        $this->withHeaders(['Origin' => config('app.frontend_url')])
            ->getJson('/api/v1/health')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', config('app.frontend_url'))
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_origin_khac_khong_duoc_phan_chieu_trong_cors(): void
    {
        $this->withHeaders(['Origin' => 'http://example.invalid'])
            ->getJson('/api/v1/health')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', config('app.frontend_url'));
    }
}
