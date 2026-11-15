<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailySalesSummaryNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public string $date,
        public float $netSales,
        public float $cashCollected,
        public float $creditDue,
        public int $transactionsCount,
        public float $grossProfit
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Daily Sales Summary for :date - :shop', ['date' => $this->date, 'shop' => $this->tenant->name]))
            ->greeting(__('Dear :name,', ['name' => $notifiable->name]))
            ->line(__('Here is the verified daily sales performance summary for :shop on :date.', [
                'shop' => $this->tenant->name,
                'date' => $this->date,
            ]))
            ->line(__('Total Net Turnover: Rs. :sales', ['sales' => number_format($this->netSales, 2)]))
            ->line(__('Total Cash Collected: Rs. :cash', ['cash' => number_format($this->cashCollected, 2)]))
            ->line(__('Customer Credit Issued: Rs. :credit', ['credit' => number_format($this->creditDue, 2)]))
            ->line(__('Completed Transactions: :count', ['count' => $this->transactionsCount]))
            ->line(__('Estimated Gross Profit: Rs. :profit', ['profit' => number_format($this->grossProfit, 2)]))
            ->action(__('Open Executive Dashboard', []), url('/tenant/dashboard'))
            ->line(__('Thank you for managing your fresh produce business with Veggitable.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'daily_sales_summary',
            'tenant_id' => $this->tenant->id,
            'title' => __('Daily Sales Summary (:date)', ['date' => $this->date]),
            'title_ur' => 'روزانہ فروخت کا خلاصہ ('.$this->date.')',
            'message' => __('Net Sales: Rs. :sales | Cash: Rs. :cash | Transactions: :count', [
                'sales' => number_format($this->netSales, 2),
                'cash' => number_format($this->cashCollected, 2),
                'count' => $this->transactionsCount,
            ]),
            'date' => $this->date,
            'net_sales' => $this->netSales,
            'cash_collected' => $this->cashCollected,
            'credit_due' => $this->creditDue,
            'transactions_count' => $this->transactionsCount,
            'gross_profit' => $this->grossProfit,
            'action_url' => '/tenant/dashboard',
        ];
    }
}
