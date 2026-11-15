<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Display units and unit conversions.
     */
    public function index(Request $request): View
    {
        Gate::authorize('units.manage');

        $tenant = $this->context->getTenant();

        $unitsQuery = Unit::where('tenant_id', $tenant->id)
            ->withCount('products');

        if ($search = $request->input('search')) {
            $unitsQuery->searchBilingual($search);
        }

        $units = $unitsQuery->orderBy('is_system', 'desc')->orderBy('name')->get();

        $conversions = UnitConversion::where('tenant_id', $tenant->id)
            ->with(['fromUnit', 'toUnit'])
            ->latest()
            ->get();

        return view('tenant.units.index', compact('units', 'conversions', 'tenant'));
    }

    /**
     * Show form to create new measurement unit.
     */
    public function create(): View
    {
        Gate::authorize('units.manage');

        $tenant = $this->context->getTenant();

        return view('tenant.units.create', compact('tenant'));
    }

    /**
     * Store new unit.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('units.manage');

        $tenant = $this->context->getTenant();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'name_ur' => ['nullable', 'string', 'max:50'],
            'code' => ['required', 'string', 'max:20'],
            'is_fractional' => ['nullable', 'boolean'],
        ]);

        if (Unit::where('tenant_id', $tenant->id)->where('code', $validated['code'])->exists()) {
            return back()->withInput()->withErrors(['code' => __('A unit with this code already exists.')]);
        }

        $unit = Unit::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'name_ur' => $validated['name_ur'] ?? null,
            'code' => strtolower(trim($validated['code'])),
            'is_fractional' => $request->boolean('is_fractional'),
            'is_system' => false,
        ]);

        AuditLog::log(
            event: 'unit.created',
            action: "Created unit '{$unit->name}' ({$unit->code})",
            auditable: $unit,
            newValues: $unit->toArray()
        );

        return redirect()->route('tenant.units.index')->with('success', __('Unit created successfully.'));
    }

    /**
     * Show edit form for unit.
     */
    public function edit(Unit $unit): View
    {
        Gate::authorize('units.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $unit->tenant_id !== (int) $tenant->id, 404);

        return view('tenant.units.edit', compact('unit', 'tenant'));
    }

    /**
     * Update unit.
     */
    public function update(Request $request, Unit $unit): RedirectResponse
    {
        Gate::authorize('units.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $unit->tenant_id !== (int) $tenant->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'name_ur' => ['nullable', 'string', 'max:50'],
            'code' => ['required', 'string', 'max:20'],
            'is_fractional' => ['nullable', 'boolean'],
        ]);

        if (Unit::where('tenant_id', $tenant->id)->where('code', $validated['code'])->where('id', '!=', $unit->id)->exists()) {
            return back()->withInput()->withErrors(['code' => __('A unit with this code already exists.')]);
        }

        $oldValues = $unit->toArray();

        $unit->update([
            'name' => $validated['name'],
            'name_ur' => $validated['name_ur'] ?? null,
            'code' => strtolower(trim($validated['code'])),
            'is_fractional' => $request->boolean('is_fractional'),
        ]);

        AuditLog::log(
            event: 'unit.updated',
            action: "Updated unit '{$unit->name}'",
            auditable: $unit,
            oldValues: $oldValues,
            newValues: $unit->toArray()
        );

        return redirect()->route('tenant.units.index')->with('success', __('Unit updated successfully.'));
    }

    /**
     * Delete unit.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        Gate::authorize('units.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $unit->tenant_id !== (int) $tenant->id, 404);

        if ($unit->is_system) {
            return back()->with('error', __('System default units cannot be deleted.'));
        }

        if ($unit->products()->exists()) {
            return back()->with('error', __('Cannot delete unit because it is used by existing products.'));
        }

        if ($unit->conversionsFrom()->exists() || $unit->conversionsTo()->exists()) {
            return back()->with('error', __('Cannot delete unit because conversion rules are linked to it.'));
        }

        $name = $unit->name;
        $unit->delete();

        AuditLog::log(
            event: 'unit.deleted',
            action: "Deleted unit '{$name}'"
        );

        return redirect()->route('tenant.units.index')->with('success', __('Unit deleted successfully.'));
    }

    /**
     * Store unit conversion rule.
     */
    public function storeConversion(Request $request): RedirectResponse
    {
        Gate::authorize('units.manage');

        $tenant = $this->context->getTenant();

        $validated = $request->validate([
            'from_unit_id' => ['required', 'exists:units,id'],
            'to_unit_id' => ['required', 'exists:units,id', 'different:from_unit_id'],
            'multiplier' => ['required', 'numeric', 'min:0.0001'],
            'description' => ['nullable', 'string', 'max:100'],
        ]);

        $conversion = UnitConversion::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'from_unit_id' => $validated['from_unit_id'],
                'to_unit_id' => $validated['to_unit_id'],
            ],
            [
                'multiplier' => $validated['multiplier'],
                'description' => $validated['description'] ?? null,
            ]
        );

        AuditLog::log(
            event: 'unit_conversion.saved',
            action: "Created/Updated conversion rule: 1 {$conversion->fromUnit?->code} = {$conversion->multiplier} {$conversion->toUnit?->code}",
            auditable: $conversion
        );

        return redirect()->route('tenant.units.index')->with('success', __('Unit conversion rule saved successfully.'));
    }

    /**
     * Delete unit conversion rule.
     */
    public function destroyConversion(UnitConversion $conversion): RedirectResponse
    {
        Gate::authorize('units.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $conversion->tenant_id !== (int) $tenant->id, 404);

        $conversion->delete();

        AuditLog::log(
            event: 'unit_conversion.deleted',
            action: 'Deleted unit conversion rule.'
        );

        return redirect()->route('tenant.units.index')->with('success', __('Conversion rule removed successfully.'));
    }
}
