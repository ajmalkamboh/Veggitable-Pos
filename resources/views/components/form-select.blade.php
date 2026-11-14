@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
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

    <select 
        id="{{ $name }}" 
        name="{{ $name }}" 
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'form-select' . ($errors->has($name) ? ' is-invalid' : '')]) }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $val => $text)
            <option value="{{ $val }}" {{ (string) old($name, $selected) === (string) $val ? 'selected' : '' }}>
                {{ $text }}
            </option>
        @endforeach
    </select>

    @if($help && !$errors->has($name))
        <div class="form-text small text-muted">{{ $help }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback small">{{ $message }}</div>
    @enderror
</div>
