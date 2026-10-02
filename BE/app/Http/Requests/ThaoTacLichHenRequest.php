<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ThaoTacLichHenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['ly_do' => [in_array($this->route('hanhDong'), ['huy', 'tu-choi', 'vang-mat', 'dong-xu-ly']) ? 'required' : 'nullable', 'string', 'max:1000', 'regex:/\S/u']];
    }
}
