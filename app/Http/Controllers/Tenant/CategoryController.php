<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Display categories listing.
     */
    public function index(Request $request): View
    {
        Gate::authorize('categories.manage');

        $tenant = $this->context->getTenant();

        $query = Category::where('tenant_id', $tenant->id)
            ->with(['parent', 'subcategories'])
            ->withCount(['products', 'subcategories']);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->input('search')) {
            $query->searchBilingual($search);
        }

        $categories = $query->latest()->paginate(15)->withQueryString();

        return view('tenant.categories.index', compact('categories', 'tenant'));
    }

    /**
     * Show form for creating a category or subcategory.
     */
    public function create(Request $request): View
    {
        Gate::authorize('categories.manage');

        $tenant = $this->context->getTenant();
        $type = $request->input('type', 'product');

        // Only root categories can be parents
        $parentCategories = Category::where('tenant_id', $tenant->id)
            ->whereNull('parent_id')
            ->where('type', $type)
            ->orderBy('name')
            ->get();

        return view('tenant.categories.create', compact('tenant', 'parentCategories', 'type'));
    }

    /**
     * Store newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('categories.manage');

        $tenant = $this->context->getTenant();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'name_ur' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'in:product,expense'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $baseSlug = Str::slug($validated['name']) ?: 'cat';
        $slug = $baseSlug;
        $counter = 1;
        while (Category::where('tenant_id', $tenant->id)->where('type', $validated['type'])->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
        }

        $category = Category::create([
            'tenant_id' => $tenant->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'name_ur' => $validated['name_ur'] ?? null,
            'slug' => $slug,
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'image_path' => $imagePath,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::log(
            event: 'category.created',
            action: "Created {$category->type} category: '{$category->name}'",
            auditable: $category,
            newValues: $category->toArray()
        );

        return redirect()->route('tenant.categories.index', ['type' => $category->type])
            ->with('success', __('Category created successfully.'));
    }

    /**
     * Show edit form.
     */
    public function edit(Category $category): View
    {
        Gate::authorize('categories.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $category->tenant_id !== (int) $tenant->id, 404);

        $parentCategories = Category::where('tenant_id', $tenant->id)
            ->whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->where('type', $category->type)
            ->orderBy('name')
            ->get();

        return view('tenant.categories.edit', compact('category', 'tenant', 'parentCategories'));
    }

    /**
     * Update existing category.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('categories.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $category->tenant_id !== (int) $tenant->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'name_ur' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:categories,id', 'not_in:'.$category->id],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $oldValues = $category->toArray();

        if ($request->hasFile('image')) {
            if ($category->image_path && Storage::disk('public')->exists($category->image_path)) {
                Storage::disk('public')->delete($category->image_path);
            }
            $category->image_path = $request->file('image')->store('categories', 'public');
        }

        $category->update([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'name_ur' => $validated['name_ur'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'image_path' => $category->image_path,
        ]);

        AuditLog::log(
            event: 'category.updated',
            action: "Updated category: '{$category->name}'",
            auditable: $category,
            oldValues: $oldValues,
            newValues: $category->toArray()
        );

        return redirect()->route('tenant.categories.index', ['type' => $category->type])
            ->with('success', __('Category updated successfully.'));
    }

    /**
     * Delete category.
     */
    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('categories.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $category->tenant_id !== (int) $tenant->id, 404);

        if ($category->subcategories()->exists()) {
            return back()->with('error', __('Cannot delete category because it has subcategories.'));
        }

        if ($category->products()->exists()) {
            return back()->with('error', __('Cannot delete category because products are assigned to it.'));
        }

        $name = $category->name;
        $type = $category->type;

        if ($category->image_path && Storage::disk('public')->exists($category->image_path)) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();

        AuditLog::log(
            event: 'category.deleted',
            action: "Deleted category: '{$name}'"
        );

        return redirect()->route('tenant.categories.index', ['type' => $type])
            ->with('success', __('Category deleted successfully.'));
    }
}
