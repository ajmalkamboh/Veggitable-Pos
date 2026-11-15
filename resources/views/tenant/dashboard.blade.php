@extends('layouts.admin')

@section('title', __('Vegetable Shop Executive Dashboard'))

@section('content')
  <!-- Executive Header & Quick Actions -->
  <div class="row align-items-center justify-content-between mb-4 gy-2">
    <div class="col-md-6">
      <h4 class="fw-bold mb-1 text-dark">
        {{ $tenant->name }} <span class="badge bg-success-subtle text-success fs-12 ms-2">{{ __('Live Business Operations') }}</span>
      </h4>
      <p class="text-muted small mb-0">{{ __('Fresh Produce Daily Management, Procurement & POS Analytics') }}</p>
    </div>
    <div class="col-md-6 d-flex flex-wrap justify-content-md-end gap-2 align-items-center">
      <!-- Branch Selector -->
      @if($branches->count() > 1)
        <form method="GET" action="{{ route('tenant.dashboard') }}" class="d-inline-block">
          <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">{{ __('All Outlets / Branches') }}</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ $selectedBranchId == $b->id ? 'selected' : '' }}>
                {{ $b->name }}
              </option>
            @endforeach
          </select>
        </form>
      @endif

      <!-- Fast Action Buttons -->
      <a href="{{ route('tenant.pos.counter') }}" class="btn btn-success btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-cart-plus"></i>
        <span>{{ __('POS Counter') }}</span>
      </a>
      <a href="{{ route('tenant.purchases.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-truck"></i>
        <span>{{ __('New Mandi Bill') }}</span>
      </a>
      <a href="{{ route('tenant.inventory.wastage.create') }}" class="btn btn-danger btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-trash3"></i>
        <span>{{ __('Record Spoilage') }}</span>
      </a>
      <a href="{{ route('tenant.expenses.create') }}" class="btn btn-warning btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-wallet2"></i>
        <span>{{ __('Log Expense') }}</span>
      </a>
    </div>
  </div>

  <!-- Real-time Operational KPIs (Row 1) -->
  <div class="row g-3 mb-4">
    <!-- Today's Sales -->
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Today\'s Sales') }}</span>
              <h3 class="mb-0 fw-bold text-success mt-1">Rs. {{ number_format($metrics['todaySales'], 2) }}</h3>
              <small class="text-muted fs-11">{{ $metrics['todayTransactionsCount'] }} {{ __('transactions') }}</small>
            </div>
            <div class="avatar-sm bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-receipt fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Today's Cash vs Credit -->
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-info border-4 h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Today Cash / Credit') }}</span>
              <h4 class="mb-0 fw-bold text-info mt-1">Rs. {{ number_format($metrics['todayCashCollected'], 2) }}</h4>
              <small class="text-danger fw-semibold fs-11">Due: Rs. {{ number_format($metrics['todayCreditDue'], 2) }}</small>
            </div>
            <div class="avatar-sm bg-info-subtle text-info rounded-circle p-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-cash-stack fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Today's Mandi Procurement -->
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Today Mandi Purchases') }}</span>
              <h3 class="mb-0 fw-bold text-primary mt-1">Rs. {{ number_format($metrics['todayPurchases'], 2) }}</h3>
              <small class="text-muted fs-11">Month: Rs. {{ number_format($metrics['monthPurchases'], 2) }}</small>
            </div>
            <div class="avatar-sm bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-truck fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Inventory Valuation -->
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Current Stock Valuation') }}</span>
              <h3 class="mb-0 fw-bold text-warning mt-1">Rs. {{ number_format($metrics['inventoryValuation'], 2) }}</h3>
              @if($metrics['lowStockCount'] > 0)
                <span class="badge bg-danger-subtle text-danger fs-11">{{ $metrics['lowStockCount'] }} {{ __('items low') }}</span>
              @else
                <small class="text-success fs-11">{{ __('Stock levels healthy') }}</small>
              @endif
            </div>
            <div class="avatar-sm bg-warning-subtle text-warning rounded-circle p-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-boxes fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Operational KPIs (Row 2) -->
  <div class="row g-3 mb-4">
    <!-- Spoilage Loss -->
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Today Spoilage Loss') }}</span>
              <h4 class="mb-0 fw-bold text-danger mt-1">Rs. {{ number_format($metrics['todayWastageLoss'], 2) }}</h4>
              <small class="text-muted fs-11">{{ number_format($metrics['todayWastageQty'], 2) }} kg lost/culled</small>
            </div>
            <div class="avatar-sm bg-danger-subtle text-danger rounded-circle p-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-trash3 fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Operating Expenses -->
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-secondary border-4 h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Today Shop Overheads') }}</span>
              <h4 class="mb-0 fw-bold text-secondary mt-1">Rs. {{ number_format($metrics['todayExpenses'], 2) }}</h4>
              <small class="text-muted fs-11">Month: Rs. {{ number_format($metrics['monthExpenses'], 2) }}</small>
            </div>
            <div class="avatar-sm bg-light text-secondary rounded-circle p-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-wallet2 fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Month Revenue & Margin -->
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Month Net Revenue') }}</span>
              <h4 class="mb-0 fw-bold text-success mt-1">Rs. {{ number_format($metrics['monthSales'], 2) }}</h4>
              <small class="text-success fw-semibold fs-11">Margin: Rs. {{ number_format($metrics['monthGrossProfit'], 2) }}</small>
            </div>
            <div class="avatar-sm bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-graph-up-arrow fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Available Cash Reserves -->
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm border-start border-dark border-4 h-100">
        <div class="card-body p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="text-muted text-uppercase fw-semibold fs-11">{{ __('Cash Till & Bank Reserves') }}</span>
              <h4 class="mb-0 fw-bold text-dark mt-1">Rs. {{ number_format($metrics['cashAndBankReserves'], 2) }}</h4>
              <small class="text-muted fs-11">{{ __('Drawers & active accounts') }}</small>
            </div>
            <div class="avatar-sm bg-light text-dark rounded-circle p-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-safe fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts & Analytics Section -->
  <div class="row g-4 mb-4">
    <!-- 7-Day Performance Trend -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <h6 class="card-title mb-0 fw-bold">
            <i class="bi bi-bar-chart-line me-2 text-primary"></i>{{ __('7-Day Sales vs Mandi Procurement') }}
          </h6>
          <div class="d-flex gap-3 small">
            <span><i class="bi bi-square-fill text-success me-1"></i>{{ __('Sales') }}</span>
            <span><i class="bi bi-square-fill text-primary me-1"></i>{{ __('Procurement') }}</span>
          </div>
        </div>
        <div class="card-body p-4">
          @php
            $maxVal = 1;
            foreach ($metrics['trend7Days'] as $t) {
              $maxVal = max($maxVal, $t['sales'], $t['purchases']);
            }
          @endphp
          <div class="d-flex justify-content-between align-items-end" style="height: 180px;">
            @foreach($metrics['trend7Days'] as $t)
              @php
                $sHeight = max(5, round(($t['sales'] / $maxVal) * 150));
                $pHeight = max(5, round(($t['purchases'] / $maxVal) * 150));
              @endphp
              <div class="text-center flex-grow-1 px-1">
                <div class="d-flex justify-content-center align-items-end gap-1 mb-2" style="height: 150px;">
                  <div class="bg-success rounded-top" style="width: 16px; height: {{ $sHeight }}px;" title="Sales: Rs. {{ number_format($t['sales'], 2) }}"></div>
                  <div class="bg-primary rounded-top" style="width: 16px; height: {{ $pHeight }}px;" title="Purchases: Rs. {{ number_format($t['purchases'], 2) }}"></div>
                </div>
                <div class="text-muted small fw-semibold" style="font-size: 11px;">{{ $t['label'] }}</div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>

    <!-- Top-Selling Produce -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="card-title mb-0 fw-bold">
            <i class="bi bi-trophy me-2 text-warning"></i>{{ __('Top-Selling Produce (Last 30 Days)') }}
          </h6>
        </div>
        <div class="card-body p-3">
          <ul class="list-group list-group-flush">
            @forelse($metrics['topProduce'] as $idx => $prod)
              <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-light text-dark border">{{ $idx + 1 }}</span>
                  <div>
                    <span class="fw-bold text-dark d-block">{{ $prod->name }}</span>
                    <small class="text-muted">{{ number_format($prod->total_qty, 1) }} {{ $prod->unit_code }}</small>
                  </div>
                </div>
                <span class="fw-bold text-success font-monospace">Rs. {{ number_format($prod->total_rev, 2) }}</span>
              </li>
            @empty
              <li class="list-group-item text-center text-muted py-4">
                {{ __('No produce sales recorded yet.') }}
              </li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Operational Activity Tables (Recent Sales & Low Stock) -->
  <div class="row g-4">
    <!-- Recent POS Invoices -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <h6 class="card-title mb-0 fw-bold">
            <i class="bi bi-receipt me-2 text-primary"></i>{{ __('Recent POS Sales Invoices') }}
          </h6>
          <a href="{{ route('tenant.sales.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('View All') }}</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 font-monospace small">
            <thead class="table-light">
              <tr>
                <th class="ps-3">{{ __('Invoice #') }}</th>
                <th>{{ __('Customer') }}</th>
                <th class="text-end">{{ __('Net Total') }}</th>
                <th class="text-center">{{ __('Status') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($metrics['recentSales'] as $inv)
                <tr>
                  <td class="ps-3 fw-bold">
                    <a href="{{ route('tenant.sales.show', $inv->id) }}" class="text-decoration-none text-primary">
                      {{ $inv->invoice_no }}
                    </a>
                  </td>
                  <td>{{ $inv->customer?->name ?? __('Walk-in Retail') }}</td>
                  <td class="text-end fw-bold text-success">Rs. {{ number_format($inv->total_amount, 2) }}</td>
                  <td class="text-center">
                    <span class="badge {{ $inv->status_badge_class }}">{{ ucfirst($inv->status) }}</span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center py-4 text-muted">{{ __('No sales invoices recorded.') }}</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Low Stock Reorder Warning -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <h6 class="card-title mb-0 fw-bold text-danger">
            <i class="bi bi-exclamation-triangle me-2 text-danger"></i>{{ __('Low Stock & Reorder Alerts') }}
          </h6>
          <a href="{{ route('tenant.inventory.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('Inventory') }}</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th class="ps-3">{{ __('Produce') }}</th>
                <th>{{ __('Code') }}</th>
                <th class="text-end">{{ __('Current Stock') }}</th>
                <th class="text-end pe-3">{{ __('Threshold') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($metrics['lowStockItems'] as $item)
                <tr>
                  <td class="ps-3 fw-bold text-dark">{{ $item->name }}</td>
                  <td class="font-monospace text-secondary">{{ $item->code }}</td>
                  <td class="text-end fw-bold text-danger font-monospace">{{ number_format($item->quantity, 2) }} kg</td>
                  <td class="text-end pe-3 text-muted font-monospace">{{ number_format($item->reorder_level, 2) }} kg</td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center py-4 text-success">
                    <i class="bi bi-check-circle me-1"></i> {{ __('All produce items have adequate stock.') }}
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection
