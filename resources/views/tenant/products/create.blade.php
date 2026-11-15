@extends('layouts.admin')

@section('title', __('Add New Product'))

@section('content')
  <x-breadcrumb 
    :title="__('Add New Product')" 
    :items="[
      __('Master Data') => '#',
      __('Products') => route('tenant.products.index'),
      __('Create') => '#'
    ]"
  />

  <div class="row justify-content-center">
    <div class="col-12 col-xl-10">
      <form method="POST" action="{{ route('tenant.products.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
          <!-- Main Product Information -->
          <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">{{ __('Basic Information') }}</h6>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <!-- Name English -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Product Name (English)') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Potato Russet, Onion Red, Apple Royal Gala" required>
                    @error('name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Name Urdu -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Product Name (Urdu)') }} <span class="text-muted">({{ __('Optional') }})</span></label>
                    <input type="text" name="name_ur" dir="rtl" class="form-control urdu-text @error('name_ur') is-invalid @enderror" value="{{ old('name_ur') }}" placeholder="مثال: آلو، پیاز، سیب">
                    @error('name_ur')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Type -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Product Type') }} <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                      @foreach($types as $key => $label)
                        <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                      @endforeach
                    </select>
                    @error('type')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Category -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Primary Category') }}</label>
                    <select name="category_id" id="categorySelect" class="form-select @error('category_id') is-invalid @enderror">
                      <option value="">{{ __('Select Category...') }}</option>
                      @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                          {{ $category->name }}
                        </option>
                      @endforeach
                    </select>
                    @error('category_id')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Code & Barcode -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Product SKU / Code') }} <span class="text-muted">({{ __('Leave blank to auto-generate') }})</span></label>
                    <input type="text" name="code" class="form-control font-monospace @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="e.g. PRD-POT-01">
                    @error('code')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Barcode / EAN-13') }} <span class="text-muted">({{ __('Leave blank to auto-generate') }})</span></label>
                    <input type="text" name="barcode" class="form-control font-monospace @error('barcode') is-invalid @enderror" value="{{ old('barcode') }}" placeholder="e.g. 200100012345">
                    @error('barcode')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>
            </div>

            <!-- Units & Wholesale Conversion -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">{{ __('Units & Packaging Configuration') }}</h6>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <!-- Purchase Unit -->
                  <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">{{ __('Purchase / Lot Unit') }}</label>
                    <select name="purchase_unit_id" class="form-select @error('purchase_unit_id') is-invalid @enderror">
                      <option value="">{{ __('Select unit...') }}</option>
                      @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('purchase_unit_id') == $unit->id ? 'selected' : '' }}>
                          {{ $unit->name }} ({{ $unit->code }})
                        </option>
                      @endforeach
                    </select>
                    <small class="text-muted">{{ __('Mandi bulk lot unit (e.g. Mann, Bori, Peti)') }}</small>
                    @error('purchase_unit_id')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Selling Unit -->
                  <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">{{ __('Selling Unit') }}</label>
                    <select name="selling_unit_id" class="form-select @error('selling_unit_id') is-invalid @enderror">
                      <option value="">{{ __('Select unit...') }}</option>
                      @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('selling_unit_id') == $unit->id ? 'selected' : '' }}>
                          {{ $unit->name }} ({{ $unit->code }})
                        </option>
                      @endforeach
                    </select>
                    <small class="text-muted">{{ __('Cashier POS unit (e.g. Kg, Dozen, Piece)') }}</small>
                    @error('selling_unit_id')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Stock Base Unit -->
                  <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">{{ __('Inventory Base Unit') }}</label>
                    <select name="stock_unit_id" class="form-select @error('stock_unit_id') is-invalid @enderror">
                      <option value="">{{ __('Select unit...') }}</option>
                      @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('stock_unit_id') == $unit->id ? 'selected' : '' }}>
                          {{ $unit->name }} ({{ $unit->code }})
                        </option>
                      @endforeach
                    </select>
                    <small class="text-muted">{{ __('Stock valuation base unit (usually kg)') }}</small>
                    @error('stock_unit_id')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Conversion Factor -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Purchase to Stock Factor') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.0001" min="0.0001" name="unit_conversion_factor" class="form-control @error('unit_conversion_factor') is-invalid @enderror" value="{{ old('unit_conversion_factor', 1.0) }}" required>
                    <small class="text-muted">{{ __('e.g. If purchasing in 40kg Mann and stock is Kg, enter 40.0') }}</small>
                    @error('unit_conversion_factor')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Reorder Level -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Low Stock Reorder Alert Level') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="reorder_level" class="form-control @error('reorder_level') is-invalid @enderror" value="{{ old('reorder_level', 10.0) }}" required>
                    <small class="text-muted">{{ __('System alerts cashier/manager when stock falls below this amount.') }}</small>
                    @error('reorder_level')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pricing & Attributes Sidebar Column -->
          <div class="col-12 col-lg-4">
            <!-- Pricing Card -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">{{ __('Pricing Rules') }} ({{ $tenant->currency }})</h6>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Cost Price') }} <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-light">{{ $tenant->currency }}</span>
                    <input type="number" step="0.01" min="0" name="cost_price" class="form-control @error('cost_price') is-invalid @enderror" value="{{ old('cost_price', '0.00') }}" required>
                  </div>
                  @error('cost_price')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Retail Selling Price') }} <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-light">{{ $tenant->currency }}</span>
                    <input type="number" step="0.01" min="0" name="selling_price" class="form-control @error('selling_price') is-invalid @enderror" value="{{ old('selling_price', '0.00') }}" required>
                  </div>
                  @error('selling_price')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Minimum Selling Price (Floor)') }}</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light">{{ $tenant->currency }}</span>
                    <input type="number" step="0.01" min="0" name="min_selling_price" class="form-control @error('min_selling_price') is-invalid @enderror" value="{{ old('min_selling_price', '0.00') }}">
                  </div>
                  <small class="text-muted">{{ __('Prevents cashier from discounting below this price.') }}</small>
                  @error('min_selling_price')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <!-- Perishability & Image -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">{{ __('Attributes & Media') }}</h6>
              </div>
              <div class="card-body">
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" name="is_perishable" id="isPerishableSwitch" value="1" {{ old('is_perishable', true) ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="isPerishableSwitch">{{ __('Perishable Produce') }}</label>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Shelf Life (Days)') }}</label>
                  <input type="number" name="shelf_life_days" class="form-control" value="{{ old('shelf_life_days', 3) }}" min="1">
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Product Photo') }}</label>
                  <input type="file" name="image" class="form-control" accept="image/*">
                  <small class="text-muted">{{ __('JPG, PNG or WEBP up to 2MB') }}</small>
                </div>

                <div class="form-check form-switch mt-3 pt-3 border-top">
                  <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="isActiveSwitch">{{ __('Available for Sale (Active)') }}</label>
                </div>
              </div>
            </div>

            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary btn-lg">{{ __('Save Product') }}</button>
              <a href="{{ route('tenant.products.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection

