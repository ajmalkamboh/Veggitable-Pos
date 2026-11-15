<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyWastageSummaryNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public string $date,
        public float $totalLossCost,
        public float $totalQuantity,
        public int $vouchersCount
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Produce Spoilage & Wastage Summary for :date - :shop', ['date' => $this->date, 'shop' => $this->tenant->name]))
            ->greeting(__('Dear :name,', ['name' => $notifiable->name]))
            ->line(__('A total of :qty kg of fresh produce was culled or recorded as loss on :date.', [
                'qty' => number_format($this->totalQuantity, 2),
                'date' => $this->date,
            ]))
            ->line(__('Total Financial Loss: Rs. :cost', ['cost' => number_format($this->totalLossCost, 2)]))
            ->line(__('Logged Vouchers: :count', ['count' => $this->vouchersCount]))
            ->action(__('View Spoilage & Regrading Logs', []), url('/tenant/inventory/wastage'))
            ->line(__('Monitor cold storage and shelf moisture to minimize spoilage losses.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'daily_wastage_summary',
            'tenant_id' => $this->tenant->id,
            'title' => __('Daily Wastage & Spoilage Alert (:date)', ['date' => $this->date]),
            'title_ur' => 'روزانہ خراب مال و نقصان کا خلاصہ ('.$this->date.')',
            'message' => __('Total Cull Loss: :qty kg valued at Rs. :cost (:count vouchers)', [
                'qty' => number_format($this->totalQuantity, 2),
                'cost' => number_format($this->totalLossCost, 2),
                'count' => $this->vouchersCount,
            ]),
            'date' => $this->date,
            'total_loss_cost' => $this->totalLossCost,
            'total_quantity' => $this->totalQuantity,
            'vouchers_count' => $this->vouchersCount,
            'action_url' => '/tenant/inventory/wastage',
        ];
    }
}
