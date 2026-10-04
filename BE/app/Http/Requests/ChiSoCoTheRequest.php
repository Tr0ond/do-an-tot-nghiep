<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChiSoCoTheRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return ['so_ngay' => ['sometimes', 'integer', Rule::in([7, 30, 90])], 'page' => ['sometimes', 'integer', 'min:1'],
                'den_ngay' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:'.now('Asia/Ho_Chi_Minh')->toDateString()]];
        }

        return ['ngay_ghi' => ['required', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:'.now('Asia/Ho_Chi_Minh')->toDateString()],
            'can_nang_kg' => ['required', 'numeric', 'between:10,500', 'decimal:0,2'],
            'chieu_cao_cm' => ['required', 'numeric', 'between:50,250', 'decimal:0,2'],
            'ghi_chu' => ['present', 'nullable', 'string', 'max:1000'],
            ...($this->isMethod('PUT') ? ['updated_at' => ['present', 'nullable', 'date_format:Y-m-d H:i:s.u']] : [])];
    }

    public function after(): array
    {
        return [function ($v) {
            foreach (array_diff(array_keys($this->isMethod('GET') ? $this->query() : $this->all()), array_keys($this->rules())) as $ten) {
                $v->errors()->add($ten, 'Trường không được hỗ trợ.');
            }
        }];
    }

    public function messages(): array
    {
        return ['*.required' => 'Vui lòng nhập đầy đủ thông tin.', '*.numeric' => 'Vui lòng nhập số.',
            '*.decimal' => 'Nhập tối đa 2 chữ số thập phân.', 'can_nang_kg.between' => 'Cân nặng cần từ 10 đến 500 kg.',
            'chieu_cao_cm.between' => 'Chiều cao cần từ 50 đến 250 cm.', 'ngay_ghi.date_format' => 'Ngày ghi nhận không hợp lệ.',
            'ngay_ghi.before_or_equal' => 'Không ghi chỉ số cho ngày tương lai.', 'ngay_ghi.after_or_equal' => 'Chọn ngày từ năm 1900.',
            'ghi_chu.max' => 'Ghi chú tối đa 1.000 ký tự.', 'updated_at.date_format' => 'Phiên bản không hợp lệ. Hãy tải lại.'];
    }
}
