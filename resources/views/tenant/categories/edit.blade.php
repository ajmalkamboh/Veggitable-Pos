@extends('layouts.admin')

@section('title', __('Edit Category'))

@section('content')
  <x-breadcrumb 
    :title="__('Edit Category')" 
    :items="[
      __('Master Data') => '#',
      __('Categories') => route('tenant.categories.index'),
      $category->name => '#'
    ]"
  />

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
          <h6 class="card-title mb-0 fw-bold">{{ __('Edit Category') }}: {{ $category->name }}</h6>
          <span class="badge bg-light text-dark font-monospace">{{ $category->slug }}</span>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('tenant.categories.update', $category) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row g-3">
              <!-- Type (Read-only on edit) -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">{{ __('Category Type') }}</label>
                <input type="text" class="form-control" value="{{ ucfirst($category->type) }} Category" disabled readonly>
              </div>

              <!-- Parent Category -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">{{ __('Parent Category') }} <span class="text-muted">({{ __('Optional for subcategory') }})</span></label>
                <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                  <option value="">{{ __('None (Root Category)') }}</option>
                  @foreach($parentCategories as $parent)
                    <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>
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
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Urdu Name -->
              <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">{{ __('Category Name (Urdu)') }}</label>
                <input type="text" name="name_ur" dir="rtl" class="form-control urdu-text @error('name_ur') is-invalid @enderror" value="{{ old('name_ur', $category->name_ur) }}">
                @error('name_ur')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Description -->
              <div class="col-12">
                <label class="form-label fw-semibold">{{ __('Description') }}</label>
                <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
                @error('description')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Image -->
              <div class="col-12 col-md-8">
                <label class="form-label fw-semibold">{{ __('Category Banner / Icon') }}</label>
                @if($category->image_path)
                  <div class="mb-2">
                    <img src="{{ asset('storage/' . $category->image_path) }}" alt="{{ $category->name }}" class="rounded img-thumbnail" style="height: 60px;">
                  </div>
                @endif
                <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                @error('image')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Active Status -->
              <div class="col-12 col-md-4 d-flex align-items-center">
                <div class="form-check form-switch mt-3">
                  <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="isActiveSwitch">{{ __('Active Category') }}</label>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
              <a href="{{ route('tenant.categories.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
              <button type="submit" class="btn btn-primary px-4">{{ __('Update Category') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

