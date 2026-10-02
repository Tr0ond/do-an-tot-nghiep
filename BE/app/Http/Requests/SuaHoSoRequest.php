<?php

namespace App\Http\Requests;

use App\Models\TaiKhoan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SuaHoSoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->trang_thai === TaiKhoan::HOAT_DONG;
    }

    public function rules(): array
    {
        $quyTac = ['ho_ten' => ['required', 'string', 'max:255'], 'updated_at' => ['present', 'nullable', 'date_format:Y-m-d H:i:s.u']];

        return match ($this->user()?->vai_tro) {
            TaiKhoan::KHACH_HANG => [...$quyTac,
                'muc_tieu' => ['nullable', 'string', 'max:255'], 'kinh_nghiem' => ['nullable', 'string', 'max:255'],
                'gioi_tinh' => ['nullable', Rule::in(['NAM', 'NU', 'KHAC'])],
                'ngay_sinh' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
                'thoi_gian_co_the_tap' => ['nullable', 'array', 'list', 'max:14'],
                'thoi_gian_co_the_tap.*' => ['required', 'string', 'max:120'],
            ],
            TaiKhoan::HUAN_LUYEN_VIEN => [...$quyTac, 'chuyen_mon' => ['nullable', 'string', 'max:255'], 'gioi_thieu' => ['nullable', 'string', 'max:5000']],
            default => $quyTac,
        };
    }

    public function after(): array
    {
        return [function (Validator $kiemTra) {
            $choPhep = array_filter(array_keys($this->rules()), fn ($truong) => ! str_contains($truong, '.'));
            foreach (array_diff(array_keys($this->all()), $choPhep) as $truong) {
                $kiemTra->errors()->add($truong, 'Trường này không được phép sửa trong hồ sơ.');
            }
        }];
    }

    public function messages(): array
    {
        return ['required' => 'Vui lòng nhập :attribute.', 'max' => ':attribute vượt giới hạn :max.',
            'present' => 'Thiếu phiên bản hồ sơ. Hãy tải lại.', 'date_format' => ':attribute không hợp lệ.',
            'ngay_sinh.before_or_equal' => 'Ngày sinh không được ở tương lai.', 'in' => 'Lựa chọn không hợp lệ.',
            'string' => ':attribute phải là văn bản.', 'array' => 'Thời gian tập phải là danh sách.', 'list' => 'Thời gian tập phải là danh sách.'];
    }

    public function attributes(): array
    {
        return ['ho_ten' => 'Họ tên', 'muc_tieu' => 'Mục tiêu', 'kinh_nghiem' => 'Kinh nghiệm', 'ngay_sinh' => 'Ngày sinh', 'gioi_tinh' => 'Giới tính', 'chuyen_mon' => 'Chuyên môn', 'gioi_thieu' => 'Giới thiệu', 'updated_at' => 'Phiên bản hồ sơ'];
    }
}
