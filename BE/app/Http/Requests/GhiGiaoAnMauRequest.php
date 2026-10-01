<?php

namespace App\Http\Requests;

use App\Models\TaiKhoan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GhiGiaoAnMauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->vai_tro === TaiKhoan::ADMIN;
    }

    public function rules(): array
    {
        if ($this->isMethod('PATCH')) {
            return ['trang_thai' => ['required', Rule::in(['DA_DUYET', 'NGUNG_SU_DUNG'])], 'updated_at' => ['required', 'date_format:Y-m-d H:i:s.u']];
        }

        return [...self::quyTacNoiDung(), ...($this->isMethod('POST') ? ['client_request_id' => ['required', 'uuid']] : ['updated_at' => ['required', 'date_format:Y-m-d H:i:s.u']])];
    }

    public static function quyTacNoiDung(): array
    {
        return [
            'ten_giao_an' => ['required', 'string', 'max:255'],
            'muc_tieu' => ['present', 'nullable', 'string', 'max:255'],
            'so_ngay_tap' => ['required', 'integer', 'between:1,30'],
            'bai_tap' => ['present', 'array', 'max:500'],
            'bai_tap.*' => ['required', 'array:bai_tap_id,ngay_thu,thu_tu,so_hiep,so_lan_lap,nghi_giay,ghi_chu'],
            'bai_tap.*.bai_tap_id' => ['required', 'integer', 'min:1'],
            'bai_tap.*.ngay_thu' => ['required', 'integer', 'between:1,30'],
            'bai_tap.*.thu_tu' => ['required', 'integer', 'between:1,500'],
            'bai_tap.*.so_hiep' => ['required', 'integer', 'between:1,100'],
            'bai_tap.*.so_lan_lap' => ['required', 'integer', 'between:1,1000'],
            'bai_tap.*.nghi_giay' => ['required', 'integer', 'between:0,3600'],
            'bai_tap.*.ghi_chu' => ['present', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $kiemTra) {
            $truongHopLe = array_filter(array_keys($this->rules()), fn ($truong) => ! str_contains($truong, '.'));
            foreach (array_diff(array_keys($this->all()), $truongHopLe) as $truong) {
                $kiemTra->errors()->add($truong, 'Trường này do hệ thống quản lý hoặc không được phép gửi.');
            }
            if ($this->isMethod('PATCH') || $kiemTra->errors()->isNotEmpty()) {
                return;
            }
            self::kiemTraThuTu($kiemTra, $this->all());
        }];
    }

    public static function kiemTraThuTu(Validator $kiemTra, array $duLieu): void
    {
        if ($kiemTra->errors()->isNotEmpty()) {
            return;
        }
        $thuTuTheoNgay = [];
        foreach ($duLieu['bai_tap'] as $viTri => $bai) {
            if ($bai['ngay_thu'] > $duLieu['so_ngay_tap']) {
                $kiemTra->errors()->add("bai_tap.$viTri.ngay_thu", 'Ngày của bài vượt quá số ngày tập.');
            }
            $thuTuTheoNgay[$bai['ngay_thu']][] = (int) $bai['thu_tu'];
        }
        foreach ($thuTuTheoNgay as $thuTu) {
            sort($thuTu);
            if ($thuTu !== range(1, count($thuTu))) {
                $kiemTra->errors()->add('bai_tap', 'Thứ tự bài trong mỗi ngày phải liên tục từ 1, không trùng.');
            }
        }
    }

    public function messages(): array
    {
        return ['required' => 'Vui lòng nhập :attribute.', 'present' => 'Thiếu trường :attribute.', 'integer' => ':attribute phải là số nguyên.', 'between' => ':attribute phải nằm trong khoảng :min–:max.', 'max' => ':attribute vượt quá giới hạn :max.', 'array' => ':attribute không đúng cấu trúc hoặc chứa trường không được phép.', 'in' => 'Trạng thái không hợp lệ.', 'uuid' => 'Mã yêu cầu không hợp lệ. Hãy mở lại biểu mẫu.', 'date_format' => 'Phiên bản không hợp lệ. Hãy tải lại giáo án.'];
    }
}
