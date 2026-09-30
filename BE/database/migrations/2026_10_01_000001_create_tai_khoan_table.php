<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Generated column và CHECK phải được database thực thi để giữ quy tắc dữ liệu.
        $ketNoi = Schema::getConnection();
        if (! in_array($ketNoi->getDriverName(), ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Bộ migrations nghiệp vụ yêu cầu MySQL 8.0.16+ hoặc MariaDB 10.4+.');
        }

        // Đọc từ PDO để cả migrate --pretend cũng kiểm tra được phiên bản thực tế.
        $pdo = $ketNoi->getPdo();
        $phienBan = (string) $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        $laMariaDb = stripos($phienBan, 'MariaDB') !== false;
        // MariaDB có thể trả tiền tố tương thích 5.5.5- trước phiên bản máy chủ.
        $mauPhienBan = $laMariaDb ? '/(\d+\.\d+\.\d+)(?=-MariaDB)/i' : '/^(\d+\.\d+\.\d+)/';
        $phienBanToiThieu = $laMariaDb ? '10.4.0' : '8.0.16';
        if (! preg_match($mauPhienBan, $phienBan, $ketQua)
            || version_compare($ketQua[1], $phienBanToiThieu, '<')) {
            throw new RuntimeException('Bộ migrations yêu cầu MySQL 8.0.16+ hoặc MariaDB 10.4+; phiên bản hiện tại: '.$phienBan);
        }

        if ($laMariaDb && (int) $pdo->query('SELECT @@SESSION.check_constraint_checks')->fetchColumn() !== 1) {
            throw new RuntimeException('MariaDB phải bật check_constraint_checks để thực thi ràng buộc CHECK.');
        }

        Schema::create('tai_khoan', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('ho_ten', 255)->nullable();
            $table->string('email', 191);
            $table->string('password', 255);
            $table->enum('vai_tro', ['KHACH_HANG', 'HUAN_LUYEN_VIEN', 'ADMIN']);
            $table->string('trang_thai', 32);
            $table->string('remember_token', 100)->nullable();
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();

            $table->unique(['email'], 'uq_t01_01');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tai_khoan');
    }
};
