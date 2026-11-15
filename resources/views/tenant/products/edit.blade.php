@extends('layouts.admin')

@section('title', __('Edit Product'))

@section('content')
  <x-breadcrumb 
    :title="__('Edit Product')" 
    :items="[
      __('Master Data') => '#',
      __('Products') => route('tenant.products.index'),
      $product->name => route('tenant.products.show', $product),
      __('Edit') => '#'
    ]"
  />

  <div class="row justify-content-center">
    <div class="col-12 col-xl-10">
      <form method="POST" action="{{ route('tenant.products.update', $product) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
          <!-- Main Product Information -->
          <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-bold">{{ __('Basic Information') }}</h6>
                <span class="badge bg-light text-dark font-monospace">{{ $product->code }}</span>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <!-- Name English -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Product Name (English)') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name) }}" required>
                    @error('name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Name Urdu -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Product Name (Urdu)') }}</label>
                    <input type="text" name="name_ur" dir="rtl" class="form-control urdu-text @error('name_ur') is-invalid @enderror" value="{{ old('name_ur', $product->name_ur) }}">
                    @error('name_ur')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Type -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Product Type') }} <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                      @foreach($types as $key => $label)
                        <option value="{{ $key }}" {{ old('type', $product->type) === $key ? 'selected' : '' }}>{{ $label }}</option>
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
                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
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
                    <label class="form-label fw-semibold">{{ __('Product SKU / Code') }} <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control font-monospace @error('code') is-invalid @enderror" value="{{ old('code', $product->code) }}" required>
                    @error('code')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Barcode / EAN-13') }}</label>
                    <input type="text" name="barcode" class="form-control font-monospace @error('barcode') is-invalid @enderror" value="{{ old('barcode', $product->barcode) }}">
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
                        <option value="{{ $unit->id }}" {{ old('purchase_unit_id', $product->purchase_unit_id) == $unit->id ? 'selected' : '' }}>
                          {{ $unit->name }} ({{ $unit->code }})
                        </option>
                      @endforeach
                    </select>
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
                        <option value="{{ $unit->id }}" {{ old('selling_unit_id', $product->selling_unit_id) == $unit->id ? 'selected' : '' }}>
                          {{ $unit->name }} ({{ $unit->code }})
                        </option>
                      @endforeach
                    </select>
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
                        <option value="{{ $unit->id }}" {{ old('stock_unit_id', $product->stock_unit_id) == $unit->id ? 'selected' : '' }}>
                          {{ $unit->name }} ({{ $unit->code }})
                        </option>
                      @endforeach
                    </select>
                    @error('stock_unit_id')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Conversion Factor -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Purchase to Stock Factor') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.0001" min="0.0001" name="unit_conversion_factor" class="form-control @error('unit_conversion_factor') is-invalid @enderror" value="{{ old('unit_conversion_factor', (float) $product->unit_conversion_factor) }}" required>
                    @error('unit_conversion_factor')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <!-- Reorder Level -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">{{ __('Low Stock Reorder Alert Level') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="reorder_level" class="form-control @error('reorder_level') is-invalid @enderror" value="{{ old('reorder_level', (float) $product->reorder_level) }}" required>
                    @error('reorder_level')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pricing & Attributes Column -->
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
                    <input type="number" step="0.01" min="0" name="cost_price" class="form-control @error('cost_price') is-invalid @enderror" value="{{ old('cost_price', (float) $product->cost_price) }}" required>
                  </div>
                  @error('cost_price')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Retail Selling Price') }} <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-light">{{ $tenant->currency }}</span>
                    <input type="number" step="0.01" min="0" name="selling_price" class="form-control @error('selling_price') is-invalid @enderror" value="{{ old('selling_price', (float) $product->selling_price) }}" required>
                  </div>
                  @error('selling_price')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Minimum Selling Price (Floor)') }}</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light">{{ $tenant->currency }}</span>
                    <input type="number" step="0.01" min="0" name="min_selling_price" class="form-control @error('min_selling_price') is-invalid @enderror" value="{{ old('min_selling_price', (float) $product->min_selling_price) }}">
                  </div>
                  @error('min_selling_price')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <!-- Attributes & Media -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-bold">{{ __('Attributes & Media') }}</h6>
              </div>
              <div class="card-body">
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" name="is_perishable" id="isPerishableSwitch" value="1" {{ old('is_perishable', $product->is_perishable) ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="isPerishableSwitch">{{ __('Perishable Produce') }}</label>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Shelf Life (Days)') }}</label>
                  <input type="number" name="shelf_life_days" class="form-control" value="{{ old('shelf_life_days', $product->shelf_life_days) }}" min="1">
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">{{ __('Product Photo') }}</label>
                  @if($product->image_path)
                    <div class="mb-2">
                      <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="rounded img-thumbnail" style="height: 60px;">
                    </div>
                  @endif
                  <input type="file" name="image" class="form-control" accept="image/*">
                </div>

                <div class="form-check form-switch mt-3 pt-3 border-top">
                  <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="isActiveSwitch">{{ __('Available for Sale (Active)') }}</label>
                </div>
              </div>
            </div>

            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary btn-lg">{{ __('Update Product') }}</button>
              <a href="{{ route('tenant.products.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection

