<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MuaGoiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->vai_tro === 'KHACH_HANG';
    }

    public function rules(): array
    {
        return ['goi_tap_id' => ['required', 'integer', 'min:1'], 'client_request_id' => ['required', 'uuid']];
    }
}
