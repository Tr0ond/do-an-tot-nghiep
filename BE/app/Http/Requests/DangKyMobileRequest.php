<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class DangKyMobileRequest extends DangKyRequest
{
    public function after(): array
    {
        return [function (Validator $v) {
            foreach (array_diff(array_keys($this->all()), ['ho_ten', 'email', 'password', 'password_confirmation']) as $truong) {
                $v->errors()->add($truong, 'Trường này không được phép gửi.');
            }
        }];
    }
}
