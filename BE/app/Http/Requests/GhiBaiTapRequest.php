<?php

namespace App\Http\Requests;

use App\Models\TaiKhoan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GhiBaiTapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->vai_tro === TaiKhoan::ADMIN;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('ma_nguon'))) {
            $this->merge(['ma_nguon' => strtoupper(trim($this->input('ma_nguon')))]);
        }
    }

    public function rules(): array
    {
        $quyTac = [
            'nhom_co_id' => ['bail', 'required', 'integer', Rule::exists('nhom_co', 'id')],
            'ten_bai_tap' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'max:255'],
            'ten_tieng_viet' => ['present', 'nullable', 'string', 'max:255'],
            'dung_cu' => ['present', 'nullable', 'string', 'max:255'],
            'huong_dan_vi' => ['present', 'nullable', 'string', 'max:10000'],
            'cac_buoc_vi' => ['present', 'array', 'list', 'max:30'],
            'cac_buoc_vi.*' => ['required', 'string', 'max:2000'],
        ];

        return $this->isMethod('POST')
            ? [...$quyTac, 'ma_nguon' => ['bail', 'required', 'string', 'regex:/^[A-Z0-9]{4}$/', Rule::unique('bai_tap', 'ma_nguon')->where('nguon_du_lieu', 'admin')], 'trang_thai' => ['required', Rule::in(['HOAT_DONG', 'NGUNG_SU_DUNG'])]]
            : [...$quyTac, 'updated_at' => ['required', 'date_format:Y-m-d H:i:s.u']];
    }

    public function after(): array
    {
        return [function (Validator $kiemTra) {
            // Chỉ nhận các trường trong hợp đồng, tránh sửa nguồn/media hoặc JSON ngôn ngữ khác.
            $truongChoPhep = array_filter(array_keys($this->rules()), fn ($truong) => ! str_contains($truong, '.'));
            foreach (array_diff(array_keys($this->all()), $truongChoPhep) as $truong) {
                $kiemTra->errors()->add($truong, 'Trường này không được phép gửi.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'required' => 'Vui lòng nhập :attribute.', 'present' => 'Thiếu trường :attribute.',
            'string' => ':attribute phải là chuỗi ký tự.', 'integer' => ':attribute phải là số nguyên.',
            'array' => ':attribute phải là danh sách.', 'list' => ':attribute phải là danh sách liên tiếp.',
            'max' => ':attribute vượt giới hạn cho phép (:max).', 'in' => ':attribute không hợp lệ.',
            'nhom_co_id.exists' => 'Nhóm cơ không tồn tại.', 'ma_nguon.regex' => 'Mã gồm đúng 4 chữ cái A–Z hoặc chữ số.',
            'ma_nguon.unique' => 'Mã bài tập này đã được sử dụng.', 'updated_at.date_format' => 'Phiên bản bài tập không hợp lệ. Hãy tải lại bài tập.',
        ];
    }

    public function attributes(): array
    {
        return ['nhom_co_id' => 'nhóm cơ', 'ten_bai_tap' => 'tên bài tập', 'ten_tieng_viet' => 'tên tiếng Việt', 'dung_cu' => 'dụng cụ', 'ma_nguon' => 'mã bài tập', 'huong_dan_vi' => 'hướng dẫn tiếng Việt', 'cac_buoc_vi' => 'các bước thực hiện', 'cac_buoc_vi.*' => 'bước thực hiện', 'trang_thai' => 'trạng thái', 'updated_at' => 'phiên bản bài tập'];
    }
}
