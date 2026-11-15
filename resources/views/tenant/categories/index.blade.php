@extends('layouts.admin')

@section('title', __('Categories & Subcategories'))

@section('content')
  <x-breadcrumb 
    :title="__('Master Categories')" 
    :items="[
      __('Master Data') => '#',
      __('Categories') => route('tenant.categories.index')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.categories.create', ['type' => request('type', 'product')]) }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        <span>{{ __('Create Category') }}</span>
      </a>
      <a href="{{ route('tenant.products.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-box-seam"></i>
        <span>{{ __('Products Catalog') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Filter & Search Toolbar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('tenant.categories.index') }}" class="row g-3 align-items-center">
        <div class="col-12 col-md-4">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="{{ __('Search category (English or Urdu)...') }}" value="{{ request('search') }}">
          </div>
        </div>
        <div class="col-12 col-md-3">
          <select name="type" class="form-select" onchange="this.form.submit()">
            <option value="">{{ __('All Types (Products & Expenses)') }}</option>
            <option value="product" {{ request('type') === 'product' ? 'selected' : '' }}>{{ __('Product Categories') }}</option>
            <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>{{ __('Expense Categories') }}</option>
          </select>
        </div>
        <div class="col-12 col-md-auto ms-auto d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm">{{ __('Filter') }}</button>
          <a href="{{ route('tenant.categories.index') }}" class="btn btn-light btn-sm">{{ __('Reset') }}</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Categories Table -->
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-4">{{ __('Category Name') }}</th>
            <th>{{ __('Urdu Name') }}</th>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Parent Category') }}</th>
            <th>{{ __('Subcategories') }}</th>
            <th>{{ __('Items Count') }}</th>
            <th>{{ __('Status') }}</th>
            <th class="text-end pe-4">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($categories as $category)
            <tr>
              <td class="ps-4">
                <div class="d-flex align-items-center gap-2">
                  @if($category->image_path)
                    <img src="{{ asset('storage/' . $category->image_path) }}" alt="{{ $category->name }}" class="rounded" style="width: 36px; height: 36px; object-fit: cover;">
                  @else
                    <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width: 36px; height: 36px;">
                      <i class="bi bi-tags"></i>
                    </div>
                  @endif
                  <div>
                    <div class="fw-bold text-dark">{{ $category->name }}</div>
                    <small class="text-muted font-monospace">{{ $category->slug }}</small>
                  </div>
                </div>
              </td>
              <td>
                <span class="urdu-text">{{ $category->name_ur ?? '—' }}</span>
              </td>
              <td>
                @if($category->type === 'product')
                  <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('Product') }}</span>
                @else
                  <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ __('Expense') }}</span>
                @endif
              </td>
              <td>
                @if($category->parent)
                  <span class="badge bg-light text-dark border">{{ $category->parent->name }}</span>
                @else
                  <span class="text-muted small">{{ __('Root Category') }}</span>
                @endif
              </td>
              <td>
                <span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle">
                  {{ $category->subcategories_count }}
                </span>
              </td>
              <td>
                <span class="badge rounded-pill bg-light text-dark border">
                  {{ $category->products_count }}
                </span>
              </td>
              <td>
                @if($category->is_active)
                  <span class="badge bg-success-subtle text-success">{{ __('Active') }}</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary">{{ __('Inactive') }}</span>
                @endif
              </td>
              <td class="text-end pe-4">
                <div class="d-inline-flex gap-1">
                  <a href="{{ route('tenant.categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('tenant.categories.destroy', $category) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this category?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="bi bi-tags fs-1 d-block mb-2 text-secondary"></i>
                <p class="mb-1 fw-semibold">{{ __('No categories found.') }}</p>
                <small>{{ __('Create your product or expense categories to organize your shop inventory.') }}</small>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($categories->hasPages())
      <div class="card-footer bg-white py-3">
        {{ $categories->links() }}
      </div>
    @endif
  </div>
@endsection

