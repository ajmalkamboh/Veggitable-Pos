@props([
    'type' => 'button',
    'variant' => 'primary', // primary, secondary, success, danger, warning, info, outline-primary etc.
    'size' => null, // sm, lg
    'icon' => null,
    'loading' => false,
])

<button 
    type="{{ $type }}" 
    {{ $attributes->merge(['class' => 'btn btn-' . $variant . ($size ? ' btn-' . $size : '') . ' d-inline-flex align-items-center justify-content-center gap-1']) }}
>
    @if($icon)
        <i class="bi {{ $icon }}"></i>
    @endif
    <span>{{ $slot }}</span>
</button>

