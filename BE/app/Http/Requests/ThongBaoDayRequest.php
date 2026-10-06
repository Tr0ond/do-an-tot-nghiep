<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ThongBaoDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['expo_token' => ['required', 'string', 'max:191', 'regex:/^(?:ExponentPushToken|ExpoPushToken)\[[A-Za-z0-9_-]+\]$/D']];
    }
}
