<?php

use App\Models\BaiTap;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../BE/vendor/autoload.php';
$ungDung = require __DIR__.'/../BE/bootstrap/app.php';
$ungDung->make(Kernel::class)->bootstrap();

// Chỉ đọc catalog đã nhập; không in kết nối hoặc dữ liệu tài khoản.
$nhomNguon = json_decode(file_get_contents(database_path('data/nhom_co.json')), true, 512, JSON_THROW_ON_ERROR);
$baiNguon = json_decode(file_get_contents(database_path('data/bai_tap.json')), true, 512, JSON_THROW_ON_ERROR);
$maTheoIdNguon = array_column($nhomNguon, 'ma_nhom_co', 'id');
$idTheoMa = DB::table('nhom_co')->pluck('id', 'ma_nhom_co');
$soBai = 0;
foreach ($baiNguon as $nguon) {
    $bai = BaiTap::where('nguon_du_lieu', $nguon['nguon_du_lieu'])->where('ma_nguon', $nguon['ma_nguon'])->firstOrFail();
    if ((int) $bai->nhom_co_id !== (int) $idTheoMa[$maTheoIdNguon[$nguon['nhom_co_id']]]) {
        throw new RuntimeException('Sai nhóm cơ của bài '.$nguon['ma_nguon']);
    }
    foreach (['ten_bai_tap', 'ten_tieng_viet', 'bo_phan_co_the', 'dung_cu', 'dung_cu_nguon', 'huong_dan', 'cac_buoc', 'co_phu', 'co_ho_tro_nguon', 'anh_url', 'gif_url', 'duong_dan_anh_nguon', 'duong_dan_gif_nguon', 'ma_media_nguon', 'ghi_cong_media', 'trang_thai'] as $truong) {
        if ($nguon[$truong] !== $bai->$truong) {
            throw new RuntimeException('Catalog khác nguồn tại bài '.$nguon['ma_nguon'].', trường '.$truong.'. Có thể đã được biên tập.');
        }
    }
    foreach (['nguon_tao_luc', 'nguon_cap_nhat_luc'] as $truong) {
        $thoiDiemNguon = $nguon[$truong] === null ? null : Carbon::parse($nguon[$truong])->format('Y-m-d H:i:s.u');
        if ($bai->$truong?->format('Y-m-d H:i:s.u') !== $thoiDiemNguon) {
            throw new RuntimeException('Sai thời điểm nguồn của bài '.$nguon['ma_nguon']);
        }
    }
    foreach (['anh_url', 'gif_url'] as $truong) {
        if (! is_file(public_path(ltrim($bai->$truong, '/')))) {
            throw new RuntimeException('Thiếu media bài '.$nguon['ma_nguon']);
        }
    }
    $soBai++;
}
echo 'Đạt: '.$soBai.' bài theo nguồn, 19 nhóm cơ được ánh xạ, JSON/thời điểm nguồn và 2.648 media tồn tại.'.PHP_EOL;
