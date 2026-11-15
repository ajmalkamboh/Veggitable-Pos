<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\PosSession;
use App\Models\SalesInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PosSessionController extends Controller
{
    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Open a new cash till / POS session.
     */
    public function open(Request $request): RedirectResponse
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();
        $user = auth()->user();

        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $branch = Branch::where('tenant_id', $tenant->id)->findOrFail($validated['branch_id']);

        // Check if there is already an open session for this user
        $existing = PosSession::where('tenant_id', $tenant->id)
            ->where('branch_id', $branch->id)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return redirect()->route('tenant.pos.counter')
                ->with('info', __('You already have an open till session for this branch.'));
        }

        $sessionNo = PosSession::generateSessionNumber($tenant);

        $session = PosSession::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'session_no' => $sessionNo,
            'opened_at' => now(),
            'opening_cash' => $validated['opening_cash'],
            'status' => 'open',
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::log(
            event: 'pos.till_opened',
            action: "Opened POS till session '{$session->session_no}' with opening cash {$tenant->currency} {$validated['opening_cash']}",
            auditable: $session
        );

        return redirect()->route('tenant.pos.counter')
            ->with('success', __('Cash till opened successfully! Ready for sales.'));
    }

    /**
     * Close the active cash till / POS session.
     */
    public function close(Request $request, PosSession $posSession): RedirectResponse
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();
        abort_if((int) $posSession->tenant_id !== (int) $tenant->id, 404);

        $validated = $request->validate([
            'closing_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Calculate total cash sales in this session
        $cashSales = SalesInvoice::where('pos_session_id', $posSession->id)
            ->where('status', 'completed')
            ->where('payment_method', 'cash')
            ->sum('paid_amount');

        $expectedCash = (float) $posSession->opening_cash + (float) $cashSales;
        $closingCash = (float) $validated['closing_cash'];
        $difference = $closingCash - $expectedCash;

        $posSession->update([
            'closed_at' => now(),
            'closing_cash' => $closingCash,
            'expected_cash' => $expectedCash,
            'cash_difference' => $difference,
            'status' => 'closed',
            'notes' => $validated['notes'] ?? $posSession->notes,
        ]);

        AuditLog::log(
            event: 'pos.till_closed',
            action: "Closed POS till session '{$posSession->session_no}'. Expected: {$tenant->currency} {$expectedCash}, Counted: {$tenant->currency} {$closingCash}, Variance: {$tenant->currency} {$difference}",
            auditable: $posSession
        );

        return redirect()->route('tenant.sales.index')
            ->with('success', __('POS till session closed successfully. Cash variance: :currency :diff', [
                'currency' => $tenant->currency,
                'diff' => number_format($difference, 2),
            ]));
    }
}
