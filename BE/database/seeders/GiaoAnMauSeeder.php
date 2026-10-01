<?php

namespace Database\Seeders;

use App\Models\BaiTap;
use App\Models\GiaoAnMau;
use App\Models\TaiKhoan;
use App\Services\GiaoAnMauService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GiaoAnMauSeeder extends Seeder
{
    public function run(GiaoAnMauService $dichVu): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Seeder giáo án demo chỉ chạy trong môi trường local hoặc testing.');
        }

        [$soTao, $soBoQua] = DB::transaction(function () use ($dichVu) {
            $admin = TaiKhoan::where('email', 'admin@example.test')->lockForUpdate()->first();
            if ($admin === null || $admin->vai_tro !== TaiKhoan::ADMIN || $admin->trang_thai !== TaiKhoan::HOAT_DONG) {
                throw new RuntimeException('Cần Admin demo admin@example.test đang hoạt động. Chạy TaiKhoanSeeder nếu chưa có; tài khoản sai vai trò hoặc bị khóa cần xử lý riêng.');
            }

            $soTao = 0;
            $soBoQua = 0;
            foreach ($this->cacGiaoAn() as $mau) {
                // UUID giữ danh tính khi đổi tên; không ghi đè nội dung hoặc trạng thái đã chỉnh sửa.
                if (GiaoAnMau::where('ma_yeu_cau_tao', $mau['client_request_id'])->lockForUpdate()->exists()) {
                    $soBoQua++;

                    continue;
                }

                $cacMa = array_unique(array_merge(...$mau['cac_ngay']));
                $cacBai = BaiTap::where('nguon_du_lieu', 'exercises-dataset')->whereIn('ma_nguon', $cacMa)->get()->keyBy('ma_nguon');
                $cacDong = [];
                foreach ($mau['cac_ngay'] as $ngay => $cacMaNgay) {
                    foreach ($cacMaNgay as $thuTu => $ma) {
                        $bai = $cacBai->get($ma);
                        if ($bai === null) {
                            throw new RuntimeException('Thiếu bài nguồn '.$ma.' cho giáo án '.$mau['ten_giao_an'].'. Chạy BaiTapSeeder trước.');
                        }
                        $cacDong[] = [
                            'bai_tap_id' => $bai->id,
                            'ngay_thu' => $ngay + 1,
                            'thu_tu' => $thuTu + 1,
                            'so_hiep' => 3,
                            'so_lan_lap' => $mau['so_lan_lap'],
                            'nghi_giay' => $mau['nghi_giay'],
                            'ghi_chu' => 'Thông số demo; PT điều chỉnh khi lập kế hoạch cho khách hàng.',
                        ];
                    }
                }

                // Dùng nghiệp vụ duyệt để kiểm tra ngày/thứ tự và bài/nhóm hoạt động trước khi công bố.
                $giaoAn = $dichVu->taoGiaoAn([
                    'client_request_id' => $mau['client_request_id'],
                    'ten_giao_an' => $mau['ten_giao_an'],
                    'muc_tieu' => $mau['muc_tieu'],
                    'so_ngay_tap' => count($mau['cac_ngay']),
                    'bai_tap' => $cacDong,
                ], $admin->id);
                $dichVu->datTrangThai($giaoAn->id, [
                    'trang_thai' => 'DA_DUYET',
                    'updated_at' => $giaoAn->updated_at->format('Y-m-d H:i:s.u'),
                ], $admin->id);
                $soTao++;
            }

            return [$soTao, $soBoQua];
        }, 3);

        $this->command?->info("Giáo án demo: tạo $soTao, giữ nguyên $soBoQua giáo án đã có.");
    }

    private function cacGiaoAn(): array
    {
        // Các mã thuộc catalog nguồn; ID bài tập trong database có thể khác ID trong JSON.
        return [
            [
                'client_request_id' => '78c6a517-f9f2-4b72-8c42-f581c356e001',
                'ten_giao_an' => 'Toàn thân cơ bản (demo)',
                'muc_tieu' => 'Minh họa giáo án toàn thân 3 ngày với tạ đơn và bài trọng lượng cơ thể.',
                'so_lan_lap' => 12, 'nghi_giay' => 60,
                'cac_ngay' => [
                    ['0413', '0289', '0293', '0274'],
                    ['1460', '0405', '0861', '0872'],
                    ['0413', '0662', '0293', '1373'],
                ],
            ],
            [
                'client_request_id' => '78c6a517-f9f2-4b72-8c42-f581c356e002',
                'ten_giao_an' => 'Thân trên - thân dưới 4 ngày (demo)',
                'muc_tieu' => 'Minh họa cách chia 2 ngày thân trên và 2 ngày thân dưới tại phòng tập.',
                'so_lan_lap' => 10, 'nghi_giay' => 90,
                'cac_ngay' => [
                    ['0025', '0861', '0405', '0294'],
                    ['0043', '0085', '0586', '1373'],
                    ['0289', '0293', '0334', '0129'],
                    ['0413', '0585', '0586', '0872'],
                ],
            ],
            [
                'client_request_id' => '78c6a517-f9f2-4b72-8c42-f581c356e003',
                'ten_giao_an' => 'Đẩy - kéo - chân 3 ngày (demo)',
                'muc_tieu' => 'Minh họa chia ngày đẩy, ngày kéo và ngày chân với bài trong catalog.',
                'so_lan_lap' => 10, 'nghi_giay' => 90,
                'cac_ngay' => [
                    ['0025', '0405', '0334', '0129'],
                    ['2330', '0027', '0293', '0294'],
                    ['0043', '0085', '0585', '1373'],
                ],
            ],
            [
                'client_request_id' => '78c6a517-f9f2-4b72-8c42-f581c356e004',
                'ten_giao_an' => 'Tập tại nhà không tạ (demo)',
                'muc_tieu' => 'Minh họa 3 ngày tập với trọng lượng cơ thể, không cần tạ hoặc máy tập.',
                'so_lan_lap' => 12, 'nghi_giay' => 60,
                'cac_ngay' => [
                    ['1685', '0662', '0274', '1373'],
                    ['1460', '1399', '0872', '0006'],
                    ['1685', '0662', '0630', '0274'],
                ],
            ],
            [
                'client_request_id' => '78c6a517-f9f2-4b72-8c42-f581c356e005',
                'ten_giao_an' => 'Cơ bụng và thể lực 2 ngày (demo)',
                'muc_tieu' => 'Minh họa giáo án ngắn kết hợp bài cơ bụng và vận động toàn thân.',
                'so_lan_lap' => 12, 'nghi_giay' => 60,
                'cac_ngay' => [
                    ['3224', '0630', '0274', '0006'],
                    ['1160', '1685', '0872', '0274'],
                ],
            ],
        ];
    }
}
