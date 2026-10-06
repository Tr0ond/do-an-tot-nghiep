<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class LichCanDongBo implements ShouldBroadcastNow
{
    public function __construct(private array $taiKhoanIds) {}

    public function broadcastOn(): array
    {
        return array_map(fn ($id) => new PrivateChannel('chat.tai-khoan.'.$id), array_values(array_unique($this->taiKhoanIds)));
    }

    public function broadcastAs(): string
    {
        return 'lich.cap-nhat';
    }

    public function broadcastWith(): array
    {
        // Chỉ yêu cầu tải lại, không lộ lịch/học viên trên socket đã bị thu hồi quyền.
        return ['can_dong_bo' => true];
    }
}
