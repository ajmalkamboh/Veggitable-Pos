@extends('layouts.admin')

@section('title', __('Business Notifications & Alerts'))

@section('content')
<div class="row align-items-center justify-content-between mb-4 gy-2">
  <div class="col-md-6">
    <h4 class="fw-bold mb-1 text-dark">{{ __('Notifications & Automated Business Alerts') }}</h4>
    <p class="text-muted small mb-0">{{ __('Real-time alerts for low produce stocks, daily sales summaries, customer overdue balances, and mandi payables') }}</p>
  </div>
  <div class="col-md-6 text-md-end d-flex flex-wrap justify-content-md-end gap-2">
    @if($unreadCount > 0)
      <form method="POST" action="{{ route('tenant.notifications.read-all') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-check-all me-1"></i> {{ __('Mark All as Read') }} ({{ $unreadCount }})
        </button>
      </form>
    @endif
    <a href="{{ route('tenant.notifications.settings') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-gear me-1"></i> {{ __('Alert Channels & Rules') }}
    </a>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

<!-- Quick Automation Scanner Toolbar -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body p-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div>
        <span class="fw-bold small text-dark"><i class="bi bi-robot me-1 text-primary"></i>{{ __('On-Demand Automation Diagnostics:') }}</span>
        <span class="text-muted small ms-1">{{ __('Run immediate background checks across store modules') }}</span>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-outline-danger btn-sm" onclick="runRoutine('low_stock')">
          <i class="bi bi-exclamation-triangle me-1"></i> {{ __('Scan Low Stock') }}
        </button>
        <button class="btn btn-outline-success btn-sm" onclick="runRoutine('daily_sales')">
          <i class="bi bi-cash-stack me-1"></i> {{ __('Generate Sales Summary') }}
        </button>
        <button class="btn btn-outline-warning btn-sm" onclick="runRoutine('daily_wastage')">
          <i class="bi bi-trash3 me-1"></i> {{ __('Generate Wastage Summary') }}
        </button>
        <button class="btn btn-outline-info btn-sm" onclick="runRoutine('customer_balances')">
          <i class="bi bi-people me-1"></i> {{ __('Check Credit Balances') }}
        </button>
      </div>
    </div>
    <div id="routineStatus" class="mt-2 text-primary small d-none font-monospace"></div>
  </div>
</div>

