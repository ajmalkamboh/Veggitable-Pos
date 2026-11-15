@extends('layouts.admin')

@section('title', __('Measurement Units & Conversions'))

@section('content')
  <x-breadcrumb 
    :title="__('Units & Conversions')" 
    :items="[
      __('Master Data') => '#',
      __('Units') => route('tenant.units.index')
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.units.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        <span>{{ __('Add Custom Unit') }}</span>
      </a>
      <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addConversionModal">
        <i class="bi bi-arrow-left-right"></i>
        <span>{{ __('Add Conversion Rule') }}</span>
      </button>
    </x-slot>
  </x-breadcrumb>

  <div class="row g-4">
    <!-- Measurement Units List -->
    <div class="col-12 col-lg-7">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
          <h6 class="card-title mb-0 fw-bold">{{ __('Measurement Units') }}</h6>
          <span class="badge bg-light text-dark border">{{ $units->count() }} {{ __('Units') }}</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3">{{ __('Unit Name') }}</th>
                <th>{{ __('Urdu') }}</th>
                <th>{{ __('Code') }}</th>
                <th>{{ __('Type') }}</th>
                <th class="text-end pe-3">{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($units as $unit)
                <tr>
                  <td class="ps-3 fw-semibold text-dark">
                    {{ $unit->name }}
                    @if($unit->is_system)
                      <span class="badge bg-secondary-subtle text-secondary ms-1 small">{{ __('Default') }}</span>
                    @endif
                  </td>
                  <td>
                    <span class="urdu-text">{{ $unit->name_ur ?? '—' }}</span>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark font-monospace border">{{ $unit->code }}</span>
                  </td>
                  <td>
                    @if($unit->is_fractional)
                      <span class="badge bg-info-subtle text-info">{{ __('Fractional (0.5)') }}</span>
                    @else
                      <span class="badge bg-light text-secondary border">{{ __('Integer Only') }}</span>
                    @endif
                  </td>
                  <td class="text-end pe-3">
                    <div class="d-inline-flex gap-1">
                      <a href="{{ route('tenant.units.edit', $unit) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                        <i class="bi bi-pencil"></i>
                      </a>
                      @if(! $unit->is_system)
                        <form method="POST" action="{{ route('tenant.units.destroy', $unit) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this unit?') }}')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">
                    {{ __('No measurement units found.') }}
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Unit Conversions Panel -->
    <div class="col-12 col-lg-5">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
          <div>
            <h6 class="card-title mb-0 fw-bold">{{ __('Wholesale / Retail Unit Conversions') }}</h6>
            <small class="text-muted">{{ __('Mandi bulk lots to retail store weight') }}</small>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3">{{ __('Conversion Rule') }}</th>
                <th>{{ __('Multiplier') }}</th>
                <th class="text-end pe-3">{{ __('Action') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($conversions as $conv)
                <tr>
                  <td class="ps-3">
                    <div class="fw-bold text-dark">
                      1 {{ $conv->fromUnit?->name }} = {{ (float) $conv->multiplier }} {{ $conv->toUnit?->name }}
                    </div>
                    @if($conv->description)
                      <small class="text-muted">{{ $conv->description }}</small>
                    @endif
                  </td>
                  <td>
                    <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace">
                      × {{ (float) $conv->multiplier }}
                    </span>
                  </td>
                  <td class="text-end pe-3">
                    <form method="POST" action="{{ route('tenant.units.conversions.destroy', $conv) }}" onsubmit="return confirm('{{ __('Remove this conversion rule?') }}')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                        <i class="bi bi-x-circle"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center py-5 text-muted">
                    <i class="bi bi-arrow-left-right fs-1 d-block mb-2 text-secondary"></i>
                    <p class="mb-1 fw-semibold">{{ __('No conversion rules established.') }}</p>
                    <small>{{ __('Example: 1 Mann (40kg) = 40 Kg, or 1 Peti = 12 Kg') }}</small>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Conversion Rule Modal -->
  <div class="modal fade" id="addConversionModal" tabindex="-1" aria-labelledby="addConversionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="{{ route('tenant.units.conversions.store') }}">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold" id="addConversionModalLabel">{{ __('New Unit Conversion Rule') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('From Unit (Bulk / Purchase Unit)') }} <span class="text-danger">*</span></label>
              <select name="from_unit_id" class="form-select" required>
                <option value="">{{ __('Select bulk unit...') }}</option>
                @foreach($units as $u)
                  <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->code }})</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('To Unit (Base / Selling Unit)') }} <span class="text-danger">*</span></label>
              <select name="to_unit_id" class="form-select" required>
                <option value="">{{ __('Select retail unit...') }}</option>
                @foreach($units as $u)
                  <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->code }})</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Multiplier Factor') }} <span class="text-danger">*</span></label>
              <input type="number" step="0.0001" min="0.0001" name="multiplier" class="form-control" placeholder="e.g. 40.0 for 1 Mann = 40 Kg" required>
              <small class="text-muted">{{ __('How many target units equal 1 source unit?') }}</small>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Description / Note') }}</label>
              <input type="text" name="description" class="form-control" placeholder="e.g. Standard Mandi auction jute sack weight">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('Save Conversion') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

