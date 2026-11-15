<?php

namespace App\Notifications;

use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupplierPaymentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public Supplier $supplier,
        public float $currentPayable,
        public int $dueInvoicesCount
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Mandi Supplier Payment Due: :supplier', ['supplier' => $this->supplier->name]))
            ->greeting(__('Dear :name,', ['name' => $notifiable->name]))
            ->line(__('A payment balance of Rs. :amt is currently due to Mandi Commission Agent / Supplier :supplier across :count procurement invoices.', [
                'amt' => number_format($this->currentPayable, 2),
                'supplier' => $this->supplier->name,
                'count' => $this->dueInvoicesCount,
            ]))
            ->action(__('Open Supplier Ledger & Settlement', []), url('/tenant/suppliers/'.$this->supplier->id))
            ->line(__('Timely settlement maintains good credit standing in the wholesale Subzi Mandi.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'supplier_payment_reminder',
            'tenant_id' => $this->tenant->id,
            'title' => __('Supplier Payment Reminder: :supplier', ['supplier' => $this->supplier->name]),
            'title_ur' => 'سپلائر / آڑھتی واجب الادا رقم: '.$this->supplier->name,
            'message' => __('Payable Due: Rs. :amt across :count invoices (Contact: :contact)', [
                'amt' => number_format($this->currentPayable, 2),
                'count' => $this->dueInvoicesCount,
                'contact' => $this->supplier->phone ?? 'N/A',
            ]),
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->name,
            'current_payable' => $this->currentPayable,
            'due_invoices_count' => $this->dueInvoicesCount,
            'action_url' => '/tenant/suppliers/'.$this->supplier->id,
        ];
    }
}
