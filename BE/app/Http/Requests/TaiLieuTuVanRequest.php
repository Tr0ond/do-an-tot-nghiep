<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TaiLieuTuVanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $noiDung = ['tieu_de' => ['required', 'string', 'max:255'], 'loai' => ['required', Rule::in(['FAQ', 'CHINH_SACH', 'HUONG_DAN'])], 'noi_dung' => ['required', 'string', 'max:8000', 'not_regex:/<[^>]+>/u']];

        return match ($this->route()->getActionMethod()) {
            'store' => [...$noiDung, 'client_request_id' => ['required', 'uuid']],
            'update' => [...$noiDung, 'phien_ban' => ['required', 'integer', 'min:1']],
            'thaoTac' => ['phien_ban' => ['required', 'integer', 'min:1']],
            default => ['page' => ['sometimes', 'integer', 'min:1', 'max:10000'], 'tu_khoa' => ['sometimes', 'nullable', 'string', 'max:100'], 'trang_thai' => ['sometimes', 'nullable', Rule::in(['NHAP', 'DA_XUAT_BAN', 'NGUNG_SU_DUNG'])]],
        };
    }

    public function after(): array
    {
        return [function (Validator $v) {
            foreach (array_diff(array_keys($this->all()), array_keys($this->rules())) as $t) {
                $v->errors()->add($t, 'Trường không được hỗ trợ.');
            }
            if ($this->route()->getActionMethod() === 'faq' && $this->has('trang_thai')) {
                $v->errors()->add('trang_thai', 'FAQ chỉ hiển thị tài liệu đã xuất bản.');
            }
        }];
    }
}
