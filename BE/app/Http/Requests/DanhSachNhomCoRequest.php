<?php

namespace App\Http\Requests;

use App\Models\TaiKhoan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DanhSachNhomCoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->vai_tro === TaiKhoan::ADMIN;
    }

    public function rules(): array
    {
        return [
            'tu_khoa' => ['nullable', 'string', 'max:100'],
            'trang_thai' => ['nullable', Rule::in(['HOAT_DONG', 'NGUNG_SU_DUNG'])],
            'page' => ['sometimes', 'integer', 'between:1,100000'],
            'per_page' => ['sometimes', 'integer', 'between:1,48'],
        ];
    }

    public function messages(): array
    {
        return ['max' => 'Từ khóa quá dài.', 'in' => 'Trạng thái không hợp lệ.', 'integer' => ':attribute phải là số nguyên.', 'between' => ':attribute nằm ngoài giới hạn cho phép.'];
    }
}
