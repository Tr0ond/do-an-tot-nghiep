<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ChatbotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'store' => ['client_request_id' => ['required', 'uuid']],
            'gui' => ['client_request_id' => ['required', 'uuid'], 'noi_dung' => ['required', 'string', 'max:2000'], 'dung_du_lieu_ca_nhan' => ['required', 'boolean']],
            default => ['page' => ['sometimes', 'integer', 'min:1', 'max:10000']],
        };
    }

    public function after(): array
    {
        return [function (Validator $v) {
            foreach (array_diff(array_keys($this->all()), array_keys($this->rules())) as $truong) {
                $v->errors()->add($truong, 'Trường không được hỗ trợ.');
            }
        }];
    }
}
