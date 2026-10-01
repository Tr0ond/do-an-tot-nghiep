<?php

namespace App\Http\Requests;

use App\Models\BaiTap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DanhSachBaiTapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['tu_khoa', 'dung_cu_nguon'] as $truong) {
            if (is_string($this->input($truong))) {
                $this->merge([$truong => trim($this->input($truong))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'tu_khoa' => ['nullable', 'string', 'max:100'],
            'nhom_co_id' => ['bail', 'nullable', 'integer', Rule::exists('nhom_co', 'id')->where('trang_thai', 'HOAT_DONG')],
            'dung_cu_nguon' => ['bail', 'nullable', 'string', 'max:255', function ($truong, $giaTri, $baoLoi) {
                if (! BaiTap::dangHienThi()->where('dung_cu_nguon', $giaTri)->exists()) {
                    $baoLoi('Dụng cụ đã chọn không có bài tập hiển thị.');
                }
            }],
            'page' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:48'],
        ];
    }

    public function messages(): array
    {
        return [
            'string' => ':attribute phải là chuỗi ký tự.', 'integer' => ':attribute phải là số nguyên.',
            'max' => ':attribute vượt giới hạn cho phép (:max).', 'min' => ':attribute phải từ :min trở lên.',
            'nhom_co_id.exists' => 'Nhóm cơ đã chọn không tồn tại hoặc đã ngừng hiển thị.',
        ];
    }

    public function attributes(): array
    {
        return ['tu_khoa' => 'Từ khóa', 'nhom_co_id' => 'Nhóm cơ', 'dung_cu_nguon' => 'Dụng cụ', 'page' => 'Trang', 'per_page' => 'Số bài mỗi trang'];
    }
}
