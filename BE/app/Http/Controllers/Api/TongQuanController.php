<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TongQuanRequest;
use App\Models\BaiTap;
use App\Models\GiaoAnMau;
use App\Models\GoiTap;
use App\Models\NhomCo;
use App\Models\TaiKhoan;
use App\Services\TongQuanKhachHangService;
use App\Services\TongQuanPtService;
use Illuminate\Http\JsonResponse;

class TongQuanController extends Controller
{
    public function index(TongQuanRequest $request): JsonResponse
    {
        $taiKhoan = $request->user();
        $thuVien = [
            'bai_tap' => BaiTap::dangHienThi()->count(),
            'nhom_co' => NhomCo::where('trang_thai', 'HOAT_DONG')->whereHas('baiTap', fn ($q) => $q->where('trang_thai', 'HOAT_DONG'))->count(),
            'goi_tap' => GoiTap::dangHienThi()->count(),
        ];
        $duLieu = ['vai_tro' => $taiKhoan->vai_tro, 'cap_nhat_luc' => now()->toIso8601String(), 'thu_vien' => $thuVien];
        if ($taiKhoan->vai_tro === TaiKhoan::ADMIN) {
            $cacVaiTro = TaiKhoan::selectRaw('vai_tro, COUNT(*) AS so_luong')->groupBy('vai_tro')->pluck('so_luong', 'vai_tro');
            $duLieu['quan_tri'] = [
                'tai_khoan' => [
                    'tong' => TaiKhoan::count(), 'hoat_dong' => TaiKhoan::where('trang_thai', TaiKhoan::HOAT_DONG)->count(),
                    'bi_khoa' => TaiKhoan::where('trang_thai', TaiKhoan::BI_KHOA)->count(),
                    'khach_hang' => (int) ($cacVaiTro[TaiKhoan::KHACH_HANG] ?? 0),
                    'huan_luyen_vien' => (int) ($cacVaiTro[TaiKhoan::HUAN_LUYEN_VIEN] ?? 0),
                    'admin' => (int) ($cacVaiTro[TaiKhoan::ADMIN] ?? 0),
                ],
                'bai_tap' => ['tong' => BaiTap::count(), 'hien_thi' => $thuVien['bai_tap']],
                'nhom_co' => ['tong' => NhomCo::count(), 'hoat_dong' => NhomCo::where('trang_thai', 'HOAT_DONG')->count()],
                'goi_tap' => ['tong' => GoiTap::count(), 'dang_ban' => $thuVien['goi_tap']],
                'giao_an_mau' => [
                    'tong' => GiaoAnMau::count(), 'da_duyet' => GiaoAnMau::where('trang_thai', 'DA_DUYET')->count(),
                    'ban_nhap' => GiaoAnMau::where('trang_thai', 'NHAP')->count(),
                    'ngung_su_dung' => GiaoAnMau::where('trang_thai', 'NGUNG_SU_DUNG')->count(),
                ],
            ];
        } else {
            $laPt = $taiKhoan->vai_tro === TaiKhoan::HUAN_LUYEN_VIEN;
            $hoSo = $taiKhoan->{$laPt ? 'hoSoHuanLuyenVien' : 'hoSoKhachHang'};
            $cacTruong = $laPt ? ['chuyen_mon' => 'Chuyên môn', 'gioi_thieu' => 'Giới thiệu'] : [
                'ngay_sinh' => 'Ngày sinh', 'gioi_tinh' => 'Giới tính', 'muc_tieu' => 'Mục tiêu tập luyện',
                'kinh_nghiem' => 'Kinh nghiệm', 'thoi_gian_co_the_tap' => 'Thời gian có thể tập',
            ];
            $cacMuc = [['ma' => 'ho_ten', 'nhan' => 'Họ và tên', 'da_co' => filled($taiKhoan->ho_ten)]];
            foreach ($cacTruong as $ma => $nhan) {
                $cacMuc[] = ['ma' => $ma, 'nhan' => $nhan, 'da_co' => filled($hoSo?->$ma)];
            }
            $duLieu['ho_so'] = ['hoan_thanh' => count(array_filter($cacMuc, fn ($muc) => $muc['da_co'])), 'tong_muc' => count($cacMuc), 'cac_muc' => $cacMuc];
            if ($laPt) {
                $duLieu['giao_an_da_duyet'] = GiaoAnMau::where('trang_thai', 'DA_DUYET')->count();
                $duLieu['huan_luyen'] = app(TongQuanPtService::class)->doc($taiKhoan);
            } else {
                $duLieu['hanh_trinh'] = app(TongQuanKhachHangService::class)->doc($taiKhoan, (int) ($request->validated('so_ngay') ?? 30));
            }
        }

        return response()->json(['status' => true, 'message' => 'Đã tải tổng quan.', 'data' => $duLieu])->header('Cache-Control', 'private, no-store');
    }
}
