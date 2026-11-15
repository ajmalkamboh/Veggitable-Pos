<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiryNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public int $daysRemaining,
        public string $expiryDate,
        public string $planName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Subscription Expiry Notice: :days Days Remaining - :shop', ['days' => $this->daysRemaining, 'shop' => $this->tenant->name]))
            ->greeting(__('Dear :name,', ['name' => $notifiable->name]))
            ->line(__('Your subscription plan (:plan) for :shop will expire in :days days on :date.', [
                'plan' => $this->planName,
                'shop' => $this->tenant->name,
                'days' => $this->daysRemaining,
                'date' => $this->expiryDate,
            ]))
            ->line(__('To prevent interruption of POS counter billing and produce inventory management, please renew your subscription.'))
            ->action(__('Renew Subscription', []), url('/tenant/subscription'))
            ->line(__('Thank you for choosing Veggitable SaaS System.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_expiry',
            'tenant_id' => $this->tenant->id,
            'title' => __('Subscription Expiry Notice (:days Days Left)', ['days' => $this->daysRemaining]),
            'title_ur' => 'سبسکرپشن کی معیاد ختم ہونے کا نوٹس ('.$this->daysRemaining.' دن باقی)',
            'message' => __('Your :plan subscription expires on :date. Please renew to keep POS billing active.', [
                'plan' => $this->planName,
                'date' => $this->expiryDate,
            ]),
            'days_remaining' => $this->daysRemaining,
            'expiry_date' => $this->expiryDate,
            'plan_name' => $this->planName,
            'action_url' => '/tenant/subscription',
        ];
    }
}
