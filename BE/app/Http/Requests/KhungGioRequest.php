<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KhungGioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['bat_dau_luc' => ['required', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/D']];
    }
}
