<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Display listing of roles and permissions matrix.
     */
    public function index(): View
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        $roles = Role::where(fn ($q) => $q->where('tenant_id', $tenant->id)->orWhereNull('tenant_id'))
            ->with(['permissions', 'users' => fn ($uq) => $uq->where('users.tenant_id', $tenant->id)])
            ->get();

        $permissionsByModule = Permission::all()->groupBy('module');

        return view('tenant.roles.index', compact('roles', 'permissionsByModule', 'tenant'));
    }

    /**
     * Show form to create custom role.
     */
    public function create(): View
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        $permissionsByModule = Permission::all()->groupBy('module');

        return view('tenant.roles.create', compact('permissionsByModule', 'tenant'));
    }

    /**
     * Store new custom role.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'nullable',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('roles', 'slug')->where('tenant_id', $tenant->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'permission_ids' => ['required', 'array', 'min:1'],
            'permission_ids.*' => ['exists:permissions,id'],
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $role = Role::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'],
            'is_system' => false,
        ]);

        $role->permissions()->sync($validated['permission_ids']);

        AuditLog::record('role.created', ['role_id' => $role->id, 'role_name' => $role->name], auth()->user(), $tenant->id);

        return redirect()->route('tenant.roles.index')->with('success', "Custom role '{$role->name}' created successfully.");
    }

    /**
     * Show form to edit custom role.
     */
    public function edit(Role $role): View|RedirectResponse
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        if ($role->is_system || (int) $role->tenant_id !== (int) $tenant->id) {
            return redirect()->route('tenant.roles.index')->with('error', 'System roles cannot be modified.');
        }

        $permissionsByModule = Permission::all()->groupBy('module');
        $assignedPermissionIds = $role->permissions->pluck('id')->toArray();

        return view('tenant.roles.edit', compact('role', 'permissionsByModule', 'assignedPermissionIds', 'tenant'));
    }

    /**
     * Update custom role.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        if ($role->is_system || (int) $role->tenant_id !== (int) $tenant->id) {
            return redirect()->route('tenant.roles.index')->with('error', 'System roles cannot be modified.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'permission_ids' => ['required', 'array', 'min:1'],
            'permission_ids.*' => ['exists:permissions,id'],
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
        ]);

        $role->permissions()->sync($validated['permission_ids']);

        AuditLog::record('role.updated', ['role_id' => $role->id, 'role_name' => $role->name], auth()->user(), $tenant->id);

        return redirect()->route('tenant.roles.index')->with('success', "Role '{$role->name}' updated successfully.");
    }
}

