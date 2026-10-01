<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class TrangThaiBaiTapRequest extends GhiBaiTapRequest
{
    public function rules(): array
    {
        return ['trang_thai' => ['required', Rule::in(['HOAT_DONG', 'NGUNG_SU_DUNG'])], 'updated_at' => ['required', 'date_format:Y-m-d H:i:s.u']];
    }
}
