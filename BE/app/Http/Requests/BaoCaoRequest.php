<?php

namespace App\Http\Requests;

use App\Models\TaiKhoan;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class BaoCaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->vai_tro === TaiKhoan::ADMIN;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('tu_ngay') && ! $this->has('den_ngay')) {
            $homNay = CarbonImmutable::today('Asia/Ho_Chi_Minh');
            $this->merge(['tu_ngay' => $homNay->subDays(29)->toDateString(), 'den_ngay' => $homNay->toDateString()]);
        }
    }

    public function rules(): array
    {
        $homNay = CarbonImmutable::today('Asia/Ho_Chi_Minh')->toDateString();

        return [
            'tu_ngay' => 'required|date_format:Y-m-d',
            'den_ngay' => 'required|date_format:Y-m-d|after_or_equal:tu_ngay|before_or_equal:'.$homNay,
            'nhom' => 'sometimes|in:ngay,thang',
            'pt_page' => 'sometimes|integer|min:1|max:100000',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->isEmpty() && CarbonImmutable::parse($this->tu_ngay)->diffInDays(CarbonImmutable::parse($this->den_ngay)) > 365) {
                $v->errors()->add('den_ngay', 'Chọn tối đa 366 ngày cho mỗi báo cáo.');
            }
        });
    }

    public function attributes(): array
    {
        return ['tu_ngay' => 'ngày bắt đầu', 'den_ngay' => 'ngày kết thúc', 'nhom' => 'cách nhóm thời gian', 'pt_page' => 'trang PT'];
    }
}
