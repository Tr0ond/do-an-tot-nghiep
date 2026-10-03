<?php

namespace App\Services;

use App\Events\ChatCanDongBo;
use App\Models\HoSoKhachHang;
use App\Models\TaiKhoan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ChatService
{
    public function baoCapNhat(array $taiKhoanIds): void
    {
        DB::afterCommit(function () use ($taiKhoanIds) {
            try {
                event(new ChatCanDongBo($taiKhoanIds));
            } catch (\Throwable) {
                // Tin đã lưu không bị báo thất bại vì WebSocket tạm ngừng; client tải bù qua HTTP.
                Log::warning('Chat: chưa gửi được tín hiệu đồng bộ realtime.');
            }
        });
    }

    private function truyVan(TaiKhoan $nguoi)
    {
        abort_unless(in_array($nguoi->vai_tro, [TaiKhoan::KHACH_HANG, TaiKhoan::HUAN_LUYEN_VIEN], true), 403);
        abort_unless(TaiKhoan::whereKey($nguoi->id)->where('trang_thai', TaiKhoan::HOAT_DONG)->exists(), 403);
        $query = DB::table('hoi_thoai as h')
            ->join('phan_cong_huan_luyen_vien as p', 'p.id', '=', 'h.phan_cong_id')
            ->join('ho_so_khach_hang as k', 'k.id', '=', 'p.khach_hang_id')
            ->join('ho_so_huan_luyen_vien as pt', 'pt.id', '=', 'p.huan_luyen_vien_id')
            ->join('tai_khoan as tk', 'tk.id', '=', 'k.tai_khoan_id')
            ->join('tai_khoan as tp', 'tp.id', '=', 'pt.tai_khoan_id');
        if ($nguoi->vai_tro === TaiKhoan::KHACH_HANG) {
            $query->where('k.tai_khoan_id', $nguoi->id);
        } else {
            $query->where('pt.tai_khoan_id', $nguoi->id)->whereNull('p.ket_thuc_luc');
        }

        return $query->select('h.*', 'p.khach_hang_id', 'p.ket_thuc_luc', 'p.bat_dau_luc', 'tk.id as khach_tai_khoan_id', 'tp.id as pt_tai_khoan_id', 'tk.ho_ten as ten_khach', 'tp.ho_ten as ten_pt', 'tk.trang_thai as trang_thai_khach', 'tp.trang_thai as trang_thai_pt');
    }

    private function duLieuHoiThoai(object $hoi, TaiKhoan $nguoi): array
    {
        $laKhach = $nguoi->vai_tro === TaiKhoan::KHACH_HANG;

        return [
            'id' => (int) $hoi->id,
            'phan_cong_id' => (int) $hoi->phan_cong_id,
            'doi_phuong' => ['id' => (int) ($laKhach ? $hoi->pt_tai_khoan_id : $hoi->khach_tai_khoan_id), 'ho_ten' => $laKhach ? $hoi->ten_pt : $hoi->ten_khach, 'vai_tro' => $laKhach ? TaiKhoan::HUAN_LUYEN_VIEN : TaiKhoan::KHACH_HANG],
            'co_the_gui' => $hoi->ket_thuc_luc === null && $hoi->trang_thai_khach === TaiKhoan::HOAT_DONG && $hoi->trang_thai_pt === TaiKhoan::HOAT_DONG,
            'da_ket_thuc' => $hoi->ket_thuc_luc !== null,
            'cursor_da_doc' => (int) ($laKhach ? $hoi->cursor_khach_da_doc : $hoi->cursor_pt_da_doc),
            'cursor_doi_phuong' => (int) ($laKhach ? $hoi->cursor_pt_da_doc : $hoi->cursor_khach_da_doc),
        ];
    }

    public function danhSach(TaiKhoan $nguoi, array $duLieu)
    {
        $query = $this->truyVan($nguoi);
        if (trim($duLieu['tu_khoa'] ?? '') !== '') {
            $cot = $nguoi->vai_tro === TaiKhoan::KHACH_HANG ? 'tp.ho_ten' : 'tk.ho_ten';
            $tuKhoa = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($duLieu['tu_khoa']));
            $query->where($cot, 'like', '%'.$tuKhoa.'%');
        }
        $cotCursor = $nguoi->vai_tro === TaiKhoan::KHACH_HANG ? 'h.cursor_khach_da_doc' : 'h.cursor_pt_da_doc';
        $query->selectSub(DB::table('tin_nhan')->selectRaw('COUNT(*)')->whereColumn('hoi_thoai_id', 'h.id')->where('nguoi_gui_id', '<>', $nguoi->id)->whereRaw('id > COALESCE('.$cotCursor.', 0)'), 'so_chua_doc');
        $query->selectSub(DB::table('tin_nhan')->select('noi_dung')->whereColumn('hoi_thoai_id', 'h.id')->orderByDesc('id')->limit(1), 'tin_cuoi');
        $query->selectSub(DB::table('tin_nhan')->select('created_at')->whereColumn('hoi_thoai_id', 'h.id')->orderByDesc('id')->limit(1), 'tin_cuoi_luc');
        $query->selectSub(DB::table('tin_nhan')->select('id')->whereColumn('hoi_thoai_id', 'h.id')->orderByDesc('id')->limit(1), 'tin_cuoi_id');
        $phanTrang = $query->orderByDesc('tin_cuoi_id')->orderByDesc('h.id')->paginate(20);
        $phanTrang->through(fn ($hoi) => [...$this->duLieuHoiThoai($hoi, $nguoi), 'so_chua_doc' => (int) $hoi->so_chua_doc, 'tin_cuoi' => $hoi->tin_cuoi === '' ? 'Đã gửi ảnh' : $hoi->tin_cuoi, 'tin_cuoi_luc' => $hoi->tin_cuoi_luc ? CarbonImmutable::parse($hoi->tin_cuoi_luc, 'UTC')->toISOString() : null]);

        return $phanTrang;
    }

    public function soChuaDoc(TaiKhoan $nguoi): int
    {
        $cot = $nguoi->vai_tro === TaiKhoan::KHACH_HANG ? 'h.cursor_khach_da_doc' : 'h.cursor_pt_da_doc';

        return $this->truyVan($nguoi)->join('tin_nhan as cd', 'cd.hoi_thoai_id', '=', 'h.id')
            ->where('cd.nguoi_gui_id', '<>', $nguoi->id)->whereRaw('cd.id > COALESCE('.$cot.', 0)')->count();
    }

    private function khoaHoiThoai(TaiKhoan $nguoi, int $id): object
    {
        $hoi = $this->truyVan($nguoi)->where('h.id', $id)->first();
        abort_unless($hoi, 404);
        // Cùng khóa KH với phân công để gửi/đọc không vượt qua transaction đổi PT.
        HoSoKhachHang::lockForUpdate()->findOrFail($hoi->khach_hang_id);
        $hoi = $this->truyVan($nguoi)->where('h.id', $id)->lockForUpdate()->first();
        abort_unless($hoi, 404);
        $trangThaiNguoi = $nguoi->vai_tro === TaiKhoan::KHACH_HANG ? $hoi->trang_thai_khach : $hoi->trang_thai_pt;
        abort_unless($trangThaiNguoi === TaiKhoan::HOAT_DONG, 403);

        return $hoi;
    }

    public function tinNhan(TaiKhoan $nguoi, int $id, array $duLieu): array
    {
        return DB::transaction(function () use ($nguoi, $id, $duLieu) {
            $hoi = $this->khoaHoiThoai($nguoi, $id);
            $query = DB::table('tin_nhan')->where('hoi_thoai_id', $id);
            $taiMoi = isset($duLieu['after_id']);
            if ($taiMoi) {
                $query->where('id', '>', $duLieu['after_id']);
            } elseif (isset($duLieu['before_id'])) {
                $query->where('id', '<', $duLieu['before_id']);
            }
            $cacTin = $query->orderBy('id', $taiMoi ? 'asc' : 'desc')->limit(51)->get();
            $conTin = $cacTin->count() > 50;
            $cacTin = $cacTin->take(50)->sortBy('id')->values();

            return ['hoi_thoai' => $this->duLieuHoiThoai($hoi, $nguoi), 'tin_nhan' => $cacTin->map(fn ($tin) => $this->duLieuTin($tin))->all(), 'con_tin' => $conTin];
        }, 3);
    }

    private function duLieuTin(object $tin): array
    {
        $anh = json_decode($tin->anh ?? '[]', true) ?: [];

        return ['id' => (int) $tin->id, 'hoi_thoai_id' => (int) $tin->hoi_thoai_id, 'nguoi_gui_id' => (int) $tin->nguoi_gui_id, 'client_message_id' => $tin->client_message_id, 'noi_dung' => $tin->noi_dung, 'anh' => array_map(fn ($tep, $viTri) => ['vi_tri' => $viTri, 'ten' => $tep['ten'], 'mime' => $tep['mime'], 'dung_luong' => $tep['dung_luong']], $anh, array_keys($anh)), 'created_at' => CarbonImmutable::parse($tin->created_at, 'UTC')->toISOString()];
    }

    public function gui(TaiKhoan $nguoi, int $id, array $duLieu): array
    {
        $cacTep = array_values($duLieu['anh'] ?? []);
        $cacHash = array_map(fn ($tep) => hash_file('sha256', $tep->getRealPath()), $cacTep);
        $noiDung = $duLieu['noi_dung'] ?? '';

        $daLuu = [];
        try {
            return DB::transaction(function () use ($nguoi, $id, $duLieu, $cacTep, $cacHash, $noiDung, &$daLuu) {
                // Khi transaction thử lại vì deadlock, dọn tệp của lần thử đã rollback.
                Storage::disk('local')->delete($daLuu);
                $daLuu = [];
                $hoi = $this->khoaHoiThoai($nguoi, $id);
                abort_unless($this->duLieuHoiThoai($hoi, $nguoi)['co_the_gui'], 409, 'Phân công đã kết thúc hoặc tài khoản đối phương đang bị khóa.');
                $ma = strtolower($duLieu['client_message_id']);
                $cu = DB::table('tin_nhan')->where('hoi_thoai_id', $id)->where('nguoi_gui_id', $nguoi->id)->where('client_message_id', $ma)->lockForUpdate()->first();
                if ($cu) {
                    $anhCu = json_decode($cu->anh ?? '[]', true) ?: [];
                    if ($cu->noi_dung !== $noiDung || array_column($anhCu, 'hash') !== $cacHash) {
                        throw new ConflictHttpException('Mã tin nhắn đã dùng với nội dung khác.');
                    }

                    return $this->duLieuTin($cu);
                }
                try {
                    $anh = [];
                    foreach ($cacTep as $viTri => $tep) {
                        $mime = $tep->getMimeType();
                        $duoi = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
                        $duongDan = 'chat/'.Str::uuid().'.'.$duoi;
                        $daLuu[] = $duongDan;
                        if (! Storage::disk('local')->putFileAs('chat', $tep, basename($duongDan))) {
                            throw new \RuntimeException('Không lưu được ảnh chat.');
                        }
                        $anh[] = ['duong_dan' => $duongDan, 'mime' => $mime, 'ten' => mb_substr(basename(str_replace('\\', '/', $tep->getClientOriginalName())), 0, 200), 'dung_luong' => $tep->getSize(), 'hash' => $cacHash[$viTri]];
                    }
                    $tinId = DB::table('tin_nhan')->insertGetId(['hoi_thoai_id' => $id, 'nguoi_gui_id' => $nguoi->id, 'client_message_id' => $ma, 'noi_dung' => $noiDung, 'anh' => $anh ? json_encode($anh, JSON_THROW_ON_ERROR) : null, 'created_at' => now(), 'updated_at' => now()]);
                    DB::table('hoi_thoai')->where('id', $id)->update(['updated_at' => now()]);
                    $this->baoCapNhat([$hoi->khach_tai_khoan_id, $hoi->pt_tai_khoan_id]);

                    return $this->duLieuTin(DB::table('tin_nhan')->find($tinId));
                } catch (\Throwable $loi) {
                    Storage::disk('local')->delete($daLuu);
                    throw $loi;
                }
            }, 3);
        } catch (\Throwable $loi) {
            Storage::disk('local')->delete($daLuu);
            throw $loi;
        }
    }

    public function anh(TaiKhoan $nguoi, int $id, int $tinId, int $viTri): array
    {
        return DB::transaction(function () use ($nguoi, $id, $tinId, $viTri) {
            $this->khoaHoiThoai($nguoi, $id);
            $tin = DB::table('tin_nhan')->where('hoi_thoai_id', $id)->where('id', $tinId)->first();
            abort_unless($tin, 404);
            $anh = json_decode($tin->anh ?? '[]', true) ?: [];
            abort_unless(isset($anh[$viTri]), 404);

            return $anh[$viTri];
        }, 3);
    }

    public function daDoc(TaiKhoan $nguoi, int $id, int $tinId): array
    {
        return DB::transaction(function () use ($nguoi, $id, $tinId) {
            $hoi = $this->khoaHoiThoai($nguoi, $id);
            abort_unless(DB::table('tin_nhan')->where('hoi_thoai_id', $id)->where('id', $tinId)->exists(), 422, 'Cursor không thuộc hội thoại.');
            $cot = $nguoi->vai_tro === TaiKhoan::KHACH_HANG ? 'cursor_khach_da_doc' : 'cursor_pt_da_doc';
            // Hàng đã khóa được đọc hiện tại, tránh cursor lùi do snapshot cũ của MySQL.
            $hienTai = (int) $hoi->$cot;
            if ($tinId > $hienTai) {
                DB::table('hoi_thoai')->where('id', $id)->update([$cot => $tinId]);
                $this->baoCapNhat([$hoi->khach_tai_khoan_id, $hoi->pt_tai_khoan_id]);
            }

            return ['cursor_da_doc' => max($hienTai, $tinId)];
        }, 3);
    }
}
