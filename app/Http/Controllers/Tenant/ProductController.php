<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Display listing of products.
     */
    public function index(Request $request): View
    {
        Gate::authorize('products.view');

        $tenant = $this->context->getTenant();

        $query = Product::where('tenant_id', $tenant->id)
            ->with(['category', 'subcategory', 'purchaseUnit', 'sellingUnit', 'stockUnit']);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($search = $request->input('search')) {
            $query->searchBilingual($search);
        }

        $products = $query->latest()->paginate(15)->withQueryString();

        $categories = Category::where('tenant_id', $tenant->id)
            ->where('type', 'product')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $types = [
            'vegetable' => __('Fresh Vegetable'),
            'fruit' => __('Fresh Fruit'),
            'mandi_lot' => __('Mandi Auction Lot'),
            'prepack' => __('Pre-packed / Processed'),
        ];

        return view('tenant.products.index', compact('products', 'categories', 'types', 'tenant'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        Gate::authorize('products.manage');

        $tenant = $this->context->getTenant();

        $categories = Category::where('tenant_id', $tenant->id)
            ->where('type', 'product')
            ->whereNull('parent_id')
            ->with('subcategories')
            ->orderBy('name')
            ->get();

        $units = Unit::where('tenant_id', $tenant->id)->orderBy('name')->get();

        $types = [
            'vegetable' => __('Fresh Vegetable'),
            'fruit' => __('Fresh Fruit'),
            'mandi_lot' => __('Mandi Auction Lot'),
            'prepack' => __('Pre-packed / Processed'),
        ];

        return view('tenant.products.create', compact('tenant', 'categories', 'units', 'types'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('products.manage');

        $tenant = $this->context->getTenant();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:vegetable,fruit,mandi_lot,prepack'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            'purchase_unit_id' => ['nullable', 'exists:units,id'],
            'selling_unit_id' => ['nullable', 'exists:units,id'],
            'stock_unit_id' => ['nullable', 'exists:units,id'],
            'unit_conversion_factor' => ['required', 'numeric', 'min:0.0001'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'min_selling_price' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'is_perishable' => ['nullable', 'boolean'],
            'shelf_life_days' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        // Auto-generate code if not provided
        $code = ! empty($validated['code']) ? strtoupper(trim($validated['code'])) : 'PRD-'.strtoupper(Str::random(6));

        // Ensure unique code within tenant
        if (Product::where('tenant_id', $tenant->id)->where('code', $code)->exists()) {
            return back()->withInput()->withErrors(['code' => __('A product with this code already exists in your inventory.')]);
        }

        // Auto-generate barcode if not provided
        $barcode = ! empty($validated['barcode']) ? $validated['barcode'] : Product::generateBarcode($tenant);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $validated['category_id'] ?? null,
            'subcategory_id' => $validated['subcategory_id'] ?? null,
            'name' => $validated['name'],
            'name_ur' => $validated['name_ur'] ?? null,
            'code' => $code,
            'barcode' => $barcode,
            'type' => $validated['type'],
            'purchase_unit_id' => $validated['purchase_unit_id'] ?? null,
            'selling_unit_id' => $validated['selling_unit_id'] ?? null,
            'stock_unit_id' => $validated['stock_unit_id'] ?? null,
            'unit_conversion_factor' => $validated['unit_conversion_factor'],
            'cost_price' => $validated['cost_price'],
            'selling_price' => $validated['selling_price'],
            'min_selling_price' => $validated['min_selling_price'] ?? 0.00,
            'reorder_level' => $validated['reorder_level'],
            'is_perishable' => $request->boolean('is_perishable', true),
            'shelf_life_days' => $validated['shelf_life_days'] ?? 3,
            'image_path' => $imagePath,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::log(
            event: 'product.created',
            action: "Created product '{$product->name}' [{$product->code}]",
            auditable: $product,
            newValues: $product->toArray()
        );

        return redirect()->route('tenant.products.index')->with('success', __('Product created successfully.'));
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): View
    {
        Gate::authorize('products.view');

        $tenant = $this->context->getTenant();
        abort_if((int) $product->tenant_id !== (int) $tenant->id, 404);

        $product->load(['category', 'subcategory', 'purchaseUnit', 'sellingUnit', 'stockUnit']);

        return view('tenant.products.show', compact('product', 'tenant'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): View
    {
        Gate::authorize('products.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $product->tenant_id !== (int) $tenant->id, 404);

        $categories = Category::where('tenant_id', $tenant->id)
            ->where('type', 'product')
            ->whereNull('parent_id')
            ->with('subcategories')
            ->orderBy('name')
            ->get();

        $units = Unit::where('tenant_id', $tenant->id)->orderBy('name')->get();

        $types = [
            'vegetable' => __('Fresh Vegetable'),
            'fruit' => __('Fresh Fruit'),
            'mandi_lot' => __('Mandi Auction Lot'),
            'prepack' => __('Pre-packed / Processed'),
        ];

        return view('tenant.products.edit', compact('product', 'tenant', 'categories', 'units', 'types'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('products.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $product->tenant_id !== (int) $tenant->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:vegetable,fruit,mandi_lot,prepack'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            'purchase_unit_id' => ['nullable', 'exists:units,id'],
            'selling_unit_id' => ['nullable', 'exists:units,id'],
            'stock_unit_id' => ['nullable', 'exists:units,id'],
            'unit_conversion_factor' => ['required', 'numeric', 'min:0.0001'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'min_selling_price' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'is_perishable' => ['nullable', 'boolean'],
            'shelf_life_days' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $code = strtoupper(trim($validated['code']));
        if (Product::where('tenant_id', $tenant->id)->where('code', $code)->where('id', '!=', $product->id)->exists()) {
            return back()->withInput()->withErrors(['code' => __('A product with this code already exists.')]);
        }

        $oldValues = $product->toArray();

        if ($request->hasFile('image')) {
            if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }
            $product->image_path = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'category_id' => $validated['category_id'] ?? null,
            'subcategory_id' => $validated['subcategory_id'] ?? null,
            'name' => $validated['name'],
            'name_ur' => $validated['name_ur'] ?? null,
            'code' => $code,
            'barcode' => ! empty($validated['barcode']) ? $validated['barcode'] : $product->barcode,
            'type' => $validated['type'],
            'purchase_unit_id' => $validated['purchase_unit_id'] ?? null,
            'selling_unit_id' => $validated['selling_unit_id'] ?? null,
            'stock_unit_id' => $validated['stock_unit_id'] ?? null,
            'unit_conversion_factor' => $validated['unit_conversion_factor'],
            'cost_price' => $validated['cost_price'],
            'selling_price' => $validated['selling_price'],
            'min_selling_price' => $validated['min_selling_price'] ?? 0.00,
            'reorder_level' => $validated['reorder_level'],
            'is_perishable' => $request->boolean('is_perishable', true),
            'shelf_life_days' => $validated['shelf_life_days'] ?? 3,
            'is_active' => $request->boolean('is_active', true),
            'image_path' => $product->image_path,
        ]);

        AuditLog::log(
            event: 'product.updated',
            action: "Updated product '{$product->name}'",
            auditable: $product,
            oldValues: $oldValues,
            newValues: $product->toArray()
        );

        return redirect()->route('tenant.products.index')->with('success', __('Product updated successfully.'));
    }

    /**
     * Remove the specified product from storage (Soft Delete).
     */
    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('products.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $product->tenant_id !== (int) $tenant->id, 404);

        $name = $product->name;
        $product->delete();

        AuditLog::log(
            event: 'product.deleted',
            action: "Soft deleted product '{$name}'"
        );

        return redirect()->route('tenant.products.index')->with('success', __('Product archived successfully.'));
    }

    /**
     * Printable barcode sticker label sheet for product.
     */
    public function barcode(Product $product): View
    {
        Gate::authorize('products.view');

        $tenant = $this->context->getTenant();
        abort_if((int) $product->tenant_id !== (int) $tenant->id, 404);

        return view('tenant.products.barcode', compact('product', 'tenant'));
    }
}
