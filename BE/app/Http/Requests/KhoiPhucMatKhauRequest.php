<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class KhoiPhucMatKhauRequest extends FormRequest
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
        $quyTac = ['email' => ['required', 'string', 'email', 'max:191']];
        if ($this->is('dat-lai-mat-khau')) {
            $quyTac = [...$quyTac, 'token' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
                'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed', function ($truong, $giaTri, $baoLoi) {
                    if (is_string($giaTri) && strlen($giaTri) > 72) {
                        $baoLoi('Mật khẩu không được quá 72 byte.');
                    }
                }], 'password_confirmation' => ['required', 'string', 'max:72']];
        }

        return $quyTac;
    }

    public function after(): array
    {
        return [function (Validator $kiemTra) {
            foreach (array_diff(array_keys($this->all()), array_keys($this->rules())) as $truong) {
                $kiemTra->errors()->add($truong, 'Trường này không được phép gửi.');
            }
        }];
    }

    public function messages(): array
    {
        return ['required' => 'Vui lòng nhập :attribute.', 'email' => 'Email không đúng định dạng.', 'confirmed' => 'Mật khẩu nhập lại chưa khớp.', 'min' => 'Mật khẩu cần ít nhất 8 ký tự.', 'max' => ':attribute quá dài.', 'token.size' => 'Liên kết khôi phục không hợp lệ.', 'token.regex' => 'Liên kết khôi phục không hợp lệ.', 'string' => ':attribute không hợp lệ.'];
    }

    public function attributes(): array
    {
        return ['email' => 'Email', 'password' => 'Mật khẩu', 'password_confirmation' => 'Xác nhận mật khẩu', 'token' => 'Liên kết khôi phục'];
    }
}
