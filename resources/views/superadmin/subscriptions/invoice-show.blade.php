@extends('layouts.admin')

@section('title', 'Subscription Invoice #' . $invoice->invoice_number)

@section('content')
  <x-breadcrumb 
    title="Invoice Details" 
    :items="[
      'Super Admin' => route('superadmin.dashboard'),
      'Subscriptions' => '#',
      'Payments' => route('superadmin.subscriptions.payments'),
      $invoice->invoice_number => '#'
    ]"
  >
    <x-slot name="actions">
      <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-printer"></i>
        <span>Print Invoice</span>
      </button>
      <a href="{{ route('superadmin.subscriptions.payments') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Payments</span>
      </a>
      @if($invoice->tenant)
        <a href="{{ route('superadmin.tenants.show', $invoice->tenant->id) }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
          <i class="bi bi-building"></i>
          <span>View Tenant</span>
        </a>
      @endif
    </x-slot>
  </x-breadcrumb>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-9">
      <div class="card border-0 shadow-sm print-container">
        <div class="card-body p-4 p-md-5">
          <!-- Invoice Header -->
          <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
            <div>
              <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-basket-fill text-success fs-3"></i>
                <span class="fs-4 fw-bold text-dark">Veggitable SaaS</span>
              </div>
              <p class="text-muted small mb-0">Vegetable & Fruit Shop Management Platform</p>
              <p class="text-muted small mb-0">Cloud Multi-Tenant SaaS Billing Service</p>
            </div>
            <div class="text-end">
              <h4 class="fw-bold text-primary font-monospace mb-1">{{ $invoice->invoice_number }}</h4>
              <div class="small text-muted mb-2">Issue Date: {{ $invoice->created_at->format('M d, Y') }}</div>
              @php
                $statusBadge = match($invoice->payment_status?->value ?? $invoice->payment_status) {
                  'paid' => 'bg-success',
                  'pending' => 'bg-warning text-dark',
                  'failed' => 'bg-danger',
                  'refunded' => 'bg-secondary',
                  default => 'bg-info',
                };
              @endphp
              <span class="badge {{ $statusBadge }} fs-6 px-3 py-1">
                {{ strtoupper($invoice->payment_status?->value ?? $invoice->payment_status) }}
              </span>
            </div>
          </div>

          <!-- Billed To & Payment Details -->
          <div class="row mb-4">
            <div class="col-sm-6 mb-3 mb-sm-0">
              <h6 class="text-uppercase text-muted fw-bold small mb-2">Billed To (Tenant)</h6>
              <h5 class="fw-bold mb-1">{{ $invoice->tenant?->name ?? 'Unknown Business' }}</h5>
              <div class="small text-muted"><strong>Tenant ID:</strong> #{{ $invoice->tenant?->id }} ({{ $invoice->tenant?->slug }})</div>
              @if($invoice->tenant?->email)
                <div class="small text-muted"><strong>Email:</strong> {{ $invoice->tenant->email }}</div>
              @endif
              @if($invoice->tenant?->phone)
                <div class="small text-muted"><strong>Phone:</strong> {{ $invoice->tenant->phone }}</div>
              @endif
              @if($invoice->tenant?->address)
                <div class="small text-muted"><strong>Address:</strong> {{ $invoice->tenant->address }}, {{ $invoice->tenant->city }}</div>
              @endif
            </div>

            <div class="col-sm-6 text-sm-end">
              <h6 class="text-uppercase text-muted fw-bold small mb-2">Payment Meta</h6>
              <div class="small mb-1"><strong>Payment Method:</strong> {{ strtoupper(str_replace('_', ' ', $invoice->payment_method)) }}</div>
              @if($invoice->transaction_reference)
                <div class="small mb-1"><strong>Reference / TXN:</strong> <code class="text-dark">{{ $invoice->transaction_reference }}</code></div>
              @endif
              @if($invoice->paid_at)
                <div class="small mb-1"><strong>Payment Date:</strong> {{ $invoice->paid_at->format('M d, Y h:i A') }}</div>
              @endif
              @if($invoice->subscription)
                <div class="small text-muted"><strong>Subscription ID:</strong> #{{ $invoice->subscription->id }} ({{ $invoice->subscription->billing_cycle?->label() ?? 'Monthly' }})</div>
              @endif
            </div>
          </div>

          <!-- Line Items Table -->
          <div class="table-responsive mb-4">
            <table class="table table-bordered">
              <thead class="table-light">
                <tr>
                  <th class="py-2">Description</th>
                  <th class="py-2 text-center" style="width: 120px;">Billing Cycle</th>
                  <th class="py-2 text-end" style="width: 150px;">Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <div class="fw-bold">{{ $invoice->plan?->name ?? 'SaaS Subscription' }} Plan</div>
                    <small class="text-muted">
                      {{ $invoice->notes ?? 'Subscription access to platform modules, branches, and features.' }}
                    </small>
                  </td>
                  <td class="text-center align-middle">
                    {{ ucfirst($invoice->subscription?->billing_cycle?->value ?? 'monthly') }}
                  </td>
                  <td class="text-end align-middle fw-semibold">
                    {{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="2" class="text-end pe-3">Subtotal:</th>
                  <td class="text-end fw-semibold">{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</td>
                </tr>
                <tr>
                  <th colspan="2" class="text-end pe-3">Tax / Deductions:</th>
                  <td class="text-end text-muted">{{ $invoice->currency }} 0.00</td>
                </tr>
                <tr class="table-light">
                  <th colspan="2" class="text-end pe-3 fs-5">Total Paid:</th>
                  <td class="text-end fw-bold fs-5 text-success">
                    {{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Footer Notice -->
          <div class="border-top pt-3 text-center text-muted small">
            <p class="mb-1">Thank you for partnering with Veggitable SaaS.</p>
            <p class="mb-0">This is a computer generated payment receipt and requires no physical signature.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <style>
    @media print {
      body * {
        visibility: hidden;
      }
      .print-container, .print-container * {
        visibility: visible;
      }
      .print-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none !important;
        border: none !important;
      }
      .btn, header, .sidebar, .breadcrumb-container {
        display: none !important;
      }
    }
  </style>
@endsection

