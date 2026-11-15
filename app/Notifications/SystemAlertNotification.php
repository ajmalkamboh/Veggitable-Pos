<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ?Tenant $tenant,
        public string $title,
        public string $titleUr,
        public string $message,
        public string $level = 'info', // info, warning, danger, success
        public ?string $actionUrl = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting(__('Dear :name,', ['name' => $notifiable->name]))
            ->line($this->message);

        if ($this->actionUrl) {
            $mail->action(__('View Details', []), url($this->actionUrl));
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'system_alert',
            'tenant_id' => $this->tenant?->id,
            'title' => $this->title,
            'title_ur' => $this->titleUr,
            'message' => $this->message,
            'level' => $this->level,
            'action_url' => $this->actionUrl,
        ];
    }
}
