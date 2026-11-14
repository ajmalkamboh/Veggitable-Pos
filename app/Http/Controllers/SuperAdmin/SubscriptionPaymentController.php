<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionPaymentController extends Controller
{
    /**
     * Display a listing of all subscription payments and invoices.
     */
    public function index(Request $request): View
    {
        $query = SubscriptionInvoice::with(['tenant', 'subscription', 'plan']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('transaction_reference', 'like', "%{$search}%")
                    ->orWhereHas('tenant', fn ($tq) => $tq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('payment_status', $status);
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();

        return view('superadmin.subscriptions.payments', compact('invoices'));
    }

    /**
     * Show form to record a manual subscription payment.
     */
    public function create(): View
    {
        $tenants = Tenant::where('status', 'active')->orderBy('name')->get();

        return view('superadmin.subscriptions.record-payment', compact('tenants'));
    }

    /**
     * Store manual payment.
     */
    public function store(Request $request, SubscriptionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);

        $invoice = $service->recordManualPayment(
            tenant: $tenant,
            amount: (float) $validated['amount'],
            paymentMethod: $validated['payment_method'],
            reference: $validated['transaction_reference'],
            notes: $validated['notes']
        );

        return redirect()->route('superadmin.subscriptions.payments')->with('success', "Payment of Rs. {$invoice->amount} recorded successfully (Invoice: {$invoice->invoice_number}).");
    }

    /**
     * Display invoice details.
     */
    public function show(SubscriptionInvoice $invoice): View
    {
        $invoice->load(['tenant', 'subscription', 'plan']);

        return view('superadmin.subscriptions.invoice-show', compact('invoice'));
    }
}
