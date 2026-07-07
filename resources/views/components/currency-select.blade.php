@props([
    'name' => 'currency',
    'value' => null,
    'options' => [],
    'required' => true,
    'id' => null,
])

@php
    $fieldId = $id ?? $name;
    $selected = old($name, $value);
@endphp

<select
    id="{{ $fieldId }}"
    name="{{ $name }}"
    @if ($required) required @endif
    {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100']) }}
>
    @foreach ($options as $code => $label)
        <option value="{{ $code }}" @selected((string) $selected === (string) $code)>{{ $label }}</option>
    @endforeach
</select>
