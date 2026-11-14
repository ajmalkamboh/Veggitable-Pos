<?php

namespace App\Http\Controllers;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Branch;
use App\Models\TenantRecord;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class TenantRecordController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Display a listing of the tenant records.
     */
    public function index(): View
    {
        $records = TenantRecord::with('branch')->latest()->get();
        $branches = $this->context->hasTenant()
            ? Branch::where('is_active', true)->get()
            : collect();

        return view('tenant.records.index', compact('records', 'branches'));
    }

    /**
     * Store a newly created tenant record.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        // Branch verification: Ensure branch belongs to active tenant
        if (! empty($validated['branch_id'])) {
            $branch = Branch::findOrFail($validated['branch_id']);
            if ((int) $branch->tenant_id !== (int) $this->context->getTenantId()) {
                abort(403, 'Selected branch does not belong to your business.');
            }
        }

        // Create record: tenant_id is automatically attached and guarded by BelongsToTenant
        $record = TenantRecord::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'amount' => $validated['amount'],
            'description' => $validated['description'] ?? null,
            'branch_id' => $validated['branch_id'] ?? $this->context->getBranchId(),
        ]);

        return redirect()->back()->with('success', "Tenant record '{$record->name}' created successfully in your business context.");
    }

    /**
     * Display the specified tenant record.
     */
    public function show(TenantRecord $record): View
    {
        $this->authorize('view', $record);

        return view('tenant.records.show', compact('record'));
    }

    /**
     * Switch active branch for current tenant.
     */
    public function switchBranch(Request $request, Branch $branch): RedirectResponse
    {
        // Security check: Branch must belong to active tenant
        if ((int) $branch->tenant_id !== (int) $this->context->getTenantId()) {
            abort(403, 'Cross-tenant branch switch forbidden.');
        }

        $this->context->setBranch($branch);
        Session::put('branch_id', $branch->id);

        $user = $request->user();
        if ($user instanceof User) {
            $user->update(['current_branch_id' => $branch->id]);
        }

        return redirect()->back()->with('success', "Active branch switched to '{$branch->name}'.");
    }
}
