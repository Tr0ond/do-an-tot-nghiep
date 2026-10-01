<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class DangKyRequest extends FormRequest
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
            'ho_ten' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:tai_khoan,email'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed', function ($attribute, $value, $fail) {
                if (is_string($value) && strlen($value) > 72) {
                    $fail('Mật khẩu không được vượt quá 72 byte; ký tự có dấu chiếm nhiều byte.');
                }
            }],
            'vai_tro' => ['prohibited'],
            'trang_thai' => ['prohibited'],
            'tai_khoan_id' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute là bắt buộc.',
            'string' => ':attribute phải là chuỗi.',
            'max' => ':attribute không được vượt quá :max ký tự.',
            'min' => ':attribute cần ít nhất :min ký tự.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email đã được sử dụng.',
            'password.confirmed' => 'Mật khẩu nhập lại chưa khớp.',
            'prohibited' => 'Bạn không được tự đặt :attribute.',
            'vai_tro.in' => 'Chỉ được tạo tài khoản huấn luyện viên hoặc Admin.',
        ];
    }

    public function attributes(): array
    {
        return ['ho_ten' => 'Họ tên', 'email' => 'Email', 'password' => 'Mật khẩu', 'vai_tro' => 'vai trò', 'trang_thai' => 'trạng thái', 'tai_khoan_id' => 'ID tài khoản'];
    }
}
