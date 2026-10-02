<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class KhoiPhucMatKhau extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        // Fragment không được browser gửi lên server; domain chỉ lấy từ cấu hình tin cậy.
        $duongDan = rtrim(config('app.frontend_url'), '/').'/dat-lai-mat-khau#'.http_build_query(['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)->subject('Đặt lại mật khẩu — Huấn luyện cá nhân')
            ->greeting('Xin chào!')->line('Bạn vừa yêu cầu đặt lại mật khẩu tài khoản.')
            ->action('Đặt lại mật khẩu', $duongDan)
            ->line('Liên kết có hiệu lực trong '.config('auth.passwords.tai_khoan.expire').' phút và chỉ dùng được một lần.')
            ->line('Nếu bạn không yêu cầu, hãy bỏ qua email này.')->salutation('Huấn luyện cá nhân');
    }
}
