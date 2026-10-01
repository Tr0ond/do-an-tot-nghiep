<?php

namespace App\Http\Requests;

use App\Models\TaiKhoan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GhiNhomCoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->vai_tro === TaiKhoan::ADMIN;
    }

    protected function prepareForValidation(): void
    {
        if ($this->isMethod('POST') && is_string($this->input('ma_nhom_co'))) {
            $this->merge(['ma_nhom_co' => strtolower(trim($this->input('ma_nhom_co')))]);
        }
    }

    public function rules(): array
    {
        $phienBan = ['updated_at' => ['present', 'nullable', 'date_format:Y-m-d H:i:s.u']];
        if ($this->isMethod('PATCH')) {
            return ['trang_thai' => ['required', Rule::in(['HOAT_DONG', 'NGUNG_SU_DUNG'])], ...$phienBan];
        }

        $quyTac = ['ten_nhom_co' => ['required', 'string', 'max:255']];

        return $this->isMethod('POST')
            ? [...$quyTac, 'ma_nhom_co' => ['bail', 'required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('nhom_co', 'ma_nhom_co')]]
            : [...$quyTac, ...$phienBan];
    }

    public function after(): array
    {
        return [function (Validator $kiemTra) {
            foreach (array_diff(array_keys($this->all()), array_keys($this->rules())) as $truong) {
                $kiemTra->errors()->add($truong, 'Trường này do hệ thống quản lý hoặc không được phép sửa.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'required' => 'Vui lòng nhập :attribute.', 'present' => 'Thiếu :attribute.',
            'string' => ':attribute phải là chuỗi ký tự.', 'max' => ':attribute không được quá :max ký tự.',
            'ma_nhom_co.regex' => 'Mã bắt đầu bằng chữ a–z, chỉ chứa chữ thường, số và dấu gạch dưới.',
            'ma_nhom_co.unique' => 'Mã nhóm cơ đã được sử dụng. Hãy kiểm tra danh sách hoặc chọn mã khác.',
            'in' => 'Trạng thái không hợp lệ.', 'date_format' => 'Phiên bản không hợp lệ. Hãy tải lại nhóm cơ.',
        ];
    }

    public function attributes(): array
    {
        return ['ma_nhom_co' => 'mã nhóm cơ', 'ten_nhom_co' => 'tên nhóm cơ', 'updated_at' => 'phiên bản nhóm cơ'];
    }
}
