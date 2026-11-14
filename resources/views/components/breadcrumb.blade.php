@props([
    'title' => '',
    'items' => [],
])

<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        @if($title)
            <h1 class="page-title fs-4 fw-bold mb-1">{{ $title }}</h1>
        @endif
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ url('/') }}" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i>{{ __('common.dashboard') }}</a></li>
                @foreach($items as $label => $url)
                    @if($loop->last)
                        <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                    @else
                        <li class="breadcrumb-item"><a href="{{ $url }}" class="text-decoration-none text-muted">{{ $label }}</a></li>
                    @endif
                @endforeach
            </ol>
        </nav>
    </div>
    @if(isset($actions))
        <div class="page-actions d-flex align-items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
