<?php

namespace App\Http\Requests;

use App\Models\TaiKhoan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GhiGoiTapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->vai_tro === TaiKhoan::ADMIN;
    }

    public static function quyTacQuyenLoi(): array
    {
        return [
            'ten_goi' => ['required', 'string', 'max:255'],
            'gia' => ['required', 'integer', 'between:1,9007199254740991'],
            'co_chatbot' => ['required', 'boolean', Rule::in([true, 1, '1'])],
            'so_luot_chatbot_moi_ngay' => ['required', 'integer', 'between:1,4294967295'],
            'so_buoi_pt' => ['required', 'integer', 'between:0,4294967295'],
            'thoi_han_ngay' => ['required', 'integer', 'between:1,36500'],
        ];
    }

    public function rules(): array
    {
        if ($this->isMethod('PATCH')) {
            return ['trang_thai' => ['required', Rule::in(['HOAT_DONG', 'NGUNG_SU_DUNG'])], 'updated_at' => ['required', 'date_format:Y-m-d H:i:s.u']];
        }

        return [...self::quyTacQuyenLoi(), ...($this->isMethod('POST') ? ['client_request_id' => ['required', 'uuid'], 'trang_thai' => ['required', Rule::in(['HOAT_DONG', 'NGUNG_SU_DUNG'])]] : ['updated_at' => ['required', 'date_format:Y-m-d H:i:s.u']])];
    }

    public function after(): array
    {
        return [function (Validator $kiemTra) {
            foreach (array_diff(array_keys($this->all()), array_keys($this->rules())) as $truong) {
                $kiemTra->errors()->add($truong, 'Trường này không được phép gửi.');
            }
        }];
    }

    public function messages(): array
    {
        return ['required' => 'Vui lòng nhập :attribute.', 'string' => ':attribute phải là chuỗi ký tự.', 'integer' => ':attribute phải là số nguyên.', 'between' => ':attribute phải nằm trong khoảng :min–:max.', 'max' => ':attribute tối đa :max ký tự.', 'in' => ':attribute không hợp lệ.', 'co_chatbot.in' => 'Hai loại gói đã chốt đều có quyền chatbot.', 'boolean' => ':attribute phải là giá trị bật/tắt.', 'uuid' => 'Mã yêu cầu không hợp lệ. Hãy mở lại biểu mẫu.', 'date_format' => 'Phiên bản không hợp lệ. Hãy tải lại gói tập.'];
    }

    public function attributes(): array
    {
        return ['ten_goi' => 'tên gói', 'gia' => 'giá VND', 'co_chatbot' => 'quyền chatbot', 'so_luot_chatbot_moi_ngay' => 'lượt chatbot mỗi ngày', 'so_buoi_pt' => 'số buổi PT', 'thoi_han_ngay' => 'thời hạn ngày', 'trang_thai' => 'trạng thái', 'updated_at' => 'phiên bản', 'client_request_id' => 'mã yêu cầu'];
    }
}
