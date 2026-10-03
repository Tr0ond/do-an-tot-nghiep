<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NhatKyTapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            if ($this->route('id') !== null) {
                return [];
            }

            return ['page' => ['sometimes', 'integer', 'min:1'], 'tu_ngay' => ['required', 'date_format:Y-m-d'],
                'den_ngay' => ['required', 'date_format:Y-m-d', 'after_or_equal:tu_ngay'],
                'trang_thai' => ['sometimes', Rule::in(['DA_LEN_LICH', 'DANG_TAP', 'HOAN_THANH', 'DA_HUY'])]];
        }
        $hanhDong = $this->route('hanhDong');
        if ($hanhDong === 'nhan-xet') {
            return ['noi_dung' => ['required', 'string', 'max:2000'], 'client_request_id' => ['required', 'uuid']];
        }
        if ($hanhDong !== null || $this->isMethod('PUT')) {
            $r = ['updated_at' => ['required', 'date_format:Y-m-d H:i:s.u']];
            if ($this->isMethod('PUT')) {
                $r += ['ghi_chu' => ['present', 'nullable', 'string', 'max:2000'],
                    'bai_tap' => ['present', 'array', 'list', 'max:200'],
                    'bai_tap.*' => ['array:id,hiep_tap'], 'bai_tap.*.id' => ['required', 'integer', 'min:1', 'distinct'],
                    'bai_tap.*.hiep_tap' => ['present', 'array', 'list', 'max:20'],
                    'bai_tap.*.hiep_tap.*' => ['array:so_lan_lap,khoi_luong_kg,nghi_giay'],
                    'bai_tap.*.hiep_tap.*.so_lan_lap' => ['required', 'integer', 'min:1', 'max:1000'],
                    'bai_tap.*.hiep_tap.*.khoi_luong_kg' => ['present', 'nullable', 'numeric', 'min:0', 'max:1000', 'decimal:0,2'],
                    'bai_tap.*.hiep_tap.*.nghi_giay' => ['required', 'integer', 'min:0', 'max:3600']];
            }

            return $r;
        }

        return ['ke_hoach_tap_id' => ['required', 'integer', 'min:1'], 'ngay_thu' => ['required', 'integer', 'min:1', 'max:31'],
            'ngay_tap' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now('Asia/Ho_Chi_Minh')->toDateString(), 'before_or_equal:'.now('Asia/Ho_Chi_Minh')->addDays(365)->toDateString()],
            'client_request_id' => ['required', 'uuid']];
    }

    public function after(): array
    {
        return [function ($v) {
            $duLieu = $this->isMethod('GET') ? $this->query() : $this->all();
            foreach (array_diff(array_keys($duLieu), array_keys($this->rules())) as $key) {
                $v->errors()->add($key, 'Trường không được hỗ trợ.');
            }
            if ($this->isMethod('GET') && $this->route('id') === null && ! $v->errors()->any()) {
                if (CarbonImmutable::parse($this->input('tu_ngay'))->diffInDays(CarbonImmutable::parse($this->input('den_ngay'))) > 365) {
                    $v->errors()->add('den_ngay', 'Chọn khoảng tối đa 366 ngày.');
                }
            }
        }];
    }

    public function messages(): array
    {
        return ['*.required' => 'Vui lòng nhập đầy đủ thông tin.', 'bai_tap.*.hiep_tap.*.so_lan_lap.min' => 'Mỗi hiệp cần ít nhất 1 lần.',
            'ngay_tap.after_or_equal' => 'Chọn ngày từ hôm nay.', 'ngay_tap.before_or_equal' => 'Chọn ngày trong 365 ngày tới.',
            'updated_at.date_format' => 'Phiên bản không hợp lệ. Hãy tải lại.'];
    }
}
