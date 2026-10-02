<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PhanCongRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->vai_tro === 'ADMIN';
    }

    public function rules(): array
    {
        return ['khach_hang_id' => ['required', 'integer', 'min:1'], 'huan_luyen_vien_id' => ['required', 'integer', 'min:1'], 'phan_cong_hien_tai_id' => ['present', 'nullable', 'integer', 'min:1'], 'client_request_id' => ['required', 'uuid'], 'ly_do' => ['nullable', 'string', 'max:1000']];
    }
}
