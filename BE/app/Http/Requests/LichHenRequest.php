<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LichHenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['khung_gio_id' => 'required|integer|min:1', 'client_request_id' => 'required|uuid'];
    }
}
