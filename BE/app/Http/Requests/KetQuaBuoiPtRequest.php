<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KetQuaBuoiPtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return [];
        }
        if ($this->isMethod('POST')) {
            return ['updated_at' => ['required', 'date_format:Y-m-d H:i:s.u']];
        }

        return [
            'updated_at' => ['present', 'nullable', 'date_format:Y-m-d H:i:s.u'],
            'ghi_chu' => ['present', 'nullable', 'string', 'max:2000'],
            'nhan_xet' => ['present', 'nullable', 'string', 'max:2000'],
            'bai_tap' => ['present', 'array', 'list', 'max:30'],
            'bai_tap.*' => ['required', 'array:bai_tap_id,hiep_tap'],
            'bai_tap.*.bai_tap_id' => ['required', 'integer', 'min:1', 'distinct'],
            'bai_tap.*.hiep_tap' => ['present', 'array', 'list', 'max:20'],
            'bai_tap.*.hiep_tap.*' => ['required', 'array:so_lan_lap,khoi_luong_kg,nghi_giay'],
            'bai_tap.*.hiep_tap.*.so_lan_lap' => ['required', 'integer', 'min:1', 'max:1000'],
            'bai_tap.*.hiep_tap.*.khoi_luong_kg' => ['present', 'nullable', 'numeric', 'min:0', 'max:1000', 'decimal:0,2'],
            'bai_tap.*.hiep_tap.*.nghi_giay' => ['required', 'integer', 'min:0', 'max:3600'],
        ];
    }

    public function after(): array
    {
        return [function ($v) {
            $d = $this->isMethod('GET') ? $this->query() : $this->all();
            foreach (array_diff(array_keys($d), array_keys($this->rules())) as $key) {
                $v->errors()->add($key, 'Trường không được hỗ trợ.');
            }
        }];
    }

    public function messages(): array
    {
        return ['updated_at.date_format' => 'Phiên bản không hợp lệ. Hãy tải lại.',
            'bai_tap.*.bai_tap_id.distinct' => 'Một bài chỉ xuất hiện một lần trong buổi tập.',
            'bai_tap.*.hiep_tap.*.so_lan_lap.required' => 'Nhập số lần thực tế.',
            'bai_tap.*.hiep_tap.*.nghi_giay.required' => 'Nhập thời gian nghỉ thực tế.'];
    }
}
