@props([
    'title',
    'value',
    'icon' => 'bi-bar-chart',
    'color' => 'primary',
    'change' => null,
    'changeType' => 'positive', // 'positive' or 'negative'
    'subtitle' => null,
])

<div class="card shadow-sm border-0 mb-4 stat-card">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted text-uppercase fw-semibold small">{{ $title }}</span>
            <div class="stat-icon-wrapper rounded-3 bg-{{ $color }}-subtle text-{{ $color }} p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                <i class="bi {{ $icon }} fs-4"></i>
            </div>
        </div>

        <div class="d-flex align-items-baseline justify-content-between">
            <h3 class="mb-0 fw-bold">{{ $value }}</h3>
            @if($change)
                <span class="badge bg-{{ $changeType === 'positive' ? 'success' : 'danger' }}-subtle text-{{ $changeType === 'positive' ? 'success' : 'danger' }} small">
                    <i class="bi bi-arrow-{{ $changeType === 'positive' ? 'up' : 'down' }}"></i>
                    {{ $change }}
                </span>
            @endif
        </div>

        @if($subtitle)
            <div class="text-muted small mt-2">{{ $subtitle }}</div>
        @endif
    </div>
</div>
