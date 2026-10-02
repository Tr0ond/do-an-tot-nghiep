<?php

namespace App\Http\Requests;

use App\Models\TaiKhoan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TrangThaiTaiKhoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->vai_tro === TaiKhoan::ADMIN;
    }

    public function rules(): array
    {
        return ['trang_thai' => ['required', Rule::in([TaiKhoan::HOAT_DONG, TaiKhoan::BI_KHOA])], 'updated_at' => ['present', 'nullable', 'date_format:Y-m-d H:i:s.u']];
    }

    public function after(): array
    {
        return [function (Validator $kiemTra) {
            foreach (array_diff(array_keys($this->all()), array_keys($this->rules())) as $truong) {
                $kiemTra->errors()->add($truong, 'Chỉ được cập nhật trạng thái và phiên bản tài khoản.');
            }
        }];
    }

    public function messages(): array
    {
        return ['required' => 'Thiếu trạng thái tài khoản.', 'in' => 'Trạng thái không hợp lệ.', 'present' => 'Thiếu phiên bản tài khoản.', 'date_format' => 'Phiên bản không hợp lệ. Hãy tải lại.'];
    }
}
