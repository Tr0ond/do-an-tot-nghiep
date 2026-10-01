<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DanhSachGiaoAnMauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['tu_khoa' => ['sometimes', 'nullable', 'string', 'max:100'], 'page' => ['sometimes', 'integer', 'between:1,100000'], 'per_page' => ['sometimes', 'integer', 'between:1,48'], 'trang_thai' => [$this->is('api/v1/admin/*') ? 'sometimes' : 'prohibited', 'nullable', Rule::in(['NHAP', 'DA_DUYET', 'NGUNG_SU_DUNG'])]];
    }
}
