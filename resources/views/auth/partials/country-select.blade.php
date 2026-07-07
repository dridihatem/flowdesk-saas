@props([
    'countries',
    'name' => 'country',
    'id' => 'country',
    'value' => null,
    'required' => false,
])

@php
    $selected = old($name, $value);
@endphp

<select
    id="{{ $id }}"
    name="{{ $name }}"
    @if ($required) required @endif
    autocomplete="country"
    {{ $attributes->merge(['class' => 'flow-input-select mt-2 block w-full']) }}
>
    <option value="">{{ __('— Select country —') }}</option>
    @foreach ($countries as $code => $label)
        <option value="{{ $code }}" @selected((string) $selected === (string) $code)>{{ $label }}</option>
    @endforeach
</select>
