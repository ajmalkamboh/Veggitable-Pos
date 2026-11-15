<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockAdjustmentAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public string $adjustmentNo,
        public string $reason,
        public float $varianceQuantity,
        public string $branchName,
        public string $userName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Important Stock Adjustment: :no at :branch', ['no' => $this->adjustmentNo, 'branch' => $this->branchName]))
            ->greeting(__('Dear :name,', ['name' => $notifiable->name]))
            ->line(__('A physical stock audit variance was posted under voucher :no by :user at :branch.', [
                'no' => $this->adjustmentNo,
                'user' => $this->userName,
                'branch' => $this->branchName,
            ]))
            ->line(__('Audit Reason: :reason', ['reason' => ucfirst(str_replace('_', ' ', $this->reason))]))
            ->line(__('Net Physical Variance: :var kg', ['var' => number_format($this->varianceQuantity, 2)]))
            ->action(__('Inspect Stock Adjustments', []), url('/tenant/inventory/adjustments'))
            ->line(__('Inventory ledger balances were updated accordingly.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'stock_adjustment_alert',
            'tenant_id' => $this->tenant->id,
            'title' => __('Stock Adjustment: :no', ['no' => $this->adjustmentNo]),
            'title_ur' => 'اسٹاک فزیکل آڈٹ اندراج: '.$this->adjustmentNo,
            'message' => __('Audit variance of :var kg posted by :user (:reason) in :branch', [
                'var' => number_format($this->varianceQuantity, 2),
                'user' => $this->userName,
                'reason' => str_replace('_', ' ', $this->reason),
                'branch' => $this->branchName,
            ]),
            'adjustment_no' => $this->adjustmentNo,
            'variance_quantity' => $this->varianceQuantity,
            'reason' => $this->reason,
            'branch_name' => $this->branchName,
            'action_url' => '/tenant/inventory/adjustments',
        ];
    }
}
