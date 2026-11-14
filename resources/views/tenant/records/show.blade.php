@extends('layouts.admin')

@section('title', 'Record Details: ' . $record->name)

@section('content')
  <x-breadcrumb 
    :title="'Record: ' . $record->name" 
    :items="[
      'Tenant Records' => route('tenant.records.index'),
      $record->code => '#'
    ]"
  >
    <x-slot name="actions">
      <a href="{{ route('tenant.records.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Records
      </a>
    </x-slot>
  </x-breadcrumb>

  <div class="row">
    <div class="col-lg-8 mx-auto">
      <x-card :title="$record->name" :subtitle="'Code: ' . $record->code">
        <div class="row mb-3">
          <div class="col-sm-4 text-muted small">Tenant ID:</div>
          <div class="col-sm-8 fw-semibold"><code>#{{ $record->tenant_id }}</code> ({{ $record->tenant?->name }})</div>
        </div>

        <div class="row mb-3">
          <div class="col-sm-4 text-muted small">Branch:</div>
          <div class="col-sm-8">{{ $record->branch?->name ?? 'All Branches (Global)' }}</div>
        </div>

        <div class="row mb-3">
          <div class="col-sm-4 text-muted small">Amount:</div>
          <div class="col-sm-8 fw-bold fs-5 text-success">Rs. {{ number_format($record->amount, 2) }}</div>
        </div>

        <div class="row mb-3">
          <div class="col-sm-4 text-muted small">Description:</div>
          <div class="col-sm-8 text-muted">{{ $record->description ?: 'No description provided.' }}</div>
        </div>

        <div class="row mb-3">
          <div class="col-sm-4 text-muted small">Created At:</div>
          <div class="col-sm-8 small text-muted">{{ $record->created_at->format('M d, Y H:i:s') }}</div>
        </div>
      </x-card>
    </div>
  </div>
@endsection
