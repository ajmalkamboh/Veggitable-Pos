<?php

namespace App\Notifications;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerBalanceAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public Customer $customer,
        public float $currentBalance,
        public ?float $creditLimit = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Customer Overdue Credit Alert: :customer', ['customer' => $this->customer->name]))
            ->greeting(__('Dear :name,', ['name' => $notifiable->name]))
            ->line(__('Customer :customer has an outstanding balance of Rs. :balance.', [
                'customer' => $this->customer->name,
                'balance' => number_format($this->currentBalance, 2),
            ]))
            ->when($this->creditLimit, fn ($mail) => $mail->line(__('Credit Limit: Rs. :limit', ['limit' => number_format($this->creditLimit, 2)])))
            ->action(__('View Customer Account Statement', []), url('/tenant/customers/'.$this->customer->id))
            ->line(__('Please follow up for collection before issuing further credit produce.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'customer_balance_alert',
            'tenant_id' => $this->tenant->id,
            'title' => __('Overdue Balance: :customer', ['customer' => $this->customer->name]),
            'title_ur' => 'گاہک کا بقایا واجب الادا بیلنس: '.$this->customer->name,
            'message' => __('Outstanding Balance: Rs. :bal (Phone: :phone)', [
                'bal' => number_format($this->currentBalance, 2),
                'phone' => $this->customer->phone ?? 'N/A',
            ]),
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'current_balance' => $this->currentBalance,
            'credit_limit' => $this->creditLimit,
            'action_url' => '/tenant/customers/'.$this->customer->id,
        ];
    }
}
