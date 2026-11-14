@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
    'footer' => null,
])

<div {{ $attributes->merge(['class' => 'card shadow-sm border-0 mb-4']) }}>
    @if($title || $actions)
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
            <div>
                @if($title)
                    <h5 class="card-title mb-0 fw-semibold">{{ $title }}</h5>
                @endif
                @if($subtitle)
                    <small class="text-muted">{{ $subtitle }}</small>
                @endif
            </div>
            @if($actions)
                <div class="card-actions d-flex align-items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="card-body">
        {{ $slot }}
    </div>

    @if($footer)
        <div class="card-footer bg-light py-2 border-top">
            {{ $footer }}
        </div>
    @endif
</div>

