<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DoiSoatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->vai_tro === 'ADMIN';
    }

    public function rules(): array
    {
        return ['ly_do' => ['required', 'string', 'min:5', 'max:1000'], 'so_tien_hoan' => ['required', 'integer', 'min:1'], 'ma_hoan_tien' => ['required', 'string', 'max:191']];
    }
}
