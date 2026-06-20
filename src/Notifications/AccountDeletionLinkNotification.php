<?php

namespace DoITs\EasyAuth\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeletionLinkNotification extends Notification
{
    public function __construct(public readonly string $url) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('easy-auth::account_deletion.mail_subject'))
            ->line(__('easy-auth::account_deletion.mail_intro'))
            ->line(__('easy-auth::account_deletion.mail_safe_notice'))
            ->action(__('easy-auth::account_deletion.mail_action'), $this->url)
            ->line(__('easy-auth::account_deletion.mail_expires'));
    }
}
