<?php

namespace Database\Seeders;

use App\Models\BaiTap;
use App\Models\NhomCo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BaiTapSeeder extends Seeder
{
    public function run(): void
    {
        $nhomCo = json_decode(file_get_contents(database_path('data/nhom_co.json')), true, 512, JSON_THROW_ON_ERROR);
        $baiTap = json_decode(file_get_contents(database_path('data/bai_tap.json')), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($nhomCo) || ! is_array($baiTap) || count($nhomCo) !== 19 || count($baiTap) !== 1324) {
            throw new RuntimeException('Catalog phải có đủ 19 nhóm cơ và 1.324 bài tập.');
        }
        $nhomTheoId = array_column($nhomCo, 'ma_nhom_co', 'id');
        if (count($nhomTheoId) !== 19 || count(array_unique($nhomTheoId)) !== 19
            || count(array_unique(array_column($baiTap, 'ma_nguon'))) !== 1324) {
            throw new RuntimeException('Catalog có mã nhóm cơ hoặc mã bài tập trùng/thiếu.');
        }
        foreach ($baiTap as $bai) {
            if (! isset($nhomTheoId[$bai['nhom_co_id']]) || $bai['nguon_du_lieu'] !== 'exercises-dataset'
                || ! preg_match('/^\d{4}$/', $bai['ma_nguon'])) {
                throw new RuntimeException('Bài tập có nhóm cơ hoặc mã nguồn không hợp lệ.');
            }
            foreach (['anh_url', 'gif_url'] as $truong) {
                if (! preg_match('#^/media/bai-tap/(images|animations)/[a-zA-Z0-9_-]+\.(jpg|gif)$#', $bai[$truong])
                    || ! is_file(public_path(ltrim($bai[$truong], '/')))) {
                    throw new RuntimeException('Thiếu hoặc sai media của bài tập '.$bai['ma_nguon'].'.');
                }
            }
        }

        DB::transaction(function () use ($nhomCo, $baiTap, $nhomTheoId) {
            $idTheoMa = [];
            foreach ($nhomCo as $nhom) {
                $banGhi = NhomCo::firstOrCreate(['ma_nhom_co' => $nhom['ma_nhom_co']], Arr::except($nhom, ['id']));
                $idTheoMa[$nhom['ma_nhom_co']] = $banGhi->id;
            }
            foreach ($baiTap as $bai) {
                // ID JSON chỉ dùng để tra nhóm nguồn, không ghi đè PK hoặc nội dung đã biên tập.
                $duLieu = Arr::except($bai, ['id']);
                $duLieu['nhom_co_id'] = $idTheoMa[$nhomTheoId[$bai['nhom_co_id']]];
                BaiTap::firstOrCreate(Arr::only($duLieu, ['nguon_du_lieu', 'ma_nguon']), $duLieu);
            }
        });
    }
}
