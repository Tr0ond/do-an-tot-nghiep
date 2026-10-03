<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand;

class ChayLocal extends ServeCommand
{
    protected $signature = 'serve:local {--host=localhost} {--port=8000} {--tries=1} {--no-reload}';

    protected $description = 'Chạy Backend local với giới hạn gửi ảnh chat 5 MB/ảnh, tổng request 24 MB';

    protected function serverCommand()
    {
        $lenh = parent::serverCommand();
        // Tham số phải truyền vào PHP server con; đặt -d trước artisan serve không được kế thừa.
        array_splice($lenh, 1, 0, ['-d', 'upload_max_filesize=5M', '-d', 'post_max_size=24M']);

        return $lenh;
    }
}
