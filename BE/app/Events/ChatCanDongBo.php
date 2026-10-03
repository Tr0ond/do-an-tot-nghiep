<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChatCanDongBo implements ShouldBroadcastNow
{
    public function __construct(private array $taiKhoanIds) {}

    public function broadcastOn(): array
    {
        return array_map(fn ($id) => new PrivateChannel('chat.tai-khoan.'.$id), array_unique($this->taiKhoanIds));
    }

    public function broadcastAs(): string
    {
        return 'chat.cap-nhat';
    }

    public function broadcastWith(): array
    {
        // Socket cũ chỉ nhận tín hiệu; nội dung luôn qua HTTP kiểm tra quyền hiện tại.
        return ['can_dong_bo' => true];
    }
}
