<?php

namespace App\Http\Requests;

use App\Models\TaiKhoan;
use Illuminate\Validation\Rule;

class TaoTaiKhoanRequest extends DangKyRequest
{
    public function authorize(): bool
    {
        return $this->user()?->vai_tro === TaiKhoan::ADMIN;
    }

    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'vai_tro' => ['required', Rule::in([TaiKhoan::HUAN_LUYEN_VIEN, TaiKhoan::ADMIN])],
        ]);
    }
}
