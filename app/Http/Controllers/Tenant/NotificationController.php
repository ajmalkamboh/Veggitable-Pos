<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Services\AutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        protected TenantContext $context,
        protected AutomationService $automationService
    ) {}

    /**
     * In-app Notification Center view.
     */
    public function index(Request $request): View
    {
        $tenant = $this->context->getTenant();
        $user = auth()->user();

        $query = $user->notifications();

        if ($request->get('filter') === 'unread') {
            $query->whereNull('read_at');
        } elseif ($request->get('filter') === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($request->filled('type')) {
            $query->where('data->type', $request->type);
        }

        $notifications = $query->paginate(15);
        $unreadCount = $user->unreadNotifications()->count();

        return view('tenant.notifications.index', compact('notifications', 'unreadCount', 'tenant'));
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(string $id): RedirectResponse
    {
        $user = auth()->user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return back()->with('success', __('Notification marked as read.'));
    }

    /**
     * Mark all notifications as read for current user.
     */
    public function markAllAsRead(): RedirectResponse
    {
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();

        return back()->with('success', __('All notifications marked as read.'));
    }

    /**
     * Delete a single notification.
     */
    public function destroy(string $id): RedirectResponse
    {
        $user = auth()->user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->delete();

        return back()->with('success', __('Notification deleted.'));
    }

    /**
     * Notification and Automation Settings.
     */
    public function settings(): View
    {
        $tenant = $this->context->getTenant();
        $settings = $tenant->settings['notifications'] ?? [
            'notify_low_stock' => true,
            'notify_daily_summary' => true,
            'notify_customer_overdue' => true,
            'notify_supplier_due' => true,
            'email_channel' => true,
            'sms_channel' => false,
            'whatsapp_channel' => false,
            'sms_provider' => 'generic_gateway',
            'sms_sender_id' => 'VEGGITABLE',
        ];

        return view('tenant.notifications.settings', compact('tenant', 'settings'));
    }

    /**
     * Save Notification Channel & Automation Settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $tenant = $this->context->getTenant();

        $validated = $request->validate([
            'notify_low_stock' => ['nullable', 'boolean'],
            'notify_daily_summary' => ['nullable', 'boolean'],
            'notify_customer_overdue' => ['nullable', 'boolean'],
            'notify_supplier_due' => ['nullable', 'boolean'],
            'email_channel' => ['nullable', 'boolean'],
            'sms_channel' => ['nullable', 'boolean'],
            'whatsapp_channel' => ['nullable', 'boolean'],
            'sms_provider' => ['nullable', 'string', 'max:50'],
            'sms_sender_id' => ['nullable', 'string', 'max:20'],
            'sms_api_url' => ['nullable', 'url', 'max:255'],
            'whatsapp_webhook_url' => ['nullable', 'url', 'max:255'],
        ]);

        $settings = $tenant->settings ?? [];
        $settings['notifications'] = [
            'notify_low_stock' => (bool) ($validated['notify_low_stock'] ?? false),
            'notify_daily_summary' => (bool) ($validated['notify_daily_summary'] ?? false),
            'notify_customer_overdue' => (bool) ($validated['notify_customer_overdue'] ?? false),
            'notify_supplier_due' => (bool) ($validated['notify_supplier_due'] ?? false),
            'email_channel' => (bool) ($validated['email_channel'] ?? false),
            'sms_channel' => (bool) ($validated['sms_channel'] ?? false),
            'whatsapp_channel' => (bool) ($validated['whatsapp_channel'] ?? false),
            'sms_provider' => $validated['sms_provider'] ?? 'generic_gateway',
            'sms_sender_id' => $validated['sms_sender_id'] ?? 'VEGGITABLE',
            'sms_api_url' => $validated['sms_api_url'] ?? null,
            'whatsapp_webhook_url' => $validated['whatsapp_webhook_url'] ?? null,
        ];

        $tenant->settings = $settings;
        $tenant->save();

        return redirect()->route('tenant.notifications.settings')
            ->with('success', __('Notification and alert channels updated successfully!'));
    }

    /**
     * On-demand execution of automation routines.
     */
    public function triggerAutomation(Request $request): JsonResponse
    {
        $request->validate([
            'routine' => ['required', 'string', 'in:low_stock,daily_sales,daily_wastage,customer_balances,supplier_payables'],
        ]);

        $tenant = $this->context->getTenant();
        $routine = $request->routine;
        $message = '';

        switch ($routine) {
            case 'low_stock':
                $count = $this->automationService->checkLowStock($tenant);
                $message = __('Scanned all produce stocks: :count alert(s) dispatched.', ['count' => $count]);
                break;
            case 'daily_sales':
                $this->automationService->sendDailySalesSummary($tenant);
                $message = __('Daily sales performance summary dispatched to business managers.');
                break;
            case 'daily_wastage':
                $this->automationService->sendDailyWastageSummary($tenant);
                $message = __('Daily produce wastage and cull summary dispatched.');
                break;
            case 'customer_balances':
                $count = $this->automationService->checkCustomerBalances($tenant);
                $message = __('Scanned customer ledgers: :count overdue balance notification(s) dispatched.', ['count' => $count]);
                break;
            case 'supplier_payables':
                $count = $this->automationService->checkSupplierPayables($tenant);
                $message = __('Scanned supplier payables: :count payment reminder(s) dispatched.', ['count' => $count]);
                break;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'routine' => $routine,
        ]);
    }
}
