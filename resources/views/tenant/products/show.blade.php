@extends('layouts.admin')

@section('title', $product->name)

@section('content')
  <x-breadcrumb 
    :title="$product->name" 
    :items="[
      __('Master Data') => '#',
      __('Products') => route('tenant.products.index'),
      $product->name => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.products.barcode', $product) }}" class="btn btn-info btn-sm text-white d-flex align-items-center gap-1">
        <i class="bi bi-upc-scan"></i>
        <span>{{ __('Barcode Sticker') }}</span>
      </a>
      <a href="{{ route('tenant.products.edit', $product) }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-pencil"></i>
        <span>{{ __('Edit Product') }}</span>
      </a>
      <a href="{{ route('tenant.products.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>{{ __('Back to List') }}</span>
      </a>
    </x-slot>
  </x-breadcrumb>

  <div class="row g-4">
    <!-- Product Header Card -->
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
          <div class="d-flex flex-column flex-md-row gap-4 align-items-start">
            @if($product->image_path)
              <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="rounded shadow-sm" style="width: 140px; height: 140px; object-fit: cover;">
            @else
              <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width: 140px; height: 140px;">
                <i class="bi bi-plant fs-1 text-success"></i>
              </div>
            @endif

            <div class="flex-grow-1">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <h4 class="mb-0 fw-bold text-dark">{{ $product->name }}</h4>
                @if($product->is_active)
                  <span class="badge bg-success-subtle text-success fs-6">{{ __('Active for Sale') }}</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary fs-6">{{ __('Archived') }}</span>
                @endif
              </div>

              @if($product->name_ur)
                <h5 class="urdu-text text-muted mb-3">{{ $product->name_ur }}</h5>
              @endif

              <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge bg-light text-dark font-monospace border">SKU: {{ $product->code }}</span>
                @if($product->barcode)
                  <span class="badge bg-light text-dark font-monospace border">Barcode: {{ $product->barcode }}</span>
                @endif
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ ucfirst($product->type) }}</span>
                @if($product->category)
                  <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $product->category->name }}</span>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Specification Cards -->
      <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
          <div class="card border-0 shadow-sm text-center py-3">
            <small class="text-muted text-uppercase fw-semibold">{{ __('Cost Price') }}</small>
            <h4 class="mb-0 fw-bold font-monospace mt-1">{{ $tenant->currency }} {{ number_format($product->cost_price, 2) }}</h4>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="card border-0 shadow-sm text-center py-3">
            <small class="text-muted text-uppercase fw-semibold">{{ __('Retail Price') }}</small>
            <h4 class="mb-0 fw-bold font-monospace text-primary mt-1">{{ $tenant->currency }} {{ number_format($product->selling_price, 2) }}</h4>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="card border-0 shadow-sm text-center py-3">
            <small class="text-muted text-uppercase fw-semibold">{{ __('Profit Margin') }}</small>
            <h4 class="mb-0 fw-bold font-monospace text-success mt-1">{{ $product->profit_margin_percentage }}%</h4>
          </div>
        </div>
      </div>

      <!-- Packaging & Stock Units -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
          <h6 class="card-title mb-0 fw-bold">{{ __('Measurement & Packaging Details') }}</h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-6 col-md-3">
              <span class="text-muted small d-block">{{ __('Purchase Bulk Unit') }}</span>
              <span class="fw-semibold">{{ $product->purchaseUnit?->name ?? '—' }} ({{ $product->purchaseUnit?->code ?? '—' }})</span>
            </div>
            <div class="col-6 col-md-3">
              <span class="text-muted small d-block">{{ __('Retail Selling Unit') }}</span>
              <span class="fw-semibold">{{ $product->sellingUnit?->name ?? '—' }} ({{ $product->sellingUnit?->code ?? '—' }})</span>
            </div>
            <div class="col-6 col-md-3">
              <span class="text-muted small d-block">{{ __('Stock Base Unit') }}</span>
              <span class="fw-semibold">{{ $product->stockUnit?->name ?? '—' }} ({{ $product->stockUnit?->code ?? '—' }})</span>
            </div>
            <div class="col-6 col-md-3">
              <span class="text-muted small d-block">{{ __('Conversion Multiplier') }}</span>
              <span class="fw-semibold font-monospace">× {{ (float) $product->unit_conversion_factor }}</span>
            </div>
            <div class="col-6 col-md-3">
              <span class="text-muted small d-block">{{ __('Reorder Alert Level') }}</span>
              <span class="fw-semibold text-danger">{{ (float) $product->reorder_level }} {{ $product->stockUnit?->code ?? 'units' }}</span>
            </div>
            <div class="col-6 col-md-3">
              <span class="text-muted small d-block">{{ __('Perishable Produce') }}</span>
              <span class="fw-semibold">{{ $product->is_perishable ? __('Yes') : __('No') }}</span>
            </div>
            <div class="col-6 col-md-3">
              <span class="text-muted small d-block">{{ __('Shelf Life') }}</span>
              <span class="fw-semibold">{{ $product->shelf_life_days }} {{ __('Days') }}</span>
            </div>
            <div class="col-6 col-md-3">
              <span class="text-muted small d-block">{{ __('Minimum Price Floor') }}</span>
              <span class="fw-semibold font-monospace">{{ $tenant->currency }} {{ number_format($product->min_selling_price, 2) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Barcode Sticker Preview -->
    <div class="col-12 col-lg-4">
      <div class="card border-0 shadow-sm text-center">
        <div class="card-header bg-white py-3">
          <h6 class="card-title mb-0 fw-bold">{{ __('Barcode Label Preview') }}</h6>
        </div>
        <div class="card-body">
          <div class="p-3 border rounded bg-light mb-3 text-center">
            <h6 class="fw-bold mb-1 text-dark">{{ $tenant->name }}</h6>
            <div class="fw-bold text-primary">{{ $product->name }}</div>
            @if($product->name_ur)
              <div class="urdu-text small text-muted">{{ $product->name_ur }}</div>
            @endif
            <div class="my-3">
              <!-- Barcode representation -->
              <div class="d-inline-block p-2 bg-white border rounded">
                <div style="font-family: monospace; letter-spacing: 5px; font-size: 24px; font-weight: 900; transform: scaleY(1.4);">
                  ||| | |||| | ||| | ||
                </div>
                <div class="font-monospace small text-dark mt-1">{{ $product->barcode }}</div>
              </div>
            </div>
            <div class="fs-5 fw-bold text-dark font-monospace">
              {{ $tenant->currency }} {{ number_format($product->selling_price, 2) }} / {{ $product->sellingUnit?->code ?? 'kg' }}
            </div>
          </div>

          <a href="{{ route('tenant.products.barcode', $product) }}" target="_blank" class="btn btn-outline-primary w-100">
            <i class="bi bi-printer me-1"></i> {{ __('Print Barcode Stickers') }}
          </a>
        </div>
      </div>
    </div>
  </div>
@endsection

