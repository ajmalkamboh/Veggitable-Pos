<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        protected TenantContext $context,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Display a listing of tenant employees.
     */
    public function index(Request $request): View
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        $query = User::where('tenant_id', $tenant->id)
            ->with(['roles', 'branches', 'currentBranch']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($roleSlug = $request->input('role')) {
            $query->whereHas('roles', fn ($rq) => $rq->where('slug', $roleSlug));
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $roles = Role::where(fn ($q) => $q->where('tenant_id', $tenant->id)->orWhereNull('tenant_id'))->get();

        return view('tenant.users.index', compact('users', 'roles', 'tenant'));
    }

    /**
     * Show form for creating a new tenant employee.
     */
    public function create(): View
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        // Enforce plan user limit before rendering form
        $this->subscriptionService->assertCanAddUser($tenant);

        $branches = Branch::where('tenant_id', $tenant->id)->where('is_active', true)->get();
        $roles = Role::where(fn ($q) => $q->where('tenant_id', $tenant->id)->orWhereNull('tenant_id'))
            ->where('slug', '!=', 'owner') // Only owner can be owner
            ->get();

        return view('tenant.users.create', compact('branches', 'roles', 'tenant'));
    }

    /**
     * Store a newly created tenant employee.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        // Enforce plan user limit
        $this->subscriptionService->assertCanAddUser($tenant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'phone' => ['nullable', 'string', 'max:50'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => [Rule::exists('branches', 'id')->where('tenant_id', $tenant->id)],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => [Rule::exists('roles', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)->orWhereNull('tenant_id'))],
        ]);

        $defaultBranchId = $validated['branch_ids'][0];

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'current_branch_id' => $defaultBranchId,
            'is_tenant_owner' => false,
            'is_super_admin' => false,
            'status' => 'active',
        ]);

        // Assign Branches with default branch marker
        $syncBranches = [];
        foreach ($validated['branch_ids'] as $index => $branchId) {
            $syncBranches[$branchId] = [
                'tenant_id' => $tenant->id,
                'is_default' => ($index === 0),
            ];
        }
        $user->branches()->sync($syncBranches);

        // Assign Roles
        $syncRoles = [];
        foreach ($validated['role_ids'] as $roleId) {
            $syncRoles[$roleId] = ['tenant_id' => $tenant->id];
        }
        $user->roles()->sync($syncRoles);

        AuditLog::record('user.created', ['created_user_id' => $user->id, 'email' => $user->email], auth()->user(), $tenant->id);

        return redirect()->route('tenant.users.index')->with('success', "Employee '{$user->name}' added successfully.");
    }

    /**
     * Show form for editing an existing tenant employee.
     */
    public function edit(User $user): View
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant && (int) $user->tenant_id === (int) $tenant->id, 403, 'Unauthorized access to user.');

        $branches = Branch::where('tenant_id', $tenant->id)->where('is_active', true)->get();
        $roles = Role::where(fn ($q) => $q->where('tenant_id', $tenant->id)->orWhereNull('tenant_id'))->get();
        $assignedBranchIds = $user->branches->pluck('id')->toArray();
        $assignedRoleIds = $user->roles->pluck('id')->toArray();

        return view('tenant.users.edit', compact('user', 'branches', 'roles', 'assignedBranchIds', 'assignedRoleIds'));
    }

    /**
     * Update tenant employee details.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant && (int) $user->tenant_id === (int) $tenant->id, 403, 'Unauthorized access to user.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', Password::min(8)->letters()->numbers()],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,suspended,inactive'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => [Rule::exists('branches', 'id')->where('tenant_id', $tenant->id)],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => [Rule::exists('roles', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)->orWhereNull('tenant_id'))],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'status' => $validated['status'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Sync Branches
        $syncBranches = [];
        foreach ($validated['branch_ids'] as $index => $branchId) {
            $syncBranches[$branchId] = [
                'tenant_id' => $tenant->id,
                'is_default' => ($index === 0),
            ];
        }
        $user->branches()->sync($syncBranches);

        // Ensure current_branch_id is valid
        if (! in_array($user->current_branch_id, $validated['branch_ids'])) {
            $user->update(['current_branch_id' => $validated['branch_ids'][0]]);
        }

        // Sync Roles
        $syncRoles = [];
        foreach ($validated['role_ids'] as $roleId) {
            $syncRoles[$roleId] = ['tenant_id' => $tenant->id];
        }
        $user->roles()->sync($syncRoles);

        AuditLog::record('user.updated', ['target_user_id' => $user->id, 'email' => $user->email], auth()->user(), $tenant->id);

        return redirect()->route('tenant.users.index')->with('success', "Employee '{$user->name}' updated successfully.");
    }

    /**
     * Toggle user status between Active and Suspended.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant && (int) $user->tenant_id === (int) $tenant->id, 403, 'Unauthorized access to user.');

        if ($user->isTenantOwner()) {
            return back()->with('error', 'Cannot suspend the primary business owner account.');
        }

        $newStatus = ($user->status === 'active') ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        AuditLog::record('user.status_toggled', ['target_user_id' => $user->id, 'new_status' => $newStatus], auth()->user(), $tenant->id);

        return back()->with('success', "Status for '{$user->name}' changed to ".strtoupper($newStatus).'.');
    }
}

