<?php

namespace App\Notifications;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public Product $product,
        public float $currentQuantity,
        public float $reorderLevel,
        public string $branchName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Low Stock Alert: :product at :branch', ['product' => $this->product->name, 'branch' => $this->branchName]))
            ->greeting(__('Dear :name,', ['name' => $notifiable->name]))
            ->line(__('The produce item :product (:urdu) has dropped below the minimum reorder threshold.', [
                'product' => $this->product->name,
                'urdu' => $this->product->name_ur ?? '',
            ]))
            ->line(__('Outlet: :branch', ['branch' => $this->branchName]))
            ->line(__('Current Stock: :qty kg (Reorder Threshold: :reorder kg)', [
                'qty' => number_format($this->currentQuantity, 2),
                'reorder' => number_format($this->reorderLevel, 2),
            ]))
            ->action(__('View Inventory & Procurement', []), url('/tenant/inventory'))
            ->line(__('Please replenish produce lots promptly from Subzi Mandi.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'tenant_id' => $this->tenant->id,
            'title' => __('Low Stock Alert: :product', ['product' => $this->product->name]),
            'title_ur' => 'اسٹاک الرٹ: '.($this->product->name_ur ?? $this->product->name),
            'message' => __('Stock of :product in :branch is :qty kg (Reorder: :reorder kg)', [
                'product' => $this->product->name,
                'branch' => $this->branchName,
                'qty' => number_format($this->currentQuantity, 2),
                'reorder' => number_format($this->reorderLevel, 2),
            ]),
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'current_quantity' => $this->currentQuantity,
            'reorder_level' => $this->reorderLevel,
            'branch_name' => $this->branchName,
            'action_url' => '/tenant/inventory',
        ];
    }
}
