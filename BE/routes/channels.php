<?php

use App\Models\TaiKhoan;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.tai-khoan.{id}', function (TaiKhoan $nguoi, string $id) {
    return (string) $nguoi->id === $id && $nguoi->trang_thai === TaiKhoan::HOAT_DONG
        && in_array($nguoi->vai_tro, [TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN], true);
}, ['guards' => ['web']]);