<!-- Notifications List -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <div class="d-flex gap-2">
      <a href="{{ route('tenant.notifications.index') }}" class="btn btn-sm {{ !request('filter') ? 'btn-primary' : 'btn-light border' }}">
        {{ __('All Alerts') }}
      </a>
      <a href="{{ route('tenant.notifications.index', ['filter' => 'unread']) }}" class="btn btn-sm {{ request('filter') === 'unread' ? 'btn-primary' : 'btn-light border' }}">
        {{ __('Unread') }} @if($unreadCount > 0)<span class="badge bg-danger ms-1">{{ $unreadCount }}</span>@endif
      </a>
      <a href="{{ route('tenant.notifications.index', ['filter' => 'read']) }}" class="btn btn-sm {{ request('filter') === 'read' ? 'btn-primary' : 'btn-light border' }}">
        {{ __('Read') }}
      </a>
    </div>
    <small class="text-muted">{{ __('Showing') }} {{ $notifications->count() }} {{ __('notifications') }}</small>
  </div>

  <div class="card-body p-0">
    <div class="list-group list-group-flush">
      @forelse($notifications as $notif)
        @php
          $data = $notif->data;
          $type = $data['type'] ?? 'system_alert';
          $isUnread = is_null($notif->read_at);

          $badgeColor = match($type) {
            'low_stock' => 'danger',
            'daily_sales_summary' => 'success',
            'daily_wastage_summary' => 'warning',
            'customer_balance_alert' => 'info',
            'supplier_payment_reminder' => 'primary',
            'subscription_expiry' => 'danger',
            default => 'secondary',
          };

          $icon = match($type) {
            'low_stock' => 'bi-exclamation-triangle',
            'daily_sales_summary' => 'bi-graph-up-arrow',
            'daily_wastage_summary' => 'bi-trash3',
            'customer_balance_alert' => 'bi-person-exclamation',
            'supplier_payment_reminder' => 'bi-truck',
            'subscription_expiry' => 'bi-clock-history',
            default => 'bi-bell',
          };
        @endphp
        <div class="list-group-item p-3 {{ $isUnread ? 'bg-light bg-opacity-50 border-start border-4 border-' . $badgeColor : '' }}">
          <div class="d-flex align-items-start justify-content-between gap-3">
            <div class="d-flex align-items-start gap-3 flex-grow-1">
              <div class="rounded-circle bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} p-2 fs-5 mt-1">
                <i class="bi {{ $icon }}"></i>
              </div>
              <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <h6 class="fw-bold mb-0 text-dark">{{ $data['title'] ?? 'System Notification' }}</h6>
                  @if($isUnread)
                    <span class="badge bg-danger-subtle text-danger" style="font-size: 10px;">{{ __('New') }}</span>
                  @endif
                </div>
                @if(!empty($data['title_ur']))
                  <div class="urdu-font text-muted small mb-1" style="font-family: 'Noto Nastaliq Urdu', serif;">
                    {{ $data['title_ur'] }}
                  </div>
                @endif
                <p class="text-secondary small mb-2">{{ $data['message'] ?? '' }}</p>
                <div class="d-flex align-items-center gap-3 font-monospace text-muted" style="font-size: 11px;">
                  <span><i class="bi bi-clock me-1"></i>{{ $notif->created_at->diffForHumans() }} ({{ $notif->created_at->format('d M Y, h:i A') }})</span>
                  @if(!empty($data['action_url']))
                    <a href="{{ url($data['action_url']) }}" class="text-decoration-none fw-bold text-primary">
                      {{ __('View Details') }} <i class="bi bi-arrow-right"></i>
                    </a>
                  @endif
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-1">
              @if($isUnread)
                <form method="POST" action="{{ route('tenant.notifications.read', $notif->id) }}">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-secondary py-1 px-2" title="{{ __('Mark as Read') }}">
                    <i class="bi bi-check2"></i>
                  </button>
                </form>
              @endif
              <form method="POST" action="{{ route('tenant.notifications.destroy', $notif->id) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="{{ __('Delete') }}" onclick="return confirm('{{ __('Delete notification?') }}')">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
      @empty
        <div class="p-5 text-center text-muted">
          <i class="bi bi-bell-slash fs-1 d-block mb-2 text-secondary"></i>
          <h6>{{ __('No notifications found.') }}</h6>
          <p class="small mb-0">{{ __('System and business alerts will appear here in real time.') }}</p>
        </div>
      @endforelse
    </div>
  </div>

  @if($notifications->hasPages())
    <div class="card-footer bg-white py-3">
      {{ $notifications->links() }}
    </div>
  @endif
</div>

<script>
function runRoutine(routineName) {
    const statusBox = document.getElementById('routineStatus');
    statusBox.classList.remove('d-none');
    statusBox.innerHTML = '<i class="bi bi-hourglass-split"></i> Running ' + routineName + ' automation scan...';

    fetch('{{ route("tenant.notifications.trigger") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ routine: routineName })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            statusBox.innerHTML = '<i class="bi bi-check-circle text-success"></i> ' + data.message;
            setTimeout(() => { location.reload(); }, 1200);
        } else {
            statusBox.innerHTML = '<i class="bi bi-exclamation-circle text-danger"></i> Failed to run automation.';
        }
    })
    .catch(err => {
        statusBox.innerHTML = '<i class="bi bi-exclamation-circle text-danger"></i> Network communication error.';
    });
}
</script>
@endsection

