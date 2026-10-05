<?php

namespace App\Http\Requests;

class DangNhapMobileRequest extends DangNhapRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'ten_thiet_bi' => ['required', 'string', 'max:100']];
    }

    public function attributes(): array
    {
        return [...parent::attributes(), 'ten_thiet_bi' => 'Tên thiết bị'];
    }
}
