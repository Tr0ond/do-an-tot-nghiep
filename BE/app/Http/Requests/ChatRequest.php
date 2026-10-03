<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return ['page' => 'sometimes|integer|min:1|max:100000', 'tu_khoa' => 'nullable|string|max:100', 'before_id' => 'sometimes|integer|min:1|prohibits:after_id', 'after_id' => 'sometimes|integer|min:0|prohibits:before_id'];
        }
        if ($this->routeIs('chat.da-doc')) {
            return ['tin_nhan_id' => 'required|integer|min:1'];
        }

        return [
            'client_message_id' => 'required|uuid',
            'noi_dung' => 'nullable|string|max:4000',
            'anh' => 'sometimes|array|max:4',
            'anh.*' => 'required|file|image|mimetypes:image/jpeg,image/png,image/webp|max:5120|dimensions:max_width=8000,max_height=8000',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $noiDung = $this->input('noi_dung');
            if ($this->isMethod('POST') && ! $this->routeIs('chat.da-doc')
                && ($noiDung === null || (is_string($noiDung) && trim($noiDung) === '')) && ! $this->hasFile('anh')) {
                $validator->errors()->add('noi_dung', 'Nội dung tin nhắn không được để trống.');
            }
        });
    }

    public function attributes(): array
    {
        return ['noi_dung' => 'nội dung tin nhắn', 'client_message_id' => 'mã tin nhắn', 'tin_nhan_id' => 'tin nhắn đã đọc', 'anh' => 'ảnh đính kèm', 'anh.*' => 'ảnh đính kèm'];
    }

    public function messages(): array
    {
        return ['anh.max' => 'Mỗi tin nhắn tối đa 4 ảnh.', 'anh.*.max' => 'Mỗi ảnh tối đa 5 MB.', 'anh.*.mimetypes' => 'Chỉ nhận ảnh JPG, PNG hoặc WebP.', 'anh.*.image' => 'Tệp đính kèm phải là ảnh hợp lệ.', 'anh.*.dimensions' => 'Ảnh không được vượt quá 8000 pixel mỗi chiều.'];
    }
}
