<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class KeHoachTapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return ['page' => 'sometimes|integer|min:1|max:100000', 'tu_khoa' => 'nullable|string|max:100', 'nguon_tao' => 'nullable|in:PT,KHACH_HANG', 'da_an' => 'sometimes|boolean'];
        }
        if ($this->route('hanhDong')) {
            return ['updated_at' => 'required|date_format:Y-m-d H:i:s.u'];
        }
        $rules = GhiGiaoAnMauRequest::quyTacNoiDung();
        unset($rules['ten_giao_an']);
        $rules['ten_ke_hoach'] = 'required|string|max:255';
        $rules['bai_tap.*'] = 'required|array:bai_tap_id,ngay_thu,thu_tu,so_hiep,so_lan_lap,nghi_giay,ghi_chu,muc_ta_kg';
        $rules['bai_tap.*.muc_ta_kg'] = 'present|nullable|numeric|min:0|max:1000|decimal:0,2';
        $rules['giao_an_mau_id'] = 'present|nullable|integer|min:1';

        return [...$rules, ...($this->isMethod('POST') ? ['client_request_id' => 'required|uuid'] : ['updated_at' => 'required|date_format:Y-m-d H:i:s.u'])];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $fields = array_filter(array_keys($this->rules()), fn ($k) => ! str_contains($k, '.'));
            foreach (array_diff(array_keys($this->all()), $fields) as $k) {
                $v->errors()->add($k, 'Trường này do hệ thống quản lý.');
            }
            if (! $this->isMethod('GET') && ! $this->route('hanhDong') && $v->errors()->isEmpty()) {
                GhiGiaoAnMauRequest::kiemTraThuTu($v, $this->all());
            }
        }];
    }

    public function messages(): array
    {
        return [...(new GhiGiaoAnMauRequest)->messages(), 'numeric' => 'Mức tạ phải là số.', 'decimal' => 'Mức tạ tối đa 2 chữ số thập phân.'];
    }
}
