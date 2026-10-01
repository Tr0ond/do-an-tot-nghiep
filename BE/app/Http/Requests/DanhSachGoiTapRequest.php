<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DanhSachGoiTapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $quyTac = ['tu_khoa' => ['sometimes', 'nullable', 'string', 'max:100'], 'loai_goi' => ['sometimes', 'nullable', Rule::in(['CHATBOT', 'PT_CHATBOT'])], 'page' => ['sometimes', 'integer', 'between:1,100000'], 'per_page' => ['sometimes', 'integer', 'between:1,48']];
        if ($this->is('api/v1/admin/*')) {
            $quyTac['trang_thai'] = ['sometimes', 'nullable', Rule::in(['HOAT_DONG', 'NGUNG_SU_DUNG'])];
        }

        return $quyTac;
    }
}
