@extends('layouts.admin')

@section('title', __('Create Category'))

@section('content')
  <x-breadcrumb 
    :title="__('Create Category')" 
    :items="[
      __('Master Data') => '#',
      __('Categories') => route('tenant.categories.index'),
      __('Create') => '#'
    ]"
  />

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
          <h6 class="card-title mb-0 fw-bold">{{ __('Category Details') }}</h6>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('tenant.categories.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="row g-3">
              <!-- Type -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">{{ __('Category Type') }} <span class="text-danger">*</span></label>
                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                  <option value="product" {{ old('type', $type) === 'product' ? 'selected' : '' }}>{{ __('Product Category') }}</option>
                  <option value="expense" {{ old('type', $type) === 'expense' ? 'selected' : '' }}>{{ __('Expense Category') }}</option>
                </select>
                @error('type')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Parent Category -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">{{ __('Parent Category') }} <span class="text-muted">({{ __('Optional for subcategory') }})</span></label>
                <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                  <option value="">{{ __('None (Root Category)') }}</option>
                  @foreach($parentCategories as $parent)
                    <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                      {{ $parent->name }} @if($parent->name_ur) ({{ $parent->name_ur }}) @endif
                    </option>
                  @endforeach
                </select>
                @error('parent_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- English Name -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">{{ __('Category Name (English)') }} <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Leafy Vegetables, Citrus Fruits" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Urdu Name -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">{{ __('Category Name (Urdu)') }} <span class="text-muted">({{ __('Optional') }})</span></label>
                <input type="text" name="name_ur" dir="rtl" class="form-control urdu-text @error('name_ur') is-invalid @enderror" value="{{ old('name_ur') }}" placeholder="مثال: پتوں والی سبزیاں">
                @error('name_ur')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Description -->
              <div class="col-12">
                <label class="form-label fw-semibold">{{ __('Description') }}</label>
                <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="{{ __('Optional category description...') }}">{{ old('description') }}</textarea>
                @error('description')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Image -->
              <div class="col-12 col-md-8">
                <label class="form-label fw-semibold">{{ __('Category Banner / Icon') }}</label>
                <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                <small class="text-muted">{{ __('Supported formats: JPG, PNG, WEBP up to 2MB') }}</small>
                @error('image')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Active Status -->
              <div class="col-12 col-md-4 d-flex align-items-center">
                <div class="form-check form-switch mt-3">
                  <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="isActiveSwitch">{{ __('Active Category') }}</label>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
              <a href="{{ route('tenant.categories.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
              <button type="submit" class="btn btn-primary px-4">{{ __('Save Category') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

