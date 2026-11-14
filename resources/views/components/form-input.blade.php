@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
])

<div class="mb-3">
    @if($label)
        <label for="{{ $name }}" class="form-label fw-medium small">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <input 
        type="{{ $type }}" 
        id="{{ $name }}" 
        name="{{ $name }}" 
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'form-control' . ($errors->has($name) ? ' is-invalid' : '')]) }}
    >

    @if($help && !$errors->has($name))
        <div class="form-text small text-muted">{{ $help }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback small">{{ $message }}</div>
    @enderror
</div>
