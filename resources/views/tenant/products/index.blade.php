@extends('layouts.admin')

@section('title', __('Vegetables & Fruits Catalog'))

@section('content')
  <x-breadcrumb 
    :title="__('Products & Inventory Items')" 
    :items="[
      __('Master Data') => '#',
      __('Products') => route('tenant.products.index')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.products.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        <span>{{ __('Add Product') }}</span>
      </a>
      <a href="{{ route('tenant.categories.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-tags"></i>
        <span>{{ __('Categories') }}</span>
      </a>
      <a href="{{ route('tenant.units.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-rulers"></i>
        <span>{{ __('Units') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <!-- Filter & Search Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('tenant.products.index') }}" class="row g-3 align-items-center">
        <!-- Search -->
        <div class="col-12 col-md-4">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="{{ __('Search by product, Urdu name, code or barcode...') }}" value="{{ request('search') }}">
          </div>
        </div>

        <!-- Type Filter -->
        <div class="col-12 col-md-3">
          <select name="type" class="form-select" onchange="this.form.submit()">
            <option value="">{{ __('All Product Types') }}</option>
            @foreach($types as $key => $label)
              <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>

        <!-- Category Filter -->
        <div class="col-12 col-md-3">
          <select name="category_id" class="form-select" onchange="this.form.submit()">
            <option value="">{{ __('All Categories') }}</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-2 d-flex gap-2 justify-content-end">
          <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('Filter') }}</button>
          <a href="{{ route('tenant.products.index') }}" class="btn btn-light btn-sm">{{ __('Reset') }}</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Products Table Card -->
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">{{ __('Item') }}</th>
            <th>{{ __('Urdu Name') }}</th>
            <th>{{ __('Category') }}</th>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Stock Unit') }}</th>
            <th class="text-end">{{ __('Cost') }}</th>
            <th class="text-end">{{ __('Selling') }}</th>
            <th class="text-center">{{ __('Margin') }}</th>
            <th class="text-center">{{ __('Shelf Life') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-end pe-3">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($products as $product)
            <tr>
              <td class="ps-3">
                <div class="d-flex align-items-center gap-2">
                  @if($product->image_path)
                    <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                  @else
                    <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width: 40px; height: 40px;">
                      <i class="bi bi-plant fs-5 text-success"></i>
                    </div>
                  @endif
                  <div>
                    <a href="{{ route('tenant.products.show', $product) }}" class="fw-bold text-dark text-decoration-none">
                      {{ $product->name }}
                    </a>
                    <div class="small text-muted font-monospace">
                      <span>{{ $product->code }}</span>
                      @if($product->barcode)
                        • <span>{{ $product->barcode }}</span>
                      @endif
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <span class="urdu-text">{{ $product->name_ur ?? '—' }}</span>
              </td>
              <td>
                @if($product->category)
                  <span class="badge bg-light text-dark border">{{ $product->category->name }}</span>
                  @if($product->subcategory)
                    <small class="d-block text-muted">{{ $product->subcategory->name }}</small>
                  @endif
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
              <td>
                @if($product->type === 'vegetable')
                  <span class="badge bg-success-subtle text-success">{{ __('Vegetable') }}</span>
                @elseif($product->type === 'fruit')
                  <span class="badge bg-warning-subtle text-warning">{{ __('Fruit') }}</span>
                @elseif($product->type === 'mandi_lot')
                  <span class="badge bg-info-subtle text-info">{{ __('Mandi Lot') }}</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary">{{ __('Pre-packed') }}</span>
                @endif
              </td>
              <td>
                <span class="badge bg-light text-dark font-monospace border">
                  {{ $product->stockUnit?->code ?? 'kg' }}
                </span>
              </td>
              <td class="text-end font-monospace">
                {{ $tenant->currency }} {{ number_format($product->cost_price, 2) }}
              </td>
              <td class="text-end font-monospace fw-bold text-primary">
                {{ $tenant->currency }} {{ number_format($product->selling_price, 2) }}
              </td>
              <td class="text-center font-monospace">
                @php $margin = $product->profit_margin_percentage; @endphp
                <span class="badge {{ $margin >= 20 ? 'bg-success-subtle text-success' : ($margin > 0 ? 'bg-info-subtle text-info' : 'bg-danger-subtle text-danger') }}">
                  {{ $margin }}%
                </span>
              </td>
              <td class="text-center small">
                @if($product->is_perishable)
                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="{{ __('Perishable') }}">
                    <i class="bi bi-clock-history me-1"></i> {{ $product->shelf_life_days }}d
                  </span>
                @else
                  <span class="badge bg-light text-muted border">{{ __('Stable') }}</span>
                @endif
              </td>
              <td class="text-center">
                @if($product->is_active)
                  <span class="badge bg-success-subtle text-success">{{ __('Active') }}</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary">{{ __('Archived') }}</span>
                @endif
              </td>
              <td class="text-end pe-3">
                <div class="d-inline-flex gap-1">
                  <a href="{{ route('tenant.products.barcode', $product) }}" class="btn btn-sm btn-outline-info" title="{{ __('Barcode Slip') }}">
                    <i class="bi bi-upc-scan"></i>
                  </a>
                  <a href="{{ route('tenant.products.edit', $product) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" action="{{ route('tenant.products.destroy', $product) }}" onsubmit="return confirm('{{ __('Are you sure you want to archive this product?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Archive') }}">
                      <i class="bi bi-archive"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="text-center py-5 text-muted">
                <i class="bi bi-plant fs-1 d-block mb-2 text-secondary"></i>
                <p class="mb-1 fw-semibold">{{ __('No products found matching criteria.') }}</p>
                <small>{{ __('Add vegetables, fruits, and wholesale lot products to begin cataloging.') }}</small>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($products->hasPages())
      <div class="card-footer bg-white py-3">
        {{ $products->links() }}
      </div>
    @endif
  </div>
@endsection

