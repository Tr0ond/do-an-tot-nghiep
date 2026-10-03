<?php

namespace App\Services;

use App\Models\TaiKhoan;
use App\Models\TaiLieuTuVan;
use Illuminate\Support\Facades\DB;

class TaiLieuTuVanService
{
    public function tao(TaiKhoan $nguoi, array $d): TaiLieuTuVan
    {
        abort_unless($nguoi->vai_tro === TaiKhoan::ADMIN, 403);

        return DB::transaction(function () use ($nguoi, $d) {
            // Unique chống trùng; khóa actor để hai request tạo của cùng Admin được tuần tự.
            TaiKhoan::whereKey($nguoi->id)->lockForUpdate()->firstOrFail();
            $hash = hash('sha256', json_encode([$nguoi->id, $d['tieu_de'], $d['loai'], $d['noi_dung']], JSON_UNESCAPED_UNICODE));
            $cu = TaiLieuTuVan::where('client_request_id', $d['client_request_id'])->first();
            if ($cu) {
                abort_unless(hash_equals((string) $cu->payload_hash, $hash), 409);

                return $cu;
            }

            return TaiLieuTuVan::create([...$d, 'payload_hash' => $hash, 'phien_ban' => 1, 'nguoi_cap_nhat_id' => $nguoi->id, 'trang_thai' => 'NHAP']);
        }, 3);
    }

    public function sua(TaiKhoan $nguoi, int $id, array $d, ?string $hanhDong = null): TaiLieuTuVan
    {
        abort_unless($nguoi->vai_tro === TaiKhoan::ADMIN, 403);

        return DB::transaction(function () use ($nguoi, $id, $d, $hanhDong) {
            $t = TaiLieuTuVan::whereKey($id)->lockForUpdate()->firstOrFail();
            $moi = $hanhDong === 'xuat-ban' ? 'DA_XUAT_BAN' : 'NGUNG_SU_DUNG';
            if ($hanhDong && $t->trang_thai === $moi && in_array($t->phien_ban, [(int) $d['phien_ban'], (int) $d['phien_ban'] + 1], true)) {
                return $t;
            }
            abort_unless($t->phien_ban === (int) $d['phien_ban'], 409, 'Tài liệu đã thay đổi. Hãy tải lại trước khi lưu.');
            if ($hanhDong) {
                abort_if($hanhDong === 'ngung' && $t->trang_thai !== 'DA_XUAT_BAN', 409);
                $t->fill(['trang_thai' => $moi, 'xuat_ban_luc' => $hanhDong === 'xuat-ban' ? now() : $t->xuat_ban_luc]);
            } else {
                $t->fill(array_intersect_key($d, array_flip(['tieu_de', 'loai', 'noi_dung'])));
                // Nội dung sửa cần được Admin xuất bản lại, tránh thay nguồn AI ngay lúc đang soạn.
                $t->trang_thai = 'NHAP';
            }
            $t->phien_ban++;
            $t->nguoi_cap_nhat_id = $nguoi->id;
            $t->save();

            return $t;
        }, 3);
    }
}
