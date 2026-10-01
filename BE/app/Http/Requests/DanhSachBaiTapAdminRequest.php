<?php

namespace App\Http\Requests;

use App\Models\TaiKhoan;
use Illuminate\Validation\Rule;

class DanhSachBaiTapAdminRequest extends DanhSachBaiTapRequest
{
    public function authorize(): bool
    {
        return $this->user()?->vai_tro === TaiKhoan::ADMIN;
    }

    public function rules(): array
    {
        return [
            'tu_khoa' => ['nullable', 'string', 'max:100'],
            'nhom_co_id' => ['bail', 'nullable', 'integer', Rule::exists('nhom_co', 'id')],
            'trang_thai' => ['nullable', Rule::in(['HOAT_DONG', 'NGUNG_SU_DUNG'])],
            'page' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:48'],
        ];
    }

    public function messages(): array
    {
        return [...parent::messages(), 'nhom_co_id.exists' => 'Nhóm cơ không tồn tại.', 'trang_thai.in' => 'Trạng thái không hợp lệ.'];
    }
}
