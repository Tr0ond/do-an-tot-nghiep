<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class DangNhapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:191'],
            'password' => ['required', 'string', 'max:72'],
        ];
    }

    public function messages(): array
    {
        return ['required' => ':attribute là bắt buộc.', 'string' => ':attribute phải là chuỗi.', 'email.email' => 'Email không đúng định dạng.', 'max' => ':attribute không được vượt quá :max ký tự.'];
    }

    public function attributes(): array
    {
        return ['email' => 'Email', 'password' => 'Mật khẩu'];
    }
}
