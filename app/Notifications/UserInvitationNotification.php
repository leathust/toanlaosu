<?php

namespace App\Notifications;

use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public UserInvitation $invitation, public string $token) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Lời mời tham gia Toán Lão Sư')
            ->greeting('Xin chào '.$this->invitation->name.',')
            ->line('Bạn đã được mời tham gia với vai trò '.$this->invitation->role->display_name.'.')
            ->line('Liên kết này hết hạn vào '.$this->invitation->expires_at->timezone(config('app.timezone'))->format('d/m/Y H:i').'.')
            ->action('Thiết lập mật khẩu', route('invite.accept', ['token' => $this->token]))
            ->line('Nếu bạn không mong đợi lời mời này, hãy bỏ qua email này.');
    }
}
