@props([
    'id',
    'title' => null,
    'size' => null, // sm, lg, xl
    'centered' => true,
    'footer' => null,
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog {{ $size ? 'modal-' . $size : '' }} {{ $centered ? 'modal-dialog-centered' : '' }}">
        <div class="modal-content border-0 shadow">
            @if($title)
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-semibold" id="{{ $id }}Label">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            @endif

            <div class="modal-body p-4">
                {{ $slot }}
            </div>

            @if($footer)
                <div class="modal-footer border-top py-2 bg-light">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
