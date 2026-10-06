<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

class KhoiPhucMatKhau extends ResetPassword
{
    public function __construct($token, private bool $mobile = false)
    {
        parent::__construct($token);
    }

    public function toMail($notifiable): MailMessage
    {
        // Fragment không được browser gửi lên server; domain chỉ lấy từ cấu hình tin cậy.
        $duongDan = rtrim(config('app.frontend_url'), '/').'/dat-lai-mat-khau#'.http_build_query(['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        $app = 'fitforge://dat-lai-mat-khau#'.http_build_query(['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)->subject('Đặt lại mật khẩu — FitForge')
            ->greeting('Xin chào!')->line('Bạn vừa yêu cầu đặt lại mật khẩu tài khoản.')
            ->action($this->mobile ? 'Mở FitForge để đặt lại mật khẩu' : 'Đặt lại mật khẩu', $this->mobile ? $app : $duongDan)
            ->line(new HtmlString('Nếu chưa cài ứng dụng, <a href="'.e($duongDan).'">đặt lại trên website</a>.'))
            ->line('Liên kết có hiệu lực trong '.config('auth.passwords.tai_khoan.expire').' phút và chỉ dùng được một lần.')
            ->line('Nếu bạn không yêu cầu, hãy bỏ qua email này.')->salutation('FitForge');
    }
}
